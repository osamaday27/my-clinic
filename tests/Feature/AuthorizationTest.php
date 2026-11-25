<?php
namespace Tests\Feature;

use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesClinic;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use MakesClinic, RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_doctor_cannot_open_other_doctors_patient_idor(): void
    {
        $b = Branch::create(['name_ar' => 'ف', 'name_en' => 'B']);
        $d1 = $this->makeDoctor($b, 'd1@x.test');
        $d2 = $this->makeDoctor($b, 'd2@x.test');
        $mine = $this->makePatient();
        $theirs = $this->makePatient();
        $this->makeAppointment($d1, $mine);
        $this->makeAppointment($d2, $theirs, time: '11:00:00');

        $this->assertTrue($d1->user->can('view', $mine));
        $this->assertFalse($d1->user->can('view', $theirs));
        $this->assertSame([$mine->id], \App\Models\Patient::visibleTo($d1->user)->pluck('id')->all());
    }

    public function test_role_middleware_blocks_pharmacist_from_patients(): void
    {
        $b = Branch::create(['name_ar' => 'ف', 'name_en' => 'B']);
        $this->actingAs($this->makeStaff('pharmacist', $b))->get('/patients')->assertForbidden();
    }

    public function test_deactivated_user_is_rejected(): void
    {
        $b = Branch::create(['name_ar' => 'ف', 'name_en' => 'B']);
        $u = $this->makeStaff('reception', $b);
        $u->update(['is_active' => false]);

        $this->actingAs($u)->get('/')->assertForbidden();
    }

    public function test_security_headers_present_on_login(): void
    {
        $this->get('/login')->assertHeader('X-Frame-Options', 'DENY')->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_patient_sensitive_fields_are_encrypted_at_rest(): void
    {
        $p = $this->makePatient(['allergies' => 'Penicillin']);
        $raw = \DB::table('patients')->where('id', $p->id)->value('allergies');

        $this->assertStringNotContainsString('Penicillin', $raw);
        $this->assertSame('Penicillin', $p->fresh()->allergies);
    }
}

/*
SYSTEM DESIGN:
Security regression tests: IDOR, RBAC, deactivated accounts, headers, encryption at rest.

DATA STRUCTURE:

*/
