<div class="rbs-root">

  {{-- ══════════ HERO ══════════ --}}
  <div class="rbs-hero">
    <div class="rbs-hero-bg"></div>
    <div class="rbs-hero-content">
      <div class="rbs-hero-icon">
        <i class="bi bi-clipboard2-pulse-fill"></i>
      </div>
      <h1 class="rbs-hero-title">Track Your Repair</h1>
      <p class="rbs-hero-sub">Enter your case number below to get a real-time update on your repair.</p>
    </div>
  </div>

  {{-- ══════════ SEARCH ══════════ --}}
  <div class="rbs-search-shell">
    <div class="rbs-search-card">
      <form wire:submit.prevent="searchCase" class="rbs-search-form" autocomplete="off">
        <div class="rbs-search-field">
          <span class="rbs-search-prefix">
            <i class="bi bi-hash"></i>
          </span>
          <input
            type="text"
            class="rbs-search-input"
            wire:model.defer="caseNumber"
            placeholder="e.g. RB-demo-MAIN-000001"
            spellcheck="false"
          >
          @if($caseNumber !== '')
            <button type="button" class="rbs-search-clear" wire:click="$set('caseNumber', '')" tabindex="-1">
              <i class="bi bi-x-circle-fill"></i>
            </button>
          @endif
        </div>
        <button type="submit" class="rbs-search-btn" wire:loading.attr="disabled">
          <span wire:loading.remove wire:target="searchCase">
            <i class="bi bi-search me-2"></i>Check Status
          </span>
          <span wire:loading wire:target="searchCase" class="rbs-loading-label">
            <span class="rbs-spinner"></span>Searching…
          </span>
        </button>
      </form>

      {{-- Error --}}
      @if($errorMessage)
        <div class="rbs-inline-error" role="alert">
          <i class="bi bi-exclamation-circle-fill"></i>
          <span>{{ $errorMessage }}</span>
        </div>
      @endif

      <div class="rbs-search-footer">
        <a href="{{ route('tenant.booking.show', ['business' => $business]) }}" class="rbs-footer-link">
          <i class="bi bi-calendar-plus"></i>Book a new repair
        </a>
        <span class="rbs-footer-divider"></span>
        <span class="rbs-footer-hint">
          <i class="bi bi-lightbulb"></i>Find your case number in your confirmation email.
        </span>
      </div>
    </div>
  </div>

  {{-- ══════════ JOB RESULT ══════════ --}}
  @if($entityType === 'job' && $job)
    <div class="rbs-result" wire:key="job-result-{{ $job['case_number'] }}">

      {{-- ── Status Hero Card ── --}}
      <div class="rbs-job-card rbs-job-card--{{ $this->getStatusClass($job['status']) }}">
        <div class="rbs-job-accent"></div>
        <div class="rbs-job-body">

          {{-- Left: case info --}}
          <div class="rbs-job-info">
            <div class="rbs-job-case">{{ $job['case_number'] }}</div>
            <div class="rbs-job-title">{{ $job['title'] }}</div>
            <div class="rbs-job-meta">
              <span><i class="bi bi-clock me-1"></i>Updated {{ $job['updated_at'] }}</span>
            </div>
          </div>

          {{-- Right: badges + actions --}}
          <div class="rbs-job-right">
            <div class="rbs-badge-group">
              <span class="rbs-badge rbs-badge--{{ $this->getStatusClass($job['status']) }}">
                <span class="rbs-badge-dot"></span>{{ $job['status_label'] }}
              </span>
              @if($job['payment_status'])
                <span class="rbs-badge rbs-badge--{{ $this->getPaymentStatusClass($job['payment_status']) }} rbs-badge--outline">
                  <i class="bi bi-credit-card me-1"></i>{{ $job['payment_status_label'] }}
                </span>
              @endif
              @if($job['priority'] && $job['priority'] !== 'normal')
                <span class="rbs-badge rbs-badge--priority-{{ $job['priority'] }}">
                  <i class="bi bi-lightning-fill me-1"></i>{{ strtoupper($job['priority']) }}
                </span>
              @endif
            </div>

            <div class="rbs-job-actions">
              <button type="button" class="rbs-action-btn" wire:click="refresh" title="Refresh">
                <i class="bi bi-arrow-clockwise"></i>
                <span wire:loading.remove wire:target="refresh">Refresh</span>
                <span wire:loading wire:target="refresh">…</span>
              </button>
            </div>
          </div>
        </div>

        {{-- Dates row --}}
        @if($job['pickup_date'] || $job['delivery_date'])
          <div class="rbs-job-dates">
            @if($job['pickup_date'])
              <div class="rbs-date-chip">
                <i class="bi bi-calendar-check-fill"></i>
                <span>Drop-off: <strong>{{ $job['pickup_date'] }}</strong></span>
              </div>
            @endif
            @if($job['delivery_date'])
              <div class="rbs-date-chip">
                <i class="bi bi-truck"></i>
                <span>Est. Ready: <strong>{{ $job['delivery_date'] }}</strong></span>
              </div>
            @endif
          </div>
        @endif
      </div>

      {{-- ── Tabs ── --}}
      <div class="rbs-panel">

        {{-- Tab Nav --}}
        <div class="rbs-tab-nav">
          <button
            class="rbs-tab-btn {{ $activeTab === 'timeline' ? 'rbs-tab-btn--active' : '' }}"
            wire:click="setActiveTab('timeline')"
            type="button"
          >
            <i class="bi bi-activity"></i>
            <span>Timeline</span>
            @if(count($job['timeline']) > 0)
              <span class="rbs-tab-count">{{ count($job['timeline']) }}</span>
            @endif
          </button>
          <button
            class="rbs-tab-btn {{ $activeTab === 'details' ? 'rbs-tab-btn--active' : '' }}"
            wire:click="setActiveTab('details')"
            type="button"
          >
            <i class="bi bi-info-circle"></i>
            <span>Details</span>
          </button>
          <button
            class="rbs-tab-btn {{ $activeTab === 'message' ? 'rbs-tab-btn--active' : '' }}"
            wire:click="setActiveTab('message')"
            type="button"
          >
            <i class="bi bi-chat-dots"></i>
            <span>Message Us</span>
          </button>
        </div>

        <div class="rbs-tab-body">

          {{-- ── TIMELINE TAB ── --}}
          @if($activeTab === 'timeline')
            @if(count($job['timeline']) > 0)
              <div class="rbs-timeline">
                @foreach($job['timeline'] as $index => $event)
                  @php
                    $evType = $event['type'] ?? '';
                    $iconClass = match(true) {
                      str_contains($evType, 'created')    => 'bi-plus-circle-fill rbs-ev--created',
                      str_contains($evType, 'status')     => 'bi-arrow-repeat rbs-ev--status',
                      str_contains($evType, 'message')    => 'bi-chat-quote-fill rbs-ev--message',
                      str_contains($evType, 'attachment'),
                      str_contains($evType, 'file')       => 'bi-paperclip rbs-ev--attach',
                      str_contains($evType, 'payment')    => 'bi-credit-card-fill rbs-ev--payment',
                      str_contains($evType, 'note')       => 'bi-pencil-fill rbs-ev--note',
                      default                             => 'bi-dot rbs-ev--default',
                    };
                  @endphp
                  <div class="rbs-tl-item {{ $index === 0 ? 'rbs-tl-item--latest' : '' }}">
                    <div class="rbs-tl-icon"><i class="bi {{ $iconClass }}"></i></div>
                    <div class="rbs-tl-body">
                      <div class="rbs-tl-head">
                        <span class="rbs-tl-title">{{ $event['title'] }}</span>
                        <time class="rbs-tl-time" datetime="{{ $event['created_at_raw'] ?? '' }}">{{ $event['created_at'] }}</time>
                      </div>
                      @if($event['message'])
                        <p class="rbs-tl-msg">{{ $event['message'] }}</p>
                      @endif
                      @if($event['attachment'])
                        <a href="{{ $event['attachment']['url'] }}" target="_blank" rel="noopener" class="rbs-tl-file">
                          <i class="bi bi-file-earmark-arrow-down"></i>{{ $event['attachment']['filename'] }}
                        </a>
                      @endif
                    </div>
                  </div>
                @endforeach
              </div>
            @else
              <div class="rbs-empty">
                <div class="rbs-empty-icon"><i class="bi bi-hourglass"></i></div>
                <p class="rbs-empty-msg">No activity recorded yet. Check back soon.</p>
              </div>
            @endif
          @endif

          {{-- ── DETAILS TAB ── --}}
          @if($activeTab === 'details')
            <div class="rbs-details">

              @if(count($job['devices']) > 0)
                <section class="rbs-section">
                  <h3 class="rbs-section-title"><i class="bi bi-cpu"></i>Devices</h3>
                  <div class="rbs-devices">
                    @foreach($job['devices'] as $device)
                      <div class="rbs-device">
                        <div class="rbs-device-thumb"><i class="bi bi-laptop"></i></div>
                        <div class="rbs-device-data">
                          <div class="rbs-device-name">
                            {{ $device['label'] ?: collect([$device['brand_name'], $device['device_name']])->filter()->implode(' ') ?: 'Device' }}
                          </div>
                          @if($device['type_name'] || $device['brand_name'])
                            <div class="rbs-device-sub">{{ collect([$device['type_name'], $device['brand_name']])->filter()->implode(' · ') }}</div>
                          @endif
                          @if($device['serial'])
                            <div class="rbs-device-serial"><i class="bi bi-upc me-1"></i><code>{{ $device['serial'] }}</code></div>
                          @endif
                        </div>
                      </div>
                    @endforeach
                  </div>
                </section>
              @endif

              @if(count($job['items']) > 0)
                <section class="rbs-section">
                  <h3 class="rbs-section-title"><i class="bi bi-tools"></i>Services &amp; Parts</h3>
                  <div class="rbs-items-list">
                    @foreach($job['items'] as $item)
                      <div class="rbs-item-row">
                        <div class="rbs-item-icon">
                          <i class="bi {{ $item['item_type'] === 'part' ? 'bi-box-seam' : 'bi-wrench-adjustable' }}"></i>
                        </div>
                        <div class="rbs-item-name">{{ $item['name'] }}</div>
                        <div class="rbs-item-type">{{ ucfirst($item['item_type']) }}</div>
                        <div class="rbs-item-qty">×{{ $item['qty'] }}</div>
                      </div>
                    @endforeach
                  </div>
                </section>
              @endif

              @if($job['case_detail'])
                <section class="rbs-section">
                  <h3 class="rbs-section-title"><i class="bi bi-file-text"></i>Notes</h3>
                  <div class="rbs-notes">{{ $job['case_detail'] }}</div>
                </section>
              @endif

              @if(count($job['devices']) === 0 && count($job['items']) === 0 && !$job['case_detail'])
                <div class="rbs-empty">
                  <div class="rbs-empty-icon"><i class="bi bi-inbox"></i></div>
                  <p class="rbs-empty-msg">No detail information on file.</p>
                </div>
              @endif
            </div>
          @endif

          {{-- ── MESSAGE TAB ── --}}
          @if($activeTab === 'message')
            <div class="rbs-msg-tab">

              <div class="rbs-msg-intro">
                <div class="rbs-msg-intro-icon"><i class="bi bi-headset"></i></div>
                <div>
                  <p class="rbs-msg-intro-title">Got a question? We’re here to help.</p>
                  <p class="rbs-msg-intro-desc">Send us a message and we’ll get back to you as soon as possible.</p>
                </div>
              </div>

              @if($messageSuccess)
                <div class="rbs-alert rbs-alert--success" role="alert">
                  <i class="bi bi-check-circle-fill"></i><span>{{ $messageSuccess }}</span>
                </div>
              @endif

              @if($messageError)
                <div class="rbs-alert rbs-alert--danger" role="alert">
                  <i class="bi bi-exclamation-circle-fill"></i><span>{{ $messageError }}</span>
                </div>
              @endif

              <form wire:submit.prevent="sendMessage" class="rbs-msg-form">
                <div class="rbs-field">
                  <label class="rbs-label">Your Message</label>
                  <textarea
                    class="rbs-textarea"
                    wire:model.defer="messageBody"
                    rows="4"
                    placeholder="Ask about your repair, provide extra details, or share any concerns…"
                  ></textarea>
                </div>

                <div class="rbs-field">
                  <label class="rbs-label">Attach a File <span class="rbs-label-opt">(optional)</span></label>
                  @if($attachment)
                    <div class="rbs-file-preview">
                      <i class="bi bi-file-earmark-check-fill"></i>
                      <div class="rbs-file-preview-info">
                        <span class="rbs-file-name">{{ $attachment->getClientOriginalName() }}</span>
                        <span class="rbs-file-size">{{ number_format($attachment->getSize() / 1024, 1) }} KB</span>
                      </div>
                      <button type="button" class="rbs-file-remove" wire:click="removeAttachment">
                        <i class="bi bi-x-lg"></i>
                      </button>
                    </div>
                  @else
                    <label class="rbs-dropzone" for="rbs-file-input">
                      <i class="bi bi-cloud-arrow-up rbs-dropzone-icon"></i>
                      <span class="rbs-dropzone-text">Click to select a file</span>
                      <span class="rbs-dropzone-hint">Images, PDF, DOC, TXT · Max 10 MB</span>
                      <input
                        id="rbs-file-input"
                        type="file"
                        class="rbs-file-hidden"
                        wire:model="attachment"
                        accept="image/*,.pdf,.doc,.docx,.txt"
                      >
                    </label>
                  @endif
                  @error('attachment')
                    <p class="rbs-field-error">{{ $message }}</p>
                  @enderror
                </div>

                <button
                  type="submit"
                  class="rbs-send-btn"
                  wire:loading.attr="disabled"
                  wire:target="sendMessage"
                  {{ ($messageBody === '' && !$attachment) ? 'disabled' : '' }}
                >
                  <span wire:loading.remove wire:target="sendMessage">
                    <i class="bi bi-send-fill me-2"></i>Send Message
                  </span>
                  <span wire:loading wire:target="sendMessage" class="rbs-loading-label">
                    <span class="rbs-spinner"></span>Sending…
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
    <div class="rbs-result">
      <div class="rbs-estimate-card">
        <div class="rbs-estimate-icon-wrap"><i class="bi bi-file-earmark-text-fill"></i></div>
        <div class="rbs-estimate-case">{{ $estimate['case_number'] }}</div>
        <div class="rbs-estimate-title-text">{{ $estimate['title'] }}</div>
        <span class="rbs-badge rbs-badge--warning"><span class="rbs-badge-dot"></span>{{ $estimate['status_label'] }}</span>
        <p class="rbs-estimate-note">This is an estimate. You can approve or reject it via the link in your email, or through the customer portal.</p>
        <button type="button" class="rbs-action-btn mt-3" wire:click="refresh"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
      </div>
    </div>
  @endif

  {{-- ══════════ EMPTY STATE ══════════ --}}
  @if(!$entityType && !$errorMessage && $caseNumber === '')
    <div class="rbs-start-state">
      <div class="rbs-start-grid">
        <div class="rbs-start-step">
          <div class="rbs-start-num">1</div>
          <p>Check your confirmation email for the case number.</p>
        </div>
        <div class="rbs-start-step">
          <div class="rbs-start-num">2</div>
          <p>Enter it in the search box above.</p>
        </div>
        <div class="rbs-start-step">
          <div class="rbs-start-num">3</div>
          <p>View your repair status and send us messages.</p>
        </div>
      </div>
    </div>
  @endif

</div>

