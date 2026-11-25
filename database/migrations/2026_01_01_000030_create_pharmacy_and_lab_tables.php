<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicines', function (Blueprint $t) {
            $t->id();
            $t->string('name_ar');
            $t->string('name_en')->index();
            $t->string('generic_name')->nullable()->index();
            $t->string('form', 30)->nullable();
            $t->string('strength', 30)->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('prescriptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('patient_id')->constrained()->restrictOnDelete();
            $t->foreignId('doctor_id')->constrained()->restrictOnDelete();
            $t->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $t->string('language', 2)->default('ar');
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['patient_id', 'created_at']);
        });

        Schema::create('prescription_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('prescription_id')->constrained()->cascadeOnDelete();
            $t->foreignId('medicine_id')->constrained()->restrictOnDelete();
            $t->string('dose', 60);
            $t->string('frequency', 60);
            $t->unsignedSmallInteger('duration_days');
            $t->string('instructions')->nullable();
            $t->timestamps();
        });

        Schema::create('lab_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('patient_id')->constrained()->restrictOnDelete();
            $t->foreignId('doctor_id')->constrained()->restrictOnDelete();
            $t->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $t->string('type', 12)->default('lab');          // lab | radiology
            $t->string('test_name');
            $t->string('status', 12)->default('requested');
            $t->text('result')->nullable();
            $t->string('attachment_path')->nullable();       // private disk
            $t->timestamps();
            $t->softDeletes();
            $t->index(['patient_id', 'status']);
        });

        Schema::create('stock_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $t->string('sku', 40)->unique();
            $t->string('name');
            $t->integer('quantity')->default(0);
            $t->unsignedInteger('min_quantity')->default(5);
            $t->decimal('unit_cost', 10, 2)->default(0);
            $t->date('expiry_date')->nullable()->index();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['branch_id', 'quantity']);
        });
    }

    public function down(): void
    {
        foreach (['stock_items', 'lab_requests', 'prescription_items', 'prescriptions', 'medicines'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

/*
SYSTEM DESIGN:
Pharmacy + diagnostics module. prescriptions (header) / prescription_items (lines) is the classic master-detail pattern. stock_items.expiry_date is indexed for the expiry-alert query.

DATA STRUCTURE:
1-N: prescriptions -> prescription_items. Lookup table medicines (cached). Inventory row per SKU per branch; unique(sku). Integer quantity guarded by DB::transaction + lockForUpdate when deducting.
*/
