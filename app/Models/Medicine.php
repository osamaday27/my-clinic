<?php
namespace App\Models;

use App\Models\Concerns\Localized;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Medicine extends Model
{
    use Localized, SoftDeletes;

    protected $fillable = ['name_ar', 'name_en', 'generic_name', 'form', 'strength', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}

/*
SYSTEM DESIGN:
Drug catalogue (lookup table). Cached for 1 hour by PrescriptionService::catalogue() because it is read-heavy and rarely written (Rule 19).

DATA STRUCTURE:
Lookup table cached as a Collection keyed by id (associative array -> O(1) access).
*/
