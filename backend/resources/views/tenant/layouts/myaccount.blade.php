@include('tenant.partials.myaccount_head', [
  'tenant' => $tenant ?? null,
  'pageTitle' => $pageTitle ?? ($title ?? 'Dashboard'),
])

@include('tenant.partials.myaccount_sidebar', [
  'tenant' => $tenant ?? null,
  'user' => $user ?? null,
  'activeNav' => $activeNav ?? null,
])

@include('tenant.partials.myaccount_topbar', [
  'tenant' => $tenant ?? null,
  'user' => $user ?? null,
  'pageTitle' => $pageTitle ?? ($title ?? 'Dashboard'),
])

@yield('content')

@include('tenant.partials.myaccount_footer')
