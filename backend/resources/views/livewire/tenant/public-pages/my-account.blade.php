@php
  $currentRoute = \Illuminate\Support\Facades\Route::currentRouteName();
  $rp = str_starts_with($currentRoute ?? '', 'tenant.subdomain.') ? 'tenant.subdomain.' : 'tenant.';
@endphp
<div class="pp-section">
  {{-- Flash messages --}}
  @if($successMessage)
    <div class="pp-alert pp-alert-success">
      <i class="bi bi-check-circle-fill"></i> {{ $successMessage }}
    </div>
  @endif
  @if($errorMessage)
    <div class="pp-alert pp-alert-danger">
      <i class="bi bi-exclamation-triangle-fill"></i> {{ $errorMessage }}
    </div>
  @endif

  @if(! $isLoggedIn)
    {{-- ═══════════ GUEST VIEW: Login + Register ═══════════ --}}
    <div class="pp-hero">
      <div class="pp-hero-icon">
        <i class="bi bi-person-circle"></i>
      </div>
      <h1 class="pp-hero-title">Welcome Back</h1>
      <p class="pp-hero-subtitle">Sign in to view your repairs and manage your account.</p>
    </div>

    <div class="pp-login-wrapper">
      <div class="pp-login-card">
        <h2 class="pp-login-card-title">Sign In</h2>
        <p class="pp-login-card-sub">Access your repair history and track open jobs.</p>
        <form wire:submit.prevent="login">
          <div class="pp-form-group">
            <label class="pp-label">Email Address <span class="pp-req">*</span></label>
            <input type="email" wire:model.defer="loginEmail" class="pp-input" placeholder="you@example.com" required>
            @error('loginEmail') <span class="pp-field-error">{{ $message }}</span> @enderror
          </div>
          <div class="pp-form-group">
            <label class="pp-label">Password <span class="pp-req">*</span></label>
            <input type="password" wire:model.defer="loginPassword" class="pp-input" placeholder="Enter your password" required>
            @error('loginPassword') <span class="pp-field-error">{{ $message }}</span> @enderror
          </div>
          <div class="pp-login-actions">
            <button type="submit" class="pp-btn pp-btn-primary pp-btn-full" wire:loading.attr="disabled">
              <span wire:loading.remove wire:target="login">Sign In →</span>
              <span wire:loading wire:target="login"><i class="bi bi-arrow-repeat pp-spin"></i> Signing in…</span>
            </button>
          </div>
          <div class="pp-login-link">
            <a href="{{ url('/login') }}">Forgot your password?</a>
          </div>
          <div class="pp-login-divider">or</div>
          <div class="pp-login-link">Don't have an account? <a href="{{ route($rp . 'register', ['business' => $business]) }}">Create one</a></div>
        </form>
      </div>

      <div class="pp-login-extra">
        <a href="{{ route($rp . 'status.show', ['business' => $business]) }}" class="pp-login-extra-link">
          <i class="bi bi-search"></i> Track a repair without signing in →
        </a>
      </div>
    </div>

  @else
    {{-- ═══════════ AUTHENTICATED VIEW: Dashboard ═══════════ --}}
    <div class="pp-dash">
      <div class="pp-dash-header">
        <div class="pp-dash-user">
          <div class="pp-dash-avatar">
            {{ strtoupper(substr($currentUser->name ?? 'U', 0, 1)) }}{{ strtoupper(substr(explode(' ', $currentUser->name ?? 'U U')[1] ?? '', 0, 1)) }}
          </div>
          <div>
            <div class="pp-dash-name">{{ $currentUser->name ?? 'Customer' }}</div>
            <div class="pp-dash-email">{{ $currentUser->email ?? '' }}</div>
          </div>
        </div>
        <div class="pp-dash-actions">
          <a href="{{ route($rp . 'booking.show', ['business' => $business]) }}" class="pp-btn pp-btn-accent">
            <i class="bi bi-phone"></i> New Booking
          </a>
          <button wire:click="logout" class="pp-btn pp-btn-outline-sm">
            <i class="bi bi-box-arrow-right"></i> Sign Out
          </button>
        </div>
      </div>

      {{-- Stats Grid --}}
      @php
        $openCount = collect($jobs)->whereNull('closed_at')->filter(fn($j) => empty($j['closed_at']))->count();
        $closedCount = collect($jobs)->filter(fn($j) => !empty($j['closed_at']))->count();
      @endphp
      <div class="pp-stats-grid">
        <div class="pp-stat-card">
          <div class="pp-stat-val">{{ $openCount }}</div>
          <div class="pp-stat-label">Active Repairs</div>
        </div>
        <div class="pp-stat-card">
          <div class="pp-stat-val">{{ $closedCount }}</div>
          <div class="pp-stat-label">Completed</div>
        </div>
        <div class="pp-stat-card">
          <div class="pp-stat-val">{{ count($jobs) }}</div>
          <div class="pp-stat-label">Total Jobs</div>
        </div>
      </div>

      {{-- Tab navigation --}}
      <div class="pp-dash-tabs">
        <button wire:click="setTab('jobs')" class="pp-dash-tab {{ $activeTab === 'jobs' ? 'active' : '' }}">
          <i class="bi bi-wrench-adjustable"></i> My Repairs
          @if($openCount > 0) <span class="pp-tab-count">{{ $openCount }}</span> @endif
        </button>
        <button wire:click="setTab('profile')" class="pp-dash-tab {{ $activeTab === 'profile' ? 'active' : '' }}">
          <i class="bi bi-gear"></i> Account Settings
        </button>
      </div>

      {{-- Tab: Repairs --}}
      @if($activeTab === 'jobs')
        <div class="pp-tab-content">
          @php
            $activeJobs = collect($jobs)->filter(fn($j) => empty($j['closed_at']))->values();
            $pastJobs = collect($jobs)->filter(fn($j) => !empty($j['closed_at']))->values();
          @endphp

          @if($activeJobs->isNotEmpty())
            <div class="pp-section-title">
              <i class="bi bi-wrench"></i> Active Repairs <span class="pp-section-badge">{{ $activeJobs->count() }}</span>
            </div>
            <div class="pp-repair-list">
              @foreach($activeJobs as $job)
                <a href="{{ route($rp . 'status.show', ['business' => $business, 'caseNumber' => $job['case_number'] ?? '']) }}" class="pp-repair-card">
                  <div class="pp-repair-info">
                    <div class="pp-repair-case">#{{ $job['case_number'] ?? '—' }}</div>
                    <div class="pp-repair-device">{{ $job['title'] ?? '—' }}</div>
                  </div>
                  <div class="pp-repair-meta">
                    <div class="pp-repair-date"><i class="bi bi-calendar3"></i> {{ $job['opened_at'] ?? '—' }}</div>
                    <span class="pp-status-badge pp-status-{{ \Illuminate\Support\Str::slug($job['status_slug'] ?? 'unknown') }}">
                      {{ ucfirst(str_replace(['-','_'], ' ', $job['status_slug'] ?? 'Unknown')) }}
                    </span>
                    <span class="pp-repair-arrow">→</span>
                  </div>
                </a>
              @endforeach
            </div>
          @endif

          @if($pastJobs->isNotEmpty())
            <div class="pp-section-title" style="margin-top: 2rem;">
              <i class="bi bi-check-circle"></i> Past Repairs
            </div>
            <div class="pp-repair-list">
              @foreach($pastJobs as $job)
                <a href="{{ route($rp . 'status.show', ['business' => $business, 'caseNumber' => $job['case_number'] ?? '']) }}" class="pp-repair-card">
                  <div class="pp-repair-info">
                    <div class="pp-repair-case">#{{ $job['case_number'] ?? '—' }}</div>
                    <div class="pp-repair-device">{{ $job['title'] ?? '—' }}</div>
                  </div>
                  <div class="pp-repair-meta">
                    <div class="pp-repair-date"><i class="bi bi-calendar3"></i> {{ $job['opened_at'] ?? '—' }}</div>
                    <span class="pp-status-badge pp-status-{{ \Illuminate\Support\Str::slug($job['status_slug'] ?? 'completed') }}">
                      {{ ucfirst(str_replace(['-','_'], ' ', $job['status_slug'] ?? 'Completed')) }}
                    </span>
                    <span class="pp-repair-arrow">→</span>
                  </div>
                </a>
              @endforeach
            </div>
          @endif

          @if(count($jobs) === 0)
            <div class="pp-empty">
              <i class="bi bi-briefcase"></i>
              <p>No jobs found for your account yet.</p>
              <a href="{{ route($rp . 'booking.show', ['business' => $business]) }}" class="pp-btn pp-btn-primary" style="margin-top: 1rem;">
                Book a Device
              </a>
            </div>
          @endif
        </div>
      @endif

      {{-- Tab: Account Settings --}}
      @if($activeTab === 'profile')
        <div class="pp-tab-content">
          <div class="pp-settings-grid">
            <div class="pp-settings-card">
              <h4 class="pp-settings-title"><i class="bi bi-person"></i> Personal Information</h4>
              <div class="pp-settings-row">
                <span class="pp-settings-label">Full Name</span>
                <span class="pp-settings-value">{{ $currentUser->name ?? '—' }}</span>
              </div>
              <div class="pp-settings-row">
                <span class="pp-settings-label">Email</span>
                <span class="pp-settings-value">{{ $currentUser->email ?? '—' }}</span>
              </div>
              <div class="pp-settings-row">
                <span class="pp-settings-label">Phone</span>
                <span class="pp-settings-value">{{ $currentUser->phone ?? '—' }}</span>
              </div>
              <div class="pp-settings-row">
                <span class="pp-settings-label">Company</span>
                <span class="pp-settings-value">{{ $currentUser->company ?? '—' }}</span>
              </div>
            </div>
            <div class="pp-settings-card">
              <h4 class="pp-settings-title"><i class="bi bi-geo-alt"></i> Default Address</h4>
              <div class="pp-settings-row">
                <span class="pp-settings-label">Street</span>
                <span class="pp-settings-value">{{ $currentUser->address ?? '—' }}</span>
              </div>
              <div class="pp-settings-row">
                <span class="pp-settings-label">City</span>
                <span class="pp-settings-value">{{ $currentUser->city ?? '—' }}</span>
              </div>
              <div class="pp-settings-row">
                <span class="pp-settings-label">Postal Code</span>
                <span class="pp-settings-value">{{ $currentUser->zip ?? '—' }}</span>
              </div>
              <div class="pp-settings-row">
                <span class="pp-settings-label">Member Since</span>
                <span class="pp-settings-value">{{ $currentUser->created_at?->format('M d, Y') ?? '—' }}</span>
              </div>
            </div>
          </div>
        </div>
      @endif
    </div>
  @endif
</div>
