<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionItem extends Model
{
    protected $fillable = ['prescription_id', 'medicine_id', 'dose', 'frequency', 'duration_days', 'instructions'];

    public function medicine(): BelongsTo { return $this->belongsTo(Medicine::class); }
}

/*
SYSTEM DESIGN:
Prescription line (detail).

DATA STRUCTURE:
Detail row; FK restrict on medicine, cascade on header.
*/
