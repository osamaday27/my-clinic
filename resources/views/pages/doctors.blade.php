<div class="stack-lg">
    <div class="page-h"><div><h1>{{ __('clinic.nav.doctors') }}</h1></div></div>
    <section class="card"><div class="tablewrap"><table>
        <thead><tr><th>{{ __('clinic.ui.doctor') }}</th><th>{{ __('clinic.ui.department') }}</th><th>{{ __('clinic.ui.phone') }}</th><th>{{ __('clinic.ui.today_visits') }}</th><th>{{ __('clinic.ui.commission') }}</th><th>{{ __('clinic.ui.status') }}</th><th></th></tr></thead>
        <tbody>
        @foreach ($this->doctors as $d)
            <tr wire:key="d-{{ $d->id }}"><td><strong>{{ $d->name }}</strong><div class="sub">{{ $d->specialty }}</div></td><td>{{ $d->clinic->name }}</td>
                <td class="num">{{ $d->user->phone ?: '—' }}</td><td class="num">{{ $d->today_count }}</td><td class="num">{{ $d->commission_rate }}%</td>
                <td><span class="badge badge--{{ $d->is_active ? 'ok' : 'bad' }}">{{ $d->is_active ? __('clinic.ui.active') : __('clinic.ui.inactive') }}</span></td>
                <td>@if (auth()->user()->role === \App\Enums\Role::Manager)
                    <button class="btn btn--sm" type="button" @click="confirmThen(@js(__('clinic.confirm.toggle')), () => $wire.toggle({{ $d->id }}), { title: @js(__('clinic.ui.confirm_title')), yes: @js(__('clinic.ui.yes')), no: @js(__('clinic.ui.no')) })">{{ $d->is_active ? __('clinic.ui.inactive') : __('clinic.ui.active') }}</button>@endif</td></tr>
        @endforeach
        </tbody></table></div></section>
</div>

{{--
SYSTEM DESIGN:
Doctors & staff list with activate/deactivate toggle and SweetAlert confirmation.

DATA STRUCTURE:

--}}
