<?php

namespace App\Http\Controllers\App\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    public function index(Request $request): View
    {
        $businessId = auth()->user()->business_id;
        $branches = Branch::where('business_id', $businessId)
            ->where('is_active', true)->orderBy('name')->get();

        $query = StockMovement::where('stock_movements.business_id', $businessId)
            ->with(['branch', 'creator', 'batch'])
            ->select('stock_movements.*');

        if ($request->filled('branch_id')) {
            $query->where('stock_movements.branch_id', $request->branch_id);
        }

        if ($request->filled('movement_type')) {
            $query->where('stock_movements.movement_type', $request->movement_type);
        }

        if ($request->filled('item_type')) {
            $query->where('stock_movements.item_type', $request->item_type);
        }

        if ($request->filled('reference_type')) {
            $query->where('stock_movements.reference_type', $request->reference_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('stock_movements.created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('stock_movements.created_at', '<=', $request->date_to);
        }

        $movements = $query->orderByDesc('stock_movements.created_at')
            ->paginate(20)
            ->withQueryString();

        $movements->load('batch.rawMaterial');
        $movements->each(function ($m) {
            if ($m->item_type === 'raw_material') {
                $m->item_name = optional(optional($m->batch)->rawMaterial)->name ?? "Raw Material #{$m->item_id}";
            } else {
                $m->item_name = "Product #{$m->item_id}";
            }
        });

        return view('app.owner.stock-movements.index', compact('movements', 'branches'));
    }
}
