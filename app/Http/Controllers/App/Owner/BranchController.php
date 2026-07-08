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
        return view('app.owner.branches.create');
    }

    public function store(Request $request): RedirectResponse
    {
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
        $this->authorizeBranch($branch);
        return view('app.owner.branches.edit', compact('branch'));
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $this->authorizeBranch($branch);

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

    private function authorizeBranch(Branch $branch): void
    {
        if ($branch->business_id !== auth()->user()->business_id) {
            abort(403);
        }
    }
}
