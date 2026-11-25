<x-layouts.guest>
    <div class="card">
        <div class="brand" style="padding-inline:0"><span class="brand__mark"><span class="ms">health_and_safety</span></span><span>{{ __('clinic.app') }}</span></div>
        <form method="POST" action="{{ url('/login') }}" class="stack" style="margin-top:12px">
            @csrf
            <div class="field">
                <label for="email">{{ __('clinic.ui.email') }}</label>
                <input class="input" style="width:100%" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                @error('email')<div class="err" role="alert">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="password">{{ __('clinic.ui.password') }}</label>
                <input class="input" style="width:100%" id="password" name="password" type="password" required autocomplete="current-password">
            </div>
            <label class="row"><input type="checkbox" name="remember" value="1"> {{ __('clinic.ui.remember') }}</label>
            <button class="btn btn--primary" style="justify-content:center">{{ __('clinic.ui.login') }}</button>
        </form>
    </div>
</x-layouts.guest>

{{--
SYSTEM DESIGN:
Login form: CSRF token, generic error message (no user enumeration), autocomplete hints, throttle:5,1 on the POST route.

DATA STRUCTURE:

--}}
