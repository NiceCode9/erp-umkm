<?php

namespace App\Http\Controllers\App\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\RawMaterial;
use App\Models\RawMaterialBatch;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RawMaterialController extends Controller
{
    public function index(): View
    {
        $businessId = auth()->user()->business_id;

        $branches = Branch::where('business_id', $businessId)
            ->where('is_active', true)->orderBy('name')->get();

        $rawMaterials = RawMaterial::where('business_id', $businessId)
            ->latest()
            ->get();

        $stockPerMaterial = RawMaterialBatch::whereIn('raw_material_id', $rawMaterials->pluck('id'))
            ->whereIn('branch_id', $branches->pluck('id'))
            ->selectRaw('raw_material_id, branch_id, SUM(quantity_remaining) as total_stock')
            ->groupBy('raw_material_id', 'branch_id')
            ->get()
            ->groupBy('raw_material_id');

        $materialData = $rawMaterials->map(function ($rm) use ($stockPerMaterial, $branches) {
            $branchStocks = collect($stockPerMaterial->get($rm->id, collect()));
            $totalStock = (float) $branchStocks->sum('total_stock');
            $lowStockBranches = $branches->filter(fn ($b) =>
                (float) ($branchStocks->firstWhere('branch_id', $b->id)?->total_stock ?? 0) < $rm->minimum_stock
            )->count();

            return (object) [
                'id' => $rm->id,
                'name' => $rm->name,
                'base_unit' => $rm->base_unit,
                'minimum_stock' => $rm->minimum_stock,
                'total_stock' => $totalStock,
                'low_stock_branches' => $lowStockBranches,
            ];
        });

        return view('app.owner.raw-materials.index', compact('materialData'));
    }

    public function create(): View
    {
        return view('app.owner.raw-materials.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'base_unit' => 'required|string|max:50',
            'minimum_stock' => 'nullable|numeric|min:0',
        ]);

        RawMaterial::create($validated);

        return redirect()
            ->route('app.raw-materials.index')
            ->with('success', 'Bahan baku berhasil ditambahkan.');
    }

    public function show(RawMaterial $rawMaterial): View
    {
        if ($rawMaterial->business_id !== auth()->user()->business_id) abort(403);

        $businessId = auth()->user()->business_id;
        $branches = Branch::where('business_id', $businessId)
            ->where('is_active', true)->orderBy('name')->get();

        $batches = RawMaterialBatch::where('raw_material_id', $rawMaterial->id)
            ->whereIn('branch_id', $branches->pluck('id'))
            ->where('quantity_remaining', '>', 0)
            ->orderBy('expired_date')
            ->with('branch')
            ->get();

        $branchStocks = RawMaterialBatch::where('raw_material_id', $rawMaterial->id)
            ->whereIn('branch_id', $branches->pluck('id'))
            ->selectRaw('branch_id, SUM(quantity_remaining) as total_stock')
            ->groupBy('branch_id')
            ->pluck('total_stock', 'branch_id');

        $branchData = $branches->map(function ($b) use ($branchStocks, $rawMaterial) {
            $stock = (float) ($branchStocks->get($b->id) ?? 0);
            return (object) [
                'name' => $b->name,
                'stock' => $stock,
                'below_minimum' => $stock < $rawMaterial->minimum_stock,
            ];
        });

        $movements = StockMovement::where('item_type', 'raw_material')
            ->where('item_id', $rawMaterial->id)
            ->whereIn('branch_id', $branches->pluck('id'))
            ->with(['branch', 'creator', 'batch'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('app.owner.raw-materials.show', compact('rawMaterial', 'branchData', 'batches', 'movements'));
    }

    public function edit(RawMaterial $rawMaterial): View
    {
        if ($rawMaterial->business_id !== auth()->user()->business_id) abort(403);
        return view('app.owner.raw-materials.edit', compact('rawMaterial'));
    }

    public function update(Request $request, RawMaterial $rawMaterial): RedirectResponse
    {
        if ($rawMaterial->business_id !== auth()->user()->business_id) abort(403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'base_unit' => 'required|string|max:50',
            'minimum_stock' => 'nullable|numeric|min:0',
        ]);

        $rawMaterial->update($validated);

        return redirect()
            ->route('app.raw-materials.index')
            ->with('success', 'Bahan baku berhasil diupdate.');
    }

    public function destroy(RawMaterial $rawMaterial): RedirectResponse
    {
        if ($rawMaterial->business_id !== auth()->user()->business_id) abort(403);
        $rawMaterial->delete();
        return redirect()
            ->route('app.raw-materials.index')
            ->with('success', 'Bahan baku berhasil dihapus.');
    }
}
