<div class="pp-section">
  {{-- Header --}}
  <div class="pp-hero">
    <div class="pp-hero-icon">
      <i class="bi bi-tools"></i>
    </div>
    <p class="pp-kicker">{{ $tenantName ?: 'RepairBuddy' }} Services</p>
    <h1 class="pp-hero-title">Our Services</h1>
    <p class="pp-hero-subtitle">Browse the full range of repair and maintenance services we offer — from screen replacements to motherboard diagnostics.</p>
  </div>

  {{-- Filters --}}
  <div class="pp-toolbar">
    <div class="pp-search-wrap">
      <i class="bi bi-search pp-search-icon"></i>
      <input
        type="text"
        wire:model.live.debounce.300ms="search"
        placeholder="Search services…"
        class="pp-search-input"
      >
    </div>

    @if(count($serviceTypes) > 0)
      <select wire:model.live="filterTypeId" class="pp-filter-select">
        <option value="">All Categories</option>
        @foreach($serviceTypes as $type)
          <option value="{{ $type['id'] }}">{{ $type['name'] }}</option>
        @endforeach
      </select>
    @endif
  </div>

  {{-- Results --}}
  <div wire:loading.class="pp-loading" class="pp-grid">
    @forelse($filteredServices as $service)
      <div class="pp-card" wire:key="svc-{{ $service['id'] }}">
        <div class="pp-card-body">
          <div class="pp-card-header-row">
            <h3 class="pp-card-title">{{ $service['name'] }}</h3>
            @if($service['type_name'])
              <span class="pp-badge pp-badge-muted">{{ $service['type_name'] }}</span>
            @endif
          </div>

          @if($service['description'])
            <p class="pp-card-desc">{{ \Illuminate\Support\Str::limit($service['description'], 120) }}</p>
          @endif

          <div class="pp-card-meta">
            @if($service['base_price_amount_cents'])
              <span class="pp-meta-item pp-meta-price">
                <i class="bi bi-tag"></i>
                {{ $service['base_price_currency'] === 'USD' ? '$' : $service['base_price_currency'] . ' ' }}{{ number_format($service['base_price_amount_cents'] / 100, 2) }}
              </span>
            @endif

            @if($service['time_required'])
              <span class="pp-meta-item">
                <i class="bi bi-clock"></i>
                {{ $service['time_required'] }}
              </span>
            @endif

            @if($service['warranty'])
              <span class="pp-meta-item">
                <i class="bi bi-shield-check"></i>
                {{ $service['warranty'] }}
              </span>
            @endif
          </div>

          <div class="pp-card-badges">
            @if($service['pick_up_delivery_available'])
              <span class="pp-badge pp-badge-success"><i class="bi bi-truck"></i> Pick-up / Delivery</span>
            @endif
            @if($service['laptop_rental_available'])
              <span class="pp-badge pp-badge-info"><i class="bi bi-laptop"></i> Rental Available</span>
            @endif
          </div>
        </div>
      </div>
    @empty
      <div class="pp-empty">
        <i class="bi bi-inbox"></i>
        <p>No services found{{ $search ? ' matching "' . $search . '"' : '' }}.</p>
      </div>
    @endforelse
  </div>

  @if(count($filteredServices) > 0)
    <div class="pp-results-count">
      Showing {{ count($filteredServices) }} service{{ count($filteredServices) !== 1 ? 's' : '' }}
    </div>
  @endif
</div>
