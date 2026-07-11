<?php

namespace App\Http\Controllers\App\Kasir;

use App\Http\Controllers\Controller;
use App\Models\BranchSetting;
use App\Models\CashierShift;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function __construct(
        private StockService $stockService
    ) {}

    public function pos(): View
    {
        $user = auth()->user();

        $products = Product::where('business_id', $user->business_id)
            ->with(['units', 'media'])
            ->whereHas('batches', function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id)
                    ->where('quantity_remaining', '>', 0);
            })
            ->orderBy('name')
            ->get()
            ->map(function ($p) use ($user) {
                $stock = $this->stockService->getProductStock($p->id, $user->branch_id);
                $p->available_stock = $stock;
                return $p;
            });

        $shift = CashierShift::where('user_id', $user->id)
            ->where('branch_id', $user->branch_id)
            ->whereNull('closed_at')
            ->first();

        $branchSetting = BranchSetting::where('branch_id', $user->branch_id)->first();

        return view('app.kasir.pos.index', compact('products', 'shift', 'branchSetting'));
    }

    public function checkout(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $items = json_decode($request->input('items', '[]'), true);
        if (!is_array($items) || count($items) === 0) {
            return back()->with('error', 'Keranjang belanja kosong.');
        }

        $request->merge(['items' => $items]);

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_unit_id' => 'nullable|exists:product_units,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'discount_type' => 'nullable|in:nominal,percent',
            'discount_value' => 'nullable|numeric|min:0',
            'payment_method' => 'required|string|max:50',
            'customer_name' => 'nullable|string|max:255',
        ]);

        $shift = CashierShift::where('user_id', $user->id)
            ->where('branch_id', $user->branch_id)
            ->whereNull('closed_at')
            ->firstOrFail();

        $branchSetting = BranchSetting::where('branch_id', $user->branch_id)->first();

        $subtotal = 0;
        $itemData = [];

        DB::beginTransaction();
        try {
            $invoiceNo = $this->stockService->generateInvoiceNo();

            $sale = Sale::create([
                'business_id' => $user->business_id,
                'branch_id' => $user->branch_id,
                'user_id' => $user->id,
                'customer_name' => $validated['customer_name'] ?? null,
                'cashier_shift_id' => $shift->id,
                'invoice_no' => $invoiceNo,
                'sale_date' => now(),
                'subtotal' => 0,
                'discount_type' => $validated['discount_type'] ?? null,
                'discount_value' => $validated['discount_value'] ?? null,
                'discount_amount' => 0,
                'tax_percentage_applied' => null,
                'tax_amount' => 0,
                'total_amount' => 0,
                'payment_status' => 'paid',
                'payment_method' => $validated['payment_method'],
            ]);

            foreach ($validated['items'] as $item) {
                $product = Product::where('id', $item['product_id'])
                    ->where('business_id', $user->business_id)
                    ->firstOrFail();

                $qtyInBaseUnit = (float) $item['quantity'];

                if (!empty($item['product_unit_id'])) {
                    $productUnit = ProductUnit::where('id', $item['product_unit_id'])
                        ->where('product_id', $product->id)
                        ->firstOrFail();
                    $qtyInBaseUnit *= (float) $productUnit->conversion_to_base;
                }

                $stockAvailable = $this->stockService->checkProductStockAvailability(
                    $product->id, $user->branch_id
                );

                if ($stockAvailable < $qtyInBaseUnit) {
                    throw new \InvalidArgumentException(
                        "Stok {$product->name} tidak mencukupi. Tersedia: {$stockAvailable}, diminta: {$qtyInBaseUnit}"
                    );
                }

                $lineSubtotal = $qtyInBaseUnit * (float) $item['unit_price'];

                $saleItem = SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'product_unit_id' => $item['product_unit_id'] ?? null,
                    'quantity' => $qtyInBaseUnit,
                    'unit_price' => (float) $item['unit_price'],
                    'subtotal' => $lineSubtotal,
                ]);

                $this->stockService->consumeProductStockForSale(
                    productId: $product->id,
                    branchId: $user->branch_id,
                    businessId: $user->business_id,
                    quantityNeeded: $qtyInBaseUnit,
                    saleItemId: $saleItem->id,
                    userId: $user->id,
                );

                $subtotal += $lineSubtotal;
                $itemData[] = [
                    'product' => $product,
                    'qty' => $qtyInBaseUnit,
                    'subtotal' => $lineSubtotal,
                ];
            }

            $discountAmount = 0;
            if (!empty($validated['discount_type']) && !empty($validated['discount_value'])) {
                if ($validated['discount_type'] === 'percent') {
                    $discountAmount = $subtotal * ((float) $validated['discount_value'] / 100);
                } else {
                    $discountAmount = (float) $validated['discount_value'];
                }
                $discountAmount = min($discountAmount, $subtotal);
            }

            $taxBase = $subtotal - $discountAmount;
            $taxPercentage = null;
            $taxAmount = 0;

            if ($branchSetting && $branchSetting->tax_enabled) {
                $taxPercentage = (float) $branchSetting->tax_percentage;
                $taxAmount = $taxBase * ($taxPercentage / 100);
            }

            $totalAmount = $taxBase + $taxAmount;

            $sale->update([
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_percentage_applied' => $taxPercentage,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
            ]);

            activity()
                ->performedOn($sale)
                ->causedBy($user)
                ->withProperties([
                    'invoice_no' => $invoiceNo,
                    'total' => $totalAmount,
                    'items' => count($validated['items']),
                ])
                ->log('Sale created');

            DB::commit();

            return redirect()->route('app.kasir.sales.receipt', $sale)
                ->with('success', "Transaksi #{$invoiceNo} berhasil!");

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Transaksi gagal: ' . $e->getMessage());
        }
    }

    public function receipt(Sale $sale): View
    {
        if ($sale->user_id !== auth()->id() && !auth()->user()->hasRole('Owner')) {
            abort(403);
        }

        $sale->load(['items.product', 'items.productUnit', 'items.batches.productBatch', 'branch']);

        return view('app.kasir.sales.receipt', compact('sale'));
    }

    public function history(): View
    {
        $user = auth()->user();

        $sales = Sale::where('user_id', $user->id)
            ->where('business_id', $user->business_id)
            ->with(['items.product', 'branch'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('app.kasir.sales.index', compact('sales'));
    }
}
