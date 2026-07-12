<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\RawMaterial;
use App\Models\RawMaterialBatch;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\SaleReturn;
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

            $outstandingPurchases = Purchase::where('business_id', $businessId)
                ->where('payment_status', '!=', 'paid')
                ->with(['supplier', 'payments'])
                ->orderBy('created_at')
                ->get()
                ->map(function ($p) {
                    $paid = (float) $p->payments->sum('amount');
                    $p->outstanding = (float) $p->total_amount - $paid;
                    return $p;
                });

            $outstandingSales = Sale::where('business_id', $businessId)
                ->where('payment_status', '!=', 'paid')
                ->with(['payments', 'returns'])
                ->orderBy('created_at')
                ->get()
                ->map(function ($s) {
                    $paid = (float) $s->payments->sum('amount');
                    $returned = (float) $s->returns->sum('total_amount');
                    $s->outstanding = (float) $s->total_amount - $paid - $returned;
                    return $s;
                });

            return view('app.dashboard-owner', compact(
                'lowStockMaterials', 'halalExpiringSoon', 'halalExpired',
                'outstandingPurchases', 'outstandingSales'
            ));
        }

        return view('app.dashboard-kasir');
    }
}
