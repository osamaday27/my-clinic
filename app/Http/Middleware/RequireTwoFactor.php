<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireTwoFactor
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $required = config('clinic.two_factor.required_roles');

        if ($user && in_array($user->role->value, $required, true) && ! $user->hasTwoFactor()
            && ! $request->routeIs('security', 'logout', 'locale')) {
            return redirect()->route('security');          // forced enrolment for configured roles
        }

        return $next($request);
    }
}

/*
SYSTEM DESIGN:
Policy enforcement point: roles listed in CLINIC_2FA_REQUIRED_ROLES (e.g. manager,doctor,accountant) cannot use the system until 2FA is enabled.

DATA STRUCTURE:
Whitelist of role strings from config.
*/
