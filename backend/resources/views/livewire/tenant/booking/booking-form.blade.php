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
          <h2 class="rb-step-title">Select {{ $labelType }}</h2>
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
          <h2 class="rb-step-title">Select {{ $labelBrand }}</h2>
          <p class="rb-step-desc">Which manufacturer or brand?</p>
        </div>

        <button class="btn rb-btn-back" wire:click="goBack(1)">
          <i class="bi bi-arrow-left me-1"></i> Back to {{ $labelType }}s
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
            <h2 class="rb-step-title">Describe Your {{ $labelDevice }}</h2>
            <p class="rb-step-desc">Enter the name or model of your device.</p>
          </div>

          <button class="btn rb-btn-back" wire:click="goBack({{ $isOtherBrand ? 2 : 3 }})">
            <i class="bi bi-arrow-left me-1"></i> Back
          </button>

          <div class="rb-other-device-form">
            <div class="mb-3">
              <label class="form-label fw-semibold">{{ $labelDevice }} Name <span class="text-danger">*</span></label>
              <input
                type="text"
                class="form-control"
                wire:model.defer="otherDeviceLabel"
                placeholder="e.g. Samsung Galaxy S24, HP Pavilion Laptop..."
                autofocus
              >
            </div>
            <button class="btn rb-btn-primary" wire:click="confirmOtherDevice">
              <i class="bi bi-plus-circle me-1"></i> Add Device
            </button>
          </div>
        @else
          {{-- Normal device list with search --}}
          <div class="rb-step-header">
            <h2 class="rb-step-title">Select {{ $labelDevice }}s</h2>
            <p class="rb-step-desc">Search and select the devices that need repair. You can add multiple devices.</p>
          </div>

          <button class="btn rb-btn-back" wire:click="goBack(2)">
            <i class="bi bi-arrow-left me-1"></i> Back to {{ $labelBrand }}s
          </button>

          {{-- Search input --}}
          <div class="rb-device-search-wrapper">
            <div class="rb-device-search">
              <i class="bi bi-search rb-device-search-icon"></i>
              <input
                type="text"
                class="form-control rb-device-search-input"
                wire:model.live.debounce.300ms="deviceSearch"
                placeholder="Search devices..."
                autocomplete="off"
              >
              @if($deviceSearch !== '')
                <button class="rb-device-search-clear" wire:click="$set('deviceSearch', '')" type="button">
                  <i class="bi bi-x-lg"></i>
                </button>
              @endif
            </div>
          </div>

          <div class="rb-selection-grid rb-selection-grid-compact">
            @forelse($devices as $device)
              <div class="rb-selection-card rb-selection-card-compact" wire:click="selectDeviceAndAddEntry({{ $device['id'] }})" wire:key="device-{{ $device['id'] }}">
                <div class="rb-selection-card-icon-sm">
                  <i class="bi bi-laptop"></i>
                </div>
                <div class="rb-selection-card-name">{{ $device['model'] }}</div>
              </div>
            @empty
              <div class="rb-empty-state">
                <i class="bi bi-search"></i>
                <p>No devices found{{ $deviceSearch !== '' ? ' for "' . $deviceSearch . '"' : '' }}.</p>
              </div>
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

        {{-- Selected Devices Section --}}
        @if(count($deviceEntries) > 0)
          <div class="rb-selected-devices">
            <h6 class="rb-selected-devices-title">
              <i class="bi bi-check2-circle me-2"></i>Selected {{ $labelDevice }}s
              <span class="rb-selected-devices-count">{{ count($deviceEntries) }}</span>
            </h6>

            @foreach($deviceEntries as $idx => $entry)
              <div class="rb-selected-device-card" wire:key="selected-device-{{ $idx }}" x-data="{ open: true }">
                <div class="rb-selected-device-header" @click="open = !open" style="cursor:pointer;">
                  <div class="rb-selected-device-name">
                    <i class="bi bi-laptop me-2"></i>
                    <strong>{{ $entry['device_label'] }}</strong>
                    @if(! empty($entry['is_other']))
                      <span class="badge bg-secondary ms-2" style="font-size: .7rem;">Custom</span>
                    @endif
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <i class="bi rb-collapse-icon" :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                    <button class="btn btn-sm rb-btn-remove" wire:click.stop="removeDeviceEntry({{ $idx }})" title="Remove device">
                      <i class="bi bi-x-lg"></i>
                    </button>
                  </div>
                </div>

                <div class="rb-selected-device-collapse" x-show="open" x-transition.duration.200ms>
                  <div class="rb-selected-device-fields">
                    {{-- Serial / IMEI --}}
                    @if(! $turnOffIdImeiBooking)
                      <div class="rb-selected-device-field">
                        <label class="form-label small fw-semibold">{{ $labelImei }}</label>
                        <input
                          type="text"
                          class="form-control form-control-sm"
                          wire:model.defer="deviceEntries.{{ $idx }}.serial"
                          placeholder="Enter {{ strtolower($labelImei) }}"
                        >
                      </div>
                    @endif

                    {{-- Pin Code --}}
                    @if($enablePinCodeField)
                      <div class="rb-selected-device-field">
                        <label class="form-label small fw-semibold">{{ $labelPin }}</label>
                        <input
                          type="text"
                          class="form-control form-control-sm"
                          wire:model.defer="deviceEntries.{{ $idx }}.pin"
                          placeholder="Enter {{ strtolower($labelPin) }}"
                        >
                      </div>
                    @endif

                    {{-- Dynamic fields --}}
                    @foreach($entry['extra_fields'] ?? [] as $efIdx => $ef)
                      <div class="rb-selected-device-field">
                        <label class="form-label small fw-semibold">{{ $ef['label'] }}</label>
                        <input
                          type="text"
                          class="form-control form-control-sm"
                          wire:model.defer="deviceEntries.{{ $idx }}.extra_fields.{{ $efIdx }}.value_text"
                          placeholder="Enter {{ strtolower($ef['label']) }}"
                        >
                      </div>
                    @endforeach

                    {{-- Device Notes --}}
                    <div class="rb-selected-device-field rb-selected-device-field-full">
                      <label class="form-label small fw-semibold">{{ $labelNote }}</label>
                      <input
                        type="text"
                        class="form-control form-control-sm"
                        wire:model.defer="deviceEntries.{{ $idx }}.notes"
                        placeholder="Any additional notes about this device"
                      >
                    </div>
                  </div>
                </div>
              </div>
            @endforeach
          </div>

          {{-- Continue to Services button --}}
          <div class="rb-step-actions">
            <div></div>
            <button class="btn rb-btn-primary" wire:click="proceedToServices">
              Continue to Services <i class="bi bi-arrow-right ms-1"></i>
            </button>
          </div>
        @endif

      </div>
    @endif

    {{-- Step 4: Service Selection (per device) --}}
    @if($step === 4)
      <div class="rb-booking-step" wire:key="step-4">
        <div class="rb-step-header">
          <h2 class="rb-step-title">Select Services</h2>
          <p class="rb-step-desc">Choose one or more services for each device.</p>
        </div>

        <button class="btn rb-btn-back" wire:click="goBack(3)">
          <i class="bi bi-arrow-left me-1"></i> Back to {{ $labelDevice }}s
        </button>

        @foreach($deviceEntries as $idx => $entry)
          <div class="rb-device-entry" wire:key="device-entry-{{ $idx }}" x-data="{ open: true }">
            <div class="rb-device-entry-header" @click="open = !open" style="cursor:pointer;">
              <div class="rb-device-entry-info">
                <i class="bi bi-laptop me-2"></i>
                <strong>{{ $entry['device_label'] }}</strong>
                @if(! empty($entry['is_other']))
                  <span class="badge bg-secondary ms-2" style="font-size: .7rem;">Custom</span>
                @endif
                @php
                  $selectedCount = is_array($entry['selectedServiceIds'] ?? null) ? count($entry['selectedServiceIds']) : 0;
                @endphp
                @if($selectedCount > 0)
                  <span class="badge bg-success ms-2" style="font-size: .7rem;">{{ $selectedCount }} selected</span>
                @endif
              </div>
              <div class="d-flex align-items-center gap-2">
                <i class="bi rb-collapse-icon" :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                <button class="btn btn-sm rb-btn-remove" wire:click.stop="removeDeviceEntry({{ $idx }})">
                  <i class="bi bi-x-lg"></i>
                </button>
              </div>
            </div>

            <div class="rb-device-entry-body" x-show="open" x-transition.duration.200ms>
              @php
                // Group services by category
                $servicesByCategory = collect($entry['services'] ?? [])->groupBy(fn ($s) => $s['service_type_name'] ?? 'Other Services');
                $selectedIds = is_array($entry['selectedServiceIds'] ?? null) ? $entry['selectedServiceIds'] : [];
              @endphp

              @forelse($servicesByCategory as $categoryName => $categorySvcs)
                <div class="rb-service-category">
                  <h6 class="rb-service-category-title">
                    <i class="bi bi-tag-fill me-1"></i> {{ $categoryName }}
                    <span class="rb-service-category-count">{{ count($categorySvcs) }}</span>
                  </h6>

                  <div class="rb-service-grid">
                    @foreach($categorySvcs as $svc)
                      @php $isSelected = in_array($svc['id'], $selectedIds); @endphp
                      <div
                        class="rb-service-card {{ $isSelected ? 'rb-service-card-selected' : '' }}"
                        wire:click="toggleService({{ $idx }}, {{ $svc['id'] }})"
                        wire:key="svc-{{ $idx }}-{{ $svc['id'] }}"
                      >
                        <div class="rb-service-card-check">
                          <i class="bi {{ $isSelected ? 'bi-check-circle-fill' : 'bi-circle' }}"></i>
                        </div>
                        <div class="rb-service-card-body">
                          <div class="rb-service-card-name">{{ $svc['name'] }}</div>
                          @if($svc['description'])
                            <div class="rb-service-card-desc">{{ Str::limit($svc['description'], 60) }}</div>
                          @endif
                        </div>
                        @if($svc['price_display'])
                          <div class="rb-service-card-price">{{ $svc['price_display'] }}</div>
                        @endif
                      </div>
                    @endforeach
                  </div>
                </div>
              @empty
                <p class="text-muted small px-3 py-2">No services available for this device.</p>
              @endforelse

              @if(! $turnOffOtherService)
                <div class="rb-other-service px-3 pb-3">
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
          </div>
        @endforeach

        {{-- Appointment Scheduling (optional) --}}
        @if(count($appointmentOptions) > 0)
          <div class="rb-appointment-section">
            <h5 class="rb-appointment-title">
              <i class="bi bi-calendar-check me-2"></i>Schedule an Appointment
              <span class="badge bg-info bg-opacity-10 text-info ms-2" style="font-size:.7rem;">Optional</span>
            </h5>
            <p class="rb-appointment-desc text-muted small">Select an appointment type, date, and preferred time slot.</p>

            {{-- Appointment option cards --}}
            <div class="rb-appointment-options">
              @foreach($appointmentOptions as $opt)
                <div
                  class="rb-appointment-option-card {{ $selectedAppointmentId === $opt['id'] ? 'rb-appointment-option-selected' : '' }}"
                  wire:click="$set('selectedAppointmentId', {{ $opt['id'] }})"
                  wire:key="appt-{{ $opt['id'] }}"
                >
                  <div class="rb-appointment-option-check">
                    <i class="bi {{ $selectedAppointmentId === $opt['id'] ? 'bi-check-circle-fill' : 'bi-circle' }}"></i>
                  </div>
                  <div class="rb-appointment-option-body">
                    <div class="fw-semibold">{{ $opt['title'] }}</div>
                    @if($opt['description'])
                      <div class="text-muted small">{{ $opt['description'] }}</div>
                    @endif
                    <div class="text-muted small mt-1">
                      <i class="bi bi-clock me-1"></i>{{ $opt['slot_duration_minutes'] }} min
                    </div>
                  </div>
                </div>
              @endforeach
            </div>

            {{-- Date + Time picker (shown when an option is selected) --}}
            @if($selectedAppointmentId)
              <div class="rb-appointment-datetime mt-3">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">Preferred Date</label>
                    <input
                      type="date"
                      class="form-control"
                      wire:model.live="selectedAppointmentDate"
                      min="{{ date('Y-m-d') }}"
                    >
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">Preferred Time</label>
                    @php
                      $activeOption = collect($appointmentOptions)->firstWhere('id', $selectedAppointmentId);
                      $enabledSlots = $activeOption ? collect($activeOption['time_slots'] ?? [])->where('enabled', true) : collect();
                      $dayOfWeek = $selectedAppointmentDate ? strtolower(date('l', strtotime($selectedAppointmentDate))) : null;
                      $todaySlot = $dayOfWeek ? $enabledSlots->firstWhere('day', $dayOfWeek) : null;
                    @endphp

                    @if($selectedAppointmentDate && $todaySlot)
                      @php
                        $duration = $activeOption['slot_duration_minutes'] ?? 30;
                        $buffer = $activeOption['buffer_minutes'] ?? 0;
                        $startMinutes = intval(substr($todaySlot['start'], 0, 2)) * 60 + intval(substr($todaySlot['start'], 3, 2));
                        $endMinutes = intval(substr($todaySlot['end'], 0, 2)) * 60 + intval(substr($todaySlot['end'], 3, 2));
                        $slots = [];
                        $current = $startMinutes;
                        while ($current + $duration <= $endMinutes) {
                          $h = str_pad((string) intdiv($current, 60), 2, '0', STR_PAD_LEFT);
                          $m = str_pad((string) ($current % 60), 2, '0', STR_PAD_LEFT);
                          $slots[] = "{$h}:{$m}";
                          $current += $duration + $buffer;
                        }
                      @endphp

                      <div class="rb-time-slot-grid">
                        @foreach($slots as $slot)
                          <button
                            type="button"
                            class="rb-time-slot {{ $selectedTimeSlot === $slot ? 'rb-time-slot-selected' : '' }}"
                            wire:click="$set('selectedTimeSlot', '{{ $slot }}')"
                          >
                            {{ $slot }}
                          </button>
                        @endforeach
                      </div>
                    @elseif($selectedAppointmentDate && ! $todaySlot)
                      <p class="text-muted small mt-2"><i class="bi bi-info-circle me-1"></i>No appointments available on this day. Please select a different date.</p>
                    @else
                      <p class="text-muted small mt-2">Select a date to see available times.</p>
                    @endif
                  </div>
                </div>
              </div>
            @endif
          </div>
        @endif

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
