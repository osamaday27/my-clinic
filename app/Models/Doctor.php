<?php
namespace App\Models;

use App\Models\Concerns\Localized;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Doctor extends Model
{
    use Localized, SoftDeletes;

    protected $fillable = ['user_id', 'clinic_id', 'specialty_ar', 'specialty_en', 'slot_minutes', 'commission_rate', 'working_hours', 'is_active'];

    protected $casts = [
        'working_hours' => 'array',
        'commission_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function getNameAttribute(): ?string
    {
        return $this->user?->name;
    }

    public function getSpecialtyAttribute(): ?string
    {
        return $this->localized('specialty');
    }
}

/*
SYSTEM DESIGN:
Doctor profile (1:1 with users). working_hours JSON drives the slot generator; commission_rate drives monthly settlement.

DATA STRUCTURE:
JSON document {start,end} -> array cast; falls back to config('clinic.working_hours').
*/
