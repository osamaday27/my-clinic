<?php

use App\Enums\Role;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LabAttachmentController;
use App\Http\Controllers\PreferenceController;
use App\Http\Controllers\PrescriptionPrintController;
use App\Livewire\Pages;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
});

Route::middleware(['auth', 'role:'.implode(',', Role::staff())])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/locale', [PreferenceController::class, 'locale'])->name('locale');
    Route::post('/branch', [PreferenceController::class, 'branch'])->name('branch');

    Route::get('/', Pages\DashboardPage::class)->name('dashboard');
    Route::get('/appointments', Pages\AppointmentsPage::class)->name('appointments');
    Route::get('/labs/{lab}/attachment', LabAttachmentController::class)->name('labs.attachment');
    Route::get('/prescriptions/{prescription}/print', PrescriptionPrintController::class)->name('prescriptions.print');

    Route::middleware('role:manager,reception,doctor,nurse')->get('/patients', Pages\PatientsPage::class)->name('patients');
    Route::middleware('role:manager,reception')->get('/doctors', Pages\DoctorsPage::class)->name('doctors');
    Route::middleware('role:manager,reception,accountant')->get('/billing', Pages\BillingPage::class)->name('billing');
    Route::middleware('role:manager,pharmacist,nurse')->get('/inventory', Pages\InventoryPage::class)->name('inventory');
    Route::middleware('role:manager,doctor,nurse')->get('/lab', Pages\LabPage::class)->name('lab');
    Route::middleware('role:manager')->get('/settings', Pages\SettingsPage::class)->name('settings');
});

/*
SYSTEM DESIGN:
Route map with layered middleware: guest/auth -> coarse RBAC (role:) -> Livewire component authorize() -> Policy. Login throttled to 5 attempts/min.

DATA STRUCTURE:
Route table (trie/hash lookup by Laravel router).
*/
