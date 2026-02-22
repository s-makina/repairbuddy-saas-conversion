<div class="rb-status-wrapper">

  {{-- Header --}}
  <div class="rb-status-header">
    <h1 class="rb-status-title">
      <i class="bi bi-search me-2"></i>Check Your Job Status
    </h1>
    <p class="rb-status-subtitle">
      Enter your case number to view the status of your repair and communicate with us.
    </p>
  </div>

  {{-- Search Form --}}
  <div class="rb-status-search-card">
    <form wire:submit.prevent="searchCase" class="rb-status-search-form">
      <div class="rb-status-search-input-wrapper">
        <i class="bi bi-hash rb-status-search-icon"></i>
        <input
          type="text"
          class="form-control rb-status-search-input"
          wire:model.defer="caseNumber"
          placeholder="Enter your case number (e.g., RB-1001)"
          autocomplete="off"
        >
        @if($caseNumber !== '')
          <button type="button" class="rb-status-search-clear" wire:click="$set('caseNumber', '')">
            <i class="bi bi-x-lg"></i>
          </button>
        @endif
      </div>
      <button type="submit" class="btn rb-btn-primary rb-status-search-btn" wire:loading.attr="disabled">
        <span wire:loading.remove wire:target="searchCase">
          <i class="bi bi-search me-1"></i> Check Status
        </span>
        <span wire:loading wire:target="searchCase">
          <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
          Searching...
        </span>
      </button>
    </form>

    {{-- Quick Links --}}
    <div class="rb-status-quick-links">
      <a href="{{ route('tenant.booking.show', ['business' => $business]) }}" class="rb-status-quick-link">
        <i class="bi bi-calendar-plus me-1"></i> Book a Repair
      </a>
    </div>
  </div>

  {{-- Error Message --}}
  @if($errorMessage)
    <div class="alert alert-danger rb-status-alert" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $errorMessage }}
    </div>
  @endif

  {{-- Job Result --}}
  @if($entityType === 'job' && $job)
    <div class="rb-status-result">

      {{-- Status Card --}}
      <div class="rb-status-card rb-status-card-main">
        <div class="rb-status-card-header">
          <div class="rb-status-case-info">
            <h2 class="rb-status-case-number">{{ $job['case_number'] }}</h2>
            <p class="rb-status-case-title">{{ $job['title'] }}</p>
            <p class="rb-status-case-updated">Last updated: {{ $job['updated_at'] }}</p>
          </div>
          <div class="rb-status-badges">
            <span class="badge rb-status-badge rb-status-badge-{{ $this->getStatusClass($job['status']) }}">
              {{ $job['status_label'] }}
            </span>
            @if($job['payment_status'])
              <span class="badge rb-status-badge rb-status-badge-{{ $this->getPaymentStatusClass($job['payment_status']) }}">
                {{ $job['payment_status_label'] }}
              </span>
            @endif
          </div>
        </div>

        {{-- Quick Info --}}
        @if($job['pickup_date'] || $job['delivery_date'] || ($job['priority'] && $job['priority'] !== 'normal'))
          <div class="rb-status-quick-info">
            @if($job['pickup_date'])
              <div class="rb-status-info-item">
                <i class="bi bi-calendar-check"></i>
                <span>Pickup: <strong>{{ $job['pickup_date'] }}</strong></span>
              </div>
            @endif
            @if($job['delivery_date'])
              <div class="rb-status-info-item">
                <i class="bi bi-truck"></i>
                <span>Expected Delivery: <strong>{{ $job['delivery_date'] }}</strong></span>
              </div>
            @endif
            @if($job['priority'] && $job['priority'] !== 'normal')
              <span class="badge rb-priority-badge rb-priority-{{ $job['priority'] }}">
                {{ strtoupper($job['priority']) }}
              </span>
            @endif
          </div>
        @endif

        <div class="rb-status-actions">
          <button class="btn btn-outline-secondary btn-sm" wire:click="refresh">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
          </button>
        </div>
      </div>

      {{-- Tabs --}}
      <div class="rb-status-tabs-card">
        <ul class="nav nav-tabs rb-status-tabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button
              class="nav-link {{ $activeTab === 'timeline' ? 'active' : '' }}"
              wire:click="setActiveTab('timeline')"
              type="button"
            >
              <i class="bi bi-clock-history me-1"></i> Activity Timeline
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button
              class="nav-link {{ $activeTab === 'details' ? 'active' : '' }}"
              wire:click="setActiveTab('details')"
              type="button"
            >
              <i class="bi bi-info-circle me-1"></i> Job Details
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button
              class="nav-link {{ $activeTab === 'message' ? 'active' : '' }}"
              wire:click="setActiveTab('message')"
              type="button"
            >
              <i class="bi bi-chat-dots me-1"></i> Send Message
            </button>
          </li>
        </ul>

        <div class="rb-status-tab-content">

          {{-- Timeline Tab --}}
          @if($activeTab === 'timeline')
            <div class="rb-status-timeline-wrapper">
              @if(count($job['timeline']) > 0)
                <div class="rb-timeline">
                  @foreach($job['timeline'] as $index => $event)
                    <div class="rb-timeline-item {{ $index === 0 ? 'rb-timeline-item-first' : '' }}">
                      <div class="rb-timeline-marker">
                        <span class="rb-timeline-icon">
                          @switch(true)
                            @case(str_contains($event['type'], 'message'))
                              💬
                              @break
                            @case(str_contains($event['type'], 'attachment') || str_contains($event['type'], 'file'))
                              📎
                              @break
                            @case(str_contains($event['type'], 'status'))
                              🔄
                              @break
                            @case(str_contains($event['type'], 'created'))
                              ✨
                              @break
                            @case(str_contains($event['type'], 'payment'))
                              💳
                              @break
                            @case(str_contains($event['type'], 'note'))
                              📝
                              @break
                            @default
                              📌
                          @endswitch
                        </span>
                      </div>
                      <div class="rb-timeline-content">
                        <div class="rb-timeline-header">
                          <span class="rb-timeline-title">{{ $event['title'] }}</span>
                          <span class="rb-timeline-time">{{ $event['created_at'] }}</span>
                        </div>
                        @if($event['message'])
                          <div class="rb-timeline-message">{{ $event['message'] }}</div>
                        @endif
                        @if($event['attachment'])
                          <a href="{{ $event['attachment']['url'] }}" target="_blank" class="rb-timeline-attachment">
                            <i class="bi bi-paperclip me-1"></i>{{ $event['attachment']['filename'] }}
                          </a>
                        @endif
                      </div>
                    </div>
                  @endforeach
                </div>
              @else
                <div class="rb-empty-state">
                  <i class="bi bi-clock-history"></i>
                  <p>No activity history yet.</p>
                </div>
              @endif
            </div>
          @endif

          {{-- Details Tab --}}
          @if($activeTab === 'details')
            <div class="rb-status-details-wrapper">

              {{-- Devices --}}
              @if(count($job['devices']) > 0)
                <div class="rb-details-section">
                  <h5 class="rb-details-section-title">
                    <i class="bi bi-phone me-2"></i>Devices
                  </h5>
                  <div class="rb-devices-grid">
                    @foreach($job['devices'] as $device)
                      <div class="rb-device-card">
                        <div class="rb-device-icon">
                          <i class="bi bi-laptop"></i>
                        </div>
                        <div class="rb-device-info">
                          <div class="rb-device-label">
                            {{ $device['label'] ?: collect([$device['type_name'], $device['brand_name'], $device['device_name']])->filter()->implode(' › ') ?: 'Device' }}
                          </div>
                          @if($device['serial'])
                            <div class="rb-device-serial">
                              Serial: <code>{{ $device['serial'] }}</code>
                            </div>
                          @endif
                          @if($device['label'] && ($device['type_name'] || $device['brand_name'] || $device['device_name']))
                            <div class="rb-device-details">
                              {{ collect([$device['type_name'], $device['brand_name'], $device['device_name']])->filter()->implode(' › ') }}
                            </div>
                          @endif
                        </div>
                      </div>
                    @endforeach
                  </div>
                </div>
              @endif

              {{-- Services & Parts --}}
              @if(count($job['items']) > 0)
                <div class="rb-details-section">
                  <h5 class="rb-details-section-title">
                    <i class="bi bi-wrench me-2"></i>Services & Parts
                  </h5>
                  <div class="table-responsive">
                    <table class="table rb-items-table">
                      <thead>
                        <tr>
                          <th>Item</th>
                          <th>Type</th>
                          <th class="text-center">Qty</th>
                        </tr>
                      </thead>
                      <tbody>
                        @foreach($job['items'] as $item)
                          <tr>
                            <td>{{ $item['name'] }}</td>
                            <td>
                              <span class="badge bg-secondary">{{ $item['item_type'] }}</span>
                            </td>
                            <td class="text-center">{{ $item['qty'] }}</td>
                          </tr>
                        @endforeach
                      </tbody>
                    </table>
                  </div>
                </div>
              @endif

              {{-- Case Details --}}
              @if($job['case_detail'])
                <div class="rb-details-section">
                  <h5 class="rb-details-section-title">
                    <i class="bi bi-file-text me-2"></i>Additional Information
                  </h5>
                  <div class="rb-case-detail-box">
                    {{ $job['case_detail'] }}
                  </div>
                </div>
              @endif

              @if(count($job['devices']) === 0 && count($job['items']) === 0 && !$job['case_detail'])
                <div class="rb-empty-state">
                  <i class="bi bi-info-circle"></i>
                  <p>No additional details available.</p>
                </div>
              @endif
            </div>
          @endif

          {{-- Message Tab --}}
          @if($activeTab === 'message')
            <div class="rb-status-message-wrapper">
              <div class="rb-message-form-header">
                <h5 class="rb-message-form-title">
                  <i class="bi bi-chat-dots me-2"></i>Send a Message
                </h5>
                <p class="rb-message-form-desc">
                  Have a question or need to provide additional information? Send us a message below.
                </p>
              </div>

              @if($messageSuccess)
                <div class="alert alert-success rb-message-alert" role="alert">
                  <i class="bi bi-check-circle-fill me-2"></i>{{ $messageSuccess }}
                </div>
              @endif

              @if($messageError)
                <div class="alert alert-danger rb-message-alert" role="alert">
                  <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $messageError }}
                </div>
              @endif

              <form wire:submit.prevent="sendMessage" class="rb-message-form">
                <div class="mb-3">
                  <label class="form-label fw-semibold">Your Message</label>
                  <textarea
                    class="form-control rb-message-textarea"
                    wire:model.defer="messageBody"
                    rows="4"
                    placeholder="Type your message here... (e.g., questions about your repair, additional information)"
                  ></textarea>
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">Attach File (optional)</label>
                  <div class="rb-file-upload-wrapper">
                    @if($attachment)
                      <div class="rb-file-uploaded">
                        <i class="bi bi-file-earmark me-2"></i>
                        <span>{{ $attachment->getClientOriginalName() }}</span>
                        <span class="text-muted ms-2">({{ number_format($attachment->getSize() / 1024, 1) }} KB)</span>
                        <button type="button" class="btn btn-link text-danger btn-sm" wire:click="removeAttachment">
                          Remove
                        </button>
                      </div>
                    @else
                      <input
                        type="file"
                        class="form-control"
                        wire:model="attachment"
                        accept="image/*,.pdf,.doc,.docx,.txt"
                      >
                      <small class="text-muted">Max 10MB. Accepted: Images, PDF, DOC, TXT</small>
                    @endif
                    @error('attachment')
                      <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                  </div>
                </div>

                <div class="rb-message-form-actions">
                  <button
                    type="submit"
                    class="btn rb-btn-primary"
                    wire:loading.attr="disabled"
                    wire:target="sendMessage"
                    {{ ($messageBody === '' && !$attachment) ? 'disabled' : '' }}
                  >
                    <span wire:loading.remove wire:target="sendMessage">
                      <i class="bi bi-send me-1"></i> Send Message
                    </span>
                    <span wire:loading wire:target="sendMessage">
                      <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                      Sending...
                    </span>
                  </button>
                </div>
              </form>
            </div>
          @endif

        </div>
      </div>

    </div>
  @endif

  {{-- Estimate Result --}}
  @if($entityType === 'estimate' && $estimate)
    <div class="rb-status-result">
      <div class="rb-status-card rb-status-card-estimate">
        <div class="rb-estimate-icon">
          <i class="bi bi-file-earmark-text"></i>
        </div>
        <h2 class="rb-estimate-case-number">Estimate: {{ $estimate['case_number'] }}</h2>
        <p class="rb-estimate-title">{{ $estimate['title'] }}</p>
        <span class="badge rb-status-badge rb-status-badge-warning">
          {{ $estimate['status_label'] }}
        </span>
        <p class="rb-estimate-info mt-3">
          This is an estimate (quote). Please check your email for approval/rejection links,
          or log in to the customer portal to view details.
        </p>
        <div class="rb-estimate-actions">
          <button class="btn btn-outline-secondary" wire:click="refresh">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
          </button>
        </div>
      </div>
    </div>
  @endif

  {{-- Empty State (no search yet) --}}
  @if(!$entityType && !$errorMessage && $caseNumber === '')
    <div class="rb-status-empty-state">
      <div class="rb-empty-state-icon">
        <i class="bi bi-search"></i>
      </div>
      <h3 class="rb-empty-state-title">Enter Your Case Number</h3>
      <p class="rb-empty-state-desc">
        You can find your case number in the confirmation email you received when you booked your repair,
        or on the receipt from our shop.
      </p>
    </div>
  @endif

</div>

