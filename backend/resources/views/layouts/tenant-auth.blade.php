<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $title ?? 'Account' }} — {{ $tenant->name ?? 'RepairBuddy' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0 }
        :root {
            --rb-blue: #063e70;
            --rb-blue-light: #0a5fa3;
            --rb-orange: #fd6742;
            --rb-bg: #fcfdfe;
            --rb-border: #f1f5f9;
            --rb-text: #0f172a;
            --rb-text-2: #64748b;
            --rb-text-3: #94a3b8;
            --rb-green: #16a34a
        }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--rb-bg);
            background-image: radial-gradient(at 0% 0%, rgba(253,103,66,.03) 0px, transparent 50%), radial-gradient(at 100% 100%, rgba(6,62,112,.03) 0px, transparent 50%);
            color: var(--rb-text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px
        }
        a { text-decoration: none; color: inherit }

        .tenant-badge { display: flex; align-items: center; gap: 12px; margin-bottom: 28px }
        .shop-logo {
            width: 42px; height: 42px; border-radius: 12px;
            background: linear-gradient(135deg, var(--rb-blue), var(--rb-blue-light));
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 800; font-size: 15px;
            box-shadow: 0 2px 8px rgba(6,62,112,.2)
        }
        .shop-name { font-size: 15px; font-weight: 700; color: var(--rb-text) }
        .shop-sub { font-size: 11px; color: var(--rb-text-3); font-weight: 500 }

        .auth-card {
            background: #fff; width: 100%; max-width: 440px; padding: 44px 48px;
            border-radius: 32px; box-shadow: 0 20px 50px rgba(0,0,0,.04);
            border: 1px solid rgba(226,232,240,.8); position: relative; overflow: hidden;
            animation: reveal .8s cubic-bezier(.4,0,.2,1) forwards
        }
        .auth-card.wider { max-width: 480px }
        .auth-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
            background: linear-gradient(90deg, var(--rb-blue), var(--rb-orange)); opacity: .8
        }
        @keyframes reveal { from { opacity: 0; transform: translateY(20px) } to { opacity: 1; transform: translateY(0) } }

        .auth-header { text-align: center; margin-bottom: 28px }
        .auth-header h1 { font-size: 1.85rem; font-weight: 800; letter-spacing: -.5px; margin-bottom: 8px }
        .auth-header p { color: var(--rb-text-2); font-size: 1rem; line-height: 1.5 }

        .input-group { position: relative; margin-bottom: 20px }
        .form-label { display: block; font-weight: 600; font-size: .9rem; color: var(--rb-text); margin-bottom: 10px }
        .label-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px }
        .forgot-link { font-size: .875rem; color: var(--rb-blue); font-weight: 600; text-decoration: none }
        .forgot-link:hover { text-decoration: underline }
        .input-wrap { position: relative }
        .input-wrap svg {
            position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
            width: 18px; height: 18px; color: var(--rb-text-3); pointer-events: none; transition: color .3s
        }
        .form-input {
            width: 100%; padding: 14px 16px 14px 48px; border: 1.5px solid #eef2f6;
            border-radius: 16px; font-size: .95rem; font-family: inherit; font-weight: 500;
            background: #fcfdfe; color: var(--rb-text); outline: none; transition: all .3s
        }
        .form-input:focus { border-color: var(--rb-blue); background: #fff; box-shadow: 0 0 0 4px rgba(6,62,112,.08) }
        .form-input::placeholder { color: var(--rb-text-3); font-weight: 400 }
        .form-input.is-invalid { border-color: #dc3545 }
        .form-input.is-invalid:focus { box-shadow: 0 0 0 4px rgba(220,53,69,.15) }

        .form-input-simple {
            width: 100%; padding: 14px 16px; border: 1.5px solid #eef2f6;
            border-radius: 16px; font-size: .95rem; font-family: inherit; font-weight: 500;
            background: #fcfdfe; color: var(--rb-text); outline: none; transition: all .3s
        }
        .form-input-simple:focus { border-color: var(--rb-blue); background: #fff; box-shadow: 0 0 0 4px rgba(6,62,112,.08) }
        .form-input-simple::placeholder { color: var(--rb-text-3); font-weight: 400 }
        .form-input-simple.is-invalid { border-color: #dc3545 }

        .name-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px }

        .pw-toggle {
            position: absolute; right: 16px; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: var(--rb-text-3); cursor: pointer; padding: 4px; transition: color .3s
        }
        .pw-toggle:hover { color: var(--rb-blue) }
        .pw-toggle svg { width: 18px; height: 18px; position: static }

        .checkbox-row { display: flex; align-items: center; gap: 8px; margin-bottom: 20px }
        .checkbox-row input { width: 16px; height: 16px; accent-color: var(--rb-blue); cursor: pointer }
        .checkbox-row label { font-size: .875rem; color: var(--rb-text-2); font-weight: 500; cursor: pointer }

        .terms { display: flex; align-items: flex-start; gap: 10px; margin: 8px 0 22px }
        .terms input { width: 18px; height: 18px; accent-color: var(--rb-blue); cursor: pointer; margin-top: 2px; flex-shrink: 0 }
        .terms label { font-size: 13px; color: var(--rb-text-2); line-height: 1.5; cursor: pointer }
        .terms a { color: var(--rb-blue); font-weight: 600 }

        .btn-submit {
            width: 100%; padding: 16px; background: var(--rb-blue); color: #fff;
            border: none; border-radius: 16px; font-size: 1.05rem; font-weight: 700;
            font-family: inherit; cursor: pointer; transition: all .4s cubic-bezier(.4,0,.2,1);
            box-shadow: 0 10px 20px rgba(6,62,112,.1);
            display: flex; align-items: center; justify-content: center; gap: 8px
        }
        .btn-submit:hover { background: #05335d; transform: translateY(-2px); box-shadow: 0 15px 30px rgba(6,62,112,.2) }
        .btn-submit:disabled { opacity: .7; cursor: not-allowed; transform: none }
        .btn-submit svg { width: 18px; height: 18px }

        .divider { display: flex; align-items: center; gap: 14px; margin: 24px 0 }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: #eef2f6 }
        .divider span { font-size: 12px; color: var(--rb-text-3); font-weight: 500 }

        .google-btn {
            width: 100%; padding: 13px; border: 1.5px solid #eef2f6; border-radius: 14px;
            font-size: .95rem; font-weight: 600; font-family: inherit; background: #fff;
            color: var(--rb-text); cursor: pointer; transition: all .2s;
            display: flex; align-items: center; justify-content: center; gap: 10px
        }
        .google-btn:hover { background: #f8fafc; border-color: #d1d5db }
        .google-btn svg { width: 18px; height: 18px }

        .footer-link { text-align: center; margin-top: 28px; font-size: .95rem; color: var(--rb-text-2) }
        .footer-link a { color: var(--rb-blue); font-weight: 700; text-decoration: none }
        .footer-link a:hover { text-decoration: underline }

        .back-link {
            display: inline-flex; align-items: center; gap: 6px;
            margin-top: 18px; font-size: 13px; color: var(--rb-text-3); transition: color .2s
        }
        .back-link:hover { color: var(--rb-blue) }
        .back-link svg { width: 14px; height: 14px }

        .lock-icon {
            width: 64px; height: 64px; border-radius: 50%; background: rgba(6,62,112,.06);
            display: flex; align-items: center; justify-content: center; margin: 0 auto 20px
        }
        .lock-icon svg { width: 28px; height: 28px; color: var(--rb-blue) }

        .alert { padding: 12px 16px; border-radius: 12px; font-size: .9rem; margin-bottom: 20px; font-weight: 500 }
        .alert-success { background: rgba(22,163,74,.08); color: #15803d; border: 1px solid rgba(22,163,74,.15) }
        .alert-danger { background: rgba(220,53,69,.08); color: #dc3545; border: 1px solid rgba(220,53,69,.15) }
        .invalid-feedback { color: #dc3545; font-size: .8rem; margin-top: 6px; font-weight: 500 }

        .spinner { display: inline-block; width: 18px; height: 18px; border: 2px solid rgba(255,255,255,.3); border-radius: 50%; border-top-color: #fff; animation: spin .6s linear infinite }
        @keyframes spin { to { transform: rotate(360deg) } }

        @media(max-width:500px) {
            .auth-card { padding: 32px 24px }
            .name-row { grid-template-columns: 1fr }
        }
    </style>
</head>
<body>
    @php
        $tenantSlug = $tenantSlug ?? null;
        $tenant = $tenant ?? null;
        $shopInitials = $tenant ? strtoupper(collect(explode(' ', $tenant->name))->map(fn($w) => substr($w, 0, 1))->take(2)->join('')) : 'RB';
    @endphp

    <a href="{{ $tenantSlug ? url('/t/' . $tenantSlug) : '/' }}" class="tenant-badge">
        <div class="shop-logo">{{ $shopInitials }}</div>
        <div>
            <div class="shop-name">{{ $tenant->name ?? 'RepairBuddy' }}</div>
            <div class="shop-sub">{{ $tenantSlug ? $tenantSlug . '.repairbuddy.com' : 'repairbuddy.com' }}</div>
        </div>
    </a>

    @yield('content')

    <a href="{{ $tenantSlug ? url('/t/' . $tenantSlug) : '/' }}" class="back-link">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to {{ $tenant->name ?? 'home' }}
    </a>

    @stack('scripts')
</body>
</html>
