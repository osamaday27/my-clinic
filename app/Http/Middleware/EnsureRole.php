<?php
namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();

        // Deactivated accounts are kicked out immediately, even with a live session.
        if (! $user || ! $user->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            abort(403);
        }
        if (! in_array($user->role->value, $roles, true)) {
            if ($user->role === Role::Patient) {
                return redirect()->route('portal');         // patients never see staff pages (not even a 403)
            }
            abort(403);
        }

        return $next($request);
    }
}

/*
SYSTEM DESIGN:
RBAC gate at route level (coarse). Policies provide the fine-grained, object-level layer.

DATA STRUCTURE:
Whitelist of role strings per route group.
*/
