@php
  $currentRoute = \Illuminate\Support\Facades\Route::currentRouteName();
  $rp = str_starts_with($currentRoute ?? '', 'tenant.subdomain.') ? 'tenant.subdomain.' : 'tenant.';
@endphp
<div class="pp-section">
  {{-- Header --}}
  <div class="pp-hero">
    <div class="pp-hero-icon">
      <i class="bi bi-tools"></i>
    </div>
    <h1 class="pp-hero-title">Our <span class="pp-hero-gradient">Services</span></h1>
    <p class="pp-hero-subtitle">Browse our full catalog of professional repair services. Quality parts, certified technicians, and fast turnaround.</p>
  </div>

  {{-- Filter Tabs --}}
  @if(count($serviceTypes) > 0)
    <div class="pp-filter-tabs">
      <button
        type="button"
        class="pp-filter-tab {{ $filterTypeId === '' ? 'active' : '' }}"
        wire:click="$set('filterTypeId', '')"
      >All Services</button>
      @foreach($serviceTypes as $type)
        <button
          type="button"
          class="pp-filter-tab {{ $filterTypeId === (string) $type['id'] ? 'active' : '' }}"
          wire:click="$set('filterTypeId', '{{ $type['id'] }}')"
        >{{ $type['name'] }}</button>
      @endforeach
    </div>
  @endif

  {{-- Search --}}
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
  </div>

  {{-- Services by Category --}}
  <div wire:loading.class="pp-loading">
    @forelse($filteredGroupedServices as $group)
      <div class="pp-category-section" wire:key="cat-{{ $group['type_id'] ?? 'other' }}">
        <div class="pp-category-header">
          <div class="pp-category-icon">
            <i class="bi bi-tools"></i>
          </div>
          <div>
            <span class="pp-category-name">{{ $group['type_name'] }}</span>
            <span class="pp-category-count">— {{ count($group['services']) }} service{{ count($group['services']) !== 1 ? 's' : '' }}</span>
          </div>
        </div>

        <div class="pp-services-grid">
          @foreach($group['services'] as $service)
            <div class="pp-svc-card" wire:key="svc-{{ $service['id'] }}">
              <div class="pp-svc-icon">
                <i class="bi bi-wrench-adjustable"></i>
              </div>
              <h3 class="pp-svc-card-title">{{ $service['name'] }}</h3>
              @if($service['description'])
                <p class="pp-svc-card-desc">{{ \Illuminate\Support\Str::limit($service['description'], 120) }}</p>
              @endif
              <div class="pp-svc-meta">
                @if($service['base_price_amount'])
                  <span class="pp-svc-price">
                    From {{ $service['base_price_currency'] === 'USD' ? '$' : $service['base_price_currency'] . ' ' }}{{ number_format($service['base_price_amount'], 2) }}
                  </span>
                @endif
                @if($service['time_required'])
                  <span class="pp-svc-time">
                    <i class="bi bi-clock"></i> {{ $service['time_required'] }}
                  </span>
                @endif
              </div>
              @if($service['warranty'])
                <div class="pp-svc-warranty">
                  <i class="bi bi-shield-check"></i> {{ $service['warranty'] }}
                </div>
              @endif
              <a href="{{ route($rp . 'booking.show', ['business' => $business]) }}" class="pp-svc-book-btn">
                Book This Service →
              </a>
            </div>
          @endforeach
        </div>
      </div>
    @empty
      <div class="pp-empty">
        <i class="bi bi-inbox"></i>
        <p>No services found{{ $search ? ' matching "' . $search . '"' : '' }}.</p>
      </div>
    @endforelse
  </div>

  {{-- CTA Banner --}}
  @if(count($filteredGroupedServices) > 0)
    <div class="pp-cta-banner">
      <h2>Can't find what you need?</h2>
      <p>Contact us for a custom repair quote. We handle all types of electronics.</p>
      <a href="{{ route($rp . 'booking.show', ['business' => $business]) }}" class="pp-cta-btn">Get a Custom Quote →</a>
    </div>
  @endif
</div>
