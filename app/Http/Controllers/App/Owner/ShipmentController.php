<?php

namespace App\Http\Controllers\App\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Sale;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ShipmentController extends Controller
{
    public function index(): View
    {
        $businessId = auth()->user()->business_id;

        $shipments = Shipment::where('business_id', $businessId)
            ->with(['branch', 'user', 'sale', 'items.product'])
            ->latest()
            ->paginate(15);

        return view('app.owner.shipments.index', compact('shipments'));
    }

    public function create(): View
    {
        $businessId = auth()->user()->business_id;

        $branches = Branch::where('business_id', $businessId)
            ->where('is_active', true)->orderBy('name')->get();

        $sales = Sale::where('business_id', $businessId)
            ->with(['items.product', 'branch', 'customer_name'])
            ->orderByDesc('created_at')
            ->get();

        $products = \App\Models\Product::where('business_id', $businessId)->orderBy('name')->get();

        return view('app.owner.shipments.create', compact('branches', 'sales', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $businessId = auth()->user()->business_id;
        $userId = auth()->id();

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'sale_id' => 'nullable|exists:sales,id',
            'type' => 'required|in:ecer,borongan',
            'destination' => 'required|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.sale_item_id' => 'nullable|exists:sale_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        Branch::where('id', $validated['branch_id'])->where('business_id', $businessId)->firstOrFail();

        if ($validated['sale_id']) {
            $sale = Sale::where('id', $validated['sale_id'])->where('business_id', $businessId)->firstOrFail();
        }

        $shipment = DB::transaction(function () use ($validated, $businessId, $userId) {
            $shipment = Shipment::create([
                'business_id' => $businessId,
                'branch_id' => $validated['branch_id'],
                'sale_id' => $validated['sale_id'] ?? null,
                'user_id' => $userId,
                'type' => $validated['type'],
                'destination' => $validated['destination'],
                'status' => 'pending',
            ]);

            foreach ($validated['items'] as $item) {
                ShipmentItem::create([
                    'shipment_id' => $shipment->id,
                    'product_id' => $item['product_id'],
                    'sale_item_id' => $item['sale_item_id'] ?? null,
                    'quantity' => $item['quantity'],
                ]);
            }

            return $shipment;
        });

        activity()
            ->performedOn($shipment)
            ->causedBy(auth()->user())
            ->log('Shipment created');

        return redirect()
            ->route('app.owner.shipments.index')
            ->with('success', 'Pengiriman berhasil dibuat.');
    }

    public function show(Shipment $shipment): View
    {
        if ($shipment->business_id !== auth()->user()->business_id) abort(403);

        $shipment->load(['branch', 'user', 'sale', 'items.product', 'items.saleItem']);

        return view('app.owner.shipments.show', compact('shipment'));
    }

    public function updateStatus(Request $request, Shipment $shipment): RedirectResponse
    {
        if ($shipment->business_id !== auth()->user()->business_id) abort(403);

        $validated = $request->validate([
            'status' => 'required|in:shipped,delivered',
        ]);

        $now = now();

        $updates = ['status' => $validated['status']];
        if ($validated['status'] === 'shipped') {
            $updates['shipped_at'] = $now;
        } elseif ($validated['status'] === 'delivered') {
            $updates['delivered_at'] = $now;
        }

        $shipment->update($updates);

        activity()
            ->performedOn($shipment)
            ->causedBy(auth()->user())
            ->withProperties(['status' => $validated['status']])
            ->log('Shipment status updated');

        return redirect()
            ->route('app.owner.shipments.show', $shipment)
            ->with('success', "Status pengiriman berhasil diubah ke {$validated['status']}.");
    }

    public function saleItems(Sale $sale)
    {
        if ($sale->business_id !== auth()->user()->business_id) abort(403);

        $sale->load('items.product');

        return response()->json(
            $sale->items->map(fn ($i) => [
                'sale_item_id' => $i->id,
                'product_id' => $i->product_id,
                'product_name' => $i->product->name,
                'quantity_sold' => (float) $i->quantity,
                'unit_price' => (float) $i->unit_price,
            ])
        );
    }
}
