<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head><meta charset="utf-8"><title>{{ __('clinic.rx.title') }} #{{ $rx->id }}</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=IBM+Plex+Sans+Arabic:wght@400;600&display=swap">
<link rel="stylesheet" href="{{ asset('css/clinic.css') }}">
<style>body{background:#fff;padding:32px;max-width:780px;margin:auto}.hd{display:flex;justify-content:space-between;border-bottom:2px solid var(--brand);padding-bottom:12px;margin-bottom:16px}</style></head>
<body onload="window.print()">
<div class="hd"><div><h1 style="margin:0">{{ $rx->doctor->clinic->branch->name ?? config('clinic.name') }}</h1><div class="sub">{{ $rx->doctor->clinic->name }}</div></div>
    <div style="text-align:end"><strong>{{ $rx->doctor->name }}</strong><div class="sub">{{ $rx->doctor->specialty }}</div></div></div>
<p><strong>{{ __('clinic.ui.patient') }}:</strong> {{ $rx->patient->name }} · <span class="num">{{ $rx->patient->file_number }}</span> · <span class="num">{{ $rx->created_at->format('Y-m-d') }}</span></p>
<h2>℞ {{ __('clinic.rx.title') }}</h2>
<table><thead><tr><th>{{ __('clinic.rx.medicine') }}</th><th>{{ __('clinic.rx.dose') }}</th><th>{{ __('clinic.rx.frequency') }}</th><th>{{ __('clinic.rx.days') }}</th></tr></thead>
<tbody>@foreach ($rx->items as $i)<tr><td>{{ $i->medicine->name }} {{ $i->medicine->strength }}</td><td>{{ $i->dose }}</td><td>{{ $i->frequency }}</td><td class="num">{{ $i->duration_days }}</td></tr>@endforeach</tbody></table>
@if ($rx->notes)<p>{{ $rx->notes }}</p>@endif
<p style="margin-top:56px">{{ __('clinic.rx.signature') }}: ____________________</p>
</body></html>

{{--
SYSTEM DESIGN:
Printable prescription rendered in the PATIENT's language (Rule 22) via app()->setLocale in the controller.

DATA STRUCTURE:

--}}
