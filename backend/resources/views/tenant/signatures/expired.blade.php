@php
    $siteName = ($tenant && $tenant->name) ? $tenant->name : config('app.name', 'RepairBuddy');
    $thePageTitle = __('Signature Link Expired') . ' - ' . $siteName;
@endphp
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $thePageTitle }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('repairbuddy/my_account/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('repairbuddy/my_account/css/bootstrap-icons.min.css') }}">
    <style>
        body { font-family: 'Inter', sans-serif; min-height: 100vh; background: #f8f9fb; }
        [data-bs-theme="dark"] body { background: #111827; }
        .sig-wrapper { max-width: 520px; margin: 0 auto; padding: 3rem 1rem; }
        .sig-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 6px 16px rgba(0,0,0,.04);
        }
        .sig-logo { text-align: center; }
        .sig-logo img { max-height: 48px; width: auto; }
        .sig-status-icon {
            width: 72px; height: 72px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="sig-wrapper">
        <div class="card sig-card">
            <div class="card-body p-4 p-md-5 text-center">
                {{-- Logo --}}
                <div class="sig-logo mb-4">
                    @if($tenant && $tenant->logo_url)
                        <img src="{{ $tenant->logo_url }}" alt="{{ $siteName }}">
                    @else
                        <h4 class="fw-bold text-primary mb-0">{{ $siteName }}</h4>
                    @endif
                </div>

                {{-- Status Icon --}}
                <div class="mb-4">
                    <div class="sig-status-icon" style="background:rgba(239,68,68,.08);">
                        <i class="bi bi-clock-history" style="font-size:2.25rem;color:#dc2626;"></i>
                    </div>
                </div>

                <h5 class="fw-bold mb-2">{{ __('Link Expired') }}</h5>
                <p class="text-muted mb-4" style="font-size:.9rem;">
                    {{ __('The signature link for') }}
                    <strong>{{ $signatureRequest->signature_label }}</strong>
                    {{ __('on job') }} <strong>#{{ $job->case_number }}</strong>
                    {{ __('has expired and is no longer valid.') }}
                </p>

                {{-- Details --}}
                <div class="rounded-3 bg-light p-3 mb-4 text-start" style="font-size:.84rem;">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-muted mb-1" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.04em;">{{ __('Job #') }}</div>
                            <div class="fw-bold">{{ $job->case_number }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted mb-1" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.04em;">{{ __('Expired At') }}</div>
                            <div class="fw-bold">{{ $signatureRequest->expires_at?->format('M d, Y H:i') ?? '---' }}</div>
                        </div>
                    </div>
                </div>

                <div class="rounded-3 border border-warning bg-warning bg-opacity-10 p-3 mb-0" style="font-size:.84rem;">
                    <i class="bi bi-info-circle text-warning me-1"></i>
                    <span class="text-muted">{{ __('Please contact the business to request a new signature link.') }}</span>
                </div>
            </div>
        </div>
    </div>
    <script src="{{ asset('repairbuddy/my_account/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
