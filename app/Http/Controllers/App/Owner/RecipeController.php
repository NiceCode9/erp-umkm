<?php

namespace App\Http\Controllers\App\Owner;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\Recipe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RecipeController extends Controller
{
    public function index(Product $product): View
    {
        if ($product->business_id !== auth()->user()->business_id) abort(403);

        $product->load(['recipes' => fn ($q) => $q->withCount('items')->orderByDesc('is_active')->orderBy('name')]);
        $rawMaterials = RawMaterial::where('business_id', auth()->user()->business_id)->get();

        return view('app.owner.production.recipes', compact('product', 'rawMaterials'));
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        if ($product->business_id !== auth()->user()->business_id) abort(403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'yield_quantity' => 'required|numeric|min:0.01',
            'items' => 'required|array|min:1',
            'items.*.raw_material_id' => 'required|exists:raw_materials,id',
            'items.*.qty_per_batch' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string|max:50',
        ]);

        $rmIds = collect($validated['items'])->pluck('raw_material_id')->unique();
        $validCount = RawMaterial::where('business_id', auth()->user()->business_id)
            ->whereIn('id', $rmIds)->count();

        if ($validCount !== $rmIds->count()) {
            return back()->with('error', 'Beberapa bahan baku tidak valid.');
        }

        DB::transaction(function () use ($validated, $product) {
            $recipe = Recipe::create([
                'product_id' => $product->id,
                'name' => $validated['name'],
                'yield_quantity' => $validated['yield_quantity'],
                'is_active' => true,
            ]);

            foreach ($validated['items'] as $item) {
                $recipe->items()->create([
                    'raw_material_id' => $item['raw_material_id'],
                    'qty_per_batch' => $item['qty_per_batch'],
                    'unit' => $item['unit'],
                ]);
            }
        });

        return redirect()
            ->route('app.products.recipes.index', $product)
            ->with('success', "Resep \"{$validated['name']}\" berhasil ditambahkan.");
    }

    public function toggle(Product $product, Recipe $recipe): RedirectResponse
    {
        if ($product->business_id !== auth()->user()->business_id) abort(403);

        $recipe->update(['is_active' => !$recipe->is_active]);

        $status = $recipe->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Resep \"{$recipe->name}\" berhasil {$status}.");
    }

    public function destroy(Product $product, Recipe $recipe): RedirectResponse
    {
        if ($product->business_id !== auth()->user()->business_id) abort(403);
        $recipe->delete();
        return back()->with('success', 'Resep berhasil dihapus.');
    }
}
