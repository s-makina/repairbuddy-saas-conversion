@php
    $siteName = ($tenant && $tenant->name) ? $tenant->name : config('app.name', 'RepairBuddy');
    $thePageTitle = __('Sign your job') . ' - ' . $siteName;
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
        :root {
            --sig-primary: #2563eb;
            --sig-radius: 12px;
        }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: #f8f9fb;
        }
        [data-bs-theme="dark"] body { background: #111827; }

        .sig-wrapper { max-width: 720px; margin: 0 auto; padding: 2rem 1rem; }

        .sig-card {
            border: none;
            border-radius: var(--sig-radius);
            box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 6px 16px rgba(0,0,0,.04);
            overflow: hidden;
        }

        .sig-logo { text-align: center; }
        .sig-logo img { max-height: 48px; width: auto; }

        .sig-detail-row {
            display: flex;
            justify-content: space-between;
            padding: .45rem 0;
            font-size: .84rem;
            border-bottom: 1px solid rgba(0,0,0,.04);
        }
        .sig-detail-row:last-child { border-bottom: none; }
        .sig-detail-label { color: #6b7280; }
        .sig-detail-value { font-weight: 500; }

        .sig-canvas-wrap {
            background: #fff;
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            position: relative;
            transition: border-color .2s;
        }
        .sig-canvas-wrap:hover,
        .sig-canvas-wrap.active { border-color: var(--sig-primary); }
        [data-bs-theme="dark"] .sig-canvas-wrap {
            background: #1f2937;
            border-color: #374151;
        }
        .sig-canvas-wrap canvas {
            width: 100%;
            height: 180px;
            cursor: crosshair;
            touch-action: none;
            display: block;
        }
        .sig-canvas-hint {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #d1d5db;
            font-size: .82rem;
            pointer-events: none;
            transition: opacity .3s;
        }
        .sig-canvas-wrap.has-signature .sig-canvas-hint { opacity: 0; }

        .sig-step {
            display: flex;
            align-items: flex-start;
            gap: .75rem;
            font-size: .82rem;
        }
        .sig-step-num {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--sig-primary);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .7rem;
            font-weight: 600;
            flex-shrink: 0;
        }

        .theme-toggle { position: fixed; bottom: 1rem; right: 1rem; z-index: 10; }
    </style>
</head>

<body>
    {{-- Theme Toggle --}}
    <div class="theme-toggle">
        <div class="dropdown dropup">
            <button class="btn btn-light btn-sm border shadow-sm rounded-circle" style="width:36px;height:36px;" type="button" data-bs-toggle="dropdown" title="{{ __('Theme') }}">
                <i class="bi bi-circle-half" style="font-size:.85rem;"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow rounded-3 p-1 mb-1" style="min-width:auto;">
                <li><button class="dropdown-item rounded-2 py-1 px-2 theme-option" data-theme="light"><i class="bi bi-sun me-1"></i> {{ __('Light') }}</button></li>
                <li><button class="dropdown-item rounded-2 py-1 px-2 theme-option" data-theme="dark"><i class="bi bi-moon me-1"></i> {{ __('Dark') }}</button></li>
                <li><button class="dropdown-item rounded-2 py-1 px-2 theme-option" data-theme="auto"><i class="bi bi-circle-half me-1"></i> {{ __('Auto') }}</button></li>
            </ul>
        </div>
    </div>

    <div class="sig-wrapper">
        {{-- Main Card --}}
        <div class="card sig-card">
            <div class="card-body p-4 p-md-5">

                {{-- Logo --}}
                <div class="sig-logo mb-4">
                    @if($tenant && $tenant->logo_url)
                        <img src="{{ $tenant->logo_url }}" alt="{{ $siteName }}">
                    @else
                        <h4 class="fw-bold text-primary mb-0">{{ $siteName }}</h4>
                    @endif
                </div>

                {{-- Title --}}
                <div class="text-center mb-4">
                    <h5 class="fw-bold mb-2">{{ __('Signature Required') }}</h5>
                    @if(!empty($signatureRequest->signature_label))
                        <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary border px-3 py-2" style="font-size:.8rem;">
                            <i class="bi bi-pen me-1"></i>{{ $signatureRequest->signature_label }}
                        </span>
                    @endif
                </div>

                {{-- Details Grid --}}
                <div class="row g-3 mb-4">
                    @if($customer)
                    <div class="col-md-6">
                        <div class="rounded-3 bg-light p-3 h-100" style="font-size:.84rem;">
                            <div class="fw-semibold text-muted text-uppercase mb-2" style="font-size:.68rem;letter-spacing:.04em;">
                                <i class="bi bi-person me-1"></i>{{ __('Customer') }}
                            </div>
                            <div class="sig-detail-row">
                                <span class="sig-detail-label">{{ __('Name') }}</span>
                                <span class="sig-detail-value">{{ $customer->name }}</span>
                            </div>
                            @if($customer->email)
                            <div class="sig-detail-row">
                                <span class="sig-detail-label">{{ __('Email') }}</span>
                                <span class="sig-detail-value text-truncate" style="max-width:140px;">{{ $customer->email }}</span>
                            </div>
                            @endif
                            @if($customer->phone)
                            <div class="sig-detail-row">
                                <span class="sig-detail-label">{{ __('Phone') }}</span>
                                <span class="sig-detail-value">{{ $customer->phone }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                    <div class="{{ $customer ? 'col-md-6' : 'col-12' }}">
                        <div class="rounded-3 bg-light p-3 h-100" style="font-size:.84rem;">
                            <div class="fw-semibold text-muted text-uppercase mb-2" style="font-size:.68rem;letter-spacing:.04em;">
                                <i class="bi bi-tools me-1"></i>{{ __('Job Information') }}
                            </div>
                            <div class="sig-detail-row">
                                <span class="sig-detail-label">{{ __('Order #') }}</span>
                                <span class="sig-detail-value text-primary">{{ $job->job_number ?? $job->id }}</span>
                            </div>
                            <div class="sig-detail-row">
                                <span class="sig-detail-label">{{ __('Case #') }}</span>
                                <span class="sig-detail-value">{{ $job->case_number }}</span>
                            </div>
                            <div class="sig-detail-row">
                                <span class="sig-detail-label">{{ __('Status') }}</span>
                                <span class="badge bg-secondary" style="font-size:.72rem;">{{ $job->status_slug }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Signature Pad Section --}}
                <div class="border-top pt-4">
                    @if($canSign)
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center" style="width:28px;height:28px;">
                                <i class="bi bi-pen text-white" style="font-size:.75rem;"></i>
                            </div>
                            <h6 class="fw-bold mb-0">{{ __('Draw Your Signature') }}</h6>
                        </div>

                        <p class="text-muted small mb-3">{{ __('Use your mouse or finger to sign in the area below.') }}</p>

                        {{-- Canvas --}}
                        <div class="sig-canvas-wrap mb-3" id="canvasWrap">
                            <canvas id="signatureCanvas"></canvas>
                            <div class="sig-canvas-hint">
                                <i class="bi bi-pen me-1"></i>{{ __('Sign here') }}
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="d-flex justify-content-between flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="clearBtn">
                                <i class="bi bi-eraser me-1"></i>{{ __('Clear') }}
                            </button>
                            <button type="button" class="btn btn-primary btn-sm px-4" id="submitBtn">
                                <i class="bi bi-check-circle me-1"></i>{{ __('Submit Signature') }}
                            </button>
                        </div>

                        {{-- Message Area --}}
                        <div id="signatureMessage" class="mt-3" style="display:none;"></div>
                    @else
                        <div class="text-center py-4">
                            <div class="rounded-circle bg-warning bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width:56px;height:56px;">
                                <i class="bi bi-exclamation-triangle text-warning" style="font-size:1.5rem;"></i>
                            </div>
                            <p class="text-muted mb-0">{{ $statusMessage ?: __('You cannot sign this document at this time.') }}</p>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="text-center mt-4 pt-3 border-top">
                    <p class="text-muted mb-0" style="font-size:.75rem;">
                        <i class="bi bi-shield-lock me-1"></i>
                        {{ __('Your information is secure and will only be used for this job approval.') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Steps Guide (below card) --}}
        @if($canSign)
        <div class="mt-3 px-2">
            <div class="d-flex flex-wrap gap-4 justify-content-center">
                <div class="sig-step">
                    <div class="sig-step-num">1</div>
                    <span class="text-muted">{{ __('Review job details above') }}</span>
                </div>
                <div class="sig-step">
                    <div class="sig-step-num">2</div>
                    <span class="text-muted">{{ __('Draw your signature') }}</span>
                </div>
                <div class="sig-step">
                    <div class="sig-step-num">3</div>
                    <span class="text-muted">{{ __('Click submit') }}</span>
                </div>
            </div>
        </div>
        @endif
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
        const canvasWrap = document.getElementById('canvasWrap');

        document.addEventListener('DOMContentLoaded', function() {
            initSignaturePad();
            setupEventListeners();
            setupThemeSwitcher();
        });

        function initSignaturePad() {
            const canvas = document.getElementById('signatureCanvas');
            if (!canvasWrap || !canvas) return;

            canvas.width = canvasWrap.offsetWidth;
            canvas.height = 180;

            signaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgb(255, 255, 255)',
                penColor: 'rgb(0, 0, 0)',
                minWidth: 1,
                maxWidth: 2.5,
                throttle: 16,
            });

            signaturePad.addEventListener('beginStroke', function() {
                canvasWrap.classList.add('active', 'has-signature');
            });

            signaturePad.addEventListener('endStroke', function() {
                canvasWrap.classList.remove('active');
            });

            window.addEventListener('resize', function() {
                const data = signaturePad.toData();
                canvas.width = canvasWrap.offsetWidth;
                canvas.height = 180;
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
                    canvasWrap.classList.remove('has-signature');
                });
            }

            const submitBtn = document.getElementById('submitBtn');
            if (submitBtn) {
                submitBtn.addEventListener('click', uploadSignature);
            }

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
                msgDiv.className = 'mt-3 alert alert-' + (type === 'error' ? 'danger' : 'success') + ' d-flex align-items-center';
                msgDiv.innerHTML = '<i class="bi bi-' + (type === 'error' ? 'exclamation-circle' : 'check-circle') + ' me-2"></i><div>' + text + '</div>';
            }
        }

        function uploadSignature() {
            if (!signaturePad || signaturePad.isEmpty()) {
                showMessage('error', '{{ __("Please provide your signature first.") }}');
                return;
            }

            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> {{ __("Submitting...") }}';
            submitBtn.disabled = true;

            const signatureData = signaturePad.toDataURL('image/png');
            const blob = dataURLtoBlob(signatureData);
            const formData = new FormData();
            formData.append('signature_file', blob, 'signature-' + Date.now() + '.png');
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
                        setTimeout(() => { window.location.href = data.data.redirect; }, 1500);
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
            while(n--) { u8arr[n] = bstr.charCodeAt(n); }
            return new Blob([u8arr], {type: mime});
        }
    })();
    </script>
    @endif
</body>
</html>
