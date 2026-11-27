<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->must_change_password && ! $request->routeIs('portal.password', 'logout', 'locale')) {
            return redirect()->route('portal.password');
        }

        return $next($request);
    }
}

/*
SYSTEM DESIGN:
Temporary passwords are single-purpose: nothing else in the portal is reachable until the patient chooses their own password.

DATA STRUCTURE:
Boolean flag on the user row.
*/
