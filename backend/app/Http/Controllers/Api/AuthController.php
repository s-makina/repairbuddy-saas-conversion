<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\AuthEvent;
use App\Models\User;
use App\Support\Permissions;
use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
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

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            $this->logAuthEvent($request, 'login_failed', null, $validated['email'], null);

            return response()->json([
                'message' => 'Invalid credentials.',
            ], 422);
        }

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

        $token = $user->createToken('api')->plainTextToken;

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

        if (! $user || ! $user->hasVerifiedEmail() || ! $user->otp_enabled || ! $user->otp_secret) {
            $this->logAuthEvent($request, 'login_otp_invalid', $user, $user?->email, $user?->tenant);

            return response()->json([
                'message' => 'OTP challenge invalid.',
            ], 422);
        }

        if (! Totp::verify($user->otp_secret, $validated['code'])) {
            $this->logAuthEvent($request, 'login_otp_failed', $user, $user->email, $user->tenant);

            return response()->json([
                'message' => 'Invalid OTP code.',
            ], 422);
        }

        Cache::forget('otp_login:'.$validated['otp_login_token']);

        $token = $user->createToken('api')->plainTextToken;

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
