<?php

namespace App\Http\Controllers\App\Kasir;

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
        $user = auth()->user();

        $sales = Sale::where('business_id', $user->business_id)
            ->where('branch_id', $user->branch_id)
            ->where('payment_status', '!=', 'paid')
            ->with(['user', 'payments', 'returns'])
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

        return view('app.kasir.receivables.index', compact('sales'));
    }

    public function payForm(Sale $sale): View
    {
        $user = auth()->user();
        if ($sale->business_id !== $user->business_id || $sale->branch_id !== $user->branch_id) abort(403);

        $sale->load(['payments', 'returns']);

        $totalPaid = (float) $sale->payments->sum('amount');
        $totalReturned = (float) $sale->returns->sum('total_amount');
        $outstanding = (float) $sale->total_amount - $totalPaid - $totalReturned;

        return view('app.kasir.receivables.pay', compact('sale', 'outstanding'));
    }

    public function payStore(Request $request, Sale $sale): RedirectResponse
    {
        $user = auth()->user();
        if ($sale->business_id !== $user->business_id || $sale->branch_id !== $user->branch_id) abort(403);

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

        return redirect()
            ->route('app.kasir.receivables.index')
            ->with('success', 'Pembayaran piutang berhasil dicatat.');
    }
}
