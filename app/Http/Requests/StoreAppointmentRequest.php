<?php
namespace App\Http\Requests;

use App\Enums\VisitType;
use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Appointment::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer', Rule::exists('patients', 'id')->whereNull('deleted_at')],
            'doctor_id' => ['required', 'integer', Rule::exists('doctors', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'appointment_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'visit_type' => ['required', Rule::enum(VisitType::class)],
            'chief_complaint' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return __('clinic.validation');
    }

    public function attributes(): array
    {
        return __('clinic.attributes');
    }
}

/*
SYSTEM DESIGN:
Form Request (Rule 3): validation + authorization out of the component/controller. Messages are Arabic/English from lang files (Rule 25/26). Reused by the Livewire modal so rules exist in ONE place.

DATA STRUCTURE:
Rule list = associative array field => rules[]. exists() rules are soft-delete aware.
*/
