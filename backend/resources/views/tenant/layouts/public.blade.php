@php
  /** @var \App\Models\Tenant|null $tenant */
  $siteName   = ($tenant && $tenant->name) ? $tenant->name : config('app.name', 'RepairBuddy');
  $business   = $business ?? ($tenant->slug ?? '');
  $customTitle = trim($__env->yieldContent('title'));
  $thePageTitle = $customTitle !== '' ? $customTitle : ($siteName . ' — RepairBuddy');

  $navItems = [
      ['label' => 'Book Device',     'icon' => 'bi-phone',          'route' => 'tenant.booking.show'],
      ['label' => 'Job Status',      'icon' => 'bi-activity',       'route' => 'tenant.status.show'],
      ['label' => 'My Account',      'icon' => 'bi-person-circle',  'route' => 'tenant.myaccount'],
      ['label' => 'Our Services',    'icon' => 'bi-tools',          'route' => 'tenant.services'],
      ['label' => 'Parts',           'icon' => 'bi-box-seam',       'route' => 'tenant.parts'],
      ['label' => 'Review Your Job', 'icon' => 'bi-file-earmark-check', 'route' => 'tenant.review'],
  ];

  $currentRoute = \Illuminate\Support\Facades\Route::currentRouteName();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $thePageTitle }}</title>

  {{-- Fonts --}}
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  {{-- Bootstrap (already used elsewhere) --}}
  <link rel="stylesheet" href="{{ asset('repairbuddy/my_account/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('repairbuddy/my_account/css/bootstrap-icons.min.css') }}">

  {{-- Public nav styles --}}
  <link rel="stylesheet" href="{{ asset('css/public-nav.css') }}">
  <link rel="stylesheet" href="{{ asset('css/public-pages.css') }}">

  @livewireStyles

  @stack('page-styles')
</head>
<body class="rpn-page">

  {{-- ═══ Header ═══ --}}
  <header class="rpn-header">
    <div class="rpn-header-inner">
      {{-- Brand --}}
      <a href="{{ route('tenant.booking.show', ['business' => $business]) }}" class="rpn-brand">
        @if($tenant && $tenant->logo_url)
          <img src="{{ $tenant->logo_url }}" alt="{{ $siteName }}" class="rpn-brand-logo">
        @else
          <span class="rpn-brand-icon">RB</span>
        @endif
        <span class="rpn-brand-name">{{ $siteName }}</span>
      </a>

      {{-- Desktop nav --}}
      <ul class="rpn-nav-desktop">
        @foreach($navItems as $nav)
          <li>
            <a
              href="{{ route($nav['route'], ['business' => $business]) }}"
              class="rpn-nav-link {{ $currentRoute === $nav['route'] ? 'active' : '' }}"
            >
              <i class="bi {{ $nav['icon'] }}"></i>
              {{ $nav['label'] }}
            </a>
          </li>
        @endforeach
      </ul>

      {{-- Mobile toggle --}}
      <button
        type="button"
        class="rpn-mobile-toggle"
        aria-expanded="false"
        aria-label="Toggle navigation"
        onclick="(function(btn){
          var menu = document.getElementById('rpnMobileMenu');
          var open = menu.classList.toggle('show');
          btn.setAttribute('aria-expanded', open);
        })(this)"
      >
        <span class="rpn-hamburger">
          <span></span><span></span><span></span>
        </span>
      </button>
    </div>

    {{-- Mobile menu --}}
    <div id="rpnMobileMenu" class="rpn-mobile-menu">
      <ul class="rpn-mobile-nav">
        @foreach($navItems as $nav)
          <li>
            <a
              href="{{ route($nav['route'], ['business' => $business]) }}"
              class="rpn-mobile-nav-link {{ $currentRoute === $nav['route'] ? 'active' : '' }}"
            >
              <i class="bi {{ $nav['icon'] }}"></i>
              {{ $nav['label'] }}
            </a>
          </li>
        @endforeach
      </ul>
    </div>
  </header>

  {{-- ═══ Main ═══ --}}
  <main class="rpn-main">
    <div class="container">
      @yield('content')
    </div>
  </main>

  {{-- ═══ Footer ═══ --}}
  <footer class="rpn-footer">
    <div class="rpn-footer-inner">
      <div>&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</div>
      <div class="rpn-footer-links">
        @foreach(array_slice($navItems, 0, 4) as $nav)
          <a href="{{ route($nav['route'], ['business' => $business]) }}">{{ $nav['label'] }}</a>
        @endforeach
      </div>
    </div>
  </footer>

  <script src="{{ asset('repairbuddy/my_account/js/bootstrap.bundle.min.js') }}"></script>

  @livewireScripts

  @stack('page-scripts')
</body>
</html>
