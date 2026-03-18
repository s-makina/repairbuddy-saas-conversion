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

    <!-- SERVICES BY CATEGORY -->
    <div class="section">
        <!-- Screen Repairs -->
        <div class="category-section" data-cat="phone tablet laptop">
            <div class="category-header">
                <div class="category-icon" style="background:rgba(253,103,66,.08);color:var(--rb-orange)">📱</div>
                <div><span class="category-name">Screen Repairs</span><span class="category-count">— 4 services</span></div>
            </div>
            <div class="services-grid">
                <div class="svc-card">
                    <div class="svc-icon" style="background:rgba(253,103,66,.08);color:var(--rb-orange)"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg></div>
                    <h3>Phone Screen Replacement</h3>
                    <p>Full LCD/OLED screen replacement for all smartphone brands and models.</p>
                    <div class="svc-meta"><span class="svc-price">From $89</span><span class="svc-time">⏱ 30–45 min</span></div>
                    <a href="{{ route($tenantRoutePrefix . '.booking.show', ['business' => $tenantSlug]) }}" class="svc-book-btn">Book This Service →</a>
                </div>
                <div class="svc-card">
                    <div class="svc-icon" style="background:rgba(6,62,112,.06);color:var(--rb-blue)"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></div>
                    <h3>Tablet Screen Replacement</h3>
                    <p>iPad, Galaxy Tab, and other tablet screen repairs with genuine parts.</p>
                    <div class="svc-meta"><span class="svc-price">From $129</span><span class="svc-time">⏱ 45–60 min</span></div>
                    <a href="{{ route($tenantRoutePrefix . '.booking.show', ['business' => $tenantSlug]) }}" class="svc-book-btn">Book This Service →</a>
                </div>
                <div class="svc-card">
                    <div class="svc-icon" style="background:rgba(43,138,62,.06);color:#2b8a3e"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></div>
                    <h3>Laptop Screen Replacement</h3>
                    <p>LCD and Retina display replacement for MacBook, Dell, HP, Lenovo.</p>
                    <div class="svc-meta"><span class="svc-price">From $149</span><span class="svc-time">⏱ 1–2 hrs</span></div>
                    <a href="{{ route($tenantRoutePrefix . '.booking.show', ['business' => $tenantSlug]) }}" class="svc-book-btn">Book This Service →</a>
                </div>
                <div class="svc-card">
                    <div class="svc-icon" style="background:rgba(112,72,232,.06);color:#7048e8"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg></div>
                    <h3>Glass Only Repair</h3>
                    <p>Front glass replacement without replacing the full display assembly.</p>
                    <div class="svc-meta"><span class="svc-price">From $59</span><span class="svc-time">⏱ 30 min</span></div>
                    <a href="{{ route($tenantRoutePrefix . '.booking.show', ['business' => $tenantSlug]) }}" class="svc-book-btn">Book This Service →</a>
                </div>
            </div>
        </div>

        <!-- Battery & Power -->
        <div class="category-section" data-cat="phone tablet laptop watch">
            <div class="category-header">
                <div class="category-icon" style="background:rgba(6,62,112,.06);color:var(--rb-blue)">⚡</div>
                <div><span class="category-name">Battery & Power</span><span class="category-count">— 3 services</span></div>
            </div>
            <div class="services-grid">
                <div class="svc-card">
                    <div class="svc-icon" style="background:rgba(6,62,112,.06);color:var(--rb-blue)"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg></div>
                    <h3>Battery Replacement</h3>
                    <p>Restore battery life with genuine OEM replacement batteries.</p>
                    <div class="svc-meta"><span class="svc-price">From $49</span><span class="svc-time">⏱ 20–30 min</span></div>
                    <a href="{{ route($tenantRoutePrefix . '.booking.show', ['business' => $tenantSlug]) }}" class="svc-book-btn">Book This Service →</a>
                </div>
                <div class="svc-card">
                    <div class="svc-icon" style="background:rgba(25,113,194,.06);color:#1971c2"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.858 15.355-5.858 21.213 0"/></svg></div>
                    <h3>Charging Port Repair</h3>
                    <p>Fix loose or non-functional USB-C, Lightning, and Micro-USB ports.</p>
                    <div class="svc-meta"><span class="svc-price">From $39</span><span class="svc-time">⏱ 20–30 min</span></div>
                    <a href="{{ route($tenantRoutePrefix . '.booking.show', ['business' => $tenantSlug]) }}" class="svc-book-btn">Book This Service →</a>
                </div>
                <div class="svc-card">
                    <div class="svc-icon" style="background:rgba(230,119,0,.06);color:#e67700"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg></div>
                    <h3>Power IC Repair</h3>
                    <p>Component-level power circuit diagnosis and microsoldering repair.</p>
                    <div class="svc-meta"><span class="svc-price">From $99</span><span class="svc-time">⏱ 1–3 hrs</span></div>
                    <a href="{{ route($tenantRoutePrefix . '.booking.show', ['business' => $tenantSlug]) }}" class="svc-book-btn">Book This Service →</a>
                </div>
            </div>
        </div>

        <!-- Camera & Sensors -->
        <div class="category-section" data-cat="phone tablet">
            <div class="category-header">
                <div class="category-icon" style="background:rgba(112,72,232,.06);color:#7048e8">📷</div>
                <div><span class="category-name">Camera & Sensors</span><span class="category-count">— 2 services</span></div>
            </div>
            <div class="services-grid">
                <div class="svc-card">
                    <div class="svc-icon" style="background:rgba(112,72,232,.06);color:#7048e8"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3"/></svg></div>
                    <h3>Camera Replacement</h3>
                    <p>Front and rear camera module replacements with calibration.</p>
                    <div class="svc-meta"><span class="svc-price">From $59</span><span class="svc-time">⏱ 25–40 min</span></div>
                    <a href="{{ route($tenantRoutePrefix . '.booking.show', ['business' => $tenantSlug]) }}" class="svc-book-btn">Book This Service →</a>
                </div>
                <div class="svc-card">
                    <div class="svc-icon" style="background:rgba(43,138,62,.06);color:#2b8a3e"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></div>
                    <h3>Face ID / Sensor Repair</h3>
                    <p>TrueDepth camera, proximity sensor, and Face ID module repair.</p>
                    <div class="svc-meta"><span class="svc-price">From $79</span><span class="svc-time">⏱ 30–45 min</span></div>
                    <a href="{{ route($tenantRoutePrefix . '.booking.show', ['business' => $tenantSlug]) }}" class="svc-book-btn">Book This Service →</a>
                </div>
            </div>
        </div>

        <!-- Water Damage -->
        <div class="category-section" data-cat="phone tablet laptop watch console">
            <div class="category-header">
                <div class="category-icon" style="background:rgba(230,119,0,.06);color:#e67700">💧</div>
                <div><span class="category-name">Water Damage Recovery</span><span class="category-count">— 2 services</span></div>
            </div>
            <div class="services-grid">
                <div class="svc-card">
                    <div class="svc-icon" style="background:rgba(230,119,0,.06);color:#e67700"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg></div>
                    <h3>Water Damage Treatment</h3>
                    <p>Ultrasonic cleaning, corrosion removal, and component-level drying.</p>
                    <div class="svc-meta"><span class="svc-price">From $79</span><span class="svc-time">⏱ 2–4 hrs</span></div>
                    <a href="{{ route($tenantRoutePrefix . '.booking.show', ['business' => $tenantSlug]) }}" class="svc-book-btn">Book This Service →</a>
                </div>
                <div class="svc-card">
                    <div class="svc-icon" style="background:rgba(253,103,66,.08);color:var(--rb-orange)"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg></div>
                    <h3>Data Recovery</h3>
                    <p>Recover data from water-damaged, dropped, or dead devices.</p>
                    <div class="svc-meta"><span class="svc-price">From $149</span><span class="svc-time">⏱ 1–3 days</span></div>
                    <a href="{{ route($tenantRoutePrefix . '.booking.show', ['business' => $tenantSlug]) }}" class="svc-book-btn">Book This Service →</a>
                </div>
            </div>
        </div>
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
