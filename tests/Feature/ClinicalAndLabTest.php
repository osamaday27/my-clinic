<?php
namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Branch;
use App\Services\ClinicalService;
use App\Services\LabService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\MakesClinic;
use Tests\TestCase;

class ClinicalAndLabTest extends TestCase
{
    use MakesClinic, RefreshDatabase;

    public function test_nurse_can_chart_only_own_branch_and_open_visits(): void
    {
        $b = Branch::create(['name_ar' => 'ف', 'name_en' => 'B']);
        $b2 = Branch::create(['name_ar' => 'ف2', 'name_en' => 'B2']);
        $doc = $this->makeDoctor($b);
        $a = $this->makeAppointment($doc, $this->makePatient(), AppointmentStatus::CheckedIn);
        $nurse = $this->makeStaff('nurse', $b);
        $other = \App\Models\User::create(['name' => 'n2', 'email' => 'n2@x.test', 'password' => 'Secret@12345', 'role' => 'nurse', 'branch_id' => $b2->id, 'is_active' => true]);

        $this->assertTrue($nurse->can('chart', $a));
        $this->assertFalse($other->can('chart', $a));
        $this->assertFalse($nurse->can('treat', $a));       // nurse may not diagnose
    }

    public function test_vitals_are_saved_and_empty_or_closed_visits_rejected(): void
    {
        $b = Branch::create(['name_ar' => 'ف', 'name_en' => 'B']);
        $doc = $this->makeDoctor($b);
        $nurse = $this->makeStaff('nurse', $b);
        $svc = app(ClinicalService::class);
        $open = $this->makeAppointment($doc, $this->makePatient(), AppointmentStatus::CheckedIn);

        $v = $svc->recordVitals($open, ['bp_systolic' => 120, 'bp_diastolic' => 80, 'heart_rate' => '', 'evil' => 1], $nurse);
        $this->assertSame('120/80', $v->blood_pressure);

        try {
            $svc->recordVitals($open, ['heart_rate' => ''], $nurse);
            $this->fail('empty');
        } catch (ValidationException) {
        }

        $closed = $this->makeAppointment($doc, $this->makePatient(), AppointmentStatus::Completed, '11:00:00');
        $this->expectException(ValidationException::class);
        $svc->recordVitals($closed, ['heart_rate' => 70], $nurse);
    }

    public function test_diagnosis_and_lab_request_are_created_for_open_visit(): void
    {
        $b = Branch::create(['name_ar' => 'ف', 'name_en' => 'B']);
        $doc = $this->makeDoctor($b);
        $a = $this->makeAppointment($doc, $this->makePatient(), AppointmentStatus::InProgress);
        $svc = app(ClinicalService::class);

        $r = $svc->diagnose($a, ['diagnosis' => 'Flu', 'icd_code' => 'J10', 'notes' => 'rest']);
        $l = $svc->requestLab($a, 'lab', 'CBC');

        $this->assertSame($a->patient_id, $r->patient_id);
        $this->assertSame('requested', $l->status);
        $this->assertStringNotContainsString('rest', \DB::table('medical_records')->value('notes'));   // encrypted at rest
    }

    public function test_lab_attachment_is_private_and_policy_protected(): void
    {
        Storage::fake('local');
        $b = Branch::create(['name_ar' => 'ف', 'name_en' => 'B']);
        $d1 = $this->makeDoctor($b, 'd1@x.test');
        $d2 = $this->makeDoctor($b, 'd2@x.test');
        $nurse = $this->makeStaff('nurse', $b);
        $a = $this->makeAppointment($d1, $this->makePatient(), AppointmentStatus::InProgress);
        $lab = app(ClinicalService::class)->requestLab($a, 'radiology', 'X-Ray');

        app(LabService::class)->recordResult($lab, 'normal', UploadedFile::fake()->create('scan.pdf', 100, 'application/pdf'), $nurse);
        $lab->refresh();

        $this->assertSame('resulted', $lab->status);
        Storage::disk('local')->assertExists($lab->attachment_path);
        $this->assertStringStartsWith('lab/'.$a->patient_id.'/', $lab->attachment_path);

        $this->actingAs($d1->user)->get(route('labs.attachment', $lab))->assertOk();
        $this->actingAs($d2->user)->get(route('labs.attachment', $lab))->assertForbidden();
    }

    public function test_result_requires_text_or_file(): void
    {
        Storage::fake('local');
        $b = Branch::create(['name_ar' => 'ف', 'name_en' => 'B']);
        $doc = $this->makeDoctor($b);
        $a = $this->makeAppointment($doc, $this->makePatient(), AppointmentStatus::InProgress);
        $lab = app(ClinicalService::class)->requestLab($a, 'lab', 'CBC');

        $this->expectException(ValidationException::class);
        app(LabService::class)->recordResult($lab, '', null, $this->makeStaff('nurse', $b));
    }
}

/*
SYSTEM DESIGN:
Tests for the new clinical + lab workflow: branch/role permissions, whitelist & state guards, encryption at rest, private-disk attachments, IDOR on downloads.

DATA STRUCTURE:

*/
