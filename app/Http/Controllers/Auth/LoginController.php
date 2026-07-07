<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Auth\Events\Authenticated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'Email harus diisi',
            'email.email' => 'Format email tidak valid',
            'password.required' => 'Password harus diisi',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();
            event(new Authenticated($user));

            if ($user->hasRole('Superadmin')) {
                return redirect()->route('superadmin.dashboard');
            }

            if ($user->business && !$user->business->is_active) {
                return redirect()->route('app.business-inactive');
            }

            return redirect()->route('app.dashboard');
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }
}
