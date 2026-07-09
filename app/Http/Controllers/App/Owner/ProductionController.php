<?php

namespace App\Http\Controllers\App\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\Recipe;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductionController extends Controller
{
    public function __construct(
        private StockService $stockService
    ) {}

    public function index(): View
    {
        $orders = ProductionOrder::where('business_id', auth()->user()->business_id)
            ->with(['product', 'branch', 'user', 'recipe'])
            ->latest()
            ->paginate(15);

        return view('app.owner.production.index', compact('orders'));
    }

    public function create(): View
    {
        $branches = Branch::where('business_id', auth()->user()->business_id)
            ->where('is_active', true)->get();
        $products = Product::where('business_id', auth()->user()->business_id)
            ->whereHas('recipes', fn ($q) => $q->where('is_active', true))
            ->get();

        return view('app.owner.production.create', compact('branches', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'recipe_id' => 'required|exists:recipes,id',
            'branch_id' => 'required|exists:branches,id',
            'batch_multiplier' => 'required|numeric|min:0.01',
            'expired_date' => 'nullable|date|after:today',
        ]);

        $businessId = auth()->user()->business_id;
        $userId = auth()->id();

        $product = Product::where('id', $validated['product_id'])
            ->where('business_id', $businessId)->firstOrFail();

        Branch::where('id', $validated['branch_id'])
            ->where('business_id', $businessId)->firstOrFail();

        $recipe = Recipe::where('id', $validated['recipe_id'])
            ->where('product_id', $product->id)->firstOrFail();

        $quantityTarget = $recipe->yield_quantity * $validated['batch_multiplier'];

        // Pre-check stock
        $shortages = $this->stockService->checkProductionStockAvailability(
            $recipe->id, $validated['branch_id'], $validated['batch_multiplier']
        );

        if (!empty($shortages)) {
            $msg = collect($shortages)->map(fn ($s) =>
                "{$s->name}: butuh {$s->needed} {$s->unit}, tersedia {$s->available} {$s->unit} (kurang {$s->shortage})"
            )->implode('<br>');

            return back()->withInput()->with('error', "Stok bahan baku tidak mencukupi:<br>{$msg}");
        }

        $productionCode = $this->stockService->generateProductionCode();

        $order = ProductionOrder::create([
            'production_code' => $productionCode,
            'business_id' => $businessId,
            'branch_id' => $validated['branch_id'],
            'product_id' => $product->id,
            'recipe_id' => $recipe->id,
            'user_id' => $userId,
            'quantity_target' => $quantityTarget,
            'batch_multiplier' => $validated['batch_multiplier'],
            'expired_date' => $validated['expired_date'] ?? null,
            'status' => 'draft',
        ]);

        try {
            $this->stockService->consumeRawMaterialsForProduction(
                recipeId: $recipe->id,
                branchId: $validated['branch_id'],
                businessId: $businessId,
                batchMultiplier: $validated['batch_multiplier'],
                productionOrderId: $order->id,
                userId: $userId,
            );

            $this->stockService->addProductStockFromProduction(
                productId: $product->id,
                branchId: $validated['branch_id'],
                businessId: $businessId,
                quantity: $quantityTarget,
                productionOrderId: $order->id,
                userId: $userId,
                expiredDate: $validated['expired_date'] ?? null,
            );

            $order->update(['status' => 'confirmed', 'produced_at' => now()]);

            activity()
                ->performedOn($order)
                ->causedBy(auth()->user())
                ->withProperties([
                    'product' => $product->name,
                    'recipe' => $recipe->name,
                    'quantity' => $quantityTarget,
                    'batch_multiplier' => $validated['batch_multiplier'],
                ])
                ->log('Production order confirmed');

        } catch (\Throwable $e) {
            $order->update(['status' => 'cancelled']);
            return back()->withInput()->with('error', 'Produksi gagal: ' . $e->getMessage());
        }

        return redirect()
            ->route('app.production.index')
            ->with('success', "Produksi {$product->name} ({$quantityTarget} unit) berhasil.");
    }

    public function show(ProductionOrder $production): View
    {
        if ($production->business_id !== auth()->user()->business_id) abort(403);

        $production->load([
            'product', 'branch', 'user', 'recipe',
            'consumptions.rawMaterialBatch.rawMaterial',
        ]);

        return view('app.owner.production.show', compact('production'));
    }

    public function getRecipes(Product $product)
    {
        if ($product->business_id !== auth()->user()->business_id) abort(403);

        $recipes = $product->recipes()
            ->where('is_active', true)
            ->with('items.rawMaterial')
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'yield_quantity' => (float) $r->yield_quantity,
                'items' => $r->items->map(fn ($i) => [
                    'raw_material_name' => $i->rawMaterial->name,
                    'qty_per_batch' => (float) $i->qty_per_batch,
                    'unit' => $i->unit,
                ]),
            ]);

        return response()->json($recipes);
    }
}
