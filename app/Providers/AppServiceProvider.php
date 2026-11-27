<?php
namespace App\Providers;

use App\Contracts\SmsGateway;
use App\Models\Appointment;
use App\Models\InsuranceClaim;
use App\Models\Invoice;
use App\Models\LabRequest;
use App\Models\Patient;
use App\Policies\AppointmentPolicy;
use App\Policies\ClaimPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\LabRequestPolicy;
use App\Policies\PatientPolicy;
use App\Services\Sms\LogSmsGateway;
use App\Services\Sms\WhatsAppCloudGateway;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SmsGateway::class, fn ($app) => config('clinic.reminders.channel') === 'whatsapp'
            ? $app->make(WhatsAppCloudGateway::class)
            : $app->make(LogSmsGateway::class));
    }

    public function boot(): void
    {
        Gate::policy(Patient::class, PatientPolicy::class);
        Gate::policy(Appointment::class, AppointmentPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(InsuranceClaim::class, ClaimPolicy::class);
        Gate::policy(LabRequest::class, LabRequestPolicy::class);

        // Detect N+1 in dev/test; in production never crash - just log.
        Model::preventLazyLoading(! app()->isProduction());
        Model::handleLazyLoadingViolationUsing(fn ($model, $rel) => Log::warning('N+1', [get_class($model), $rel]));

        Password::defaults(fn () => Password::min(10)->letters()->mixedCase()->numbers());
    }
}

/*
SYSTEM DESIGN:
Composition root: binds the SMS port, registers policies, enables N+1 detection and a strong password policy.

DATA STRUCTURE:
IoC container map: interface -> implementation.
*/
