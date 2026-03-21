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
    <div class="cd-page"
         x-data="{
            activeSection: '{{ $activeSection }}',
            showSection(key) {
                this.activeSection = key;
                @this.setSection(key);
            }
         }"
         x-cloak>

        {{-- ═══ Top Bar ═══ --}}
        <div class="cd-top-bar">
            <div class="cd-top-bar-inner">
                <div class="cd-left">
                    <a href="{{ route($rp . 'welcome', ['business' => $business]) }}"
                       class="cd-back-btn" title="Back to Home">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                        </svg>
                    </a>
                    <div class="cd-title-block">
                        <h1 class="cd-page-title">My Account</h1>
                        <p class="cd-page-subtitle">Manage your repairs and account settings</p>
                    </div>
                </div>
                <div class="cd-top-bar-actions">
                    <a href="{{ route($rp . 'booking.show', ['business' => $business]) }}" class="cd-btn cd-btn-primary cd-btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        New Booking
                    </a>
                    <button wire:click="logout" class="cd-btn cd-btn-outline cd-btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
                        </svg>
                        Sign Out
                    </button>
                </div>
            </div>
        </div>

        <div class="cd-layout">

            {{-- ─── Sidebar ─── --}}
            <aside class="cd-sidebar">
                <div class="cd-sidebar-top">
                    <div class="cd-sidebar-brand">My Account</div>
                </div>
                <ul class="cd-sidebar-items">
                    @foreach ($sections as $sectionKey => $section)
                        <li class="cd-sidebar-item"
                            :class="{ 'active': activeSection === '{{ $sectionKey }}' }"
                            @click="showSection('{{ $sectionKey }}')">
                            @if($sectionKey === 'jobs')
                                <svg class="cd-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.207-.766M11.42 15.17l-2.496 3.03A2.65 2.65 0 016.531 21H5.25a.75.75 0 01-.75-.75v-1.281c0-.597.237-1.17.659-1.591l5.877-5.877M11.42 15.17l-5.877-5.877A2.65 2.65 0 015.25 6.531V5.25A.75.75 0 016 4.5h1.281c.597 0 1.17.237 1.591.659l5.877 5.877"/>
                                </svg>
                            @else
                                <svg class="cd-nav-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.198.275-.34.476-.379A11.408 11.408 0 0112 3.75c2.03 0 3.977.365 5.75 1.03.201.04.386.18.476.379M9.594 3.94L8.485 4.72a2.25 2.25 0 00-.688.657l-.896 1.512a2.25 2.25 0 00-.278 1.056v3.175a2.25 2.25 0 00.278 1.056l.896 1.512c.19.32.427.587.688.657l1.11.78m0 0l1.11.78a2.25 2.25 0 001.544.285l1.77-.363a2.25 2.25 0 001.544-.285l1.11-.78m-6.224 0l-1.11.78a2.25 2.25 0 00-.688.657l-.896 1.512a2.25 2.25 0 00-.278 1.056v3.175a2.25 2.25 0 00.278 1.056l.896 1.512c.19.32.427.587.688.657l1.11.78m6.224 0l1.11-.78a2.25 2.25 0 00.688-.657l.896-1.512a2.25 2.25 0 00.278-1.056v-3.175a2.25 2.25 0 00-.278-1.056l-.896-1.512a2.25 2.25 0 00-.688-.657l-1.11-.78m-6.224 0l-1.11-.78a2.25 2.25 0 01-.688-.657l-.896-1.512a2.25 2.25 0 01-.278-1.056V7.887c0-.376.094-.747.278-1.056l.896-1.512c.19-.32.427-.587.688-.657l1.11-.78m6.224 0l1.11.78c.261.07.498.337.688.657l.896 1.512c.184.309.278.68.278 1.056v3.175c0 .376-.094.747-.278 1.056l-.896 1.512c-.19.32-.427.587-.688.657l-1.11.78"/>
                                </svg>
                            @endif
                            <span>{{ $section['label'] }}</span>
                            @if($sectionKey === 'jobs')
                                @php
                                    $openCount = collect($jobs)->filter(fn($j) => empty($j['closed_at']))->count();
                                @endphp
                                @if($openCount > 0)
                                    <span class="cd-nav-badge">{{ $openCount }}</span>
                                @endif
                            @endif
                        </li>
                    @endforeach
                </ul>
            </aside>

            {{-- ─── Content ─── --}}
            <main class="cd-content">

                {{-- Section: Dashboard --}}
                <div x-show="activeSection === 'dashboard'" wire:init="loadStats">
                    <h2 class="cd-dash-section-title">Dashboard</h2>
                    <p style="font-size:.82rem;color:var(--rb-text-3);margin:-0.75rem 0 1.5rem;">
                        Quick access to your repairs and account settings.
                    </p>

                    {{-- Navigation Cards --}}
                    <div class="cd-dash-nav-grid">
                        <a href="{{ route($rp . 'booking.show', ['business' => $business]) }}" class="cd-dash-nav-card">
                            <div class="cd-dash-nav-card-img">
                                <img src="{{ asset('repairbuddy/plugin/assets/admin/images/icons/services.png') }}" alt="New Booking" loading="lazy">
                            </div>
                            <p class="cd-dash-nav-card-label">New Booking</p>
                        </a>
                        <a href="#jobs" class="cd-dash-nav-card" @click.prevent="showSection('jobs')">
                            <div class="cd-dash-nav-card-img">
                                <img src="{{ asset('repairbuddy/plugin/assets/admin/images/icons/jobs.png') }}" alt="My Repairs" loading="lazy">
                            </div>
                            <p class="cd-dash-nav-card-label">My Repairs</p>
                        </a>
                        <a href="#profile" class="cd-dash-nav-card" @click.prevent="showSection('profile')">
                            <div class="cd-dash-nav-card-img">
                                <img src="{{ asset('repairbuddy/plugin/assets/admin/images/icons/clients.png') }}" alt="Account" loading="lazy">
                            </div>
                            <p class="cd-dash-nav-card-label">Account</p>
                        </a>
                        <a href="{{ route($rp . 'status.show', ['business' => $business]) }}" class="cd-dash-nav-card">
                            <div class="cd-dash-nav-card-img">
                                <img src="{{ asset('repairbuddy/plugin/assets/admin/images/icons/report.png') }}" alt="Track Repair" loading="lazy">
                            </div>
                            <p class="cd-dash-nav-card-label">Track Repair</p>
                        </a>
                    </div>

                    {{-- Jobs by Status --}}
                    <h3 style="font-size:.88rem;font-weight:700;color:var(--rb-text);margin:0 0 .65rem;">Jobs by Status</h3>
                    <div class="cd-widget-grid">
                        @if (! $statsLoaded)
                            <p style="font-size:.82rem;color:var(--rb-text-3);">Loading job status summary…</p>
                        @else
                            @forelse ($jobStatusList as $status)
                                <div class="cd-widget">
                                    <div class="cd-widget-body">
                                        <div class="cd-widget-media">
                                            <div class="cd-widget-icon">
                                                <img src="{{ asset('repairbuddy/plugin/assets/admin/images/icons/jobs.png') }}" alt="" loading="lazy">
                                            </div>
                                            <div class="cd-widget-info">
                                                <div class="cd-widget-title">{{ $status['label'] }}</div>
                                                <div class="cd-widget-number">{{ number_format($status['count']) }} Jobs</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p style="font-size:.82rem;color:var(--rb-text-3);">No job statuses found.</p>
                            @endforelse
                        @endif
                    </div>

                    {{-- Estimates by Status --}}
                    <h3 style="font-size:.88rem;font-weight:700;color:var(--rb-text);margin:1.25rem 0 .65rem;">Estimates by Status</h3>
                    <div class="cd-widget-grid">
                        @php
                            $estimateItems = [
                                ['key' => 'pending',  'label' => 'Pending'],
                                ['key' => 'approved', 'label' => 'Approved'],
                                ['key' => 'rejected', 'label' => 'Rejected'],
                            ];
                            $eCounts = $estimateCountList;
                        @endphp
                        @foreach ($estimateItems as $est)
                            <div class="cd-widget">
                                <div class="cd-widget-body">
                                    <div class="cd-widget-media">
                                        <div class="cd-widget-icon">
                                            <img src="{{ asset('repairbuddy/plugin/assets/admin/images/icons/estimate.png') }}" alt="" loading="lazy">
                                        </div>
                                        <div class="cd-widget-info">
                                            <div class="cd-widget-title">{{ $est['label'] }}</div>
                                            <div class="cd-widget-number">{{ number_format($eCounts[$est['key']] ?? 0) }} Estimates</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Section: My Repairs --}}
                <div x-show="activeSection === 'jobs'">
                    @php
                        $activeJobs = collect($jobs)->filter(fn($j) => empty($j['closed_at']))->values();
                        $pastJobs = collect($jobs)->filter(fn($j) => !empty($j['closed_at']))->values();
                    @endphp

                    @if($activeJobs->isNotEmpty())
                        <div class="cd-section">
                            <div class="cd-section-header">
                                <h3 class="cd-section-title">
                                    <svg class="cd-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.207-.766M11.42 15.17l-2.496 3.03A2.65 2.65 0 016.531 21H5.25a.75.75 0 01-.75-.75v-1.281c0-.597.237-1.17.659-1.591l5.877-5.877M11.42 15.17l-5.877-5.877A2.65 2.65 0 015.25 6.531V5.25A.75.75 0 016 4.5h1.281c.597 0 1.17.237 1.591.659l5.877 5.877"/>
                                    </svg>
                                    Active Repairs
                                </h3>
                                <span class="cd-section-badge">{{ $activeJobs->count() }}</span>
                            </div>
                            <div class="cd-section-body">
                                <div class="cd-repair-list">
                                    @foreach($activeJobs as $job)
                                        <a href="{{ route($rp . 'status.show', ['business' => $business, 'caseNumber' => $job['case_number'] ?? '']) }}" class="cd-repair-card">
                                            <div class="cd-repair-info">
                                                <div class="cd-repair-case">#{{ $job['case_number'] ?? '—' }}</div>
                                                <div class="cd-repair-device">{{ $job['title'] ?? '—' }}</div>
                                            </div>
                                            <div class="cd-repair-meta">
                                                <div class="cd-repair-date">{{ $job['opened_at'] ?? '—' }}</div>
                                                <span class="cd-repair-status {{ \Illuminate\Support\Str::slug($job['status_slug'] ?? 'in-progress') }}">
                                                    {{ ucfirst(str_replace(['-','_'], ' ', $job['status_slug'] ?? 'In Progress')) }}
                                                </span>
                                                <span class="cd-repair-arrow">→</span>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($pastJobs->isNotEmpty())
                        <div class="cd-section">
                            <div class="cd-section-header">
                                <h3 class="cd-section-title">
                                    <svg class="cd-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Past Repairs
                                </h3>
                            </div>
                            <div class="cd-section-body">
                                <div class="cd-repair-list">
                                    @foreach($pastJobs as $job)
                                        <a href="{{ route($rp . 'status.show', ['business' => $business, 'caseNumber' => $job['case_number'] ?? '']) }}" class="cd-repair-card">
                                            <div class="cd-repair-info">
                                                <div class="cd-repair-case">#{{ $job['case_number'] ?? '—' }}</div>
                                                <div class="cd-repair-device">{{ $job['title'] ?? '—' }}</div>
                                            </div>
                                            <div class="cd-repair-meta">
                                                <div class="cd-repair-date">{{ $job['opened_at'] ?? '—' }}</div>
                                                <span class="cd-repair-status completed">
                                                    {{ ucfirst(str_replace(['-','_'], ' ', $job['status_slug'] ?? 'Completed')) }}
                                                </span>
                                                <span class="cd-repair-arrow">→</span>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(count($jobs) === 0)
                        <div class="cd-section">
                            <div class="cd-section-body">
                                <div class="cd-empty">
                                    <svg class="cd-empty-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                                    </svg>
                                    <div class="cd-empty-title">No jobs found</div>
                                    <div class="cd-empty-text">You don't have any repair jobs yet.</div>
                                    <a href="{{ route($rp . 'booking.show', ['business' => $business]) }}" class="cd-btn cd-btn-primary" style="margin-top: 1rem;">
                                        Book a Device
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Section: Account Settings --}}
                <div x-show="activeSection === 'profile'">
                    <div class="cd-settings-grid">
                        <div class="cd-settings-card">
                            <div class="cd-settings-header">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.28-.55-7.499-1.632z"/>
                                </svg>
                                Personal Information
                            </div>
                            <div class="cd-settings-body">
                                <div class="cd-settings-row">
                                    <span class="cd-settings-label">Full Name</span>
                                    <span class="cd-settings-value">{{ $currentUser->name ?? '—' }}</span>
                                </div>
                                <div class="cd-settings-row">
                                    <span class="cd-settings-label">Email</span>
                                    <span class="cd-settings-value">{{ $currentUser->email ?? '—' }}</span>
                                </div>
                                <div class="cd-settings-row">
                                    <span class="cd-settings-label">Phone</span>
                                    <span class="cd-settings-value">{{ $currentUser->phone ?? '—' }}</span>
                                </div>
                                <div class="cd-settings-row">
                                    <span class="cd-settings-label">Company</span>
                                    <span class="cd-settings-value">{{ $currentUser->company ?? '—' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="cd-settings-card">
                            <div class="cd-settings-header">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                                </svg>
                                Default Address
                            </div>
                            <div class="cd-settings-body">
                                <div class="cd-settings-row">
                                    <span class="cd-settings-label">Street</span>
                                    <span class="cd-settings-value">{{ $currentUser->address ?? '—' }}</span>
                                </div>
                                <div class="cd-settings-row">
                                    <span class="cd-settings-label">City</span>
                                    <span class="cd-settings-value">{{ $currentUser->city ?? '—' }}</span>
                                </div>
                                <div class="cd-settings-row">
                                    <span class="cd-settings-label">Postal Code</span>
                                    <span class="cd-settings-value">{{ $currentUser->zip ?? '—' }}</span>
                                </div>
                                <div class="cd-settings-row">
                                    <span class="cd-settings-label">Member Since</span>
                                    <span class="cd-settings-value">{{ $currentUser->created_at?->format('M d, Y') ?? '—' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>
  @endif
</div>
