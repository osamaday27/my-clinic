<div class="stack-lg">
    <div class="page-h"><div><h1>{{ __('clinic.nav.billing') }}</h1></div>
        <select class="select" wire:model.live="status"><option value="">{{ __('clinic.ui.status') }}: {{ __('clinic.ui.all') }}</option><option value="unpaid">unpaid</option><option value="partial">partial</option><option value="paid">paid</option></select></div>
    <section class="card"><div class="tablewrap"><table>
        <thead><tr><th>{{ __('clinic.ui.invoice') }}</th><th>{{ __('clinic.ui.patient') }}</th><th>{{ __('clinic.ui.total') }}</th><th>{{ __('clinic.ui.paid') }}</th><th>{{ __('clinic.ui.balance') }}</th><th>{{ __('clinic.ui.status') }}</th><th></th></tr></thead>
        <tbody>
        @forelse ($this->invoices as $i)
            <tr wire:key="i-{{ $i->id }}"><td class="num">{{ $i->number }}</td><td>{{ $i->patient->name }}</td><td class="num">{{ $i->total }}</td><td class="num">{{ $i->paid }}</td><td class="num">{{ $i->balance }}</td>
                <td><span class="badge badge--{{ ['paid' => 'ok', 'partial' => 'warn', 'unpaid' => 'bad'][$i->status] ?? 'info' }}">{{ $i->status }}</span></td>
                <td>@if ($i->status !== 'paid')<button class="btn btn--sm btn--primary" wire:click="collect({{ $i->id }})">{{ __('clinic.ui.collect') }}</button>@endif</td></tr>
        @empty<tr><td colspan="7" class="empty">{{ __('clinic.ui.empty') }}</td></tr>@endforelse
        </tbody></table></div>
        <div class="simple-pager"><button class="btn btn--sm" wire:click="previousPage" @disabled($this->invoices->onFirstPage())>{{ __('clinic.ui.prev') }}</button>
            <button class="btn btn--sm" wire:click="nextPage" @disabled(! $this->invoices->hasMorePages())>{{ __('clinic.ui.next') }}</button></div>
    </section>

    @if ($payId)
        <section class="card"><div class="card__h"><h2>{{ __('clinic.ui.collect') }}</h2></div>
            <div class="row" style="padding:20px;align-items:flex-start">
                <div class="field"><label>{{ __('clinic.ui.amount') }}</label><input class="input num" wire:model="amount" inputmode="decimal">@error('amount')<div class="err" role="alert">{{ $message }}</div>@enderror</div>
                <div class="field"><label>{{ __('clinic.ui.method') }}</label><select class="select" wire:model="method"><option value="cash">{{ __('clinic.ui.cash') }}</option><option value="card">{{ __('clinic.ui.card') }}</option><option value="insurance">{{ __('clinic.ui.insurance_m') }}</option></select></div>
                <button class="btn btn--primary" style="margin-top:22px" wire:click="pay">{{ __('clinic.ui.save') }}</button>
                <button class="btn" style="margin-top:22px" wire:click="$set('payId', null)">{{ __('clinic.ui.close') }}</button>
            </div></section>
    @endif
</div>

{{--
SYSTEM DESIGN:
Invoices ledger with partial payments (Rule: billing).

DATA STRUCTURE:
Paginated list + inline payment form.
--}}
