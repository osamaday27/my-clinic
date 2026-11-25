<?php
namespace App\Http\Controllers;

use App\Models\Prescription;
use App\Services\AuditService;

class PrescriptionPrintController extends Controller
{
    public function __invoke(Prescription $prescription, AuditService $audit)
    {
        $prescription->load(['patient', 'doctor.user', 'doctor.clinic', 'items.medicine']);
        abort_unless(request()->user()->can('view', $prescription->patient), 403);   // IDOR guard
        $audit->log('prescription.printed', $prescription);

        app()->setLocale($prescription->language);        // printed in the PATIENT's language (Rule 22)

        return view('prescriptions.print', ['rx' => $prescription]);
    }
}

/*
SYSTEM DESIGN:
Printable prescription. Route model binding + policy check on the owning patient.

DATA STRUCTURE:
Eager-loaded graph: prescription -> items -> medicine (3 queries total).
*/
