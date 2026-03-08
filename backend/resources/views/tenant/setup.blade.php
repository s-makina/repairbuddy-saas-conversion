@php
    /** @var \App\Models\Tenant $tenant */
    /** @var \App\Models\User $user */
    $siteName = config('app.name', 'RepairBuddy');
    $apiBase = url('api/' . $tenant->slug . '/app');
    $dashboardUrl = route('tenant.dashboard', ['business' => $tenant->slug]);
    $initials = strtoupper(substr($user->name ?? 'U', 0, 2));
    $address = is_array($tenant->billing_address_json) ? $tenant->billing_address_json : [];
    $state = is_array($tenant->setup_state) ? $tenant->setup_state : [];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Set Up Your Business &ndash; {{ $siteName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{
            --bg:#faf9f7;--surface:#fff;--surface-2:#f5f3f0;
            --border:#ebe8e3;--border-2:#d9d4cd;
            --text:#1a1714;--text-2:#6b6560;--text-3:#a09890;
            --orange:#e8590c;--orange-light:#f76707;--orange-bg:#fff4ed;
            --green:#2b8a3e;--green-bg:#f0fdf4;
            --blue:#1971c2;--blue-bg:#eff6ff;
            --purple:#7048e8;--purple-bg:#f5f3ff;
            --red:#e03131;--red-bg:#fff1f2;
            --r:10px;--r-sm:6px;--r-lg:16px;
            --sh:0 1px 3px rgba(0,0,0,.06);--sh-md:0 4px 12px rgba(0,0,0,.08);--sh-lg:0 8px 30px rgba(0,0,0,.1)
        }
        body{font-family:'DM Sans',system-ui,sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;flex-direction:column}
        a{text-decoration:none;color:inherit}

        /* TOPBAR */
        .topbar{background:var(--surface);border-bottom:1px solid var(--border);height:64px;display:flex;align-items:center;justify-content:space-between;padding:0 32px;flex-shrink:0}
        .nav-brand{display:flex;align-items:center;gap:10px}
        .logo-mark{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#e8590c,#f76707);display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(232,89,12,.25)}
        .logo-mark svg{width:18px;height:18px;color:#fff}
        .brand-name{font-size:18px;font-weight:800;letter-spacing:-.02em}
        .tb-right{display:flex;align-items:center;gap:12px}
        .tb-user{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;color:var(--text-2)}
        .tb-avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#e8590c,#f76707);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff}
        .skip-link{font-size:13px;color:var(--text-3);font-weight:500;padding:8px 16px;border-radius:var(--r-sm);transition:all .2s;cursor:pointer;background:none;border:none;font-family:inherit}
        .skip-link:hover{color:var(--orange);background:var(--orange-bg)}

        /* MAIN LAYOUT */
        .setup-main{flex:1;display:flex;flex-direction:column;max-width:800px;width:100%;margin:0 auto;padding:32px 32px 48px}

        /* PROGRESS */
        .progress-section{margin-bottom:36px}
        .progress-steps{display:flex;align-items:center;gap:0;position:relative}
        .p-step{display:flex;flex-direction:column;align-items:center;gap:10px;flex:1;position:relative;cursor:pointer;text-align:center}
        .p-step:not(:last-child)::after{content:'';position:absolute;top:18px;left:calc(50% + 22px);width:calc(100% - 44px);height:2px;background:var(--border);transition:background .3s;z-index:0}
        .p-step.done:not(:last-child)::after{background:var(--orange)}
        .p-num{width:36px;height:36px;border-radius:50%;border:2px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:var(--text-3);background:var(--surface);flex-shrink:0;transition:all .3s;position:relative;z-index:1}
        .p-step.active .p-num{border-color:var(--orange);background:var(--orange);color:#fff;box-shadow:0 0 0 4px rgba(232,89,12,.12)}
        .p-step.done .p-num{border-color:var(--orange);background:var(--orange-bg);color:var(--orange)}
        .p-label{font-size:13px;font-weight:600;color:var(--text-3);white-space:nowrap;transition:color .3s}
        .p-step.active .p-label{color:var(--text)}
        .p-step.done .p-label{color:var(--orange)}

        /* WIZARD CARD */
        .wizard-card{background:var(--surface);border:1px solid var(--border);border-radius:20px;box-shadow:var(--sh-md);overflow:hidden;flex:1;display:flex;flex-direction:column}
        .wc-header{padding:28px 32px 0;margin-bottom:24px}
        .wc-header h2{font-size:22px;font-weight:800;letter-spacing:-.02em;margin-bottom:6px}
        .wc-header p{font-size:14px;color:var(--text-2);line-height:1.5}
        .wc-body{padding:0 32px;flex:1}
        .wc-footer{padding:20px 32px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;background:var(--surface-2);margin-top:auto}

        /* BUTTONS */
        .btn{display:inline-flex;align-items:center;gap:8px;padding:11px 24px;border-radius:var(--r);font-size:14px;font-weight:600;cursor:pointer;border:none;font-family:inherit;transition:all .2s}
        .btn svg{width:16px;height:16px}
        .btn-primary{background:var(--orange);color:#fff;box-shadow:0 2px 12px rgba(232,89,12,.3)}
        .btn-primary:hover{background:#cf4c08;transform:translateY(-1px)}
        .btn-primary:disabled{opacity:.5;cursor:not-allowed;transform:none}
        .btn-ghost{background:var(--surface);border:1.5px solid var(--border);color:var(--text-2)}
        .btn-ghost:hover{background:var(--surface-2);color:var(--text)}
        .btn-success{background:var(--green);color:#fff;box-shadow:0 2px 12px rgba(43,138,62,.3);padding:14px 36px;font-size:15px;border-radius:var(--r-lg)}
        .btn-success:hover{background:#237032;transform:translateY(-1px)}
        .btn-success:disabled{opacity:.5;cursor:not-allowed;transform:none}
        .btn-secondary{background:var(--surface-2);border:1px solid var(--border);color:var(--text-2)}
        .btn-secondary:hover{background:var(--border);color:var(--text)}

        /* FORM ELEMENTS */
        .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}
        .form-group{margin-bottom:0}
        .form-group.full{grid-column:1/-1}
        .form-label{display:block;font-size:13px;font-weight:600;color:var(--text);margin-bottom:7px}
        .form-sub{font-size:11.5px;color:var(--text-3);font-weight:400;margin-left:4px}
        .input-wrap{position:relative}
        .input-wrap svg{position:absolute;left:14px;top:50%;transform:translateY(-50%);width:17px;height:17px;color:var(--text-3);pointer-events:none}
        .form-input{width:100%;padding:11px 14px 11px 42px;border:1.5px solid var(--border);border-radius:var(--r-sm);font-size:14px;font-family:inherit;font-weight:500;background:var(--surface);color:var(--text);outline:none;transition:all .2s}
        .form-input:focus{border-color:var(--orange);box-shadow:0 0 0 3px rgba(232,89,12,.08)}
        .form-input::placeholder{color:var(--text-3);font-weight:400}
        .form-select{width:100%;padding:11px 36px 11px 42px;border:1.5px solid var(--border);border-radius:var(--r-sm);font-size:14px;font-family:inherit;font-weight:500;background:var(--surface);color:var(--text);outline:none;cursor:pointer;transition:all .2s;appearance:none;background-image:url("data:image/svg+xml,%3Csvg width='10' height='6' viewBox='0 0 10 6' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%23a09890' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 14px center}
        .form-select:focus{border-color:var(--orange);box-shadow:0 0 0 3px rgba(232,89,12,.08)}
        textarea.form-input{padding:11px 14px;min-height:80px;resize:vertical}

        /* WORKING HOURS */
        .hours-table{width:100%}
        .hour-row{display:flex;align-items:center;gap:14px;padding:10px 0;border-bottom:1px solid var(--border)}
        .hour-row:last-child{border-bottom:none}
        .hour-toggle{width:40px;height:22px;border-radius:99px;background:var(--border-2);position:relative;cursor:pointer;transition:background .3s;flex-shrink:0;border:none}
        .hour-toggle.on{background:var(--orange)}
        .hour-toggle::after{content:'';position:absolute;left:2px;top:2px;width:18px;height:18px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.15);transition:transform .3s}
        .hour-toggle.on::after{transform:translateX(18px)}
        .hour-day{width:100px;font-size:14px;font-weight:600;flex-shrink:0}
        .hour-times{display:flex;align-items:center;gap:8px;flex:1}
        .hour-times select{padding:7px 28px 7px 10px;border:1.5px solid var(--border);border-radius:var(--r-sm);font-size:13px;font-family:inherit;background:var(--surface);color:var(--text);cursor:pointer;outline:none;appearance:none;background-image:url("data:image/svg+xml,%3Csvg width='10' height='6' viewBox='0 0 10 6' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%23a09890' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 8px center}
        .hour-times span{font-size:12px;color:var(--text-3)}
        .hour-closed{font-size:13px;color:var(--text-3);font-style:italic}

        /* TAXES */
        .tax-list{display:flex;flex-direction:column;gap:12px;margin-bottom:16px}
        .tax-item{display:grid;grid-template-columns:2fr 1fr 0.8fr 0.8fr auto;gap:12px;align-items:center;background:var(--surface-2);padding:14px 16px;border-radius:var(--r);border:1px solid var(--border)}
        .tax-item input,.tax-item select{padding:8px 12px;border:1.5px solid var(--border);border-radius:var(--r-sm);font-size:13px;font-family:inherit;background:var(--surface);color:var(--text);outline:none;transition:border-color .2s}
        .tax-item input:focus,.tax-item select:focus{border-color:var(--orange)}
        .tax-item select{appearance:none;padding-right:28px;background-image:url("data:image/svg+xml,%3Csvg width='10' height='6' viewBox='0 0 10 6' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%23a09890' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 8px center;cursor:pointer}
        .tax-enabled-toggle{width:36px;height:20px;border-radius:99px;background:var(--border-2);position:relative;cursor:pointer;transition:background .3s;flex-shrink:0;border:none}
        .tax-enabled-toggle.on{background:var(--green)}
        .tax-enabled-toggle::after{content:'';position:absolute;left:2px;top:2px;width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.15);transition:transform .3s}
        .tax-enabled-toggle.on::after{transform:translateX(16px)}
        .remove-btn{width:32px;height:32px;border-radius:50%;border:1px solid var(--border);background:var(--surface);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s;flex-shrink:0}
        .remove-btn:hover{background:var(--red-bg);border-color:var(--red);color:var(--red)}
        .remove-btn svg{width:14px;height:14px;color:var(--text-3)}
        .remove-btn:hover svg{color:var(--red)}
        .add-row-btn{display:flex;align-items:center;gap:8px;padding:10px 18px;background:var(--surface);border:1.5px dashed var(--border-2);border-radius:var(--r);font-size:13px;font-weight:600;color:var(--orange);cursor:pointer;font-family:inherit;transition:all .2s}
        .add-row-btn:hover{background:var(--orange-bg);border-color:var(--orange)}
        .add-row-btn svg{width:16px;height:16px}

        /* SHOPS / BRANCHES */
        .shop-list{display:flex;flex-direction:column;gap:16px;margin-bottom:16px}
        .shop-card{background:var(--surface-2);border:1px solid var(--border);border-radius:var(--r);padding:20px;position:relative;transition:all .2s}
        .shop-card:hover{border-color:var(--border-2);box-shadow:var(--sh)}
        .shop-card-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
        .shop-card-num{display:flex;align-items:center;gap:10px}
        .shop-card-num .shop-icon{width:36px;height:36px;border-radius:var(--r-sm);background:var(--orange-bg);display:flex;align-items:center;justify-content:center;flex-shrink:0}
        .shop-card-num .shop-icon svg{width:18px;height:18px;color:var(--orange)}
        .shop-card-num span{font-size:15px;font-weight:700}
        .shop-card-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
        .shop-card-grid .form-group.full{grid-column:1/-1}
        .shop-card-grid .form-input,.shop-card-grid .form-select{padding-left:14px;font-size:13px;padding-top:9px;padding-bottom:9px}
        .shop-card-grid .form-label{font-size:12px;margin-bottom:5px}
        .primary-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;background:var(--green-bg);color:var(--green);border:1px solid rgba(43,138,62,.15)}

        /* REVIEW */
        .review-block{background:var(--surface-2);border:1px solid var(--border);border-radius:var(--r);padding:20px;position:relative}
        .review-block-title{font-size:14px;font-weight:700;margin-bottom:12px;display:flex;align-items:center;justify-content:space-between}
        .review-block-title a{font-size:12px;color:var(--orange);font-weight:600}
        .review-row{display:flex;justify-content:space-between;align-items:center;padding:7px 0;font-size:13px}
        .review-row:not(:last-child){border-bottom:1px solid var(--border)}
        .review-label{color:var(--text-3)}
        .review-value{font-weight:600;color:var(--text)}
        .review-card-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
        .launch-section{text-align:center;padding:20px 0}
        .launch-section p{font-size:14px;color:var(--text-2);margin-bottom:20px}

        /* TABS (STEPS) */
        .step-panel{display:none}
        .step-panel.active{display:block;animation:fadeIn .3s ease}
        @keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}

        /* WELCOME CARD */
        .welcome-hero{background:linear-gradient(135deg,var(--orange-bg),#fff);border:1px solid rgba(232,89,12,.12);border-radius:var(--r-lg);padding:28px;position:relative;overflow:hidden}
        .welcome-hero::before{content:'';position:absolute;right:-60px;top:-60px;width:200px;height:200px;border-radius:50%;background:rgba(232,89,12,.06);filter:blur(40px)}
        .welcome-hero h3{font-size:20px;font-weight:800;margin-bottom:6px}
        .welcome-hero p{font-size:14px;color:var(--text-2);line-height:1.5}
        .welcome-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:20px}
        .welcome-stat{background:rgba(255,255,255,.7);border:1px solid var(--border);border-radius:var(--r);padding:12px 16px}
        .welcome-stat .label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text-3)}
        .welcome-stat .val{font-size:14px;font-weight:600;margin-top:4px}
        .step-list{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:16px}
        .step-list-item{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:12px 16px;display:flex;align-items:center;justify-content:space-between}
        .step-list-item .sli-name{font-size:13px;font-weight:600}
        .step-list-item .sli-desc{font-size:11px;color:var(--text-3);margin-top:2px}
        .optional-badge{display:inline-flex;padding:2px 8px;border-radius:99px;font-size:10px;font-weight:700;background:var(--surface-2);color:var(--text-3);border:1px solid var(--border)}

        /* FORM EXTRAS */
        .color-picker-wrap{display:flex;align-items:center;gap:12px}
        .color-picker-wrap input[type=color]{width:56px;height:40px;border:1.5px solid var(--border);border-radius:var(--r-sm);background:var(--surface);cursor:pointer;padding:2px}
        .upload-area{display:flex;align-items:center;gap:12px;padding:16px;border:1.5px dashed var(--border-2);border-radius:var(--r);background:var(--surface-2);cursor:pointer}
        .upload-placeholder{width:48px;height:48px;border-radius:var(--r-sm);border:1px solid var(--border);background:var(--surface);display:flex;align-items:center;justify-content:center;flex-shrink:0}
        .upload-placeholder svg{width:20px;height:20px;color:var(--text-3)}
        .upload-info{font-size:13px;color:var(--text-2)}
        .upload-info span{font-weight:600;color:var(--orange);cursor:pointer}
        .upload-info small{display:block;font-size:11px;color:var(--text-3);margin-top:4px}
        .checkbox-label{display:flex;align-items:center;gap:8px;font-size:14px;font-weight:500;cursor:pointer}
        .checkbox-label input[type=checkbox]{width:18px;height:18px;accent-color:var(--orange)}
        .section-divider{border:none;border-top:1px solid var(--border);margin:20px 0}
        .section-title{font-size:13px;font-weight:700;color:var(--text);margin-bottom:12px}
        .format-preview{margin-top:8px;padding:10px 14px;border:1px solid var(--border);border-radius:var(--r-sm);background:var(--surface-2);font-size:13px;font-family:monospace;color:var(--text-2)}

        /* TOAST */
        .toast-container{position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px}
        .toast{padding:12px 20px;border-radius:var(--r);font-size:13px;font-weight:600;box-shadow:var(--sh-md);animation:fadeIn .3s ease}
        .toast-success{background:var(--green);color:#fff}
        .toast-error{background:var(--red);color:#fff}

        /* RESPONSIVE */
        @media(max-width:768px){
            .form-grid{grid-template-columns:1fr}
            .tax-item{grid-template-columns:1fr;gap:8px}
            .shop-card-grid{grid-template-columns:1fr}
            .review-card-grid{grid-template-columns:1fr}
            .p-label{display:none}
            .progress-steps{justify-content:center}
            .hour-times{flex-wrap:wrap}
        }
        @media(max-width:500px){
            .setup-main{padding:20px 16px}
            .wc-header,.wc-body{padding-left:20px;padding-right:20px}
            .wc-footer{padding:16px 20px;flex-direction:column;gap:10px}
        }
    </style>
</head>
<body>

    <div class="toast-container" id="toast-container"></div>

    {{-- TOPBAR --}}
    <div class="topbar">
        <div class="nav-brand">
            <div class="logo-mark">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/>
                </svg>
            </div>
            <span class="brand-name">{{ $siteName }}</span>
        </div>
        <div class="tb-right">
            <button type="button" class="skip-link" onclick="skipSetup()">Skip for now &rarr;</button>
            <div class="tb-user">
                <div class="tb-avatar">{{ $initials }}</div>
                {{ $user->name }}
            </div>
        </div>
    </div>

    {{-- MAIN --}}
    <div class="setup-main">

        {{-- PROGRESS --}}
        <div class="progress-section">
            <div class="progress-steps">
                @foreach(['Welcome','Business','Address','Branches','Brand','Operations','Tax','Team','Finish'] as $i => $label)
                <div class="p-step {{ $i === 0 ? 'active' : '' }}" data-step="{{ $i + 1 }}" onclick="goToStep({{ $i + 1 }})">
                    <div class="p-num">{{ $i + 1 }}</div>
                    <span class="p-label">{{ $label }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- WIZARD CARD --}}
        <div class="wizard-card">

            {{-- STEP 1: Welcome --}}
            <div class="step-panel active" id="step-1">
                <div class="wc-header">
                    <h2>Welcome to {{ $siteName }}</h2>
                    <p>Let&rsquo;s get your business ready. This wizard saves as you go. You can skip optional steps and come back later.</p>
                </div>
                <div class="wc-body">
                    <div class="welcome-hero">
                        <h3>Welcome, let&rsquo;s get your business ready</h3>
                        <p>This wizard saves as you go. You can skip optional steps and come back later.</p>
                        <div class="welcome-stats">
                            <div class="welcome-stat"><div class="label">Time</div><div class="val">3-5 minutes</div></div>
                            <div class="welcome-stat"><div class="label">Saving</div><div class="val">Auto-saved</div></div>
                            <div class="welcome-stat"><div class="label">Flexibility</div><div class="val">Optional steps</div></div>
                        </div>
                    </div>
                    <div style="margin-top:24px">
                        <div style="font-size:14px;font-weight:600;margin-bottom:8px">What you&rsquo;ll configure</div>
                        <div class="step-list">
                            <div class="step-list-item"><div><div class="sli-name">Business</div><div class="sli-desc">Confirm your business details.</div></div></div>
                            <div class="step-list-item"><div><div class="sli-name">Address</div><div class="sli-desc">Add your address and locale details.</div></div></div>
                            <div class="step-list-item"><div><div class="sli-name">Branches</div><div class="sli-desc">Add and manage your shop locations.</div></div></div>
                            <div class="step-list-item"><div><div class="sli-name">Brand</div><div class="sli-desc">Upload a logo and configure customer-facing info.</div></div><span class="optional-badge">Optional</span></div>
                            <div class="step-list-item"><div><div class="sli-name">Operations</div><div class="sli-desc">Set default operations and notification preferences.</div></div><span class="optional-badge">Optional</span></div>
                            <div class="step-list-item"><div><div class="sli-name">Tax</div><div class="sli-desc">Configure VAT and invoice numbering.</div></div><span class="optional-badge">Optional</span></div>
                            <div class="step-list-item"><div><div class="sli-name">Team</div><div class="sli-desc">Invite your team.</div></div><span class="optional-badge">Optional</span></div>
                            <div class="step-list-item"><div><div class="sli-name">Finish</div><div class="sli-desc">Review and complete setup.</div></div></div>
                        </div>
                    </div>
                    <div style="margin-top:20px;font-size:12px;color:var(--text-3)">You can adjust everything later from Settings.</div>
                </div>
            </div>

            {{-- STEP 2: Business --}}
            <div class="step-panel" id="step-2">
                <div class="wc-header">
                    <h2>Confirm your business details</h2>
                    <p>This information helps us set up your workspace and customise your experience.</p>
                </div>
                <div class="wc-body">
                    <div class="form-grid">
                        <div class="form-group full">
                            <label class="form-label">Business Name</label>
                            <div class="input-wrap">
                                <input type="text" class="form-input" id="f-name" placeholder="e.g. QuickFix Electronics" value="{{ $tenant->name }}">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Display Name <span class="form-sub">(optional)</span></label>
                            <div class="input-wrap">
                                <input type="text" class="form-input" id="f-display-name" placeholder="Defaults to business name" value="{{ data_get($state, 'identity.display_name', '') }}">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            </div>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Primary Contact Person Name</label>
                            <div class="input-wrap">
                                <input type="text" class="form-input" id="f-primary-contact" placeholder="e.g. Alex Johnson" value="{{ data_get($state, 'identity.primary_contact_name', $user->name) }}">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Contact Email</label>
                            <div class="input-wrap">
                                <input type="email" class="form-input" id="f-email" placeholder="billing@company.com" value="{{ $tenant->contact_email ?? $user->email }}">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Contact Phone</label>
                            <div class="input-wrap">
                                <input type="tel" class="form-input" id="f-phone" placeholder="+1 555 123 4567" value="{{ $tenant->contact_phone ?? '' }}">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            </div>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Business Registration Number <span class="form-sub">(optional)</span></label>
                            <div class="input-wrap">
                                <input type="text" class="form-input" id="f-reg-number" placeholder="e.g. CRN-123456" value="{{ data_get($state, 'identity.registration_number', '') }}">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- STEP 3: Address --}}
            <div class="step-panel" id="step-3">
                <div class="wc-header">
                    <h2>Add your address &amp; locale</h2>
                    <p>Set your billing country, currency, and business address.</p>
                </div>
                <div class="wc-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Billing Country <span class="form-sub">(2-letter)</span></label>
                            <div class="input-wrap">
                                <input type="text" class="form-input" id="f-country" placeholder="US" value="{{ $tenant->billing_country ?? '' }}" maxlength="2" style="text-transform:uppercase">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Currency <span class="form-sub">(3-letter)</span></label>
                            <div class="input-wrap">
                                <input type="text" class="form-input" id="f-currency" placeholder="USD" value="{{ $tenant->currency ?? '' }}" maxlength="3" style="text-transform:uppercase">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">VAT Number <span class="form-sub">(optional)</span></label>
                            <div class="input-wrap">
                                <input type="text" class="form-input" id="f-vat" placeholder="EU123456789" value="{{ $tenant->billing_vat_number ?? '' }}">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                        </div>
                        <div class="form-group full" style="margin-top:4px"><div class="section-title">Address</div></div>
                        <div class="form-group full">
                            <label class="form-label">Address Line 1</label>
                            <input type="text" class="form-input" id="f-addr1" style="padding-left:14px" placeholder="742 Maple Avenue" value="{{ data_get($address, 'line1', '') }}">
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Address Line 2 <span class="form-sub">(optional)</span></label>
                            <input type="text" class="form-input" id="f-addr2" style="padding-left:14px" placeholder="Suite, Unit, Floor" value="{{ data_get($address, 'line2', '') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">City</label>
                            <input type="text" class="form-input" id="f-city" style="padding-left:14px" placeholder="Portland" value="{{ data_get($address, 'city', '') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">State / Region</label>
                            <input type="text" class="form-input" id="f-state" style="padding-left:14px" placeholder="OR" value="{{ data_get($address, 'state', '') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Postal Code</label>
                            <input type="text" class="form-input" id="f-postal" style="padding-left:14px" placeholder="97201" value="{{ data_get($address, 'postal_code', '') }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- STEP 4: Branches --}}
            <div class="step-panel" id="step-4">
                <div class="wc-header">
                    <h2>Set up your branches</h2>
                    <p>Add the physical locations where your business operates. You can always add more later.</p>
                </div>
                <div class="wc-body">
                    <div class="shop-list" id="branches-list">
                        {{-- Branches loaded dynamically via JS --}}
                    </div>
                    <button type="button" class="add-row-btn" onclick="addBranch()">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add Another Branch
                    </button>
                </div>
            </div>

            {{-- STEP 5: Brand (Optional) --}}
            <div class="step-panel" id="step-5">
                <div class="wc-header">
                    <h2>Brand &amp; customer-facing info</h2>
                    <p>Upload a logo, set a brand colour, and configure customer-facing contact details.</p>
                </div>
                <div class="wc-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Brand Colour</label>
                            <div class="color-picker-wrap">
                                <input type="color" id="f-brand-color-picker" value="{{ $tenant->brand_color ?? '#e8590c' }}">
                                <input type="text" class="form-input" id="f-brand-color" style="padding-left:14px" value="{{ $tenant->brand_color ?? '#e8590c' }}" placeholder="#2563eb">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Logo</label>
                            <div class="upload-area" onclick="document.getElementById('f-logo').click()">
                                <div class="upload-placeholder" id="logo-preview">
                                    @if($tenant->logo_url)
                                        <img src="{{ $tenant->logo_url }}" alt="Logo" style="width:100%;height:100%;object-fit:cover;border-radius:var(--r-sm)">
                                    @else
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    @endif
                                </div>
                                <div class="upload-info"><span>Click to upload</span> or drag and drop<small>PNG, JPG or WEBP up to 5MB</small></div>
                            </div>
                            <input type="file" id="f-logo" accept="image/png,image/jpeg,image/webp" style="display:none" onchange="handleLogoChange(this)">
                        </div>
                        <div class="form-group full" style="margin-top:4px"><div class="section-title">Customer-facing contact (optional)</div></div>
                        <div class="form-group">
                            <label class="form-label">Support Email</label>
                            <div class="input-wrap">
                                <input type="email" class="form-input" id="f-support-email" placeholder="support@quickfix.com" value="{{ data_get($state, 'brand.support_email', '') }}">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Support Phone</label>
                            <div class="input-wrap">
                                <input type="tel" class="form-input" id="f-support-phone" placeholder="+1 555 000 0000" value="{{ data_get($state, 'brand.support_phone', '') }}">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            </div>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Website <span class="form-sub">(optional)</span></label>
                            <div class="input-wrap">
                                <input type="url" class="form-input" id="f-website" placeholder="https://quickfix.com" value="{{ data_get($state, 'brand.website', '') }}">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                            </div>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Document Footer Text <span class="form-sub">(optional)</span></label>
                            <textarea class="form-input" id="f-footer-text" style="padding-left:14px" placeholder="e.g. Thank you for your business!">{{ data_get($state, 'brand.footer_text', '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- STEP 6: Operations (Optional) --}}
            <div class="step-panel" id="step-6">
                <div class="wc-header">
                    <h2>Operations &amp; preferences</h2>
                    <p>Set your default operations and notification preferences.</p>
                </div>
                <div class="wc-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Timezone</label>
                            <div class="input-wrap">
                                <select class="form-select" id="f-timezone" style="padding-left:42px">
                                    @php $tz = $tenant->timezone ?? 'UTC'; @endphp
                                    @foreach(['UTC','Europe/London','Europe/Berlin','America/New_York','America/Chicago','America/Denver','America/Los_Angeles','Asia/Tokyo','Australia/Sydney'] as $t)
                                        <option value="{{ $t }}" {{ $tz === $t ? 'selected' : '' }}>{{ $t }}</option>
                                    @endforeach
                                </select>
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Language</label>
                            <div class="input-wrap">
                                <input type="text" class="form-input" id="f-language" value="{{ $tenant->language ?? 'en' }}" disabled style="background:var(--surface-2)">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>
                            </div>
                        </div>
                        <div class="form-group full" style="margin-top:4px"><div class="section-title">Operations defaults (optional)</div></div>
                        <div class="form-group full">
                            <label class="form-label">Working Hours</label>
                            <input type="text" class="form-input" id="f-working-hours" style="padding-left:14px" placeholder="e.g. Mon&ndash;Fri 09:00&ndash;17:00" value="{{ data_get($state, 'operations.working_hours', '') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Default Labor Rate <span class="form-sub">(optional)</span></label>
                            <input type="text" class="form-input" id="f-labor-rate" style="padding-left:14px" placeholder="e.g. 75.00" value="{{ data_get($state, 'operations.labor_rate', '') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Default Warranty Terms <span class="form-sub">(optional)</span></label>
                            <input type="text" class="form-input" id="f-warranty" style="padding-left:14px" placeholder="e.g. 90 days parts and labor" value="{{ data_get($state, 'operations.warranty_terms', '') }}">
                        </div>
                        <hr class="section-divider" style="grid-column:1/-1">
                        <div class="form-group full">
                            <label class="checkbox-label"><input type="checkbox" id="f-notify-status" {{ data_get($state, 'operations.notify_status_change') ? 'checked' : '' }}> Email customer on repair status change</label>
                        </div>
                        <div class="form-group full">
                            <label class="checkbox-label"><input type="checkbox" id="f-notify-invoice" {{ data_get($state, 'operations.notify_invoice_created') ? 'checked' : '' }}> Email customer on invoice created</label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- STEP 7: Tax (Optional) --}}
            <div class="step-panel" id="step-7">
                <div class="wc-header">
                    <h2>Tax &amp; invoicing</h2>
                    <p>Configure VAT registration, invoice numbering, and tax rates (optional).</p>
                </div>
                <div class="wc-body">
                    <div class="form-grid">
                        <div class="form-group full">
                            <label class="checkbox-label"><input type="checkbox" id="f-tax-registered" {{ data_get($state, 'tax.tax_registered') ? 'checked' : '' }}> Tax/VAT registered?</label>
                        </div>
                        <div class="form-group">
                            <label class="form-label">VAT Number</label>
                            <div class="input-wrap">
                                <input type="text" class="form-input" id="f-tax-vat" placeholder="EU123456789" value="{{ $tenant->billing_vat_number ?? '' }}">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Invoice Prefix <span class="form-sub">(optional)</span></label>
                            <div class="input-wrap">
                                <input type="text" class="form-input" id="f-invoice-prefix" placeholder="RB" value="{{ data_get($state, 'tax.invoice_prefix', '') }}">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
                            </div>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Invoice Format Preview</label>
                            <div class="format-preview" id="invoice-preview">{{ data_get($state, 'tax.invoice_prefix', 'RB') }}-{{ $tenant->slug }}-{{ date('Y') }}-000001</div>
                        </div>
                    </div>
                    <hr class="section-divider">
                    <div class="form-group" style="margin-bottom:16px">
                        <label class="checkbox-label"><input type="checkbox" id="f-taxes-enabled" {{ data_get($state, 'tax.taxes_enabled', false) ? 'checked' : '' }}> Enable taxes</label>
                    </div>
                    <div style="display:grid;grid-template-columns:2fr 1fr 0.8fr 0.8fr auto;gap:12px;margin-bottom:10px;padding:0 16px;font-size:11px;font-weight:700;color:var(--text-3);text-transform:uppercase;letter-spacing:.05em">
                        <div>Tax Name</div><div>Rate (%)</div><div>Active</div><div>Default</div><div style="width:32px"></div>
                    </div>
                    <div class="tax-list" id="tax-list">
                        {{-- Taxes loaded dynamically via JS --}}
                    </div>
                    <button type="button" class="add-row-btn" style="margin-top:12px" onclick="addTaxRow()">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add Another Tax
                    </button>
                    <hr class="section-divider">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Invoice Amounts</label>
                            <select class="form-select" id="f-tax-type" style="padding-left:14px">
                                <option value="exclusive" {{ data_get($state, 'tax.tax_type', 'exclusive') === 'exclusive' ? 'selected' : '' }}>Exclusive</option>
                                <option value="inclusive" {{ data_get($state, 'tax.tax_type') === 'inclusive' ? 'selected' : '' }}>Inclusive</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- STEP 8: Team (Optional) --}}
            <div class="step-panel" id="step-8">
                <div class="wc-header">
                    <h2>Invite your team</h2>
                    <p>Invite team members to join your workspace. You can always add more later.</p>
                </div>
                <div class="wc-body">
                    <div class="form-grid">
                        <div class="form-group full">
                            <label class="form-label">Invite Team Members <span class="form-sub">(optional)</span></label>
                            <textarea class="form-input" id="f-team-emails" style="padding-left:14px;min-height:100px" placeholder="Enter emails separated by commas&#10;e.g. alex@quickfix.com, sarah@quickfix.com">{{ data_get($state, 'team.emails', '') }}</textarea>
                            <div style="margin-top:6px;font-size:11px;color:var(--text-3)">Invites are stored for later processing.</div>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Default Role for Invites <span class="form-sub">(optional)</span></label>
                            <div class="input-wrap">
                                <select class="form-select" id="f-team-role" style="padding-left:42px">
                                    @php $role = data_get($state, 'team.default_role', 'member'); @endphp
                                    @foreach(['member','technician','front_desk','owner'] as $r)
                                        <option value="{{ $r }}" {{ $role === $r ? 'selected' : '' }}>{{ $r }}</option>
                                    @endforeach
                                </select>
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </div>
                            <div style="margin-top:6px;font-size:11px;color:var(--text-3)">Example values: owner, member, technician, front_desk.</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- STEP 9: Finish --}}
            <div class="step-panel" id="step-9">
                <div class="wc-header">
                    <h2>Review &amp; complete setup</h2>
                    <p>Please confirm the details below. Clicking complete will finish setup and take you to the dashboard.</p>
                </div>
                <div class="wc-body">
                    <div class="review-card-grid" id="review-grid">
                        {{-- Populated by JS --}}
                    </div>
                    <div class="launch-section">
                        <p>Your workspace is ready. Complete setup and start managing your repair shop like a pro!</p>
                        <button type="button" class="btn btn-success" id="btn-complete" onclick="completeSetup()">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:18px;height:18px"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            Complete Setup
                        </button>
                    </div>
                </div>
            </div>

            {{-- FOOTER NAV --}}
            <div class="wc-footer" id="wizard-footer">
                <button type="button" class="btn btn-ghost" id="btn-prev" onclick="prevStep()" style="visibility:hidden">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Previous
                </button>
                <div style="font-size:12px;color:var(--text-3)">Step <span id="step-current">1</span> of 9</div>
                <div style="display:flex;gap:8px;align-items:center">
                    <button type="button" class="btn btn-secondary" id="btn-skip" onclick="nextStep()" style="display:none">Skip for now</button>
                    <button type="button" class="btn btn-primary" id="btn-next" onclick="nextStep()">
                        Start Setup
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    const API_BASE = @json($apiBase);
    const DASHBOARD_URL = @json($dashboardUrl);
    const TENANT_SLUG = @json($tenant->slug);
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

    let currentStep = {{ (int)($tenant->setup_step ?: 1) }};
    const totalSteps = 9;
    const optionalSteps = [5, 6, 7, 8];
    let saving = false;
    let branches = [];
    let taxRows = @json(data_get($state, 'tax.taxes', [])) || [];

    // ── Helpers ──
    function val(id) { return (document.getElementById(id)?.value ?? '').trim(); }
    function checked(id) { return !!document.getElementById(id)?.checked; }
    function toast(msg, type = 'success') {
        const el = document.createElement('div');
        el.className = 'toast toast-' + type;
        el.textContent = msg;
        document.getElementById('toast-container').appendChild(el);
        setTimeout(() => el.remove(), 4000);
    }
    async function apiFetch(path, opts = {}) {
        const url = API_BASE + path;
        const headers = { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, ...(opts.headers || {}) };
        if (!(opts.body instanceof FormData)) headers['Content-Type'] = 'application/json';
        const res = await fetch(url, { credentials: 'same-origin', ...opts, headers });
        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            throw new Error(err.message || 'Request failed');
        }
        return res.json();
    }

    // ── Navigation ──
    function goToStep(n) {
        if (n < 1 || n > totalSteps) return;
        saveCurrentStep();
        currentStep = n;
        updateUI();
        if (n === 4) loadBranches();
        if (n === 9) buildReview();
    }

    function nextStep() {
        if (currentStep < totalSteps) {
            saveCurrentStep();
            currentStep++;
            updateUI();
            if (currentStep === 4) loadBranches();
            if (currentStep === 9) buildReview();
        }
    }

    function prevStep() {
        if (currentStep > 1) {
            saveCurrentStep();
            currentStep--;
            updateUI();
            if (currentStep === 4) loadBranches();
        }
    }

    function updateUI() {
        document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
        document.getElementById('step-' + currentStep).classList.add('active');
        document.querySelectorAll('.p-step').forEach(s => {
            const sn = parseInt(s.dataset.step);
            s.classList.remove('active', 'done');
            if (sn === currentStep) s.classList.add('active');
            else if (sn < currentStep) s.classList.add('done');
        });
        document.getElementById('btn-prev').style.visibility = currentStep === 1 ? 'hidden' : 'visible';
        const nextBtn = document.getElementById('btn-next');
        const skipBtn = document.getElementById('btn-skip');
        if (currentStep === totalSteps) {
            nextBtn.style.display = 'none';
            skipBtn.style.display = 'none';
        } else {
            nextBtn.style.display = 'inline-flex';
            nextBtn.innerHTML = currentStep === 1
                ? 'Start Setup <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>'
                : 'Next Step <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>';
            skipBtn.style.display = optionalSteps.includes(currentStep) ? 'inline-flex' : 'none';
        }
        document.getElementById('step-current').textContent = currentStep;
    }

    // ── Auto-save on step change ──
    async function saveCurrentStep() {
        if (saving) return;
        saving = true;
        try {
            const payload = collectFormData();
            payload.setup_step = String(currentStep);
            await apiFetch('/setup', { method: 'PATCH', body: JSON.stringify(payload) });
        } catch (e) {
            console.warn('Auto-save failed:', e.message);
        } finally {
            saving = false;
        }
    }

    function collectFormData() {
        return {
            name: val('f-name') || undefined,
            contact_email: val('f-email') || undefined,
            contact_phone: val('f-phone') || undefined,
            billing_country: val('f-country')?.toUpperCase() || undefined,
            currency: val('f-currency')?.toUpperCase() || undefined,
            billing_vat_number: val('f-vat') || val('f-tax-vat') || undefined,
            billing_address_json: {
                line1: val('f-addr1'),
                line2: val('f-addr2'),
                city: val('f-city'),
                state: val('f-state'),
                postal_code: val('f-postal'),
            },
            timezone: val('f-timezone') || undefined,
            language: val('f-language') || 'en',
            brand_color: val('f-brand-color') || undefined,
            setup_state: {
                identity: {
                    display_name: val('f-display-name'),
                    primary_contact_name: val('f-primary-contact'),
                    registration_number: val('f-reg-number'),
                },
                brand: {
                    support_email: val('f-support-email'),
                    support_phone: val('f-support-phone'),
                    website: val('f-website'),
                    footer_text: val('f-footer-text'),
                },
                operations: {
                    working_hours: val('f-working-hours'),
                    labor_rate: val('f-labor-rate'),
                    warranty_terms: val('f-warranty'),
                    notify_status_change: checked('f-notify-status'),
                    notify_invoice_created: checked('f-notify-invoice'),
                },
                tax: {
                    tax_registered: checked('f-tax-registered'),
                    invoice_prefix: val('f-invoice-prefix'),
                    taxes_enabled: checked('f-taxes-enabled'),
                    tax_type: val('f-tax-type'),
                    taxes: collectTaxRows(),
                },
                team: {
                    emails: val('f-team-emails'),
                    default_role: val('f-team-role'),
                },
            },
        };
    }

    // ── Brand color sync ──
    document.getElementById('f-brand-color-picker')?.addEventListener('input', e => {
        document.getElementById('f-brand-color').value = e.target.value;
    });
    document.getElementById('f-brand-color')?.addEventListener('input', e => {
        if (/^#[0-9a-fA-F]{6}$/.test(e.target.value)) {
            document.getElementById('f-brand-color-picker').value = e.target.value;
        }
    });

    // ── Logo upload ──
    function handleLogoChange(input) {
        if (!input.files || !input.files[0]) return;
        const file = input.files[0];
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('logo-preview').innerHTML = '<img src="' + e.target.result + '" alt="Logo" style="width:100%;height:100%;object-fit:cover;border-radius:var(--r-sm)">';
        };
        reader.readAsDataURL(file);

        // Upload immediately
        const fd = new FormData();
        fd.append('logo', file);
        fd.append('setup_step', String(currentStep));
        apiFetch('/setup', { method: 'POST', body: fd, headers: { 'X-HTTP-Method-Override': 'PATCH' } })
            .then(() => toast('Logo uploaded'))
            .catch(e => toast(e.message, 'error'));
    }

    // ── Invoice prefix preview ──
    document.getElementById('f-invoice-prefix')?.addEventListener('input', e => {
        const prefix = e.target.value || 'RB';
        document.getElementById('invoice-preview').textContent = prefix + '-' + TENANT_SLUG + '-' + new Date().getFullYear() + '-000001';
    });

    // ── Branches ──
    async function loadBranches() {
        try {
            const data = await apiFetch('/branches');
            branches = data.data || data.branches || data || [];
            if (!Array.isArray(branches)) branches = [];
            renderBranches();
        } catch (e) {
            console.warn('Failed to load branches:', e.message);
        }
    }

    function renderBranches() {
        const list = document.getElementById('branches-list');
        list.innerHTML = '';
        branches.forEach((b, i) => {
            const isDefault = b.is_default || b.id === {{ $tenant->default_branch_id ?? 'null' }};
            list.innerHTML += `
            <div class="shop-card" data-branch-id="${b.id || ''}">
                <div class="shop-card-header">
                    <div class="shop-card-num">
                        <div class="shop-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg></div>
                        <span>${b.name || 'Branch ' + (i + 1)}</span>
                    </div>
                    ${isDefault ? '<span class="primary-badge">&#x2726; Default</span>' : '<button type="button" class="remove-btn" style="width:28px;height:28px" onclick="removeBranch(' + i + ')"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>'}
                </div>
                <div class="shop-card-grid">
                    <div class="form-group"><label class="form-label">Branch Name</label><input type="text" class="form-input branch-name" value="${b.name || ''}" data-idx="${i}"></div>
                    <div class="form-group"><label class="form-label">Branch Code</label><input type="text" class="form-input branch-code" value="${b.code || ''}" maxlength="16" data-idx="${i}"></div>
                    <div class="form-group"><label class="form-label">Phone</label><input type="tel" class="form-input branch-phone" value="${b.phone || ''}" data-idx="${i}"></div>
                    <div class="form-group"><label class="form-label">Active</label><select class="form-select branch-active" style="padding-left:14px" data-idx="${i}"><option value="1" ${b.is_active !== false && b.is_active !== 0 ? 'selected' : ''}>Yes</option><option value="0" ${b.is_active === false || b.is_active === 0 ? 'selected' : ''}>No</option></select></div>
                    <div class="form-group full"><label class="form-label">Address</label><input type="text" class="form-input branch-address" value="${b.address || ''}" data-idx="${i}"></div>
                </div>
            </div>`;
        });
    }

    function addBranch() {
        branches.push({ name: '', code: '', phone: '', is_active: true, address: '' });
        renderBranches();
    }

    async function removeBranch(idx) {
        const b = branches[idx];
        if (b && b.id) {
            try {
                await apiFetch('/branches/' + b.id, { method: 'DELETE' });
            } catch (e) { toast(e.message, 'error'); return; }
        }
        branches.splice(idx, 1);
        renderBranches();
    }

    // Save branches before leaving step 4
    async function saveBranches() {
        const cards = document.querySelectorAll('.shop-card');
        for (let i = 0; i < cards.length; i++) {
            const card = cards[i];
            const branchData = {
                name: card.querySelector('.branch-name')?.value || '',
                code: card.querySelector('.branch-code')?.value || '',
                phone: card.querySelector('.branch-phone')?.value || '',
                is_active: card.querySelector('.branch-active')?.value === '1',
                address: card.querySelector('.branch-address')?.value || '',
            };
            if (branches[i] && branches[i].id) {
                try {
                    await apiFetch('/branches/' + branches[i].id, { method: 'PUT', body: JSON.stringify(branchData) });
                } catch (e) { console.warn('Branch update failed:', e.message); }
            } else if (branchData.name) {
                try {
                    const res = await apiFetch('/branches', { method: 'POST', body: JSON.stringify(branchData) });
                    if (res.data) branches[i] = res.data;
                    else if (res.branch) branches[i] = res.branch;
                } catch (e) { console.warn('Branch create failed:', e.message); }
            }
        }
    }

    // Hook branch save into step change
    const _origSave = saveCurrentStep;

    // ── Tax rows ──
    function collectTaxRows() {
        const rows = [];
        document.querySelectorAll('#tax-list .tax-item').forEach(row => {
            rows.push({
                name: row.querySelector('.tax-name')?.value || '',
                rate: row.querySelector('.tax-rate')?.value || '',
                active: row.querySelector('.tax-active')?.value === 'Yes',
                is_default: row.querySelector('.tax-default')?.value === 'Yes',
            });
        });
        return rows.length ? rows : taxRows;
    }

    function renderTaxRows() {
        const list = document.getElementById('tax-list');
        list.innerHTML = '';
        taxRows.forEach((t, i) => {
            list.innerHTML += `
            <div class="tax-item" style="grid-template-columns:2fr 1fr 0.8fr 0.8fr auto">
                <input type="text" class="tax-name" value="${t.name || ''}">
                <input type="text" class="tax-rate" value="${t.rate || ''}">
                <select class="tax-active"><option ${t.active !== false ? 'selected' : ''}>Yes</option><option ${t.active === false ? 'selected' : ''}>No</option></select>
                <select class="tax-default"><option ${t.is_default ? 'selected' : ''}>Yes</option><option ${!t.is_default ? 'selected' : ''}>No</option></select>
                <button type="button" class="remove-btn" onclick="removeTaxRow(${i})"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>`;
        });
    }

    function addTaxRow() {
        taxRows.push({ name: '', rate: '', active: true, is_default: false });
        renderTaxRows();
    }

    function removeTaxRow(idx) {
        taxRows.splice(idx, 1);
        renderTaxRows();
    }

    // ── Review ──
    function buildReview() {
        const grid = document.getElementById('review-grid');
        const d = collectFormData();
        const st = d.setup_state || {};
        const addr = d.billing_address_json || {};
        const plan = @json($tenant->plan);

        grid.innerHTML = `
        <div class="review-block">
            <div class="review-block-title"><span>Business</span><a href="#" onclick="goToStep(2);return false">Edit</a></div>
            <div class="review-row"><span class="review-label">Name</span><span class="review-value">${d.name || '&mdash;'}</span></div>
            <div class="review-row"><span class="review-label">Contact</span><span class="review-value">${d.contact_email || '&mdash;'}</span></div>
            <div class="review-row"><span class="review-label">Primary Contact</span><span class="review-value">${st.identity?.primary_contact_name || '&mdash;'}</span></div>
        </div>
        <div class="review-block">
            <div class="review-block-title"><span>Address & Locale</span><a href="#" onclick="goToStep(3);return false">Edit</a></div>
            <div class="review-row"><span class="review-label">Country</span><span class="review-value">${d.billing_country || '&mdash;'}</span></div>
            <div class="review-row"><span class="review-label">Currency</span><span class="review-value">${d.currency || '&mdash;'}</span></div>
            <div class="review-row"><span class="review-label">Address</span><span class="review-value">${[addr.line1, addr.city].filter(Boolean).join(', ') || '&mdash;'}</span></div>
            <div class="review-row"><span class="review-label">VAT</span><span class="review-value" style="${d.billing_vat_number ? '' : 'color:var(--text-3)'}">${d.billing_vat_number || '&mdash;'}</span></div>
        </div>
        <div class="review-block">
            <div class="review-block-title"><span>Branches (${branches.length})</span><a href="#" onclick="goToStep(4);return false">Edit</a></div>
            ${branches.map(b => '<div class="review-row"><span class="review-label">' + (b.name || '&mdash;') + '</span><span class="review-value">' + (b.code || '') + '</span></div>').join('') || '<div class="review-row"><span class="review-label" style="color:var(--text-3)">No branches added</span></div>'}
        </div>
        <div class="review-block">
            <div class="review-block-title"><span>Branding</span><a href="#" onclick="goToStep(5);return false">Edit</a></div>
            <div class="review-row"><span class="review-label">Colour</span><span class="review-value">${d.brand_color || '&mdash;'}</span></div>
            <div class="review-row"><span class="review-label">Logo</span><span class="review-value" style="color:var(--text-3)">${document.querySelector('#logo-preview img') ? 'Uploaded' : '&mdash;'}</span></div>
        </div>
        <div class="review-block">
            <div class="review-block-title"><span>Operations</span><a href="#" onclick="goToStep(6);return false">Edit</a></div>
            <div class="review-row"><span class="review-label">Timezone</span><span class="review-value">${d.timezone || '&mdash;'}</span></div>
            <div class="review-row"><span class="review-label">Language</span><span class="review-value">${d.language || 'en'}</span></div>
        </div>
        <div class="review-block">
            <div class="review-block-title"><span>Tax & Invoicing</span><a href="#" onclick="goToStep(7);return false">Edit</a></div>
            <div class="review-row"><span class="review-label">Registered</span><span class="review-value">${st.tax?.tax_registered ? 'Yes' : 'No'}</span></div>
            <div class="review-row"><span class="review-label">Prefix</span><span class="review-value">${st.tax?.invoice_prefix || '&mdash;'}</span></div>
        </div>
        <div class="review-block">
            <div class="review-block-title"><span>Team</span><a href="#" onclick="goToStep(8);return false">Edit</a></div>
            <div class="review-row"><span class="review-label">Invites</span><span class="review-value" style="${st.team?.emails ? '' : 'color:var(--text-3)'}">${st.team?.emails ? st.team.emails.split(',').length + ' email(s)' : '&mdash;'}</span></div>
            <div class="review-row"><span class="review-label">Role</span><span class="review-value">${st.team?.default_role || 'member'}</span></div>
        </div>
        ${plan ? `<div class="review-block" style="background:var(--orange-bg);border-color:rgba(232,89,12,.15)">
            <div class="review-block-title"><span>Your Plan</span></div>
            <div class="review-row"><span class="review-label">Plan</span><span class="review-value" style="color:var(--orange);font-weight:700">${plan.name || '&mdash;'}</span></div>
        </div>` : ''}`;
    }

    // ── Complete setup ──
    async function completeSetup() {
        const btn = document.getElementById('btn-complete');
        btn.disabled = true;
        btn.textContent = 'Completing...';

        try {
            // Save current form data first
            await saveCurrentStep();

            // Save any branch changes
            await saveBranches();

            // Call complete endpoint
            const d = collectFormData();
            await apiFetch('/setup/complete', {
                method: 'POST',
                body: JSON.stringify({
                    name: d.name,
                    contact_email: d.contact_email,
                    contact_phone: d.contact_phone,
                    billing_country: d.billing_country,
                    billing_address_json: d.billing_address_json,
                    currency: d.currency,
                    timezone: d.timezone,
                    language: d.language || 'en',
                }),
            });

            toast('Setup complete!');
            setTimeout(() => window.location.href = DASHBOARD_URL, 800);
        } catch (e) {
            toast(e.message, 'error');
            btn.disabled = false;
            btn.innerHTML = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:18px;height:18px"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg> Complete Setup';
        }
    }

    // ── Skip setup ──
    function skipSetup() {
        saveCurrentStep();
        window.location.href = DASHBOARD_URL;
    }

    // ── Init ──
    renderTaxRows();
    if (currentStep > 1) {
        goToStep(currentStep);
    }
    </script>
</body>
</html>
