<?php

namespace App\Http\Controllers\App\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CashierShift;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShiftController extends Controller
{
    public function index(Request $request): View
    {
        $businessId = auth()->user()->business_id;

        $query = CashierShift::where('cashier_shifts.business_id', $businessId)
            ->with(['user', 'branch'])
            ->whereNotNull('closed_at')
            ->latest('opened_at');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('opened_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('opened_at', '<=', $request->date_to);
        }

        $shift = $request->filled('branch_id') ? $request->branch_id : null;
        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;

        $branches = Branch::where('business_id', $businessId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $shifts = $query->paginate(20);

        return view('app.owner.shifts.index', compact('shifts', 'branches', 'shift', 'dateFrom', 'dateTo'));
    }
}
