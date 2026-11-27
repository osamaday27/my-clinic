<div class="stack-lg" style="max-width:760px">
    <div class="page-h"><div><h1>{{ __('clinic.ui.book_new') }}</h1><p>{{ __('clinic.ui.portal_book_hint') }}</p></div></div>
    @error('action')<div class="alert alert--bad" role="alert"><span class="ms">error</span>{{ $message }}</div>@enderror
    <section class="card"><div class="form">
        <div class="field full"><label for="bd">{{ __('clinic.ui.doctor') }}</label>
            <select id="bd" class="select" style="width:100%" wire:model.live="doctor_id"><option value="">—</option>
                @foreach ($this->doctors as $d)<option value="{{ $d->id }}">{{ $d->name }} — {{ $d->specialty }} — {{ $d->clinic->name }} ({{ $d->clinic->branch->name }})</option>@endforeach</select>
            @error('doctor_id')<div class="err" role="alert">{{ $message }}</div>@enderror</div>
        <div class="field"><label for="bdate">{{ __('clinic.attributes.appointment_date') }}</label>
            <input id="bdate" type="date" class="input" style="width:100%" min="{{ today()->toDateString() }}" max="{{ today()->addDays(60)->toDateString() }}" wire:model.live="appointment_date">
            <div class="hint">{{ __('clinic.ui.hint_date') }}</div>@error('appointment_date')<div class="err" role="alert">{{ $message }}</div>@enderror</div>
        <div class="field"><label for="bc">{{ __('clinic.ui.complaint') }}</label><input id="bc" class="input" style="width:100%" maxlength="500" wire:model="chief_complaint">@error('chief_complaint')<div class="err">{{ $message }}</div>@enderror</div>
        <div class="field full"><label>{{ __('clinic.ui.pick_slot') }}</label>
            @if ($this->slots)
                <div class="slots" role="group">@foreach ($this->slots as $time => $free)<button type="button" class="slot" @disabled(! $free) aria-pressed="{{ $start_time === $time ? 'true' : 'false' }}" wire:click="$set('start_time', '{{ $time }}')">{{ $time }}</button>@endforeach</div>
                <div class="hint">{{ __('clinic.ui.hint_slot') }}</div>
            @else<div class="hint">{{ __('clinic.ui.no_slots') }}</div>@endif
            @error('start_time')<div class="err" role="alert">{{ $message }}</div>@enderror</div>
        <div class="full row" style="justify-content:flex-end"><a class="btn" href="{{ route('portal') }}">{{ __('clinic.ui.back') }}</a>
            <button class="btn btn--primary" wire:click="save" wire:loading.attr="disabled">{{ __('clinic.ui.save') }}</button></div>
    </div></section>
</div>

{{--
SYSTEM DESIGN:
Booking form: slot grid, helper text and inline errors; same server-side guarantees as staff booking plus portal limits.

DATA STRUCTURE:

--}}
