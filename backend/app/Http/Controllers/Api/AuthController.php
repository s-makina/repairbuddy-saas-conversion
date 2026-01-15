<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
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

        if ($tenantSlug) {
            $tenant = Tenant::query()->where('slug', $tenantSlug)->firstOrFail();
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

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'is_admin' => false,
        ]);

        $user->sendEmailVerificationNotification();

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
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 422);
        }

        if (! $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email address not verified.',
                'verification_required' => true,
            ], 403);
        }

        if ($user->otp_enabled && $user->otp_secret && $user->otp_confirmed_at) {
            $otpLoginToken = Str::random(64);

            Cache::put('otp_login:'.$otpLoginToken, (int) $user->id, now()->addMinutes(5));

            return response()->json([
                'otp_required' => true,
                'otp_login_token' => $otpLoginToken,
            ]);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
            'tenant' => $user->tenant,
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
            return response()->json([
                'message' => 'OTP challenge expired.',
            ], 422);
        }

        $user = User::query()->find($userId);

        if (! $user || ! $user->hasVerifiedEmail() || ! $user->otp_enabled || ! $user->otp_secret) {
            return response()->json([
                'message' => 'OTP challenge invalid.',
            ], 422);
        }

        if (! Totp::verify($user->otp_secret, $validated['code'])) {
            return response()->json([
                'message' => 'Invalid OTP code.',
            ], 422);
        }

        Cache::forget('otp_login:'.$validated['otp_login_token']);

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
            'tenant' => $user->tenant,
        ]);
    }

    public function resendVerificationEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return response()->json([
            'status' => 'ok',
        ]);
    }

    public function otpSetup(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $secret = Totp::generateSecret();
        $user->forceFill([
            'otp_enabled' => false,
            'otp_secret' => $secret,
            'otp_confirmed_at' => null,
        ])->save();

        $issuer = (string) config('app.name', 'RepairBuddy');
        $uri = Totp::provisioningUri($issuer, $user->email, $secret);

        return response()->json([
            'secret' => $secret,
            'otpauth_uri' => $uri,
        ]);
    }

    public function otpConfirm(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! $user || ! $user->otp_secret) {
            return response()->json([
                'message' => 'OTP not initialized.',
            ], 422);
        }

        if (! Totp::verify($user->otp_secret, $validated['code'])) {
            return response()->json([
                'message' => 'Invalid OTP code.',
            ], 422);
        }

        $user->forceFill([
            'otp_enabled' => true,
            'otp_confirmed_at' => now(),
        ])->save();

        return response()->json([
            'status' => 'ok',
        ]);
    }

    public function otpDisable(Request $request)
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! $user || ! $user->otp_enabled || ! $user->otp_secret) {
            return response()->json([
                'message' => 'OTP is not enabled.',
            ], 422);
        }

        if (! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid password.',
            ], 422);
        }

        if (! Totp::verify($user->otp_secret, $validated['code'])) {
            return response()->json([
                'message' => 'Invalid OTP code.',
            ], 422);
        }

        $user->forceFill([
            'otp_enabled' => false,
            'otp_secret' => null,
            'otp_confirmed_at' => null,
        ])->save();

        return response()->json([
            'status' => 'ok',
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $user->currentAccessToken()?->delete();
        }

        return response()->json([
            'status' => 'ok',
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => $user,
            'tenant' => $user?->tenant,
        ]);
    }
}
