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

  $isAuthed = (bool) $user;
  $isCustomer = $isAuthed && ((string) ($user?->role ?? '') === 'customer');

  $canUsersManage = $isAuthed && $user?->can('users.manage');
  $canRolesManage = $isAuthed && $user?->can('roles.manage');
  $canBranchesManage = $isAuthed && $user?->can('branches.manage');
  $canSettingsManage = $isAuthed && $user?->can('settings.manage');
  $canTechniciansView = $isAuthed && ($user?->can('technicians.view') ?? false);
  $canManagersView = $isAuthed && ($user?->can('managers.view') ?? false);
  $canHourlyRatesView = $isAuthed && ($user?->can('hourly_rates.view') ?? false);

  $canOps = $isAuthed && (
    $user?->can('devices.manage')
    || $user?->can('device_brands.manage')
    || $user?->can('device_types.manage')
    || $user?->can('parts.manage')
    || $user?->can('service_types.manage')
    || $user?->can('services.manage')
    || $user?->can('clients.view')
  );

  $baseUrl = $tenantSlug ? route('tenant.dashboard', ['business' => $tenantSlug]) : '#';
  $screenUrl = function (string $screen) use ($baseUrl): string {
    if ($baseUrl === '#') return '#';
    return $baseUrl.'?screen='.$screen;
  };

  $settingsUrl = $tenantSlug ? route('tenant.settings.v2', ['business' => $tenantSlug]) : '#';
  $settingsSectionUrl = function (string $section) use ($tenantSlug): string {
    if (! $tenantSlug) return '#';
    return route('tenant.settings.section', ['business' => $tenantSlug, 'section' => $section]);
  };

  $operationsBrandsUrl = $tenantSlug ? route('tenant.operations.brands.index', ['business' => $tenantSlug]) : '#';
  $operationsBrandTypesUrl = $tenantSlug ? route('tenant.operations.brand_types.index', ['business' => $tenantSlug]) : '#';
  $operationsDevicesUrl = $tenantSlug ? route('tenant.operations.devices.index', ['business' => $tenantSlug]) : '#';
  $operationsPartsUrl = $tenantSlug ? route('tenant.operations.parts.index', ['business' => $tenantSlug]) : '#';
  $operationsPartBrandsUrl = $tenantSlug ? route('tenant.operations.part_brands.index', ['business' => $tenantSlug]) : '#';
  $operationsPartTypesUrl = $tenantSlug ? route('tenant.operations.part_types.index', ['business' => $tenantSlug]) : '#';
  $operationsServiceTypesUrl = $tenantSlug ? route('tenant.operations.service_types.index', ['business' => $tenantSlug]) : '#';
  $operationsServicesUrl = $tenantSlug ? route('tenant.operations.services.index', ['business' => $tenantSlug]) : '#';
  $operationsClientsUrl = $tenantSlug ? route('tenant.operations.clients.index', ['business' => $tenantSlug]) : '#';

  $techniciansUrl = $tenantSlug ? route('tenant.technicians.index', ['business' => $tenantSlug]) : '#';
  $managersUrl = $tenantSlug ? route('tenant.managers.index', ['business' => $tenantSlug]) : '#';

  $usersUrl = $tenantSlug ? route('tenant.settings.users.index', ['business' => $tenantSlug]) : '#';
  $rolesUrl = $tenantSlug ? route('tenant.settings.roles.index', ['business' => $tenantSlug]) : '#';
  $permissionsUrl = $tenantSlug ? route('tenant.settings.permissions.index', ['business' => $tenantSlug]) : '#';
  $hourlyRatesUrl = $tenantSlug ? route('tenant.settings.hourly_rates.index', ['business' => $tenantSlug]) : '#';
  $shopsUrl = $tenantSlug ? route('tenant.settings.shops.index', ['business' => $tenantSlug]) : '#';

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
      'visible' => $isAuthed && ! $isCustomer,
    ],
    [
      'id' => 'jobs',
      'title' => 'Jobs',
      'icon' => 'bi bi-wrench',
      'url' => $screenUrl('jobs'),
      'access' => ['all'],
    ],

    [
      'id' => 'technicians',
      'title' => 'Technicians',
      'icon' => 'bi bi-person-workspace',
      'url' => $techniciansUrl,
      'visible' => $canTechniciansView,
    ],
    [
      'id' => 'managers',
      'title' => 'Managers',
      'icon' => 'bi bi-person-badge',
      'url' => $managersUrl,
      'visible' => $canManagersView,
    ],
    [
      'id' => 'timelog',
      'title' => 'Time Log',
      'icon' => 'bi bi-stopwatch',
      'url' => $tenantSlug ? route('tenant.time_log.dashboard', ['business' => $tenantSlug]) : '#',
      'visible' => $isAuthed && ($user?->can('time_logs.view') ?? false),
    ],
    [
      'id' => 'timelogs',
      'title' => 'Time Logs',
      'icon' => 'bi bi-clock-history',
      'url' => $tenantSlug ? route('tenant.time_logs.index', ['business' => $tenantSlug]) : '#',
      'visible' => $isAuthed && in_array((string) ($user?->role ?? ''), ['administrator', 'store_manager']),
    ],
    [
      'id' => 'estimates',
      'title' => 'Estimates',
      'icon' => 'bi bi-file-earmark-text',
      'url' => $tenantSlug ? route('tenant.estimates.index', ['business' => $tenantSlug]) : '#',
      'access' => ['all'],
    ],
    [
      'id' => 'appointments',
      'title' => 'Appointments',
      'icon' => 'bi bi-calendar-check',
      'url' => $tenantSlug ? route('tenant.appointments.index', ['business' => $tenantSlug]) : '#',
      'access' => ['all'],
    ],
    [
      'id' => 'my-devices',
      'title' => 'My Devices',
      'icon' => 'bi bi-phone',
      'url' => $screenUrl('customer-devices'),
      'visible' => $isCustomer,
    ],
    [
      'id' => 'customer-devices',
      'title' => 'Customer Devices',
      'icon' => 'bi bi-phone',
      'url' => $screenUrl('customer-devices'),
      'visible' => $isAuthed && ($user?->can('customer_devices.view') ?? false),
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
      'visible' => $isCustomer,
    ],
    [
      'id' => 'expenses_parent',
      'title' => 'Expenses',
      'icon' => 'bi bi-calculator',
      'url' => '#',
      'visible' => $isAuthed && ($user?->can('expenses.view') ?? false),
      'extra_class' => 'mt-3',
    ],
    [
      'id' => 'expenses',
      'title' => 'Expenses',
      'parent' => 'expenses_parent',
      'icon' => 'bi bi-calculator',
      'url' => $screenUrl('expenses'),
      'visible' => $isAuthed && ($user?->can('expenses.view') ?? false),
    ],
    [
      'id' => 'expense_categories',
      'title' => 'Expense Categories',
      'parent' => 'expenses_parent',
      'icon' => 'bi bi-tags',
      'url' => $screenUrl('expense_categories'),
      'visible' => $isAuthed && ($user?->can('expense_categories.view') ?? false),
    ],

    [
      'id' => 'operations',
      'title' => 'Operations',
      'icon' => 'bi bi-boxes',
      'url' => '#',
      'visible' => $canOps,
      'extra_class' => 'mt-3',
    ],
    [
      'id' => 'operations_brands',
      'title' => 'Brands',
      'parent' => 'operations',
      'icon' => 'bi bi-bookmark-star',
      'url' => $operationsBrandsUrl,
      'visible' => $isAuthed && ($user?->can('device_brands.view') ?? false),
    ],
    [
      'id' => 'operations_brand_types',
      'title' => 'Device Types',
      'parent' => 'operations',
      'icon' => 'bi bi-diagram-2',
      'url' => $operationsBrandTypesUrl,
      'visible' => $isAuthed && ($user?->can('device_types.view') ?? false),
    ],
    [
      'id' => 'operations_devices',
      'title' => 'Devices',
      'parent' => 'operations',
      'icon' => 'bi bi-phone',
      'url' => $operationsDevicesUrl,
      'visible' => $isAuthed && ($user?->can('devices.view') ?? false),
    ],
    [
      'id' => 'operations_parts',
      'title' => 'Parts',
      'parent' => 'operations',
      'icon' => 'bi bi-box-seam',
      'url' => $operationsPartsUrl,
      'visible' => $isAuthed && ($user?->can('parts.view') ?? false),
    ],
    [
      'id' => 'operations_part_brands',
      'title' => 'Part Brands',
      'parent' => 'operations',
      'icon' => 'bi bi-bookmark-star',
      'url' => $operationsPartBrandsUrl,
      'visible' => $isAuthed && ($user?->can('parts.view') ?? false),
    ],
    [
      'id' => 'operations_part_types',
      'title' => 'Part Types',
      'parent' => 'operations',
      'icon' => 'bi bi-diagram-2',
      'url' => $operationsPartTypesUrl,
      'visible' => $isAuthed && ($user?->can('parts.view') ?? false),
    ],
    [
      'id' => 'operations_service_types',
      'title' => 'Service Types',
      'parent' => 'operations',
      'icon' => 'bi bi-diagram-2',
      'url' => $operationsServiceTypesUrl,
      'visible' => $isAuthed && ($user?->can('service_types.view') ?? false),
    ],
    [
      'id' => 'operations_services',
      'title' => 'Services',
      'parent' => 'operations',
      'icon' => 'bi bi-tools',
      'url' => $operationsServicesUrl,
      'visible' => $isAuthed && ($user?->can('services.view') ?? false),
    ],
    [
      'id' => 'operations_clients',
      'title' => 'Clients',
      'parent' => 'operations',
      'icon' => 'bi bi-people',
      'url' => $operationsClientsUrl,
      'visible' => $isAuthed && ($user?->can('clients.view') ?? false),
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
      'id' => 'settings_users',
      'title' => 'Users',
      'parent' => 'settings',
      'icon' => 'bi bi-people',
      'url' => $usersUrl,
      'visible' => $canUsersManage,
    ],
    [
      'id' => 'settings_roles',
      'title' => 'Roles',
      'parent' => 'settings',
      'icon' => 'bi bi-shield-lock',
      'url' => $rolesUrl,
      'visible' => $canRolesManage,
    ],
    [
      'id' => 'settings_permissions',
      'title' => 'Permissions',
      'parent' => 'settings',
      'icon' => 'bi bi-key',
      'url' => $permissionsUrl,
      'visible' => $canRolesManage,
    ],
    [
      'id' => 'settings_shops',
      'title' => 'Shops',
      'parent' => 'settings',
      'icon' => 'bi bi-shop',
      'url' => $shopsUrl,
      'visible' => $canBranchesManage,
    ],
    [
      'id' => 'settings_hourly_rates',
      'title' => 'Manage Hourly Rates',
      'parent' => 'settings',
      'icon' => 'bi bi-cash-coin',
      'url' => $hourlyRatesUrl,
      'visible' => $canHourlyRatesView,
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
      'visible' => $isAuthed && ! $isCustomer,
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

  $isVisible = function (array $item): bool {
    if (array_key_exists('visible', $item)) {
      return (bool) $item['visible'];
    }

    return true;
  };

  if (request()->routeIs('tenant.settings')) {
    $currentPage = 'settings';
  } elseif (request()->routeIs('tenant.settings.section')) {
    $currentPage = 'settings';
  } elseif (request()->routeIs('tenant.settings.shops.*')) {
    $currentPage = 'settings_shops';
  } elseif (request()->routeIs('tenant.settings.hourly_rates.*')) {
    $currentPage = 'settings_hourly_rates';
  } elseif (request()->routeIs('tenant.technicians.*')) {
    $currentPage = 'technicians';
  } elseif (request()->routeIs('tenant.operations.brands.*')) {
    $currentPage = 'operations_brands';
  } elseif (request()->routeIs('tenant.operations.brand_types.*')) {
    $currentPage = 'operations_brand_types';
  } elseif (request()->routeIs('tenant.operations.devices.*')) {
    $currentPage = 'operations_devices';
  } elseif (request()->routeIs('tenant.operations.parts.*')) {
    $currentPage = 'operations_parts';
  } elseif (request()->routeIs('tenant.operations.part_brands.*')) {
    $currentPage = 'operations_part_brands';
  } elseif (request()->routeIs('tenant.operations.part_types.*')) {
    $currentPage = 'operations_part_types';
  } elseif (request()->routeIs('tenant.operations.clients.*')) {
    $currentPage = 'operations_clients';
  } elseif (request()->routeIs('tenant.operations.service_types.*')) {
    $currentPage = 'operations_service_types';
  } elseif (request()->routeIs('tenant.operations.services.*')) {
    $currentPage = 'operations_services';
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
        @if ($isVisible($item))
          @php
            $hasChildren = array_key_exists($item['id'], $childItems);
            $isActive = ($currentPage === $item['id']) ? 'active text-white' : 'text-white-50';
            $hasActiveChild = false;
            if ($hasChildren) {
              foreach ($childItems[$item['id']] as $child) {
                if (! $isVisible($child)) {
                  continue;
                }
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
                    @if ($isVisible($child))
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
