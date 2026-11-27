<?php
namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function login(Request $request, AuditService $audit)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);

        $provider = Auth::getProvider();
        $user = $provider->retrieveByCredentials(['email' => $data['email'], 'is_active' => true]);

        if (! $user) {
            Hash::check($data['password'], '$2y$12$'.str_repeat('a', 53));   // equalise timing -> no user enumeration
        }
        if (! $user || ! $provider->validateCredentials($user, $data)) {
            return back()->withErrors(['email' => __('clinic.errors.login')])->onlyInput('email');
        }

        if ($user->hasTwoFactor()) {
            // Password OK but NOT logged in yet: only a pending marker lives in the session.
            $request->session()->put('two_factor', ['id' => $user->id, 'remember' => $request->boolean('remember'), 'at' => now()->timestamp]);

            return redirect()->route('two-factor.challenge');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();          // session-fixation protection
        $audit->log('auth.login');

        return redirect()->intended($user->homeUrl());
    }

    public function logout(Request $request, AuditService $audit)
    {
        $audit->log('auth.logout');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

/*
SYSTEM DESIGN:
Session auth with brute-force throttle (route middleware throttle:5,1), session regeneration on login, inactive users rejected inside the credential query, audit entries for login/logout.

DATA STRUCTURE:
Credential array + is_active condition -> single indexed lookup on users.email.
*/
