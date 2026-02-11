@php
  /** @var \App\Models\Tenant|null $tenant */
  /** @var \App\Models\User|null $user */

  $activeNav = is_string($activeNav ?? null) ? $activeNav : null;

  $tenantSlug = null;
  if ($tenant && is_string($tenant->slug) && $tenant->slug !== '') {
    $tenantSlug = $tenant->slug;
  } elseif (is_string(request()->route('business')) && request()->route('business') !== '') {
    $tenantSlug = (string) request()->route('business');
  }

  $frontendBase = rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/');

  $nextTenantBase = $tenantSlug ? ($frontendBase . '/app/' . $tenantSlug) : ($frontendBase . '/app');

  $logoUrl = null;
  if ($tenant && is_string($tenant->logo_url) && $tenant->logo_url !== '') {
    $logoUrl = $tenant->logo_url;
  }

  $brandFallback = '/brand/repair-buddy-logo.png';
@endphp

<nav class="rb-sidebar d-flex flex-column text-white" id="sidebar">
  <div class="rb-sidebar-header p-3 border-bottom" style="border-color: rgba(255,255,255,0.15) !important;">
    <div class="d-flex align-items-center gap-2">
      @if ($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ $tenant?->name ?? 'RepairBuddy' }}" style="max-width: 160px; height: auto;" />
      @else
        <img src="{{ $brandFallback }}" alt="RepairBuddy" style="max-width: 160px; height: auto;" />
      @endif
    </div>
  </div>

  <div class="p-3 flex-grow-1 overflow-auto">
    <div class="nav nav-pills flex-column gap-1">
      <a class="nav-link {{ $activeNav === 'dashboard' ? 'active' : '' }}" href="{{ $tenantSlug ? route('tenant.dashboard', ['business' => $tenantSlug]) : '#' }}">
        <i class="bi bi-speedometer2 me-2" aria-hidden="true"></i>
        Dashboard
      </a>

      <a class="nav-link {{ $activeNav === 'jobs' ? 'active' : '' }}" href="{{ $nextTenantBase . '/jobs' }}">
        <i class="bi bi-briefcase me-2" aria-hidden="true"></i>
        Jobs
      </a>

      <a class="nav-link {{ $activeNav === 'estimates' ? 'active' : '' }}" href="{{ $nextTenantBase . '/estimates' }}">
        <i class="bi bi-file-earmark-text me-2" aria-hidden="true"></i>
        Estimates
      </a>

      <a class="nav-link {{ $activeNav === 'devices' ? 'active' : '' }}" href="{{ $nextTenantBase . '/devices' }}">
        <i class="bi bi-laptop me-2" aria-hidden="true"></i>
        Devices
      </a>

      <a class="nav-link {{ $activeNav === 'parts' ? 'active' : '' }}" href="{{ $nextTenantBase . '/parts' }}">
        <i class="bi bi-box-seam me-2" aria-hidden="true"></i>
        Parts
      </a>

      <a class="nav-link {{ $activeNav === 'customers' ? 'active' : '' }}" href="{{ $nextTenantBase . '/clients' }}">
        <i class="bi bi-people me-2" aria-hidden="true"></i>
        Customers
      </a>

      <a class="nav-link {{ $activeNav === 'settings' ? 'active' : '' }}" href="{{ $nextTenantBase . '/settings' }}">
        <i class="bi bi-gear me-2" aria-hidden="true"></i>
        Settings
      </a>
    </div>
  </div>

  <div class="border-top p-3" style="border-color: rgba(255,255,255,0.15) !important;">
    <div class="rounded p-3" style="background: rgba(255,255,255,0.08);">
      <div class="fw-semibold text-truncate">{{ $user?->name ?? 'User' }}</div>
      <div class="small text-white-50 text-truncate">{{ $user?->email ?? '' }}</div>
    </div>
  </div>
</nav>
