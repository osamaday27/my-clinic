<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('clinic.app') }}</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=IBM+Plex+Sans+Arabic:wght@400;600;700&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined&display=swap">
    <link rel="stylesheet" href="{{ asset('css/clinic.css') }}">
</head>
<body><main class="auth">{{ $slot }}</main></body>
</html>

{{--
SYSTEM DESIGN:
Minimal layout for unauthenticated pages.

DATA STRUCTURE:

--}}
