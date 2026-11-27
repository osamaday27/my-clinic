<?php
namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = ['name', 'email', 'password', 'role', 'branch_id', 'phone', 'is_active'];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function doctor(): HasOne
    {
        return $this->hasOne(Doctor::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function homeUrl(): string
    {
        return $this->role === Role::Patient ? route('portal') : '/';
    }

    public function hasTwoFactor(): bool
    {
        return $this->two_factor_confirmed_at !== null && filled($this->two_factor_secret);
    }

    public function hasRole(Role ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    protected ?int $doctorIdCache = null;
    protected bool $doctorIdResolved = false;

    /** Memoised doctor id (1 query per request instead of one per policy check). */
    public function doctorId(): ?int
    {
        if (! $this->doctorIdResolved) {
            $id = Doctor::query()->where('user_id', $this->id)->value('id');
            $this->doctorIdCache = $id !== null ? (int) $id : null;
            $this->doctorIdResolved = true;
        }

        return $this->doctorIdCache;
    }

    public function initials(): string
    {
        return mb_substr($this->name, 0, 1);
    }
}

/*
SYSTEM DESIGN:
Authenticatable user + RBAC. role and password are mass-assignable ONLY through server-side code paths (never exposed to request()->all()). password uses the 'hashed' cast (bcrypt/argon).

DATA STRUCTURE:
Enum-cast role; HasOne doctor profile. in_array strict (O(k), k<=7).
*/
