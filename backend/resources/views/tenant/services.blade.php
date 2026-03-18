@php
    $tenantSlug = $tenantSlug ?? $business ?? null;
    $tenant = $tenant ?? null;
    $activePage = 'services';
    $isSubdomain = request()->routeIs('tenant.subdomain.*');
    $tenantRoutePrefix = $isSubdomain ? 'tenant.subdomain' : 'tenant';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $tenant->name ?? 'RepairBuddy' }} — Our Services</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/tenant-public.css') }}">
    <style>
        /* PAGE HEADER */
        .page-hero{padding:60px 28px 40px;text-align:center;position:relative;overflow:hidden}
        .page-hero::before{content:'';position:absolute;top:-100px;right:-200px;width:600px;height:600px;border-radius:50%;background:radial-gradient(circle,rgba(253,103,66,.05) 0%,transparent 60%);pointer-events:none}
        .page-hero h1{font-size:36px;font-weight:800;letter-spacing:-.02em;margin-bottom:8px}
        .page-hero h1 span{background:linear-gradient(135deg,var(--rb-blue),var(--rb-orange));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
        .page-hero p{font-size:16px;color:var(--rb-text-2);max-width:500px;margin:0 auto}

        /* FILTER TABS */
        .filter-section{max-width:1200px;margin:0 auto;padding:0 28px}
        .filter-tabs{display:flex;gap:8px;flex-wrap:wrap;justify-content:center;padding:20px 0 40px}
        .filter-tab{padding:8px 20px;border-radius:99px;font-size:13px;font-weight:600;cursor:pointer;border:1.5px solid var(--rb-border);background:var(--rb-surface);color:var(--rb-text-2);transition:all .2s}
        .filter-tab:hover{border-color:#cbd5e1;background:var(--rb-surface-2)}
        .filter-tab.active{border-color:var(--rb-blue);background:rgba(6,62,112,.06);color:var(--rb-blue)}

        /* SERVICES GRID */
        .section{max-width:1200px;margin:0 auto;padding:0 28px 60px}
        .category-section{margin-bottom:48px}
        .category-header{display:flex;align-items:center;gap:12px;margin-bottom:20px}
        .category-icon{width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px}
        .category-name{font-size:20px;font-weight:800;letter-spacing:-.01em}
        .category-count{font-size:12px;color:var(--rb-text-3);font-weight:500;margin-left:4px}
        .services-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
        .svc-card{background:var(--rb-surface);border:1px solid var(--rb-border);border-radius:20px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.04);transition:all .3s;cursor:pointer;text-decoration:none;color:inherit}
        .svc-card:hover{transform:translateY(-4px);box-shadow:0 12px 32px rgba(0,0,0,.08)}
        .svc-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:14px}
        .svc-icon svg{width:20px;height:20px}
        .svc-card h3{font-size:15px;font-weight:700;margin-bottom:4px}
        .svc-card p{font-size:12px;color:var(--rb-text-2);line-height:1.6}
        .svc-meta{display:flex;justify-content:space-between;align-items:center;margin-top:12px;padding-top:12px;border-top:1px solid var(--rb-border);font-size:11px}
        .svc-price{font-weight:800;color:var(--rb-text);font-size:15px}
        .svc-time{color:var(--rb-text-3);font-weight:500}
        .svc-book-btn{margin-top:14px;width:100%;padding:10px;border-radius:12px;border:1.5px solid var(--rb-blue);background:none;color:var(--rb-blue);font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;transition:all .2s;display:block;text-align:center;text-decoration:none}
        .svc-book-btn:hover{background:var(--rb-blue);color:#fff}

        /* CTA */
        .cta-banner{max-width:1200px;margin:0 auto 48px;padding:0 28px}
        .cta-inner{background:linear-gradient(135deg,var(--rb-blue) 0%,#0a5fa3 50%,var(--rb-orange) 100%);border-radius:24px;padding:52px 40px;text-align:center;color:#fff;position:relative;overflow:hidden}
        .cta-inner::after{content:'';position:absolute;top:-50px;right:-50px;width:200px;height:200px;border-radius:50%;background:rgba(255,255,255,.05)}
        .cta-inner h2{font-size:28px;font-weight:800;margin-bottom:12px;letter-spacing:-.02em;position:relative;z-index:1}
        .cta-inner p{font-size:15px;opacity:.85;margin-bottom:28px;position:relative;z-index:1}
        .btn-white{background:#fff;color:var(--rb-blue);font-weight:700;box-shadow:0 4px 14px rgba(0,0,0,.15);position:relative;z-index:1}
        .btn-white:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,.2)}

        @media(max-width:900px){.services-grid{grid-template-columns:1fr 1fr}}
        @media(max-width:768px){
            .services-grid{grid-template-columns:1fr}
            .page-hero h1{font-size:28px}
        }
    </style>
</head>
<body>
    @include('tenant.partials.tenant-nav', [
        'tenantSlug' => $tenantSlug,
        'tenant' => $tenant,
        'activePage' => $activePage
    ])

    <!-- PAGE HEADER -->
    <section class="page-hero">
        <h1>Our <span>Services</span></h1>
        <p>Browse our full catalog of professional repair services. Quality parts, certified technicians, and fast turnaround.</p>
    </section>

    <!-- FILTER TABS -->
    <div class="filter-section">
        <div class="filter-tabs">
            <div class="filter-tab active" onclick="filterCategory(this,'all')">All Services</div>
            <div class="filter-tab" onclick="filterCategory(this,'phone')">📱 Smartphones</div>
            <div class="filter-tab" onclick="filterCategory(this,'tablet')">📋 Tablets</div>
            <div class="filter-tab" onclick="filterCategory(this,'laptop')">💻 Laptops</div>
            <div class="filter-tab" onclick="filterCategory(this,'watch')">⌚ Smartwatches</div>
            <div class="filter-tab" onclick="filterCategory(this,'console')">🎮 Consoles</div>
        </div>
    </div>

    @php
    $categoryColors = [
        ['bg' => 'rgba(253,103,66,.08)', 'color' => 'var(--rb-orange)'],
        ['bg' => 'rgba(6,62,112,.06)', 'color' => 'var(--rb-blue)'],
        ['bg' => 'rgba(43,138,62,.06)', 'color' => '#2b8a3e'],
        ['bg' => 'rgba(112,72,232,.06)', 'color' => '#7048e8'],
        ['bg' => 'rgba(230,119,0,.06)', 'color' => '#e67700'],
        ['bg' => 'rgba(25,113,194,.06)', 'color' => '#1971c2'],
    ];
    $colorIndex = 0;
    $currency = $tenant->currency ?? 'USD';
    
    $currencySymbols = ['USD' => '$', 'EUR' => '€', 'GBP' => '£', 'CAD' => 'CA$', 'AUD' => 'A$', 'PHP' => '₱'];
    $currencySymbol = $currencySymbols[$currency] ?? $currency;
@endphp

    <!-- SERVICES BY CATEGORY -->
    <div class="section">
        @forelse($serviceTypes as $serviceType)
            @php
                $color = $categoryColors[$colorIndex % count($categoryColors)];
                $colorIndex++;
                $serviceCount = $serviceType->services->count();
            @endphp
            @if($serviceCount > 0)
            <div class="category-section" data-cat="all">
                <div class="category-header">
                    <div class="category-icon" style="background:{{ $color['bg'] }};color:{{ $color['color'] }}">🔧</div>
                    <div><span class="category-name">{{ $serviceType->name }}</span><span class="category-count">— {{ $serviceCount }} service{{ $serviceCount !== 1 ? 's' : '' }}</span></div>
                </div>
                <div class="services-grid">
                    @foreach($serviceType->services as $service)
                        @php
                            $svcColor = $categoryColors[($colorIndex + $loop->index) % count($categoryColors)];
                        @endphp
                        <div class="svc-card">
                            <div class="svc-icon" style="background:{{ $svcColor['bg'] }};color:{{ $svcColor['color'] }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg></div>
                            <h3>{{ $service->name }}</h3>
                            <p>{{ $service->description ?? 'Professional repair service.' }}</p>
                            <div class="svc-meta">
                                <span class="svc-price">
                                    @if($service->base_price_amount_cents)
                                        @php $svcCurrency = $service->base_price_currency ?? $currency; $svcSymbol = $currencySymbols[$svcCurrency] ?? $svcCurrency; @endphp
                                        From {{ $svcSymbol }}{{ number_format($service->base_price_amount_cents / 100, 2) }}
                                    @else
                                        Contact for price
                                    @endif
                                </span>
                                @if($service->time_required)
                                    <span class="svc-time">⏱ {{ $service->time_required }}</span>
                                @endif
                            </div>
                            <a href="{{ route($tenantRoutePrefix . '.booking.show', ['business' => $tenantSlug, 'service' => $service->id]) }}" class="svc-book-btn">Book This Service →</a>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        @empty
            <!-- No service types configured -->
        @endforelse

        @if($untypedServices->count() > 0)
            @php
                $color = $categoryColors[$colorIndex % count($categoryColors)];
                $colorIndex++;
            @endphp
            <div class="category-section" data-cat="all">
                <div class="category-header">
                    <div class="category-icon" style="background:{{ $color['bg'] }};color:{{ $color['color'] }}">🔧</div>
                    <div><span class="category-name">Other Services</span><span class="category-count">— {{ $untypedServices->count() }} service{{ $untypedServices->count() !== 1 ? 's' : '' }}</span></div>
                </div>
                <div class="services-grid">
                    @foreach($untypedServices as $service)
                        @php
                            $svcColor = $categoryColors[($colorIndex + $loop->index) % count($categoryColors)];
                        @endphp
                        <div class="svc-card">
                            <div class="svc-icon" style="background:{{ $svcColor['bg'] }};color:{{ $svcColor['color'] }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg></div>
                            <h3>{{ $service->name }}</h3>
                            <p>{{ $service->description ?? 'Professional repair service.' }}</p>
                            <div class="svc-meta">
                                <span class="svc-price">
                                    @if($service->base_price_amount_cents)
                                        @php $svcCurrency = $service->base_price_currency ?? $currency; $svcSymbol = $currencySymbols[$svcCurrency] ?? $svcCurrency; @endphp
                                        From {{ $svcSymbol }}{{ number_format($service->base_price_amount_cents / 100, 2) }}
                                    @else
                                        Contact for price
                                    @endif
                                </span>
                                @if($service->time_required)
                                    <span class="svc-time">⏱ {{ $service->time_required }}</span>
                                @endif
                            </div>
                            <a href="{{ route($tenantRoutePrefix . '.booking.show', ['business' => $tenantSlug, 'service' => $service->id]) }}" class="svc-book-btn">Book This Service →</a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if($serviceTypes->isEmpty() && $untypedServices->isEmpty())
            <div class="category-section" style="text-align:center;padding:60px 20px;">
                <div class="category-icon" style="background:rgba(6,62,112,.06);color:var(--rb-blue);margin:0 auto 16px;">📋</div>
                <h3 style="font-size:20px;font-weight:700;margin-bottom:8px;">No Services Available</h3>
                <p style="color:var(--rb-text-2);">This business hasn't added any services yet. Please check back later or contact them directly.</p>
            </div>
        @endif
    </div>

    <!-- CTA -->
    <div class="cta-banner">
        <div class="cta-inner">
            <h2>Can't find what you need?</h2>
            <p>Contact us for a custom repair quote. We handle all types of electronics.</p>
            <a href="{{ route($tenantRoutePrefix . '.booking.show', ['business' => $tenantSlug]) }}" class="btn btn-white btn-lg">Get a Custom Quote →</a>
        </div>
    </div>

    @include('tenant.partials.tenant-footer', [
        'tenantSlug' => $tenantSlug,
        'tenant' => $tenant
    ])

    <script>
        function filterCategory(el, cat) {
            document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
            el.classList.add('active');
            document.querySelectorAll('.category-section').forEach(s => {
                if (cat === 'all') { s.style.display = ''; return; }
                s.style.display = s.dataset.cat.includes(cat) ? '' : 'none';
            });
        }
    </script>
</body>
</html>
