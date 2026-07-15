<?php

namespace App\Http\Controllers\App\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\RawMaterial;
use App\Models\RawMaterialBatch;
use App\Models\StockOpname;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockOpnameController extends Controller
{
    public function __construct(
        private StockService $stockService
    ) {}

    public function index(): View
    {
        $opnames = StockOpname::where('business_id', auth()->user()->business_id)
            ->with(['branch', 'user'])
            ->latest()
            ->paginate(20);

        return view('app.owner.stock-opnames.index', compact('opnames'));
    }

    public function create(): View
    {
        $branches = Branch::where('business_id', auth()->user()->business_id)
            ->where('is_active', true)->get();
        $rawMaterials = RawMaterial::where('business_id', auth()->user()->business_id)->get();
        $products = Product::where('business_id', auth()->user()->business_id)->get();

        return view('app.owner.stock-opnames.create', compact('branches', 'rawMaterials', 'products'));
    }

    public function getBatches(Request $request)
    {
        $itemType = $request->item_type;
        $itemId = $request->item_id;
        $branchId = $request->branch_id;

        if ($itemType === 'raw_material') {
            $batches = RawMaterialBatch::where('raw_material_id', $itemId)
                ->where('branch_id', $branchId)
                ->where('quantity_remaining', '>', 0)
                ->orderBy('expired_date')
                ->get();
        } else {
            $batches = ProductBatch::where('product_id', $itemId)
                ->where('branch_id', $branchId)
                ->where('quantity_remaining', '>', 0)
                ->orderBy('expired_date')
                ->get();
        }

        return response()->json($batches->map(fn ($b) => [
            'id' => $b->id,
            'label' => $b->batch_no . ($b->expired_date ? ' (Exp: ' . $b->expired_date->format('d/m/Y') . ')' : ''),
            'quantity_remaining' => (float) $b->quantity_remaining,
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'item_type' => 'required|in:raw_material,product',
            'item_id' => 'required|integer',
            'batch_id' => 'required|integer',
            'system_quantity' => 'required|numeric|min:0',
            'actual_quantity' => 'required|numeric|min:0',
            'reason' => 'required|string',
        ]);

        $businessId = auth()->user()->business_id;
        $userId = auth()->id();

        $this->stockService->adjustStockFromOpname(
            branchId: $validated['branch_id'],
            businessId: $businessId,
            itemType: $validated['item_type'],
            itemId: $validated['item_id'],
            batchId: $validated['batch_id'],
            systemQty: $validated['system_quantity'],
            actualQty: $validated['actual_quantity'],
            reason: $validated['reason'],
            userId: $userId,
        );

        activity()
            ->causedBy(auth()->user())
            ->withProperties([
                'item_type' => $validated['item_type'],
                'item_id' => $validated['item_id'],
                'batch_id' => $validated['batch_id'],
                'diff' => $validated['actual_quantity'] - $validated['system_quantity'],
            ])
            ->log('Stock opname recorded');

        return redirect()
            ->route('app.stock-opnames.index')
            ->with('success', 'Stok opname berhasil dicatat.');
    }
}
