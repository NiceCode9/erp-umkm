<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class BusinessController extends Controller
{
    public function index(): View
    {
        $businesses = Business::all();
        return view('superadmin.businesses.index', compact('businesses'));
    }

    public function create(): View
    {
        return view('superadmin.businesses.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'address' => 'required|string',
        ]);

        $business = Business::create($validated);

        activity()
            ->performedOn($business)
            ->causedBy(auth()->user())
            ->log('Business created');

        return redirect()
            ->route('superadmin.businesses.index')
            ->with('success', 'Business berhasil ditambahkan');
    }

    public function edit(Business $business): View
    {
        return view('superadmin.businesses.edit', compact('business'));
    }

    public function update(Request $request, Business $business): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'address' => 'required|string',
        ]);

        $business->update($validated);

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
