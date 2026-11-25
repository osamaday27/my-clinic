<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->string('number', 30)->unique();
            $t->foreignId('patient_id')->constrained()->restrictOnDelete();
            $t->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('branch_id')->constrained()->restrictOnDelete();
            $t->decimal('subtotal', 12, 2)->default(0);
            $t->decimal('discount', 12, 2)->default(0);
            $t->decimal('insurance_covered', 12, 2)->default(0);
            $t->decimal('total', 12, 2)->default(0);
            $t->decimal('paid', 12, 2)->default(0);
            $t->string('status', 10)->default('unpaid');   // unpaid | partial | paid
            $t->timestamps();
            $t->softDeletes();
            $t->index(['branch_id', 'status']);
            $t->index(['patient_id', 'created_at']);
        });

        Schema::create('invoice_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $t->foreignId('doctor_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('stock_item_id')->nullable()->constrained()->nullOnDelete();
            $t->string('description');
            $t->unsignedInteger('quantity')->default(1);
            $t->decimal('unit_price', 12, 2);
            $t->decimal('line_total', 12, 2);
            $t->timestamps();
        });

        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $t->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $t->decimal('amount', 12, 2);
            $t->string('method', 12)->default('cash');
            $t->timestamp('paid_at')->useCurrent()->index();
            $t->timestamps();
        });

        Schema::create('doctor_commissions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('doctor_id')->constrained()->restrictOnDelete();
            $t->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $t->decimal('base_amount', 12, 2);
            $t->decimal('rate', 5, 2);
            $t->decimal('amount', 12, 2);
            $t->char('period', 7)->index();                // YYYY-MM
            $t->timestamps();
            $t->unique(['doctor_id', 'invoice_id']);
        });

        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action', 40);
            $t->string('auditable_type', 60)->nullable();
            $t->unsignedBigInteger('auditable_id')->nullable();
            $t->string('ip', 45)->nullable();
            $t->string('user_agent', 255)->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['auditable_type', 'auditable_id']);
            $t->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach (['audit_logs', 'doctor_commissions', 'payments', 'invoice_items', 'invoices'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

/*
SYSTEM DESIGN:
Billing ledger: invoice header / lines / payments (partial payments supported) + commission snapshot per invoice + append-only audit_logs (HIPAA-style access trail).

DATA STRUCTURE:
unique(doctor_id, invoice_id) makes commission posting idempotent. audit_logs is an append-only log (no updated_at, no soft delete). Money = DECIMAL(12,2), never float columns. period CHAR(7) 'YYYY-MM' indexed for monthly GROUP BY.
*/
