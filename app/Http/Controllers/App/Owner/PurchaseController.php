<?php

namespace App\Http\Controllers\App\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchasePayment;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\RawMaterial;
use App\Models\Supplier;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function __construct(
        private StockService $stockService
    ) {}

    public function index(): View
    {
        $purchases = Purchase::where('business_id', auth()->user()->business_id)
            ->with(['supplier', 'branch', 'items.rawMaterial'])
            ->latest()
            ->paginate(15);

        return view('app.owner.purchases.index', compact('purchases'));
    }

    public function create(): View
    {
        $branches = Branch::where('business_id', auth()->user()->business_id)
            ->where('is_active', true)->get();
        $suppliers = Supplier::where('business_id', auth()->user()->business_id)->get();
        $rawMaterials = RawMaterial::where('business_id', auth()->user()->business_id)->get();

        return view('app.owner.purchases.create', compact('branches', 'suppliers', 'rawMaterials'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_no' => 'required|string|max:255',
            'purchase_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.raw_material_id' => 'required|exists:raw_materials,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.batch_no' => 'required|string|max:255',
            'items.*.expired_date' => 'nullable|date',
        ]);

        // Verify branch belongs to user's business
        $branch = Branch::where('id', $validated['branch_id'])
            ->where('business_id', auth()->user()->business_id)
            ->firstOrFail();

        $businessId = auth()->user()->business_id;
        $userId = auth()->id();

        $purchase = DB::transaction(function () use ($validated, $businessId, $branch, $userId) {
            $items = collect($validated['items']);
            $totalAmount = $items->sum(fn ($i) => $i['quantity'] * $i['unit_price']);

            $purchase = Purchase::create([
                'business_id' => $businessId,
                'branch_id' => $branch->id,
                'supplier_id' => $validated['supplier_id'],
                'user_id' => $userId,
                'invoice_no' => $validated['invoice_no'],
                'purchase_date' => $validated['purchase_date'],
                'total_amount' => $totalAmount,
                'payment_status' => 'unpaid',
            ]);

            foreach ($validated['items'] as $item) {
                $subtotal = $item['quantity'] * $item['unit_price'];

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'raw_material_id' => $item['raw_material_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,
                    'batch_no' => $item['batch_no'],
                    'expired_date' => $item['expired_date'] ?? null,
                ]);

                $this->stockService->increaseRawMaterialStock(
                    rawMaterialId: $item['raw_material_id'],
                    branchId: $branch->id,
                    businessId: $businessId,
                    batchNo: $item['batch_no'],
                    quantity: $item['quantity'],
                    purchasePrice: $item['unit_price'],
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
        if ($purchase->business_id !== auth()->user()->business_id) abort(403);

        $purchase->load(['supplier', 'branch', 'items.rawMaterial', 'payments', 'returns.items.rawMaterialBatch.rawMaterial']);

        return view('app.owner.purchases.show', compact('purchase'));
    }

    public function payForm(Purchase $purchase): View
    {
        if ($purchase->business_id !== auth()->user()->business_id) abort(403);

        return view('app.owner.purchases.pay', compact('purchase'));
    }

    public function payStore(Request $request, Purchase $purchase): RedirectResponse
    {
        if ($purchase->business_id !== auth()->user()->business_id) abort(403);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'paid_at' => 'required|date',
            'method' => 'required|string|max:50',
        ]);

        DB::transaction(function () use ($validated, $purchase) {
            PurchasePayment::create([
                'purchase_id' => $purchase->id,
                'amount' => $validated['amount'],
                'paid_at' => $validated['paid_at'],
                'method' => $validated['method'],
            ]);

            $totalPaid = $purchase->payments()->sum('amount') + $validated['amount'];

            $purchase->update([
                'payment_status' => $totalPaid >= $purchase->total_amount ? 'paid' : 'partial',
            ]);
        });

        activity()
            ->performedOn($purchase)
            ->causedBy(auth()->user())
            ->log('Purchase payment recorded');

        return redirect()
            ->route('app.purchases.show', $purchase)
            ->with('success', 'Pembayaran berhasil dicatat.');
    }

    public function returnForm(Purchase $purchase): View
    {
        if ($purchase->business_id !== auth()->user()->business_id) abort(403);

        $purchase->load(['items.rawMaterial']);
        $branches = Branch::where('business_id', auth()->user()->business_id)
            ->where('is_active', true)->get();

        return view('app.owner.purchases.return', compact('purchase', 'branches'));
    }

    public function returnStore(Request $request, Purchase $purchase): RedirectResponse
    {
        if ($purchase->business_id !== auth()->user()->business_id) abort(403);

        $validated = $request->validate([
            'return_date' => 'required|date',
            'reason' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.purchase_item_id' => 'required|exists:purchase_items,id',
            'items.*.raw_material_batch_id' => 'required|exists:raw_material_batches,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $purchase) {
            $purchaseReturn = PurchaseReturn::create([
                'purchase_id' => $purchase->id,
                'business_id' => $purchase->business_id,
                'branch_id' => $purchase->branch_id,
                'user_id' => auth()->id(),
                'return_date' => $validated['return_date'],
                'reason' => $validated['reason'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $subtotal = $item['quantity'] * $item['unit_price'];

                PurchaseReturnItem::create([
                    'purchase_return_id' => $purchaseReturn->id,
                    'raw_material_batch_id' => $item['raw_material_batch_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,
                ]);

                $this->stockService->decreaseRawMaterialStockFromBatch(
                    batchId: $item['raw_material_batch_id'],
                    quantity: $item['quantity'],
                    referenceType: 'purchase_return',
                    referenceId: $purchaseReturn->id,
                    userId: auth()->id(),
                );
            }

            activity()
                ->performedOn($purchase)
                ->causedBy(auth()->user())
                ->log('Purchase return created');
        });

        return redirect()
            ->route('app.purchases.show', $purchase)
            ->with('success', 'Retur pembelian berhasil dicatat.');
    }
}
