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
                <div x-show="activeSection === 'jobs'" wire:init="loadJobsData">
                    <h2 class="cd-dash-section-title">My Repairs</h2>
                    <p style="font-size:.82rem;color:var(--rb-text-3);margin:-0.75rem 0 1.5rem;">
                        Track and manage your repair jobs.
                    </p>

                    {{-- Status Cards --}}
                    @if(count($jobsStatusCards) > 0)
                        <div class="cd-status-cards">
                            @foreach($jobsStatusCards as $card)
                                <button type="button" 
                                    class="cd-status-card {{ $jobsStatusFilter === $card['slug'] ? 'active' : '' }}"
                                    wire:click="$set('jobsStatusFilter', '{{ $card['slug'] }}'); applyJobsFilters">
                                    <div class="cd-status-count">{{ $card['count'] }}</div>
                                    <div class="cd-status-name">{{ $card['name'] }}</div>
                                </button>
                            @endforeach
                            @if($jobsStatusFilter)
                                <button type="button" class="cd-status-card cd-status-clear" wire:click="clearJobsFilters">
                                    <i class="bi bi-x-lg"></i> Clear
                                </button>
                            @endif
                        </div>
                    @endif

                    {{-- Filters --}}
                    <div class="cd-filter-bar">
                        <div class="cd-filter-search">
                            <i class="bi bi-search"></i>
                            <input type="text" wire:model.defer="jobsSearch" placeholder="Search by case #, title..." />
                        </div>
                        <select wire:model.defer="jobsPriorityFilter" class="cd-filter-select">
                            <option value="">All Priorities</option>
                            <option value="high">High</option>
                            <option value="normal">Normal</option>
                            <option value="low">Low</option>
                        </select>
                        <button type="button" class="cd-btn cd-btn-outline" wire:click="applyJobsFilters">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                        <button type="button" class="cd-btn cd-btn-ghost" wire:click="clearJobsFilters">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>

                    {{-- Jobs Table --}}
                    @if(count($jobsList) > 0)
                        <div class="cd-card">
                            <div class="cd-card-header">
                                <i class="bi bi-wrench me-2"></i> Jobs
                            </div>
                            <div class="cd-card-body p-0">
                                <div class="cd-data-table">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Job #</th>
                                                <th>Case #</th>
                                                <!-- <th>Title</th> -->
                                                <th>Devices</th>
                                                <th>Opened</th>
                                                <th>Closed</th>
                                                <th>Status</th>
                                                <th>Priority</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($jobsList as $job)
                                                <tr>
                                                    <td><span class="cd-badge cd-badge-secondary">#{{ $job['job_number'] }}</span></td>
                                                    <td>{{ $job['case_number'] }}</td>
                                                    <!-- <td>{{ $job['title'] }}</td> -->
                                                    <td>{{ $job['devices'] }}</td>
                                                    <td>{{ $job['opened_at'] }}</td>
                                                    <td>{{ $job['closed_at'] ?: '-' }}</td>
                                                    <td>
                                                        <span class="cd-status-badge cd-status-{{ \Illuminate\Support\Str::slug($job['status_slug']) }}">
                                                            {{ ucfirst(str_replace(['-','_'], ' ', $job['status_slug'])) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="cd-priority cd-priority-{{ $job['priority'] }}">
                                                            {{ ucfirst($job['priority']) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="{{ route($rp . 'status.show', ['business' => $business, 'caseNumber' => $job['case_number']]) }}" class="cd-btn cd-btn-sm">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="cd-empty">
                            <i class="bi bi-wrench" style="font-size:3rem;color:var(--rb-text-3);"></i>
                            <div class="cd-empty-title">No Jobs Found</div>
                            <div class="cd-empty-text">No repair jobs match your criteria.</div>
                        </div>
                    @endif
                </div>

                {{-- Section: Estimates --}}
                <div x-show="activeSection === 'estimates'" wire:init="loadEstimatesData">
                    <h2 class="cd-dash-section-title">Estimates</h2>
                    <p style="font-size:.82rem;color:var(--rb-text-3);margin:-0.75rem 0 1.5rem;">
                        View and manage your repair estimates.
                    </p>

                    {{-- Status Cards --}}
                    <div class="cd-status-cards">
                        @foreach($estimatesStatusCards as $card)
                            <button type="button" 
                                class="cd-status-card cd-status-{{ $card['color'] }} {{ $estimatesStatusFilter === $card['slug'] ? 'active' : '' }}"
                                wire:click="$set('estimatesStatusFilter', '{{ $card['slug'] }}'); applyEstimatesFilters">
                                <div class="cd-status-count">{{ $card['count'] }}</div>
                                <div class="cd-status-name">{{ $card['name'] }}</div>
                            </button>
                        @endforeach
                        @if($estimatesStatusFilter)
                            <button type="button" class="cd-status-card cd-status-clear" wire:click="clearEstimatesFilters">
                                <i class="bi bi-x-lg"></i> Clear
                            </button>
                        @endif
                    </div>

                    {{-- Filters --}}
                    <div class="cd-filter-bar">
                        <div class="cd-filter-search">
                            <i class="bi bi-search"></i>
                            <input type="text" wire:model.defer="estimatesSearch" placeholder="Search by case #, title..." />
                        </div>
                        <button type="button" class="cd-btn cd-btn-outline" wire:click="applyEstimatesFilters">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                        <button type="button" class="cd-btn cd-btn-ghost" wire:click="clearEstimatesFilters">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>

                    {{-- Estimates Table --}}
                    @if(count($estimatesList) > 0)
                        <div class="cd-card">
                            <div class="cd-card-header">
                                <i class="bi bi-file-earmark-text me-2"></i> Estimates
                            </div>
                            <div class="cd-card-body p-0">
                                <div class="cd-data-table">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Case #</th>
                                                <th>Title</th>
                                                <th>Created</th>
                                                <th>Status</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($estimatesList as $est)
                                                <tr>
                                                    <td>#{{ $est['id'] }}</td>
                                                    <td>{{ $est['case_number'] }}</td>
                                                    <td>{{ $est['title'] }}</td>
                                                    <td>{{ $est['created_at'] }}</td>
                                                    <td>
                                                        <span class="cd-status-badge cd-status-{{ $est['status'] }}">
                                                            {{ ucfirst($est['status']) }}
                                                        </span>
                                                    </td>
                                                    <td>${{ $est['total'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="cd-empty">
                            <i class="bi bi-file-earmark-text" style="font-size:3rem;color:var(--rb-text-3);"></i>
                            <div class="cd-empty-title">No Estimates Found</div>
                            <div class="cd-empty-text">No estimates match your criteria.</div>
                        </div>
                    @endif
                </div>

                {{-- Section: My Devices --}}
                <div x-show="activeSection === 'my-devices'" wire:init="loadDevicesData">
                    <h2 class="cd-dash-section-title">My Devices</h2>
                    <p style="font-size:.82rem;color:var(--rb-text-3);margin:-0.75rem 0 1.5rem;">
                        Your registered devices for repair.
                    </p>

                    {{-- Stats --}}
                    <div class="cd-status-cards">
                        <div class="cd-status-card cd-status-primary">
                            <div class="cd-status-count">{{ $devicesTotal }}</div>
                            <div class="cd-status-name">Total Devices</div>
                        </div>
                    </div>

                    {{-- Filters & Add Button --}}
                    <div class="cd-filter-bar">
                        <div class="cd-filter-search">
                            <i class="bi bi-search"></i>
                            <input type="text" wire:model.defer="devicesSearch" placeholder="Search by name, ID/IMEI..." />
                        </div>
                        <button type="button" class="cd-btn cd-btn-primary" wire:click="openAddDeviceModal">
                            <i class="bi bi-plus-circle"></i> Add Device
                        </button>
                    </div>

                    {{-- Devices Table --}}
                    @if(count($devicesList) > 0)
                        <div class="cd-card">
                            <div class="cd-card-header">
                                <i class="bi bi-phone me-2"></i> My Devices
                            </div>
                            <div class="cd-card-body p-0">
                                <div class="cd-data-table">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Type</th>
                                                <th>Brand</th>
                                                <th>ID/IMEI</th>
                                                <th>Pin Code</th>
                                                <th>Notes</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($devicesList as $dev)
                                                <tr>
                                                    <td>{{ $dev['name'] }}</td>
                                                    <td>{{ $dev['type'] }}</td>
                                                    <td>{{ $dev['brand'] }}</td>
                                                    <td>{{ $dev['identifier'] }}</td>
                                                    <td>{{ $dev['pin_code'] }}</td>
                                                    <td>{{ $dev['notes'] }}</td>
                                                    <td>
                                                        <div class="cd-actions">
                                                            <button type="button" class="cd-btn cd-btn-sm cd-btn-ghost" wire:click="editDevice({{ $dev['id'] }})">
                                                                <i class="bi bi-pencil"></i>
                                                            </button>
                                                            <button type="button" class="cd-btn cd-btn-sm cd-btn-ghost cd-btn-danger" wire:click="deleteDevice({{ $dev['id'] }})" wire:confirm="Are you sure you want to delete this device?">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="cd-empty">
                            <i class="bi bi-phone" style="font-size:3rem;color:var(--rb-text-3);"></i>
                            <div class="cd-empty-title">No Devices Found</div>
                            <div class="cd-empty-text">No devices match your search.</div>
                        </div>
                    @endif
                </div>

                {{-- Add/Edit Device Modal --}}
                @if($showAddDeviceModal)
                    <div class="cd-modal-overlay" wire:click.self="closeAddDeviceModal">
                        <div class="cd-modal">
                            <div class="cd-modal-header">
                                <h3>{{ $editingDeviceId ? 'Edit Device' : 'Add Device' }}</h3>
                                <button type="button" class="cd-modal-close" wire:click="closeAddDeviceModal">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            <form class="cd-modal-body" wire:submit.prevent="saveDevice">
                                <div class="cd-form-group">
                                    <label>Device Name *</label>
                                    <input type="text" wire:model.defer="deviceName" required />
                                </div>
                                <div class="cd-form-row">
                                    <div class="cd-form-group">
                                        <label>Device Type</label>
                                        <select wire:model.defer="deviceTypeId">
                                            <option value="">Select Type</option>
                                            @foreach(\App\Models\RepairBuddyDeviceType::orderBy('name')->get() as $type)
                                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="cd-form-group">
                                        <label>Brand</label>
                                        <select wire:model.defer="deviceBrandId">
                                            <option value="">Select Brand</option>
                                            @foreach(\App\Models\RepairBuddyDeviceBrand::orderBy('name')->get() as $brand)
                                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="cd-form-row">
                                    <div class="cd-form-group">
                                        <label>ID/IMEI</label>
                                        <input type="text" wire:model.defer="deviceIdentifier" placeholder="Serial number, IMEI..." />
                                    </div>
                                    <div class="cd-form-group">
                                        <label>Pin Code/Password</label>
                                        <input type="text" wire:model.defer="devicePinCode" placeholder="Device unlock code" />
                                    </div>
                                </div>
                                <div class="cd-form-group">
                                    <label>Notes</label>
                                    <textarea wire:model.defer="deviceNotes" rows="3" placeholder="Any additional details..."></textarea>
                                </div>
                                <div class="cd-modal-footer">
                                    <button type="button" class="cd-btn cd-btn-ghost" wire:click="closeAddDeviceModal">Cancel</button>
                                    <button type="submit" class="cd-btn cd-btn-primary">{{ $editingDeviceId ? 'Update' : 'Add' }} Device</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif

                {{-- Section: Reviews --}}
                <div x-show="activeSection === 'reviews'" wire:init="loadReviewsData">
                    <h2 class="cd-dash-section-title">Reviews</h2>
                    <p style="font-size:.82rem;color:var(--rb-text-3);margin:-0.75rem 0 1.5rem;">
                        Your reviews and feedback on completed repairs.
                    </p>

                    {{-- Stats Overview --}}
                    <div class="cd-reviews-stats">
                        <div class="cd-stat-card cd-stat-card-main">
                            <div class="cd-stat-number">{{ $reviewStats['total'] }}</div>
                            <div class="cd-stat-label">Total Reviews</div>
                        </div>
                        <div class="cd-stat-card">
                            <div class="cd-stat-number">{{ $reviewStats['avg'] }}</div>
                            <div class="cd-stat-label">Avg Rating</div>
                        </div>
                        <div class="cd-stat-card cd-rating-bars">
                            <div class="cd-rating-bar-row">
                                <span>5 <i class="bi bi-star-fill"></i></span>
                                <div class="cd-rating-bar"><div class="cd-rating-fill" style="width: {{ $reviewStats['total'] > 0 ? ($reviewStats[5] / $reviewStats['total'] * 100) : 0 }}%"></div></div>
                                <span>{{ $reviewStats[5] }}</span>
                            </div>
                            <div class="cd-rating-bar-row">
                                <span>4 <i class="bi bi-star-fill"></i></span>
                                <div class="cd-rating-bar"><div class="cd-rating-fill" style="width: {{ $reviewStats['total'] > 0 ? ($reviewStats[4] / $reviewStats['total'] * 100) : 0 }}%"></div></div>
                                <span>{{ $reviewStats[4] }}</span>
                            </div>
                            <div class="cd-rating-bar-row">
                                <span>3 <i class="bi bi-star-fill"></i></span>
                                <div class="cd-rating-bar"><div class="cd-rating-fill" style="width: {{ $reviewStats['total'] > 0 ? ($reviewStats[3] / $reviewStats['total'] * 100) : 0 }}%"></div></div>
                                <span>{{ $reviewStats[3] }}</span>
                            </div>
                            <div class="cd-rating-bar-row">
                                <span>2 <i class="bi bi-star-fill"></i></span>
                                <div class="cd-rating-bar"><div class="cd-rating-fill" style="width: {{ $reviewStats['total'] > 0 ? ($reviewStats[2] / $reviewStats['total'] * 100) : 0 }}%"></div></div>
                                <span>{{ $reviewStats[2] }}</span>
                            </div>
                            <div class="cd-rating-bar-row">
                                <span>1 <i class="bi bi-star-fill"></i></span>
                                <div class="cd-rating-bar"><div class="cd-rating-fill" style="width: {{ $reviewStats['total'] > 0 ? ($reviewStats[1] / $reviewStats['total'] * 100) : 0 }}%"></div></div>
                                <span>{{ $reviewStats[1] }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Reviews List --}}
                    @if(count($reviews) > 0)
                        <div class="cd-card">
                            <div class="cd-card-header">
                                <i class="bi bi-star me-2"></i> My Reviews
                            </div>
                            <div class="cd-card-body p-0">
                                <div class="cd-reviews-table">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Case #</th>
                                                <th>Job Title</th>
                                                <th>Rating</th>
                                                <th>Feedback</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($reviews as $review)
                                                <tr>
                                                    <td><span class="cd-badge cd-badge-secondary">{{ $review['job_case_number'] }}</span></td>
                                                    <td>{{ $review['job_title'] }}</td>
                                                    <td>
                                                        <span class="cd-stars">
                                                            @for($i = 1; $i <= 5; $i++)
                                                                @if($i <= $review['rating'])
                                                                    <i class="bi bi-star-fill"></i>
                                                                @else
                                                                    <i class="bi bi-star"></i>
                                                                @endif
                                                            @endfor
                                                        </span>
                                                    </td>
                                                    <td class="cd-feedback-cell">{{ $review['feedback'] ?? '-' }}</td>
                                                    <td>{{ $review['created_at'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="cd-empty">
                            <i class="bi bi-star" style="font-size:3rem;color:var(--rb-text-3);"></i>
                            <div class="cd-empty-title">No Reviews Yet</div>
                            <div class="cd-empty-text">Your reviews will appear here after completing repairs.</div>
                        </div>
                    @endif
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
