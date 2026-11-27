<?php
namespace App\Models;

use App\Models\Concerns\Localized;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InsuranceCompany extends Model
{
    use Localized, SoftDeletes;

    protected $fillable = ['name_ar', 'name_en', 'coverage_percent', 'is_active'];

    protected $casts = ['coverage_percent' => 'decimal:2', 'is_active' => 'boolean'];
}

/*
SYSTEM DESIGN:
Insurer master data with default coverage percentage.

DATA STRUCTURE:
Lookup table.
*/
