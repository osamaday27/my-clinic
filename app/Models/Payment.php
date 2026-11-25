<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['invoice_id', 'received_by', 'amount', 'method', 'paid_at'];

    protected $casts = ['amount' => 'decimal:2', 'paid_at' => 'datetime'];

    public function invoice(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}

/*
SYSTEM DESIGN:
Immutable payment ledger row (no soft delete; corrections are made with a new row).

DATA STRUCTURE:
Append-only ledger; daily revenue = SUM(amount) WHERE DATE(paid_at)=today (index on paid_at).
*/
