<?php
namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\ClaimStatus as C;
use App\Models\Branch;
use App\Models\InsuranceClaim;
use App\Models\InsuranceCompany;
use App\Services\BillingService;
use App\Services\ClaimService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\MakesClinic;
use Tests\TestCase;

class InsuranceClaimsTest extends TestCase
{
    use MakesClinic, RefreshDatabase;

    /** Insured visit: fee 300, coverage 80% -> claim 240, patient owes 60. */
    private function insuredVisit(): array
    {
        $b = Branch::create(['name_ar' => 'ف', 'name_en' => 'B']);
        $co = InsuranceCompany::create(['name_ar' => 'ش', 'name_en' => 'Co', 'coverage_percent' => 80]);
        $doc = $this->makeDoctor($b);
        $p = $this->makePatient(['insurance_company' => 'Co', 'insurance_company_id' => $co->id]);
        $a = $this->makeAppointment($doc, $p, AppointmentStatus::InProgress);
        $inv = app(BillingService::class)->completeVisit($a);

        return [$inv->fresh(), $inv->claim()->first(), app(ClaimService::class), $b];
    }

    public function test_insured_visit_opens_a_draft_claim_with_company_coverage(): void
    {
        [$inv, $claim] = $this->insuredVisit();

        $this->assertSame('240.00', (string) $inv->insurance_covered);
        $this->assertSame('60.00', (string) $inv->total);
        $this->assertSame(C::Draft, $claim->status);
        $this->assertSame('240.00', (string) $claim->amount_claimed);
        $this->assertStringStartsWith('CLM-', $claim->claim_number);
    }

    public function test_rejection_moves_amount_to_patient_and_appeal_reverses_it(): void
    {
        [$inv, $claim, $svc] = $this->insuredVisit();
        $svc->submit($claim);
        $svc->decide($claim, C::Rejected, 0, 'not covered');
        $this->assertSame('300.00', (string) $inv->fresh()->total);

        $svc->appeal($claim);
        $svc->decide($claim, C::Approved);
        $this->assertSame('60.00', (string) $inv->fresh()->total);          // shift fully reversed
        $this->assertSame('240.00', (string) $claim->fresh()->amount_approved);
    }

    public function test_partial_approval_shifts_only_the_difference(): void
    {
        [$inv, $claim, $svc] = $this->insuredVisit();
        $svc->submit($claim);
        $svc->decide($claim, C::PartiallyApproved, 150);

        $this->assertSame('150.00', (string) $inv->fresh()->total);         // 60 + (240-150)
        $this->assertSame('90.00', (string) $claim->fresh()->patient_shift);
    }

    public function test_payments_are_capped_and_close_the_claim(): void
    {
        [, $claim, $svc] = $this->insuredVisit();
        $svc->submit($claim);
        $svc->decide($claim, C::Approved);

        $svc->recordPayment($claim, 100);
        $this->assertSame(C::Approved, $claim->fresh()->status);
        $svc->recordPayment($claim, 140);
        $this->assertSame(C::Paid, $claim->fresh()->status);

        $this->expectException(ValidationException::class);
        $svc->recordPayment($claim, 1);
    }

    public function test_invalid_transitions_and_bad_inputs_are_rejected(): void
    {
        [, $claim, $svc] = $this->insuredVisit();

        try { $svc->decide($claim, C::Approved); $this->fail('draft cannot be decided'); } catch (ValidationException) {}
        $svc->submit($claim);
        try { $svc->decide($claim, C::PartiallyApproved, 999); $this->fail('amount above claimed'); } catch (ValidationException) {}
        try { $svc->decide($claim, C::Rejected, 0, ''); $this->fail('reason required'); } catch (ValidationException) {}

        $this->assertSame(C::Submitted, $claim->fresh()->status);
    }

    public function test_only_finance_roles_manage_claims_and_doctors_cannot_open_the_page(): void
    {
        [, $claim, , $b] = $this->insuredVisit();
        $claim->load('invoice');

        $this->assertTrue($this->makeStaff('accountant', $b)->can('manage', $claim));
        $this->assertFalse($this->makeStaff('reception', $b)->can('manage', $claim));
        $this->actingAs($this->makeStaff('nurse', $b))->get('/insurance')->assertForbidden();
        $this->actingAs($this->makeStaff('accountant', $b))->get('/insurance')->assertOk();
        $this->assertSame(1, InsuranceClaim::count());
    }
}

/*
SYSTEM DESIGN:
Financial-correctness tests: coverage math, reversible patient shift (reject -> appeal -> approve), partial approval, capped payments, FSM guards, role separation.

DATA STRUCTURE:

*/
