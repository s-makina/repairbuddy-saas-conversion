@php
    $tenantSlug = $business ?? null;
    $tenant = $tenant ?? null;
    $activePage = 'home';
    $isSubdomain = request()->routeIs('tenant.subdomain.*') || request()->routeIs('tenant.estimates.public.*');
    $tenantRoutePrefix = $isSubdomain ? 'tenant.subdomain' : 'tenant';

    // Determine status styling
    $statusConfig = [
        'approve' => [
            'icon' => 'bi-check-lg',
            'title' => 'Estimate Approved!',
            'message' => 'Thank you for approving your estimate. We have received your confirmation and will begin processing your request shortly.',
            'bg_class' => 'rgba(16, 185, 129, 0.1)',
            'color' => '#10b981',
            'badge_class' => 'success',
        ],
        'reject' => [
            'icon' => 'bi-x-lg',
            'title' => 'Estimate Rejected',
            'message' => 'You have rejected the estimate. We have updated our records accordingly.',
            'bg_class' => 'rgba(107, 114, 128, 0.1)',
            'color' => '#6b7280',
            'badge_class' => 'secondary',
        ],
        'reject_form' => [
            'icon' => 'bi-x-lg',
            'title' => 'Reject Estimate',
            'message' => 'Please let us know why you are rejecting this estimate. This helps us improve our service.',
            'bg_class' => 'rgba(107, 114, 128, 0.1)',
            'color' => '#6b7280',
            'badge_class' => 'secondary',
        ],
        'error' => [
            'icon' => 'bi-exclamation-triangle',
            'title' => 'Action Failed',
            'message' => $message ?? 'We encountered an issue while processing your request. This could be due to an expired or invalid link.',
            'bg_class' => 'rgba(239, 68, 68, 0.1)',
            'color' => '#ef4444',
            'badge_class' => 'danger',
        ],
    ];
    $config = $statusConfig[$purpose] ?? $statusConfig['error'];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $config['title'] }} — {{ ($tenant->name ?? null) ?: 'RepairBuddy' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('repairbuddy/my_account/css/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tenant-public.css') }}">
    <style>
        /* RESULT SECTION */
        .result-section {
            min-height: 70vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px 28px;
            position: relative;
            overflow: hidden;
        }
        .result-section::before {
            content: '';
            position: absolute;
            top: -100px;
            right: -200px;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(253,103,66,.06) 0%, transparent 60%);
            pointer-events: none;
        }
        .result-card {
            max-width: 520px;
            width: 100%;
            background: var(--rb-surface);
            border: 1px solid var(--rb-border);
            border-radius: 24px;
            padding: 48px 40px;
            text-align: center;
            box-shadow: 0 4px 24px rgba(0,0,0,.06);
            position: relative;
            z-index: 1;
        }
        .result-icon {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }
        .result-icon i {
            font-size: 44px;
        }
        .result-card h1 {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -.02em;
            margin-bottom: 12px;
            color: var(--rb-text);
        }
        .result-card > p {
            font-size: 15px;
            color: var(--rb-text-2);
            line-height: 1.7;
            margin-bottom: 28px;
        }
        .estimate-info {
            background: rgba(6,62,112,.03);
            border: 1px solid var(--rb-border);
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 28px;
            text-align: left;
        }
        .estimate-info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid var(--rb-border);
        }
        .estimate-info-row:last-child {
            border-bottom: none;
        }
        .estimate-info-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--rb-text-3);
        }
        .estimate-info-value {
            font-size: 14px;
            font-weight: 700;
            color: var(--rb-text);
        }
        .estimate-info-value.badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 99px;
            font-size: 12px;
            font-weight: 700;
        }
        .estimate-info-value.badge.success {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
        }
        .estimate-info-value.badge.secondary {
            background: rgba(107, 114, 128, 0.15);
            color: #6b7280;
        }
        .estimate-info-value.badge.danger {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
        }
        .result-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .result-footer {
            margin-top: 24px;
            font-size: 13px;
            color: var(--rb-text-3);
        }
        .result-footer a {
            color: var(--rb-blue);
            font-weight: 600;
            text-decoration: none;
        }
        .result-footer a:hover {
            text-decoration: underline;
        }

        /* REJECTION FORM */
        .rejection-form {
            width: 100%;
            text-align: left;
        }
        .form-group {
            margin-bottom: 24px;
        }
        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--rb-text);
            margin-bottom: 8px;
        }
        .form-textarea {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--rb-border);
            border-radius: 12px;
            font-size: 14px;
            font-family: inherit;
            color: var(--rb-text);
            background: var(--rb-surface);
            resize: vertical;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-textarea:focus {
            outline: none;
            border-color: var(--rb-blue);
            box-shadow: 0 0 0 3px rgba(6, 62, 112, 0.1);
        }
        .form-textarea::placeholder {
            color: var(--rb-text-3);
        }

        @media(max-width:600px) {
            .result-section { padding: 40px 16px; }
            .result-card { padding: 32px 20px; }
            .result-card h1 { font-size: 24px; }
        }
    </style>
</head>
<body>
    @include('tenant.partials.tenant-nav', [
        'tenantSlug' => $tenantSlug,
        'tenant' => $tenant,
        'activePage' => $activePage
    ])

    <!-- RESULT SECTION -->
    <section class="result-section">
        <div class="result-card">
            <div class="result-icon" style="background: {{ $config['bg_class'] }}; color: {{ $config['color'] }};">
                <i class="bi {{ $config['icon'] }}"></i>
            </div>
            <h1>{{ $config['title'] }}</h1>
            <p>{{ $config['message'] }}</p>

            @if($purpose === 'reject_form')
                <!-- REJECTION FORM -->
                <form action="{{ route('tenant.estimates.public.reject.submit', ['business' => $tenantSlug, 'caseNumber' => $estimate->case_number ?? '']) }}" method="POST" class="rejection-form">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token ?? '' }}">
                    
                    <div class="form-group">
                        <label for="rejection_reason" class="form-label">Reason for rejection (optional)</label>
                        <textarea 
                            name="rejection_reason" 
                            id="rejection_reason" 
                            class="form-textarea" 
                            rows="4" 
                            placeholder="Please tell us why you are rejecting this estimate..."
                            maxlength="1000"
                        ></textarea>
                    </div>
                    
                    <div class="result-actions">
                        <button type="submit" class="btn btn-orange btn-lg">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Confirm Rejection
                        </button>
                        <a href="{{ route($tenantRoutePrefix . '.booking.show', ['business' => $tenantSlug]) }}" class="btn btn-outline btn-lg">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Cancel
                        </a>
                    </div>
                </form>
            @else
                <div class="estimate-info">
                    <div class="estimate-info-row">
                        <span class="estimate-info-label">Estimate Number</span>
                        <span class="estimate-info-value">#{{ $estimate->case_number ?? 'N/A' }}</span>
                    </div>
                    <div class="estimate-info-row">
                        <span class="estimate-info-label">Status</span>
                        <span class="estimate-info-value badge {{ $config['badge_class'] }}">
                            {{ ucfirst($estimate->status ?? 'Unknown') }}
                        </span>
                    </div>
                    @if($estimate->title ?? null)
                    <div class="estimate-info-row">
                        <span class="estimate-info-label">Description</span>
                        <span class="estimate-info-value">{{ $estimate->title }}</span>
                    </div>
                    @endif
                    @if($purpose === 'reject' && ($estimate->rejection_reason ?? null))
                    <div class="estimate-info-row">
                        <span class="estimate-info-label">Rejection Reason</span>
                        <span class="estimate-info-value">{{ $estimate->rejection_reason }}</span>
                    </div>
                    @endif
                </div>

                <div class="result-actions">
                    <a href="{{ route($tenantRoutePrefix . '.status.show', ['business' => $tenantSlug]) }}" class="btn btn-orange btn-lg">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        View Detailed Status
                    </a>
                    <a href="{{ route($tenantRoutePrefix . '.booking.show', ['business' => $tenantSlug]) }}" class="btn btn-outline btn-lg">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        Back to Home
                    </a>
                </div>

                <p class="result-footer">
                    If you have any questions, please contact us at
                    @if($tenant && $tenant->email)
                        <a href="mailto:{{ $tenant->email }}">{{ $tenant->email }}</a>
                    @else
                        our support line.
                    @endif
                </p>
            @endif
        </div>
    </section>

    @include('tenant.partials.tenant-footer', [
        'tenantSlug' => $tenantSlug,
        'tenant' => $tenant
    ])
</body>
</html>
