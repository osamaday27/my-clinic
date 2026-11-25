@use('App\Enums\AppointmentStatus', 'S')
<div class="split" wire:poll.15s>
    <section class="card">
        <div class="card__h">
            <h2>{{ __('clinic.ui.schedule') }} <span class="sub num">{{ $date }}</span></h2>
            <div class="filters">
                @if ($pickDate)
                    <input type="date" class="input" wire:model.live="date" aria-label="{{ __('clinic.ui.today') }}">
                @endif
                @if (auth()->user()->role !== \App\Enums\Role::Doctor)
                    <select class="select" wire:model.live="doctorFilter" aria-label="{{ __('clinic.ui.doctor') }}">
                        <option value="">{{ __('clinic.ui.doctor') }}: {{ __('clinic.ui.all') }}</option>
                        @foreach ($this->doctors as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                    </select>
                @endif
                <select class="select" wire:model.live="statusFilter" aria-label="{{ __('clinic.ui.status') }}">
                    <option value="">{{ __('clinic.ui.status') }}: {{ __('clinic.ui.all') }}</option>
                    @foreach (S::cases() as $s)<option value="{{ $s->value }}">{{ $s->label() }}</option>@endforeach
                </select>
            </div>
        </div>

        @error('action')<div class="alert alert--bad" role="alert" style="margin:12px 20px"><span class="ms">error</span>{{ $message }}</div>@enderror

        <div class="tablewrap">
        <table>
            <thead><tr>
                <th>{{ __('clinic.ui.token') }}</th><th>{{ __('clinic.ui.patient') }}</th><th>{{ __('clinic.ui.doctor') }}</th>
                <th>{{ __('clinic.ui.time') }}</th><th>{{ __('clinic.ui.type') }}</th><th>{{ __('clinic.ui.status') }}</th><th>{{ __('clinic.ui.actions') }}</th>
            </tr></thead>
            <tbody>
            @forelse ($this->appointments as $a)
                <tr wire:key="appt-{{ $a->id }}">
                    <td><span class="tok num">{{ $a->token_number }}</span></td>
                    <td>
                        <button class="pname" type="button" wire:click="$dispatch('open-patient', { id: {{ $a->patient_id }} })">{{ $a->patient->name }}</button>
                        @if ($a->patient->hasAlerts())<span class="ms" style="color:var(--bad);font-size:16px" title="{{ __('clinic.ui.allergy') }}">warning</span>@endif
                        <div class="sub num">{{ $a->patient->file_number }}</div>
                    </td>
                    <td>{{ $a->doctor->name }}<div class="sub">{{ $a->clinic->name }}</div></td>
                    <td class="num">{{ $a->time_label }}</td>
                    <td>{{ $a->visit_type->label() }}</td>
                    <td><span class="badge badge--{{ $a->status->tone() }}">{{ $a->status->label() }}</span></td>
                    <td>
                        <div class="rowact">
                            @can('manage', $a)
                                @if (in_array($a->status, [S::Scheduled, S::Confirmed], true))
                                    <button class="btn btn--sm btn--primary" wire:click="checkIn({{ $a->id }})">{{ __('clinic.ui.check_in') }}</button>
                                    <button class="btn btn--sm" wire:click="openReschedule({{ $a->id }})">{{ __('clinic.ui.reschedule') }}</button>
                                @endif
                                @if (in_array($a->status, [S::Scheduled, S::Confirmed, S::CheckedIn], true))
                                    <button class="btn btn--sm btn--danger" type="button"
                                        @click="confirmThen(@js(__('clinic.confirm.cancel')), () => $wire.cancel({{ $a->id }}), { title: @js(__('clinic.ui.confirm_title')), yes: @js(__('clinic.ui.yes')), no: @js(__('clinic.ui.no')) })">{{ __('clinic.ui.cancel') }}</button>
                                @endif
                            @endcan
                            @can('treat', $a)
                                @if ($a->status === S::CheckedIn)
                                    <button class="btn btn--sm btn--primary" wire:click="start({{ $a->id }})">{{ __('clinic.ui.start_visit') }}</button>
                                @endif
                            @endcan
                            @can('chart', $a)
                                @if (in_array($a->status, [S::CheckedIn, S::InProgress], true))
                                    <button class="btn btn--sm" type="button" @click="Livewire.dispatch('open-visit-chart', { appointmentId: {{ $a->id }} })"><span class="ms">clinical_notes</span>{{ __('clinic.ui.chart') }}</button>
                                @endif
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">{{ __('clinic.ui.empty') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </section>

    <aside class="stack">
        <section class="card">
            <div class="card__h"><h2>{{ __('clinic.ui.next_patient') }}</h2></div>
            @if ($a = $this->active)
                <div class="card__b">
                    <div class="row" style="justify-content:space-between">
                        <strong style="font-size:17px">{{ $a->patient->name }}</strong>
                        <span class="badge badge--{{ $a->status->tone() }}">{{ $a->status->label() }}</span>
                    </div>
                    <div class="stack" style="margin-top:12px">
                        @if (filled($a->patient->allergies))
                            <div class="alert alert--bad" role="alert"><span class="ms">warning</span><span>{{ __('clinic.ui.allergy') }}: {{ $a->patient->allergies }}</span></div>
                        @endif
                        @if (filled($a->patient->chronic_conditions))
                            <div class="alert alert--warn"><span class="ms">monitor_heart</span><span>{{ __('clinic.ui.chronic') }}: {{ $a->patient->chronic_conditions }}</span></div>
                        @endif
                    </div>
                    <dl class="kv">
                        <dt>{{ __('clinic.ui.age') }}</dt><dd class="num">{{ $a->patient->age ?? '—' }} {{ __('clinic.ui.years') }}</dd>
                        <dt>{{ __('clinic.ui.last_visit') }}</dt><dd class="num">{{ $this->lastVisit ? \Illuminate\Support\Carbon::parse($this->lastVisit)->format('Y-m-d') : '—' }}</dd>
                        <dt>{{ __('clinic.ui.complaint') }}</dt><dd>{{ $a->chief_complaint ?: '—' }}</dd>
                        <dt>{{ __('clinic.ui.doctor') }}</dt><dd>{{ $a->doctor->name }}</dd>
                        <dt>{{ __('clinic.ui.token') }}</dt><dd class="num">#{{ $a->token_number }} · {{ $a->time_label }}</dd>
                    </dl>
                    <div class="rowact">
                        @can('chart', $a)
                            <button class="btn" type="button" @click="Livewire.dispatch('open-visit-chart', { appointmentId: {{ $a->id }} })"><span class="ms">clinical_notes</span>{{ __('clinic.ui.chart') }}</button>
                        @endcan
                        @can('treat', $a)
                            @if ($a->status === S::CheckedIn)
                                <button class="btn btn--primary" wire:click="start({{ $a->id }})">{{ __('clinic.ui.start_visit') }}</button>
                            @elseif ($a->status === S::InProgress)
                                <button class="btn" type="button" @click="Livewire.dispatch('open-prescription', { appointmentId: {{ $a->id }} })"><span class="ms">prescriptions</span>{{ __('clinic.ui.prescription') }}</button>
                                <button class="btn btn--primary" type="button"
                                    @click="confirmThen(@js(__('clinic.confirm.complete')), () => $wire.complete({{ $a->id }}), { title: @js(__('clinic.ui.confirm_title')), yes: @js(__('clinic.ui.yes')), no: @js(__('clinic.ui.no')) })">{{ __('clinic.ui.complete') }}</button>
                            @endif
                        @endcan
                    </div>
                </div>
            @else
                <div class="empty">{{ __('clinic.ui.no_active') }}</div>
            @endif
        </section>

        <section class="card">
            <div class="card__h"><h2>{{ __('clinic.ui.waiting_queue') }}</h2><span class="badge badge--warn num">{{ $this->queue->count() }}</span></div>
            <ul class="qlist">
                @forelse ($this->queue as $q)
                    <li wire:key="q-{{ $q->id }}"><span class="tok num">{{ $q->token_number }}</span>
                        <span class="grow"><strong>{{ $q->patient->name }}</strong><br><span class="sub">{{ $q->doctor->name }}</span></span>
                        <span class="sub num">{{ $q->checked_in_at ? (int) $q->checked_in_at->diffInMinutes(now()) : 0 }}′</span></li>
                @empty
                    <li class="sub">{{ __('clinic.ui.empty') }}</li>
                @endforelse
            </ul>
        </section>
    </aside>

    @if ($rescheduleId)
        <div class="scrim" wire:click="$set('rescheduleId', null)"></div>
        <div class="modal" role="dialog" aria-modal="true"><div class="modal__box" style="width:min(420px,100%)">
            <div class="card__h"><h2>{{ __('clinic.ui.reschedule') }}</h2></div>
            <div class="stack" style="padding:20px">
                <div class="field"><label>{{ __('clinic.attributes.appointment_date') }}</label><input type="date" class="input" style="width:100%" wire:model="newDate">@error('newDate')<div class="err">{{ $message }}</div>@enderror</div>
                <div class="field"><label>{{ __('clinic.attributes.start_time') }}</label><input type="time" class="input" style="width:100%" wire:model="newTime">@error('newTime')<div class="err">{{ $message }}</div>@enderror
                    @error('start_time')<div class="err" role="alert">{{ $message }}</div>@enderror</div>
                <div class="row"><button class="btn btn--primary" wire:click="saveReschedule">{{ __('clinic.ui.save') }}</button>
                    <button class="btn" wire:click="$set('rescheduleId', null)">{{ __('clinic.ui.close') }}</button></div>
            </div>
        </div></div>
    @endif
</div>

{{--
SYSTEM DESIGN:
Split view: LEFT = schedule table with filters + quick row actions (Check-in / Start / Reschedule / Cancel); RIGHT = 'Active consultation / next patient' card (allergy + chronic banners, age, last visit, complaint) and the waiting queue. Row actions are shown only if the Policy allows them (@can) and are re-checked on the server.

DATA STRUCTURE:
Table rows keyed (wire:key) for DOM diffing. Queue = list ordered by token_number (FIFO); waiting time = now - checked_in_at.
--}}
