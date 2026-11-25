<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $t) {
            $t->id();
            $t->string('name_ar');
            $t->string('name_en');
            $t->string('phone', 20)->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('clinics', function (Blueprint $t) {
            $t->id();
            $t->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $t->string('name_ar');
            $t->string('name_en');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->softDeletes();
            $t->index(['branch_id', 'is_active']);
        });

        Schema::create('doctors', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $t->string('specialty_ar');
            $t->string('specialty_en');
            $t->unsignedSmallInteger('slot_minutes')->default(20);
            $t->decimal('commission_rate', 5, 2)->default(30);
            $t->json('working_hours')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->softDeletes();
            $t->index(['clinic_id', 'is_active']);
        });

        Schema::create('patients', function (Blueprint $t) {
            $t->id();
            $t->string('file_number', 20)->unique();
            $t->string('name_ar');
            $t->string('name_en')->nullable();
            $t->string('phone', 20)->index();
            $t->text('national_id')->nullable();          // encrypted cast
            $t->date('birth_date')->nullable();
            $t->string('gender', 10)->nullable();
            $t->text('allergies')->nullable();            // encrypted cast
            $t->text('chronic_conditions')->nullable();   // encrypted cast
            $t->string('insurance_company')->nullable();
            $t->string('insurance_number')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index('name_ar');
        });
    }

    public function down(): void
    {
        foreach (['patients', 'doctors', 'clinics', 'branches'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

/*
SYSTEM DESIGN:
Normalised (3NF) core entities. Patient identity is separated from visits/diagnoses (Rule 6). Sensitive columns are TEXT because encrypted ciphertext is longer than plaintext.

DATA STRUCTURE:
Tables: branches 1-N clinics 1-N doctors; users 1-1 doctors; patients standalone. Unique B-tree on patients.file_number, index on phone and name_ar (search paths). working_hours = JSON document.
*/
