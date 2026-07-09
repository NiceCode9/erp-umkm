<?php

namespace App\Http\Controllers\App\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductUnit;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $businessId = auth()->user()->business_id;

        $products = Product::where('business_id', $businessId)
            ->with('units')
            ->latest()
            ->paginate(15);

        $stockData = ProductBatch::whereIn('product_id', $products->pluck('id'))
            ->selectRaw('product_id, SUM(quantity_remaining) as total_stock')
            ->groupBy('product_id')
            ->pluck('total_stock', 'product_id');

        return view('app.owner.products.index', compact('products', 'stockData'));
    }

    public function show(Product $product): View
    {
        if ($product->business_id !== auth()->user()->business_id) abort(403);

        $businessId = auth()->user()->business_id;
        $branches = Branch::where('business_id', $businessId)->where('is_active', true)->orderBy('name')->get();

        $branchStocks = ProductBatch::where('product_id', $product->id)
            ->whereIn('branch_id', $branches->pluck('id'))
            ->selectRaw('branch_id, SUM(quantity_remaining) as total_stock')
            ->groupBy('branch_id')
            ->pluck('total_stock', 'branch_id');

        $branchData = $branches->map(fn ($b) => (object) [
            'name' => $b->name,
            'stock' => (float) ($branchStocks->get($b->id) ?? 0),
        ]);

        $movements = StockMovement::where('item_type', 'product')
            ->where('item_id', $product->id)
            ->whereIn('branch_id', $branches->pluck('id'))
            ->with(['branch', 'creator'])
            ->orderByDesc('created_at')
            ->paginate(20);

        $product->load('units', 'recipes.items.rawMaterial');
        $units = $product->units;

        return view('app.owner.products.show', compact('product', 'branchData', 'movements', 'units'));
    }

    public function create(): View
    {
        return view('app.owner.products.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'base_unit' => 'required|string|max:50',
            'selling_price' => 'required|numeric|min:0',
            'minimum_stock' => 'nullable|numeric|min:0',
            'halal_cert_number' => 'nullable|string|max:255',
            'halal_cert_issuer' => 'nullable|string|max:255',
            'halal_cert_expired_date' => 'nullable|date',
            'units' => 'nullable|array',
            'units.*.unit_name' => 'required_with:units|string|max:50',
            'units.*.conversion_to_base' => 'required_with:units|numeric|min:0.01',
            'units.*.price_override' => 'nullable|numeric|min:0',
        ]);

        $product = DB::transaction(function () use ($validated) {
            $product = Product::create([
                'name' => $validated['name'],
                'sku' => $validated['sku'] ?? null,
                'base_unit' => $validated['base_unit'],
                'selling_price' => $validated['selling_price'],
                'minimum_stock' => $validated['minimum_stock'] ?? 0,
                'halal_cert_number' => $validated['halal_cert_number'] ?? null,
                'halal_cert_issuer' => $validated['halal_cert_issuer'] ?? null,
                'halal_cert_expired_date' => $validated['halal_cert_expired_date'] ?? null,
            ]);

            if (!empty($validated['units'])) {
                foreach ($validated['units'] as $unit) {
                    if (!empty($unit['unit_name'])) {
                        $product->units()->create([
                            'unit_name' => $unit['unit_name'],
                            'conversion_to_base' => $unit['conversion_to_base'],
                            'price_override' => $unit['price_override'] ?? null,
                        ]);
                    }
                }
            }

            return $product;
        });

        return redirect()
            ->route('app.products.index')
            ->with('success', "Produk \"{$product->name}\" berhasil ditambahkan.");
    }

    public function edit(Product $product): View
    {
        $product->load('units');
        return view('app.owner.products.edit', compact('product'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'base_unit' => 'required|string|max:50',
            'selling_price' => 'required|numeric|min:0',
            'minimum_stock' => 'nullable|numeric|min:0',
            'halal_cert_number' => 'nullable|string|max:255',
            'halal_cert_issuer' => 'nullable|string|max:255',
            'halal_cert_expired_date' => 'nullable|date',
            'units' => 'nullable|array',
            'units.*.id' => 'nullable|exists:product_units,id',
            'units.*.unit_name' => 'required_with:units|string|max:50',
            'units.*.conversion_to_base' => 'required_with:units|numeric|min:0.01',
            'units.*.price_override' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $product) {
            $product->update([
                'name' => $validated['name'],
                'sku' => $validated['sku'] ?? null,
                'base_unit' => $validated['base_unit'],
                'selling_price' => $validated['selling_price'],
                'minimum_stock' => $validated['minimum_stock'] ?? 0,
                'halal_cert_number' => $validated['halal_cert_number'] ?? null,
                'halal_cert_issuer' => $validated['halal_cert_issuer'] ?? null,
                'halal_cert_expired_date' => $validated['halal_cert_expired_date'] ?? null,
            ]);

            if (isset($validated['units'])) {
                $existingIds = $product->units()->pluck('id')->toArray();
                $submittedIds = [];

                foreach ($validated['units'] as $unit) {
                    if (!empty($unit['unit_name'])) {
                        if (!empty($unit['id'])) {
                            ProductUnit::where('id', $unit['id'])->update([
                                'unit_name' => $unit['unit_name'],
                                'conversion_to_base' => $unit['conversion_to_base'],
                                'price_override' => $unit['price_override'] ?? null,
                            ]);
                            $submittedIds[] = $unit['id'];
                        } else {
                            $product->units()->create([
                                'unit_name' => $unit['unit_name'],
                                'conversion_to_base' => $unit['conversion_to_base'],
                                'price_override' => $unit['price_override'] ?? null,
                            ]);
                        }
                    }
                }

                ProductUnit::whereIn('id', array_diff($existingIds, $submittedIds))->delete();
            }
        });

        return redirect()
            ->route('app.products.index')
            ->with('success', 'Produk berhasil diupdate.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();
        return redirect()
            ->route('app.products.index')
            ->with('success', 'Produk berhasil dihapus.');
    }
}
