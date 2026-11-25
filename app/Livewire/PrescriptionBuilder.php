<?php
namespace App\Livewire;

use App\Models\Appointment;
use App\Services\PrescriptionService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class PrescriptionBuilder extends Component
{
    public ?int $appointmentId = null;
    public array $items = [];
    public string $notes = '';
    public string $language = 'ar';
    public bool $acknowledged = false;
    public ?int $issuedId = null;

    #[On('open-prescription')]
    public function show(int $appointmentId): void
    {
        $a = Appointment::query()->findOrFail($appointmentId);
        Gate::authorize('treat', $a);
        $this->reset();
        $this->appointmentId = $appointmentId;
        $this->items = [$this->blank()];
    }

    private function blank(): array
    {
        return ['medicine_id' => '', 'dose' => '', 'frequency' => '', 'duration_days' => 5, 'instructions' => ''];
    }

    public function addItem(): void
    {
        if (count($this->items) < 15) {
            $this->items[] = $this->blank();
        }
    }

    public function removeItem(int $i): void
    {
        unset($this->items[$i]);
        $this->items = array_values($this->items) ?: [$this->blank()];
    }

    public function close(): void
    {
        $this->reset();
    }

    #[Computed]
    public function appointment(): ?Appointment
    {
        if (! $this->appointmentId) {
            return null;
        }
        $a = Appointment::query()->with(['patient', 'doctor.user'])->findOrFail($this->appointmentId);
        Gate::authorize('treat', $a);

        return $a;
    }

    #[Computed]
    public function catalogue()
    {
        return app(PrescriptionService::class)->catalogue();
    }

    #[Computed]
    public function conflicts()
    {
        if (! $this->appointment) {
            return collect();
        }
        $ids = array_filter(array_map('intval', array_column($this->items, 'medicine_id')));

        return app(PrescriptionService::class)->allergyConflicts($this->appointment->patient, $ids);
    }

    public function save(PrescriptionService $svc): void
    {
        $this->validate([
            'items' => ['required', 'array', 'min:1', 'max:15'],
            'items.*.medicine_id' => ['required', 'integer', 'exists:medicines,id'],
            'items.*.dose' => ['required', 'string', 'max:60'],
            'items.*.frequency' => ['required', 'string', 'max:60'],
            'items.*.duration_days' => ['required', 'integer', 'min:1', 'max:365'],
            'items.*.instructions' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'language' => ['required', 'in:ar,en'],
        ], [], ['items.*.medicine_id' => __('clinic.rx.medicine'), 'items.*.dose' => __('clinic.rx.dose'), 'items.*.frequency' => __('clinic.rx.frequency')]);

        $a = $this->appointment;
        $rx = $svc->issue($a->doctor, $a->patient, $this->items, $a->id, $this->language, $this->notes ?: null, $this->acknowledged);
        $this->issuedId = $rx->id;
        $this->dispatch('toast', message: __('clinic.toast.rx_issued'));
    }

    public function render()
    {
        return view('livewire.prescription-builder');
    }
}

/*
SYSTEM DESIGN:
Digital prescription editor with LIVE preview and drug-allergy banner. Server re-validates every line, then PrescriptionService::issue() blocks on allergy unless the doctor ticks an explicit acknowledgement (audit-logged).

DATA STRUCTURE:
items = ordered list of associative arrays (max 15) bound with wire:model; catalogue keyed by id for O(1) lookup in the preview.
*/
