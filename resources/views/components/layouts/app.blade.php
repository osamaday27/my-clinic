@php
    $user = auth()->user();
    $rtl = app()->getLocale() === 'ar';
    $branchId = \App\Support\CurrentBranch::id();
    $alerts = \Illuminate\Support\Facades\Cache::remember('alerts:'.$branchId, 60, fn () =>
        \App\Models\StockItem::where('branch_id', $branchId)->expiringSoon()->count() + \App\Models\StockItem::where('branch_id', $branchId)->low()->count());
    $nav = [
        ['dashboard', 'dashboard', 'home', []],
        ['appointments', 'appointments', 'calendar_month', []],
        ['patients', 'patients', 'groups', ['manager', 'reception', 'doctor', 'nurse']],
        ['doctors', 'doctors', 'stethoscope', ['manager', 'reception']],
        ['billing', 'billing', 'receipt_long', ['manager', 'reception', 'accountant']],
        ['insurance', 'insurance', 'verified_user', ['manager', 'reception', 'accountant']],
        ['lab', 'lab', 'biotech', ['manager', 'doctor', 'nurse']],
        ['inventory', 'inventory', 'medication', ['manager', 'pharmacist', 'nurse']],
        ['settings', 'settings', 'settings', ['manager']],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? __('clinic.app') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Readex+Pro:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined&display=swap">
    <link rel="stylesheet" href="{{ asset('css/clinic.css') }}">
    <script src="{{ asset('js/clinic.js') }}"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js"></script>
    @livewireStyles
</head>
<body>
<a class="skip" href="#main">{{ __('clinic.ui.skip') }}</a>
<div class="shell" x-data="navState()" :class="{ 'nav-collapsed': collapsed, 'nav-open': open }">
    <aside class="sidebar" aria-label="Primary">
        <div class="brand">
            <span class="brand__mark"><span class="ms">health_and_safety</span></span>
            <span class="brand__t">{{ __('clinic.app') }}</span>
        </div>
        <nav class="nav">
            @foreach ($nav as [$route, $key, $icon, $roles])
                @if (! $roles || in_array($user->role->value, $roles, true))
                    <a href="{{ route($route) }}" @if (request()->routeIs($route)) aria-current="page" @endif title="{{ __('clinic.nav.'.$key) }}">
                        <span class="ms">{{ $icon }}</span><span class="nav__t">{{ __('clinic.nav.'.$key) }}</span>
                    </a>
                @endif
            @endforeach
        </nav>
        <div class="side-foot">
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="btn" style="width:100%;border:0;justify-content:flex-start"><span class="ms">logout</span><span class="nav__t">{{ __('clinic.ui.logout') }}</span></button>
            </form>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <button class="iconbtn" type="button" @click="toggle()" aria-label="Menu"><span class="ms">menu</span></button>
            <livewire:global-search />
            <span class="grow"></span>

            @if ($user->role === \App\Enums\Role::Manager)
                <form method="POST" action="{{ route('branch') }}" class="hide-m">@csrf
                    <select name="branch_id" class="select" onchange="this.form.submit()" aria-label="{{ __('clinic.ui.branch') }}">
                        @foreach (\App\Models\Branch::where('is_active', true)->get() as $b)
                            <option value="{{ $b->id }}" @selected($b->id === $branchId)>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </form>
            @else
                <span class="badge badge--info badge--plain hide-m">{{ $user->branch?->name }}</span>
            @endif

            @can('create', \App\Models\Appointment::class)
                <button type="button" class="btn btn--primary" @click="Livewire.dispatch('open-new-appointment')">
                    <span class="ms">add</span><span class="hide-m">{{ __('clinic.ui.new_appointment') }}</span>
                </button>
            @endcan

            <form method="POST" action="{{ route('locale') }}">@csrf
                <input type="hidden" name="locale" value="{{ $rtl ? 'en' : 'ar' }}">
                <button class="btn btn--sm" type="submit">{{ __('clinic.ui.language') }}</button>
            </form>

            <a class="iconbtn" href="{{ route('inventory') }}" aria-label="{{ __('clinic.ui.notifications') }}">
                <span class="ms">notifications</span>
                @if ($alerts > 0)<span class="badge-dot num">{{ $alerts }}</span>@endif
            </a>
            <a class="who" href="{{ route('security') }}" title="{{ __('clinic.ui.security') }}">
                <span class="avatar">{{ $user->initials() }}</span>
                <span class="hide-m"><strong>{{ $user->name }}</strong><br><small>{{ $user->role->label() }}</small></span>
            </a>
        </header>

        <main class="canvas" id="main">{{ $slot }}</main>
    </div>
</div>

<livewire:patient-drawer />
<livewire:new-appointment />
<livewire:prescription-builder />
<livewire:visit-chart />
<livewire:patient-form />
<livewire:portal-account-modal />

<div class="toasts" x-data="toasts()" @toast.window="push($event.detail.message)" aria-live="polite">
    <template x-for="t in list" :key="t.id"><div class="toast" x-text="t.m"></div></template>
</div>
@livewireScripts
</body>
</html>

{{--
SYSTEM DESIGN:
Application shell: 240px collapsible sidebar (72px collapsed, off-canvas on mobile), 64px sticky top bar (global search, branch selector, + New Appointment CTA, bell with badge, profile). Navigation is filtered by role so users never see links they cannot open. Direction (RTL/LTR) and lang are set from the active locale.

DATA STRUCTURE:
Nav = array of tuples [route, i18n key, icon, allowed roles]. Alert badge count cached 60s per branch.
--}}
