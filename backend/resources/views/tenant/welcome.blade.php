@php
    $tenantSlug = $tenantSlug ?? null;
    $tenant = $tenant ?? null;
    $shopInitials = $tenant ? strtoupper(collect(explode(' ', $tenant->name))->map(fn($w) => substr($w, 0, 1))->take(2)->join('')) : 'RB';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $tenant->name ?? 'RepairBuddy' }} — Book a Repair</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0 }
        :root {
            --rb-blue: #063e70; --rb-blue-light: #0a5fa3; --rb-orange: #fd6742;
            --rb-orange-light: #ff8a6b; --rb-bg: #fcfdfe; --rb-border: #f1f5f9;
            --rb-text: #0f172a; --rb-text-2: #475569; --rb-text-3: #94a3b8;
            --rb-surface: #fff; --rb-surface-2: #f8fafc
        }
        body { font-family: 'Inter', system-ui, sans-serif; background: var(--rb-bg); color: var(--rb-text); margin: 0; line-height: 1.6 }

        /* NAV */
        .navbar { background: var(--rb-surface); border-bottom: 1px solid var(--rb-border); position: sticky; top: 0; z-index: 100; backdrop-filter: blur(12px) }
        .nav-inner { max-width: 1200px; margin: 0 auto; padding: 0 28px; height: 68px; display: flex; align-items: center; justify-content: space-between }
        .nav-brand { display: flex; align-items: center; gap: 12px; text-decoration: none }
        .shop-logo { width: 42px; height: 42px; border-radius: 12px; background: linear-gradient(135deg, var(--rb-blue), var(--rb-blue-light)); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 800; font-size: 16px; box-shadow: 0 2px 8px rgba(6,62,112,.2) }
        .shop-name { font-size: 17px; font-weight: 700; color: var(--rb-text) }
        .shop-sub { font-size: 11px; color: var(--rb-text-3); font-weight: 500 }
        .nav-links { display: flex; align-items: center; gap: 8px }
        .nav-links a { padding: 8px 16px; border-radius: 12px; font-size: 13px; font-weight: 600; text-decoration: none; color: var(--rb-text-2); transition: all .2s }
        .nav-links a:hover { background: var(--rb-surface-2); color: var(--rb-text) }

        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; border-radius: 14px; font-size: 13px; font-weight: 700; cursor: pointer; border: none; font-family: inherit; transition: all .25s; text-decoration: none }
        .btn-primary { background: var(--rb-blue); color: #fff; box-shadow: 0 4px 14px rgba(6,62,112,.2) }
        .btn-primary:hover { background: #05335d; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(6,62,112,.3) }
        .btn-outline { background: var(--rb-surface); border: 1.5px solid var(--rb-border); color: var(--rb-text-2) }
        .btn-outline:hover { background: var(--rb-surface-2); border-color: #d1d5db }
        .btn-orange { background: var(--rb-orange); color: #fff; box-shadow: 0 4px 14px rgba(253,103,66,.25) }
        .btn-orange:hover { background: #e5532e; transform: translateY(-1px) }
        .btn svg { width: 16px; height: 16px }
        .btn-lg { padding: 16px 32px; font-size: 15px; border-radius: 16px }

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
        .services-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px }
        .svc-card { background: var(--rb-surface); border: 1px solid var(--rb-border); border-radius: 20px; padding: 28px; box-shadow: 0 1px 3px rgba(0,0,0,.04); transition: all .3s; cursor: pointer }
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

        /* FOOTER */
        .footer { background: var(--rb-surface); border-top: 1px solid var(--rb-border); padding: 32px 28px; text-align: center }
        .footer-inner { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px }
        .footer-copy { font-size: 12px; color: var(--rb-text-3) }
        .footer-powered { font-size: 11px; color: var(--rb-text-3) }
        .footer-powered a { color: var(--rb-blue); font-weight: 700; text-decoration: none }

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
    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="nav-inner">
            <a href="{{ url('/t/' . $tenantSlug) }}" class="nav-brand">
                <div class="shop-logo">{{ $shopInitials }}</div>
                <div>
                    <div class="shop-name">{{ $tenant->name ?? 'RepairBuddy' }}</div>
                    <div class="shop-sub">{{ $tenantSlug }}.repairbuddy.com</div>
                </div>
            </a>
            <div class="nav-links">
                <a href="#services">Services</a>
                <a href="#hours">Hours & Contact</a>
                <a href="{{ route('tenant.login', ['business' => $tenantSlug]) }}" class="btn btn-outline">Sign In</a>
                <a href="{{ route('tenant.register', ['business' => $tenantSlug]) }}" class="btn btn-primary">Book a Repair</a>
            </div>
        </div>
    </nav>

    <!-- HERO -->
    <section class="hero">
        <div class="hero-inner">
            <div class="hero-badge">Now accepting online bookings</div>
            <h1>Expert Electronics Repairs, <span>Done Right</span></h1>
            <p>From cracked screens to water damage — get fast, reliable repairs from our certified technicians. Book online in seconds.</p>
            <div class="hero-ctas">
                <a href="{{ route('tenant.register', ['business' => $tenantSlug]) }}" class="btn btn-orange btn-lg">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Book Appointment
                </a>
                <a href="#hours" class="btn btn-outline btn-lg">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    Contact Us
                </a>
            </div>
            <div class="hero-stats">
                <div class="h-stat"><div class="h-stat-val">4.9&#9733;</div><div class="h-stat-lbl">Customer Rating</div></div>
                <div class="h-stat"><div class="h-stat-val">2,400+</div><div class="h-stat-lbl">Repairs Completed</div></div>
                <div class="h-stat"><div class="h-stat-val">45 min</div><div class="h-stat-lbl">Avg. Repair Time</div></div>
                <div class="h-stat"><div class="h-stat-val">90 day</div><div class="h-stat-lbl">Warranty</div></div>
            </div>
        </div>
    </section>

    <!-- SERVICES -->
    <section class="section" id="services">
        <div class="sec-header">
            <div class="sec-label">Our Services</div>
            <div class="sec-title">What we can fix</div>
            <p class="sec-sub">Professional repairs for all major devices with quality parts and certified technicians.</p>
        </div>
        <div class="services-grid">
            <div class="svc-card">
                <div class="svc-icon" style="background:rgba(253,103,66,.08);color:var(--rb-orange)"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg></div>
                <h3>Screen Replacement</h3>
                <p>Cracked or broken screen? We replace LCD, OLED, and glass panels for all phone and tablet brands.</p>
                <div class="svc-meta"><span class="svc-price">From $89</span><span class="svc-time">&#9201; 30–45 min</span></div>
            </div>
            <div class="svc-card">
                <div class="svc-icon" style="background:rgba(6,62,112,.06);color:var(--rb-blue)"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg></div>
                <h3>Battery Replacement</h3>
                <p>Restore your device's battery life with genuine replacement batteries and expert installation.</p>
                <div class="svc-meta"><span class="svc-price">From $49</span><span class="svc-time">&#9201; 20–30 min</span></div>
            </div>
            <div class="svc-card">
                <div class="svc-icon" style="background:rgba(43,138,62,.06);color:#2b8a3e"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></div>
                <h3>Laptop Repair</h3>
                <p>Hardware diagnostics, motherboard repair, keyboard replacement, and upgrades for all laptop brands.</p>
                <div class="svc-meta"><span class="svc-price">From $99</span><span class="svc-time">&#9201; 1–2 hrs</span></div>
            </div>
            <div class="svc-card">
                <div class="svc-icon" style="background:rgba(112,72,232,.06);color:#7048e8"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3"/></svg></div>
                <h3>Camera Repair</h3>
                <p>Front and rear camera replacements, lens fixes, and autofocus calibration for clear photos.</p>
                <div class="svc-meta"><span class="svc-price">From $59</span><span class="svc-time">&#9201; 25–40 min</span></div>
            </div>
            <div class="svc-card">
                <div class="svc-icon" style="background:rgba(25,113,194,.06);color:#1971c2"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.858 15.355-5.858 21.213 0"/></svg></div>
                <h3>Charging Port</h3>
                <p>Fix loose or non-functional charging ports. We repair USB-C, Lightning, and wireless charging issues.</p>
                <div class="svc-meta"><span class="svc-price">From $39</span><span class="svc-time">&#9201; 20–30 min</span></div>
            </div>
            <div class="svc-card">
                <div class="svc-icon" style="background:rgba(230,119,0,.06);color:#e67700"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg></div>
                <h3>Water Damage</h3>
                <p>Specialized treatment for water-damaged devices. Ultrasonic cleaning and component-level repair.</p>
                <div class="svc-meta"><span class="svc-price">From $79</span><span class="svc-time">&#9201; 2–4 hrs</span></div>
            </div>
        </div>
    </section>

    <!-- HOURS & CONTACT -->
    <section class="section" id="hours" style="padding-bottom:40px">
        <div class="sec-header">
            <div class="sec-label">Visit Us</div>
            <div class="sec-title">Hours & Contact</div>
        </div>
        <div class="info-grid">
            <div class="info-card">
                <h3><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Working Hours</h3>
                <div class="hours-row"><span class="hours-day">Monday – Friday</span><span class="hours-time">9:00 AM – 6:00 PM</span></div>
                <div class="hours-row"><span class="hours-day">Saturday</span><span class="hours-time">10:00 AM – 3:00 PM</span></div>
                <div class="hours-row"><span class="hours-day">Sunday</span><span class="hours-closed">Closed</span></div>
            </div>
            <div class="info-card">
                <h3><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>Contact & Location</h3>
                <div class="contact-row"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>742 Maple Ave, Portland, OR 97201</div>
                <div class="contact-row"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg><a href="tel:+15551234567">+1 (555) 123-4567</a></div>
                <div class="contact-row"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg><a href="mailto:hello@quickfix.com">hello@quickfix.com</a></div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <div class="cta-banner">
        <div class="cta-inner">
            <h2>Ready to get your device fixed?</h2>
            <p>Book an appointment online and skip the wait. Most repairs done while you wait.</p>
            <a href="{{ route('tenant.register', ['business' => $tenantSlug]) }}" class="btn btn-white btn-lg">Book Your Repair &rarr;</a>
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="footer-inner">
            <div class="footer-copy">&copy; {{ date('Y') }} {{ $tenant->name ?? 'RepairBuddy' }}. All rights reserved.</div>
            <div class="footer-powered">Powered by <a href="/">RepairBuddy</a></div>
        </div>
    </footer>
</body>
</html>
