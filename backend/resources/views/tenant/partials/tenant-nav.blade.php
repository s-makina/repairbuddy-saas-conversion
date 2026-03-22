@php
    $tenantSlug = $tenantSlug ?? null;
    $tenant = $tenant ?? null;
    $activePage = $activePage ?? 'home';
    
    // Generate shop initials from tenant name
    $shopInitials = $tenant ? strtoupper(collect(explode(' ', $tenant->name))->map(fn($w) => substr($w, 0, 1))->take(2)->join('')) : 'RB';
    
    // Determine route prefix based on subdomain mode
    $isSubdomain = request()->routeIs('tenant.subdomain.*');
    $tenantRoutePrefix = $isSubdomain ? 'tenant.subdomain' : 'tenant';
    $tenantHomeUrl = $isSubdomain ? url('/') : url('/t/' . $tenantSlug);
    
    // Nav items with icons for mobile menu
    $navItems = [
        ['id' => 'home', 'label' => 'Home', 'route' => $tenantRoutePrefix . '.welcome', 'icon' => '🏠'],
        ['id' => 'book', 'label' => 'Book a Repair', 'route' => $tenantRoutePrefix . '.booking.show', 'icon' => '📱'],
        ['id' => 'services', 'label' => 'Our Services', 'route' => $tenantRoutePrefix . '.services', 'icon' => '🔧'],
    ];
@endphp

<!-- NAV -->
<nav class="rb-navbar">
    <div class="nav-inner">
        <a href="{{ $tenantHomeUrl }}" class="nav-brand">
            <div>
            @if($tenant && $tenant->logo_url)
                <img src="{{ $tenant->logo_url }}" alt="{{ $tenant->name }}" class="shop-logo-img">
            @else
                <img src="{{ asset('brand/logo.png') }}" alt="RepairBuddy" class="shop-logo-img">
            @endif
                <!-- <div class="shop-name">{{ $tenant->name ?? 'RepairBuddy' }}</div> -->
                <!-- <div class="shop-sub">{{ $tenantSlug }}.{{ config('tenancy.base_domain') }}</div> -->
            </div>
        </a>
        <div class="nav-links">
            @foreach($navItems as $item)
                <a href="{{ route($item['route'], ['business' => $tenantSlug]) }}" {{ $activePage === $item['id'] ? 'class="active"' : '' }}>
                    {{ $item['label'] }}
                </a>
            @endforeach
            @auth
                <a href="{{ route($tenantRoutePrefix . '.myaccount', ['business' => $tenantSlug]) }}" class="btn btn-outline">My Account</a>
                <form method="POST" action="{{ route($tenantRoutePrefix . '.logout', ['business' => $tenantSlug]) }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-outline">Sign Out</button>
                </form>
            @else
                <a href="{{ route($tenantRoutePrefix . '.login', ['business' => $tenantSlug]) }}" class="btn btn-outline">Sign In</a>
            @endauth
            <a href="{{ route($tenantRoutePrefix . '.booking.show', ['business' => $tenantSlug]) }}" class="btn btn-primary text-white">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Book Now
            </a>
        </div>
        <button class="nav-mobile-toggle" onclick="document.getElementById('mobileMenu{{ $tenantSlug }}').classList.toggle('show')">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
    </div>
    <div id="mobileMenu{{ $tenantSlug }}" class="nav-mobile-menu">
        @foreach($navItems as $item)
            <a href="{{ route($item['route'], ['business' => $tenantSlug]) }}" {{ $activePage === $item['id'] ? 'class="active"' : '' }}>
                {{ $item['icon'] }} {{ $item['label'] }}
            </a>
        @endforeach
        @auth
            <a href="{{ route($tenantRoutePrefix . '.myaccount', ['business' => $tenantSlug]) }}">👤 My Account</a>
            <form method="POST" action="{{ route($tenantRoutePrefix . '.logout', ['business' => $tenantSlug]) }}" style="display:inline;">
                @csrf
                <button type="submit" class="btn btn-outline" style="width:100%;text-align:left;">🚪 Sign Out</button>
            </form>
        @else
            <a href="{{ route($tenantRoutePrefix . '.login', ['business' => $tenantSlug]) }}">🔑 Sign In</a>
        @endauth
    </div>
</nav>
