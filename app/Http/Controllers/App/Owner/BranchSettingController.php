<?php

namespace App\Http\Controllers\App\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchSettingController extends Controller
{
    public function edit(Branch $branch): View
    {
        if ($branch->business_id !== auth()->user()->business_id) {
            abort(403);
        }

        $setting = BranchSetting::firstOrCreate(
            ['branch_id' => $branch->id],
            ['tax_enabled' => false, 'tax_percentage' => null]
        );

        return view('app.owner.branch-settings.edit', compact('branch', 'setting'));
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        if ($branch->business_id !== auth()->user()->business_id) {
            abort(403);
        }

        $validated = $request->validate([
            'tax_enabled' => 'boolean',
            'tax_percentage' => 'nullable|required_if:tax_enabled,true|numeric|min:0|max:100',
        ]);

        $setting = BranchSetting::updateOrCreate(
            ['branch_id' => $branch->id],
            [
                'tax_enabled' => $validated['tax_enabled'] ?? false,
                'tax_percentage' => ($validated['tax_enabled'] ?? false) ? ($validated['tax_percentage'] ?? 0) : null,
            ]
        );

        activity()
            ->performedOn($branch)
            ->causedBy(auth()->user())
            ->withProperties([
                'tax_enabled' => $setting->tax_enabled,
                'tax_percentage' => $setting->tax_percentage,
            ])
            ->log('Branch tax setting updated');

        return redirect()
            ->route('app.branches.index')
            ->with('success', 'Setting pajak cabang berhasil diupdate.');
    }
}
