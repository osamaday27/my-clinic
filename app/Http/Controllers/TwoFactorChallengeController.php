<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditService;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwoFactorChallengeController extends Controller
{
    public function show(Request $request)
    {
        if (! $request->session()->has('two_factor')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request, TwoFactorService $tf, AuditService $audit)
    {
        $pending = $request->session()->get('two_factor');
        if (! $pending || now()->timestamp - $pending['at'] > config('clinic.two_factor.challenge_ttl')) {
            $request->session()->forget('two_factor');

            return redirect()->route('login')->withErrors(['email' => __('clinic.errors.tf_session')]);
        }

        $user = User::query()->where('is_active', true)->find($pending['id']);
        if (! $user) {
            $request->session()->forget('two_factor');

            return redirect()->route('login');
        }

        $data = $request->validate(['code' => ['nullable', 'string', 'max:32'], 'recovery_code' => ['nullable', 'string', 'max:32']]);
        $ok = filled($data['recovery_code'] ?? null)
            ? $tf->useRecoveryCode($user, $data['recovery_code'])
            : $tf->verifyAndConsume($user, (string) ($data['code'] ?? ''));

        if (! $ok) {
            return back()->withErrors(['code' => __('clinic.errors.tf_invalid')]);
        }

        $request->session()->forget('two_factor');
        Auth::login($user, (bool) $pending['remember']);
        $request->session()->regenerate();
        $audit->log('auth.login.2fa');

        return redirect()->intended('/');
    }
}

/*
SYSTEM DESIGN:
Second login step. Until this succeeds the user is NOT authenticated (only a pending marker with a 5-minute TTL exists in the session). Route is throttled (5/min) and codes are single-use.

DATA STRUCTURE:
Session record {id, remember, at}; TTL check by timestamp difference.
*/
