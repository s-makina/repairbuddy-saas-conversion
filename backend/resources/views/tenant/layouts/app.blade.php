@php
  $tenant = $tenant ?? null;
  $user = $user ?? null;
  $activeNav = $activeNav ?? null;
  $title = $title ?? ($tenant?->name ?? 'RepairBuddy');
@endphp

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title>{{ $title }}</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />

    <style>
      :root {
        --rb-blue: #063e70;
        --rb-orange: #fd6742;
      }

      .rb-sidebar {
        width: 280px;
        min-height: 100vh;
        background: var(--rb-blue);
      }

      .rb-sidebar .nav-link {
        color: rgba(255, 255, 255, 0.9);
      }

      .rb-sidebar .nav-link:hover {
        color: #fff;
        background: rgba(253, 103, 66, 0.7);
      }

      .rb-sidebar .nav-link.active {
        color: #fff;
        background: var(--rb-orange);
      }

      .rb-sidebar-header {
        background: #fff;
      }

      body {
        background: #f8f9fa;
      }
    </style>
  </head>
  <body>
    <div class="d-flex">
      @include('tenant.partials.sidebar', [
        'tenant' => $tenant,
        'user' => $user,
        'activeNav' => $activeNav,
      ])

      <main class="flex-grow-1">
        <div class="container-fluid py-4">
          <div class="container-lg">
            @yield('content')
          </div>
        </div>
      </main>
    </div>
  </body>
</html>
