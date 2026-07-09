<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Product;
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

            $now = now();
            $halalExpiringSoon = Product::where('business_id', $businessId)
                ->whereNotNull('halal_cert_expired_date')
                ->where('halal_cert_expired_date', '>=', $now)
                ->where('halal_cert_expired_date', '<=', $now->copy()->addDays(30))
                ->get();

            $halalExpired = Product::where('business_id', $businessId)
                ->whereNotNull('halal_cert_expired_date')
                ->where('halal_cert_expired_date', '<', $now)
                ->get();

            return view('app.dashboard-owner', compact(
                'lowStockMaterials', 'halalExpiringSoon', 'halalExpired'
            ));
        }

        return view('app.dashboard-kasir');
    }
}
