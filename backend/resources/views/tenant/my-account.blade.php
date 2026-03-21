@php
    $tenantSlug = $tenantSlug ?? $business ?? null;
    $tenant = $tenant ?? null;
    $activePage = 'account';
    $isSubdomain = request()->routeIs('tenant.subdomain.*');
    $tenantRoutePrefix = $isSubdomain ? 'tenant.subdomain' : 'tenant';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $tenant->name ?? 'RepairBuddy' }} — My Account</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/tenant-public.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tenant-my-account.css') }}">
    <link rel="stylesheet" href="{{ asset('repairbuddy/my_account/css/bootstrap-icons.min.css') }}">
    @stack('page-styles')
</head>
<body>
    @include('tenant.partials.tenant-nav', [
        'tenantSlug' => $tenantSlug,
        'tenant' => $tenant,
        'activePage' => $activePage
    ])

    <div class="page-wrapper">
        @livewire('tenant.public-pages.my-account', [
            'tenant' => $tenant,
            'business' => $tenantSlug,
        ])
    </div>

    @include('tenant.partials.tenant-footer', [
        'tenantSlug' => $tenantSlug,
        'tenant' => $tenant
    ])
</body>
</html>
