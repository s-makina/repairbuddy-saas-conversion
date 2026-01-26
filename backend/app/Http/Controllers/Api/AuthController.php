<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\AuthEvent;
use App\Models\TenantSecuritySetting;
use App\Models\User;
use App\Support\Permissions;
use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Laravel\Sanctum\NewAccessToken;

class AuthController extends Controller
{
    protected function resolveInitialActiveBranchIdForUser(User $user): ?int
    {
        if ($user->is_admin) {
            return null;
        }

        $tenantId = (int) ($user->tenant_id ?? 0);
        if ($tenantId <= 0) {
            return null;
        }

        if (Permissions::userHas($user, 'branches.manage')) {
            $defaultBranchId = DB::table('tenants')->where('id', $tenantId)->value('default_branch_id');
            $defaultBranchId = is_numeric($defaultBranchId) ? (int) $defaultBranchId : null;

            return $defaultBranchId && $defaultBranchId > 0 ? $defaultBranchId : null;
        }

        $branchIds = DB::table('branch_user')
            ->join('branches', 'branches.id', '=', 'branch_user.branch_id')
            ->where('branch_user.tenant_id', $tenantId)
            ->where('branch_user.user_id', (int) $user->id)
            ->where('branches.is_active', true)
            ->orderBy('branches.id')
            ->pluck('branches.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $branchIds = array_values(array_unique(array_filter($branchIds, fn ($id) => $id > 0)));

        if (count($branchIds) === 1) {
            return $branchIds[0];
        }

        return null;
    }

    protected function logAuthEvent(Request $request, string $eventType, ?User $user = null, ?string $email = null, ?Tenant $tenant = null, array $metadata = []): void
    {
        AuthEvent::query()->create([
            'tenant_id' => $tenant?->id ?? $user?->tenant_id,
            'user_id' => $user?->id,
            'email' => $email,
            'event_type' => $eventType,
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'metadata' => $metadata,
        ]);
    }

    protected function lockoutKey(string $email, string $ip): string
    {
        return 'auth_lockout:'.strtolower(trim($email)).':'.$ip;
    }

    protected function lockoutAttemptsKey(string $email, string $ip): string
    {
        return 'auth_attempts:'.strtolower(trim($email)).':'.$ip;
    }

    protected function lockoutPolicyForUser(?User $user): array
    {
        if (! $user) {
            return [10, 15];
        }

        $settings = TenantSecuritySetting::query()->where('tenant_id', (int) $user->tenant_id)->first();

        $maxAttempts = (int) ($settings?->lockout_max_attempts ?? 10);
        $durationMinutes = (int) ($settings?->lockout_duration_minutes ?? 15);

        if ($maxAttempts < 1) {
            $maxAttempts = 1;
        }
        if ($durationMinutes < 1) {
            $durationMinutes = 1;
        }

        return [$maxAttempts, $durationMinutes];
    }

    protected function isLockedOut(Request $request, string $email, ?User $user = null): bool
    {
        $key = $this->lockoutKey($email, (string) $request->ip());

        return Cache::has($key);
    }

    protected function registerFailedAttempt(Request $request, string $email, ?User $user = null): void
    {
        [$maxAttempts, $durationMinutes] = $this->lockoutPolicyForUser($user);

        $attemptsKey = $this->lockoutAttemptsKey($email, (string) $request->ip());
        $lockoutKey = $this->lockoutKey($email, (string) $request->ip());

        $attempts = (int) Cache::get($attemptsKey, 0);
        $attempts++;

        Cache::put($attemptsKey, $attempts, now()->addMinutes($durationMinutes));

        if ($attempts >= $maxAttempts) {
            Cache::put($lockoutKey, true, now()->addMinutes($durationMinutes));

            $this->logAuthEvent($request, 'lockout_triggered', $user, $email, $user?->tenant, [
                'max_attempts' => $maxAttempts,
                'duration_minutes' => $durationMinutes,
            ]);
        }
    }

    protected function clearFailedAttempts(Request $request, string $email): void
    {
        Cache::forget($this->lockoutAttemptsKey($email, (string) $request->ip()));
        Cache::forget($this->lockoutKey($email, (string) $request->ip()));
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'string',
                PasswordRule::min(12)->letters()->mixedCase()->numbers()->symbols(),
            ],
            'tenant_name' => ['nullable', 'string', 'max:255'],
            'tenant_slug' => ['nullable', 'string', 'max:64'],
        ]);

        $tenantSlug = $validated['tenant_slug'] ?? null;

        $ownerPermissions = [
            'app.access',
            'dashboard.view',
            'appointments.view',
            'jobs.view',
            'estimates.view',
            'services.view',
            'devices.view',
            'device_brands.view',
            'device_types.view',
            'parts.view',
            'payments.view',
            'reports.view',
            'expenses.view',
            'expense_categories.view',
            'clients.view',
            'customer_devices.view',
            'technicians.view',
            'managers.view',
            'job_reviews.view',
            'time_logs.view',
            'hourly_rates.view',
            'reminder_logs.view',
            'print_screen.view',
            'security.manage',
            'profile.manage',
            'settings.manage',
            'users.manage',
            'roles.manage',
            'branches.manage',
        ];

        $memberPermissions = [
            'app.access',
            'dashboard.view',
            'jobs.view',
            'appointments.view',
            'estimates.view',
            'clients.view',
            'customer_devices.view',
            'profile.manage',
            'security.manage',
        ];

        $result = DB::transaction(function () use ($validated, $tenantSlug, $ownerPermissions, $memberPermissions) {
            if ($tenantSlug) {
                if (Tenant::query()->where('slug', $tenantSlug)->exists()) {
                    return response()->json([
                        'message' => 'The tenant slug is already taken.',
                        'errors' => [
                            'tenant_slug' => ['The tenant slug is already taken.'],
                        ],
                    ], 422);
                }

                $tenant = Tenant::query()->create([
                    'name' => $validated['tenant_name'] ?? $tenantSlug,
                    'slug' => $tenantSlug,
                    'status' => 'active',
                    'contact_email' => $validated['email'],
                ]);
            } else {
                $name = $validated['tenant_name'] ?? 'Tenant';
                $baseSlug = Str::slug($name) ?: 'tenant';
                $slug = $baseSlug;
                $i = 1;

                while (Tenant::query()->where('slug', $slug)->exists()) {
                    $slug = $baseSlug.'-'.$i;
                    $i++;
                }

                $tenant = Tenant::query()->create([
                    'name' => $name,
                    'slug' => $slug,
                    'status' => 'active',
                    'contact_email' => $validated['email'],
                ]);
            }

            foreach (Permissions::all() as $permName) {
                Permission::query()->firstOrCreate([
                    'name' => $permName,
                ]);
            }

            $ownerRole = Role::query()->firstOrCreate([
                'tenant_id' => $tenant->id,
                'name' => 'Owner',
            ]);

            $memberRole = Role::query()->firstOrCreate([
                'tenant_id' => $tenant->id,
                'name' => 'Member',
            ]);

            $permissionIdsByName = Permission::query()->pluck('id', 'name')->all();

            $ownerRole->permissions()->sync(array_values(array_filter(array_map(function (string $name) use ($permissionIdsByName) {
                return $permissionIdsByName[$name] ?? null;
            }, $ownerPermissions))));

            $memberRole->permissions()->sync(array_values(array_filter(array_map(function (string $name) use ($permissionIdsByName) {
                return $permissionIdsByName[$name] ?? null;
            }, $memberPermissions))));

            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'tenant_id' => $tenant->id,
                'role' => 'owner',
                'role_id' => $ownerRole->id,
                'is_admin' => false,
            ]);

            return [$tenant, $user];
        });

        if ($result instanceof \Illuminate\Http\JsonResponse) {
            return $result;
        }

        [$tenant, $user] = $result;

        $user->sendEmailVerificationNotification();

        $this->logAuthEvent($request, 'register_success', $user, $user->email, $tenant);

        return response()->json([
            'verification_required' => true,
            'user' => $user,
            'tenant' => $tenant,
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = (string) $validated['email'];
        $user = User::query()->where('email', $email)->first();

        if ($this->isLockedOut($request, $email, $user)) {
            $this->logAuthEvent($request, 'login_locked_out', $user, $email, $user?->tenant);

            return response()->json([
                'message' => 'Too many login attempts. Please try again later.',
            ], 423);
        }

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            $this->logAuthEvent($request, 'login_failed', null, $validated['email'], null);

            $this->registerFailedAttempt($request, $email, $user);

            return response()->json([
                'message' => 'Invalid credentials.',
            ], 422);
        }

        $this->clearFailedAttempts($request, $email);

        if (! $user->hasVerifiedEmail()) {
            $this->logAuthEvent($request, 'login_unverified', $user, $user->email, $user->tenant);

            return response()->json([
                'message' => 'Email address not verified.',
                'verification_required' => true,
            ], 403);
        }

        if ($user->otp_enabled && $user->otp_secret && $user->otp_confirmed_at) {
            $otpLoginToken = Str::random(64);

            Cache::put('otp_login:'.$otpLoginToken, (int) $user->id, now()->addMinutes(5));

            $this->logAuthEvent($request, 'login_otp_required', $user, $user->email, $user->tenant);

            return response()->json([
                'otp_required' => true,
                'otp_login_token' => $otpLoginToken,
            ]);
        }

        if (! $user->is_admin) {
            $tenantId = (int) ($user->tenant_id ?? 0);
            if ($tenantId <= 0) {
                return response()->json([
                    'message' => 'Account is not linked to a business.',
                ], 403);
            }

            $hasAnyAssignment = DB::table('branch_user')
                ->where('tenant_id', $tenantId)
                ->where('user_id', (int) $user->id)
                ->exists();

            $permissions = Permissions::forUser($user);
            $isBranchAdmin = in_array('branches.manage', $permissions, true);

            if (! $isBranchAdmin && ! $hasAnyAssignment) {
                return response()->json([
                    'message' => 'No branch access assigned. Please contact your administrator.',
                ], 403);
            }
        }

        /** @var NewAccessToken $newToken */
        $newToken = $user->createToken('api');
        $activeBranchId = $this->resolveInitialActiveBranchIdForUser($user);
        if ($activeBranchId) {
            $newToken->accessToken->forceFill([
                'active_branch_id' => $activeBranchId,
            ])->save();
        }
        $token = $newToken->plainTextToken;

        $this->logAuthEvent($request, 'login_success', $user, $user->email, $user->tenant);

        return response()->json([
            'token' => $token,
            'user' => $user,
            'tenant' => $user->tenant,
            'permissions' => Permissions::forUser($user),
        ]);
    }

    public function loginOtp(Request $request)
    {
        $validated = $request->validate([
            'otp_login_token' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);

        $userId = Cache::get('otp_login:'.$validated['otp_login_token']);

        if (! $userId) {
            $this->logAuthEvent($request, 'login_otp_expired', null, null, null);

            return response()->json([
                'message' => 'OTP challenge expired.',
            ], 422);
        }

        $user = User::query()->find($userId);

        $email = (string) ($user?->email ?? '');

        if ($email !== '' && $this->isLockedOut($request, $email, $user)) {
            $this->logAuthEvent($request, 'login_otp_locked_out', $user, $email, $user?->tenant);

            return response()->json([
                'message' => 'Too many login attempts. Please try again later.',
            ], 423);
        }

        if (! $user || ! $user->hasVerifiedEmail() || ! $user->otp_enabled || ! $user->otp_secret) {
            $this->logAuthEvent($request, 'login_otp_invalid', $user, $user?->email, $user?->tenant);

            return response()->json([
                'message' => 'OTP challenge invalid.',
            ], 422);
        }

        if (! Totp::verify($user->otp_secret, $validated['code'])) {
            $this->logAuthEvent($request, 'login_otp_failed', $user, $user->email, $user->tenant);

            $this->registerFailedAttempt($request, (string) $user->email, $user);

            return response()->json([
                'message' => 'Invalid OTP code.',
            ], 422);
        }

        $this->clearFailedAttempts($request, (string) $user->email);

        Cache::forget('otp_login:'.$validated['otp_login_token']);

        if (! $user->is_admin) {
            $tenantId = (int) ($user->tenant_id ?? 0);
            if ($tenantId <= 0) {
                return response()->json([
                    'message' => 'Account is not linked to a business.',
                ], 403);
            }

            $hasAnyAssignment = DB::table('branch_user')
                ->where('tenant_id', $tenantId)
                ->where('user_id', (int) $user->id)
                ->exists();

            $isBranchAdmin = Permissions::userHas($user, 'branches.manage');

            if (! $isBranchAdmin && ! $hasAnyAssignment) {
                return response()->json([
                    'message' => 'No branch access assigned. Please contact your administrator.',
                ], 403);
            }
        }

        /** @var NewAccessToken $newToken */
        $newToken = $user->createToken('api');
        $activeBranchId = $this->resolveInitialActiveBranchIdForUser($user);
        if ($activeBranchId) {
            $newToken->accessToken->forceFill([
                'active_branch_id' => $activeBranchId,
            ])->save();
        }
        $token = $newToken->plainTextToken;

        $this->logAuthEvent($request, 'login_otp_success', $user, $user->email, $user->tenant);

        return response()->json([
            'token' => $token,
            'user' => $user,
            'tenant' => $user->tenant,
            'permissions' => Permissions::forUser($user),
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $user->currentAccessToken()?->delete();
            $this->logAuthEvent($request, 'logout', $user, $user->email, $user->tenant);
        }

        return response()->json([
            'status' => 'ok',
        ]);
    }

    public function resendVerificationEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = (string) $validated['email'];
        $user = User::query()->where('email', $email)->first();

        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
            $this->logAuthEvent($request, 'verification_email_resent', $user, $email, $user->tenant);
        } else {
            $this->logAuthEvent($request, 'verification_email_resend_ignored', $user, $email, $user?->tenant);
        }

        return response()->json([
            'status' => 'ok',
        ]);
    }

    public function sendResetLinkEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = (string) $validated['email'];

        $status = Password::sendResetLink([
            'email' => $email,
        ]);

        $user = User::query()->where('email', $email)->first();
        $this->logAuthEvent($request, 'password_reset_link_requested', $user, $email, $user?->tenant, [
            'result' => $status,
        ]);

        return response()->json([
            'message' => __($status),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => [
                'required',
                'string',
                PasswordRule::min(12)->letters()->mixedCase()->numbers()->symbols(),
                'confirmed',
            ],
        ]);

        $email = (string) $validated['email'];

        $status = Password::reset(
            [
                'email' => $validated['email'],
                'password' => $validated['password'],
                'password_confirmation' => $request->input('password_confirmation'),
                'token' => $validated['token'],
            ],
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                $user->tokens()->delete();
            }
        );

        $user = User::query()->where('email', $email)->first();
        $this->logAuthEvent($request, 'password_reset_attempt', $user, $email, $user?->tenant, [
            'result' => $status,
        ]);

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'message' => __($status),
            ], 422);
        }

        return response()->json([
            'message' => __($status),
        ]);
    }

    public function otpSetup(Request $request)
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        $secret = Totp::generateSecret();
        $issuer = (string) config('app.name');
        $uri = Totp::provisioningUri($issuer, (string) $user->email, $secret);

        $user->forceFill([
            'otp_enabled' => true,
            'otp_secret' => $secret,
            'otp_confirmed_at' => null,
        ])->save();

        $this->logAuthEvent($request, 'otp_setup_started', $user, $user->email, $user->tenant);

        return response()->json([
            'secret' => $secret,
            'otpauth_uri' => $uri,
        ]);
    }

    public function otpConfirm(Request $request)
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        if (! is_string($user->otp_secret) || $user->otp_secret === '') {
            return response()->json([
                'message' => 'OTP setup not started.',
            ], 422);
        }

        if (! Totp::verify($user->otp_secret, (string) $validated['code'])) {
            $this->logAuthEvent($request, 'otp_confirm_failed', $user, $user->email, $user->tenant);

            return response()->json([
                'message' => 'Invalid OTP code.',
            ], 422);
        }

        $user->forceFill([
            'otp_enabled' => true,
            'otp_confirmed_at' => now(),
        ])->save();

        $this->logAuthEvent($request, 'otp_confirmed', $user, $user->email, $user->tenant);

        return response()->json([
            'status' => 'ok',
        ]);
    }

    public function otpDisable(Request $request)
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        $validated = $request->validate([
            'password' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);

        if (! Hash::check((string) $validated['password'], (string) $user->password)) {
            return response()->json([
                'message' => 'Invalid password.',
            ], 422);
        }

        if (! is_string($user->otp_secret) || $user->otp_secret === '' || ! Totp::verify($user->otp_secret, (string) $validated['code'])) {
            return response()->json([
                'message' => 'Invalid OTP code.',
            ], 422);
        }

        $user->forceFill([
            'otp_enabled' => false,
            'otp_secret' => null,
            'otp_confirmed_at' => null,
        ])->save();

        $this->logAuthEvent($request, 'otp_disabled', $user, $user->email, $user->tenant);

        return response()->json([
            'status' => 'ok',
        ]);
    }

    public function updateMe(Request $request)
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        $session = $request->attributes->get('impersonation_session');
        if ($session) {
            return response()->json([
                'message' => 'Profile updates are not allowed during impersonation.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'address_city' => ['nullable', 'string', 'max:255'],
            'address_state' => ['nullable', 'string', 'max:255'],
            'address_postal_code' => ['nullable', 'string', 'max:64'],
            'address_country' => ['nullable', 'string', 'size:2'],
        ]);

        $before = [
            'name' => $user->name,
            'phone' => $user->phone,
            'address_line1' => $user->address_line1,
            'address_line2' => $user->address_line2,
            'address_city' => $user->address_city,
            'address_state' => $user->address_state,
            'address_postal_code' => $user->address_postal_code,
            'address_country' => $user->address_country,
        ];

        $user->forceFill([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'address_line1' => $validated['address_line1'] ?? null,
            'address_line2' => $validated['address_line2'] ?? null,
            'address_city' => $validated['address_city'] ?? null,
            'address_state' => $validated['address_state'] ?? null,
            'address_postal_code' => $validated['address_postal_code'] ?? null,
            'address_country' => isset($validated['address_country']) && $validated['address_country']
                ? strtoupper((string) $validated['address_country'])
                : null,
        ])->save();

        $this->logAuthEvent($request, 'profile.updated', $user, $user->email, $user->tenant, [
            'before' => $before,
            'after' => [
                'name' => $user->name,
                'phone' => $user->phone,
                'address_line1' => $user->address_line1,
                'address_line2' => $user->address_line2,
                'address_city' => $user->address_city,
                'address_state' => $user->address_state,
                'address_postal_code' => $user->address_postal_code,
                'address_country' => $user->address_country,
            ],
        ]);

        return response()->json([
            'user' => $user,
        ]);
    }

    public function updateAvatar(Request $request)
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        $session = $request->attributes->get('impersonation_session');
        if ($session) {
            return response()->json([
                'message' => 'Profile updates are not allowed during impersonation.',
            ], 403);
        }

        $validated = $request->validate([
            'avatar' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $before = [
            'avatar_path' => $user->avatar_path,
        ];

        if (is_string($user->avatar_path) && $user->avatar_path !== '') {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $file = $validated['avatar'];
        $path = $file->storePublicly('avatars/'.$user->id, ['disk' => 'public']);

        $user->forceFill([
            'avatar_path' => $path,
        ])->save();

        $this->logAuthEvent($request, 'profile.avatar_updated', $user, $user->email, $user->tenant, [
            'before' => $before,
            'after' => [
                'avatar_path' => $user->avatar_path,
            ],
        ]);

        return response()->json([
            'user' => $user,
        ]);
    }

    public function deleteAvatar(Request $request)
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        $session = $request->attributes->get('impersonation_session');
        if ($session) {
            return response()->json([
                'message' => 'Profile updates are not allowed during impersonation.',
            ], 403);
        }

        $before = [
            'avatar_path' => $user->avatar_path,
        ];

        if (is_string($user->avatar_path) && $user->avatar_path !== '') {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->forceFill([
            'avatar_path' => null,
        ])->save();

        $this->logAuthEvent($request, 'profile.avatar_deleted', $user, $user->email, $user->tenant, [
            'before' => $before,
            'after' => [
                'avatar_path' => $user->avatar_path,
            ],
        ]);

        return response()->json([
            'user' => $user,
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        $actor = $request->attributes->get('impersonator_user');
        $session = $request->attributes->get('impersonation_session');

        $actorUser = $actor instanceof User ? $actor : null;

        return response()->json([
            'user' => $user,
            'tenant' => $user?->tenant,
            'permissions' => Permissions::forUser($user),
            'actor_user' => $actorUser,
            'actor_permissions' => $actorUser ? Permissions::forUser($actorUser) : [],
            'impersonation' => $session ? [
                'session_id' => $session->id ?? null,
                'tenant_id' => $session->tenant_id ?? null,
                'target_user_id' => $session->target_user_id ?? null,
                'started_at' => $session->started_at?->toIso8601String(),
                'expires_at' => $session->expires_at?->toIso8601String(),
                'reference_id' => $session->reference_id ?? null,
            ] : null,
        ]);
    }
}
