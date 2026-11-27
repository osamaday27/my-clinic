<?php
namespace App\Models;

use App\Enums\ClaimStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InsuranceClaim extends Model
{
    use SoftDeletes;

    protected $fillable = ['claim_number', 'invoice_id', 'patient_id', 'insurance_company_id', 'amount_claimed', 'amount_approved', 'amount_paid',
        'patient_shift', 'status', 'rejection_reason', 'submitted_at', 'decided_at', 'paid_at', 'created_by'];

    protected function casts(): array
    {
        return [
            'status' => ClaimStatus::class,
            'amount_claimed' => 'decimal:2', 'amount_approved' => 'decimal:2', 'amount_paid' => 'decimal:2', 'patient_shift' => 'decimal:2',
            'submitted_at' => 'datetime', 'decided_at' => 'datetime', 'paid_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function company(): BelongsTo { return $this->belongsTo(InsuranceCompany::class, 'insurance_company_id'); }
    public function events(): HasMany { return $this->hasMany(InsuranceClaimEvent::class, 'claim_id'); }

    public function getOutstandingAttribute(): string
    {
        return number_format((float) $this->amount_approved - (float) $this->amount_paid, 2, '.', '');
    }
}

/*
SYSTEM DESIGN:
Claim aggregate: one per invoice; money columns are DECIMAL; status is an enum-FSM.

DATA STRUCTURE:
Aggregate root with append-only events.
*/
