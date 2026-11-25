<div class="stack-lg">
    <div class="page-h"><div><h1>{{ __('clinic.nav.settings') }}</h1></div></div>
    <section class="card"><div class="card__h"><h2>{{ __('clinic.ui.branches') }}</h2></div><div class="tablewrap"><table>
        <thead><tr><th>{{ __('clinic.ui.name') }}</th><th>{{ __('clinic.ui.phone') }}</th><th>{{ __('clinic.ui.department') }}</th></tr></thead>
        <tbody>@foreach ($this->branches as $b)<tr><td>{{ $b->name }}</td><td class="num">{{ $b->phone }}</td><td class="num">{{ $b->clinics_count }}</td></tr>@endforeach</tbody></table></div></section>
    <section class="card"><div class="card__h"><h2>{{ __('clinic.ui.staff') }}</h2></div><div class="tablewrap"><table>
        <thead><tr><th>{{ __('clinic.ui.name') }}</th><th>{{ __('clinic.ui.role') }}</th><th>{{ __('clinic.ui.branch') }}</th><th>{{ __('clinic.ui.status') }}</th><th></th></tr></thead>
        <tbody>@foreach ($this->staff as $u)<tr wire:key="u-{{ $u->id }}"><td>{{ $u->name }}<div class="sub">{{ $u->email }}</div></td><td>{{ $u->role->label() }}</td><td>{{ $u->branch?->name ?? '—' }}</td>
            <td><span class="badge badge--{{ $u->is_active ? 'ok' : 'bad' }}">{{ $u->is_active ? __('clinic.ui.active') : __('clinic.ui.inactive') }}</span></td>
            <td>@if ($u->id !== auth()->id())<button class="btn btn--sm" type="button" @click="confirmThen(@js(__('clinic.confirm.toggle')), () => $wire.toggle({{ $u->id }}), { title: @js(__('clinic.ui.confirm_title')), yes: @js(__('clinic.ui.yes')), no: @js(__('clinic.ui.no')) })">{{ $u->is_active ? __('clinic.ui.inactive') : __('clinic.ui.active') }}</button>@endif</td></tr>@endforeach</tbody></table></div></section>
</div>

{{--
SYSTEM DESIGN:
Manager-only: branches overview and staff access control (enable/disable accounts).

DATA STRUCTURE:

--}}
