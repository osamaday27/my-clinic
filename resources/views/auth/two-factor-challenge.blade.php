<x-layouts.guest>
    <div class="card" x-data="{ recovery: false }">
        <div class="brand" style="padding-inline:0"><span class="brand__mark"><span class="ms">shield_lock</span></span><span>{{ __('clinic.ui.tf_challenge') }}</span></div>
        <form method="POST" action="{{ route('two-factor.verify') }}" class="stack" style="margin-top:12px">
            @csrf
            <p class="sub" style="margin:0" x-show="!recovery">{{ __('clinic.ui.tf_challenge_hint') }}</p>
            <div class="field" x-show="!recovery">
                <label for="code">{{ __('clinic.ui.tf_code') }}</label>
                <input class="input num" style="width:100%;font-size:20px;letter-spacing:.2em;text-align:center" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="10" x-bind:autofocus="!recovery" autofocus>
            </div>
            <div class="field" x-show="recovery" x-cloak style="display:none">
                <label for="rc">{{ __('clinic.ui.tf_use_recovery') }}</label>
                <input class="input num" style="width:100%" id="rc" name="recovery_code" maxlength="32" dir="ltr" x-bind:disabled="!recovery" placeholder="xxxx-xxxx-xxxx-xxxx" autocomplete="off">
            </div>
            @error('code')<div class="err" role="alert" style="color:var(--bad);font-weight:600">{{ $message }}</div>@enderror
            <button class="btn btn--primary" style="justify-content:center">{{ __('clinic.ui.tf_verify') }}</button>
            <button type="button" class="btn btn--sm" style="justify-content:center;border:0" @click="recovery = !recovery">{{ __('clinic.ui.tf_use_recovery') }}</button>
        </form>
    </div>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
</x-layouts.guest>

{{--
SYSTEM DESIGN:
Second-step login screen (TOTP or recovery code). Uses Alpine only for the toggle; the form works without JavaScript for the TOTP path.

DATA STRUCTURE:
Two mutually exclusive inputs; the hidden one is disabled so it is not submitted.
--}}
