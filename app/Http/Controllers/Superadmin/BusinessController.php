<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBusinessRequest;
use App\Http\Requests\UpdateBusinessRequest;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class BusinessController extends Controller
{
    public function index(): View
    {
        $businesses = Business::withCount('branches')
            ->with(['users' => function ($q) {
                $q->whereHas('roles', fn ($r) => $r->where('name', 'Owner'))->limit(1);
            }])
            ->latest()
            ->paginate(15);

        return view('superadmin.businesses.index', compact('businesses'));
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
            ->route('superadmin.businesses.index')
            ->with('success', "Business \"{$business->name}\" berhasil dibuat beserta akun Owner.");
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
            ->route('superadmin.businesses.index')
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
}
