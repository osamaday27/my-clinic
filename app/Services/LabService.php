<?php
namespace App\Services;

use App\Models\LabRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class LabService
{
    public function __construct(private AuditService $audit) {}

    public function disk()
    {
        return Storage::disk(config('clinic.lab.disk'));      // PRIVATE disk: not reachable by URL
    }

    public function recordResult(LabRequest $lab, ?string $result, ?UploadedFile $file, User $by): LabRequest
    {
        if (blank($result) && ! $file) {
            throw ValidationException::withMessages(['result' => __('clinic.errors.result_empty')]);
        }

        return DB::transaction(function () use ($lab, $result, $file, $by) {
            $path = $lab->attachment_path;
            if ($file) {
                // Random server-side name (hashName) + per-patient folder: user-supplied names are never used on disk.
                $new = $file->store('lab/'.$lab->patient_id, config('clinic.lab.disk'));
                if ($path) {
                    $this->disk()->delete($path);
                }
                $path = $new;
            }
            $lab->update(['result' => $result ?: $lab->result, 'attachment_path' => $path, 'status' => 'resulted']);
            $this->audit->log('lab.resulted', $lab);

            return $lab;
        });
    }
}

/*
SYSTEM DESIGN:
Lab result workflow. Attachments live on a private disk, are stored under random names, and are only served by LabAttachmentController after a policy check (never via public/ or storage:link).

DATA STRUCTURE:
Object keyed by path 'lab/{patient_id}/{hash}.ext' (folder-per-patient). Old file replaced inside the same transaction.
*/
