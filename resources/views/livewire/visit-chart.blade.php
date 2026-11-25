<div>
@if ($appointmentId && ($a = $this->appointment))
    <div class="scrim" wire:click="close"></div>
    <div class="modal" role="dialog" aria-modal="true" @keydown.escape.window="$wire.close()">
        <div class="modal__box">
            <div class="card__h">
                <h2>{{ __('clinic.ui.chart') }} — {{ $a->patient->name }} <span class="sub num">#{{ $a->token_number }}</span></h2>
                <button class="iconbtn" wire:click="close" aria-label="{{ __('clinic.ui.close') }}"><span class="ms">close</span></button>
            </div>

            @if (filled($a->patient->allergies))
                <div class="alert alert--bad" role="alert" style="margin:12px 20px 0"><span class="ms">warning</span><span>{{ __('clinic.ui.allergy') }}: {{ $a->patient->allergies }}</span></div>
            @endif
            @error('action')<div class="alert alert--bad" role="alert" style="margin:12px 20px 0">{{ $message }}</div>@enderror

            <div class="tabs" role="tablist" style="margin-top:8px">
                <button role="tab" aria-selected="{{ $tab === 'vitals' ? 'true' : 'false' }}" wire:click="setTab('vitals')">{{ __('clinic.ui.tab_vitals') }}</button>
                @can('treat', $a)
                    <button role="tab" aria-selected="{{ $tab === 'diagnosis' ? 'true' : 'false' }}" wire:click="setTab('diagnosis')">{{ __('clinic.ui.tab_diagnosis') }}</button>
                    <button role="tab" aria-selected="{{ $tab === 'lab' ? 'true' : 'false' }}" wire:click="setTab('lab')">{{ __('clinic.ui.tab_lab') }}</button>
                @endcan
            </div>

            @if ($tab === 'vitals')
                <div class="form">
                    @foreach ([['bp_systolic', 'systolic', '50-260'], ['bp_diastolic', 'diastolic', '30-160'], ['heart_rate', 'bpm', '20-250'], ['temperature', 'temp_c', '30-45'], ['spo2', null, '50-100'], ['weight', 'weight_kg', '1-400']] as [$f, $l, $range])
                        <div class="field">
                            <label for="v-{{ $f }}">{{ $l ? __('clinic.ui.'.$l) : 'SpO₂ %' }}</label>
                            <input id="v-{{ $f }}" class="input num" style="width:100%" inputmode="decimal" wire:model="v.{{ $f }}">
                            <div class="hint num">{{ $range }}</div>
                            @error("v.$f")<div class="err" role="alert">{{ $message }}</div>@enderror
                        </div>
                    @endforeach
                    @error('v')<div class="err full" role="alert">{{ $message }}</div>@enderror
                    <div class="full row" style="justify-content:flex-end"><button class="btn btn--primary" wire:click="saveVitals" wire:loading.attr="disabled">{{ __('clinic.ui.save') }}</button></div>
                    <div class="full">
                        <strong>{{ __('clinic.ui.recent') }}</strong>
                        @forelse ($this->recentVitals as $r)
                            <div class="sub num" style="margin-top:4px">{{ $r->created_at->format('Y-m-d H:i') }} · {{ $r->blood_pressure ?? '—' }} · {{ $r->heart_rate ?? '—' }} · {{ $r->temperature ?? '—' }}° · {{ $r->spo2 ?? '—' }}% · {{ $r->weight ?? '—' }}</div>
                        @empty<div class="sub">{{ __('clinic.ui.no_vitals') }}</div>@endforelse
                    </div>
                </div>

            @elseif ($tab === 'diagnosis')
                <div class="form">
                    <div class="field full"><label for="dg">{{ __('clinic.ui.diagnosis') }}</label>
                        <textarea id="dg" class="input" rows="2" maxlength="1000" wire:model="diagnosis"></textarea>
                        @error('diagnosis')<div class="err" role="alert">{{ $message }}</div>@enderror</div>
                    <div class="field"><label for="icd">{{ __('clinic.ui.icd') }}</label>
                        <input id="icd" class="input num" style="width:100%" maxlength="12" placeholder="J06.9" wire:model="icd_code">
                        <div class="hint">J06.9 / E11 / I10</div>
                        @error('icd_code')<div class="err" role="alert">{{ $message }}</div>@enderror</div>
                    <div class="field full"><label for="nt">{{ __('clinic.ui.clin_notes') }}</label>
                        <textarea id="nt" class="input" rows="3" maxlength="2000" wire:model="notes"></textarea>
                        @error('notes')<div class="err">{{ $message }}</div>@enderror</div>
                    <div class="full row" style="justify-content:flex-end"><button class="btn btn--primary" wire:click="saveDiagnosis" wire:loading.attr="disabled">{{ __('clinic.ui.save') }}</button></div>
                    <div class="full"><strong>{{ __('clinic.ui.saved_records') }}</strong>
                        @forelse ($this->records as $r)
                            <div class="card card__b" style="margin-top:6px"><strong>{{ $r->diagnosis }}</strong> <span class="sub num">{{ $r->icd_code }}</span>@if ($r->notes)<div class="sub">{{ $r->notes }}</div>@endif</div>
                        @empty<div class="sub">{{ __('clinic.ui.empty') }}</div>@endforelse</div>
                </div>

            @else
                <div class="form">
                    <div class="field"><label for="lt">{{ __('clinic.ui.type') }}</label>
                        <select id="lt" class="select" style="width:100%" wire:model="lab_type"><option value="lab">{{ __('clinic.ui.lab') }}</option><option value="radiology">{{ __('clinic.ui.radiology') }}</option></select></div>
                    <div class="field"><label for="tn">{{ __('clinic.ui.test_name') }}</label>
                        <input id="tn" class="input" style="width:100%" maxlength="150" wire:model="test_name" placeholder="CBC / X-Ray Chest">
                        @error('test_name')<div class="err" role="alert">{{ $message }}</div>@enderror</div>
                    <div class="full row" style="justify-content:flex-end"><button class="btn btn--primary" wire:click="requestLab" wire:loading.attr="disabled">{{ __('clinic.ui.request_lab') }}</button></div>
                    <div class="full"><strong>{{ __('clinic.ui.visit_labs') }}</strong>
                        @forelse ($this->labs as $l)
                            <div class="row" style="justify-content:space-between;margin-top:6px"><span>{{ $l->test_name }} <span class="sub">({{ __('clinic.ui.'.$l->type) }})</span></span>
                                <span class="badge badge--{{ $l->status === 'resulted' ? 'ok' : 'warn' }}">{{ __('clinic.ui.'.$l->status) }}</span></div>
                        @empty<div class="sub">{{ __('clinic.ui.empty') }}</div>@endforelse</div>
                </div>
            @endif
        </div>
    </div>
@endif
</div>

{{--
SYSTEM DESIGN:
Tabbed modal: Vitals / Diagnosis / Lab-Radiology with helper text under every field (ranges, ICD examples) and inline errors (aria role=alert). Free-text clinical fields are escaped by Blade.

DATA STRUCTURE:
Form arrays rendered from a tuple list [field, i18n key, range hint] (data-driven UI, no repeated markup).
--}}
