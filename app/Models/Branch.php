<?php
namespace App\Models;

use App\Models\Concerns\Localized;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use Localized, SoftDeletes;

    protected $fillable = ['name_ar', 'name_en', 'phone', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function clinics(): HasMany
    {
        return $this->hasMany(Clinic::class);
    }
}

/*
SYSTEM DESIGN:
Branch = tenant-like partition. All operational queries are filtered by branch_id (see App\Support\CurrentBranch).

DATA STRUCTURE:
Root of the hierarchy Branch -> Clinic -> Doctor (tree, depth 3).
*/
