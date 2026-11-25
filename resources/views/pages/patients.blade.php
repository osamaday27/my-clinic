<div class="stack-lg">
    <div class="page-h"><div><h1>{{ __('clinic.nav.patients') }}</h1></div>
        <input class="input" style="width:280px" type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('clinic.ui.search') }}" aria-label="{{ __('clinic.ui.search') }}"></div>
    <section class="card">
        <div class="tablewrap"><table>
            <thead><tr><th>{{ __('clinic.ui.file') }}</th><th>{{ __('clinic.ui.name') }}</th><th>{{ __('clinic.ui.phone') }}</th><th>{{ __('clinic.ui.age') }}</th><th>{{ __('clinic.ui.insurance') }}</th><th></th></tr></thead>
            <tbody>
            @forelse ($this->patients as $p)
                <tr wire:key="p-{{ $p->id }}"><td class="num">{{ $p->file_number }}</td>
                    <td><button class="pname" wire:click="$dispatch('open-patient', { id: {{ $p->id }} })">{{ $p->name }}</button></td>
                    <td class="num">{{ $p->phone }}</td><td class="num">{{ $p->age ?? '—' }}</td><td>{{ $p->insurance_company ?: '—' }}</td>
                    <td>@if ($p->hasAlerts())<span class="badge badge--bad">{{ __('clinic.ui.allergy') }}</span>@endif</td></tr>
            @empty<tr><td colspan="6" class="empty">{{ __('clinic.ui.empty') }}</td></tr>@endforelse
            </tbody></table></div>
        <div class="simple-pager">
            <button class="btn btn--sm" wire:click="previousPage" @disabled($this->patients->onFirstPage())>{{ __('clinic.ui.prev') }}</button>
            <button class="btn btn--sm" wire:click="nextPage" @disabled(! $this->patients->hasMorePages())>{{ __('clinic.ui.next') }}</button>
        </div>
    </section>
</div>

{{--
SYSTEM DESIGN:
Patient directory (server-side search + simple pagination).

DATA STRUCTURE:
Page of 15 rows.
--}}
