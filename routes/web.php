<?php

use App\Enums\Role;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LabAttachmentController;
use App\Http\Controllers\PreferenceController;
use App\Http\Controllers\TwoFactorChallengeController;
use App\Http\Controllers\PrescriptionPrintController;
use App\Http\Controllers\PortalDocumentController;
use App\Livewire\Pages;
use App\Livewire\Portal;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/two-factor-challenge', [TwoFactorChallengeController::class, 'show'])->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [TwoFactorChallengeController::class, 'verify'])->middleware('throttle:5,1')->name('two-factor.verify');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/locale', [PreferenceController::class, 'locale'])->name('locale');
    Route::get('/portal/password', Portal\Password::class)->middleware('role:patient')->name('portal.password');
});

// Patient portal: identity is ALWAYS taken from the authenticated user (users.patient_id), never from the URL/request.
Route::middleware(['auth', 'role:patient', 'pwchange'])->prefix('portal')->group(function () {
    Route::get('/', Portal\Home::class)->name('portal');
    Route::get('/book', Portal\Book::class)->name('portal.book');
    Route::get('/prescriptions/{prescription}', [PortalDocumentController::class, 'prescription'])->name('portal.rx');
    Route::get('/labs/{lab}/attachment', [PortalDocumentController::class, 'lab'])->name('portal.lab');
});

Route::middleware(['auth', 'role:'.implode(',', Role::staff()), 'twofactor'])->group(function () {
    Route::post('/branch', [PreferenceController::class, 'branch'])->name('branch');

    Route::get('/security', Pages\SecurityPage::class)->name('security');
    Route::get('/', Pages\DashboardPage::class)->name('dashboard');
    Route::get('/appointments', Pages\AppointmentsPage::class)->name('appointments');
    Route::get('/labs/{lab}/attachment', LabAttachmentController::class)->name('labs.attachment');
    Route::get('/prescriptions/{prescription}/print', PrescriptionPrintController::class)->name('prescriptions.print');

    Route::middleware('role:manager,reception,doctor,nurse')->get('/patients', Pages\PatientsPage::class)->name('patients');
    Route::middleware('role:manager,reception')->get('/doctors', Pages\DoctorsPage::class)->name('doctors');
    Route::middleware('role:manager,reception,accountant')->get('/billing', Pages\BillingPage::class)->name('billing');
    Route::middleware('role:manager,reception,accountant')->get('/insurance', Pages\ClaimsPage::class)->name('insurance');
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
