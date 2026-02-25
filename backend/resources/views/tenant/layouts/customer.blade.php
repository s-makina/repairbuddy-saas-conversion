@php
    /** @var \App\Models\Tenant|null $tenant */
    $siteName    = ($tenant && $tenant->name) ? $tenant->name : config('app.name', 'RepairBuddy');
    $business    = $business ?? ($tenant->slug ?? '');
    $customTitle = trim($__env->yieldContent('title'));
    $thePageTitle = $customTitle !== '' ? $customTitle : ($siteName . ' — My Portal');
    $activeMenu  = $activeMenu ?? 'dashboard';
    $user        = $user ?? auth()->user();

    $menuItems = [
        ['key' => 'dashboard',  'label' => 'Dashboard',    'icon' => 'bi-speedometer2',        'route' => 'tenant.customer.dashboard'],
        ['key' => 'jobs',       'label' => 'My Jobs',      'icon' => 'bi-tools',               'route' => 'tenant.customer.jobs'],
        ['key' => 'estimates',  'label' => 'My Estimates',  'icon' => 'bi-file-earmark-text',  'route' => 'tenant.customer.estimates'],
        ['key' => 'devices',    'label' => 'My Devices',    'icon' => 'bi-phone',              'route' => 'tenant.customer.devices'],
        ['key' => 'account',    'label' => 'My Account',    'icon' => 'bi-person-circle',      'route' => 'tenant.customer.account'],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $thePageTitle }}</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Bootstrap --}}
    <link rel="stylesheet" href="{{ asset('repairbuddy/my_account/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('repairbuddy/my_account/css/bootstrap-icons.min.css') }}">

    {{-- Customer Portal Styles --}}
    <link rel="stylesheet" href="{{ asset('css/customer-portal.css') }}">

    @livewireStyles
    @stack('page-styles')
</head>
<body class="cp-body">

    {{-- ═══════════════════════ Top Bar ═══════════════════════ --}}
    <header class="cp-topbar">
        <div class="cp-topbar-inner">
            {{-- Left: Brand --}}
            <div class="cp-topbar-left">
                <a href="{{ route('tenant.customer.dashboard', ['business' => $business]) }}" class="cp-brand">
                    @if($tenant && $tenant->logo_url)
                        <img src="{{ $tenant->logo_url }}" alt="{{ $siteName }}" class="cp-brand-logo">
                    @else
                        <span class="cp-brand-icon">
                            <i class="bi bi-shield-check"></i>
                        </span>
                    @endif
                    <span class="cp-brand-name">{{ $siteName }}</span>
                </a>
            </div>

            {{-- Right: User menu --}}
            <div class="cp-topbar-right">
                <a href="{{ route('tenant.booking.show', ['business' => $business]) }}"
                   class="cp-btn-book">
                    <i class="bi bi-plus-lg"></i>
                    <span>Book Repair</span>
                </a>
                <div class="cp-user-menu" x-data="{ open: false }">
                    <button @click="open = !open" class="cp-user-toggle" type="button">
                        <span class="cp-user-avatar">
                            {{ strtoupper(substr($user->first_name ?? $user->name ?? 'U', 0, 1)) }}
                        </span>
                        <span class="cp-user-name d-none d-md-inline">{{ $user->first_name ?? $user->name }}</span>
                        <i class="bi bi-chevron-down cp-chevron"></i>
                    </button>
                    <div class="cp-dropdown" x-show="open" @click.outside="open = false" x-cloak>
                        <div class="cp-dropdown-header">
                            <strong>{{ $user->name }}</strong>
                            <small>{{ $user->email }}</small>
                        </div>
                        <div class="cp-dropdown-divider"></div>
                        <a href="{{ route('tenant.customer.account', ['business' => $business]) }}" class="cp-dropdown-item">
                            <i class="bi bi-person"></i> My Account
                        </a>
                        <a href="{{ route('tenant.booking.show', ['business' => $business]) }}" class="cp-dropdown-item">
                            <i class="bi bi-house"></i> Back to Site
                        </a>
                        <div class="cp-dropdown-divider"></div>
                        <form method="POST" action="{{ route('web.logout') }}">
                            @csrf
                            <button type="submit" class="cp-dropdown-item cp-dropdown-logout">
                                <i class="bi bi-box-arrow-right"></i> Sign Out
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Mobile sidebar toggle --}}
                <button class="cp-mobile-toggle d-md-none" type="button"
                        onclick="document.getElementById('cpSidebar').classList.toggle('show')">
                    <i class="bi bi-list"></i>
                </button>
            </div>
        </div>
    </header>

    {{-- ═══════════════════════ Layout ═══════════════════════ --}}
    <div class="cp-layout">

        {{-- ─── Sidebar ─── --}}
        <aside class="cp-sidebar" id="cpSidebar">
            <div class="cp-sidebar-top">
                <div class="cp-sidebar-label">Customer Portal</div>
            </div>

            <nav class="cp-sidebar-nav">
                <ul class="cp-nav-list">
                    @foreach($menuItems as $item)
                        <li>
                            <a href="{{ route($item['route'], ['business' => $business]) }}"
                               class="cp-nav-item {{ $activeMenu === $item['key'] ? 'active' : '' }}"
                               @if($activeMenu === $item['key']) aria-current="page" @endif>
                                <i class="bi {{ $item['icon'] }} cp-nav-icon"></i>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <div class="cp-sidebar-footer">
                <a href="{{ route('tenant.booking.show', ['business' => $business]) }}" class="cp-nav-item">
                    <i class="bi bi-arrow-left cp-nav-icon"></i>
                    <span>Back to Site</span>
                </a>
            </div>
        </aside>

        {{-- ─── Content ─── --}}
        <main class="cp-content">
            {{-- Flash messages --}}
            @if(session('success'))
                <div class="cp-flash cp-flash-success">
                    <i class="bi bi-check-circle"></i>
                    <span>{{ session('success') }}</span>
                    <button class="cp-flash-dismiss" onclick="this.parentElement.remove()">&times;</button>
                </div>
            @endif
            @if(session('error'))
                <div class="cp-flash cp-flash-error">
                    <i class="bi bi-exclamation-circle"></i>
                    <span>{{ session('error') }}</span>
                    <button class="cp-flash-dismiss" onclick="this.parentElement.remove()">&times;</button>
                </div>
            @endif

            @yield('content')
        </main>

    </div>

    {{-- ═══ Footer ═══ --}}
    <footer class="cp-footer">
        <div class="cp-footer-inner">
            &copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.
        </div>
    </footer>

    <script src="{{ asset('repairbuddy/my_account/js/bootstrap.bundle.min.js') }}"></script>

    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @livewireScripts
    @stack('page-scripts')
</body>
</html>
