<?php
namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    public function log(string $action, ?Model $subject = null): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => $subject ? class_basename($subject) : null,
            'auditable_id' => $subject?->getKey(),
            'ip' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 255),
        ]);
    }
}

/*
SYSTEM DESIGN:
Single place to write the audit trail; called whenever a medical file is opened, a prescription issued or an invoice paid.

DATA STRUCTURE:
Append-only INSERT; O(1).
*/
