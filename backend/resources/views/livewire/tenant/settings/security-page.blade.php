{{-- Security Page — Alpine + Livewire --}}
@push('page-styles')
<style>
/* ═══════════════════════════════════════════════════════════
   Security Page — Matches Settings Page Design System
   Alpine.js for UI state, Livewire for data/persistence
   ═══════════════════════════════════════════════════════════ */

:root {
    --st-brand: #0ea5e9;
    --st-brand-soft: #e0f2fe;
    --st-brand-dark: #0284c7;
    --st-success: #22c55e;
    --st-success-soft: #dcfce7;
    --st-danger: #ef4444;
    --st-danger-soft: #fef2f2;
    --st-warning: #f59e0b;
    --st-warning-soft: #fef3c7;
    --st-bg: #f8fafc;
    --st-card: #ffffff;
    --st-border: #e2e8f0;
    --st-border-h: #cbd5e1;
    --st-text: #0f172a;
    --st-text-2: #475569;
    --st-text-3: #94a3b8;
    --st-radius: 12px;
    --st-radius-sm: 8px;
    --st-shadow: 0 1px 3px rgba(0,0,0,.06);
    --st-shadow-md: 0 4px 12px rgba(0,0,0,.07);
}

[x-cloak] { display: none !important; }

/* Hide wire:loading elements before Livewire JS initializes */
[wire\:loading] { display: none; }

/* ── Page Container ── */
.st-page {
    background: linear-gradient(160deg, #e8f4fd 0%, #f4f8fb 30%, #edf1f5 100%);
    min-height: 100vh;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    line-height: 1.5;
    color: var(--st-text);
}

/* ── Top Bar ── */
.st-top-bar {
    background: rgba(255,255,255,.92);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--st-border);
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 1px 0 var(--st-border), 0 2px 8px rgba(14,165,233,.04);
}
.st-top-bar-inner {
    max-width: 1440px;
    margin: 0 auto;
    padding: .65rem 2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}
.st-top-bar-inner .st-left { display: flex; align-items: center; gap: 1rem; }
.st-back-btn {
    width: 34px; height: 34px;
    border-radius: 10px;
    border: 1px solid var(--st-border);
    background: #fff;
    color: var(--st-text-2);
    display: flex; align-items: center; justify-content: center;
    text-decoration: none; flex-shrink: 0; font-size: .88rem;
    transition: all .15s;
    box-shadow: 0 1px 3px rgba(0,0,0,.05);
}
.st-back-btn:hover { background: var(--st-bg); color: var(--st-brand); border-color: var(--st-brand); }
.st-title-block { line-height: 1.2; }
.st-page-title { font-size: 1.05rem; font-weight: 700; color: var(--st-text); margin: 0; }
.st-page-subtitle { font-size: .78rem; color: var(--st-text-3); margin: 0; }

/* ── Layout ── */
.st-layout {
    max-width: 1440px;
    margin: 0 auto;
    display: flex;
    gap: 0;
    min-height: calc(100vh - 60px);
}

/* ── Content Area ── */
.st-content {
    flex: 1;
    min-width: 0;
    padding: 1.5rem 2rem 3rem;
}

/* ── Flash / Toast ── */
.st-flash {
    display: flex; align-items: center; gap: .6rem;
    padding: .65rem 1rem;
    border-radius: var(--st-radius-sm);
    font-size: .82rem; font-weight: 500;
    margin-bottom: 1.25rem;
    animation: stFlashIn .3s ease;
}
@keyframes stFlashIn { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: translateY(0); } }
.st-flash-success { background: var(--st-success-soft); color: #15803d; border: 1px solid #bbf7d0; }
.st-flash-error { background: var(--st-danger-soft); color: #dc2626; border: 1px solid #fecaca; }
.st-flash-dismiss {
    margin-left: auto; background: none; border: none; cursor: pointer;
    color: inherit; opacity: .6; font-size: 1rem; padding: 0;
}
.st-flash-dismiss:hover { opacity: 1; }

/* ── Section Card ── */
.st-section {
    background: var(--st-card);
    border: 1px solid var(--st-border);
    border-radius: var(--st-radius);
    box-shadow: var(--st-shadow);
    overflow: hidden;
    margin-bottom: 1.25rem;
}
.st-section-body { padding: 0 1.25rem 1.25rem; }

/* ── Form Group ── */
.st-fg { margin-bottom: 1rem; }
.st-fg > label {
    display: block;
    font-size: .72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--st-text-2);
    margin-bottom: .35rem;
}
.st-fg > .st-help {
    font-size: .7rem;
    color: var(--st-text-3);
    margin-top: .2rem;
}
.st-fg .form-control,
.st-fg input[type="text"],
.st-fg input[type="email"],
.st-fg input[type="url"],
.st-fg input[type="number"],
.st-fg input[type="password"],
.st-fg input[type="tel"],
.st-fg select,
.st-fg textarea {
    width: 100%;
    padding: .55rem .75rem;
    font-size: .84rem;
    color: var(--st-text);
    background: #fff;
    border: 1px solid var(--st-border);
    border-radius: var(--st-radius-sm);
    outline: none;
    transition: border-color .15s, box-shadow .15s;
    font-family: inherit;
    line-height: 1.5;
    box-sizing: border-box;
}
.st-fg input:focus,
.st-fg select:focus,
.st-fg textarea:focus {
    border-color: var(--st-brand);
    box-shadow: 0 0 0 3px rgba(14,165,233,.12);
}
.st-fg input::placeholder,
.st-fg textarea::placeholder {
    color: var(--st-text-3);
}

/* ── Toggle Switch ── */
.st-toggle {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: .65rem;
    cursor: pointer;
    user-select: none;
}
.st-toggle input { position: absolute; opacity: 0; width: 0; height: 0; }
.st-toggle-track {
    width: 40px; height: 22px;
    background: #cbd5e1;
    border-radius: 999px;
    position: relative;
    transition: background .2s;
    flex-shrink: 0;
}
.st-toggle input:checked + .st-toggle-track { background: var(--st-brand); }
.st-toggle-track::after {
    content: '';
    position: absolute;
    top: 2px; left: 2px;
    width: 18px; height: 18px;
    border-radius: 50%;
    background: #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,.15);
    transition: transform .2s;
}
.st-toggle input:checked + .st-toggle-track::after { transform: translateX(18px); }
.st-toggle-label {
    font-size: .82rem; font-weight: 500; color: var(--st-text);
}

/* ── Save Button ── */
.st-save-bar {
    display: flex; align-items: center; gap: .75rem;
    padding-top: 1rem;
    border-top: 1px solid var(--st-border);
    margin-top: 1rem;
}
.st-btn-save {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .55rem 1.25rem;
    font-size: .82rem; font-weight: 600;
    color: #fff;
    background: var(--st-brand);
    border: none;
    border-radius: var(--st-radius-sm);
    cursor: pointer;
    transition: all .15s;
    box-shadow: 0 1px 3px rgba(14,165,233,.2);
}
.st-btn-save:hover { background: var(--st-brand-dark); box-shadow: 0 2px 8px rgba(14,165,233,.3); }
.st-btn-save:disabled { opacity: .6; cursor: not-allowed; }
.st-btn-save .st-spinner {
    width: 14px; height: 14px;
    border: 2px solid rgba(255,255,255,.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: stSpin .6s linear infinite;
}
@keyframes stSpin { to { transform: rotate(360deg); } }

/* ── Help text ── */
.st-help {
    font-size: .78rem;
    color: var(--st-text-3);
}

/* Security-specific styles */
.sec-badge {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .35rem .75rem;
    font-size: .75rem;
    font-weight: 600;
    border-radius: 999px;
}
.sec-badge-success { background: var(--st-success-soft); color: #15803d; }
.sec-badge-danger { background: var(--st-danger-soft); color: #dc2626; }
.sec-badge-default { background: #f1f5f9; color: var(--st-text-2); }

.sec-tabs {
    display: flex;
    gap: .25rem;
    margin-bottom: 1.25rem;
    border-bottom: 1px solid var(--st-border);
    padding-bottom: .5rem;
}
.sec-tab {
    padding: .5rem 1rem;
    font-size: .82rem;
    font-weight: 500;
    color: var(--st-text-2);
    background: none;
    border: none;
    border-radius: var(--st-radius-sm) var(--st-radius-sm) 0 0;
    cursor: pointer;
    transition: all .15s;
}
.sec-tab:hover { color: var(--st-text); background: rgba(14,165,233,.04); }
.sec-tab.active { color: var(--st-brand); background: var(--st-brand-soft); }

.sec-qr-container {
    display: flex;
    justify-content: center;
    padding: 1rem;
    background: #fff;
    border-radius: var(--st-radius);
    border: 1px solid var(--st-border);
}

.sec-secret-box {
    background: var(--st-bg);
    padding: .75rem 1rem;
    border-radius: var(--st-radius-sm);
    font-family: 'SF Mono', Monaco, 'Courier New', monospace;
    font-size: .85rem;
    word-break: break-all;
}

.sec-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .82rem;
}
.sec-table th {
    text-align: left;
    padding: .5rem .75rem;
    background: var(--st-bg);
    font-weight: 600;
    color: var(--st-text-2);
    border-bottom: 1px solid var(--st-border);
}
.sec-table td {
    padding: .5rem .75rem;
    border-bottom: 1px solid var(--st-border);
}
.sec-table tr:hover td { background: rgba(14,165,233,.02); }

.sec-pagination {
    display: flex;
    align-items: center;
    gap: .5rem;
    margin-top: 1rem;
}
.sec-pagination button {
    padding: .35rem .75rem;
    font-size: .78rem;
    border: 1px solid var(--st-border);
    background: #fff;
    border-radius: var(--st-radius-sm);
    cursor: pointer;
}
.sec-pagination button:disabled { opacity: .5; cursor: not-allowed; }
.sec-pagination button:not(:disabled):hover { background: var(--st-bg); }
</style>
@endpush

<div class="st-page"
     x-data="{
        activeTab: '{{ $activeTab }}',
        setTab(tab) {
            this.activeTab = tab;
            @this.set('activeTab', tab);
        }
     }"
     x-cloak>
    {{-- ═══ Top Bar ═══ --}}
    <div class="st-top-bar">
        <div class="st-top-bar-inner">
            <div class="st-left">
                <a href="{{ route('tenant.dashboard', ['business' => $tenant->slug]) }}"
                   class="st-back-btn" title="Back to Dashboard">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                </a>
                <div class="st-title-block">
                    <h1 class="st-page-title">Security</h1>
                    <p class="st-page-subtitle">Manage multi-factor authentication and security policies</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ Layout ═══ --}}
    <div class="st-layout">
        <main class="st-content" style="margin-left: 0; max-width: 100%;">

            {{-- Flash message --}}
            @if ($flashMessage)
                <div class="st-flash {{ $flashType === 'success' ? 'st-flash-success' : 'st-flash-error' }}" x-data x-init="setTimeout(() => $el.remove(), 5000)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        @if ($flashType === 'success')
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                        @endif
                    </svg>
                    <span>{{ $flashMessage }}</span>
                    <button class="st-flash-dismiss" wire:click="dismissFlash">&times;</button>
                </div>
            @endif

            {{-- Tabs --}}
            <div class="sec-tabs">
                <button class="sec-tab" :class="{ 'active': activeTab === 'mfa' }" @click="setTab('mfa')">
                    MFA / OTP
                </button>
                <button class="sec-tab" :class="{ 'active': activeTab === 'policies' }" @click="setTab('policies')">
                    Policies
                </button>
                <button class="sec-tab" :class="{ 'active': activeTab === 'compliance' }" @click="setTab('compliance')">
                    Compliance
                </button>
                <button class="sec-tab" :class="{ 'active': activeTab === 'audit' }" @click="setTab('audit')">
                    Audit Log
                </button>
            </div>

            {{-- ═══ MFA / OTP Tab ═══ --}}
            <div x-show="activeTab === 'mfa'" x-cloak>
                <div class="st-section">
                    <div class="st-section-body" style="padding-top: 1.25rem;">
                        <div style="margin-bottom: .75rem;">
                            <span class="sec-badge {{ $this->otpEnabled ? 'sec-badge-success' : 'sec-badge-danger' }}">
                                @if ($this->otpEnabled)
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Enabled
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                                    </svg>
                                    Disabled
                                @endif
                            </span>
                        </div>

                        @if (! $this->otpEnabled && ! $otpSetup)
                            <p class="st-help" style="margin-bottom: 1rem;">Enable multi-factor authentication to secure your account.</p>
                            <button type="button" class="st-btn-save" wire:click="startOtpSetup" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="startOtpSetup">Enable OTP</span>
                                <span wire:loading wire:target="startOtpSetup">Starting...</span>
                            </button>
                        @elseif ($otpSetup)
                            <div style="margin-bottom: 1rem;">
                                <p class="st-help">Scan the QR code with your authenticator app, or enter the secret manually.</p>
                            </div>

                            <div class="sec-qr-container" style="margin-bottom: 1rem;" data-otp-uri="{{ $otpSetup['otpauth_uri'] }}" x-init="QRCode.toCanvas($el.querySelector('canvas'), '{{ $otpSetup['otpauth_uri'] }}', { width: 200 })">
                                <canvas id="otp-qrcode"></canvas>
                            </div>

                            <div style="margin-bottom: 1rem;">
                                <label style="font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--st-text-2); display: block; margin-bottom: .35rem;">Secret</label>
                                <div class="sec-secret-box">{{ $otpSetup['secret'] }}</div>
                            </div>

                            <form wire:submit.prevent="confirmOtp">
                                <div class="st-fg" style="max-width: 280px;">
                                    <label for="otp_code">Enter 6-digit code</label>
                                    <input type="text"
                                           id="otp_code"
                                           wire:model.defer="otpCode"
                                           inputmode="numeric"
                                           pattern="[0-9]{6}"
                                           maxlength="6"
                                           placeholder="000000"
                                           required>
                                </div>
                                <div style="margin-top: 1rem;">
                                    <button type="submit" class="st-btn-save" wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="confirmOtp">Confirm OTP</span>
                                        <span wire:loading wire:target="confirmOtp">Confirming...</span>
                                    </button>
                                </div>
                            </form>
                        @else
                            <p class="st-help" style="margin-bottom: 1rem;">To disable OTP, enter your password and a code from your authenticator.</p>
                            <form wire:submit.prevent="disableOtp">
                                <div class="st-fg" style="max-width: 320px;">
                                    <label for="disable_password">Password</label>
                                    <input type="password"
                                           id="disable_password"
                                           wire:model.defer="disablePassword"
                                           autocomplete="current-password"
                                           required>
                                </div>
                                <div class="st-fg" style="max-width: 280px;">
                                    <label for="disable_code">OTP code</label>
                                    <input type="text"
                                           id="disable_code"
                                           wire:model.defer="disableCode"
                                           inputmode="numeric"
                                           pattern="[0-9]{6}"
                                           maxlength="6"
                                           placeholder="000000"
                                           required>
                                </div>
                                <div style="margin-top: 1rem;">
                                    <button type="submit" class="st-btn-save" style="background: var(--st-danger);" wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="disableOtp">Disable OTP</span>
                                        <span wire:loading wire:target="disableOtp">Disabling...</span>
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ═══ Policies Tab ═══ --}}
            <div x-show="activeTab === 'policies'" x-cloak>
                <form wire:submit.prevent="savePolicies">
                    <div class="st-section">
                        <div class="st-section-body" style="padding-top: 1.25rem;">
                            <div class="st-fg">
                                <label>MFA required roles</label>
                                <div style="background: #fff; border: 1px solid var(--st-border); border-radius: var(--st-radius-sm); padding: .75rem;">
                                    @if (count($roles) === 0)
                                        <p class="st-help">No roles found.</p>
                                    @else
                                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: .5rem;">
                                            @foreach ($roles as $role)
                                                <label class="st-toggle" style="cursor: pointer;">
                                                    <input type="checkbox"
                                                           value="{{ $role['id'] }}"
                                                           wire:model.defer="mfaRoleIds"
                                                           style="position: absolute; opacity: 0;">
                                                    <span class="st-toggle-track"></span>
                                                    <span class="st-toggle-label">{{ $role['name'] }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <p class="st-help">Users in these roles will be required to enable OTP.</p>
                            </div>

                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
                                <div class="st-fg">
                                    <label for="mfa_grace">MFA grace period (days)</label>
                                    <input type="number"
                                           id="mfa_grace"
                                           wire:model.defer="mfaGraceDays"
                                           min="0"
                                           max="365">
                                </div>

                                <div class="st-fg">
                                    <label for="idle_timeout">Session idle timeout (minutes)</label>
                                    <input type="number"
                                           id="idle_timeout"
                                           wire:model.defer="idleMinutes"
                                           min="5"
                                           max="1440">
                                </div>

                                <div class="st-fg">
                                    <label for="max_life">Session max lifetime (days)</label>
                                    <input type="number"
                                           id="max_life"
                                           wire:model.defer="maxLifetimeDays"
                                           min="1"
                                           max="365">
                                </div>

                                <div class="st-fg">
                                    <label for="lockout_attempts">Lockout max attempts</label>
                                    <input type="number"
                                           id="lockout_attempts"
                                           wire:model.defer="lockoutAttempts"
                                           min="1"
                                           max="100">
                                </div>

                                <div class="st-fg">
                                    <label for="lockout_minutes">Lockout duration (minutes)</label>
                                    <input type="number"
                                           id="lockout_minutes"
                                           wire:model.defer="lockoutMinutes"
                                           min="1"
                                           max="1440">
                                </div>
                            </div>

                            <div class="st-save-bar" style="margin-top: 1.5rem;">
                                <button type="submit" class="st-btn-save" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="savePolicies">Save policies</span>
                                    <span wire:loading wire:target="savePolicies">Saving...</span>
                                </button>
                                <button type="button"
                                        class="st-btn-save"
                                        style="background: var(--st-danger);"
                                        wire:click="forceLogout"
                                        wire:confirm="Are you sure you want to log out all users?"
                                        wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="forceLogout">Force logout all users</span>
                                    <span wire:loading wire:target="forceLogout">Processing...</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            {{-- ═══ Compliance Tab ═══ --}}
            <div x-show="activeTab === 'compliance'" x-cloak>
                <div class="st-section">
                    <div class="st-section-body" style="padding-top: 1.25rem;">
                        <div style="display: flex; flex-wrap: wrap; gap: .75rem; margin-bottom: 1.25rem;">
                            <span class="sec-badge sec-badge-default">
                                In scope: {{ $compliance['total_in_scope'] ?? 0 }}
                            </span>
                            <span class="sec-badge sec-badge-success">
                                Compliant: {{ $compliance['compliant'] ?? 0 }}
                            </span>
                            <span class="sec-badge sec-badge-danger">
                                Non-compliant: {{ $compliance['non_compliant'] ?? 0 }}
                            </span>
                        </div>

                        <h4 style="font-size: .88rem; font-weight: 600; margin-bottom: .75rem;">Non-compliant users</h4>

                        @if (empty($compliance['non_compliant_users']))
                            <p class="st-help">No non-compliant users.</p>
                        @else
                            <table class="sec-table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Role</th>
                                        <th>OTP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($compliance['non_compliant_users'] as $user)
                                        <tr>
                                            <td>
                                                <div style="font-weight: 600;">{{ $user['name'] }}</div>
                                                <div style="font-size: .75rem; color: var(--st-text-3);">{{ $user['email'] }}</div>
                                            </td>
                                            <td>{{ $user['role_name'] }}</td>
                                            <td>
                                                <span class="sec-badge sec-badge-danger">Missing</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ═══ Audit Log Tab ═══ --}}
            <div x-show="activeTab === 'audit'" x-cloak>
                <div class="st-section">
                    <div class="st-section-body" style="padding-top: 1.25rem;">
                        <div class="st-fg" style="max-width: 300px; margin-bottom: 1rem;">
                            <label for="audit_type">Filter by type</label>
                            <input type="text"
                                   id="audit_type"
                                   wire:model.debounce.300ms="auditType"
                                   placeholder="e.g. login_success">
                        </div>

                        @if (empty($auditEvents))
                            <p class="st-help">No events found.</p>
                        @else
                            <table class="sec-table">
                                <thead>
                                    <tr>
                                        <th>When</th>
                                        <th>Type</th>
                                        <th>Source</th>
                                        <th>User</th>
                                        <th>IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($auditEvents as $event)
                                        <tr>
                                            <td style="font-size: .75rem; color: var(--st-text-3);">
                                                {{ $event['created_at'] ? \Carbon\Carbon::parse($event['created_at'])->format('M j, Y H:i:s') : '' }}
                                            </td>
                                            <td style="font-weight: 600;">{{ $event['type'] }}</td>
                                            <td>{{ $event['source'] }}</td>
                                            <td>{{ $event['email'] ?? ($event['user_id'] ? '#'.$event['user_id'] : '') }}</td>
                                            <td>{{ $event['ip'] ?? '' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <div class="sec-pagination">
                                <button wire:click="setAuditPage({{ $auditPage - 1 }})" {{ $auditPage <= 1 ? 'disabled' : '' }}>
                                    Previous
                                </button>
                                <span>Page {{ $auditPage }} of {{ $auditTotalPages }} ({{ $auditTotalRows }} total)</span>
                                <button wire:click="setAuditPage({{ $auditPage + 1 }})" {{ $auditPage >= $auditTotalPages ? 'disabled' : '' }}>
                                    Next
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

@push('page-scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('otpSetupUpdated', (event) => {
            if (event && event.otpauth_uri) {
                setTimeout(() => {
                    const canvas = document.getElementById('otp-qrcode');
                    if (canvas) {
                        QRCode.toCanvas(canvas, event.otpauth_uri, { width: 200 });
                    }
                }, 100);
            }
        });
    });

    // Watch for OTP setup changes
    document.addEventListener('livewire:update', () => {
        const canvas = document.getElementById('otp-qrcode');
        const uriElement = document.querySelector('[data-otp-uri]');
        if (canvas && uriElement) {
            const uri = uriElement.getAttribute('data-otp-uri');
            if (uri) {
                QRCode.toCanvas(canvas, uri, { width: 200 });
            }
        }
    });
</script>
@endpush
