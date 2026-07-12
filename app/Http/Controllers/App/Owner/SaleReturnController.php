<?php

namespace App\Http\Controllers\App\Owner;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SaleReturnController extends Controller
{
    public function __construct(
        private StockService $stockService
    ) {}

    public function create(Sale $sale): View
    {
        if ($sale->business_id !== auth()->user()->business_id) abort(403);

        $sale->load(['items.product', 'items.batches.productBatch', 'returns']);

        return view('app.owner.sales.return', compact('sale'));
    }

    public function store(Request $request, Sale $sale): RedirectResponse
    {
        if ($sale->business_id !== auth()->user()->business_id) abort(403);

        $validated = $request->validate([
            'return_date' => 'required|date',
            'reason' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.sale_item_id' => 'required|exists:sale_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $user = auth()->user();

        DB::beginTransaction();
        try {
            $totalAmount = 0;
            $return = SaleReturn::create([
                'sale_id' => $sale->id,
                'business_id' => $sale->business_id,
                'branch_id' => $sale->branch_id,
                'user_id' => $user->id,
                'return_date' => $validated['return_date'],
                'reason' => $validated['reason'] ?? null,
                'total_amount' => 0,
            ]);

            foreach ($validated['items'] as $item) {
                $saleItem = SaleItem::where('id', $item['sale_item_id'])
                    ->where('sale_id', $sale->id)
                    ->firstOrFail();

                $qty = (float) $item['quantity'];
                $returnSubtotal = $qty * (float) $saleItem->unit_price;

                $returnItem = SaleReturnItem::create([
                    'sale_return_id' => $return->id,
                    'sale_item_id' => $saleItem->id,
                    'product_batch_id' => $saleItem->batches()->first()?->product_batch_id,
                    'quantity' => $qty,
                    'unit_price' => $saleItem->unit_price,
                    'subtotal' => $returnSubtotal,
                ]);

                $this->stockService->returnProductStockFromSale(
                    saleItemId: $saleItem->id,
                    quantityToReturn: $qty,
                    saleReturnItemId: $returnItem->id,
                    userId: $user->id,
                );

                $totalAmount += $returnSubtotal;
            }

            $return->update(['total_amount' => $totalAmount]);

            $this->stockService->recalculateSalePaymentStatus($sale);

            activity()
                ->performedOn($return)
                ->causedBy($user)
                ->withProperties([
                    'sale_invoice' => $sale->invoice_no,
                    'total_return' => $totalAmount,
                ])
                ->log('Sale return created');

            DB::commit();

            return redirect()
                ->route('app.owner.sales.show', $sale)
                ->with('success', "Retur penjualan berhasil. Total retur: " . format_currency($totalAmount));
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Retur gagal: ' . $e->getMessage());
        }
    }
}
