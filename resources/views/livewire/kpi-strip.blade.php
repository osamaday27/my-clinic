@php
    $k = $this->kpis;
    $t = function ($v, $goodUp = true) {
        $cls = $v == 0 ? 'flat' : ((($v > 0) === $goodUp) ? 'good' : 'bad');
        $ic = $v > 0 ? 'trending_up' : ($v < 0 ? 'trending_down' : 'trending_flat');
        return [$cls, $ic];
    };
    [$c1, $i1] = $t($k['appointments']['trend']);
    [$c2, $i2] = $t($k['waiting']['trend'], false);
    [$c3, $i3] = $t($k['revenue']['trend']);
    [$c4, $i4] = $t($k['no_show']['trend'], false);
@endphp
<section class="kpis" wire:poll.30s aria-label="KPIs">
    <article class="card kpi">
        <div class="kpi__l"><span class="ms">event_available</span>{{ __('clinic.kpi.appointments') }}</div>
        <div class="kpi__v">{{ $k['appointments']['total'] }}</div>
        <div class="kpi__f">
            <span class="num">{{ $k['appointments']['checked_in'] + $k['appointments']['in_progress'] + $k['appointments']['completed'] }} {{ __('clinic.kpi.checked') }} · {{ $k['appointments']['pending'] }} {{ __('clinic.kpi.pending') }}</span>
            <span class="trend trend--{{ $c1 }}"><span class="ms">{{ $i1 }}</span>{{ abs($k['appointments']['trend']) }}%</span>
        </div>
    </article>
    <article class="card kpi">
        <div class="kpi__l"><span class="ms">airline_seat_recline_normal</span>{{ __('clinic.kpi.waiting') }}</div>
        <div class="kpi__v">{{ $k['waiting']['count'] }}</div>
        <div class="kpi__f"><span class="badge badge--warn">{{ __('clinic.ui.wait_trend') }}</span>
            <span class="trend trend--{{ $c2 }}"><span class="ms">{{ $i2 }}</span>{{ abs($k['waiting']['trend']) }}%</span></div>
    </article>
    <article class="card kpi">
        <div class="kpi__l"><span class="ms">payments</span>{{ __('clinic.kpi.revenue') }}</div>
        <div class="kpi__v">{{ number_format($k['revenue']['amount']) }} <small class="sub">{{ __('clinic.kpi.currency') }}</small></div>
        <div class="kpi__f"><span>{{ __('clinic.ui.vs_yesterday') }}</span>
            <span class="trend trend--{{ $c3 }}"><span class="ms">{{ $i3 }}</span>{{ abs($k['revenue']['trend']) }}%</span></div>
    </article>
    <article class="card kpi">
        <div class="kpi__l"><span class="ms">event_busy</span>{{ __('clinic.kpi.no_show') }}</div>
        <div class="kpi__v">{{ $k['no_show']['rate'] }}%</div>
        <div class="kpi__f"><span>{{ __('clinic.ui.vs_yesterday') }}</span>
            <span class="trend trend--{{ $c4 }}"><span class="ms">{{ $i4 }}</span>{{ abs($k['no_show']['trend']) }}</span></div>
    </article>
</section>

{{--
SYSTEM DESIGN:
KPI strip (4 cards) with trend chips. Colour semantics invert for 'bad-when-up' metrics (waiting, no-show). Never relies on colour alone: arrow icon + number accompany it (accessibility).

DATA STRUCTURE:
Input: nested associative array from DashboardService::kpis().
--}}
