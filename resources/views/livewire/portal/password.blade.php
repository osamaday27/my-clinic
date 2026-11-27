<div class="stack-lg" style="max-width:520px">
    <div class="page-h"><div><h1>{{ __('clinic.ui.change_password') }}</h1>
        @if (auth()->user()->must_change_password)<p>{{ __('clinic.ui.must_change') }}</p>@endif</div></div>
    <section class="card"><div class="stack" style="padding:20px">
        <div class="field"><label for="cp">{{ __('clinic.ui.cur_password') }}</label><input id="cp" type="password" class="input" style="width:100%" autocomplete="current-password" wire:model="current">@error('current')<div class="err" role="alert">{{ $message }}</div>@enderror</div>
        <div class="field"><label for="np">{{ __('clinic.ui.new_password') }}</label><input id="np" type="password" class="input" style="width:100%" autocomplete="new-password" wire:model="password"><div class="hint">{{ __('clinic.ui.pw_hint') }}</div>@error('password')<div class="err" role="alert">{{ $message }}</div>@enderror</div>
        <div class="field"><label for="cf">{{ __('clinic.ui.confirm_password') }}</label><input id="cf" type="password" class="input" style="width:100%" autocomplete="new-password" wire:model="password_confirmation" wire:keydown.enter="save"></div>
        <button class="btn btn--primary" style="width:fit-content" wire:click="save" wire:loading.attr="disabled">{{ __('clinic.ui.save') }}</button>
    </div></section>
</div>

{{--
SYSTEM DESIGN:
Forced / voluntary password change screen.

DATA STRUCTURE:

--}}
