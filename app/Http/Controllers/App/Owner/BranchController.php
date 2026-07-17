<?php

namespace App\Http\Controllers\App\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function create(): View
    {
        $this->authorize('create', Branch::class);
        return view('app.owner.branches.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Branch::class);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
        ]);

        $branch = DB::transaction(function () use ($validated) {
            $branch = Branch::create([
                'business_id' => auth()->user()->business_id,
                'name' => $validated['name'],
                'address' => $validated['address'] ?? '',
                'is_active' => true,
            ]);

            BranchSetting::create([
                'branch_id' => $branch->id,
                'tax_enabled' => false,
                'tax_percentage' => null,
            ]);

            return $branch;
        });

        return redirect()
            ->route('app.branches.index')
            ->with('success', "Cabang \"{$branch->name}\" berhasil ditambahkan.");
    }

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

    public function destroy(Branch $branch): RedirectResponse
    {
        $this->authorize('delete', $branch);

        $branch->delete();

        return redirect()
            ->route('app.branches.index')
            ->with('success', 'Cabang berhasil dihapus.');
    }
}
}
