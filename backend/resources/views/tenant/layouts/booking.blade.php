@php
  /** @var \App\Models\Tenant|null $tenant */
  $siteName = ($tenant && $tenant->name) ? $tenant->name : config('app.name', 'RepairBuddy');
  $thePageTitle = 'Book a Repair - ' . $siteName;
@endphp
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $thePageTitle }}</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="{{ asset('repairbuddy/my_account/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('repairbuddy/my_account/css/bootstrap-icons.min.css') }}">
  <link rel="stylesheet" href="{{ asset('css/booking.css') }}">

  @livewireStyles

  @stack('page-styles')
</head>
<body class="rb-booking-body">

  {{-- Header --}}
  <header class="rb-booking-header">
    <div class="container">
      <div class="rb-booking-header-inner">
        <div class="rb-booking-brand">
          @if($tenant && $tenant->logo_url)
            <img src="{{ $tenant->logo_url }}" alt="{{ $siteName }}" class="rb-booking-logo">
          @endif
          <span class="rb-booking-brand-name">{{ $siteName }}</span>
        </div>
      </div>
    </div>
  </header>

  {{-- Main Content --}}
  <main class="rb-booking-main">
    <div class="container">
      @yield('content')
    </div>
  </main>

  {{-- Footer --}}
  <footer class="rb-booking-footer">
    <div class="container">
      <p class="mb-0">&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
    </div>
  </footer>

  <script src="{{ asset('repairbuddy/my_account/js/bootstrap.bundle.min.js') }}"></script>

  @livewireScripts

  @stack('page-scripts')

</body>
</html>
