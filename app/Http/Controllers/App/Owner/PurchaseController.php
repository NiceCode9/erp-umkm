<?php

namespace App\Http\Controllers\App\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchSetting;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\RawMaterial;
use App\Models\RawMaterialBatch;
use App\Models\Supplier;
use App\Services\PurchaseService;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function __construct(
        private StockService $stockService,
        private PurchaseService $purchaseService,
    ) {}

    public function index(): View
    {
        $purchases = Purchase::where('business_id', auth()->user()->business_id)
            ->with(['supplier', 'branch', 'items.rawMaterial'])
            ->withSum(['payments as paid_amount'], 'amount')
            ->withSum(['returns as returned_amount'], 'total_amount')
            ->latest()
            ->paginate(15);

        $purchases->getCollection()->each(function (Purchase $purchase) {
            $purchase->outstanding_amount = (float) $purchase->total_amount
                - (float) $purchase->paid_amount
                - (float) $purchase->returned_amount;
        });

        return view('app.owner.purchases.index', compact('purchases'));
    }

    public function create(): View
    {
        $businessId = auth()->user()->business_id;

        $branches = Branch::where('business_id', $businessId)->where('is_active', true)->orderBy('name')->get();
        $suppliers = Supplier::where('business_id', $businessId)->orderBy('name')->get();
        $rawMaterials = RawMaterial::where('business_id', $businessId)->orderBy('name')->get();

        $branchSettings = BranchSetting::whereIn('branch_id', $branches->pluck('id'))->get()->keyBy('branch_id');

        return view('app.owner.purchases.create', compact('branches', 'suppliers', 'rawMaterials', 'branchSettings'));
    }

    public function store(Request $request): RedirectResponse
    {
        $businessId = auth()->user()->business_id;

        $validated = $request->validate([
            'branch_id' => [
                'required',
                Rule::exists('branches', 'id')->where('business_id', $businessId),
            ],
            'supplier_id' => [
                'required',
                Rule::exists('suppliers', 'id')->where('business_id', $businessId),
            ],
            'invoice_no' => 'required|string|max:255',
            'purchase_date' => 'required|date',
            'discount_type' => 'nullable|in:nominal,percent',
            'discount_value' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.raw_material_id' => [
                'required',
                Rule::exists('raw_materials', 'id')->where('business_id', $businessId),
            ],
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.batch_no' => 'required|string|max:255',
            'items.*.expired_date' => 'nullable|date',
        ]);

        $branch = Branch::where('id', $validated['branch_id'])
            ->where('business_id', $businessId)
            ->firstOrFail();

        $userId = auth()->id();

        $purchase = DB::transaction(function () use ($validated, $businessId, $branch, $userId) {
            $items = collect($validated['items']);

            // Snapshot diskon & pajak (AGENTS.md bagian 6).
            $subtotal = round($items->sum(fn ($i) => (float) $i['quantity'] * (float) $i['unit_price']), 2);

            $discountAmount = 0.0;
            if (! empty($validated['discount_type']) && ! empty($validated['discount_value'])) {
                $discountAmount = $validated['discount_type'] === 'percent'
                    ? $subtotal * ((float) $validated['discount_value'] / 100)
                    : (float) $validated['discount_value'];
                $discountAmount = round(min($discountAmount, $subtotal), 2);
            }

            $taxBase = $subtotal - $discountAmount;
            $branchSetting = BranchSetting::where('branch_id', $branch->id)->first();
            $taxPercentage = null;
            $taxAmount = 0.0;

            if ($branchSetting && $branchSetting->tax_enabled) {
                $taxPercentage = (float) $branchSetting->tax_percentage;
                $taxAmount = round($taxBase * ($taxPercentage / 100), 2);
            }

            $totalAmount = round($taxBase + $taxAmount, 2);

            $purchase = Purchase::create([
                'business_id' => $businessId,
                'branch_id' => $branch->id,
                'supplier_id' => $validated['supplier_id'],
                'user_id' => $userId,
                'invoice_no' => $validated['invoice_no'],
                'purchase_date' => $validated['purchase_date'],
                'subtotal' => $subtotal,
                'discount_type' => $validated['discount_type'] ?? null,
                'discount_value' => $validated['discount_value'] ?? null,
                'discount_amount' => $discountAmount,
                'tax_percentage_applied' => $taxPercentage,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'payment_status' => 'unpaid',
            ]);

            foreach ($validated['items'] as $item) {
                $subtotalLine = round((float) $item['quantity'] * (float) $item['unit_price'], 2);

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'raw_material_id' => $item['raw_material_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotalLine,
                    'batch_no' => $item['batch_no'],
                    'expired_date' => $item['expired_date'] ?? null,
                ]);

                $this->stockService->increaseRawMaterialStock(
                    rawMaterialId: $item['raw_material_id'],
                    branchId: $branch->id,
                    businessId: $businessId,
                    batchNo: $item['batch_no'],
                    quantity: (float) $item['quantity'],
                    purchasePrice: (float) $item['unit_price'],
                    expiredDate: $item['expired_date'] ?? null,
                    referenceType: 'purchase',
                    referenceId: $purchase->id,
                    userId: $userId,
                );
            }

            activity()
                ->performedOn($purchase)
                ->causedBy(auth()->user())
                ->withProperties(['total' => $totalAmount, 'items' => count($validated['items'])])
                ->log('Purchase created');

            return $purchase;
        });

        return redirect()
            ->route('app.purchases.index')
            ->with('success', "Pembelian #{$purchase->invoice_no} berhasil dicatat.");
    }

    public function show(Purchase $purchase): View
    {
        $this->authorize('view', $purchase);

        $purchase->load([
            'supplier', 'branch',
            'items.rawMaterial',
            'payments', 'returns.items.rawMaterialBatch.rawMaterial',
        ]);

        $purchase->paid_amount = (float) $purchase->payments()->sum('amount');
        $purchase->returned_amount = (float) $purchase->returns()->sum('total_amount');
        $purchase->outstanding_amount = (float) $purchase->total_amount
            - $purchase->paid_amount
            - $purchase->returned_amount;

        return view('app.owner.purchases.show', compact('purchase'));
    }

    public function payForm(Purchase $purchase): View
    {
        $this->authorize('pay', $purchase);

        $purchase->paid_amount = (float) $purchase->payments()->sum('amount');
        $purchase->returned_amount = (float) $purchase->returns()->sum('total_amount');
        $purchase->outstanding_amount = (float) $purchase->total_amount
            - $purchase->paid_amount
            - $purchase->returned_amount;

        return view('app.owner.purchases.pay', compact('purchase'));
    }

    public function payStore(Request $request, Purchase $purchase): RedirectResponse
    {
        $this->authorize('pay', $purchase);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'paid_at' => 'required|date',
            'method' => 'required|string|max:50',
        ]);

        $userId = auth()->id();

        DB::transaction(function () use ($validated, $purchase, $userId) {
            $this->purchaseService->recordPayment(
                $purchase,
                (float) $validated['amount'],
                $validated['paid_at'],
                $validated['method'],
            );

            $this->purchaseService->recalculatePaymentStatus($purchase);
        });

        activity()
            ->performedOn($purchase)
            ->causedBy(auth()->user())
            ->withProperties(['amount' => $validated['amount'], 'method' => $validated['method']])
            ->log('Purchase payment recorded');

        return redirect()
            ->route('app.purchases.show', $purchase)
            ->with('success', 'Pembayaran berhasil dicatat.');
    }

    /**
     * Form retur. Batch yang bisa diretur diambil di sini (bukan di Blade) dan
     * sudah ter-scope tenant + hanya yang masih punya sisa stok.
     */
    public function returnForm(Purchase $purchase): View
    {
        $this->authorize('returnPurchase', $purchase);

        $purchase->load(['items.rawMaterial']);

        // Kunci item retur: item_id => ['item' => PurchaseItem, 'batches' => Collection]
        $returnable = new Collection();

        foreach ($purchase->items as $item) {
            $batches = RawMaterialBatch::where('raw_material_id', $item->raw_material_id)
                ->where('branch_id', $purchase->branch_id)
                ->where('quantity_remaining', '>', 0)
                ->orderByRaw("COALESCE(expired_date, '9999-12-31') ASC")
                ->get();

            if ($batches->isNotEmpty() && $item->returnableQuantity() > 0) {
                $returnable->put($item->id, ['item' => $item, 'batches' => $batches]);
            }
        }

        return view('app.owner.purchases.return', compact('purchase', 'returnable'));
    }

    public function returnStore(Request $request, Purchase $purchase): RedirectResponse
    {
        $this->authorize('returnPurchase', $purchase);

        $validated = $request->validate([
            'return_date' => 'required|date',
            'reason' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.purchase_item_id' => [
                'required',
                Rule::exists('purchase_items', 'id')->where('purchase_id', $purchase->id),
            ],
            'items.*.raw_material_batch_id' => 'required|exists:raw_material_batches,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $userId = auth()->id();

        try {
            $return = $this->purchaseService->recordReturn($purchase, $validated, $userId);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', 'Retur gagal: ' . $e->getMessage());
        }

        activity()
            ->performedOn($purchase)
            ->causedBy(auth()->user())
            ->withProperties(['return_id' => $return->id, 'total' => $return->total_amount])
            ->log('Purchase return created');

        return redirect()
            ->route('app.purchases.show', $purchase)
            ->with('success', "Retur pembelian #{$return->id} berhasil dicatat (Rp " . number_format((float) $return->total_amount, 0, ',', '.') . ").");
    }
}