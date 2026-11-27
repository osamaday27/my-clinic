<div>
@if ($open && ($acc = $this->account) !== false)
    <div class="scrim" wire:click="close"></div>
    <div class="modal" role="dialog" aria-modal="true" @keydown.escape.window="$wire.close()"><div class="modal__box" style="width:min(520px,100%)">
        <div class="card__h"><h2>{{ __('clinic.ui.portal_account') }}</h2><button class="iconbtn" wire:click="close" aria-label="{{ __('clinic.ui.close') }}"><span class="ms">close</span></button></div>
        <div class="stack" style="padding:20px">
            @if ($tempPassword)
                <div class="alert alert--warn" role="alert"><span class="ms">key</span><span>{{ __('clinic.ui.temp_password') }}</span></div>
                <code class="num" dir="ltr" style="font-size:20px;user-select:all;text-align:center;padding:12px;background:var(--bg);border-radius:10px">{{ $tempPassword }}</code>
            @endif
            @if ($acc)
                <div class="row" style="justify-content:space-between"><span>{{ __('clinic.ui.account_exists') }}<br><span class="sub num" dir="ltr">{{ $acc->email }}</span></span>
                    <span class="badge badge--{{ $acc->is_active ? 'ok' : 'bad' }}">{{ $acc->is_active ? __('clinic.ui.active') : __('clinic.ui.inactive') }}</span></div>
                <div class="row"><button class="btn" wire:click="resetPassword"><span class="ms">lock_reset</span>{{ __('clinic.ui.reset_password') }}</button>
                    <button class="btn btn--danger" wire:click="toggle">{{ $acc->is_active ? __('clinic.ui.disable_account') : __('clinic.ui.enable_account') }}</button></div>
            @else
                <div class="sub">{{ __('clinic.ui.no_account') }}</div>
                <div class="field"><label for="pa-em">{{ __('clinic.ui.portal_email') }}</label><input id="pa-em" type="email" class="input" style="width:100%" dir="ltr" wire:model="email" wire:keydown.enter="create">
                    @error('email')<div class="err" role="alert">{{ $message }}</div>@enderror</div>
                <button class="btn btn--primary" style="width:fit-content" wire:click="create" wire:loading.attr="disabled">{{ __('clinic.ui.create_account') }}</button>
            @endif
        </div>
    </div></div>
@endif
</div>

{{--
SYSTEM DESIGN:
Front-desk modal for portal account lifecycle (create / reset / disable). The temporary password is displayed once and removed when the modal closes.

DATA STRUCTURE:

--}}
