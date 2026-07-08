<?php

namespace App\Http\Controllers\App\Owner;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use Illuminate\View\View;

class DebtController extends Controller
{
    public function index(): View
    {
        $debts = Purchase::where('business_id', auth()->user()->business_id)
            ->where('payment_status', '!=', 'paid')
            ->with(['supplier', 'branch'])
            ->latest()
            ->paginate(15);

        return view('app.owner.debts.index', compact('debts'));
    }
}
