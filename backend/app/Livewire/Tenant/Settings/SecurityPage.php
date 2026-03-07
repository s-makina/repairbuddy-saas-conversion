<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\AuthEvent;
use App\Models\PlatformAuditLog;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantSecuritySetting;
use App\Models\User;
use App\Support\BranchContext;
use App\Support\TenantContext;
use App\Support\Totp;
use App\Support\PlatformAudit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;
use Livewire\Component;

class SecurityPage extends Component
{
    public $tenant;

    // Tab state
    public string $activeTab = 'mfa';

    // OTP setup state
    public ?array $otpSetup = null;
    public string $otpCode = '';
    public string $disablePassword = '';
    public string $disableCode = '';

    // Policy settings
    public array $mfaRoleIds = [];
    public int $mfaGraceDays = 7;
    public int $idleMinutes = 60;
    public int $maxLifetimeDays = 30;
    public int $lockoutAttempts = 10;
    public int $lockoutMinutes = 15;

    // Data
    public array $roles = [];
    public array $compliance = [];
    public array $auditEvents = [];
    public int $auditPage = 1;
    public int $auditTotalPages = 1;
    public int $auditTotalRows = 0;
    public string $auditType = '';
    public int $auditPageSize = 25;

    // Flash messages
    public string $flashMessage = '';
    public string $flashType = 'success';

    protected function rules(): array
    {
        return [
            'mfaGraceDays' => ['integer', 'min:0', 'max:365'],
            'idleMinutes' => ['integer', 'min:5', 'max:1440'],
            'maxLifetimeDays' => ['integer', 'min:1', 'max:365'],
            'lockoutAttempts' => ['integer', 'min:1', 'max:100'],
            'lockoutMinutes' => ['integer', 'min:1', 'max:1440'],
            'otpCode' => ['required', 'string', 'min:6', 'max:6'],
            'disablePassword' => ['required', 'string'],
            'disableCode' => ['required', 'string', 'min:6', 'max:6'],
        ];
    }

    public function mount(): void
    {
        $this->tenant = TenantContext::tenant();

        if (! $this->tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $this->loadSettings();
        $this->loadRoles();
        $this->loadCompliance();
        $this->loadAuditEvents();
    }

    public function hydrate(): void
    {
        if ($this->tenant instanceof Tenant && is_int($this->tenant->id)) {
            TenantContext::set($this->tenant);
            $branch = $this->tenant->defaultBranch;
            if ($branch) {
                BranchContext::set($branch);
            }
        }
    }

    protected function currentUser(): ?User
    {
        return Auth::user();
    }

    public function getOtpEnabledProperty(): bool
    {
        $user = $this->currentUser();
        return $user && $user->otp_enabled && $user->otp_confirmed_at;
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;

        if ($tab === 'compliance') {
            $this->loadCompliance();
        } elseif ($tab === 'audit') {
            $this->loadAuditEvents();
        }
    }

    protected function loadSettings(): void
    {
        $settings = TenantSecuritySetting::query()
            ->where('tenant_id', $this->tenant->id)
            ->first();

        if ($settings) {
            $this->mfaRoleIds = is_array($settings->mfa_required_roles) ? $settings->mfa_required_roles : [];
            $this->mfaGraceDays = (int) ($settings->mfa_grace_period_days ?? 7);
            $this->idleMinutes = (int) ($settings->session_idle_timeout_minutes ?? 60);
            $this->maxLifetimeDays = (int) ($settings->session_max_lifetime_days ?? 30);
            $this->lockoutAttempts = (int) ($settings->lockout_max_attempts ?? 10);
            $this->lockoutMinutes = (int) ($settings->lockout_duration_minutes ?? 15);
        }
    }

    protected function loadRoles(): void
    {
        $this->roles = Role::query()
            ->where('tenant_id', $this->tenant->id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name])
            ->all();
    }

    protected function loadCompliance(): void
    {
        $settings = TenantSecuritySetting::query()
            ->where('tenant_id', $this->tenant->id)
            ->first();

        $requiredRoles = is_array($settings?->mfa_required_roles) ? $settings->mfa_required_roles : [];
        $requiredRoles = array_values(array_unique(array_filter(array_map('intval', $requiredRoles), fn ($v) => $v > 0)));

        if (count($requiredRoles) === 0) {
            $this->compliance = [
                'total_in_scope' => 0,
                'compliant' => 0,
                'non_compliant' => 0,
                'non_compliant_users' => [],
            ];
            return;
        }

        $tenantId = (int) $this->tenant->id;

        $base = User::query()
            ->where('tenant_id', $tenantId)
            ->where('is_admin', false)
            ->whereIn('role_id', $requiredRoles);

        $total = (clone $base)->count();

        $compliantCount = (clone $base)
            ->where('otp_enabled', true)
            ->whereNotNull('otp_confirmed_at')
            ->count();

        $nonCompliantUsers = (clone $base)
            ->where(function ($q) {
                $q->where('otp_enabled', false)
                    ->orWhereNull('otp_confirmed_at');
            })
            ->with(['roleModel'])
            ->orderBy('name')
            ->get();

        $this->compliance = [
            'total_in_scope' => $total,
            'compliant' => $compliantCount,
            'non_compliant' => max(0, $total - $compliantCount),
            'non_compliant_users' => $nonCompliantUsers->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role_name' => $u->roleModel?->name ?? '(none)',
                'otp_enabled' => $u->otp_enabled && $u->otp_confirmed_at,
            ])->all(),
        ];
    }

    protected function loadAuditEvents(): void
    {
        $tenantId = (int) $this->tenant->id;
        $type = trim($this->auditType);

        $authQ = AuthEvent::query()->where('tenant_id', $tenantId);
        $platQ = PlatformAuditLog::query()->where('tenant_id', $tenantId);

        if ($type !== '') {
            $authQ->where('event_type', $type);
            $platQ->where('action', $type);
        }

        $authCount = (clone $authQ)->count();
        $platCount = (clone $platQ)->count();
        $total = $authCount + $platCount;

        $limit = $this->auditPageSize;
        $offset = ($this->auditPage - 1) * $limit;

        $auth = (clone $authQ)->orderByDesc('created_at')->limit($limit + $offset)->get();
        $plat = (clone $platQ)->orderByDesc('created_at')->limit($limit + $offset)->get();

        $items = [];

        foreach ($auth as $e) {
            $items[] = [
                'source' => 'auth',
                'id' => $e->id,
                'type' => $e->event_type,
                'user_id' => $e->user_id,
                'email' => $e->email,
                'created_at' => $e->created_at?->toIso8601String(),
                'ip' => $e->ip,
            ];
        }

        foreach ($plat as $e) {
            $items[] = [
                'source' => 'platform',
                'id' => $e->id,
                'type' => $e->action,
                'user_id' => $e->actor_user_id,
                'email' => null,
                'created_at' => $e->created_at?->toIso8601String(),
                'ip' => $e->ip,
            ];
        }

        usort($items, fn ($a, $b) => ($b['created_at'] ?? '') <=> ($a['created_at'] ?? ''));

        $paged = array_slice($items, $offset, $limit);

        $this->auditEvents = $paged;
        $this->auditTotalRows = $total;
        $this->auditTotalPages = (int) ceil(max(1, $total) / $limit);
    }

    public function setAuditPage(int $page): void
    {
        $this->auditPage = max(1, $page);
        $this->loadAuditEvents();
    }

    public function updatedAuditType(): void
    {
        $this->auditPage = 1;
        $this->loadAuditEvents();
    }

    public function startOtpSetup(): void
    {
        $user = $this->currentUser();
        if (! $user) {
            return;
        }

        $secret = Totp::generateSecret();
        $issuer = (string) config('app.name');
        $uri = Totp::provisioningUri($issuer, (string) $user->email, $secret);

        $user->forceFill([
            'otp_enabled' => true,
            'otp_secret' => $secret,
            'otp_confirmed_at' => null,
        ])->save();

        AuthEvent::query()->create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'email' => $user->email,
            'event_type' => 'otp_setup_started',
            'ip' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
        ]);

        $this->otpSetup = [
            'secret' => $secret,
            'otpauth_uri' => $uri,
        ];
        $this->otpCode = '';
    }

    public function confirmOtp(): void
    {
        $this->validate(['otpCode' => $this->rules()['otpCode']]);

        $user = $this->currentUser();
        if (! $user) {
            return;
        }

        if (! is_string($user->otp_secret) || $user->otp_secret === '') {
            $this->flashMessage = 'OTP setup not started.';
            $this->flashType = 'error';
            return;
        }

        if (! Totp::verify($user->otp_secret, $this->otpCode)) {
            AuthEvent::query()->create([
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'email' => $user->email,
                'event_type' => 'otp_confirm_failed',
                'ip' => request()->ip(),
                'user_agent' => (string) request()->userAgent(),
            ]);

            $this->flashMessage = 'Invalid OTP code.';
            $this->flashType = 'error';
            return;
        }

        $user->forceFill([
            'otp_enabled' => true,
            'otp_confirmed_at' => now(),
        ])->save();

        AuthEvent::query()->create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'email' => $user->email,
            'event_type' => 'otp_confirmed',
            'ip' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
        ]);

        $this->otpSetup = null;
        $this->otpCode = '';
        $this->flashMessage = 'OTP enabled successfully.';
        $this->flashType = 'success';
    }

    public function disableOtp(): void
    {
        $this->validate([
            'disablePassword' => $this->rules()['disablePassword'],
            'disableCode' => $this->rules()['disableCode'],
        ]);

        $user = $this->currentUser();
        if (! $user) {
            return;
        }

        if (! Hash::check($this->disablePassword, (string) $user->password)) {
            $this->flashMessage = 'Invalid password.';
            $this->flashType = 'error';
            return;
        }

        if (! is_string($user->otp_secret) || $user->otp_secret === '' || ! Totp::verify($user->otp_secret, $this->disableCode)) {
            $this->flashMessage = 'Invalid OTP code.';
            $this->flashType = 'error';
            return;
        }

        $user->forceFill([
            'otp_enabled' => false,
            'otp_secret' => null,
            'otp_confirmed_at' => null,
        ])->save();

        AuthEvent::query()->create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'email' => $user->email,
            'event_type' => 'otp_disabled',
            'ip' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
        ]);

        $this->disablePassword = '';
        $this->disableCode = '';
        $this->flashMessage = 'OTP disabled successfully.';
        $this->flashType = 'success';
    }

    public function savePolicies(): void
    {
        $this->validate([
            'mfaGraceDays' => $this->rules()['mfaGraceDays'],
            'idleMinutes' => $this->rules()['idleMinutes'],
            'maxLifetimeDays' => $this->rules()['maxLifetimeDays'],
            'lockoutAttempts' => $this->rules()['lockoutAttempts'],
            'lockoutMinutes' => $this->rules()['lockoutMinutes'],
        ]);

        $tenantId = (int) $this->tenant->id;

        $roleIds = array_values(array_unique(array_filter(array_map('intval', $this->mfaRoleIds), fn ($v) => $v > 0)));

        if (count($roleIds) > 0) {
            $validCount = Role::query()->where('tenant_id', $tenantId)->whereIn('id', $roleIds)->count();
            if ($validCount !== count($roleIds)) {
                $this->flashMessage = 'One or more selected roles are invalid.';
                $this->flashType = 'error';
                return;
            }
        }

        $settings = TenantSecuritySetting::query()->firstOrNew(['tenant_id' => $tenantId]);

        $previousRoles = is_array($settings->mfa_required_roles) ? $settings->mfa_required_roles : [];
        $previousRoles = array_values(array_unique(array_filter(array_map('intval', $previousRoles), fn ($v) => $v > 0)));

        $isPreviousRequiring = count($previousRoles) > 0;
        $isNewRequiring = count($roleIds) > 0;

        $settings->forceFill([
            'mfa_required_roles' => $roleIds,
            'mfa_grace_period_days' => $this->mfaGraceDays,
            'session_idle_timeout_minutes' => $this->idleMinutes,
            'session_max_lifetime_days' => $this->maxLifetimeDays,
            'lockout_max_attempts' => $this->lockoutAttempts,
            'lockout_duration_minutes' => $this->lockoutMinutes,
        ]);

        if (! $isNewRequiring) {
            $settings->mfa_enforce_after = null;
        } else {
            $candidate = now()->addDays($this->mfaGraceDays);

            if (! $isPreviousRequiring || ! $settings->mfa_enforce_after) {
                $settings->mfa_enforce_after = $candidate;
            } else {
                if ($settings->mfa_enforce_after->lte(now())) {
                    // keep existing
                } else {
                    $settings->mfa_enforce_after = $settings->mfa_enforce_after->gte($candidate) ? $settings->mfa_enforce_after : $candidate;
                }
            }
        }

        $settings->save();

        PlatformAudit::log(request(), 'tenant.security_settings.updated', $this->tenant, null, [
            'mfa_required_roles' => $roleIds,
            'mfa_grace_period_days' => $this->mfaGraceDays,
            'session_idle_timeout_minutes' => $this->idleMinutes,
            'session_max_lifetime_days' => $this->maxLifetimeDays,
            'lockout_max_attempts' => $this->lockoutAttempts,
            'lockout_duration_minutes' => $this->lockoutMinutes,
        ]);

        $this->flashMessage = 'Security policies saved successfully.';
        $this->flashType = 'success';

        $this->loadCompliance();
    }

    public function forceLogout(): void
    {
        $tenantId = (int) $this->tenant->id;

        $tokensDeleted = PersonalAccessToken::query()
            ->whereHasMorph('tokenable', [User::class], function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)->where('is_admin', false);
            })
            ->delete();

        PlatformAudit::log(request(), 'tenant.force_logout', $this->tenant, null, [
            'tokens_deleted' => $tokensDeleted,
        ]);

        $this->flashMessage = "Logged out {$tokensDeleted} active sessions.";
        $this->flashType = 'success';
    }

    public function dismissFlash(): void
    {
        $this->flashMessage = '';
    }

    public function render()
    {
        return view('livewire.tenant.settings.security-page');
    }
}
