@php
    $siteName = ($tenant && $tenant->name) ? $tenant->name : config('app.name', 'RepairBuddy');
    $thePageTitle = __('Signature Link Expired') . ' — ' . $siteName;
@endphp
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $thePageTitle }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('repairbuddy/my_account/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('repairbuddy/my_account/css/bootstrap-icons.min.css') }}">
    <style>body { font-family: 'Inter', sans-serif; min-height: 100vh; }</style>
</head>
<body class="bg-light">
    <div style="max-width:600px;margin:0 auto;padding:3rem 1rem;">
        <div class="card border-0 shadow-lg" style="border-radius:16px;">
            <div class="card-body p-4 p-md-5 text-center">
                <div class="mb-4">
                    @if($tenant && $tenant->logo_url)
                        <img src="{{ $tenant->logo_url }}" alt="{{ $siteName }}" class="img-fluid" style="max-height:60px;">
                    @else
                        <h3 class="fw-bold text-primary">{{ $siteName }}</h3>
                    @endif
                </div>

                <div class="mb-4">
                    <div class="rounded-circle bg-danger bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width:80px;height:80px;">
                        <i class="bi bi-clock-history text-danger" style="font-size:3rem;"></i>
                    </div>
                </div>

                <h4 class="fw-bold mb-3">{{ __('This Signature Link Has Expired') }}</h4>
                <p class="text-muted mb-4">
                    {{ __('The signature link for') }} <strong>{{ $signatureRequest->signature_label }}</strong>
                    {{ __('on job') }} #{{ $job->case_number }}
                    {{ __('has expired and is no longer valid.') }}
                </p>

                <p class="text-muted small">
                    <i class="bi bi-info-circle me-1"></i>
                    {{ __('Please contact the business to request a new signature link.') }}
                </p>
            </div>
        </div>
    </div>
    <script src="{{ asset('repairbuddy/my_account/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
