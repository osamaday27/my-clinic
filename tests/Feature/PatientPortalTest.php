<?php
namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Branch;
use App\Models\LabRequest;
use App\Models\Prescription;
use App\Models\User;
use App\Services\AppointmentService;
use App\Services\PortalAccountService;
use App\Services\PortalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\MakesClinic;
use Tests\TestCase;

class PatientPortalTest extends TestCase
{
    use MakesClinic, RefreshDatabase;

    private function world(): array
    {
        $b = Branch::create(['name_ar' => 'ف', 'name_en' => 'B']);
        $doc = $this->makeDoctor($b);
        $svc = app(PortalAccountService::class);
        $pa = $this->makePatient();
        $pb = $this->makePatient();
        [$ua, $tempA] = $svc->create($pa, 'a@portal.test');
        [$ub] = $svc->create($pb, 'b@portal.test');
        $ua->forceFill(['must_change_password' => false])->save();
        $ub->forceFill(['must_change_password' => false])->save();

        return [$b, $doc, $pa, $pb, $ua->fresh(), $ub->fresh(), $tempA];
    }

    public function test_patient_is_redirected_away_from_staff_pages(): void
    {
        [, , , , $ua] = $this->world();

        $this->actingAs($ua)->get('/')->assertRedirect(route('portal'));
        $this->actingAs($ua)->get('/patients')->assertRedirect(route('portal'));
        $this->actingAs($ua)->get('/billing')->assertRedirect(route('portal'));
    }

    public function test_portal_home_renders_for_patient_and_is_closed_to_staff(): void
    {
        [$b, , $pa, , $ua] = $this->world();

        $this->actingAs($ua)->get('/portal')->assertOk()->assertSee($pa->name_ar);
        $this->actingAs($this->makeStaff('reception', $b))->get('/portal')->assertForbidden();
    }

    public function test_temporary_password_login_forces_password_change(): void
    {
        [, , , , , , $temp] = $this->world();
        User::where('email', 'a@portal.test')->update(['must_change_password' => true]);

        $this->post('/login', ['email' => 'a@portal.test', 'password' => $temp])->assertRedirect(route('portal'));
        $this->get('/portal')->assertRedirect(route('portal.password'));
    }

    public function test_patient_cannot_read_another_patients_documents(): void
    {
        Storage::fake('local');
        [$b, $doc, $pa, $pb, $ua, $ub] = $this->world();
        $rxB = Prescription::create(['patient_id' => $pb->id, 'doctor_id' => $doc->id, 'language' => 'ar']);
        $a = $this->makeAppointment($doc, $pb, AppointmentStatus::Completed);
        $lab = LabRequest::create(['patient_id' => $pb->id, 'doctor_id' => $doc->id, 'appointment_id' => $a->id, 'test_name' => 'CBC', 'status' => 'resulted', 'attachment_path' => 'lab/'.$pb->id.'/x.pdf']);
        Storage::disk('local')->put($lab->attachment_path, 'pdf');

        $this->actingAs($ua)->get(route('portal.rx', $rxB))->assertNotFound();
        $this->actingAs($ua)->get(route('portal.lab', $lab))->assertNotFound();
        $this->actingAs($ub)->get(route('portal.rx', $rxB))->assertOk();
        $this->actingAs($ub)->get(route('portal.lab', $lab))->assertOk();
    }

    public function test_unresulted_lab_files_are_hidden_from_the_patient(): void
    {
        Storage::fake('local');
        [, $doc, $pa, , $ua] = $this->world();
        $a = $this->makeAppointment($doc, $pa, AppointmentStatus::InProgress);
        $lab = LabRequest::create(['patient_id' => $pa->id, 'doctor_id' => $doc->id, 'appointment_id' => $a->id, 'test_name' => 'CBC', 'status' => 'requested', 'attachment_path' => 'lab/'.$pa->id.'/d.pdf']);
        Storage::disk('local')->put($lab->attachment_path, 'draft');

        $this->actingAs($ua)->get(route('portal.lab', $lab))->assertNotFound();
    }

    public function test_booking_is_forced_to_own_patient_and_enforces_hours_past_and_limits(): void
    {
        [, $doc, $pa, , $ua] = $this->world();
        $svc = app(PortalService::class);
        $tomorrow = now()->addDay()->toDateString();

        $a = $svc->book($ua, ['doctor_id' => $doc->id, 'appointment_date' => $tomorrow, 'start_time' => '10:00', 'patient_id' => 999999]);
        $this->assertSame($pa->id, $a->patient_id);

        foreach (['03:00', '18:00'] as $bad) {                                   // outside 09:00-17:00
            try { $svc->book($ua, ['doctor_id' => $doc->id, 'appointment_date' => $tomorrow, 'start_time' => $bad]); $this->fail('hours'); } catch (ValidationException) {}
        }
        $svc->book($ua, ['doctor_id' => $doc->id, 'appointment_date' => $tomorrow, 'start_time' => '11:00']);
        $svc->book($ua, ['doctor_id' => $doc->id, 'appointment_date' => $tomorrow, 'start_time' => '12:00']);

        $this->expectException(ValidationException::class);                      // 4th active booking (limit 3)
        $svc->book($ua, ['doctor_id' => $doc->id, 'appointment_date' => $tomorrow, 'start_time' => '13:00']);
    }

    public function test_past_times_are_rejected_for_everyone(): void
    {
        [, $doc, , , $ua] = $this->world();
        $this->travelTo(now()->setTime(12, 0));

        $this->expectException(ValidationException::class);
        app(PortalService::class)->book($ua, ['doctor_id' => $doc->id, 'appointment_date' => today()->toDateString(), 'start_time' => '10:00']);
    }

    public function test_cancel_rules_ownership_and_cutoff(): void
    {
        [, $doc, $pa, $pb, $ua] = $this->world();
        $svc = app(PortalService::class);
        $mine = $this->makeAppointment($doc, $pa, AppointmentStatus::Scheduled, '10:00:00');
        $theirs = $this->makeAppointment($doc, $pb, AppointmentStatus::Scheduled, '11:00:00');

        try { $svc->cancel($ua, $theirs->id); $this->fail('ownership'); } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {}

        $this->assertSame(AppointmentStatus::Cancelled, $svc->cancel($ua, $mine->id)->fresh()->status);

        $late = $this->makeAppointment($doc, $pa, AppointmentStatus::Scheduled, '14:00:00');
        $this->travelTo(now()->addDay()->setTime(13, 0));                          // 1h before start (< 2h cut-off)
        $this->expectException(ValidationException::class);
        $svc->cancel($ua, $late->id);
    }

    public function test_reception_provisions_and_resets_accounts_and_disabled_accounts_cannot_login(): void
    {
        $b = Branch::create(['name_ar' => 'ف', 'name_en' => 'B']);
        $p = $this->makePatient();
        $svc = app(PortalAccountService::class);

        [$u, $temp] = $svc->create($p, 'new@portal.test');
        $this->assertTrue($u->must_change_password);
        $this->assertNotSame($temp, $u->password);                               // stored hashed
        $this->assertTrue($this->makeStaff('reception', $b)->can('managePortal', $p));
        $this->assertFalse($this->makeStaff('nurse', $b)->can('managePortal', $p));

        try { $svc->create($p, 'second@portal.test'); $this->fail('one account per patient'); } catch (ValidationException) {}

        $svc->setActive($u, false);
        $this->post('/login', ['email' => 'new@portal.test', 'password' => $temp])->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}

/*
SYSTEM DESIGN:
Portal security regression tests: staff-page isolation, document IDOR (404), draft lab hiding, forced identity on booking, hours/past/limit enforcement, cancel cut-off, provisioning rules.

DATA STRUCTURE:

*/
