<div class="stack-lg">
    <div class="page-h"><div><h1>{{ __('clinic.nav.appointments') }}</h1></div></div>
    <livewire:appointments-board :pick-date="true" />
</div>

{{--
SYSTEM DESIGN:
Calendar & queue view = same board with the date picker enabled.

DATA STRUCTURE:

--}}
