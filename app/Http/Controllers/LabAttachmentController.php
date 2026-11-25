<?php
namespace App\Http\Controllers;

use App\Models\LabRequest;
use App\Services\AuditService;
use App\Services\LabService;
use Illuminate\Support\Facades\Gate;

class LabAttachmentController extends Controller
{
    public function __invoke(LabRequest $lab, LabService $labs, AuditService $audit)
    {
        Gate::authorize('view', $lab);                              // IDOR guard
        abort_unless($lab->attachment_path && $labs->disk()->exists($lab->attachment_path), 404);

        $audit->log('lab.downloaded', $lab);
        $ext = pathinfo($lab->attachment_path, PATHINFO_EXTENSION);

        // Generic download name: no patient data in file names; forced download (no inline rendering).
        return $labs->disk()->download($lab->attachment_path, 'lab-'.$lab->id.'.'.$ext, ['X-Content-Type-Options' => 'nosniff']);
    }
}

/*
SYSTEM DESIGN:
Authorised streaming of private files (radiology/lab). Audit-logged on every download.

DATA STRUCTURE:
Streamed response from the private disk.
*/
