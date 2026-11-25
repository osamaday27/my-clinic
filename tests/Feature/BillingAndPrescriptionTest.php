<?php
namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Branch;
use App\Models\DoctorCommission;
use App\Models\Medicine;
use App\Models\StockItem;
use App\Services\BillingService;
use App\Services\PrescriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\MakesClinic;
use Tests\TestCase;

class BillingAndPrescriptionTest extends TestCase
{
    use MakesClinic, RefreshDatabase;

    private function setupVisit(int $stock = 10): array
    {
        $b = Branch::create(['name_ar' => 'ف', 'name_en' => 'B']);
        $doc = $this->makeDoctor($b);
        $a = $this->makeAppointment($doc, $this->makePatient(), AppointmentStatus::InProgress);
        $item = StockItem::create(['branch_id' => $b->id, 'sku' => 'S1', 'name' => 'Gloves', 'quantity' => $stock]);

        return [$doc, $a, $item, $this->makeStaff('accountant', $b)];
    }

    public function test_complete_visit_creates_invoice_commission_and_deducts_stock(): void
    {
        [$doc, $a, $item] = $this->setupVisit();

        $inv = app(BillingService::class)->completeVisit($a, [
            ['description' => 'Consultation', 'quantity' => 1, 'unit_price' => 300, 'doctor_id' => $doc->id],
            ['description' => 'Gloves', 'quantity' => 2, 'unit_price' => 10, 'stock_item_id' => $item->id],
        ]);

        $this->assertSame('320.00', (string) $inv->total);
        $this->assertSame(8, $item->fresh()->quantity);
        $this->assertSame('90.00', (string) DoctorCommission::first()->amount);     // 30% of 300
        $this->assertSame(AppointmentStatus::Completed, $a->fresh()->status);
    }

    public function test_insufficient_stock_rolls_everything_back(): void
    {
        [$doc, $a, $item] = $this->setupVisit(1);

        try {
            app(BillingService::class)->completeVisit($a, [['description' => 'Gloves', 'quantity' => 5, 'unit_price' => 10, 'stock_item_id' => $item->id]]);
            $this->fail('Expected exception');
        } catch (ValidationException) {
        }

        $this->assertSame(AppointmentStatus::InProgress, $a->fresh()->status);
        $this->assertSame(0, \App\Models\Invoice::count());
        $this->assertSame(1, $item->fresh()->quantity);
    }

    public function test_partial_then_full_payment_and_overpayment_rejected(): void
    {
        [, $a, , $acc] = $this->setupVisit();
        $svc = app(BillingService::class);
        $inv = $svc->completeVisit($a);                     // default fee 300

        $svc->pay($inv, 100, 'cash', $acc);
        $this->assertSame('partial', $inv->fresh()->status);
        $svc->pay($inv, 200, 'card', $acc);
        $this->assertSame('paid', $inv->fresh()->status);

        $this->expectException(ValidationException::class);
        $svc->pay($inv, 1, 'cash', $acc);
    }

    public function test_allergy_conflict_blocks_prescription_unless_acknowledged(): void
    {
        $b = Branch::create(['name_ar' => 'ف', 'name_en' => 'B']);
        $doc = $this->makeDoctor($b);
        $p = $this->makePatient(['allergies' => 'Penicillin']);
        $m = Medicine::create(['name_ar' => 'أموكسيسيلين', 'name_en' => 'Amoxicillin', 'generic_name' => 'amoxicillin (penicillin class)']);
        $items = [['medicine_id' => $m->id, 'dose' => '1', 'frequency' => '3/day', 'duration_days' => 5]];
        $svc = app(PrescriptionService::class);

        try {
            $svc->issue($doc, $p, $items, null);
            $this->fail('Expected allergy block');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('items', $e->errors());
        }

        $this->assertNotNull($svc->issue($doc, $p, $items, null, 'ar', null, true)->id);
    }
}

/*
SYSTEM DESIGN:
Tests for the transactional unit-of-work (all-or-nothing), money ledger rules, and the drug-allergy safety net.

DATA STRUCTURE:

*/
