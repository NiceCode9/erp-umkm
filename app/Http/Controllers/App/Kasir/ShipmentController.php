<?php

namespace App\Http\Controllers\App\Kasir;

use App\Http\Controllers\Controller;
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
        $user = auth()->user();

        $shipments = Shipment::where('shipments.business_id', $user->business_id)
            ->where('shipments.branch_id', $user->branch_id)
            ->where(function ($q) use ($user) {
                $q->where('shipments.user_id', $user->id)
                  ->orWhereHas('sale', fn ($sq) => $sq->where('user_id', $user->id));
            })
            ->with(['branch', 'user', 'sale', 'items.product'])
            ->latest()
            ->paginate(15);

        return view('app.kasir.shipments.index', compact('shipments'));
    }

    public function createFromSale(Sale $sale): View
    {
        $user = auth()->user();

        if ($sale->business_id !== $user->business_id || $sale->branch_id !== $user->branch_id) {
            abort(403);
        }

        if ($sale->user_id !== $user->id) {
            abort(403, 'Anda hanya bisa membuat pengiriman untuk transaksi Anda sendiri.');
        }

        $sale->load('items.product');

        return view('app.kasir.shipments.create', compact('sale'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'type' => 'required|in:ecer,borongan',
            'destination' => 'required|string|max:1000',
            'recipient_name' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.sale_item_id' => 'required|exists:sale_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $sale = Sale::where('id', $validated['sale_id'])
            ->where('business_id', $user->business_id)
            ->where('branch_id', $user->branch_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $shipment = DB::transaction(function () use ($validated, $user, $sale) {
            $shipment = Shipment::create([
                'business_id' => $user->business_id,
                'branch_id' => $user->branch_id,
                'sale_id' => $sale->id,
                'user_id' => $user->id,
                'type' => $validated['type'],
                'destination' => $validated['destination'],
                'recipient_name' => $validated['recipient_name'],
                'status' => 'pending',
            ]);

            foreach ($validated['items'] as $item) {
                ShipmentItem::create([
                    'shipment_id' => $shipment->id,
                    'product_id' => $item['product_id'],
                    'sale_item_id' => $item['sale_item_id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            return $shipment;
        });

        activity()
            ->performedOn($shipment)
            ->causedBy($user)
            ->log('Shipment created by kasir');

        return redirect()
            ->route('app.kasir.shipments.index')
            ->with('success', 'Pengiriman berhasil dibuat.');
    }

    public function show(Shipment $shipment): View
    {
        $user = auth()->user();

        if ($shipment->business_id !== $user->business_id || $shipment->branch_id !== $user->branch_id) {
            abort(403);
        }

        $ownSale = $shipment->sale_id && $shipment->sale?->user_id === $user->id;
        $ownShipment = $shipment->user_id === $user->id;

        if (!$ownSale && !$ownShipment) {
            abort(403);
        }

        $shipment->load(['branch', 'user', 'sale', 'items.product']);

        return view('app.kasir.shipments.show', compact('shipment'));
    }

    public function saleItems(Sale $sale)
    {
        $user = auth()->user();

        if ($sale->business_id !== $user->business_id || $sale->branch_id !== $user->branch_id) {
            abort(403);
        }

        if ($sale->user_id !== $user->id) {
            abort(403);
        }

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
