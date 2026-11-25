<?php
namespace App\Models;

use App\Models\Concerns\Localized;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Clinic extends Model
{
    use Localized, SoftDeletes;

    protected $fillable = ['branch_id', 'name_ar', 'name_en', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function doctors(): HasMany
    {
        return $this->hasMany(Doctor::class);
    }
}

/*
SYSTEM DESIGN:
Clinic = department / specialty unit inside a branch (Dermatology, Pediatrics...).

DATA STRUCTURE:
Tree node: parent Branch, children Doctors.
*/
