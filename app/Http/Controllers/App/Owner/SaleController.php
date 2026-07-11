<?php

namespace App\Http\Controllers\App\Owner;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(): View
    {
        $businessId = auth()->user()->business_id;

        $sales = Sale::where('business_id', $businessId)
            ->with(['user', 'branch', 'items.product'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('app.owner.sales.index', compact('sales'));
    }

    public function show(Sale $sale): View
    {
        if ($sale->business_id !== auth()->user()->business_id) abort(403);

        $sale->load([
            'user', 'branch', 'items.product', 'items.productUnit',
            'items.batches.productBatch', 'cashierShift',
        ]);

        return view('app.owner.sales.show', compact('sale'));
    }
}
