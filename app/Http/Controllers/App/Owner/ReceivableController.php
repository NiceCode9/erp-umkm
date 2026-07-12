<?php

namespace App\Http\Controllers\App\Owner;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReceivableController extends Controller
{
    public function __construct(
        private StockService $stockService
    ) {}

    public function index(): View
    {
        $businessId = auth()->user()->business_id;

        $sales = Sale::where('business_id', $businessId)
            ->where('payment_status', '!=', 'paid')
            ->with(['user', 'branch', 'payments', 'returns'])
            ->orderBy('created_at')
            ->get()
            ->map(function ($sale) {
                $totalPaid = (float) $sale->payments->sum('amount');
                $totalReturned = (float) $sale->returns->sum('total_amount');
                $sale->total_paid = $totalPaid;
                $sale->total_returned = $totalReturned;
                $sale->outstanding = (float) $sale->total_amount - $totalPaid - $totalReturned;
                return $sale;
            });

        return view('app.owner.receivables.index', compact('sales'));
    }

    public function payForm(Sale $sale): View
    {
        if ($sale->business_id !== auth()->user()->business_id) abort(403);

        $sale->load(['payments', 'returns', 'branch', 'user']);

        $totalPaid = (float) $sale->payments->sum('amount');
        $totalReturned = (float) $sale->returns->sum('total_amount');
        $outstanding = (float) $sale->total_amount - $totalPaid - $totalReturned;

        return view('app.owner.receivables.pay', compact('sale', 'outstanding'));
    }

    public function payStore(Request $request, Sale $sale): RedirectResponse
    {
        if ($sale->business_id !== auth()->user()->business_id) abort(403);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'paid_at' => 'required|date',
            'method' => 'required|string|max:50',
        ]);

        DB::transaction(function () use ($validated, $sale) {
            SalePayment::create([
                'sale_id' => $sale->id,
                'amount' => $validated['amount'],
                'paid_at' => $validated['paid_at'],
                'method' => $validated['method'],
            ]);

            $this->stockService->recalculateSalePaymentStatus($sale);
        });

        activity()
            ->performedOn($sale)
            ->causedBy(auth()->user())
            ->withProperties(['amount' => $validated['amount'], 'method' => $validated['method']])
            ->log('Sale payment received');

        return redirect()
            ->route('app.owner.receivables.index')
            ->with('success', 'Pembayaran piutang berhasil dicatat.');
    }
}
