<?php

namespace App\Http\Controllers\App\Kasir;

use App\Http\Controllers\Controller;
use App\Models\CashierShift;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CashierShiftController extends Controller
{
    public function openForm(): View
    {
        return view('app.kasir.shifts.open');
    }

    public function openStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'opening_cash' => 'required|numeric|min:0',
        ]);

        $user = auth()->user();

        $existingShift = CashierShift::where('user_id', $user->id)
            ->whereNull('closed_at')
            ->first();

        if ($existingShift) {
            return redirect()->route('app.kasir.pos')
                ->with('error', 'Anda masih memiliki shift yang aktif. Tutup shift terlebih dahulu.');
        }

        CashierShift::create([
            'business_id' => $user->business_id,
            'branch_id' => $user->branch_id,
            'user_id' => $user->id,
            'opening_cash' => $validated['opening_cash'],
            'opened_at' => now(),
        ]);

        return redirect()->route('app.kasir.pos')
            ->with('success', 'Shift berhasil dibuka. Selamat bertugas!');
    }

    public function closeForm(): View
    {
        $user = auth()->user();

        $shift = CashierShift::where('user_id', $user->id)
            ->whereNull('closed_at')
            ->firstOrFail();

        $shift->load('sales');

        $totalCashSales = (float) Sale::where('cashier_shift_id', $shift->id)
            ->where('payment_method', 'tunai')
            ->sum('total_amount');

        $closingCashSystem = $shift->opening_cash + $totalCashSales;

        return view('app.kasir.shifts.close', compact('shift', 'totalCashSales', 'closingCashSystem'));
    }

    public function closeStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'closing_cash_actual' => 'required|numeric|min:0',
        ]);

        $user = auth()->user();

        $shift = CashierShift::where('user_id', $user->id)
            ->whereNull('closed_at')
            ->firstOrFail();

        $totalCashSales = (float) Sale::where('cashier_shift_id', $shift->id)
            ->where('payment_method', 'tunai')
            ->sum('total_amount');

        $closingCashSystem = $shift->opening_cash + $totalCashSales;
        $difference = $validated['closing_cash_actual'] - $closingCashSystem;

        $shift->update([
            'closing_cash_system' => $closingCashSystem,
            'closing_cash_actual' => $validated['closing_cash_actual'],
            'difference' => $difference,
            'closed_at' => now(),
        ]);

        activity()
            ->performedOn($shift)
            ->causedBy($user)
            ->withProperties([
                'closing_cash_system' => $closingCashSystem,
                'closing_cash_actual' => $validated['closing_cash_actual'],
                'difference' => $difference,
            ])
            ->log('Shift closed');

        return redirect()->route('app.kasir.shifts.open')
            ->with('success', "Shift berhasil ditutup. Selisih: " . format_currency($difference));
    }
}
