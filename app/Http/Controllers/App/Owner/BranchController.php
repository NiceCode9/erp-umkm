<?php

namespace App\Http\Controllers\App\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(): View
    {
        $branches = Branch::where('business_id', auth()->user()->business_id)
            ->latest()
            ->paginate(15);

        return view('app.owner.branches.index', compact('branches'));
    }

    /**
     * Owner hanya boleh MENGUBAH cabang yang sudah ada.
     * Penambahan & penghapusan cabang hanya lewat Superadmin
     * (/superadmin/businesses/{business}/branches/create) — lihat PERMISSIONS.md.
     */
    public function edit(Branch $branch): View
    {
        $this->authorize('update', $branch);

        return view('app.owner.branches.edit', compact('branch'));
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $this->authorize('update', $branch);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $branch->update($validated);

        return redirect()
            ->route('app.branches.index')
            ->with('success', 'Cabang berhasil diupdate.');
    }
}
