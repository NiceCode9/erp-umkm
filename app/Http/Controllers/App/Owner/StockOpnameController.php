<?php

namespace App\Http\Controllers\App\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\ProductBatch;
use App\Models\RawMaterialBatch;
use App\Models\StockOpname;
use App\Models\StockOpnameSession;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockOpnameController extends Controller
{
    public function __construct(
        private StockService $stockService
    ) {}

    public function index(): View
    {
        $sessions = StockOpnameSession::where('business_id', auth()->user()->business_id)
            ->with(['branch', 'user'])
            ->latest()
            ->paginate(20);

        return view('app.owner.stock-opnames.index', compact('sessions'));
    }

    public function create(): View
    {
        $branches = Branch::where('business_id', auth()->user()->business_id)
            ->where('is_active', true)->get();

        return view('app.owner.stock-opnames.create', compact('branches'));
    }

    public function storeSession(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'item_type' => 'required|in:raw_material,product',
            'title' => 'nullable|string|max:255',
            'opname_date' => 'required|date',
        ]);

        $businessId = auth()->user()->business_id;

        Branch::where('id', $validated['branch_id'])
            ->where('business_id', $businessId)->firstOrFail();

        $session = StockOpnameSession::create([
            'business_id' => $businessId,
            'branch_id' => $validated['branch_id'],
            'item_type' => $validated['item_type'],
            'title' => $validated['title'],
            'status' => 'draft',
            'opname_date' => $validated['opname_date'],
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('app.stock-opnames.worksheet', $session)
            ->with('success', 'Sesi opname dibuat. Silakan isi stok aktual.');
    }

    public function worksheet(StockOpnameSession $session): View
    {
        if ($session->business_id !== auth()->user()->business_id) abort(403);

        $batches = $session->item_type === 'raw_material'
            ? RawMaterialBatch::whereIn('raw_material_id', function ($q) {
                $q->select('id')->from('raw_materials')
                    ->where('business_id', auth()->user()->business_id);
            })
                ->where('branch_id', $session->branch_id)
                ->with('rawMaterial')
                ->orderBy('expired_date')
                ->get()
                ->map(fn ($b) => [
                    'type' => 'raw_material', 'item_id' => $b->raw_material_id,
                    'item_name' => $b->rawMaterial->name, 'batch_id' => $b->id,
                    'batch_no' => $b->batch_no, 'expired' => $b->expired_date?->format('d/m/Y'),
                    'unit' => $b->rawMaterial->base_unit, 'system_qty' => (float) $b->quantity_remaining,
                ])
            : ProductBatch::whereIn('product_id', function ($q) {
                $q->select('id')->from('products')
                    ->where('business_id', auth()->user()->business_id);
            })
                ->where('branch_id', $session->branch_id)
                ->with('product')
                ->orderBy('expired_date')
                ->get()
                ->map(fn ($b) => [
                    'type' => 'product', 'item_id' => $b->product_id,
                    'item_name' => $b->product->name, 'batch_id' => $b->id,
                    'batch_no' => $b->batch_no, 'expired' => $b->expired_date?->format('d/m/Y'),
                    'unit' => $b->product->base_unit, 'system_qty' => (float) $b->quantity_remaining,
                ]);

        $existingRecords = StockOpname::where('session_id', $session->id)
            ->get()
            ->keyBy('batch_id');

        return view('app.owner.stock-opnames.worksheet', compact('session', 'batches', 'existingRecords'));
    }

    public function saveWorksheet(Request $request, StockOpnameSession $session): RedirectResponse
    {
        if ($session->business_id !== auth()->user()->business_id) abort(403);
        if ($session->status === 'confirmed') {
            return back()->with('error', 'Sesi opname sudah dikunci.');
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.item_id' => 'required|integer',
            'items.*.batch_id' => 'required|integer',
            'items.*.system_qty' => 'required|numeric|min:0',
            'items.*.actual_qty' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $session) {
            foreach ($validated['items'] as $item) {
                $diff = (float) $item['actual_qty'] - (float) $item['system_qty'];

                StockOpname::updateOrCreate(
                    ['session_id' => $session->id, 'batch_id' => $item['batch_id']],
                    [
                        'business_id' => $session->business_id,
                        'branch_id' => $session->branch_id,
                        'item_type' => $session->item_type,
                        'item_id' => $item['item_id'],
                        'system_quantity' => $item['system_qty'],
                        'actual_quantity' => $item['actual_qty'],
                        'difference' => $diff,
                        'reason' => 'Stok opname',
                        'user_id' => auth()->id(),
                    ]
                );
            }
        });

        return back()->with('success', 'Data opname disimpan (draft).');
    }

    public function confirm(Request $request, StockOpnameSession $session): RedirectResponse
    {
        if ($session->business_id !== auth()->user()->business_id) abort(403);
        if ($session->status === 'confirmed') {
            return back()->with('error', 'Sesi ini sudah dikunci sebelumnya.');
        }

        // Auto-save if form data is included
        if ($request->has('items')) {
            $validated = $request->validate([
                'items' => 'required|array',
                'items.*.item_id' => 'required|integer',
                'items.*.batch_id' => 'required|integer',
                'items.*.system_qty' => 'required|numeric|min:0',
                'items.*.actual_qty' => 'required|numeric|min:0',
            ]);

            DB::transaction(function () use ($validated, $session) {
                foreach ($validated['items'] as $item) {
                    $diff = (float) $item['actual_qty'] - (float) $item['system_qty'];

                    StockOpname::updateOrCreate(
                        ['session_id' => $session->id, 'batch_id' => $item['batch_id']],
                        [
                            'business_id' => $session->business_id,
                            'branch_id' => $session->branch_id,
                            'item_type' => $session->item_type,
                            'item_id' => $item['item_id'],
                            'system_quantity' => $item['system_qty'],
                            'actual_quantity' => $item['actual_qty'],
                            'difference' => $diff,
                            'reason' => 'Stok opname',
                            'user_id' => auth()->id(),
                        ]
                    );
                }
            });
        }

        $records = StockOpname::where('session_id', $session->id)->get();

        if ($records->isEmpty()) {
            return back()->with('error', 'Belum ada data opname. Isi worksheet dan simpan draft dulu.');
        }

        $businessId = $session->business_id;
        $userId = auth()->id();
        $count = 0;

        DB::transaction(function () use ($records, $businessId, $userId, $session, &$count) {
            foreach ($records as $record) {
                if ((float) $record->difference == 0) continue;

                $this->stockService->adjustStockFromOpname(
                    branchId: $session->branch_id,
                    businessId: $businessId,
                    itemType: $session->item_type,
                    itemId: $record->item_id,
                    batchId: $record->batch_id,
                    systemQty: $record->system_quantity,
                    actualQty: $record->actual_quantity,
                    reason: $record->reason . ' (confirmed)',
                    userId: $userId,
                );
                $count++;
            }

            $session->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'confirmed_by' => $userId,
            ]);
        });

        activity()
            ->performedOn($session)
            ->causedBy(auth()->user())
            ->withProperties(['count' => $count, 'total' => $records->count()])
            ->log('Stock opname confirmed');

        return redirect()
            ->route('app.stock-opnames.index')
            ->with('success', "Opname dikunci. {$count} batch disesuaikan.");
    }

}
