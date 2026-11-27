<?php
namespace App\Services;

use App\Enums\ClaimStatus as C;
use App\Models\InsuranceClaim;
use App\Models\InsuranceCompany;
use App\Models\Invoice;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClaimService
{
    public function __construct(private AuditService $audit) {}

    /** Called inside BillingService::completeVisit (same transaction). */
    public function openFor(Invoice $invoice, InsuranceCompany $company, Patient $patient): InsuranceClaim
    {
        $claim = InsuranceClaim::create([
            'claim_number' => 'TMP-'.Str::uuid(), 'invoice_id' => $invoice->id, 'patient_id' => $patient->id,
            'insurance_company_id' => $company->id, 'amount_claimed' => $invoice->insurance_covered,
            'status' => C::Draft, 'created_by' => auth()->id(),
        ]);
        $claim->update(['claim_number' => sprintf('CLM-%s-%05d', now()->format('Ymd'), $claim->id)]);
        $this->event($claim, C::Draft, 'opened');

        return $claim;
    }

    public function submit(InsuranceClaim $c): InsuranceClaim
    {
        return DB::transaction(function () use ($c) {
            $c = $this->lock($c);
            $this->guard($c, C::Submitted);
            $c->update(['status' => C::Submitted, 'submitted_at' => now()]);
            $this->event($c, C::Submitted);
            $this->audit->log('claim.submitted', $c);

            return $c;
        });
    }

    /** Appeal a rejected claim (back to submitted). The patient shift stays until the new decision replaces it. */
    public function appeal(InsuranceClaim $c): InsuranceClaim
    {
        return $this->submit($c);
    }

    /**
     * Record the insurer's decision. Whatever the insurer will NOT pay is moved to the patient's invoice
     * (patient_shift). A re-decision first reverses the previous shift, so the invoice is always consistent.
     */
    public function decide(InsuranceClaim $c, C $to, float $approved = 0, ?string $reason = null): InsuranceClaim
    {
        return DB::transaction(function () use ($c, $to, $approved, $reason) {
            $c = $this->lock($c);
            $this->guard($c, $to);

            $claimed = $this->cents($c->amount_claimed);
            $ap = match ($to) {
                C::Approved => $claimed,
                C::Rejected => 0,
                default => $this->cents($approved),
            };
            if ($to === C::Rejected && blank($reason)) {
                throw ValidationException::withMessages(['reason' => __('clinic.errors.claim_reason')]);
            }
            if ($to === C::PartiallyApproved && ($ap <= 0 || $ap >= $claimed)) {
                throw ValidationException::withMessages(['approved' => __('clinic.errors.claim_amount')]);
            }

            $invoice = Invoice::query()->lockForUpdate()->findOrFail($c->invoice_id);
            $shift = $claimed - $ap;
            $total = $this->cents($invoice->total) - $this->cents($c->patient_shift) + $shift;
            $paid = $this->cents($invoice->paid);
            $invoice->update(['total' => $total / 100, 'status' => $paid >= $total ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid')]);

            $c->update([
                'status' => $to, 'amount_approved' => $ap / 100, 'patient_shift' => $shift / 100,
                'rejection_reason' => $to === C::Rejected ? $reason : null, 'decided_at' => now(),
            ]);
            $this->event($c, $to, $reason);
            $this->audit->log('claim.decided', $c);

            return $c;
        });
    }

    /** Money received FROM the insurer (kept separate from patient payments on the invoice). */
    public function recordPayment(InsuranceClaim $c, float $amount): InsuranceClaim
    {
        return DB::transaction(function () use ($c, $amount) {
            $c = $this->lock($c);
            if (! in_array($c->status, [C::Approved, C::PartiallyApproved], true)) {
                throw ValidationException::withMessages(['action' => __('clinic.errors.bad_transition')]);
            }
            $amt = $this->cents($amount);
            $remain = $this->cents($c->amount_approved) - $this->cents($c->amount_paid);
            if ($amt <= 0 || $amt > $remain) {
                throw ValidationException::withMessages(['amount' => __('clinic.errors.claim_pay')]);
            }

            $paid = $this->cents($c->amount_paid) + $amt;
            $done = $paid === $this->cents($c->amount_approved);
            $c->update(['amount_paid' => $paid / 100] + ($done ? ['status' => C::Paid, 'paid_at' => now()] : []));
            $this->event($c, $done ? C::Paid : $c->status, 'payment '.number_format($amt / 100, 2, '.', ''));
            $this->audit->log('claim.payment', $c);

            return $c;
        });
    }

    private function lock(InsuranceClaim $c): InsuranceClaim
    {
        return InsuranceClaim::query()->lockForUpdate()->findOrFail($c->id);
    }

    private function guard(InsuranceClaim $c, C $to): void
    {
        if (! $c->status->canMoveTo($to)) {
            throw ValidationException::withMessages(['action' => __('clinic.errors.bad_transition')]);
        }
    }

    private function event(InsuranceClaim $c, C $s, ?string $note = null): void
    {
        $c->events()->create(['status' => $s->value, 'note' => $note, 'user_id' => auth()->id()]);
    }

    /** Integer cents: no floating-point drift in money comparisons. */
    private function cents(float|string|null $v): int
    {
        return (int) round(((float) $v) * 100);
    }
}

/*
SYSTEM DESIGN:
Receivables workflow service. All steps run in transactions with row locks on claim AND invoice. Money arithmetic is done in integer cents. The patient_shift ledger makes decisions reversible (appeal -> re-decide) without double-charging the patient.

DATA STRUCTURE:
FSM (ClaimStatus) + append-only events. Integer-cents arithmetic. Compensating entry pattern: new_total = total - old_shift + new_shift.
*/
