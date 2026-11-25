<div class="stack-lg">
    <div class="page-h"><div><h1>{{ __('clinic.nav.lab') }}</h1></div>
        <select class="select" wire:model.live="status" aria-label="{{ __('clinic.ui.status') }}"><option value="">{{ __('clinic.ui.status') }}: {{ __('clinic.ui.all') }}</option><option value="requested">{{ __('clinic.ui.requested') }}</option><option value="resulted">{{ __('clinic.ui.resulted') }}</option></select></div>
    <section class="card"><div class="tablewrap"><table>
        <thead><tr><th>{{ __('clinic.ui.patient') }}</th><th>{{ __('clinic.ui.test_name') }}</th><th>{{ __('clinic.ui.type') }}</th><th>{{ __('clinic.ui.doctor') }}</th><th>{{ __('clinic.ui.status') }}</th><th></th></tr></thead>
        <tbody>
        @forelse ($this->requests as $r)
            <tr wire:key="lr-{{ $r->id }}">
                <td><button class="pname" wire:click="$dispatch('open-patient', { id: {{ $r->patient_id }} })">{{ $r->patient->name }}</button><div class="sub num">{{ $r->created_at->format('Y-m-d H:i') }}</div></td>
                <td>{{ $r->test_name }}@if ($r->result)<div class="sub">{{ \Illuminate\Support\Str::limit($r->result, 80) }}</div>@endif</td>
                <td>{{ __('clinic.ui.'.$r->type) }}</td><td>{{ $r->doctor->name }}</td>
                <td><span class="badge badge--{{ $r->status === 'resulted' ? 'ok' : 'warn' }}">{{ __('clinic.ui.'.$r->status) }}</span></td>
                <td><div class="rowact">
                    @can('result', $r)<button class="btn btn--sm btn--primary" wire:click="openResult({{ $r->id }})">{{ __('clinic.ui.record_result') }}</button>@endcan
                    @if ($r->attachment_path)<a class="btn btn--sm" href="{{ route('labs.attachment', $r) }}"><span class="ms">download</span>{{ __('clinic.ui.download') }}</a>@endif
                </div></td></tr>
        @empty<tr><td colspan="6" class="empty">{{ __('clinic.ui.empty') }}</td></tr>@endforelse
        </tbody></table></div>
        <div class="simple-pager"><button class="btn btn--sm" wire:click="previousPage" @disabled($this->requests->onFirstPage())>{{ __('clinic.ui.prev') }}</button>
            <button class="btn btn--sm" wire:click="nextPage" @disabled(! $this->requests->hasMorePages())>{{ __('clinic.ui.next') }}</button></div>
    </section>

    @if ($resultId)
        <div class="scrim" wire:click="$set('resultId', null)"></div>
        <div class="modal" role="dialog" aria-modal="true"><div class="modal__box" style="width:min(520px,100%)">
            <div class="card__h"><h2>{{ __('clinic.ui.record_result') }}</h2></div>
            <div class="stack" style="padding:20px">
                <div class="field"><label for="rs">{{ __('clinic.ui.result') }}</label><textarea id="rs" class="input" rows="4" maxlength="5000" wire:model="result"></textarea>
                    @error('result')<div class="err" role="alert">{{ $message }}</div>@enderror</div>
                <div class="field"><label for="fl">{{ __('clinic.ui.attachment') }}</label><input id="fl" type="file" class="input" style="width:100%;padding-top:8px" accept=".pdf,.jpg,.jpeg,.png" wire:model="file">
                    <div class="hint">PDF / JPG / PNG · ≤ {{ intdiv(config('clinic.lab.max_kb'), 1024) }} MB</div>
                    <div wire:loading wire:target="file" class="hint">…</div>
                    @error('file')<div class="err" role="alert">{{ $message }}</div>@enderror</div>
                <div class="row"><button class="btn btn--primary" wire:click="saveResult" wire:loading.attr="disabled" wire:target="saveResult">{{ __('clinic.ui.save') }}</button>
                    <button class="btn" wire:click="$set('resultId', null)">{{ __('clinic.ui.close') }}</button></div>
            </div>
        </div></div>
    @endif
</div>

{{--
SYSTEM DESIGN:
Lab worklist page + result-entry modal with file input and helper text (allowed types/size).

DATA STRUCTURE:

--}}
