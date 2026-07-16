<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Purchase;
use App\Models\RawMaterial;
use App\Models\RawMaterialBatch;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\StockService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
            return $this->ownerDashboard($user);
        }

        return $this->kasirDashboard($user);
    }

    private function ownerDashboard($user): View
    {
        $businessId = $user->business_id;

        // Low stock materials
        $lowStockMaterials = RawMaterial::where('business_id', $businessId)
            ->get()
            ->filter(function ($rm) use ($businessId) {
                $totalStock = (float) RawMaterialBatch::where('raw_material_id', $rm->id)
                    ->whereIn('branch_id', function ($q) use ($businessId) {
                        $q->select('id')->from('branches')->where('business_id', $businessId)->where('is_active', true);
                    })
                    ->sum('quantity_remaining');
                return $totalStock < $rm->minimum_stock;
            })->values();

        // Halal notifications
        $now = now();
        $halalExpiringSoon = Product::where('business_id', $businessId)
            ->whereNotNull('halal_cert_expired_date')
            ->whereBetween('halal_cert_expired_date', [$now, $now->copy()->addDays(30)])
            ->get();
        $halalExpired = Product::where('business_id', $businessId)
            ->whereNotNull('halal_cert_expired_date')
            ->where('halal_cert_expired_date', '<', $now)
            ->get();

        // Outstanding utang piutang
        $outstandingPurchases = Purchase::where('business_id', $businessId)
            ->where('payment_status', '!=', 'paid')
            ->with(['payments', 'returns'])
            ->orderBy('created_at')
            ->get()
            ->map(function ($p) {
                $paid = (float) $p->payments->sum('amount');
                $returned = (float) $p->returns->sum('total_amount');
                $p->outstanding = max(0, (float) $p->total_amount - $paid - $returned);
                return $p;
            })
            ->filter(fn ($p) => $p->outstanding > 0);

        $outstandingSales = Sale::where('business_id', $businessId)
            ->where('payment_status', '!=', 'paid')
            ->with(['payments', 'returns'])
            ->orderBy('created_at')
            ->get()
            ->map(function ($s) {
                $paid = (float) $s->payments->sum('amount');
                $returned = (float) $s->returns->sum('total_amount');
                $s->outstanding = max(0, (float) $s->total_amount - $paid - $returned);
                return $s;
            })
            ->filter(fn ($s) => $s->outstanding > 0);

        // Today's sales summary
        $todayStart = now()->startOfDay();
        $todaySales = Sale::where('business_id', $businessId)
            ->where('sale_date', '>=', $todayStart)
            ->get();
        $todayTotal = (float) $todaySales->sum('total_amount');
        $todayCount = $todaySales->count();
        $todayAvg = $todayCount > 0 ? $todayTotal / $todayCount : 0;

        // Sales chart data (last 14 days)
        $chartDays = collect();
        for ($i = 13; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $chartDays->push([
                'date' => $date->format('d/m'),
                'total' => (float) Sale::where('business_id', $businessId)
                    ->whereDate('sale_date', $date)
                    ->sum('total_amount'),
            ]);
        }

        return view('app.dashboard-owner', compact(
            'lowStockMaterials', 'halalExpiringSoon', 'halalExpired',
            'outstandingPurchases', 'outstandingSales',
            'todayTotal', 'todayCount', 'todayAvg', 'chartDays'
        ));
    }

    private function kasirDashboard($user): View
    {
        $businessId = $user->business_id;
        $branchId = $user->branch_id;

        $todaySales = Sale::where('business_id', $businessId)
            ->where('user_id', $user->id)
            ->whereDate('sale_date', now()->startOfDay())
            ->get();

        $todayTotal = (float) $todaySales->sum('total_amount');
        $todayCount = $todaySales->count();

        $activeShift = CashierShift::where('business_id', $businessId)
            ->where('branch_id', $branchId)
            ->where('user_id', $user->id)
            ->whereNull('closed_at')
            ->latest()
            ->first();

        return view('app.dashboard-kasir', compact('todayTotal', 'todayCount', 'activeShift'));
    }
}
