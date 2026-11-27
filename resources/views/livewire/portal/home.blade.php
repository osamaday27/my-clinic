<div class="stack-lg">
    @php $p = $this->patient; @endphp
    <div class="page-h"><div><h1>{{ __('clinic.ui.hello') }} {{ $p->name }}</h1><p class="num">{{ $p->file_number }}</p></div></div>

    @if (session('status'))<div class="alert alert--warn" style="background:var(--ok-bg);color:var(--ok);border-color:#A7F3D0" role="status"><span class="ms">check_circle</span>{{ session('status') }}</div>@endif
    @error('action')<div class="alert alert--bad" role="alert"><span class="ms">error</span>{{ $message }}</div>@enderror
    @if (filled($p->allergies))<div class="alert alert--bad"><span class="ms">warning</span>{{ __('clinic.ui.allergy') }}: {{ $p->allergies }}</div>@endif

    <section class="card">
        <div class="card__h"><h2>{{ __('clinic.ui.upcoming') }}</h2><span class="sub">{{ __('clinic.ui.cancel_window') }}</span></div>
        <ul class="qlist" style="padding:0">
            @forelse ($this->upcoming as $a)
                <li wire:key="up-{{ $a->id }}" style="justify-content:space-between;flex-wrap:wrap">
                    <span><strong class="num">{{ $a->appointment_date->format('Y-m-d') }} · {{ $a->time_label }}</strong><br>
                        <span class="sub">{{ $a->doctor->name }} · {{ $a->clinic->name }} · {{ $a->branch->name }}</span></span>
                    <span class="row"><span class="badge badge--{{ $a->status->tone() }}">{{ $a->status->label() }}</span>
                        @if (in_array($a->status, [\App\Enums\AppointmentStatus::Scheduled, \App\Enums\AppointmentStatus::Confirmed], true))
                            <button class="btn btn--sm btn--danger" type="button"
                                @click="confirmThen(@js(__('clinic.confirm.cancel')), () => $wire.cancel({{ $a->id }}), { title: @js(__('clinic.ui.confirm_title')), yes: @js(__('clinic.ui.yes')), no: @js(__('clinic.ui.no')) })">{{ __('clinic.ui.cancel_appt') }}</button>
                        @endif</span></li>
            @empty<li class="sub">{{ __('clinic.ui.nothing_upcoming') }} — <a href="{{ route('portal.book') }}" style="color:var(--brand);font-weight:600">{{ __('clinic.ui.book_new') }}</a></li>@endforelse
        </ul>
    </section>

    <div class="split" style="grid-template-columns:repeat(2,minmax(0,1fr))">
        <section class="card"><div class="card__h"><h2>{{ __('clinic.ui.my_rx') }}</h2></div><ul class="qlist" style="padding:0">
            @forelse ($this->prescriptions as $rx)
                <li wire:key="rx-{{ $rx->id }}" style="justify-content:space-between"><span><strong class="num">{{ $rx->created_at->format('Y-m-d') }}</strong> <span class="sub">{{ $rx->doctor->name }}</span>
                    <br><span class="sub">{{ $rx->items->map(fn ($i) => $i->medicine->name)->join('، ') }}</span></span>
                    <a class="btn btn--sm" target="_blank" href="{{ route('portal.rx', $rx) }}"><span class="ms">print</span>{{ __('clinic.ui.print') }}</a></li>
            @empty<li class="sub">{{ __('clinic.ui.empty') }}</li>@endforelse</ul></section>

        <section class="card"><div class="card__h"><h2>{{ __('clinic.ui.my_labs') }}</h2></div><ul class="qlist" style="padding:0">
            @forelse ($this->labs as $l)
                <li wire:key="lb-{{ $l->id }}" style="justify-content:space-between"><span><strong>{{ $l->test_name }}</strong> <span class="sub num">{{ $l->created_at->format('Y-m-d') }}</span>
                    @if ($l->result)<br><span class="sub">{{ \Illuminate\Support\Str::limit($l->result, 100) }}</span>@endif</span>
                    @if ($l->attachment_path)<a class="btn btn--sm" href="{{ route('portal.lab', $l) }}"><span class="ms">download</span>{{ __('clinic.ui.download') }}</a>@endif</li>
            @empty<li class="sub">{{ __('clinic.ui.empty') }}</li>@endforelse</ul></section>
    </div>

    <div class="split" style="grid-template-columns:repeat(2,minmax(0,1fr))">
        <section class="card"><div class="card__h"><h2>{{ __('clinic.ui.my_invoices') }}</h2></div><ul class="qlist" style="padding:0">
            @forelse ($this->invoices as $i)
                <li wire:key="iv-{{ $i->id }}" style="justify-content:space-between"><span><strong class="num">{{ $i->number }}</strong><br><span class="sub num">{{ $i->total }} / {{ $i->paid }}</span></span>
                    <span class="badge badge--{{ ['paid' => 'ok', 'partial' => 'warn', 'unpaid' => 'bad'][$i->status] ?? 'info' }}">{{ $i->status }}</span></li>
            @empty<li class="sub">{{ __('clinic.ui.empty') }}</li>@endforelse</ul></section>

        <section class="card"><div class="card__h"><h2>{{ __('clinic.ui.past') }}</h2></div><ul class="qlist" style="padding:0">
            @forelse ($this->past as $a)
                <li wire:key="pa-{{ $a->id }}" style="justify-content:space-between"><span><strong class="num">{{ $a->appointment_date->format('Y-m-d') }}</strong><br><span class="sub">{{ $a->doctor->name }} · {{ $a->visit_type->label() }}</span></span>
                    <span class="badge badge--{{ $a->status->tone() }}">{{ $a->status->label() }}</span></li>
            @empty<li class="sub">{{ __('clinic.ui.empty') }}</li>@endforelse</ul></section>
    </div>
</div>

{{--
SYSTEM DESIGN:
Patient dashboard: upcoming (with guarded cancel), prescriptions (printable), final lab results (downloadable), invoices and visit history. Responsive 2-column grid collapses to one column on phones.

DATA STRUCTURE:

--}}
