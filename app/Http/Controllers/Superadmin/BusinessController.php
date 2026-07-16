<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBusinessRequest;
use App\Http\Requests\UpdateBusinessRequest;
use App\Models\Branch;
use App\Models\BranchSetting;
use App\Models\Business;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class BusinessController extends Controller
{
    public function index(): View
    {
        $businesses = Business::withCount('branches', 'users')
            ->latest()
            ->paginate(15);

        return view('superadmin.businesses.index', compact('businesses'));
    }

    public function show(Business $business): View
    {
        $business->load(['branches', 'users' => function ($q) {
            $q->with('roles', 'branch');
        }]);

        return view('superadmin.businesses.show', compact('business'));
    }

    public function create(): View
    {
        return view('superadmin.businesses.create');
    }

    public function store(StoreBusinessRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $business = DB::transaction(function () use ($validated) {
            $business = Business::create([
                'name' => $validated['name'],
                'owner_name' => $validated['owner_name'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
            ]);

            $owner = User::create([
                'name' => $validated['owner_name'],
                'email' => $validated['owner_email'],
                'password' => Hash::make($validated['owner_password']),
                'business_id' => $business->id,
                'branch_id' => null,
                'is_active' => true,
            ]);

            $owner->assignRole('Owner');

            activity()
                ->performedOn($business)
                ->causedBy(auth()->user())
                ->withProperties(['owner_email' => $validated['owner_email']])
                ->log('Business created');

            return $business;
        });

        return redirect()
            ->route('superadmin.businesses.show', $business)
            ->with('success', "Business \"{$business->name}\" berhasil dibuat.");
    }

    public function edit(Business $business): View
    {
        return view('superadmin.businesses.edit', compact('business'));
    }

    public function update(UpdateBusinessRequest $request, Business $business): RedirectResponse
    {
        $business->update($request->validated());

        activity()
            ->performedOn($business)
            ->causedBy(auth()->user())
            ->log('Business updated');

        return redirect()
            ->route('superadmin.businesses.show', $business)
            ->with('success', 'Business berhasil diupdate');
    }

    public function activate(Business $business): RedirectResponse
    {
        $business->update([
            'is_active' => true,
            'deactivated_at' => null,
            'deactivated_by' => null,
        ]);

        activity()
            ->performedOn($business)
            ->causedBy(auth()->user())
            ->log('Business activated');

        return back()->with('success', 'Business berhasil diaktifkan');
    }

    public function deactivate(Business $business): RedirectResponse
    {
        $business->update([
            'is_active' => false,
            'deactivated_at' => now(),
            'deactivated_by' => auth()->id(),
        ]);

        activity()
            ->performedOn($business)
            ->causedBy(auth()->user())
            ->log('Business deactivated');

        return back()->with('success', 'Business berhasil dinonaktifkan');
    }

    // ---- Branch management within business ----

    public function createBranch(Business $business): View
    {
        return view('superadmin.businesses.branches.create', compact('business'));
    }

    public function storeBranch(Business $business, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $business) {
            $branch = Branch::create([
                'business_id' => $business->id,
                'name' => $validated['name'],
                'address' => $validated['address'] ?? '',
                'is_active' => true,
            ]);

            BranchSetting::create([
                'branch_id' => $branch->id,
                'tax_enabled' => false,
            ]);
        });

        activity()
            ->performedOn($business)
            ->causedBy(auth()->user())
            ->withProperties(['branch_name' => $validated['name']])
            ->log('Branch created by Superadmin');

        return redirect()->route('superadmin.businesses.show', $business)
            ->with('success', "Cabang \"{$validated['name']}\" berhasil ditambahkan.");
    }

    // ---- Owner management within business ----

    public function createOwner(Business $business): View
    {
        return view('superadmin.businesses.users.create-owner', compact('business'));
    }

    public function storeOwner(Business $business, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $owner = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'business_id' => $business->id,
            'branch_id' => null,
            'is_active' => true,
        ]);

        $owner->assignRole('Owner');

        activity()
            ->performedOn($business)
            ->causedBy(auth()->user())
            ->withProperties(['email' => $validated['email'], 'role' => 'Owner'])
            ->log('Owner created by Superadmin');

        return redirect()->route('superadmin.businesses.show', $business)
            ->with('success', "Akun Owner \"{$validated['name']}\" berhasil ditambahkan.");
    }

    // ---- Kasir management within business ----

    public function createKasir(Business $business): View
    {
        $branches = Branch::where('business_id', $business->id)->where('is_active', true)->get();
        return view('superadmin.businesses.users.create-kasir', compact('business', 'branches'));
    }

    public function storeKasir(Business $business, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'branch_id' => 'required|exists:branches,id',
        ]);

        Branch::where('id', $validated['branch_id'])
            ->where('business_id', $business->id)
            ->firstOrFail();

        $kasir = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'business_id' => $business->id,
            'branch_id' => $validated['branch_id'],
            'is_active' => true,
        ]);

        $kasir->assignRole('Kasir');

        activity()
            ->performedOn($business)
            ->causedBy(auth()->user())
            ->withProperties(['email' => $validated['email'], 'role' => 'Kasir', 'branch_id' => $validated['branch_id']])
            ->log('Kasir created by Superadmin');

        return redirect()->route('superadmin.businesses.show', $business)
            ->with('success', "Akun Kasir \"{$validated['name']}\" berhasil ditambahkan.");
    }
}
