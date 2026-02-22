<div class="rb-booking-wizard-wrapper">

  {{-- Success state --}}
  @if($submitted)
    <div class="rb-booking-success">
      <div class="rb-booking-success-icon">
        <i class="bi bi-check-circle-fill"></i>
      </div>
      <h2>Booking Submitted!</h2>
      <p class="text-muted">{{ $submissionMessage }}</p>
      @if($submissionCaseNumber)
        <div class="rb-booking-case-number">
          <span class="rb-case-label">Case Number</span>
          <span class="rb-case-value">{{ $submissionCaseNumber }}</span>
        </div>
      @endif
      <p class="mt-3 text-muted">You will receive an email confirmation shortly.</p>
    </div>
  @else

    {{-- Progress bar --}}
    <div class="rb-booking-progress">
      <div class="rb-progress-steps">
        @php
          $steps = [
            1 => ['label' => 'Device Type', 'icon' => 'bi-phone'],
            2 => ['label' => 'Brand', 'icon' => 'bi-tag'],
            3 => ['label' => 'Device', 'icon' => 'bi-laptop'],
            4 => ['label' => 'Service', 'icon' => 'bi-wrench'],
            5 => ['label' => 'Your Details', 'icon' => 'bi-person'],
          ];
        @endphp
        @foreach($steps as $num => $info)
          <div class="rb-progress-step {{ $step >= $num ? 'active' : '' }} {{ $step === $num ? 'current' : '' }}">
            <div class="rb-progress-step-circle">
              <i class="bi {{ $info['icon'] }}"></i>
            </div>
            <span class="rb-progress-step-label">{{ $info['label'] }}</span>
          </div>
          @if($num < 5)
            <div class="rb-progress-connector {{ $step > $num ? 'active' : '' }}"></div>
          @endif
        @endforeach
      </div>
    </div>

    {{-- Error messages --}}
    @if($errorMessage)
      <div class="alert alert-danger rb-booking-alert" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $errorMessage }}
      </div>
    @endif

    {{-- Step 1: Device Type --}}
    @if($step === 1)
      <div class="rb-booking-step" wire:key="step-1">
        <div class="rb-step-header">
          <h2 class="rb-step-title">Select Device Type</h2>
          <p class="rb-step-desc">What type of device needs repair?</p>
        </div>

        <div class="rb-selection-grid">
          @forelse($deviceTypes as $type)
            <div class="rb-selection-card" wire:click="selectType({{ $type['id'] }})" wire:key="type-{{ $type['id'] }}">
              <div class="rb-selection-card-media">
                @if($type['image_url'])
                  <img src="{{ $type['image_url'] }}" alt="{{ $type['name'] }}" class="rb-selection-card-img">
                @else
                  <div class="rb-selection-card-icon">
                    <i class="bi bi-phone"></i>
                  </div>
                @endif
              </div>
              <div class="rb-selection-card-name">{{ $type['name'] }}</div>
            </div>
          @empty
            <div class="rb-empty-state">
              <i class="bi bi-inbox"></i>
              <p>No device types available.</p>
            </div>
          @endforelse
        </div>
      </div>
    @endif

    {{-- Step 2: Brand --}}
    @if($step === 2)
      <div class="rb-booking-step" wire:key="step-2">
        <div class="rb-step-header">
          <h2 class="rb-step-title">Select Brand</h2>
          <p class="rb-step-desc">Which manufacturer or brand?</p>
        </div>

        <button class="btn rb-btn-back" wire:click="goBack(1)">
          <i class="bi bi-arrow-left me-1"></i> Back to Device Types
        </button>

        <div class="rb-selection-grid">
          @forelse($brands as $brand)
            <div class="rb-selection-card" wire:click="selectBrand({{ $brand['id'] }})" wire:key="brand-{{ $brand['id'] }}">
              <div class="rb-selection-card-media">
                @if($brand['image_url'])
                  <img src="{{ $brand['image_url'] }}" alt="{{ $brand['name'] }}" class="rb-selection-card-img">
                @else
                  <div class="rb-selection-card-icon">
                    <i class="bi bi-tag"></i>
                  </div>
                @endif
              </div>
              <div class="rb-selection-card-name">{{ $brand['name'] }}</div>
            </div>
          @empty
            {{-- No brands found — if Other is allowed, show only the Other card --}}
          @endforelse

          {{-- Other brand card --}}
          @if(! $turnOffOtherDeviceBrand)
            <div class="rb-selection-card rb-selection-card-other" wire:click="selectOtherBrand" wire:key="brand-other">
              <div class="rb-selection-card-media">
                <div class="rb-selection-card-icon rb-icon-other">
                  <i class="bi bi-question-circle"></i>
                </div>
              </div>
              <div class="rb-selection-card-name">Other</div>
            </div>
          @endif
        </div>
      </div>
    @endif

    {{-- Step 3: Device --}}
    @if($step === 3)
      <div class="rb-booking-step" wire:key="step-3">

        @if($isOtherDevice)
          {{-- Other Device: custom label input --}}
          <div class="rb-step-header">
            <h2 class="rb-step-title">Describe Your Device</h2>
            <p class="rb-step-desc">Enter the name or model of your device.</p>
          </div>

          <button class="btn rb-btn-back" wire:click="goBack({{ $isOtherBrand ? 2 : 3 }})">
            <i class="bi bi-arrow-left me-1"></i> Back
          </button>

          <div class="rb-other-device-form">
            <div class="mb-3">
              <label class="form-label fw-semibold">Device Name <span class="text-danger">*</span></label>
              <input
                type="text"
                class="form-control"
                wire:model.defer="otherDeviceLabel"
                placeholder="e.g. Samsung Galaxy S24, HP Pavilion Laptop..."
                autofocus
              >
            </div>
            <button class="btn rb-btn-primary" wire:click="confirmOtherDevice">
              Continue <i class="bi bi-arrow-right ms-1"></i>
            </button>
          </div>
        @else
          {{-- Normal device list --}}
          <div class="rb-step-header">
            <h2 class="rb-step-title">Select Device</h2>
            <p class="rb-step-desc">Choose your specific device model.</p>
          </div>

          <button class="btn rb-btn-back" wire:click="goBack(2)">
            <i class="bi bi-arrow-left me-1"></i> Back to Brands
          </button>

          <div class="rb-selection-grid rb-selection-grid-compact">
            @forelse($devices as $device)
              <div class="rb-selection-card rb-selection-card-compact" wire:click="selectDeviceAndAddEntry({{ $device['id'] }})" wire:key="device-{{ $device['id'] }}">
                <div class="rb-selection-card-icon-sm">
                  <i class="bi bi-laptop"></i>
                </div>
                <div class="rb-selection-card-name">{{ $device['model'] }}</div>
              </div>
            @empty
              {{-- No devices — if Other is allowed, the Other card below is the only option --}}
            @endforelse

            {{-- Other device card --}}
            @if(! $turnOffOtherDeviceBrand)
              <div class="rb-selection-card rb-selection-card-compact rb-selection-card-other" wire:click="selectOtherDevice" wire:key="device-other">
                <div class="rb-selection-card-icon-sm rb-icon-other">
                  <i class="bi bi-question-circle"></i>
                </div>
                <div class="rb-selection-card-name">Other</div>
              </div>
            @endif
          </div>
        @endif

      </div>
    @endif

    {{-- Step 4: Service Selection (per device) --}}
    @if($step === 4)
      <div class="rb-booking-step" wire:key="step-4">
        <div class="rb-step-header">
          <h2 class="rb-step-title">Select Services</h2>
          <p class="rb-step-desc">Choose a service for each device.</p>
        </div>

        <button class="btn rb-btn-back" wire:click="goBack(3)">
          <i class="bi bi-arrow-left me-1"></i> Back to Devices
        </button>

        @foreach($deviceEntries as $idx => $entry)
          <div class="rb-device-entry" wire:key="device-entry-{{ $idx }}">
            <div class="rb-device-entry-header">
              <div class="rb-device-entry-info">
                <i class="bi bi-laptop me-2"></i>
                <strong>{{ $entry['device_label'] }}</strong>
                @if(! empty($entry['is_other']))
                  <span class="badge bg-secondary ms-2" style="font-size: .7rem;">Custom</span>
                @endif
              </div>
              <button class="btn btn-sm rb-btn-remove" wire:click="removeDeviceEntry({{ $idx }})">
                <i class="bi bi-x-lg"></i>
              </button>
            </div>

            <div class="rb-device-entry-body">
              {{-- Service list --}}
              <div class="rb-service-list">
                @forelse($entry['services'] ?? [] as $svc)
                  <label class="rb-service-item {{ ($entry['selectedServiceId'] ?? null) == $svc['id'] ? 'selected' : '' }}" wire:key="svc-{{ $idx }}-{{ $svc['id'] }}">
                    <input
                      type="radio"
                      name="service_{{ $idx }}"
                      value="{{ $svc['id'] }}"
                      wire:model.live="deviceEntries.{{ $idx }}.selectedServiceId"
                      class="rb-service-radio"
                    >
                    <div class="rb-service-info">
                      <span class="rb-service-name">{{ $svc['name'] }}</span>
                      @if($svc['service_type_name'])
                        <span class="rb-service-type">{{ $svc['service_type_name'] }}</span>
                      @endif
                      @if($svc['description'])
                        <span class="rb-service-desc">{{ Str::limit($svc['description'], 80) }}</span>
                      @endif
                    </div>
                    @if($svc['price_display'])
                      <span class="rb-service-price">{{ $svc['price_display'] }}</span>
                    @endif
                  </label>
                @empty
                  <p class="text-muted small">No services available for this device.</p>
                @endforelse

                @if(! $turnOffOtherService)
                  <div class="rb-other-service mt-3">
                    <label class="form-label small fw-semibold">Or describe the service needed:</label>
                    <input
                      type="text"
                      class="form-control form-control-sm"
                      placeholder="e.g. Screen replacement..."
                      wire:model.live="deviceEntries.{{ $idx }}.otherService"
                    >
                  </div>
                @endif
              </div>

              {{-- Serial / IMEI (optional) --}}
              @if(! $turnOffIdImeiBooking)
                <div class="row mt-3">
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold">Serial / IMEI</label>
                    <input type="text" class="form-control form-control-sm" wire:model.defer="deviceEntries.{{ $idx }}.serial" placeholder="Optional">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold">Device Notes</label>
                    <input type="text" class="form-control form-control-sm" wire:model.defer="deviceEntries.{{ $idx }}.notes" placeholder="Optional">
                  </div>
                </div>
              @endif
            </div>
          </div>
        @endforeach

        <div class="rb-step-actions">
          <button class="btn rb-btn-secondary" wire:click="addAnotherDevice">
            <i class="bi bi-plus-circle me-1"></i> Add Another Device
          </button>
          <button class="btn rb-btn-primary" wire:click="goToCustomerStep">
            Continue <i class="bi bi-arrow-right ms-1"></i>
          </button>
        </div>
      </div>
    @endif

    {{-- Step 5: Customer Details --}}
    @if($step === 5)
      <div class="rb-booking-step" wire:key="step-5">
        <div class="rb-step-header">
          <h2 class="rb-step-title">Your Details</h2>
          <p class="rb-step-desc">Tell us how to reach you.</p>
        </div>

        <button class="btn rb-btn-back" wire:click="goBack(4)">
          <i class="bi bi-arrow-left me-1"></i> Back to Services
        </button>

        {{-- Summary of devices --}}
        <div class="rb-booking-summary">
          <h6 class="rb-summary-title"><i class="bi bi-cart3 me-2"></i>Your Devices</h6>
          @foreach($deviceEntries as $entry)
            <div class="rb-summary-item">
              <span class="rb-summary-device"><i class="bi bi-laptop me-1"></i> {{ $entry['device_label'] }}</span>
              @php
                $svcName = '';
                if (! empty($entry['selectedServiceId'])) {
                  foreach ($entry['services'] ?? [] as $s) {
                    if ((int) $s['id'] === (int) $entry['selectedServiceId']) {
                      $svcName = $s['name'];
                      break;
                    }
                  }
                } elseif (! empty($entry['otherService'])) {
                  $svcName = $entry['otherService'];
                }
              @endphp
              @if($svcName)
                <span class="rb-summary-service text-muted">— {{ $svcName }}</span>
              @endif
            </div>
          @endforeach
        </div>

        <form wire:submit.prevent="submit">
          {{-- Contact Info Section --}}
          <div class="rb-form-section mb-4">
            <h6 class="rb-form-section-title"><i class="bi bi-person me-2"></i>Contact Information</h6>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">First Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" wire:model.defer="firstName" required>
                @error('firstName') <span class="text-danger small">{{ $message }}</span> @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" wire:model.defer="lastName" required>
                @error('lastName') <span class="text-danger small">{{ $message }}</span> @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" wire:model.defer="email" required>
                @error('email') <span class="text-danger small">{{ $message }}</span> @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input type="text" class="form-control" wire:model.defer="phone">
                @error('phone') <span class="text-danger small">{{ $message }}</span> @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label">Company</label>
                <input type="text" class="form-control" wire:model.defer="company">
              </div>
              <div class="col-md-6">
                <label class="form-label">Tax ID</label>
                <input type="text" class="form-control" wire:model.defer="taxId">
              </div>
            </div>
          </div>

          {{-- Address Section --}}
          <div class="rb-form-section mb-4">
            <h6 class="rb-form-section-title"><i class="bi bi-geo-alt me-2"></i>Address <span class="text-muted fw-normal fs-6">(Optional)</span></h6>
            <div class="row g-3">
              <div class="col-md-12">
                <label class="form-label">Street Address</label>
                <input type="text" class="form-control" wire:model.defer="addressLine1" placeholder="e.g. 123 Main St">
              </div>
              <div class="col-md-6">
                <label class="form-label">City</label>
                <input type="text" class="form-control" wire:model.defer="city">
              </div>
              <div class="col-md-6">
                <label class="form-label">Postal Code</label>
                <input type="text" class="form-control" wire:model.defer="postalCode">
              </div>
            </div>
          </div>

          {{-- Booking Details Section --}}
          <div class="rb-form-section">
            <h6 class="rb-form-section-title"><i class="bi bi-card-text me-2"></i>Booking Details</h6>
            <div class="row g-3">
              <div class="col-12">
              <label class="form-label">Job Details <span class="text-danger">*</span></label>
              <textarea class="form-control" rows="4" wire:model.defer="jobDetails" placeholder="Describe the issue or service needed..." required></textarea>
              @error('jobDetails') <span class="text-danger small">{{ $message }}</span> @enderror
            </div>
            
            @if($gdprLabel)
              <div class="col-12 mt-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" wire:model.defer="gdprAccepted" id="gdprAccepted" required>
                  <label class="form-check-label" for="gdprAccepted">
                    {{ $gdprLabel }}
                    @if($gdprLinkLabel && $gdprLinkUrl)
                      <a href="{{ $gdprLinkUrl }}" target="_blank" class="text-primary text-decoration-underline ms-1">{{ $gdprLinkLabel }}</a>
                    @endif
                    <span class="text-danger">*</span>
                  </label>
                </div>
                @error('gdprAccepted') <span class="text-danger small d-block mt-1">{{ $message }}</span> @enderror
              </div>
            @endif
            </div>
          </div>

          <div class="rb-step-actions mt-4 pt-4 border-top">
            <button type="submit" class="btn rb-btn-primary rb-btn-submit" wire:loading.attr="disabled">
              <span wire:loading.remove wire:target="submit">
                <i class="bi bi-send me-1"></i> Submit Booking
              </span>
              <span wire:loading wire:target="submit">
                <span class="spinner-border spinner-border-sm me-1" role="status"></span> Submitting...
              </span>
            </button>
          </div>
        </form>
      </div>
    @endif

  @endif

</div>
