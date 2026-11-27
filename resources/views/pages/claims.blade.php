@use('App\Enums\ClaimStatus', 'C')
<div class="stack-lg">
    <div class="page-h"><div><h1>{{ __('clinic.ui.claims') }}</h1></div>
        <div class="tabs" role="tablist" style="border:0">
            <button role="tab" aria-selected="{{ $tab === 'claims' ? 'true' : 'false' }}" wire:click="$set('tab','claims')">{{ __('clinic.ui.claims') }}</button>
            <button role="tab" aria-selected="{{ $tab === 'companies' ? 'true' : 'false' }}" wire:click="$set('tab','companies')">{{ __('clinic.ui.companies') }}</button>
        </div></div>

    @error('action')<div class="alert alert--bad" role="alert"><span class="ms">error</span>{{ $message }}</div>@enderror

    @if ($tab === 'claims')
        @php $s = $this->summary; @endphp
        <section class="kpis" style="grid-template-columns:repeat(3,minmax(0,1fr))">
            <article class="card kpi"><div class="kpi__l">{{ __('clinic.ui.pending_amt') }}</div><div class="kpi__v">{{ number_format($s['pending'], 2) }}</div></article>
            <article class="card kpi"><div class="kpi__l">{{ __('clinic.ui.receivable') }}</div><div class="kpi__v">{{ number_format($s['receivable'], 2) }}</div></article>
            <article class="card kpi"><div class="kpi__l">{{ __('clinic.claim.rejected') }}</div><div class="kpi__v">{{ $s['rejected'] }}</div></article>
        </section>

        <section class="card">
            <div class="card__h"><h2>{{ __('clinic.ui.claims') }}</h2>
                <div class="filters">
                    <select class="select" wire:model.live="status" aria-label="{{ __('clinic.ui.status') }}"><option value="">{{ __('clinic.ui.status') }}: {{ __('clinic.ui.all') }}</option>@foreach (C::cases() as $c)<option value="{{ $c->value }}">{{ $c->label() }}</option>@endforeach</select>
                    <select class="select" wire:model.live="companyFilter" aria-label="{{ __('clinic.ui.company') }}"><option value="">{{ __('clinic.ui.company') }}: {{ __('clinic.ui.all') }}</option>@foreach ($this->companies as $co)<option value="{{ $co->id }}">{{ $co->name }}</option>@endforeach</select>
                </div></div>
            <div class="tablewrap"><table>
                <thead><tr><th>{{ __('clinic.ui.claim_no') }}</th><th>{{ __('clinic.ui.patient') }}</th><th>{{ __('clinic.ui.company') }}</th><th>{{ __('clinic.ui.claimed') }}</th><th>{{ __('clinic.ui.approved') }}</th><th>{{ __('clinic.ui.received') }}</th><th>{{ __('clinic.ui.status') }}</th><th></th></tr></thead>
                <tbody>
                @forelse ($this->claims as $c)
                    <tr wire:key="cl-{{ $c->id }}">
                        <td class="num">{{ $c->claim_number }}<div class="sub num">{{ $c->invoice->number }}</div></td>
                        <td>{{ $c->patient->name }}</td><td>{{ $c->company->name }}</td>
                        <td class="num">{{ $c->amount_claimed }}</td><td class="num">{{ $c->amount_approved ?? '—' }}</td><td class="num">{{ $c->amount_paid }}</td>
                        <td><span class="badge badge--{{ $c->status->tone() }}">{{ $c->status->label() }}</span>
                            @if ((float) $c->patient_shift > 0)<div class="sub num">{{ __('clinic.ui.patient_share') }}: {{ $c->patient_shift }}</div>@endif
                            @if ($c->rejection_reason)<div class="sub">{{ $c->rejection_reason }}</div>@endif</td>
                        <td>@can('manage', $c)<div class="rowact">
                            @if ($c->status === C::Draft)<button class="btn btn--sm btn--primary" wire:click="submit({{ $c->id }})">{{ __('clinic.ui.submit') }}</button>@endif
                            @if ($c->status === C::Submitted)<button class="btn btn--sm btn--primary" wire:click="openDecide({{ $c->id }})">{{ __('clinic.ui.decide') }}</button>@endif
                            @if ($c->status === C::Rejected)<button class="btn btn--sm" wire:click="submit({{ $c->id }})">{{ __('clinic.ui.appeal') }}</button>@endif
                            @if (in_array($c->status, [C::Approved, C::PartiallyApproved], true))<button class="btn btn--sm btn--primary" wire:click="openPay({{ $c->id }})">{{ __('clinic.ui.receive_payment') }}</button>@endif
                        </div>@endcan</td></tr>
                @empty<tr><td colspan="8" class="empty">{{ __('clinic.ui.empty') }}</td></tr>@endforelse
                </tbody></table></div>
            <div class="simple-pager"><button class="btn btn--sm" wire:click="previousPage" @disabled($this->claims->onFirstPage())>{{ __('clinic.ui.prev') }}</button>
                <button class="btn btn--sm" wire:click="nextPage" @disabled(! $this->claims->hasMorePages())>{{ __('clinic.ui.next') }}</button></div>
        </section>

        @if ($decideId)
            <div class="scrim" wire:click="$set('decideId', null)"></div>
            <div class="modal" role="dialog" aria-modal="true"><div class="modal__box" style="width:min(460px,100%)">
                <div class="card__h"><h2>{{ __('clinic.ui.decide') }}</h2></div>
                <div class="stack" style="padding:20px">
                    <div class="field"><label for="dd">{{ __('clinic.ui.decision') }}</label><select id="dd" class="select" style="width:100%" wire:model.live="decision">
                        <option value="approved">{{ __('clinic.ui.approve_full') }}</option><option value="partially_approved">{{ __('clinic.ui.approve_part') }}</option><option value="rejected">{{ __('clinic.ui.reject') }}</option></select></div>
                    @if ($decision === 'partially_approved')
                        <div class="field"><label for="da">{{ __('clinic.ui.approved_amount') }}</label><input id="da" class="input num" style="width:100%" inputmode="decimal" wire:model="approvedAmount">
                            @error('approved')<div class="err" role="alert">{{ $message }}</div>@enderror</div>
                    @endif
                    @if ($decision === 'rejected')
                        <div class="field"><label for="dr">{{ __('clinic.ui.reason') }}</label><input id="dr" class="input" style="width:100%" maxlength="255" wire:model="reason">
                            @error('reason')<div class="err" role="alert">{{ $message }}</div>@enderror</div>
                    @endif
                    <div class="row"><button class="btn btn--primary" wire:click="saveDecide" wire:loading.attr="disabled">{{ __('clinic.ui.save') }}</button><button class="btn" wire:click="$set('decideId', null)">{{ __('clinic.ui.close') }}</button></div>
                </div>
            </div></div>
        @endif

        @if ($payId)
            <div class="scrim" wire:click="$set('payId', null)"></div>
            <div class="modal" role="dialog" aria-modal="true"><div class="modal__box" style="width:min(420px,100%)">
                <div class="card__h"><h2>{{ __('clinic.ui.receive_payment') }}</h2></div>
                <div class="stack" style="padding:20px">
                    <div class="field"><label for="pa">{{ __('clinic.ui.amount') }}</label><input id="pa" class="input num" style="width:100%" inputmode="decimal" wire:model="payAmount">
                        @error('payAmount')<div class="err" role="alert">{{ $message }}</div>@enderror @error('amount')<div class="err" role="alert">{{ $message }}</div>@enderror</div>
                    <div class="row"><button class="btn btn--primary" wire:click="savePay" wire:loading.attr="disabled">{{ __('clinic.ui.save') }}</button><button class="btn" wire:click="$set('payId', null)">{{ __('clinic.ui.close') }}</button></div>
                </div>
            </div></div>
        @endif
    @else
        <section class="card"><div class="tablewrap"><table>
            <thead><tr><th>{{ __('clinic.ui.name_ar') }}</th><th>{{ __('clinic.ui.name_en') }}</th><th>{{ __('clinic.ui.coverage') }}</th><th>{{ __('clinic.ui.status') }}</th><th></th></tr></thead>
            <tbody>@foreach ($this->companies as $co)<tr wire:key="co-{{ $co->id }}"><td>{{ $co->name_ar }}</td><td>{{ $co->name_en }}</td><td class="num">{{ $co->coverage_percent }}%</td>
                <td><span class="badge badge--{{ $co->is_active ? 'ok' : 'bad' }}">{{ $co->is_active ? __('clinic.ui.active') : __('clinic.ui.inactive') }}</span></td>
                <td>@if (auth()->user()->role === \App\Enums\Role::Manager)<div class="rowact"><button class="btn btn--sm" wire:click="editCompany({{ $co->id }})">{{ __('clinic.ui.save') }}…</button>
                    <button class="btn btn--sm" wire:click="toggleCompany({{ $co->id }})">{{ $co->is_active ? __('clinic.ui.inactive') : __('clinic.ui.active') }}</button></div>@endif</td></tr>@endforeach</tbody></table></div></section>
        @if (auth()->user()->role === \App\Enums\Role::Manager)
            <section class="card"><div class="card__h"><h2>{{ $companyId ? __('clinic.ui.companies') : __('clinic.ui.add_company') }}</h2></div>
                <div class="form">
                    <div class="field"><label>{{ __('clinic.ui.name_ar') }}</label><input class="input" style="width:100%" wire:model="c_name_ar">@error('c_name_ar')<div class="err">{{ $message }}</div>@enderror</div>
                    <div class="field"><label>{{ __('clinic.ui.name_en') }}</label><input class="input" style="width:100%" dir="ltr" wire:model="c_name_en">@error('c_name_en')<div class="err">{{ $message }}</div>@enderror</div>
                    <div class="field"><label>{{ __('clinic.ui.coverage') }}</label><input class="input num" style="width:100%" inputmode="decimal" wire:model="c_percent"><div class="hint">0 – 100</div>@error('c_percent')<div class="err">{{ $message }}</div>@enderror</div>
                    <div class="field" style="align-self:end"><button class="btn btn--primary" wire:click="saveCompany">{{ __('clinic.ui.add_company') }}</button></div>
                </div></section>
        @endif
    @endif
</div>

{{--
SYSTEM DESIGN:
Claims page: KPI cards (under review / receivable / rejected), filterable worklist, decision and payment modals (fields appear only when relevant), and an insurers tab.

DATA STRUCTURE:

--}}
