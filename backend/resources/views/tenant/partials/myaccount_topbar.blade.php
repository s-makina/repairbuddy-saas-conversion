@php
  /** @var \App\Models\User|null $user */
  /** @var \App\Models\Tenant|null $tenant */
  $pageTitle = is_string($pageTitle ?? null) ? $pageTitle : 'Dashboard';
  $tenantSlug = $tenant?->slug;

  $firstName = '';
  if ($user && is_string($user->name)) {
    $parts = preg_split('/\s+/', trim($user->name));
    $firstName = is_array($parts) && count($parts) > 0 ? (string) $parts[0] : '';
  }

  $displayName = $user?->name ?? 'User';
  $initials = $firstName !== '' ? strtoupper(substr($firstName, 0, 1)) : 'U';
@endphp

<!-- Main Content -->
<div class="main-content" id="main-content">
  <!-- Top Bar -->
  <header class="top-bar bg-white shadow-sm border-bottom">
    <div class="container-fluid">
      <div class="row align-items-center py-2">
        <div class="col-md-6">
          <button class="btn btn-outline-secondary btn-sm me-2 d-md-none" id="sidebarToggle">
            <i class="bi bi-list"></i>
          </button>
          <h4 class="mb-0 text-dark">{{ $pageTitle }}</h4>
        </div>
        <div class="col-md-6 text-end">
          <div class="d-flex align-items-center justify-content-end gap-2">
            <!-- Fullscreen Toggle -->
            <button class="btn btn-outline-secondary btn-sm" id="fullscreenToggle" title="Toggle Fullscreen">
              <i class="bi bi-arrows-fullscreen"></i>
            </button>

            <div class="dropdown">
              <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" title="Theme Settings">
                <i class="bi bi-palette"></i>
              </button>
              <ul class="dropdown-menu rounded-md rounded-3 p-0">
                <li>
                  <div class="btn-group w-100" role="group">
                    <button type="button" class="btn theme-option" data-theme="light" title="Light Mode">
                      <i class="bi bi-sun"></i>
                    </button>
                    <button type="button" class="btn border-start border-end theme-option" data-theme="dark" title="Dark Mode">
                      <i class="bi bi-moon"></i>
                    </button>
                    <button type="button" class="btn theme-option" data-theme="auto" title="Auto Mode">
                      <i class="bi bi-circle-half"></i>
                    </button>
                  </div>
                </li>
              </ul>
            </div>

            <a class="btn btn-primary btn-sm" href="{{ route('tenant.jobs.create', ['business' => $tenantSlug]) }}">
              <i class="bi bi-plus-circle me-1"></i>New Job
            </a>

            <!-- User Menu -->
            <div class="dropdown">
              <button class="btn btn-outline-secondary btn-sm dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown">
                <div class="user-avatar bg-primary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                  <small class="text-white fw-bold">{{ $initials }}</small>
                </div>
                <span class="user-name">{{ $firstName !== '' ? $firstName : $displayName }}</span>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li>
                  <div class="dropdown-header text-muted small">Signed in as</div>
                </li>
                <li>
                  <div class="dropdown-header fw-bold">{{ $displayName }}</div>
                </li>
                <li><hr class="dropdown-divider m-1"></li>
                <li>
                  <a class="dropdown-item" href="#">
                    <i class="bi bi-person me-2"></i>Profile
                  </a>
                </li>
                <li>
                  <a class="dropdown-item" href="#">
                    <i class="bi bi-briefcase me-2"></i>My Jobs
                  </a>
                </li>
                <li><hr class="dropdown-divider m-1"></li>
                <li>
                  <form method="POST" action="{{ route('web.logout') }}" class="m-0">
                    @csrf
                    <button type="submit" class="dropdown-item text-danger">
                      <i class="bi bi-box-arrow-right me-2"></i>Logout
                    </button>
                  </form>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>
