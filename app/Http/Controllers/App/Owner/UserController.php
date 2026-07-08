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

    public function create(): View
    {
        $branches = Branch::where('business_id', auth()->user()->business_id)
            ->where('is_active', true)
            ->get();

        return view('app.owner.kasir.create', compact('branches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'branch_id' => 'required|exists:branches,id',
        ]);

        // Verify branch belongs to Owner's business
        $branch = Branch::where('id', $validated['branch_id'])
            ->where('business_id', auth()->user()->business_id)
            ->firstOrFail();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'business_id' => auth()->user()->business_id,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $user->assignRole('Kasir');

        return redirect()
            ->route('app.kasir.index')
            ->with('success', "Kasir \"{$user->name}\" berhasil ditambahkan.");
    }

    public function edit(User $kasir): View
    {
        if ($kasir->business_id !== auth()->user()->business_id) {
            abort(403);
        }

        $branches = Branch::where('business_id', auth()->user()->business_id)
            ->where('is_active', true)
            ->get();

        return view('app.owner.kasir.edit', compact('kasir', 'branches'));
    }

    public function update(Request $request, User $kasir): RedirectResponse
    {
        if ($kasir->business_id !== auth()->user()->business_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email,' . $kasir->id,
            'branch_id' => 'required|exists:branches,id',
            'is_active' => 'boolean',
        ]);

        // Verify branch belongs to Owner's business
        Branch::where('id', $validated['branch_id'])
            ->where('business_id', auth()->user()->business_id)
            ->firstOrFail();

        $kasir->update($validated);

        return redirect()
            ->route('app.kasir.index')
            ->with('success', 'Kasir berhasil diupdate.');
    }
}
