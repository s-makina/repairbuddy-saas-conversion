<div class="pp-section">
  {{-- Header --}}
  <div class="pp-hero">
    <div class="pp-hero-icon">
      <i class="bi bi-file-earmark-check"></i>
    </div>
    <p class="pp-kicker">{{ $tenantName ?: 'RepairBuddy' }} Quotes</p>
    <h1 class="pp-hero-title">Review Your Job</h1>
    <p class="pp-hero-subtitle">Need a repair estimate? Fill in the form below and we'll get back to you with a detailed quote.</p>
  </div>

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

  @if($submitted)
    {{-- ═══════════ SUCCESS STATE ═══════════ --}}
    <div class="pp-success-panel">
      <div class="pp-success-icon">
        <i class="bi bi-check-circle-fill"></i>
      </div>
      <h2>Quote Request Submitted!</h2>
      <p>Your case number is:</p>
      <div class="pp-case-number">{{ $createdCaseNumber }}</div>
      <p class="pp-success-hint">Save this number to track your job status.</p>
      <div class="pp-success-actions">
        <a href="{{ route('tenant.status.show', ['business' => $business]) }}" class="pp-btn pp-btn-primary">
          <i class="bi bi-activity"></i> Check Job Status
        </a>
        <button wire:click="resetForm" class="pp-btn pp-btn-outline">
          <i class="bi bi-plus-circle"></i> Submit Another
        </button>
      </div>
    </div>

  @else
    {{-- ═══════════ FORM ═══════════ --}}
    <div class="pp-form-card">
      <form wire:submit.prevent="submitQuote">

        @if(! $isLoggedIn)
          <h3 class="pp-form-section-title">Your Details</h3>
          <div class="pp-form-row">
            <div class="pp-form-group">
              <label class="pp-label">First Name <span class="pp-req">*</span></label>
              <input type="text" wire:model.defer="firstName" class="pp-input" required>
              @error('firstName') <span class="pp-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="pp-form-group">
              <label class="pp-label">Last Name <span class="pp-req">*</span></label>
              <input type="text" wire:model.defer="lastName" class="pp-input" required>
              @error('lastName') <span class="pp-field-error">{{ $message }}</span> @enderror
            </div>
          </div>
          <div class="pp-form-row">
            <div class="pp-form-group">
              <label class="pp-label">Email <span class="pp-req">*</span></label>
              <input type="email" wire:model.defer="email" class="pp-input" required>
              @error('email') <span class="pp-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="pp-form-group">
              <label class="pp-label">Phone</label>
              <input type="text" wire:model.defer="phone" class="pp-input">
            </div>
          </div>
          <div class="pp-form-row">
            <div class="pp-form-group">
              <label class="pp-label">City</label>
              <input type="text" wire:model.defer="city" class="pp-input">
            </div>
            <div class="pp-form-group">
              <label class="pp-label">Postal Code</label>
              <input type="text" wire:model.defer="postalCode" class="pp-input">
            </div>
          </div>
          <div class="pp-form-row">
            <div class="pp-form-group">
              <label class="pp-label">Company</label>
              <input type="text" wire:model.defer="company" class="pp-input">
            </div>
            <div class="pp-form-group">
              <label class="pp-label">Address</label>
              <input type="text" wire:model.defer="address" class="pp-input">
            </div>
          </div>

          <hr class="pp-divider">
        @else
          <div class="pp-logged-in-note">
            <i class="bi bi-person-check-fill"></i>
            Submitting as <strong>{{ Auth::user()->name ?? Auth::user()->email }}</strong>
          </div>
        @endif

        <h3 class="pp-form-section-title">Job Information</h3>
        <div class="pp-form-group">
          <label class="pp-label">Job Details <span class="pp-req">*</span></label>
          <textarea
            wire:model.defer="jobDetails"
            rows="5"
            class="pp-textarea"
            placeholder="Describe the issue with your device — model, symptoms, when it started, etc."
            required
          ></textarea>
          @error('jobDetails') <span class="pp-field-error">{{ $message }}</span> @enderror
        </div>

        <p class="pp-form-hint"><small>* Required fields</small></p>

        <button type="submit" class="pp-btn pp-btn-primary pp-btn-lg" wire:loading.attr="disabled">
          <span wire:loading.remove wire:target="submitQuote"><i class="bi bi-send"></i> Request Quote</span>
          <span wire:loading wire:target="submitQuote"><i class="bi bi-arrow-repeat pp-spin"></i> Submitting…</span>
        </button>
      </form>
    </div>
  @endif
</div>
