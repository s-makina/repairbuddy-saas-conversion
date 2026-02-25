@php
    $siteName = ($tenant && $tenant->name) ? $tenant->name : config('app.name', 'RepairBuddy');
    $thePageTitle = __('Sign your job') . ' — ' . $siteName;
@endphp
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $thePageTitle }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('repairbuddy/my_account/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('repairbuddy/my_account/css/bootstrap-icons.min.css') }}">
    <style>
        body { font-family: 'Inter', sans-serif; min-height: 100vh; }
        .auth-container { max-width: 900px; margin: 0 auto; padding: 2rem 1rem; }
        .auth-card { border-radius: 16px; overflow: hidden; }
        .auth-logo { text-align: center; }
        .auth-logo img { max-height: 60px; width: auto; }
        #signatureCanvas { touch-action: none; border: 2px dashed #dee2e6; border-radius: 8px; cursor: crosshair; background: #fff; }
        [data-bs-theme="dark"] #signatureCanvas { background: #1a1a2e; border-color: #495057; }
        [data-bs-theme="dark"] body { background: #121212; }
    </style>
</head>

<body class="bg-light">
    <div class="auth-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-10 col-lg-8">
                    {{-- Top Bar --}}
                    <div class="d-flex justify-content-end mb-4">
                        <div class="dropdown">
                            <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" title="{{ __('Theme Settings') }}">
                                <i class="bi bi-palette"></i>
                            </button>
                            <ul class="dropdown-menu rounded-3 p-0">
                                <li>
                                    <div class="btn-group w-100" role="group">
                                        <button type="button" class="btn theme-option" data-theme="light" title="{{ __('Light Mode') }}">
                                            <i class="bi bi-sun"></i>
                                        </button>
                                        <button type="button" class="btn border-start border-end theme-option" data-theme="dark" title="{{ __('Dark Mode') }}">
                                            <i class="bi bi-moon"></i>
                                        </button>
                                        <button type="button" class="btn theme-option" data-theme="auto" title="{{ __('Auto Mode') }}">
                                            <i class="bi bi-circle-half"></i>
                                        </button>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>

                    {{-- Auth Card --}}
                    <div class="card auth-card border-0 shadow-lg">
                        <div class="card-body p-4 p-md-5">
                            {{-- Logo --}}
                            <div class="auth-logo mb-4">
                                @if($tenant && $tenant->logo_url)
                                    <img src="{{ $tenant->logo_url }}" alt="{{ $siteName }}" class="img-fluid">
                                @else
                                    <h3 class="fw-bold text-primary">{{ $siteName }}</h3>
                                @endif
                            </div>

                            <div class="text-center mb-3">
                                <h4 class="fw-bold mb-3">{{ __('Complete Your Signature Requirement') }}</h4>
                                @if(!empty($signatureRequest->signature_label))
                                    <div class="alert alert-info d-inline-block">
                                        <i class="bi bi-pen me-2"></i>
                                        {{ __('Signature for:') }}
                                        <strong>{{ $signatureRequest->signature_label }}</strong>
                                    </div>
                                @endif
                            </div>

                            {{-- Job Details Card --}}
                            <div class="card border shadow-sm mb-3">
                                <div class="card-body p-4">
                                    <div class="row">
                                        {{-- Customer Information --}}
                                        @if($customer)
                                        <div class="col-md-6 mb-4 mb-md-0">
                                            <div class="card h-100 border-0 bg-light">
                                                <div class="card-body">
                                                    <h6 class="card-subtitle mb-3 text-muted fw-bold">
                                                        <i class="bi bi-person-circle me-2"></i>{{ __('Customer Information') }}
                                                    </h6>
                                                    <div class="d-flex flex-column gap-2">
                                                        <div class="d-flex align-items-start">
                                                            <span class="text-muted me-2" style="min-width: 80px;">
                                                                <i class="bi bi-person me-1"></i>{{ __('Name') }}:
                                                            </span>
                                                            <span class="fw-medium">{{ $customer->name }}</span>
                                                        </div>
                                                        @if($customer->email)
                                                        <div class="d-flex align-items-start">
                                                            <span class="text-muted me-2" style="min-width: 80px;">
                                                                <i class="bi bi-envelope me-1"></i>{{ __('Email') }}:
                                                            </span>
                                                            <span class="fw-medium">{{ $customer->email }}</span>
                                                        </div>
                                                        @endif
                                                        @if($customer->phone)
                                                        <div class="d-flex align-items-start">
                                                            <span class="text-muted me-2" style="min-width: 80px;">
                                                                <i class="bi bi-telephone me-1"></i>{{ __('Phone') }}:
                                                            </span>
                                                            <span class="fw-medium">{{ $customer->phone }}</span>
                                                        </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @endif

                                        {{-- Job Information --}}
                                        <div class="col-md-6">
                                            <div class="card h-100 border-0 bg-light">
                                                <div class="card-body">
                                                    <h6 class="card-subtitle mb-3 text-muted fw-bold">
                                                        <i class="bi bi-tools me-2"></i>{{ __('Job Information') }}
                                                    </h6>
                                                    <div class="d-flex flex-column gap-2">
                                                        <div class="d-flex align-items-start">
                                                            <span class="text-muted me-2" style="min-width: 100px;">
                                                                <i class="bi bi-hash me-1"></i>{{ __('Order #') }}:
                                                            </span>
                                                            <span class="fw-bold text-primary">{{ $job->job_number ?? $job->id }}</span>
                                                        </div>
                                                        <div class="d-flex align-items-start">
                                                            <span class="text-muted me-2" style="min-width: 100px;">
                                                                <i class="bi bi-folder me-1"></i>{{ __('Case #') }}:
                                                            </span>
                                                            <span class="fw-medium">{{ $job->case_number }}</span>
                                                        </div>
                                                        <div class="d-flex align-items-start">
                                                            <span class="text-muted me-2" style="min-width: 100px;">
                                                                <i class="bi bi-calendar me-1"></i>{{ __('Created') }}:
                                                            </span>
                                                            <span class="fw-medium">{{ $job->created_at?->format('M d, Y') }}</span>
                                                        </div>
                                                        <div class="d-flex align-items-start">
                                                            <span class="text-muted me-2" style="min-width: 100px;">
                                                                <i class="bi bi-info-circle me-1"></i>{{ __('Status') }}:
                                                            </span>
                                                            <span class="badge bg-secondary">{{ $job->status_slug }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Signature Section --}}
                            <div class="card border shadow-sm">
                                <div class="card-header bg-primary py-3">
                                    <h5 class="card-title mb-0 text-white">
                                        <i class="bi bi-pen me-2"></i>{{ __('Signature Required') }}
                                    </h5>
                                </div>
                                <div class="card-body p-4">
                                    @if($canSign)
                                        <p class="text-muted mb-4">
                                            <i class="bi bi-info-circle me-2"></i>
                                            {{ __('Please provide your signature in the area below to acknowledge and approve the job details.') }}
                                        </p>

                                        {{-- Signature Pad --}}
                                        <div id="signaturepad" class="bg-light border rounded p-3 position-relative" style="min-height: 200px;">
                                            <canvas id="signatureCanvas" style="width:100%;height:200px;"></canvas>
                                        </div>

                                        <div class="mt-4 d-flex justify-content-between flex-wrap gap-2">
                                            <button type="button" class="btn btn-outline-secondary" id="clearBtn">
                                                <i class="bi bi-x-circle me-1"></i>{{ __('Clear Signature') }}
                                            </button>
                                            <button type="button" class="btn btn-primary" id="submitBtn">
                                                <i class="bi bi-check-circle me-1"></i>{{ __('Submit Signature') }}
                                            </button>
                                        </div>

                                        {{-- Error/Success Messages --}}
                                        <div id="signatureMessage" class="mt-3" style="display:none;"></div>
                                    @else
                                        <div class="alert alert-warning mb-0">
                                            <i class="bi bi-exclamation-triangle me-2"></i>
                                            {{ $statusMessage ?: __('You cannot sign this document at this time.') }}
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Footer Note --}}
                            <div class="text-center mt-4 pt-3 border-top">
                                <p class="text-muted small mb-0">
                                    <i class="bi bi-shield-check me-1"></i>
                                    {{ __('Your information is secure and will only be used for this job approval.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('repairbuddy/my_account/js/bootstrap.bundle.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.2.0/dist/signature_pad.umd.min.js"></script>

    @if($canSign)
    <script>
    (function() {
        const signatureParams = {
            submitUrl: '{{ route("tenant.signature.submit", ["business" => $tenant->slug, "verification" => $signatureRequest->verification_code]) }}',
            csrfToken: '{{ csrf_token() }}'
        };

        let signaturePad = null;

        document.addEventListener('DOMContentLoaded', function() {
            initSignaturePad();
            setupEventListeners();
            setupThemeSwitcher();
        });

        function initSignaturePad() {
            const container = document.getElementById('signaturepad');
            const canvas = document.getElementById('signatureCanvas');
            if (!container || !canvas) return;

            canvas.width = container.offsetWidth - 24;
            canvas.height = 200;

            signaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgb(255, 255, 255)',
                penColor: 'rgb(0, 0, 0)',
                minWidth: 1,
                maxWidth: 3,
                throttle: 16,
            });

            window.addEventListener('resize', function() {
                const data = signaturePad.toData();
                canvas.width = container.offsetWidth - 24;
                canvas.height = 200;
                signaturePad.clear();
                if (data && data.length > 0) {
                    signaturePad.fromData(data);
                }
            });
        }

        function setupEventListeners() {
            const clearBtn = document.getElementById('clearBtn');
            if (clearBtn) {
                clearBtn.addEventListener('click', function() {
                    if (signaturePad) signaturePad.clear();
                });
            }

            const submitBtn = document.getElementById('submitBtn');
            if (submitBtn) {
                submitBtn.addEventListener('click', uploadSignature);
            }

            // Prevent scrolling on touch for canvas
            const canvas = document.getElementById('signatureCanvas');
            if (canvas) {
                canvas.addEventListener('touchstart', function(e) {
                    if (e.target === canvas) e.preventDefault();
                }, { passive: false });
                canvas.addEventListener('touchmove', function(e) {
                    if (e.target === canvas) e.preventDefault();
                }, { passive: false });
            }
        }

        function setupThemeSwitcher() {
            document.querySelectorAll('.theme-option').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const theme = this.getAttribute('data-theme');
                    if (theme === 'auto') {
                        document.documentElement.removeAttribute('data-bs-theme');
                    } else {
                        document.documentElement.setAttribute('data-bs-theme', theme);
                    }
                });
            });
        }

        function showMessage(type, text) {
            const msgDiv = document.getElementById('signatureMessage');
            if (msgDiv) {
                msgDiv.style.display = 'block';
                msgDiv.className = 'mt-3 alert alert-' + (type === 'error' ? 'danger' : 'success');
                msgDiv.innerHTML = '<i class="bi bi-' + (type === 'error' ? 'exclamation-circle' : 'check-circle') + ' me-2"></i>' + text;
            }
        }

        function uploadSignature() {
            if (!signaturePad || signaturePad.isEmpty()) {
                showMessage('error', '{{ __("Please provide your signature first.") }}');
                return;
            }

            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> {{ __("Saving...") }}';
            submitBtn.disabled = true;

            // Convert signature to blob
            const signatureData = signaturePad.toDataURL('image/png');
            const blob = dataURLtoBlob(signatureData);
            const formData = new FormData();
            const fileName = 'signature-' + Date.now() + '.png';

            formData.append('signature_file', blob, fileName);
            formData.append('_token', signatureParams.csrfToken);

            fetch(signatureParams.submitUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;

                if (data.success) {
                    showMessage('success', data.message || '{{ __("Signature submitted successfully!") }}');
                    submitBtn.style.display = 'none';
                    document.getElementById('clearBtn').style.display = 'none';

                    if (data.data && data.data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.data.redirect;
                        }, 2000);
                    }
                } else {
                    showMessage('error', data.error || '{{ __("An error occurred. Please try again.") }}');
                }
            })
            .catch(error => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                showMessage('error', '{{ __("Network error. Please try again.") }}');
                console.error('Upload error:', error);
            });
        }

        function dataURLtoBlob(dataurl) {
            const arr = dataurl.split(',');
            const mime = arr[0].match(/:(.*?);/)[1];
            const bstr = atob(arr[1]);
            let n = bstr.length;
            const u8arr = new Uint8Array(n);
            while(n--) {
                u8arr[n] = bstr.charCodeAt(n);
            }
            return new Blob([u8arr], {type: mime});
        }
    })();
    </script>
    @endif
</body>
</html>
