@props([
    'backHref' => null,
    'iconClass' => 'bi bi-briefcase-fill',
    'title' => '',
    'subtitle' => '',
])

@once
    @push('page-styles')
        <style>
            .hero-header {
                --ui-hero-gradient-from: var(--ui-sidebar-bg, var(--bs-dark, #212529));
                --ui-hero-gradient-to: var(--ui-hero-to, #111827);
                background: linear-gradient(135deg, var(--ui-hero-gradient-from), var(--ui-hero-gradient-to));
                border-radius: 16px;
                padding: 1.5rem;
                color: white;
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 2rem;
                position: relative;
                overflow: hidden;
            }
            .hero-header::before {
                content: '';
                position: absolute;
                top: -50%;
                right: -50%;
                width: 200%;
                height: 200%;
                background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
                transform: rotate(30deg);
            }
            .hero-icon {
                width: 56px;
                height: 56px;
                background: rgba(255,255,255,0.15);
                border-radius: 14px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.8rem;
                z-index: 2;
            }
            .hero-title h2 {
                font-weight: 700;
                font-size: 1.5rem;
                margin: 0;
                color: #fff;
                z-index: 2;
                position: relative;
            }
            .hero-title h2 span {
                font-weight: 400;
                opacity: 0.8;
            }
            .hero-title .subtitle {
                font-size: 0.9rem;
                opacity: 0.85;
                margin-top: 0.25rem;
                color: rgba(255, 255, 255, 0.85);
                z-index: 2;
                position: relative;
            }
            .hero-header .hero-title a,
            .hero-header .hero-title i {
                color: inherit;
            }
            .btn-export {
                background: rgba(255,255,255,0.1);
                border: 1px solid rgba(255,255,255,0.2);
                color: white;
                padding: 0.5rem 1rem;
                border-radius: 8px;
                font-size: 0.85rem;
            }
            .btn-export:hover {
                background: rgba(255,255,255,0.2);
                color: white;
            }
            .btn-save-review {
                background: var(--rb-primary, #2563eb);
                border: none;
                color: white;
                padding: 0.5rem 1rem;
                border-radius: 8px;
                font-size: 0.85rem;
                font-weight: 600;
            }
            .btn-save-review:hover {
                background: var(--rb-primary-dark, #1e40af);
                color: white;
            }
        </style>
    @endpush
@endonce

<div class="hero-header shadow-sm">
    <div class="d-flex align-items-center gap-4">
        @if ($backHref)
            <a href="{{ $backHref }}" class="btn btn-export p-2">
                <i class="bi bi-chevron-left"></i>
            </a>
        @endif

        <div class="hero-icon">
            <i class="{{ $iconClass }} text-white"></i>
        </div>

        <div class="hero-title">
            <h2 class="mb-0">{!! $title !!}</h2>
            @if (trim((string) $subtitle) !== '')
                <div class="subtitle">{!! $subtitle !!}</div>
            @endif
        </div>
    </div>

    <div class="d-flex align-items-center gap-3">
        {{ $actions ?? '' }}
    </div>
</div>
