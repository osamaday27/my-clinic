<?php
namespace Tests\Feature;

use App\Models\Branch;
use App\Models\InsuranceCompany;
use App\Models\Patient;
use App\Services\PatientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesClinic;
use Tests\TestCase;

class PatientRegistrationTest extends TestCase
{
    use MakesClinic, RefreshDatabase;

    public function test_register_generates_file_number_and_ignores_unlisted_fields(): void
    {
        $co = InsuranceCompany::create(['name_ar' => 'ش', 'name_en' => 'Misr', 'coverage_percent' => 80]);
        $p = app(PatientService::class)->register([
            'name_ar' => 'سارة', 'phone' => '01012345678', 'allergies' => ' Penicillin ', 'national_id' => '',
            'insurance_company_id' => $co->id, 'file_number' => 'HACK-1', 'id' => 999,
        ]);

        $this->assertSame('P-'.str_pad((string) $p->id, 5, '0', STR_PAD_LEFT), $p->file_number);
        $this->assertNotSame(999, $p->id);
        $this->assertSame('Penicillin', $p->allergies);
        $this->assertNull($p->national_id);
        $this->assertSame('Misr', $p->insurance_company);
    }

    public function test_update_changes_data_and_clears_insurance(): void
    {
        $p = app(PatientService::class)->register(['name_ar' => 'أ', 'phone' => '0100000001']);
        app(PatientService::class)->update($p, ['name_ar' => 'ب', 'phone' => '0100000002', 'insurance_company_id' => '']);

        $this->assertSame('ب', $p->fresh()->name_ar);
        $this->assertNull($p->fresh()->insurance_company);
    }

    public function test_only_front_desk_roles_can_create_patients(): void
    {
        $b = Branch::create(['name_ar' => 'ف', 'name_en' => 'B']);

        $this->assertTrue($this->makeStaff('reception', $b)->can('create', Patient::class));
        $this->assertTrue($this->makeStaff('manager', $b)->can('create', Patient::class));
        $this->assertFalse($this->makeStaff('doctor', $b)->can('create', Patient::class));
        $this->assertFalse($this->makeStaff('pharmacist', $b)->can('create', Patient::class));
    }

    public function test_duplicate_phone_detection_ignores_self(): void
    {
        $svc = app(PatientService::class);
        $p = $svc->register(['name_ar' => 'أ', 'phone' => '0100000009']);

        $this->assertTrue($svc->phoneExists('0100000009'));
        $this->assertFalse($svc->phoneExists('0100000009', $p->id));
    }
}

/*
SYSTEM DESIGN:
Registration tests: whitelist, file-number generation, normalisation, policy, duplicate detection.

DATA STRUCTURE:

*/
