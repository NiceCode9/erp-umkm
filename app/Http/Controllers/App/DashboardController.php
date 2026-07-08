<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\RawMaterial;
use App\Models\RawMaterialBatch;
use App\Services\StockService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private StockService $stockService
    ) {}

    public function index(): View
    {
        $user = auth()->user();

        if ($user->hasRole('Owner')) {
            $businessId = $user->business_id;

            $lowStockMaterials = RawMaterial::where('business_id', $businessId)
                ->get()
                ->filter(function ($rm) use ($businessId) {
                    $totalStock = (float) RawMaterialBatch::where('raw_material_id', $rm->id)
                        ->whereIn('branch_id', function ($q) use ($businessId) {
                            $q->select('id')->from('branches')
                                ->where('business_id', $businessId)
                                ->where('is_active', true);
                        })
                        ->sum('quantity_remaining');

                    return $totalStock < $rm->minimum_stock;
                })
                ->values();

            return view('app.dashboard-owner', compact('lowStockMaterials'));
        }

        return view('app.dashboard-kasir');
    }
}
