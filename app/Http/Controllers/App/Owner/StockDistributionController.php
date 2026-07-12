<?php

namespace App\Http\Controllers\App\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\StockDistribution;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockDistributionController extends Controller
{
    public function __construct(
        private StockService $stockService
    ) {}

    public function index(): View
    {
        $businessId = auth()->user()->business_id;

        $distributions = StockDistribution::where('business_id', $businessId)
            ->with(['originBranch', 'destinationBranch', 'user', 'items'])
            ->latest()
            ->paginate(15);

        return view('app.owner.stock-distributions.index', compact('distributions'));
    }

    public function create(): View
    {
        $businessId = auth()->user()->business_id;

        $branches = Branch::where('business_id', $businessId)
            ->where('is_active', true)->orderBy('name')->get();

        $rawMaterials = RawMaterial::where('business_id', $businessId)->orderBy('name')->get();
        $products = Product::where('business_id', $businessId)->orderBy('name')->get();

        return view('app.owner.stock-distributions.create', compact('branches', 'rawMaterials', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $businessId = auth()->user()->business_id;

        $validated = $request->validate([
            'origin_branch_id' => 'required|exists:branches,id',
            'destination_branch_id' => 'required|exists:branches,id|different:origin_branch_id',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.item_type' => 'required|in:raw_material,product',
            'items.*.item_id' => 'required|integer|min:1',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        Branch::where('id', $validated['origin_branch_id'])->where('business_id', $businessId)->firstOrFail();
        Branch::where('id', $validated['destination_branch_id'])->where('business_id', $businessId)->firstOrFail();

        foreach ($validated['items'] as $item) {
            $available = $this->stockService->checkDistributionStockAvailability(
                $item['item_type'], $item['item_id'], $validated['origin_branch_id']
            );

            if ($available < (float) $item['quantity']) {
                $name = $item['item_type'] === 'raw_material'
                    ? (RawMaterial::find($item['item_id'])?->name ?? "ID {$item['item_id']}")
                    : (Product::find($item['item_id'])?->name ?? "ID {$item['item_id']}");

                return back()->withInput()->with('error',
                    "Stok {$name} di cabang asal tidak mencukupi. Tersedia: {$available}, diminta: {$item['quantity']}"
                );
            }
        }

        $distribution = DB::transaction(function () use ($validated, $businessId) {
            $distribution = StockDistribution::create([
                'business_id' => $businessId,
                'origin_branch_id' => $validated['origin_branch_id'],
                'destination_branch_id' => $validated['destination_branch_id'],
                'user_id' => auth()->id(),
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $distribution->items()->create([
                    'item_type' => $item['item_type'],
                    'item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            return $distribution;
        });

        activity()
            ->performedOn($distribution)
            ->causedBy(auth()->user())
            ->log('Stock distribution created');

        return redirect()
            ->route('app.owner.stock-distributions.index')
            ->with('success', 'Distribusi stok berhasil dibuat.');
    }

    public function show(StockDistribution $stock_distribution): View
    {
        if ($stock_distribution->business_id !== auth()->user()->business_id) abort(403);

        $stock_distribution->load([
            'originBranch', 'destinationBranch', 'user',
            'items.batchRecords.productBatch',
            'items.batchRecords.rawMaterialBatch',
            'items.rawMaterial',
            'items.product',
        ]);

        return view('app.owner.stock-distributions.show', compact('stock_distribution'));
    }

    public function ship(StockDistribution $stock_distribution): RedirectResponse
    {
        if ($stock_distribution->business_id !== auth()->user()->business_id) abort(403);

        try {
            $this->stockService->distributeStockForShip(
                $stock_distribution->id, auth()->id()
            );

            activity()
                ->performedOn($stock_distribution)
                ->causedBy(auth()->user())
                ->log('Stock distribution shipped');

            return redirect()
                ->route('app.owner.stock-distributions.show', $stock_distribution)
                ->with('success', 'Distribusi telah dikirim. Stok dalam perjalanan.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal mengirim distribusi: ' . $e->getMessage());
        }
    }

    public function receive(StockDistribution $stock_distribution): RedirectResponse
    {
        if ($stock_distribution->business_id !== auth()->user()->business_id) abort(403);

        try {
            $this->stockService->distributeStockForReceive(
                $stock_distribution->id, auth()->id()
            );

            activity()
                ->performedOn($stock_distribution)
                ->causedBy(auth()->user())
                ->log('Stock distribution received');

            return redirect()
                ->route('app.owner.stock-distributions.show', $stock_distribution)
                ->with('success', 'Distribusi telah diterima. Stok masuk ke cabang tujuan.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menerima distribusi: ' . $e->getMessage());
        }
    }
}
