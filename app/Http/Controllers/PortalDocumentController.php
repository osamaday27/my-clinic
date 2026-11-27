<?php
namespace App\Http\Controllers;

use App\Models\LabRequest;
use App\Models\Prescription;
use App\Services\AuditService;
use App\Services\LabService;
use App\Services\PortalService;

class PortalDocumentController extends Controller
{
    public function prescription(Prescription $prescription, PortalService $portal, AuditService $audit)
    {
        $patient = $portal->patientOf(request()->user());
        abort_unless($prescription->patient_id === $patient->id, 404);          // 404, not 403: do not confirm the id exists

        $prescription->load(['patient', 'doctor.user', 'doctor.clinic', 'items.medicine']);
        $audit->log('portal.prescription_viewed', $prescription);
        app()->setLocale($prescription->language);

        return view('prescriptions.print', ['rx' => $prescription]);
    }

    public function lab(LabRequest $lab, PortalService $portal, LabService $labs, AuditService $audit)
    {
        $patient = $portal->patientOf(request()->user());
        abort_unless($lab->patient_id === $patient->id && $lab->status === 'resulted' && $lab->attachment_path && $labs->disk()->exists($lab->attachment_path), 404);

        $audit->log('portal.lab_downloaded', $lab);

        return $labs->disk()->download($lab->attachment_path, 'result-'.$lab->id.'.'.pathinfo($lab->attachment_path, PATHINFO_EXTENSION), ['X-Content-Type-Options' => 'nosniff']);
    }
}

/*
SYSTEM DESIGN:
Document access for patients: ownership check + only FINAL (resulted) lab files. Unknown/foreign ids return 404 to prevent enumeration.

DATA STRUCTURE:

*/
