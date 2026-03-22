@php
    $tenant = $tenant ?? null;
    $tenantSlug = $tenantSlug ?? null;
    
    // Determine route prefix based on subdomain mode
    $isSubdomain = request()->routeIs('tenant.subdomain.*');
    $tenantRoutePrefix = $isSubdomain ? 'tenant.subdomain' : 'tenant';
@endphp

<!-- FOOTER -->
<footer class="footer">
    <div class="footer-inner">
        <div class="footer-copy">&copy; {{ date('Y') }} {{ $tenant->name ?? 'RepairBuddy' }}. All rights reserved.</div>
        <div class="footer-links">
            <a href="{{ route($tenantRoutePrefix . '.welcome', ['business' => $tenantSlug]) }}">Home</a>
            <a href="{{ route($tenantRoutePrefix . '.booking.show', ['business' => $tenantSlug]) }}">Book a Repair</a>
            <a href="{{ route($tenantRoutePrefix . '.services', ['business' => $tenantSlug]) }}">Our Services</a>
            <a href="{{ route($tenantRoutePrefix . '.myaccount', ['business' => $tenantSlug]) }}">My Account</a>
        </div>
        <div class="footer-powered">Powered by <a href="/"><img src="{{ asset('brand/logo.png') }}" alt="RepairBuddy" class="footer-logo"></a></div>
    </div>
</footer>
