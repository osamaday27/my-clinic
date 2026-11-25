<div>
@if ($appointmentId && ($appt = $this->appointment))
    <div class="scrim" wire:click="close"></div>
    <div class="modal" role="dialog" aria-modal="true" @keydown.escape.window="$wire.close()">
        <div class="modal__box">
            <div class="card__h"><h2>{{ __('clinic.ui.prescription') }} — {{ $appt->patient->name }}</h2><button class="iconbtn" wire:click="close" aria-label="{{ __('clinic.ui.close') }}"><span class="ms">close</span></button></div>

            @if ($issuedId)
                <div class="stack" style="padding:20px">
                    <div class="alert alert--warn" style="background:var(--ok-bg);color:var(--ok);border-color:#A7F3D0"><span class="ms">check_circle</span>{{ __('clinic.toast.rx_issued') }}</div>
                    <a class="btn btn--primary" target="_blank" href="{{ route('prescriptions.print', $issuedId) }}"><span class="ms">print</span>{{ __('clinic.ui.print') }}</a>
                </div>
            @else
            <div class="stack" style="padding:20px">
                @if ($this->conflicts->isNotEmpty())
                    <div class="alert alert--bad" role="alert"><span class="ms">warning</span><span>{{ __('clinic.rx.alert', ['names' => $this->conflicts->pluck('name')->join(', ')]) }}</span></div>
                    <label class="row"><input type="checkbox" wire:model.live="acknowledged"> {{ __('clinic.ui.ack') }}</label>
                @endif
                @error('items')<div class="alert alert--bad" role="alert">{{ $message }}</div>@enderror

                @foreach ($items as $i => $row)
                    <div class="row" wire:key="rx-{{ $i }}" style="align-items:flex-start">
                        <div class="field grow" style="min-width:180px"><label>{{ __('clinic.rx.medicine') }}</label>
                            <select class="select" style="width:100%" wire:model.live="items.{{ $i }}.medicine_id"><option value="">—</option>
                                @foreach ($this->catalogue as $m)<option value="{{ $m->id }}">{{ $m->name }} {{ $m->strength }}</option>@endforeach</select>
                            @error("items.$i.medicine_id")<div class="err">{{ $message }}</div>@enderror</div>
                        <div class="field" style="width:110px"><label>{{ __('clinic.rx.dose') }}</label><input class="input" style="width:100%" wire:model.live.debounce.300ms="items.{{ $i }}.dose">@error("items.$i.dose")<div class="err">{{ $message }}</div>@enderror</div>
                        <div class="field" style="width:130px"><label>{{ __('clinic.rx.frequency') }}</label><input class="input" style="width:100%" wire:model.live.debounce.300ms="items.{{ $i }}.frequency">@error("items.$i.frequency")<div class="err">{{ $message }}</div>@enderror</div>
                        <div class="field" style="width:80px"><label>{{ __('clinic.rx.days') }}</label><input type="number" min="1" class="input num" style="width:100%" wire:model.live="items.{{ $i }}.duration_days"></div>
                        <button class="iconbtn" style="margin-top:22px" wire:click="removeItem({{ $i }})" aria-label="remove"><span class="ms">delete</span></button>
                    </div>
                @endforeach
                <div><button class="btn btn--sm" wire:click="addItem"><span class="ms">add</span>{{ __('clinic.ui.add_line') }}</button></div>

                <div class="field"><label>{{ __('clinic.ui.notes') }}</label><textarea class="input" rows="2" maxlength="1000" wire:model.live.debounce.400ms="notes"></textarea></div>
                <div class="field"><label>Language / اللغة</label><select class="select" wire:model.live="language"><option value="ar">العربية</option><option value="en">English</option></select></div>

                <div class="preview" aria-live="polite">
                    <h3>{{ __('clinic.ui.live_preview') }} — {{ __('clinic.rx.title') }}</h3>
                    <ol style="margin:0;padding-inline-start:18px">
                        @foreach ($items as $row)
                            @if ($m = $this->catalogue->get((int) $row['medicine_id']))
                                <li>{{ $m->name }} {{ $m->strength }} — {{ $row['dose'] }} / {{ $row['frequency'] }} / <span class="num">{{ $row['duration_days'] }}</span></li>
                            @endif
                        @endforeach
                    </ol>
                </div>
                <div class="row" style="justify-content:flex-end">
                    <button class="btn" wire:click="close">{{ __('clinic.ui.close') }}</button>
                    <button class="btn btn--primary" wire:click="save" wire:loading.attr="disabled">{{ __('clinic.ui.issue') }}</button>
                </div>
            </div>
            @endif
        </div>
    </div>
@endif
</div>

{{--
SYSTEM DESIGN:
Digital prescription editor: medicine lookup from the cached catalogue, dose/frequency/duration lines, live preview, allergy banner with explicit acknowledgement (Rule 33).

DATA STRUCTURE:
Lines = ordered list (max 15); preview resolves medicine via O(1) keyed lookup.
--}}
