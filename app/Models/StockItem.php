<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockItem extends Model
{
    use SoftDeletes;

    protected $fillable = ['branch_id', 'sku', 'name', 'quantity', 'min_quantity', 'unit_cost', 'expiry_date'];

    protected $casts = ['expiry_date' => 'date', 'unit_cost' => 'decimal:2'];

    public function scopeExpiringSoon(Builder $q): Builder
    {
        return $q->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays(config('clinic.expiry_warning_days')));
    }

    public function scopeLow(Builder $q): Builder
    {
        return $q->whereColumn('quantity', '<=', 'min_quantity');
    }
}

/*
SYSTEM DESIGN:
Inventory item with expiry + reorder-level alerts (Rule: stock warning near expiry).

DATA STRUCTURE:
Range query on indexed expiry_date; reorder level via column-to-column comparison.
*/
