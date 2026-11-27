<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsuranceClaimEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['claim_id', 'status', 'note', 'user_id'];
}

/*
SYSTEM DESIGN:
Status history row (never updated).

DATA STRUCTURE:
Append-only log.
*/
