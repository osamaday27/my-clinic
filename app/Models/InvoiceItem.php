<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = ['invoice_id', 'doctor_id', 'stock_item_id', 'description', 'quantity', 'unit_price', 'line_total'];
}

/*
SYSTEM DESIGN:
Invoice line; doctor_id marks which lines count for commission.

DATA STRUCTURE:
Detail row.
*/
