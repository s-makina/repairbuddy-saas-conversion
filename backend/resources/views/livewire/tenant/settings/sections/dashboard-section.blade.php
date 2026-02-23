{{-- Dashboard Section — navigation cards matching original wcrb_dashboard_nav style --}}
<div wire:init="loadStats">

    {{-- ── Page Heading ── --}}
    <h2 class="st-dash-section-title">Dashboard</h2>
    <p style="font-size:.82rem;color:var(--st-text-3);margin:-0.75rem 0 1.5rem;">
        Quick access to all modules in your RepairBuddy workspace.
    </p>

    {{-- ── Navigation Cards ── --}}
    <div class="st-dash-nav-grid">
        @foreach ($this->navItems as $item)
            <a href="{{ $item['route'] }}" class="st-dash-nav-card">
                <div class="st-dash-nav-card-img">
                    <img src="{{ asset('repairbuddy/plugin/assets/admin/images/icons/' . $item['image']) }}"
                         alt="{{ $item['label'] }}" loading="lazy">
                </div>
                <p class="st-dash-nav-card-label">{{ $item['label'] }}</p>
            </a>
        @endforeach
    </div>

    {{-- ── Job Status Summary ── --}}
    <h3 style="font-size:.88rem;font-weight:700;color:var(--st-text);margin:0 0 .65rem;">Jobs by Status</h3>
    <div class="st-widget-grid">
        @if (! $statsLoaded)
            <p style="font-size:.82rem;color:var(--st-text-3);">Loading job status summary…</p>
        @else
            @forelse ($jobStatusList as $status)
                <div class="st-widget">
                    <a href="{{ $status['link'] }}">
                        <div class="st-widget-body">
                            <div class="st-widget-media">
                                <div class="st-widget-icon">
                                    <img src="{{ asset('repairbuddy/plugin/assets/admin/images/icons/jobs.png') }}"
                                         alt="" loading="lazy">
                                </div>
                                <div class="st-widget-info">
                                    <div class="st-widget-title">{{ $status['label'] }}</div>
                                    <div class="st-widget-number">{{ number_format($status['count']) }} Jobs</div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @empty
                <p style="font-size:.82rem;color:var(--st-text-3);">No job statuses configured yet.</p>
            @endforelse
        @endif
    </div>

    {{-- ── Estimate Status Summary ── --}}
    <h3 style="font-size:.88rem;font-weight:700;color:var(--st-text);margin:1.25rem 0 .65rem;">Estimates by Status</h3>
    <div class="st-widget-grid">
        @php
            $estimateItems = [
                ['key' => 'pending',  'label' => 'Pending'],
                ['key' => 'approved', 'label' => 'Approved'],
                ['key' => 'rejected', 'label' => 'Rejected'],
            ];
            $eCounts = $estimateCountList;
            $slug    = $tenant->slug;
        @endphp
        @foreach ($estimateItems as $est)
            @php
                $estUrl = route('tenant.estimates.index', ['business' => $slug])
                          . '?status=' . urlencode($est['key']);
            @endphp
            <div class="st-widget">
                <a href="{{ $estUrl }}">
                    <div class="st-widget-body">
                        <div class="st-widget-media">
                            <div class="st-widget-icon">
                                <img src="{{ asset('repairbuddy/plugin/assets/admin/images/icons/estimate.png') }}"
                                     alt="" loading="lazy">
                            </div>
                            <div class="st-widget-info">
                                <div class="st-widget-title">{{ $est['label'] }}</div>
                                <div class="st-widget-number">{{ number_format($eCounts[$est['key']] ?? 0) }} Estimates</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

</div>
