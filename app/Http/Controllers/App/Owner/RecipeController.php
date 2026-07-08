<?php

namespace App\Http\Controllers\App\Owner;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\RawMaterial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RecipeController extends Controller
{
    public function index(Product $product): View
    {
        if ($product->business_id !== auth()->user()->business_id) abort(403);

        $product->load('recipes.rawMaterial');
        $rawMaterials = RawMaterial::where('business_id', auth()->user()->business_id)->get();

        return view('app.owner.production.recipes', compact('product', 'rawMaterials'));
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        if ($product->business_id !== auth()->user()->business_id) abort(403);

        $validated = $request->validate([
            'recipes' => 'required|array|min:1',
            'recipes.*.raw_material_id' => 'required|exists:raw_materials,id',
            'recipes.*.qty_per_batch' => 'required|numeric|min:0.01',
            'recipes.*.unit' => 'required|string|max:50',
        ]);

        $rmIds = collect($validated['recipes'])->pluck('raw_material_id')->unique();

        // Verify all raw materials belong to this business
        $validRmCount = RawMaterial::where('business_id', auth()->user()->business_id)
            ->whereIn('id', $rmIds)->count();

        if ($validRmCount !== $rmIds->count()) {
            return back()->with('error', 'Beberapa bahan baku tidak valid.');
        }

        DB::transaction(function () use ($validated, $product) {
            foreach ($validated['recipes'] as $recipe) {
                ProductRecipe::create([
                    'product_id' => $product->id,
                    'raw_material_id' => $recipe['raw_material_id'],
                    'qty_per_batch' => $recipe['qty_per_batch'],
                    'unit' => $recipe['unit'],
                ]);
            }
        });

        $count = count($validated['recipes']);
        return back()->with('success', "{$count} bahan resep berhasil ditambahkan.");
    }

    public function update(Request $request, Product $product, ProductRecipe $recipe): RedirectResponse
    {
        if ($product->business_id !== auth()->user()->business_id) abort(403);

        $validated = $request->validate([
            'raw_material_id' => 'required|exists:raw_materials,id',
            'qty_per_batch' => 'required|numeric|min:0.01',
            'unit' => 'required|string|max:50',
        ]);

        $recipe->update($validated);

        return back()->with('success', 'Resep berhasil diupdate.');
    }

    public function destroy(Product $product, ProductRecipe $recipe): RedirectResponse
    {
        if ($product->business_id !== auth()->user()->business_id) abort(403);
        $recipe->delete();
        return back()->with('success', 'Resep berhasil dihapus.');
    }
}
