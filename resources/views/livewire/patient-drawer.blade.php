<div @keydown.escape.window="$wire.close()">
@if ($patientId && ($p = $this->patient))
    <div class="scrim" wire:click="close"></div>
    <aside class="drawer" role="dialog" aria-modal="true" aria-label="{{ $p->name }}">
        <div class="drawer__h">
            <div>
                <h2 style="margin:0">{{ $p->name }}</h2>
                <div class="sub num">{{ $p->file_number }} · {{ $p->age ?? '—' }} {{ __('clinic.ui.years') }} · {{ $p->phone }}</div>
            </div>
            <div class="row">
                @can('create', \App\Models\Appointment::class)
                    <button class="btn btn--sm btn--primary" wire:click="$dispatch('open-new-appointment', { patientId: {{ $p->id }} })"><span class="ms">add</span></button>
                @endcan
                <button class="iconbtn" wire:click="close" aria-label="{{ __('clinic.ui.close') }}"><span class="ms">close</span></button>
            </div>
        </div>

        @if (filled($p->allergies) || filled($p->chronic_conditions))
            <div class="stack" style="padding:12px 20px 0">
                @if (filled($p->allergies))<div class="alert alert--bad" role="alert"><span class="ms">warning</span><span>{{ __('clinic.ui.allergy') }}: {{ $p->allergies }}</span></div>@endif
                @if (filled($p->chronic_conditions))<div class="alert alert--warn"><span class="ms">monitor_heart</span><span>{{ __('clinic.ui.chronic') }}: {{ $p->chronic_conditions }}</span></div>@endif
            </div>
        @endif

        <div class="tabs" role="tablist">
            @foreach (['overview' => 'overview', 'history' => 'history', 'appointments' => 'appointments', 'invoices' => 'invoices'] as $k => $l)
                <button role="tab" aria-selected="{{ $tab === $k ? 'true' : 'false' }}" wire:click="setTab('{{ $k }}')">{{ $k === 'appointments' ? __('clinic.nav.appointments') : __('clinic.ui.'.$l) }}</button>
            @endforeach
        </div>

        <div class="drawer__b">
        @if ($tab === 'overview')
            <h3 style="margin:0">{{ __('clinic.ui.vitals') }}</h3>
            @php $v = $p->vitals->first(); @endphp
            @if ($v)
                <div class="kpis" style="grid-template-columns:repeat(2,1fr)">
                    <div class="card kpi"><span class="sub">{{ __('clinic.ui.bp') }}</span><span class="kpi__v" style="font-size:22px">{{ $v->blood_pressure ?? '—' }}</span></div>
                    <div class="card kpi"><span class="sub">{{ __('clinic.ui.hr') }}</span><span class="kpi__v" style="font-size:22px">{{ $v->heart_rate ?? '—' }}</span></div>
                    <div class="card kpi"><span class="sub">{{ __('clinic.ui.temp') }}</span><span class="kpi__v" style="font-size:22px">{{ $v->temperature ?? '—' }}°</span></div>
                    <div class="card kpi"><span class="sub">SpO₂ / {{ __('clinic.ui.weight') }}</span><span class="kpi__v" style="font-size:22px">{{ $v->spo2 ?? '—' }}% · {{ $v->weight ?? '—' }}</span></div>
                </div>
            @else<div class="sub">{{ __('clinic.ui.no_vitals') }}</div>@endif
            <dl class="kv">
                <dt>{{ __('clinic.ui.insurance') }}</dt><dd>{{ $p->insurance_company ?: '—' }} <span class="num">{{ $p->insurance_number }}</span></dd>
            </dl>
        @elseif ($tab === 'history')
            @forelse ($p->records as $r)
                <div class="card card__b"><strong>{{ $r->diagnosis }}</strong> <span class="sub num">{{ $r->icd_code }}</span>
                    <div class="sub">{{ $r->doctor->name }} · <span class="num">{{ $r->created_at->format('Y-m-d') }}</span></div>@if ($r->notes)<p style="margin:6px 0 0">{{ $r->notes }}</p>@endif</div>
            @empty<div class="sub">{{ __('clinic.ui.empty') }}</div>@endforelse
            <h3 style="margin:8px 0 0">{{ __('clinic.ui.prescription') }}</h3>
            @forelse ($p->prescriptions as $rx)
                <div class="card card__b">
                    <div class="row" style="justify-content:space-between"><span class="sub num">{{ $rx->created_at->format('Y-m-d') }} · {{ $rx->doctor->name }}</span>
                        <a class="btn btn--sm" target="_blank" href="{{ route('prescriptions.print', $rx) }}"><span class="ms">print</span>{{ __('clinic.ui.print') }}</a></div>
                    <ul style="margin:6px 0 0;padding-inline-start:18px">@foreach ($rx->items as $i)<li>{{ $i->medicine->name }} — {{ $i->dose }} / {{ $i->frequency }} / <span class="num">{{ $i->duration_days }}d</span></li>@endforeach</ul>
                </div>
            @empty<div class="sub">{{ __('clinic.ui.empty') }}</div>@endforelse
            <h3 style="margin:8px 0 0">{{ __('clinic.ui.tab_lab') }}</h3>
            @forelse ($p->labRequests as $lr)
                <div class="card card__b row" style="justify-content:space-between"><span><strong>{{ $lr->test_name }}</strong> <span class="sub">{{ __('clinic.ui.'.$lr->type) }} · <span class="num">{{ $lr->created_at->format('Y-m-d') }}</span></span>
                    @if ($lr->result)<br><span class="sub">{{ \Illuminate\Support\Str::limit($lr->result, 120) }}</span>@endif</span>
                    <span class="row"><span class="badge badge--{{ $lr->status === 'resulted' ? 'ok' : 'warn' }}">{{ __('clinic.ui.'.$lr->status) }}</span>
                    @if ($lr->attachment_path)<a class="btn btn--sm" href="{{ route('labs.attachment', $lr) }}"><span class="ms">download</span></a>@endif</span></div>
            @empty<div class="sub">{{ __('clinic.ui.empty') }}</div>@endforelse
        @elseif ($tab === 'appointments')
            @forelse ($p->appointments as $a)
                <div class="card card__b row" style="justify-content:space-between"><span><strong class="num">{{ $a->appointment_date->format('Y-m-d') }} {{ $a->time_label }}</strong><br><span class="sub">{{ $a->doctor->name }} · {{ $a->visit_type->label() }}</span></span>
                    <span class="badge badge--{{ $a->status->tone() }}">{{ $a->status->label() }}</span></div>
            @empty<div class="sub">{{ __('clinic.ui.empty') }}</div>@endforelse
        @else
            @forelse ($p->invoices as $inv)
                <div class="card card__b row" style="justify-content:space-between"><span><strong class="num">{{ $inv->number }}</strong><br><span class="sub num">{{ $inv->total }} / {{ $inv->paid }}</span></span>
                    <span class="badge badge--{{ ['paid' => 'ok', 'partial' => 'warn', 'unpaid' => 'bad'][$inv->status] ?? 'info' }}">{{ $inv->status }}</span></div>
            @empty<div class="sub">{{ __('clinic.ui.empty') }}</div>@endforelse
        @endif
        </div>
    </aside>
@endif
</div>

{{--
SYSTEM DESIGN:
Patient profile drawer: tabbed (Overview & Vitals / History & Rx / Appointments / Invoices) with a persistent high-visibility allergy + chronic-condition banner under the header. Esc closes. Output is HTML-escaped by Blade ({{ }}) -> XSS-safe even for free-text clinical fields.

DATA STRUCTURE:
Tab = whitelisted string; each tab renders from the single eager-loaded Patient graph (no extra queries).
--}}
