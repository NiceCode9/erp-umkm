<?php

namespace App\Http\Controllers\App\Owner;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DebtController extends Controller
{
    /**
     * Halaman Utang Supplier.
     *
     * Dua section terpisah:
     * - Utang Outstanding : outstanding > 0 (kita owe supplier)
     * - Kredit/Klaim       : outstanding < 0 (retur melebihi sisa utang → supplier owe kita)
     *
     * FORMULA (BUSINESS-RULES.md bagian 5):
     *   outstanding = total_amount - SUM(payments) - SUM(returns.total_amount)
     *
     * Kolom outstanding TIDAK di-clamp ke 0. Kalau di-clamp, kredit akibat retur
     * akan hilang dari halaman ini (bug lama: retur tidak pernah mengurangi utang).
     */
    public function index(): View
    {
        $businessId = auth()->user()->business_id;

        $purchases = Purchase::where('business_id', $businessId)
            ->with(['supplier', 'branch'])
            ->withSum(['payments as paid_amount'], 'amount')
            ->withSum(['returns as returned_amount'], 'total_amount')
            // created_at ASC = paling lama belum dibayar dulu
            // (BUSINESS-RULES.md bagian 6, proxy paling lama belum dibayar).
            ->orderBy('created_at')
            ->paginate(15);

        $purchases->getCollection()->each(function (Purchase $purchase) {
            $purchase->outstanding_amount = (float) $purchase->total_amount
                - (float) $purchase->paid_amount
                - (float) $purchase->returned_amount;
        });

        // Pisahkan utang vs kredit di level collection agar view tetap sederhana.
        $grouped = $this->groupByOutstanding($purchases->getCollection());

        $payables = $grouped['payable'];
        $credits = $grouped['credit'];

        // Credit_summary untuk badge di header.
        $creditTotal = $credits->sum(fn (Purchase $p) => abs((float) $p->outstanding_amount));
        $payableTotal = $payables->sum(fn (Purchase $p) => (float) $p->outstanding_amount);

        return view('app.owner.debts.index', compact(
            'purchases',
            'payables',
            'credits',
            'creditTotal',
            'payableTotal',
        ));
    }

    /**
     * @param  Collection<int, Purchase>  $purchases
     * @return array{payable: Collection, credit: Collection}
     */
    private function groupByOutstanding(Collection $purchases): array
    {
        return [
            'payable' => $purchases
                ->filter(fn (Purchase $p) => (float) $p->outstanding_amount > 0)
                ->values(),
            'credit' => $purchases
                ->filter(fn (Purchase $p) => (float) $p->outstanding_amount < 0)
                ->values(),
        ];
    }
}