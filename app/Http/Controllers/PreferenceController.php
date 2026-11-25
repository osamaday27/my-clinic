<?php
namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Branch;
use Illuminate\Http\Request;

class PreferenceController extends Controller
{
    public function locale(Request $request)
    {
        $data = $request->validate(['locale' => ['required', 'in:ar,en']]);
        $request->session()->put('locale', $data['locale']);

        return back();
    }

    public function branch(Request $request)
    {
        abort_unless($request->user()->role === Role::Manager, 403);
        $data = $request->validate(['branch_id' => ['required', 'integer', 'exists:branches,id']]);
        $request->session()->put('branch_id', (int) $data['branch_id']);

        return back();
    }
}

/*
SYSTEM DESIGN:
Thin controller (Rule 3): receives request -> validates -> returns. Only managers may switch branch.

DATA STRUCTURE:
Session key/value.
*/
