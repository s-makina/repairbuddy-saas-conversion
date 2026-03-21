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
                        @if(!empty($section['external']))
                            {{-- External link (Book My Device) --}}
                            <li class="cd-sidebar-item">
                                <a href="{{ route($rp . 'booking.show', ['business' => $business]) }}" class="cd-sidebar-link">
                                    <i class="bi {{ $section['icon'] }} cd-nav-icon"></i>
                                    <span>{{ $section['label'] }}</span>
                                </a>
                            </li>
                        @else
                            <li class="cd-sidebar-item"
                                :class="{ 'active': activeSection === '{{ $sectionKey }}' }"
                                @click="showSection('{{ $sectionKey }}')">
                                <i class="bi {{ $section['icon'] }} cd-nav-icon"></i>
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
                        @endif
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

                {{-- Section: Estimates --}}
                <div x-show="activeSection === 'estimates'">
                    <h2 class="cd-dash-section-title">Estimates</h2>
                    <p style="font-size:.82rem;color:var(--rb-text-3);margin:-0.75rem 0 1.5rem;">
                        View and manage your repair estimates.
                    </p>
                    <div class="cd-empty">
                        <i class="bi bi-file-earmark-text" style="font-size:3rem;color:var(--rb-text-3);"></i>
                        <div class="cd-empty-title">No Estimates Yet</div>
                        <div class="cd-empty-text">Your estimates will appear here once created.</div>
                        <a href="{{ route($rp . 'booking.show', ['business' => $business]) }}" class="cd-btn cd-btn-primary" style="margin-top: 1rem;">
                            Request an Estimate
                        </a>
                    </div>
                </div>

                {{-- Section: My Devices --}}
                <div x-show="activeSection === 'my-devices'">
                    <h2 class="cd-dash-section-title">My Devices</h2>
                    <p style="font-size:.82rem;color:var(--rb-text-3);margin:-0.75rem 0 1.5rem;">
                        Your registered devices for repair.
                    </p>
                    <div class="cd-empty">
                        <i class="bi bi-phone" style="font-size:3rem;color:var(--rb-text-3);"></i>
                        <div class="cd-empty-title">No Devices Registered</div>
                        <div class="cd-empty-text">Your registered devices will appear here.</div>
                        <a href="{{ route($rp . 'booking.show', ['business' => $business]) }}" class="cd-btn cd-btn-primary" style="margin-top: 1rem;">
                            Register a Device
                        </a>
                    </div>
                </div>

                {{-- Section: Reviews --}}
                <div x-show="activeSection === 'reviews'">
                    <h2 class="cd-dash-section-title">Reviews</h2>
                    <p style="font-size:.82rem;color:var(--rb-text-3);margin:-0.75rem 0 1.5rem;">
                        Your reviews and feedback on completed repairs.
                    </p>
                    <div class="cd-empty">
                        <i class="bi bi-star" style="font-size:3rem;color:var(--rb-text-3);"></i>
                        <div class="cd-empty-title">No Reviews Yet</div>
                        <div class="cd-empty-text">Your reviews will appear here after completing repairs.</div>
                    </div>
                </div>

                {{-- Section: Profile --}}
                <div x-show="activeSection === 'profile'" wire:init="loadProfileData">
                    <h2 class="cd-dash-section-title">Profile</h2>
                    <p style="font-size:.82rem;color:var(--rb-text-3);margin:-0.75rem 0 1.5rem;">
                        Manage your account information and preferences.
                    </p>

                    <div class="cd-profile-grid">
                        {{-- Left Column: Forms --}}
                        <div class="cd-profile-main">
                            {{-- Personal Information Card --}}
                            <div class="cd-card">
                                <div class="cd-card-header">
                                    <i class="bi bi-person me-2"></i> Personal Information
                                </div>
                                <div class="cd-card-body">
                                    <form wire:submit.prevent="updateProfile">
                                        <div class="cd-form-grid">
                                            <div class="cd-form-group">
                                                <label class="cd-label">First Name <span class="cd-req">*</span></label>
                                                <input type="text" wire:model.defer="profileFirstName" class="cd-input" placeholder="John">
                                                @error('profileFirstName') <span class="cd-field-error">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="cd-form-group">
                                                <label class="cd-label">Last Name</label>
                                                <input type="text" wire:model.defer="profileLastName" class="cd-input" placeholder="Doe">
                                            </div>
                                            <div class="cd-form-group">
                                                <label class="cd-label">Email <span class="cd-req">*</span></label>
                                                <input type="email" wire:model.defer="profileEmail" class="cd-input" placeholder="john@example.com">
                                                @error('profileEmail') <span class="cd-field-error">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="cd-form-group">
                                                <label class="cd-label">Phone</label>
                                                <input type="tel" wire:model.defer="profilePhone" class="cd-input" placeholder="+1 234 567 890">
                                            </div>
                                            <div class="cd-form-group">
                                                <label class="cd-label">Company</label>
                                                <input type="text" wire:model.defer="profileCompany" class="cd-input" placeholder="Company name">
                                            </div>
                                            <div class="cd-form-group">
                                                <label class="cd-label">Tax ID</label>
                                                <input type="text" wire:model.defer="profileTaxId" class="cd-input" placeholder="VAT/Tax number">
                                            </div>
                                            <div class="cd-form-group cd-form-full">
                                                <label class="cd-label">Address</label>
                                                <input type="text" wire:model.defer="profileAddress" class="cd-input" placeholder="Street address">
                                            </div>
                                            <div class="cd-form-group">
                                                <label class="cd-label">City</label>
                                                <input type="text" wire:model.defer="profileCity" class="cd-input" placeholder="City">
                                            </div>
                                            <div class="cd-form-group">
                                                <label class="cd-label">State/Province</label>
                                                <input type="text" wire:model.defer="profileState" class="cd-input" placeholder="State">
                                            </div>
                                            <div class="cd-form-group">
                                                <label class="cd-label">Postal Code</label>
                                                <input type="text" wire:model.defer="profilePostalCode" class="cd-input" placeholder="12345">
                                            </div>
                                            <div class="cd-form-group">
                                                <label class="cd-label">Country</label>
                                                <select wire:model.defer="profileCountry" class="cd-select">
                                                    <option value="">Select country</option>
                                                    <option value="US" {{ $profileCountry === 'US' ? 'selected' : '' }}>United States</option>
                                                    <option value="GB" {{ $profileCountry === 'GB' ? 'selected' : '' }}>United Kingdom</option>
                                                    <option value="CA" {{ $profileCountry === 'CA' ? 'selected' : '' }}>Canada</option>
                                                    <option value="AU" {{ $profileCountry === 'AU' ? 'selected' : '' }}>Australia</option>
                                                    <option value="DE" {{ $profileCountry === 'DE' ? 'selected' : '' }}>Germany</option>
                                                    <option value="FR" {{ $profileCountry === 'FR' ? 'selected' : '' }}>France</option>
                                                    <option value="ES" {{ $profileCountry === 'ES' ? 'selected' : '' }}>Spain</option>
                                                    <option value="IT" {{ $profileCountry === 'IT' ? 'selected' : '' }}>Italy</option>
                                                    <option value="NL" {{ $profileCountry === 'NL' ? 'selected' : '' }}>Netherlands</option>
                                                    <option value="PT" {{ $profileCountry === 'PT' ? 'selected' : '' }}>Portugal</option>
                                                    <option value="BR" {{ $profileCountry === 'BR' ? 'selected' : '' }}>Brazil</option>
                                                    <option value="MX" {{ $profileCountry === 'MX' ? 'selected' : '' }}>Mexico</option>
                                                    <option value="IN" {{ $profileCountry === 'IN' ? 'selected' : '' }}>India</option>
                                                    <option value="JP" {{ $profileCountry === 'JP' ? 'selected' : '' }}>Japan</option>
                                                    <option value="CN" {{ $profileCountry === 'CN' ? 'selected' : '' }}>China</option>
                                                    <option value="OTHER">Other</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="cd-form-actions">
                                            <small class="cd-form-hint">(*) fields are required</small>
                                            <button type="submit" class="cd-btn cd-btn-primary" wire:loading.attr="disabled">
                                                <span wire:loading.remove wire:target="updateProfile">Update Profile</span>
                                                <span wire:loading wire:target="updateProfile"><i class="bi bi-arrow-repeat pp-spin"></i> Saving…</span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- Change Password Card --}}
                            <div class="cd-card mt-4">
                                <div class="cd-card-header">
                                    <i class="bi bi-shield-lock me-2"></i> Change Password
                                </div>
                                <div class="cd-card-body">
                                    <form wire:submit.prevent="updatePassword">
                                        <div class="cd-form-grid">
                                            <div class="cd-form-group cd-form-full">
                                                <label class="cd-label">Current Password <span class="cd-req">*</span></label>
                                                <input type="password" wire:model.defer="currentPassword" class="cd-input" placeholder="Enter current password">
                                                @error('currentPassword') <span class="cd-field-error">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="cd-form-group">
                                                <label class="cd-label">New Password <span class="cd-req">*</span></label>
                                                <input type="password" wire:model.defer="newPassword" class="cd-input" placeholder="Min 8 characters">
                                                @error('newPassword') <span class="cd-field-error">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="cd-form-group">
                                                <label class="cd-label">Confirm Password <span class="cd-req">*</span></label>
                                                <input type="password" wire:model.defer="confirmPassword" class="cd-input" placeholder="Confirm new password">
                                                @error('confirmPassword') <span class="cd-field-error">{{ $message }}</span> @enderror
                                            </div>
                                        </div>
                                        <div class="cd-form-actions">
                                            <button type="submit" class="cd-btn cd-btn-primary" wire:loading.attr="disabled">
                                                <span wire:loading.remove wire:target="updatePassword">Update Password</span>
                                                <span wire:loading wire:target="updatePassword"><i class="bi bi-arrow-repeat pp-spin"></i> Updating…</span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        {{-- Right Column: Sidebar --}}
                        <div class="cd-profile-sidebar">
                            {{-- Profile Picture Card --}}
                            <div class="cd-card">
                                <div class="cd-card-header">Profile Picture</div>
                                <div class="cd-card-body text-center">
                                    <div class="cd-avatar-wrapper">
                                        @if($currentUser->avatar_url)
                                            <img src="{{ $currentUser->avatar_url }}" alt="Avatar" class="cd-avatar">
                                        @else
                                            <div class="cd-avatar cd-avatar-placeholder">
                                                <i class="bi bi-person"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <label class="cd-btn cd-btn-outline cd-btn-sm cd-upload-btn">
                                        <i class="bi bi-upload me-1"></i> Upload Photo
                                        <input type="file" wire:model="profilePhoto" accept="image/jpeg,image/png,image/gif" class="d-none">
                                    </label>
                                    <small class="cd-form-hint d-block mt-2">JPG, PNG or GIF. Max 2MB.</small>
                                    @error('profilePhoto') <span class="cd-field-error">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            {{-- Your Activity Card --}}
                            <div class="cd-card mt-4">
                                <div class="cd-card-header">Your Activity</div>
                                <div class="cd-card-body">
                                    <div class="cd-stat-row">
                                        <span class="cd-stat-label">Total Jobs</span>
                                        <span class="cd-stat-value">{{ number_format($totalJobs) }}</span>
                                    </div>
                                    <div class="cd-stat-row">
                                        <span class="cd-stat-label">Total Estimates</span>
                                        <span class="cd-stat-value">{{ number_format($totalEstimates) }}</span>
                                    </div>
                                    <div class="cd-stat-row">
                                        <span class="cd-stat-label">Lifetime Value</span>
                                        <span class="cd-stat-value">${{ $lifetimeValue }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Account Status Card --}}
                            <div class="cd-card mt-4">
                                <div class="cd-card-header">Account Status</div>
                                <div class="cd-card-body">
                                    <div class="cd-stat-row">
                                        <span class="cd-stat-label">Member Since</span>
                                        <span class="cd-stat-value">{{ $currentUser->created_at?->format('M d, Y') ?? '—' }}</span>
                                    </div>
                                    <div class="cd-stat-row">
                                        <span class="cd-stat-label">Account Type</span>
                                        <span class="cd-badge cd-badge-success">{{ ucfirst($currentUser->role ?? 'Customer') }}</span>
                                    </div>
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
