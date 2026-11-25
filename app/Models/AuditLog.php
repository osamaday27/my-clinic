<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'action', 'auditable_type', 'auditable_id', 'ip', 'user_agent'];
}

/*
SYSTEM DESIGN:
Append-only access trail for every chart view / sensitive action (Rule 39).

DATA STRUCTURE:
Append-only log table, indexed by (auditable_type, auditable_id) and (user_id, created_at).
*/
