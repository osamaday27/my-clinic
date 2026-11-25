<div class="stack-lg">
    <div class="page-h"><div><h1>{{ __('clinic.nav.inventory') }}</h1></div>
        <div class="row"><span class="badge badge--warn">{{ __('clinic.ui.expiring') }}: <span class="num">{{ $this->alerts['expiring'] }}</span></span>
            <span class="badge badge--bad">{{ __('clinic.ui.low') }}: <span class="num">{{ $this->alerts['low'] }}</span></span></div></div>
    <section class="card"><div class="tablewrap"><table>
        <thead><tr><th>SKU</th><th>{{ __('clinic.ui.name') }}</th><th>{{ __('clinic.ui.qty') }}</th><th>{{ __('clinic.ui.expiry') }}</th><th>{{ __('clinic.ui.status') }}</th></tr></thead>
        <tbody>
        @forelse ($this->items as $s)
            @php
                $expired = $s->expiry_date && $s->expiry_date->isPast();
                $soon = $s->expiry_date && ! $expired && $s->expiry_date->lte(now()->addDays(config('clinic.expiry_warning_days')));
                $low = $s->quantity <= $s->min_quantity;
            @endphp
            <tr wire:key="s-{{ $s->id }}"><td class="num">{{ $s->sku }}</td><td>{{ $s->name }}</td><td class="num">{{ $s->quantity }}</td><td class="num">{{ $s->expiry_date?->format('Y-m-d') ?? '—' }}</td>
                <td>@if ($expired)<span class="badge badge--bad">{{ __('clinic.ui.expired') }}</span>@elseif ($soon)<span class="badge badge--warn">{{ __('clinic.ui.expiring') }}</span>@elseif ($low)<span class="badge badge--warn">{{ __('clinic.ui.low') }}</span>@else<span class="badge badge--ok">{{ __('clinic.ui.ok') }}</span>@endif</td></tr>
        @empty<tr><td colspan="5" class="empty">{{ __('clinic.ui.empty') }}</td></tr>@endforelse
        </tbody></table></div></section>
</div>

{{--
SYSTEM DESIGN:
Inventory with FEFO ordering and expiry / low-stock badges.

DATA STRUCTURE:

--}}
