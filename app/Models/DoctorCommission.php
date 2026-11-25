<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorCommission extends Model
{
    protected $fillable = ['doctor_id', 'invoice_id', 'base_amount', 'rate', 'amount', 'period'];

    protected $casts = ['base_amount' => 'decimal:2', 'rate' => 'decimal:2', 'amount' => 'decimal:2'];
}

/*
SYSTEM DESIGN:
Commission snapshot: rate is copied at posting time so later rate changes never rewrite history.

DATA STRUCTURE:
Grouped by (doctor_id, period) for the monthly statement.
*/
