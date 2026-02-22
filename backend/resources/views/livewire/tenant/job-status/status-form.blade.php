<div class="pp-section">

  {{-- ══════════ HERO ══════════ --}}
  <div class="pp-hero">
    <div class="pp-hero-icon">
      <i class="bi bi-activity"></i>
    </div>
    <p class="pp-kicker">{{ $tenantName ?: 'RepairBuddy' }} Status Portal</p>
    <h1 class="pp-hero-title">Track Your Repair</h1>
    <p class="pp-hero-subtitle">Enter your case number to view timeline updates, repair details, and send a message to the shop.</p>
  </div>

  {{-- ══════════ SEARCH ══════════ --}}
  <div class="pp-form-card st-search-card">
    <form wire:submit.prevent="searchCase" class="st-search-form" autocomplete="off">
      <div class="pp-search-wrap st-search-wrap">
        <i class="bi bi-hash pp-search-icon"></i>
        <input
          type="text"
          class="pp-search-input st-case-input"
          wire:model.defer="caseNumber"
          placeholder="e.g. RB-demo-MAIN-000001"
          spellcheck="false"
        >
        @if($caseNumber !== '')
          <button type="button" class="st-search-clear" wire:click="$set('caseNumber', '')" tabindex="-1">
            <i class="bi bi-x-circle-fill"></i>
          </button>
        @endif
      </div>
      <button type="submit" class="pp-btn pp-btn-primary st-search-btn" wire:loading.attr="disabled">
        <span wire:loading.remove wire:target="searchCase">
          <i class="bi bi-search"></i> Check Status
        </span>
        <span wire:loading wire:target="searchCase">
          <i class="bi bi-arrow-repeat pp-spin"></i> Searching…
        </span>
      </button>
    </form>

    {{-- Error --}}
    @if($errorMessage)
      <div class="pp-alert pp-alert-danger" style="margin-top: 1rem; margin-bottom: 0;">
        <i class="bi bi-exclamation-circle-fill"></i>
        <span>{{ $errorMessage }}</span>
      </div>
    @endif

    <div class="st-search-footer">
      <a href="{{ route('tenant.booking.show', ['business' => $business]) }}" class="pp-link">
        <i class="bi bi-calendar-plus"></i> Book a new repair
      </a>
      <span class="st-footer-sep"></span>
      <span class="st-footer-hint">
        <i class="bi bi-lightbulb"></i> Find your case number in your confirmation email.
      </span>
    </div>
  </div>

  {{-- ══════════ JOB RESULT ══════════ --}}
  @if($entityType === 'job' && $job)
    <div class="st-result" wire:key="job-result-{{ $job['case_number'] }}">

      {{-- ── Status Card ── --}}
      <div class="st-job-card st-job-card--{{ $this->getStatusClass($job['status']) }}">
        <div class="st-job-accent"></div>
        <div class="st-job-body">
          {{-- Left --}}
          <div class="st-job-info">
            <div class="st-job-case">{{ $job['case_number'] }}</div>
            <div class="st-job-title">{{ $job['title'] }}</div>
            <div class="st-job-meta">
              <span><i class="bi bi-clock"></i> Updated {{ $job['updated_at'] }}</span>
            </div>
          </div>
          {{-- Right --}}
          <div class="st-job-right">
            <div class="st-badge-group">
              <span class="pp-badge st-badge--{{ $this->getStatusClass($job['status']) }}">
                <span class="st-badge-dot"></span>{{ $job['status_label'] }}
              </span>
              @if($job['payment_status'])
                <span class="pp-badge st-badge--{{ $this->getPaymentStatusClass($job['payment_status']) }} st-badge--outline">
                  <i class="bi bi-credit-card"></i> {{ $job['payment_status_label'] }}
                </span>
              @endif
              @if($job['priority'] && $job['priority'] !== 'normal')
                <span class="pp-badge st-badge--priority-{{ $job['priority'] }}">
                  <i class="bi bi-lightning-fill"></i> {{ strtoupper($job['priority']) }}
                </span>
              @endif
            </div>
            <button type="button" class="pp-btn pp-btn-outline-sm" wire:click="refresh" title="Refresh">
              <i class="bi bi-arrow-clockwise"></i>
              <span wire:loading.remove wire:target="refresh">Refresh</span>
              <span wire:loading wire:target="refresh">…</span>
            </button>
          </div>
        </div>

        {{-- Dates row --}}
        @if($job['pickup_date'] || $job['delivery_date'])
          <div class="st-job-dates">
            @if($job['pickup_date'])
              <span class="st-date-chip">
                <i class="bi bi-calendar-check-fill"></i>
                Drop-off: <strong>{{ $job['pickup_date'] }}</strong>
              </span>
            @endif
            @if($job['delivery_date'])
              <span class="st-date-chip">
                <i class="bi bi-truck"></i>
                Est. Ready: <strong>{{ $job['delivery_date'] }}</strong>
              </span>
            @endif
          </div>
        @endif
      </div>

      {{-- ── Tabs ── --}}
      <div class="st-panel">
        <div class="pp-tab-bar">
          <button
            class="pp-tab {{ $activeTab === 'timeline' ? 'active' : '' }}"
            wire:click="setActiveTab('timeline')"
            type="button"
          >
            <i class="bi bi-activity"></i> Timeline
            @if(count($job['timeline']) > 0)
              <span class="st-tab-count">{{ count($job['timeline']) }}</span>
            @endif
          </button>
          <button
            class="pp-tab {{ $activeTab === 'details' ? 'active' : '' }}"
            wire:click="setActiveTab('details')"
            type="button"
          >
            <i class="bi bi-info-circle"></i> Details
          </button>
          <button
            class="pp-tab {{ $activeTab === 'message' ? 'active' : '' }}"
            wire:click="setActiveTab('message')"
            type="button"
          >
            <i class="bi bi-chat-dots"></i> Message Us
          </button>
        </div>

        <div class="st-tab-body">

          {{-- ── TIMELINE TAB ── --}}
          @if($activeTab === 'timeline')
            @if(count($job['timeline']) > 0)
              <div class="st-timeline">
                @foreach($job['timeline'] as $index => $event)
                  @php
                    $evType = $event['type'] ?? '';
                    $iconClass = match(true) {
                      str_contains($evType, 'created')    => 'bi-plus-circle-fill st-ev--created',
                      str_contains($evType, 'status')     => 'bi-arrow-repeat st-ev--status',
                      str_contains($evType, 'message')    => 'bi-chat-quote-fill st-ev--message',
                      str_contains($evType, 'attachment'),
                      str_contains($evType, 'file')       => 'bi-paperclip st-ev--attach',
                      str_contains($evType, 'payment')    => 'bi-credit-card-fill st-ev--payment',
                      str_contains($evType, 'note')       => 'bi-pencil-fill st-ev--note',
                      default                             => 'bi-dot st-ev--default',
                    };
                  @endphp
                  <div class="st-tl-item {{ $index === 0 ? 'st-tl-item--latest' : '' }}">
                    <div class="st-tl-icon"><i class="bi {{ $iconClass }}"></i></div>
                    <div class="st-tl-body">
                      <div class="st-tl-head">
                        <span class="st-tl-title">{{ $event['title'] }}</span>
                        <time class="st-tl-time" datetime="{{ $event['created_at_raw'] ?? '' }}">{{ $event['created_at'] }}</time>
                      </div>
                      @if($event['message'])
                        <p class="st-tl-msg">{{ $event['message'] }}</p>
                      @endif
                      @if($event['attachment'])
                        <a href="{{ $event['attachment']['url'] }}" target="_blank" rel="noopener" class="st-tl-file">
                          <i class="bi bi-file-earmark-arrow-down"></i> {{ $event['attachment']['filename'] }}
                        </a>
                      @endif
                    </div>
                  </div>
                @endforeach
              </div>
            @else
              <div class="pp-empty">
                <i class="bi bi-hourglass"></i>
                <p>No activity recorded yet. Check back soon.</p>
              </div>
            @endif
          @endif

          {{-- ── DETAILS TAB ── --}}
          @if($activeTab === 'details')
            <div class="st-details">

              @if(count($job['devices']) > 0)
                <section class="st-section">
                  <h3 class="st-section-title"><i class="bi bi-cpu"></i> Devices</h3>
                  <div class="st-devices">
                    @foreach($job['devices'] as $device)
                      <div class="st-device">
                        <div class="st-device-thumb"><i class="bi bi-laptop"></i></div>
                        <div class="st-device-data">
                          <div class="st-device-name">
                            {{ $device['label'] ?: collect([$device['brand_name'], $device['device_name']])->filter()->implode(' ') ?: 'Device' }}
                          </div>
                          @if($device['type_name'] || $device['brand_name'])
                            <div class="st-device-sub">{{ collect([$device['type_name'], $device['brand_name']])->filter()->implode(' · ') }}</div>
                          @endif
                          @if($device['serial'])
                            <div class="st-device-serial"><i class="bi bi-upc"></i> <code>{{ $device['serial'] }}</code></div>
                          @endif
                        </div>
                      </div>
                    @endforeach
                  </div>
                </section>
              @endif

              @if(count($job['items']) > 0)
                <section class="st-section">
                  <h3 class="st-section-title"><i class="bi bi-tools"></i> Services &amp; Parts</h3>
                  <div class="st-items-list">
                    @foreach($job['items'] as $item)
                      <div class="st-item-row">
                        <div class="st-item-icon">
                          <i class="bi {{ $item['item_type'] === 'part' ? 'bi-box-seam' : 'bi-wrench-adjustable' }}"></i>
                        </div>
                        <div class="st-item-name">{{ $item['name'] }}</div>
                        <span class="pp-badge pp-badge-muted">{{ ucfirst($item['item_type']) }}</span>
                        <div class="st-item-qty">&times;{{ $item['qty'] }}</div>
                      </div>
                    @endforeach
                  </div>
                </section>
              @endif

              @if($job['case_detail'])
                <section class="st-section">
                  <h3 class="st-section-title"><i class="bi bi-file-text"></i> Notes</h3>
                  <div class="st-notes">{{ $job['case_detail'] }}</div>
                </section>
              @endif

              @if(count($job['devices']) === 0 && count($job['items']) === 0 && !$job['case_detail'])
                <div class="pp-empty">
                  <i class="bi bi-inbox"></i>
                  <p>No detail information on file.</p>
                </div>
              @endif
            </div>
          @endif

          {{-- ── MESSAGE TAB ── --}}
          @if($activeTab === 'message')
            <div class="st-msg-tab">

              <div class="st-msg-intro">
                <div class="st-msg-intro-icon"><i class="bi bi-headset"></i></div>
                <div>
                  <p class="st-msg-intro-title">Got a question? We're here to help.</p>
                  <p class="st-msg-intro-desc">Send us a message and we'll get back to you as soon as possible.</p>
                </div>
              </div>

              @if($messageSuccess)
                <div class="pp-alert pp-alert-success">
                  <i class="bi bi-check-circle-fill"></i> {{ $messageSuccess }}
                </div>
              @endif

              @if($messageError)
                <div class="pp-alert pp-alert-danger">
                  <i class="bi bi-exclamation-circle-fill"></i> {{ $messageError }}
                </div>
              @endif

              <form wire:submit.prevent="sendMessage" class="st-msg-form">
                <div class="pp-form-group">
                  <label class="pp-label">Your Message</label>
                  <textarea
                    class="pp-textarea"
                    wire:model.defer="messageBody"
                    rows="4"
                    placeholder="Ask about your repair, provide extra details, or share any concerns…"
                  ></textarea>
                </div>

                <div class="pp-form-group">
                  <label class="pp-label">Attach a File <span style="font-weight: 400; color: var(--rpn-text-muted); font-size: .8rem;">(optional)</span></label>
                  @if($attachment)
                    <div class="st-file-preview">
                      <i class="bi bi-file-earmark-check-fill"></i>
                      <div class="st-file-info">
                        <span class="st-file-name">{{ $attachment->getClientOriginalName() }}</span>
                        <span class="st-file-size">{{ number_format($attachment->getSize() / 1024, 1) }} KB</span>
                      </div>
                      <button type="button" class="st-file-remove" wire:click="removeAttachment">
                        <i class="bi bi-x-lg"></i>
                      </button>
                    </div>
                  @else
                    <label class="st-dropzone" for="st-file-input">
                      <i class="bi bi-cloud-arrow-up st-dropzone-icon"></i>
                      <span class="st-dropzone-text">Click to select a file</span>
                      <span class="st-dropzone-hint">Images, PDF, DOC, TXT &middot; Max 10 MB</span>
                      <input
                        id="st-file-input"
                        type="file"
                        class="st-file-hidden"
                        wire:model="attachment"
                        accept="image/*,.pdf,.doc,.docx,.txt"
                      >
                    </label>
                  @endif
                  @error('attachment')
                    <span class="pp-field-error">{{ $message }}</span>
                  @enderror
                </div>

                <button
                  type="submit"
                  class="pp-btn pp-btn-primary"
                  wire:loading.attr="disabled"
                  wire:target="sendMessage"
                  {{ ($messageBody === '' && !$attachment) ? 'disabled' : '' }}
                >
                  <span wire:loading.remove wire:target="sendMessage">
                    <i class="bi bi-send-fill"></i> Send Message
                  </span>
                  <span wire:loading wire:target="sendMessage">
                    <i class="bi bi-arrow-repeat pp-spin"></i> Sending…
                  </span>
                </button>
              </form>
            </div>
          @endif

        </div>
      </div>

    </div>
  @endif

  {{-- ══════════ ESTIMATE RESULT ══════════ --}}
  @if($entityType === 'estimate' && $estimate)
    <div class="st-result">
      <div class="pp-success-panel">
        <div class="pp-hero-icon" style="background: rgba(245,158,11,.12);">
          <i class="bi bi-file-earmark-text-fill" style="color: #d97706;"></i>
        </div>
        <h2>{{ $estimate['title'] }}</h2>
        <div class="pp-case-number">{{ $estimate['case_number'] }}</div>
        <span class="pp-badge st-badge--warning" style="margin: .75rem 0;">
          <span class="st-badge-dot"></span> {{ $estimate['status_label'] }}
        </span>
        <p class="pp-success-hint">This is an estimate. You can approve or reject it via the link in your email, or through the customer portal.</p>
        <button type="button" class="pp-btn pp-btn-outline" wire:click="refresh" style="margin-top: .75rem;">
          <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
      </div>
    </div>
  @endif

  {{-- ══════════ EMPTY STATE ══════════ --}}
  @if(!$entityType && !$errorMessage && $caseNumber === '')
    <div class="st-steps">
      <div class="st-step">
        <div class="st-step-num">1</div>
        <p>Check your confirmation email for the case number.</p>
      </div>
      <div class="st-step">
        <div class="st-step-num">2</div>
        <p>Enter it in the search box above.</p>
      </div>
      <div class="st-step">
        <div class="st-step-num">3</div>
        <p>View your repair status and send us messages.</p>
      </div>
    </div>
  @endif

</div>
