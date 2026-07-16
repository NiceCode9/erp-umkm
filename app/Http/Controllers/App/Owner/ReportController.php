<?php
namespace App\Http\Controllers\App\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\RawMaterial;
use App\Models\Product;
use App\Models\RawMaterialBatch;
use App\Models\ProductBatch;
use App\Models\CashierShift;
use App\Models\ProductionOrder;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function sales(Request $request): View
    {
        $businessId = auth()->user()->business_id;
        $branches = Branch::where('business_id', $businessId)->where('is_active', true)->get();

        $query = Sale::where('business_id', $businessId)
            ->with(['branch', 'user', 'items.product', 'payments', 'returns.items'])
            ->latest('sale_date');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('date_from')) {
            $query->where('sale_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('sale_date', '<=', $request->date_to . ' 23:59:59');
        }

        $sales = $query->paginate(20)->withQueryString();

        // Summary
        $summaryQuery = Sale::where('business_id', $businessId);
        if ($request->filled('branch_id')) $summaryQuery->where('branch_id', $request->branch_id);
        if ($request->filled('date_from')) $summaryQuery->where('sale_date', '>=', $request->date_from);
        if ($request->filled('date_to')) $summaryQuery->where('sale_date', '<=', $request->date_to . ' 23:59:59');

        $summary = [
            'total' => (float) $summaryQuery->clone()->sum('total_amount'),
            'count' => $summaryQuery->clone()->count(),
            'avg' => $summaryQuery->clone()->avg('total_amount') ?? 0,
        ];

        // Top products
        $topProducts = SaleItem::whereHas('sale', function ($q) use ($businessId, $request) {
            $q->where('business_id', $businessId);
            if ($request->filled('branch_id')) $q->where('branch_id', $request->branch_id);
            if ($request->filled('date_from')) $q->where('sale_date', '>=', $request->date_from);
            if ($request->filled('date_to')) $q->where('sale_date', '<=', $request->date_to . ' 23:59:59');
        })
        ->selectRaw('product_id, SUM(quantity) as total_qty, SUM(subtotal) as total_revenue')
        ->groupBy('product_id')
        ->orderByDesc('total_qty')
        ->limit(10)
        ->with('product')
        ->get();

        return view('app.owner.reports.sales', compact('sales', 'summary', 'topProducts', 'branches'));
    }

    public function debts(Request $request): View
    {
        $businessId = auth()->user()->business_id;

        // Supplier debts (purchases unpaid/partial)
        $supplierDebts = Purchase::where('business_id', $businessId)
            ->with(['supplier', 'branch', 'payments', 'returns'])
            ->where('payment_status', '!=', 'paid')
            ->latest()
            ->get()
            ->map(function ($p) {
                $paid = (float) $p->payments->sum('amount');
                $returned = (float) $p->returns->sum('total_amount');
                $outstanding = (float) $p->total_amount - $paid - $returned;
                $p->outstanding = max(0, $outstanding);
                return $p;
            })
            ->filter(fn ($p) => $p->outstanding > 0);

        // Customer debts (sales unpaid/partial)
        $customerDebts = Sale::where('business_id', $businessId)
            ->with(['user', 'payments', 'returns.items'])
            ->where('payment_status', '!=', 'paid')
            ->latest()
            ->get()
            ->map(function ($s) {
                $paid = (float) $s->payments->sum('amount');
                $returned = (float) $s->returns->sum('total_amount');
                $outstanding = (float) $s->total_amount - $paid - $returned;
                $s->outstanding = max(0, $outstanding);
                return $s;
            })
            ->filter(fn ($s) => $s->outstanding > 0);

        return view('app.owner.reports.debts', compact('supplierDebts', 'customerDebts'));
    }

    public function stock(Request $request): View
    {
        $businessId = auth()->user()->business_id;
        $branches = Branch::where('business_id', $businessId)->where('is_active', true)->get();
        $branchFilter = $request->branch_id;
        $itemType = $request->item_type ?: 'raw_material';

        $batchTable = $itemType === 'raw_material' ? 'raw_material_batches' : 'product_batches';
        $idCol = $itemType === 'raw_material' ? 'raw_material_id' : 'product_id';
        $itemModel = $itemType === 'raw_material' ? RawMaterial::class : Product::class;

        $query = $itemModel::where('business_id', $businessId)->select('id', 'name', 'base_unit', 'minimum_stock');

        if ($itemType === 'raw_material') {
            $query->withCount(['batches' => fn ($q) => $q->where('quantity_remaining', '>', 0)]);
            $query->withSum(['batches' => fn ($q) => $q->where('quantity_remaining', '>', 0)], 'quantity_remaining');
            if ($branchFilter) {
                $query->withCount(['batches' => fn ($q) => $q->where('branch_id', $branchFilter)->where('quantity_remaining', '>', 0)]);
                $query->withSum(['batches' => fn ($q) => $q->where('branch_id', $branchFilter)->where('quantity_remaining', '>', 0)], 'quantity_remaining');
            }
        } else {
            $query->withCount(['batches' => fn ($q) => $q->where('quantity_remaining', '>', 0)]);
            $query->withSum(['batches' => fn ($q) => $q->where('quantity_remaining', '>', 0)], 'quantity_remaining');
            if ($branchFilter) {
                $query->withCount(['batches' => fn ($q) => $q->where('branch_id', $branchFilter)->where('quantity_remaining', '>', 0)]);
                $query->withSum(['batches' => fn ($q) => $q->where('branch_id', $branchFilter)->where('quantity_remaining', '>', 0)], 'quantity_remaining');
            }
        }

        $items = $query->orderBy('name')->paginate(20);

        $summary = [
            'total_items' => $items->total(),
            'total_qty' => (float) $items->getCollection()->sum(fn ($i) => (float) ($i->quantity_remaining_sum ?? 0)),
            'low_stock' => (float) $items->getCollection()->filter(fn ($i) => (float) ($i->quantity_remaining_sum ?? 0) < ($i->minimum_stock ?? 0))->count(),
        ];

        return view('app.owner.reports.stock', compact('items', 'branches', 'itemType', 'summary'));
    }

    public function production(Request $request): View
    {
        $businessId = auth()->user()->business_id;
        $branches = Branch::where('business_id', $businessId)->where('is_active', true)->get();
        
        $query = ProductionOrder::where('business_id', $businessId)
            ->with(['product', 'branch', 'recipe'])
            ->latest();
        
        if ($request->filled('branch_id')) $query->where('branch_id', $request->branch_id);
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('created_at', '<=', $request->date_to);
        
        $orders = $query->paginate(20)->withQueryString();
        
        return view('app.owner.reports.production', compact('orders', 'branches'));
    }
    
    public function shifts(Request $request): View
    {
        $businessId = auth()->user()->business_id;
        $branches = Branch::where('business_id', $businessId)->where('is_active', true)->get();

        $query = CashierShift::where('business_id', $businessId)
            ->with(['branch', 'user', 'sales'])
            ->latest();

        if ($request->filled('branch_id')) $query->where('branch_id', $request->branch_id);
        if ($request->filled('date_from')) $query->whereDate('opened_at', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('opened_at', '<=', $request->date_to);
        if ($request->filled('status') && $request->status === 'closed') {
            $query->whereNotNull('closed_at');
        }

        $shifts = $query->paginate(20)->withQueryString();

        return view('app.owner.reports.shifts', compact('shifts', 'branches'));
    }

    public function export($type, $format, Request $request)
    {
        $businessId = auth()->user()->business_id;
        $fileName = "report_{$type}_" . now()->format('Y-m-d_His');
        
        if ($type === 'sales') {
            $data = Sale::where('business_id', $businessId)
                ->with(['branch', 'user'])
                ->latest('sale_date');
            if ($request->filled('branch_id')) $data->where('branch_id', $request->branch_id);
            if ($request->filled('date_from')) $data->where('sale_date', '>=', $request->date_from);
            if ($request->filled('date_to')) $data->where('sale_date', '<=', $request->date_to . ' 23:59:59');
            $exportClass = new \App\Exports\SalesExport($data->get());
        } elseif ($type === 'supplier-debts') {
            $data = Purchase::where('business_id', $businessId)
                ->with(['supplier', 'branch', 'payments', 'returns'])
                ->where('payment_status', '!=', 'paid')->latest()
                ->get()->map(function ($p) {
                    $paid = (float) $p->payments->sum('amount');
                    $returned = (float) $p->returns->sum('total_amount');
                    $p->outstanding = max(0, (float) $p->total_amount - $paid - $returned);
                    return $p;
                })->filter(fn ($p) => $p->outstanding > 0);
            $exportClass = new \App\Exports\SupplierDebtExport(collect($data));
        } elseif ($type === 'stock') {
            $itemType = $request->item_type ?: 'raw_material';
            if ($itemType === 'raw_material') {
                $items = RawMaterial::where('business_id', $businessId)
                    ->with(['batches.branch'])->get();
                $flattened = $items->flatMap(function ($item) {
                    return $item->batches->filter(fn ($b) => $b->quantity_remaining > 0)->map(fn ($b) => [
                        'type' => 'raw_material', 'item_name' => $item->name, 'branch_name' => $b->branch->name,
                        'batch_no' => $b->batch_no, 'expired' => $b->expired_date?->format('d/m/Y'),
                        'quantity' => (float) $b->quantity_remaining,
                    ]);
                });
            } else {
                $items = Product::where('business_id', $businessId)
                    ->with(['batches.branch'])->get();
                $flattened = $items->flatMap(function ($item) {
                    return $item->batches->filter(fn ($b) => $b->quantity_remaining > 0)->map(fn ($b) => [
                        'type' => 'product', 'item_name' => $item->name, 'branch_name' => $b->branch->name,
                        'batch_no' => $b->batch_no, 'expired' => $b->expired_date?->format('d/m/Y'),
                        'quantity' => (float) $b->quantity_remaining,
                    ]);
                });
            }
            $exportClass = new \App\Exports\StockExport(collect($flattened));
        } elseif ($type === 'production') {
            $data = ProductionOrder::where('business_id', $businessId)
                ->with(['product', 'branch', 'recipe'])->latest();
            if ($request->filled('branch_id')) $data->where('branch_id', $request->branch_id);
            if ($request->filled('date_from')) $data->whereDate('created_at', '>=', $request->date_from);
            if ($request->filled('date_to')) $data->whereDate('created_at', '<=', $request->date_to);
            $exportClass = new \App\Exports\ProductionExport($data->get());
        } else {
            abort(404);
        }
        
        if ($format === 'pdf') {
            $view = "app.owner.reports.{$type}";
            $data = match($type) {
                'sales' => compact('sales') ?? [],
                'debts' => ['supplierDebts' => collect(), 'customerDebts' => collect()],
                'stock' => ['items' => collect(), 'branches' => collect(), 'itemType' => $request->item_type ?? 'raw_material'],
                'production' => ['orders' => collect(), 'branches' => collect()],
                default => [],
            };
            
            return \Barryvdh\DomPDF\Facade\Pdf::loadView("app.owner.reports.pdf.{$type}", array_merge($data, ['exportData' => $exportClass->collection()]))
                ->download("{$fileName}.pdf");
        }
        
        return \Maatwebsite\Excel\Facades\Excel::download($exportClass, "{$fileName}.xlsx");
    }
}
