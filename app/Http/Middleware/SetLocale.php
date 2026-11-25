<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->session()->get('locale', 'ar');
        app()->setLocale(in_array($locale, ['ar', 'en'], true) ? $locale : 'ar');

        return $next($request);
    }
}

/*
SYSTEM DESIGN:
Locale resolved from session whitelist (never from raw input) -> no path traversal into lang files.

DATA STRUCTURE:
Whitelist ['ar','en'].
*/
