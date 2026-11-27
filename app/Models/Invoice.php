<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = ['number', 'patient_id', 'appointment_id', 'branch_id', 'subtotal', 'discount', 'insurance_covered', 'total', 'paid', 'status'];

    protected $casts = [
        'subtotal' => 'decimal:2', 'discount' => 'decimal:2', 'insurance_covered' => 'decimal:2',
        'total' => 'decimal:2', 'paid' => 'decimal:2',
    ];

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function items(): HasMany { return $this->hasMany(InvoiceItem::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function claim(): \Illuminate\Database\Eloquent\Relations\HasOne { return $this->hasOne(InsuranceClaim::class); }

    public function getBalanceAttribute(): string
    {
        return number_format((float) $this->total - (float) $this->paid, 2, '.', '');
    }
}

/*
SYSTEM DESIGN:
Invoice header. total = subtotal - discount - insurance_covered. Status derived from paid vs total (see BillingService::pay).

DATA STRUCTURE:
Master-detail with invoice_items and payments (ledger of partial payments).
*/
