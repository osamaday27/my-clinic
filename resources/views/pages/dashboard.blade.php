<div class="stack-lg">
    <div class="page-h"><div><h1>{{ __('clinic.nav.dashboard') }}</h1><p class="num">{{ today()->format('Y-m-d') }}</p></div></div>
    <livewire:kpi-strip />
    <livewire:appointments-board />
</div>

{{--
SYSTEM DESIGN:
Dashboard = KPI strip + live queue/schedule split view. Children refresh independently (30s / 15s polling).

DATA STRUCTURE:
Composition.
--}}
