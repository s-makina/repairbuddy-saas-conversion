<div class="pp-section">
  {{-- Header --}}
  <div class="pp-hero">
    <div class="pp-hero-icon">
      <i class="bi bi-box-seam"></i>
    </div>
    <p class="pp-kicker">{{ $tenantName ?: 'RepairBuddy' }} Inventory</p>
    <h1 class="pp-hero-title">Parts &amp; Components</h1>
    <p class="pp-hero-subtitle">Explore the parts and components we stock — batteries, screens, motherboards, and more, ready for your repair.</p>
  </div>

  {{-- Filters --}}
  <div class="pp-toolbar">
    <div class="pp-search-wrap">
      <i class="bi bi-search pp-search-icon"></i>
      <input
        type="text"
        wire:model.live.debounce.300ms="search"
        placeholder="Search parts…"
        class="pp-search-input"
      >
    </div>

    <div class="pp-filter-group">
      @if(count($partTypes) > 0)
        <select wire:model.live="filterTypeId" class="pp-filter-select">
          <option value="">All Types</option>
          @foreach($partTypes as $type)
            <option value="{{ $type['id'] }}">{{ $type['name'] }}</option>
          @endforeach
        </select>
      @endif

      @if(count($partBrands) > 0)
        <select wire:model.live="filterBrandId" class="pp-filter-select">
          <option value="">All Brands</option>
          @foreach($partBrands as $brand)
            <option value="{{ $brand['id'] }}">{{ $brand['name'] }}</option>
          @endforeach
        </select>
      @endif
    </div>
  </div>

  {{-- Results --}}
  <div wire:loading.class="pp-loading" class="pp-grid">
    @forelse($filteredParts as $part)
      <div class="pp-card" wire:key="part-{{ $part['id'] }}">
        <div class="pp-card-body">
          <div class="pp-card-header-row">
            <h3 class="pp-card-title">{{ $part['name'] }}</h3>
            @if($part['brand_name'])
              <span class="pp-badge pp-badge-accent">{{ $part['brand_name'] }}</span>
            @endif
          </div>

          @if($part['type_name'])
            <span class="pp-badge pp-badge-muted" style="margin-bottom: .5rem; display: inline-block;">{{ $part['type_name'] }}</span>
          @endif

          @if($part['core_features'])
            <p class="pp-card-desc">{{ \Illuminate\Support\Str::limit($part['core_features'], 120) }}</p>
          @endif

          <div class="pp-card-meta">
            @if($part['price_amount_cents'])
              <span class="pp-meta-item pp-meta-price">
                <i class="bi bi-tag"></i>
                {{ $part['price_currency'] === 'USD' ? '$' : $part['price_currency'] . ' ' }}{{ number_format($part['price_amount_cents'] / 100, 2) }}
              </span>
            @endif

            @if($part['sku'])
              <span class="pp-meta-item">
                <i class="bi bi-upc-scan"></i>
                {{ $part['sku'] }}
              </span>
            @endif

            @if($part['warranty'])
              <span class="pp-meta-item">
                <i class="bi bi-shield-check"></i>
                {{ $part['warranty'] }}
              </span>
            @endif
          </div>

          <div class="pp-card-footer-row">
            @if($part['capacity'])
              <span class="pp-badge pp-badge-info"><i class="bi bi-hdd"></i> {{ $part['capacity'] }}</span>
            @endif

            @if($part['stock'] !== null)
              @if($part['stock'] > 0)
                <span class="pp-badge pp-badge-success"><i class="bi bi-check-circle"></i> In Stock ({{ $part['stock'] }})</span>
              @else
                <span class="pp-badge pp-badge-danger"><i class="bi bi-x-circle"></i> Out of Stock</span>
              @endif
            @endif
          </div>
        </div>
      </div>
    @empty
      <div class="pp-empty">
        <i class="bi bi-inbox"></i>
        <p>No parts found{{ $search ? ' matching "' . $search . '"' : '' }}.</p>
      </div>
    @endforelse
  </div>

  @if(count($filteredParts) > 0)
    <div class="pp-results-count">
      Showing {{ count($filteredParts) }} part{{ count($filteredParts) !== 1 ? 's' : '' }}
    </div>
  @endif
</div>
