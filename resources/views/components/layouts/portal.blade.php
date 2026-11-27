@php $rtl = app()->getLocale() === 'ar'; $user = auth()->user(); @endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('clinic.ui.portal_title') }}</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined&display=swap">
    <link rel="stylesheet" href="{{ asset('css/clinic.css') }}">
    <script src="{{ asset('js/clinic.js') }}"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js"></script>
    @livewireStyles
</head>
<body>
<a class="skip" href="#main">{{ __('clinic.ui.skip') }}</a>
<header class="topbar">
    <a class="brand" style="padding:0" href="{{ route('portal') }}"><span class="brand__mark"><span class="ms">health_and_safety</span></span><span>{{ __('clinic.ui.portal_title') }}</span></a>
    <span class="grow"></span>
    @unless ($user->must_change_password)
        <a class="btn btn--primary" href="{{ route('portal.book') }}"><span class="ms">add</span><span class="hide-m">{{ __('clinic.ui.book_new') }}</span></a>
    @endunless
    <form method="POST" action="{{ route('locale') }}">@csrf<input type="hidden" name="locale" value="{{ $rtl ? 'en' : 'ar' }}"><button class="btn btn--sm">{{ __('clinic.ui.language') }}</button></form>
    <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn--sm"><span class="ms">logout</span><span class="hide-m">{{ __('clinic.ui.logout') }}</span></button></form>
</header>
<main class="canvas" id="main" style="margin-inline:auto">{{ $slot }}</main>
<div class="toasts" x-data="toasts()" @toast.window="push($event.detail.message)" aria-live="polite">
    <template x-for="t in list" :key="t.id"><div class="toast" x-text="t.m"></div></template>
</div>
@livewireScripts
</body>
</html>

{{--
SYSTEM DESIGN:
Lightweight patient-facing layout (no staff navigation, no search across patients). Reuses the same design tokens, RTL support and toast component.

DATA STRUCTURE:

--}}
