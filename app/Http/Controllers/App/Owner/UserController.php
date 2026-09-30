<?php

namespace App\Http\Controllers\App\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $kasir = User::where('business_id', auth()->user()->business_id)
            ->whereHas('roles', fn ($q) => $q->where('name', 'Kasir'))
            ->with('branch')
            ->latest()
            ->paginate(15);

        return view('app.owner.kasir.index', compact('kasir'));
    }

    /**
     * Owner hanya boleh MENGUBAH akun Kasir yang sudah ada di business-nya sendiri.
     *
     * Catatan desain: tabel `users` sengaja TIDAK memakai global scope generik
     * (lihat AGENTS.md bagian 2.1 — akan menyebabkan infinite recursion saat
     * resolve Auth::user()). Karena itu route model binding `{kasir}` TIDAK otomatis
     * ter-scope, dan pengecekan kepemilikan tenant dilakukan eksplisit di dalam
     * App\Policies\UserPolicy.
     */
    public function edit(User $kasir): View
    {
        $this->authorize('update', $kasir);

        $branches = Branch::where('business_id', auth()->user()->business_id)
            ->where('is_active', true)
            ->get();

        return view('app.owner.kasir.edit', compact('kasir', 'branches'));
    }

    public function update(Request $request, User $kasir): RedirectResponse
    {
        $this->authorize('update', $kasir);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email,' . $kasir->id,
            'branch_id' => 'required|exists:branches,id',
            'is_active' => 'boolean',
        ]);

        // Pastikan cabang tujuan benar-benar milik business Owner.
        Branch::where('id', $validated['branch_id'])
            ->where('business_id', auth()->user()->business_id)
            ->firstOrFail();

        $kasir->update($validated);

        return redirect()
            ->route('app.kasir.index')
            ->with('success', 'Kasir berhasil diupdate.');
    }

    public function resetPasswordForm(User $kasir): View
    {
        $this->authorize('resetPassword', $kasir);

        return view('app.owner.kasir.reset-password', compact('kasir'));
    }

    public function resetPassword(Request $request, User $kasir): RedirectResponse
    {
        $this->authorize('resetPassword', $kasir);

        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $kasir->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('app.kasir.index')
            ->with('success', 'Password kasir berhasil direset.');
    }
}
