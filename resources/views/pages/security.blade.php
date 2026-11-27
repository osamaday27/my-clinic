@php
    $u = $this->user;
    $required = in_array($u->role->value, config('clinic.two_factor.required_roles'), true);
    $pending = ! $u->hasTwoFactor() && filled($u->two_factor_secret);
@endphp
<div class="stack-lg">
    <div class="page-h"><div><h1>{{ __('clinic.ui.security') }}</h1><p>{{ $u->name }} · {{ $u->email }}</p></div></div>

    @if ($required && ! $u->hasTwoFactor())
        <div class="alert alert--warn" role="alert"><span class="ms">shield</span>{{ __('clinic.ui.tf_required') }}</div>
    @endif

    <section class="card">
        <div class="card__h"><h2>{{ __('clinic.ui.two_factor') }}</h2>
            <span class="badge badge--{{ $u->hasTwoFactor() ? 'ok' : 'warn' }}">{{ $u->hasTwoFactor() ? __('clinic.ui.tf_on') : __('clinic.ui.tf_off') }}</span></div>
        <div class="stack" style="padding:20px">

            @if ($codes)
                <div class="alert alert--warn"><span class="ms">key</span><span>{{ __('clinic.ui.tf_recovery_hint') }}</span></div>
                <div class="preview"><h3>{{ __('clinic.ui.tf_recovery') }}</h3>
                    <div class="slots" style="grid-template-columns:repeat(auto-fill,minmax(190px,1fr))">@foreach ($codes as $c)<code class="num" dir="ltr" style="font-size:15px">{{ $c }}</code>@endforeach</div></div>
            @endif

            @if (! $u->hasTwoFactor() && ! $pending)
                <button class="btn btn--primary" style="width:fit-content" wire:click="start">{{ __('clinic.ui.tf_enable') }}</button>

            @elseif ($pending)
                <p style="margin:0">{{ __('clinic.ui.tf_steps') }}</p>
                <div class="field"><label>{{ __('clinic.ui.tf_secret') }}</label>
                    <code class="num" dir="ltr" style="font-size:18px;letter-spacing:.08em;user-select:all">{{ trim(chunk_split($u->two_factor_secret, 4, ' ')) }}</code></div>
                <div class="field"><label>{{ __('clinic.ui.tf_uri') }}</label>
                    <input class="input" style="width:100%" dir="ltr" readonly value="{{ app(\App\Services\TwoFactorService::class)->uri($u) }}" onclick="this.select()"></div>
                <div class="field" style="max-width:260px"><label for="tfc">{{ __('clinic.ui.tf_code') }}</label>
                    <input id="tfc" class="input num" style="width:100%" inputmode="numeric" autocomplete="one-time-code" maxlength="10" wire:model="code" wire:keydown.enter="confirm">
                    @error('code')<div class="err" role="alert">{{ $message }}</div>@enderror</div>
                <button class="btn btn--primary" style="width:fit-content" wire:click="confirm">{{ __('clinic.ui.tf_confirm') }}</button>

            @else
                <div class="field" style="max-width:320px"><label for="pw">{{ __('clinic.ui.cur_password') }}</label>
                    <input id="pw" type="password" class="input" style="width:100%" autocomplete="current-password" wire:model="password">
                    @error('password')<div class="err" role="alert">{{ $message }}</div>@enderror</div>
                <div class="row">
                    <button class="btn" wire:click="regenerate"><span class="ms">key</span>{{ __('clinic.ui.tf_regen') }}</button>
                    <span class="sub num">{{ __('clinic.ui.tf_codes_left') }}: {{ count($u->two_factor_recovery_codes ?? []) }}</span>
                </div>
                <hr style="border:0;border-top:1px solid var(--line);width:100%">
                @if (! $showDisable)
                    <button class="btn btn--danger" style="width:fit-content" wire:click="$set('showDisable', true)">{{ __('clinic.ui.tf_disable') }}</button>
                @else
                    <div class="field" style="max-width:260px"><label for="dc">{{ __('clinic.ui.tf_code') }}</label>
                        <input id="dc" class="input num" style="width:100%" inputmode="numeric" autocomplete="one-time-code" maxlength="10" wire:model="code">
                        @error('code')<div class="err" role="alert">{{ $message }}</div>@enderror</div>
                    <div class="row"><button class="btn btn--danger" wire:click="disable">{{ __('clinic.ui.tf_disable') }}</button>
                        <button class="btn" wire:click="$set('showDisable', false)">{{ __('clinic.ui.close') }}</button></div>
                @endif
            @endif
        </div>
    </section>
</div>

{{--
SYSTEM DESIGN:
Security page: enrol (secret + otpauth link + code confirmation) -> show recovery codes once -> manage (regenerate / disable). Secret is displayed in 4-char groups for manual entry; helper text under each field.

DATA STRUCTURE:
Three UI states derived from user columns: not-enrolled / pending (secret, unconfirmed) / enabled.
--}}
