<?php
namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function login(Request $request, AuditService $audit)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);

        if (! Auth::attempt($data + ['is_active' => true], $request->boolean('remember'))) {
            return back()->withErrors(['email' => __('clinic.errors.login')])->onlyInput('email');
        }

        $request->session()->regenerate();          // session-fixation protection
        $audit->log('auth.login');

        return redirect()->intended('/');
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
