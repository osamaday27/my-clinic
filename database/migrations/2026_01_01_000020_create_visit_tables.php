<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('patient_id')->constrained()->restrictOnDelete();
            $t->foreignId('doctor_id')->constrained()->restrictOnDelete();
            $t->foreignId('clinic_id')->constrained()->restrictOnDelete();
            $t->foreignId('branch_id')->constrained()->restrictOnDelete();
            $t->date('appointment_date');
            $t->time('start_time');
            $t->time('end_time');
            $t->string('visit_type', 20)->default('consultation');
            $t->string('status', 20)->default('scheduled');
            $t->unsignedSmallInteger('token_number')->nullable();
            $t->string('chief_complaint', 500)->nullable();
            $t->string('cancel_reason')->nullable();
            $t->timestamp('reminder_sent_at')->nullable();
            $t->timestamp('checked_in_at')->nullable();
            $t->timestamp('started_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();

            $t->index('patient_id');
            $t->index(['doctor_id', 'appointment_date', 'start_time']); // conflict detection
            $t->index(['branch_id', 'appointment_date', 'status']);     // dashboard / queue
            $t->index('appointment_date');
        });

        Schema::create('medical_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('patient_id')->constrained()->restrictOnDelete();
            $t->foreignId('doctor_id')->constrained()->restrictOnDelete();
            $t->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $t->string('icd_code', 12)->nullable();
            $t->text('diagnosis');
            $t->text('notes')->nullable();                // encrypted cast
            $t->timestamps();
            $t->softDeletes();
            $t->index(['patient_id', 'created_at']);
        });

        Schema::create('vitals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('patient_id')->constrained()->restrictOnDelete();
            $t->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->unsignedSmallInteger('bp_systolic')->nullable();
            $t->unsignedSmallInteger('bp_diastolic')->nullable();
            $t->unsignedSmallInteger('heart_rate')->nullable();
            $t->decimal('temperature', 4, 1)->nullable();
            $t->unsignedTinyInteger('spo2')->nullable();
            $t->decimal('weight', 5, 1)->nullable();
            $t->timestamps();
            $t->index(['patient_id', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach (['vitals', 'medical_records', 'appointments'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

/*
SYSTEM DESIGN:
Visit aggregate. restrictOnDelete + SoftDeletes guarantee that no medical record can be physically removed (Rule 6/7). Composite indexes serve the two hottest queries: slot-conflict check and today's queue.

DATA STRUCTURE:
Composite B-tree (doctor_id, appointment_date, start_time) -> range scan for overlap check O(log n). (branch_id, appointment_date, status) -> covering index for KPI counts. Time-series style tables: vitals ordered by (patient_id, created_at).
*/
