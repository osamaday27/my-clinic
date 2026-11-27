<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_companies', function (Blueprint $t) {
            $t->id();
            $t->string('name_ar');
            $t->string('name_en');
            $t->decimal('coverage_percent', 5, 2)->default(50);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('insurance_claims', function (Blueprint $t) {
            $t->id();
            $t->string('claim_number', 30)->unique();
            $t->foreignId('invoice_id')->unique()->constrained()->restrictOnDelete();      // 1 claim per invoice
            $t->foreignId('patient_id')->constrained()->restrictOnDelete();
            $t->foreignId('insurance_company_id')->constrained()->restrictOnDelete();
            $t->decimal('amount_claimed', 12, 2);
            $t->decimal('amount_approved', 12, 2)->nullable();
            $t->decimal('amount_paid', 12, 2)->default(0);
            $t->decimal('patient_shift', 12, 2)->default(0);     // part pushed back to the patient after a decision
            $t->string('status', 20)->default('draft')->index();
            $t->string('rejection_reason')->nullable();
            $t->timestamp('submitted_at')->nullable();
            $t->timestamp('decided_at')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['insurance_company_id', 'status']);
        });

        Schema::create('insurance_claim_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('claim_id')->constrained('insurance_claims')->cascadeOnDelete();
            $t->string('status', 20);
            $t->string('note')->nullable();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamp('created_at')->useCurrent();
            $t->index('claim_id');
        });

        Schema::table('patients', function (Blueprint $t) {
            $t->unsignedBigInteger('insurance_company_id')->nullable()->index()->after('insurance_company');
        });
    }

    public function down(): void
    {
        Schema::table('patients', fn (Blueprint $t) => $t->dropColumn('insurance_company_id'));
        Schema::dropIfExists('insurance_claim_events');
        Schema::dropIfExists('insurance_claims');
        Schema::dropIfExists('insurance_companies');
    }
};

/*
SYSTEM DESIGN:
Insurance receivables module. unique(invoice_id) = one claim per invoice. insurance_claim_events is an append-only status history (audit-grade trail). patients.insurance_company_id is a plain indexed column (no FK) because SQLite cannot add FKs with ALTER; integrity is enforced by validation (exists rule).

DATA STRUCTURE:
State machine column (status) + append-only event log. patient_shift stores the exact amount pushed to the patient so a later appeal can reverse it precisely (reversible ledger entry).
*/
