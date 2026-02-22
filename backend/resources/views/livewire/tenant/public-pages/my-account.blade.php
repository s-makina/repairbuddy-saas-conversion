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
      <p class="pp-kicker">{{ $tenantName ?: 'RepairBuddy' }} Accounts</p>
      <h1 class="pp-hero-title">My Account</h1>
      <p class="pp-hero-subtitle">Log in to view your repair history, manage devices, and track open jobs. New here? Register in seconds.</p>
    </div>

    <div class="pp-auth-grid">
      {{-- Register card --}}
      <div class="pp-auth-card">
        <h2 class="pp-auth-card-title"><i class="bi bi-person-plus"></i> Register</h2>
        <form wire:submit.prevent="register">
          <div class="pp-form-row">
            <div class="pp-form-group">
              <label class="pp-label">First Name <span class="pp-req">*</span></label>
              <input type="text" wire:model.defer="regFirstName" class="pp-input" required>
              @error('regFirstName') <span class="pp-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="pp-form-group">
              <label class="pp-label">Last Name <span class="pp-req">*</span></label>
              <input type="text" wire:model.defer="regLastName" class="pp-input" required>
              @error('regLastName') <span class="pp-field-error">{{ $message }}</span> @enderror
            </div>
          </div>
          <div class="pp-form-row">
            <div class="pp-form-group">
              <label class="pp-label">Email <span class="pp-req">*</span></label>
              <input type="email" wire:model.defer="regEmail" class="pp-input" required>
              @error('regEmail') <span class="pp-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="pp-form-group">
              <label class="pp-label">Phone</label>
              <input type="text" wire:model.defer="regPhone" class="pp-input">
            </div>
          </div>
          <div class="pp-form-row">
            <div class="pp-form-group">
              <label class="pp-label">Company</label>
              <input type="text" wire:model.defer="regCompany" class="pp-input">
            </div>
            <div class="pp-form-group">
              <label class="pp-label">Address</label>
              <input type="text" wire:model.defer="regAddress" class="pp-input">
            </div>
          </div>
          <div class="pp-form-row">
            <div class="pp-form-group">
              <label class="pp-label">City</label>
              <input type="text" wire:model.defer="regCity" class="pp-input">
            </div>
            <div class="pp-form-group">
              <label class="pp-label">Postal Code</label>
              <input type="text" wire:model.defer="regPostalCode" class="pp-input">
            </div>
          </div>
          <div class="pp-form-row">
            <div class="pp-form-group">
              <label class="pp-label">State / Province</label>
              <input type="text" wire:model.defer="regState" class="pp-input">
            </div>
          </div>
          <button type="submit" class="pp-btn pp-btn-primary" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="register">Register Account</span>
            <span wire:loading wire:target="register"><i class="bi bi-arrow-repeat pp-spin"></i> Creating…</span>
          </button>
        </form>
      </div>

      {{-- Login card --}}
      <div class="pp-auth-card">
        <h2 class="pp-auth-card-title"><i class="bi bi-box-arrow-in-right"></i> Login</h2>
        <form wire:submit.prevent="login">
          <div class="pp-form-group">
            <label class="pp-label">Email <span class="pp-req">*</span></label>
            <input type="email" wire:model.defer="loginEmail" class="pp-input" required>
            @error('loginEmail') <span class="pp-field-error">{{ $message }}</span> @enderror
          </div>
          <div class="pp-form-group">
            <label class="pp-label">Password <span class="pp-req">*</span></label>
            <input type="password" wire:model.defer="loginPassword" class="pp-input" required>
            @error('loginPassword') <span class="pp-field-error">{{ $message }}</span> @enderror
          </div>
          <button type="submit" class="pp-btn pp-btn-primary" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="login">Sign In</span>
            <span wire:loading wire:target="login"><i class="bi bi-arrow-repeat pp-spin"></i> Signing in…</span>
          </button>
        </form>
        <p class="pp-auth-help">
          <a href="{{ url('/login') }}">Forgot password?</a>
        </p>
      </div>
    </div>

  @else
    {{-- ═══════════ AUTHENTICATED VIEW: Dashboard ═══════════ --}}
    <div class="pp-dash">
      <div class="pp-dash-header">
        <div>
          <h1 class="pp-dash-title">Welcome, {{ $currentUser->name ?? 'Customer' }}</h1>
          <p class="pp-dash-subtitle">{{ $currentUser->email ?? '' }}</p>
        </div>
        <button wire:click="logout" class="pp-btn pp-btn-outline-sm">
          <i class="bi bi-box-arrow-right"></i> Sign Out
        </button>
      </div>

      {{-- Tab navigation --}}
      <div class="pp-tab-bar">
        <button wire:click="setTab('jobs')" class="pp-tab {{ $activeTab === 'jobs' ? 'active' : '' }}">
          <i class="bi bi-briefcase"></i> My Jobs
        </button>
        <button wire:click="setTab('profile')" class="pp-tab {{ $activeTab === 'profile' ? 'active' : '' }}">
          <i class="bi bi-person"></i> Profile
        </button>
      </div>

      {{-- Tab content --}}
      @if($activeTab === 'jobs')
        <div class="pp-tab-content">
          @if(count($jobs) > 0)
            <div class="pp-table-wrap">
              <table class="pp-table">
                <thead>
                  <tr>
                    <th>Case #</th>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Opened</th>
                    <th>Closed</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($jobs as $job)
                    <tr>
                      <td>
                        <a href="{{ route('tenant.status.show', ['business' => $business, 'case' => $job['case_number'] ?? '']) }}" class="pp-link">
                          {{ $job['case_number'] ?? '—' }}
                        </a>
                      </td>
                      <td>{{ $job['title'] ?? '—' }}</td>
                      <td>
                        <span class="pp-status-badge pp-status-{{ \Illuminate\Support\Str::slug($job['status_slug'] ?? 'unknown') }}">
                          {{ ucfirst(str_replace(['-','_'], ' ', $job['status_slug'] ?? 'Unknown')) }}
                        </span>
                      </td>
                      <td>
                        <span class="pp-badge pp-badge-muted">
                          {{ ucfirst(str_replace(['-','_'], ' ', $job['payment_status_slug'] ?? '—')) }}
                        </span>
                      </td>
                      <td>{{ $job['opened_at'] ?? '—' }}</td>
                      <td>{{ $job['closed_at'] ?? '—' }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @else
            <div class="pp-empty">
              <i class="bi bi-briefcase"></i>
              <p>No jobs found for your account yet.</p>
              <a href="{{ route('tenant.booking.show', ['business' => $business]) }}" class="pp-btn pp-btn-primary" style="margin-top: 1rem;">
                Book a Device
              </a>
            </div>
          @endif
        </div>
      @endif

      @if($activeTab === 'profile')
        <div class="pp-tab-content">
          <div class="pp-profile-card">
            <div class="pp-profile-row">
              <span class="pp-profile-label">Name</span>
              <span class="pp-profile-value">{{ $currentUser->name ?? '—' }}</span>
            </div>
            <div class="pp-profile-row">
              <span class="pp-profile-label">Email</span>
              <span class="pp-profile-value">{{ $currentUser->email ?? '—' }}</span>
            </div>
            <div class="pp-profile-row">
              <span class="pp-profile-label">Role</span>
              <span class="pp-profile-value">{{ ucfirst($currentUser->role ?? 'customer') }}</span>
            </div>
            <div class="pp-profile-row">
              <span class="pp-profile-label">Member Since</span>
              <span class="pp-profile-value">{{ $currentUser->created_at?->format('M d, Y') ?? '—' }}</span>
            </div>
          </div>
        </div>
      @endif
    </div>
  @endif
</div>
