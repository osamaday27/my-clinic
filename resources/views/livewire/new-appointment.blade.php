<div>
@if ($open)
    <div class="scrim" wire:click="close"></div>
    <div class="modal" role="dialog" aria-modal="true" aria-label="{{ __('clinic.ui.new_appointment') }}" @keydown.escape.window="$wire.close()">
        <div class="modal__box">
            <div class="card__h"><h2>{{ __('clinic.ui.new_appointment') }}</h2><button class="iconbtn" wire:click="close" aria-label="{{ __('clinic.ui.close') }}"><span class="ms">close</span></button></div>
            <div class="form">
                <div class="field full">
                    <label>{{ __('clinic.ui.patient') }}</label>
                    @if ($this->patient)
                        <div class="row"><span class="badge badge--info badge--plain">{{ $this->patient->name }} · <span class="num">{{ $this->patient->file_number }}</span></span>
                            <button class="btn btn--sm" wire:click="$set('patient_id', null)">{{ __('clinic.ui.back') }}</button></div>
                    @else
                        <input class="input" style="width:100%" wire:model.live.debounce.300ms="patientQuery" placeholder="{{ __('clinic.ui.search_patient') }}" autocomplete="off">
                        @foreach ($this->patientResults as $p)
                            <button type="button" class="btn btn--sm" style="margin:6px 6px 0 0" wire:click="pick({{ $p->id }})">{{ $p->name }} · <span class="num">{{ $p->phone }}</span></button>
                        @endforeach
                    @endif
                    @error('patient_id')<div class="err" role="alert">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="na-doc">{{ __('clinic.ui.doctor') }}</label>
                    <select id="na-doc" class="select" style="width:100%" wire:model.live="doctor_id">
                        <option value="">—</option>
                        @foreach ($this->doctors as $d)<option value="{{ $d->id }}">{{ $d->name }} — {{ $d->specialty }}</option>@endforeach
                    </select>
                    @error('doctor_id')<div class="err" role="alert">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="na-date">{{ __('clinic.attributes.appointment_date') }}</label>
                    <input id="na-date" type="date" class="input" style="width:100%" min="{{ today()->toDateString() }}" wire:model.live="appointment_date">
                    <div class="hint">{{ __('clinic.ui.hint_date') }}</div>
                    @error('appointment_date')<div class="err" role="alert">{{ $message }}</div>@enderror
                </div>

                <div class="field full">
                    <label>{{ __('clinic.ui.pick_slot') }}</label>
                    @if ($this->slots)
                        <div class="slots" role="group">
                            @foreach ($this->slots as $time => $free)
                                <button type="button" class="slot" @disabled(! $free) aria-pressed="{{ $start_time === $time ? 'true' : 'false' }}" wire:click="$set('start_time', '{{ $time }}')">{{ $time }}</button>
                            @endforeach
                        </div>
                        <div class="hint">{{ __('clinic.ui.hint_slot') }}</div>
                    @else
                        <div class="hint">{{ __('clinic.ui.no_slots') }}</div>
                    @endif
                    @error('start_time')<div class="err" role="alert">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="na-type">{{ __('clinic.ui.type') }}</label>
                    <select id="na-type" class="select" style="width:100%" wire:model.live="visit_type">
                        @foreach ($this->visitTypes() as $t)<option value="{{ $t->value }}">{{ $t->label() }}</option>@endforeach
                    </select>
                    @error('visit_type')<div class="err">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="na-c">{{ __('clinic.ui.complaint') }}</label>
                    <input id="na-c" class="input" style="width:100%" maxlength="500" wire:model.live.debounce.400ms="chief_complaint">
                    @error('chief_complaint')<div class="err">{{ $message }}</div>@enderror
                </div>

                <div class="preview full" aria-live="polite">
                    <h3>{{ __('clinic.ui.live_preview') }}</h3>
                    <div><strong>{{ $this->patient?->name ?? '—' }}</strong> → {{ $this->selectedDoctor?->name ?? '—' }}</div>
                    <div class="sub num">{{ $appointment_date }} {{ $start_time ?: '--:--' }} · {{ \App\Enums\VisitType::tryFrom($visit_type)?->label() }}</div>
                    @if ($this->patient && filled($this->patient->allergies))
                        <div class="alert alert--bad" style="margin-top:8px"><span class="ms">warning</span>{{ __('clinic.ui.allergy') }}: {{ $this->patient->allergies }}</div>
                    @endif
                </div>
            </div>
            <div class="row" style="padding:0 20px 20px;justify-content:flex-end">
                <button class="btn" wire:click="close">{{ __('clinic.ui.close') }}</button>
                <button class="btn btn--primary" wire:click="save" wire:loading.attr="disabled">{{ __('clinic.ui.save') }}</button>
            </div>
        </div>
    </div>
@endif
</div>

{{--
SYSTEM DESIGN:
Booking modal with live preview below the fields and per-field helper text (Rules 27-36). Time picker is a slot grid generated from the doctor's calendar; taken slots are disabled but the server re-validates anyway.

DATA STRUCTURE:
Slot hash-map rendered as button grid; form state = public properties synced via wire:model.
--}}
