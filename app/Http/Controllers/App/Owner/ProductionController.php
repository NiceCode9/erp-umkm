<?php

namespace App\Http\Controllers\App\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductionController extends Controller
{
    public function __construct(
        private StockService $stockService
    ) {}

    public function index(): View
    {
        $orders = ProductionOrder::where('business_id', auth()->user()->business_id)
            ->with(['product', 'branch', 'user'])
            ->latest()
            ->paginate(15);

        return view('app.owner.production.index', compact('orders'));
    }

    public function create(): View
    {
        $branches = Branch::where('business_id', auth()->user()->business_id)
            ->where('is_active', true)->get();
        $products = Product::where('business_id', auth()->user()->business_id)
            ->whereHas('recipes')
            ->get();

        return view('app.owner.production.create', compact('branches', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'branch_id' => 'required|exists:branches,id',
            'quantity_target' => 'required|numeric|min:0.01',
            'expired_date' => 'nullable|date|after:today',
        ]);

        $businessId = auth()->user()->business_id;
        $userId = auth()->id();

        $product = Product::where('id', $validated['product_id'])
            ->where('business_id', $businessId)->firstOrFail();

        Branch::where('id', $validated['branch_id'])
            ->where('business_id', $businessId)->firstOrFail();

        // Pre-check stock availability
        $shortages = $this->stockService->checkProductionStockAvailability(
            $product->id, $validated['branch_id'], $validated['quantity_target']
        );

        if (!empty($shortages)) {
            $msg = collect($shortages)->map(fn ($s) =>
                "{$s->name}: butuh {$s->needed} {$s->unit}, tersedia {$s->available} {$s->unit} (kurang {$s->shortage})"
            )->implode('<br>');

            return back()
                ->withInput()
                ->with('error', "Stok bahan baku tidak mencukupi:<br>{$msg}");
        }

        $productionCode = $this->stockService->generateProductionCode();

        $order = ProductionOrder::create([
            'production_code' => $productionCode,
            'business_id' => $businessId,
            'branch_id' => $validated['branch_id'],
            'product_id' => $product->id,
            'user_id' => $userId,
            'quantity_target' => $validated['quantity_target'],
            'expired_date' => $validated['expired_date'] ?? null,
            'status' => 'draft',
        ]);

        try {
            $this->stockService->consumeRawMaterialsForProduction(
                productId: $product->id,
                branchId: $validated['branch_id'],
                businessId: $businessId,
                quantityTarget: $validated['quantity_target'],
                productionOrderId: $order->id,
                userId: $userId,
            );

            $this->stockService->addProductStockFromProduction(
                productId: $product->id,
                branchId: $validated['branch_id'],
                businessId: $businessId,
                quantity: $validated['quantity_target'],
                productionOrderId: $order->id,
                userId: $userId,
                expiredDate: $validated['expired_date'] ?? null,
            );

            $order->update([
                'status' => 'confirmed',
                'produced_at' => now(),
            ]);

            activity()
                ->performedOn($order)
                ->causedBy(auth()->user())
                ->withProperties([
                    'product' => $product->name,
                    'quantity' => $validated['quantity_target'],
                    'branch_id' => $validated['branch_id'],
                ])
                ->log('Production order confirmed');

        } catch (\Throwable $e) {
            $order->update(['status' => 'cancelled']);
            return back()
                ->withInput()
                ->with('error', 'Produksi gagal: ' . $e->getMessage());
        }

        return redirect()
            ->route('app.production.index')
            ->with('success', "Produksi {$product->name} ({$validated['quantity_target']} unit) berhasil.");
    }

    public function show(ProductionOrder $production): View
    {
        if ($production->business_id !== auth()->user()->business_id) abort(403);

        $production->load([
            'product', 'branch', 'user',
            'consumptions.rawMaterialBatch.rawMaterial',
        ]);

        return view('app.owner.production.show', compact('production'));
    }
}
