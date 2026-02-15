@php
  /** @var \App\Models\Tenant|null $tenant */
  /** @var \App\Models\User|null $user */
  $brandText = (is_string($tenant?->name) && $tenant?->name !== '')
    ? (string) $tenant->name
    : (string) config('app.name', 'RepairBuddy');

  $tenantSlug = is_string($tenant?->slug) && $tenant?->slug !== '' ? (string) $tenant->slug : null;

  $currentPage = is_string(request()->query('screen')) && request()->query('screen') !== ''
    ? (string) request()->query('screen')
    : 'dashboard';

  $userRole = is_string($user?->role) && $user?->role !== '' ? (string) $user->role : 'guest';
  $userRoles = [$userRole];

  if ($user && ($user->is_admin ?? false)) {
    $userRoles[] = 'administrator';
  }

  // Treat non-customer tenant users as staff for nav access.
  if ($userRole !== 'customer' && $userRole !== 'guest') {
    $userRoles[] = 'store_manager';
    $userRoles[] = 'technician';
  }

  $userRoles = array_values(array_unique(array_filter($userRoles)));

  $baseUrl = $tenantSlug ? route('tenant.dashboard', ['business' => $tenantSlug]) : '#';
  $screenUrl = function (string $screen) use ($baseUrl): string {
    if ($baseUrl === '#') return '#';
    return $baseUrl.'?screen='.$screen;
  };

  $settingsUrl = $tenantSlug ? route('tenant.settings', ['business' => $tenantSlug]) : '#';
  $settingsSectionUrl = function (string $section) use ($tenantSlug): string {
    if (! $tenantSlug) return '#';
    return route('tenant.settings.section', ['business' => $tenantSlug, 'section' => $section]);
  };

  $operationsBrandsUrl = $tenantSlug ? route('tenant.operations.brands.index', ['business' => $tenantSlug]) : '#';
  $operationsBrandTypesUrl = $tenantSlug ? route('tenant.operations.brand_types.index', ['business' => $tenantSlug]) : '#';
  $operationsDevicesUrl = $tenantSlug ? route('tenant.operations.devices.index', ['business' => $tenantSlug]) : '#';

  $navItems = [
    [
      'id' => 'dashboard',
      'title' => 'Dashboard',
      'icon' => 'bi bi-speedometer2',
      'url' => $screenUrl('dashboard'),
      'access' => ['all'],
    ],
    [
      'id' => 'calendar',
      'title' => 'Calendar',
      'icon' => 'bi bi-bag-check',
      'url' => $screenUrl('calendar'),
      'access' => ['administrator', 'store_manager', 'technician'],
    ],
    [
      'id' => 'jobs',
      'title' => 'Jobs',
      'icon' => 'bi bi-wrench',
      'url' => $screenUrl('jobs'),
      'access' => ['all'],
    ],
    [
      'id' => 'timelog',
      'title' => 'Time Log',
      'icon' => 'bi bi-stopwatch',
      'url' => $screenUrl('timelog'),
      'access' => ['administrator', 'store_manager', 'technician'],
    ],
    [
      'id' => 'estimates',
      'title' => 'Estimates',
      'icon' => 'bi bi-file-earmark-text',
      'url' => $screenUrl('estimates'),
      'access' => ['all'],
    ],
    [
      'id' => 'my-devices',
      'title' => 'My Devices',
      'icon' => 'bi bi-phone',
      'url' => $screenUrl('customer-devices'),
      'access' => ['customer'],
    ],
    [
      'id' => 'customer-devices',
      'title' => 'Customer Devices',
      'icon' => 'bi bi-phone',
      'url' => $screenUrl('customer-devices'),
      'access' => ['administrator', 'store_manager', 'technician'],
    ],
    [
      'id' => 'reviews',
      'title' => 'Reviews',
      'icon' => 'bi bi-star',
      'url' => $screenUrl('reviews'),
      'access' => ['all'],
    ],
    [
      'id' => 'book-my-device',
      'title' => 'Book My Device',
      'icon' => 'bi bi-calendar-plus',
      'url' => $screenUrl('book-my-device'),
      'access' => ['customer'],
    ],
    [
      'id' => 'expenses_parent',
      'title' => 'Expenses',
      'icon' => 'bi bi-calculator',
      'url' => '#',
      'access' => ['administrator', 'store_manager'],
      'extra_class' => 'mt-3',
    ],
    [
      'id' => 'expenses',
      'title' => 'Expenses',
      'parent' => 'expenses_parent',
      'icon' => 'bi bi-calculator',
      'url' => $screenUrl('expenses'),
      'access' => ['administrator', 'store_manager'],
    ],
    [
      'id' => 'expense_categories',
      'title' => 'Expense Categories',
      'parent' => 'expenses_parent',
      'icon' => 'bi bi-tags',
      'url' => $screenUrl('expense_categories'),
      'access' => ['administrator', 'store_manager'],
    ],

    [
      'id' => 'operations',
      'title' => 'Operations',
      'icon' => 'bi bi-boxes',
      'url' => '#',
      'access' => ['administrator', 'store_manager', 'technician'],
      'extra_class' => 'mt-3',
    ],
    [
      'id' => 'operations_brands',
      'title' => 'Brands',
      'parent' => 'operations',
      'icon' => 'bi bi-bookmark-star',
      'url' => $operationsBrandsUrl,
      'access' => ['administrator', 'store_manager', 'technician'],
    ],
    [
      'id' => 'operations_brand_types',
      'title' => 'Device Types',
      'parent' => 'operations',
      'icon' => 'bi bi-diagram-2',
      'url' => $operationsBrandTypesUrl,
      'access' => ['administrator', 'store_manager', 'technician'],
    ],
    [
      'id' => 'operations_devices',
      'title' => 'Devices',
      'parent' => 'operations',
      'icon' => 'bi bi-phone',
      'url' => $operationsDevicesUrl,
      'access' => ['administrator', 'store_manager', 'technician'],
    ],
    [
      'id' => 'settings',
      'title' => 'Settings',
      'icon' => 'bi bi-gear',
      'url' => $settingsUrl,
      'access' => ['all'],
    ],
    [
      'id' => 'settings_overview',
      'title' => 'All Settings',
      'parent' => 'settings',
      'icon' => 'bi bi-grid',
      'url' => $settingsUrl,
      'access' => ['all'],
    ],
    [
      'id' => 'settings_services',
      'title' => 'Service Settings',
      'parent' => 'settings',
      'icon' => 'bi bi-tools',
      'url' => $settingsSectionUrl('services'),
      'access' => ['administrator', 'store_manager', 'technician'],
    ],
    [
      'id' => 'settings_taxes',
      'title' => 'Manage Taxes',
      'parent' => 'settings',
      'icon' => 'bi bi-percent',
      'url' => $settingsSectionUrl('taxes'),
      'access' => ['administrator', 'store_manager'],
    ],
    [
      'id' => 'settings_bookings',
      'title' => 'Booking Settings',
      'parent' => 'settings',
      'icon' => 'bi bi-calendar-check',
      'url' => $settingsSectionUrl('bookings'),
      'access' => ['administrator', 'store_manager', 'technician'],
    ],
    [
      'id' => 'settings_reviews',
      'title' => 'Job Reviews',
      'parent' => 'settings',
      'icon' => 'bi bi-star',
      'url' => $settingsSectionUrl('reviews'),
      'access' => ['administrator', 'store_manager'],
    ],
    [
      'id' => 'settings_styling',
      'title' => 'Styling & Labels',
      'parent' => 'settings',
      'icon' => 'bi bi-palette',
      'url' => $settingsSectionUrl('styling'),
      'access' => ['administrator', 'store_manager'],
    ],
    [
      'id' => 'settings_estimates',
      'title' => 'Estimates',
      'parent' => 'settings',
      'icon' => 'bi bi-file-earmark-text',
      'url' => $settingsSectionUrl('estimates'),
      'access' => ['administrator', 'store_manager', 'technician'],
    ],
    [
      'id' => 'settings_timelog',
      'title' => 'Time Log Settings',
      'parent' => 'settings',
      'icon' => 'bi bi-stopwatch',
      'url' => $settingsSectionUrl('timelog'),
      'access' => ['administrator', 'store_manager', 'technician'],
    ],
    [
      'id' => 'settings_account',
      'title' => 'My Account Settings',
      'parent' => 'settings',
      'icon' => 'bi bi-person-gear',
      'url' => $settingsSectionUrl('account'),
      'access' => ['administrator', 'store_manager'],
    ],
    [
      'id' => 'settings_signature',
      'title' => 'Signature Workflow',
      'parent' => 'settings',
      'icon' => 'bi bi-pen',
      'url' => $settingsSectionUrl('signature'),
      'access' => ['administrator', 'store_manager'],
    ],
    [
      'id' => 'settings_maintenance_reminders',
      'title' => 'Maintenance Reminders',
      'parent' => 'settings',
      'icon' => 'bi bi-bell',
      'url' => $settingsSectionUrl('maintenance-reminders'),
      'access' => ['administrator', 'store_manager'],
    ],
    [
      'id' => 'profile',
      'title' => 'Profile',
      'icon' => 'bi bi-person-circle',
      'url' => $tenantSlug ? route('tenant.profile.edit', ['business' => $tenantSlug]) : '#',
      'access' => ['all'],
      'extra_class' => 'mt-3',
    ],
    [
      'id' => 'support',
      'title' => 'Support',
      'icon' => 'bi bi-life-preserver',
      'url' => 'https://www.webfulcreations.com/repairbuddy-wordpress-plugin/contact/',
      'access' => ['administrator', 'store_manager'],
      'target' => '_blank',
    ],
  ];

  $parentItems = [];
  $childItems = [];
  foreach ($navItems as $item) {
    if (isset($item['parent'])) {
      $childItems[$item['parent']][] = $item;
    } else {
      $parentItems[$item['id']] = $item;
    }
  }

  $canAccess = function (array $access) use ($userRoles): bool {
    if (in_array('all', $access, true)) return true;
    return count(array_intersect($userRoles, $access)) > 0;
  };

  if (request()->routeIs('tenant.settings')) {
    $currentPage = 'settings';
  } elseif (request()->routeIs('tenant.settings.section')) {
    $currentPage = 'settings';
  } elseif (request()->routeIs('tenant.operations.brands.*')) {
    $currentPage = 'operations_brands';
  } elseif (request()->routeIs('tenant.operations.brand_types.*')) {
    $currentPage = 'operations_brand_types';
  } elseif (request()->routeIs('tenant.operations.devices.*')) {
    $currentPage = 'operations_devices';
  } elseif (request()->routeIs('tenant.operations.*')) {
    $currentPage = 'operations';
  } elseif (request()->routeIs('tenant.profile.*')) {
    $currentPage = 'profile';
  }
@endphp

<!-- Sidebar -->
<nav class="sidebar bg-dark text-white" id="sidebar">
  <div class="bg-grey sidebar-header p-2 border-bottom border-secondary">
    <h1 class="site-title">{{ $brandText }}</h1>
  </div>

  <div class="sidebar-nav p-3">
    <ul class="nav nav-pills flex-column wcrb-sidebar-nav">
      @foreach ($parentItems as $item)
        @if ($canAccess($item['access']))
          @php
            $hasChildren = array_key_exists($item['id'], $childItems);
            $isActive = ($currentPage === $item['id']) ? 'active text-white' : 'text-white-50';
            $hasActiveChild = false;
            if ($hasChildren) {
              foreach ($childItems[$item['id']] as $child) {
                if ($currentPage === $child['id']) {
                  $hasActiveChild = true;
                  break;
                }
              }
            }
            $itemIsActive = ($isActive === 'active text-white') || $hasActiveChild;
          @endphp

          <li class="wcrb-nav-item nav-item {{ isset($item['extra_class']) ? $item['extra_class'] : '' }}">
            @if ($hasChildren)
              <div class="wcrb-nav-parent">
                @php
                  $parentClass = $itemIsActive ? 'active text-white' : 'text-white-50';
                  $chevronClass = $itemIsActive ? 'bi bi-chevron-down' : 'bi bi-chevron-right';
                  $collapseClass = $itemIsActive ? 'show' : '';
                @endphp
                <a href="{{ $item['url'] }}" class="wcrb-nav-link nav-link d-flex justify-content-between align-items-center {{ $parentClass }}" data-bs-toggle="collapse" data-bs-target="#wcrb-submenu-{{ $item['id'] }}" {{ isset($item['target']) ? ' target='.$item['target'] : '' }}>
                  <span>
                    <i class="{{ $item['icon'] }} me-2"></i>
                    <span>{{ $item['title'] }}</span>
                  </span>
                  <i class="{{ $chevronClass }} ms-2 wcrb-chevron"></i>
                </a>
              </div>

              <div id="wcrb-submenu-{{ $item['id'] }}" class="collapse {{ $collapseClass }}">
                <ul class="nav flex-column ms-3">
                  @foreach ($childItems[$item['id']] as $child)
                    @if ($canAccess($child['access']))
                      @php
                        $childIsActive = ($currentPage === $child['id']) ? 'active text-white' : 'text-white-50';
                      @endphp
                      <li class="wcrb-nav-item nav-item">
                        <a href="{{ $child['url'] }}" class="wcrb-nav-link nav-link {{ $childIsActive }}" {{ isset($child['target']) ? ' target='.$child['target'] : '' }}>
                          <i class="{{ $child['icon'] }} me-2"></i>
                          <span>{{ $child['title'] }}</span>
                        </a>
                      </li>
                    @endif
                  @endforeach
                </ul>
              </div>
            @else
              <a href="{{ $item['url'] }}" class="wcrb-nav-link nav-link {{ $isActive }}" {{ isset($item['target']) ? ' target='.$item['target'] : '' }}>
                <i class="{{ $item['icon'] }} me-2"></i>
                <span>{{ $item['title'] }}</span>
              </a>
            @endif
          </li>
        @endif
      @endforeach
    </ul>
  </div>
</nav>
