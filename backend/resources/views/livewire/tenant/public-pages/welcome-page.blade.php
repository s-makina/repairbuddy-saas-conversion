<div data-welcome-page>
@php
    $tenantSlug = $tenantSlug ?? $business;
    $tenant = $tenant ?? null;
    $activePage = 'home';
    $isSubdomain = request()->routeIs('tenant.subdomain.*');
    $tenantRoutePrefix = $isSubdomain ? 'tenant.subdomain' : 'tenant';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $tenant->name ?? 'RepairBuddy' }} — Home</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/tenant-public.css') }}">
    <style>
        :root {
            --rb-blue: {{ $primaryColor }};
            --rb-blue-light: {{ $primaryColor }};
            --rb-orange: {{ $secondaryColor }};
            --rb-orange-light: {{ $secondaryColor }};
        }

        /* HERO */
        .hero { padding: 80px 28px 60px; text-align: center; position: relative; overflow: hidden }
        .hero::before { content: ''; position: absolute; top: -100px; right: -200px; width: 600px; height: 600px; border-radius: 50%; background: radial-gradient(circle, rgba(253,103,66,.06) 0%, transparent 60%); pointer-events: none }
        .hero::after { content: ''; position: absolute; bottom: -100px; left: -200px; width: 500px; height: 500px; border-radius: 50%; background: radial-gradient(circle, rgba(6,62,112,.04) 0%, transparent 60%); pointer-events: none }
        .hero-inner { max-width: 700px; margin: 0 auto; position: relative; z-index: 1 }
        .hero-badge { display: inline-flex; align-items: center; gap: 8px; padding: 6px 16px; border-radius: 99px; background: rgba(6,62,112,.06); color: var(--rb-blue); font-size: 12px; font-weight: 700; margin-bottom: 20px }
        .hero-badge::before { content: ''; width: 8px; height: 8px; border-radius: 50%; background: var(--rb-orange); animation: pulse 2s infinite }
        @keyframes pulse { 0%, 100% { opacity: 1 } 50% { opacity: .4 } }
        .hero h1 { font-size: 48px; font-weight: 800; letter-spacing: -.03em; line-height: 1.15; margin-bottom: 18px; color: var(--rb-text) }
        .hero h1 span { background: linear-gradient(135deg, var(--rb-blue), var(--rb-orange)); -webkit-background-clip: text; -webkit-text-fill-color: transparent }
        .hero p { font-size: 17px; color: var(--rb-text-2); max-width: 500px; margin: 0 auto 32px; line-height: 1.7 }
        .hero-ctas { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap }
        .hero-stats { display: flex; gap: 40px; justify-content: center; margin-top: 48px; flex-wrap: wrap }
        .h-stat { text-align: center }
        .h-stat-val { font-size: 28px; font-weight: 800; color: var(--rb-text); letter-spacing: -.02em }
        .h-stat-lbl { font-size: 12px; color: var(--rb-text-3); font-weight: 500; margin-top: 2px }

        /* SERVICES */
        .section { max-width: 1200px; margin: 0 auto; padding: 60px 28px }
        .sec-header { text-align: center; margin-bottom: 40px }
        .sec-label { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--rb-orange); margin-bottom: 8px }
        .sec-title { font-size: 30px; font-weight: 800; letter-spacing: -.02em }
        .sec-sub { font-size: 15px; color: var(--rb-text-2); margin-top: 8px; max-width: 500px; margin-left: auto; margin-right: auto }
        .services-grid { display: flex; flex-wrap: wrap; justify-content: center; gap: 20px; max-width: 1000px; margin: 0 auto }
        .svc-card { width: 300px; flex-shrink: 0; background: var(--rb-surface); border: 1px solid var(--rb-border); border-radius: 20px; padding: 28px; box-shadow: 0 1px 3px rgba(0,0,0,.04); transition: all .3s; cursor: pointer }
        .svc-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,.08) }
        .svc-icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px }
        .svc-icon svg { width: 22px; height: 22px }
        .svc-card h3 { font-size: 16px; font-weight: 700; margin-bottom: 6px }
        .svc-card p { font-size: 13px; color: var(--rb-text-2); line-height: 1.6 }
        .svc-meta { display: flex; justify-content: space-between; align-items: center; margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--rb-border); font-size: 12px }
        .svc-price { font-weight: 800; color: var(--rb-text); font-size: 16px }
        .svc-time { color: var(--rb-text-3); font-weight: 500 }

        /* HOURS */
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px }
        .info-card { background: var(--rb-surface); border: 1px solid var(--rb-border); border-radius: 20px; padding: 28px; box-shadow: 0 1px 3px rgba(0,0,0,.04) }
        .info-card h3 { font-size: 16px; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; gap: 10px }
        .info-card h3 svg { width: 20px; height: 20px; color: var(--rb-blue) }
        .hours-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 13px; border-bottom: 1px solid var(--rb-border) }
        .hours-row:last-child { border: none }
        .hours-day { font-weight: 600; color: var(--rb-text) }
        .hours-time { color: var(--rb-text-2) }
        .hours-closed { color: var(--rb-orange); font-weight: 600 }
        .contact-row { display: flex; align-items: center; gap: 12px; padding: 10px 0; font-size: 13.5px; color: var(--rb-text-2) }
        .contact-row svg { width: 18px; height: 18px; color: var(--rb-blue); flex-shrink: 0 }
        .contact-row a { color: var(--rb-blue); font-weight: 600; text-decoration: none }
        .contact-row a:hover { text-decoration: underline }

        /* CTA BANNER */
        .cta-banner { max-width: 1200px; margin: 0 auto 48px; padding: 0 28px }
        .cta-inner { background: linear-gradient(135deg, var(--rb-blue) 0%, #0a5fa3 50%, var(--rb-orange) 100%); border-radius: 24px; padding: 52px 40px; text-align: center; color: #fff; position: relative; overflow: hidden }
        .cta-inner::after { content: ''; position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; border-radius: 50%; background: rgba(255,255,255,.05) }
        .cta-inner h2 { font-size: 28px; font-weight: 800; margin-bottom: 12px; letter-spacing: -.02em; position: relative; z-index: 1 }
        .cta-inner p { font-size: 15px; opacity: .85; margin-bottom: 28px; position: relative; z-index: 1 }
        .btn-white { background: #fff; color: var(--rb-blue); font-weight: 700; box-shadow: 0 4px 14px rgba(0,0,0,.15); position: relative; z-index: 1 }
        .btn-white:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.2) }

        .empty-state { text-align: center; padding: 40px 20px; color: var(--rb-text-3) }

        @media(max-width:900px) {
            .services-grid { grid-template-columns: 1fr }
            .info-grid { grid-template-columns: 1fr }
            .hero h1 { font-size: 34px }
            .hero-stats { gap: 24px }
        }
        @media(max-width:600px) {
            .nav-links a:not(.btn) { display: none }
            .hero { padding: 50px 20px 40px }
            .hero h1 { font-size: 28px }
        }
    </style>
</head>
<body>
    @include('tenant.partials.tenant-nav', [
        'tenantSlug' => $tenantSlug,
        'tenant' => $tenant,
        'activePage' => $activePage
    ])

    <!-- HERO -->
    <section class="hero">
        <div class="hero-inner">
            @if($heroBadge)
                <div class="hero-badge">{{ $heroBadge }}</div>
            @endif
            <h1>{!! $heroTitle !!}</h1>
            <p>{{ $heroSubtitle }}</p>
            <div class="hero-ctas">
                <a href="{{ route($tenantRoutePrefix.'.booking.show', ['business' => $tenantSlug]) }}" class="btn btn-orange btn-lg">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Book Repair
                </a>
                @if($contactPhone || $contactEmail)
                <a href="#hours" class="btn btn-outline btn-lg">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    Contact Us
                </a>
                @endif
            </div>
            @if($hasStats)
            <div class="hero-stats">
                @foreach($heroStats as $stat)
                    <div class="h-stat"><div class="h-stat-val">{{ $stat['value'] ?? '' }}</div><div class="h-stat-lbl">{{ $stat['label'] ?? '' }}</div></div>
                @endforeach
            </div>
            @endif
        </div>
    </section>

    @if($hasServices)
    <!-- SERVICES -->
    <section class="section" id="services">
        <div class="sec-header">
            <div class="sec-label">Our Services</div>
            <div class="sec-title">What we can fix</div>
            <p class="sec-sub">Professional repairs with quality parts and certified technicians.</p>
        </div>
        <div class="services-grid">
            @foreach($services as $service)
            <div class="svc-card">
                <div class="svc-icon" style="background:rgba(253,103,66,.08);color:var(--rb-orange)">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </div>
                <h3>{{ $service['name'] }}</h3>
                <p>{{ $service['description'] ?: 'Professional repair service.' }}</p>
                <div class="svc-meta">
                    @if($service['base_price_amount_cents'])
                        <span class="svc-price">From {{ $this->formatPrice($service['base_price_amount_cents'], $service['base_price_currency']) }}</span>
                    @else
                        <span class="svc-price">Contact for price</span>
                    @endif
                    @if($service['time_required'])
                        <span class="svc-time">⏱ {{ $service['time_required'] }}</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    @if($hasHours || $contactPhone || $contactEmail || $contactAddress)
    <!-- HOURS & CONTACT -->
    <section class="section" id="hours" style="padding-bottom:40px">
        <div class="sec-header">
            <div class="sec-label">Visit Us</div>
            <div class="sec-title">Hours & Contact</div>
        </div>
        <div class="info-grid">
            @if($hasHours)
            <div class="info-card">
                <h3><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Working Hours</h3>
                @foreach($businessHours as $hours)
                    <div class="hours-row">
                        <span class="hours-day">{{ $hours['day'] }}</span>
                        @if($hours['closed'])
                            <span class="hours-closed">Closed</span>
                        @else
                            <span class="hours-time">{{ $this->formatTime($hours['open']) }} – {{ $this->formatTime($hours['close']) }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
            @endif

            @if($contactPhone || $contactEmail || $contactAddress)
            <div class="info-card">
                <h3><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>Contact & Location</h3>
                @if($contactAddress)
                    <div class="contact-row">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ $contactAddress }}
                    </div>
                @endif
                @if($contactPhone)
                    <div class="contact-row">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $contactPhone) }}">{{ $contactPhone }}</a>
                    </div>
                @endif
                @if($contactEmail)
                    <div class="contact-row">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>
                    </div>
                @endif
            </div>
            @endif
        </div>
    </section>
    @endif

    <!-- CTA -->
    <div class="cta-banner">
        <div class="cta-inner">
            <h2>Ready to get your device fixed?</h2>
            <p>Book an appointment online and skip the wait. Most repairs done while you wait.</p>
            <a href="{{ route($tenantRoutePrefix.'.booking.show', ['business' => $tenantSlug]) }}" class="btn btn-white btn-lg">Book Your Repair &rarr;</a>
        </div>
    </div>

    @include('tenant.partials.tenant-footer', [
        'tenantSlug' => $tenantSlug,
        'tenant' => $tenant
    ])
</body>
</html>
</div>
