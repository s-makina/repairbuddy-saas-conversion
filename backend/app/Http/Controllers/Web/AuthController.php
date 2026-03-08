<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected function tenantRouteName(Request $request, string $suffix): string
    {
        $prefix = $request->routeIs('tenant.subdomain.*') ? 'tenant.subdomain' : 'tenant';

        return $prefix.'.'.$suffix;
    }

    /**
     * Get the tenant slug from the current request URL.
     */
    protected function getTenantSlugFromRequest(Request $request): ?string
    {
        $param = config('tenancy.route_param', 'business');
        $slug = $request->route($param);

        return is_string($slug) && $slug !== '' ? $slug : null;
    }

    /**
     * Get the current tenant from TenantContext (resolved by ResolveTenant middleware).
     */
    protected function getCurrentTenant(): ?Tenant
    {
        return TenantContext::tenant();
    }

    protected function dashboardFallbackUrl(?int $tenantId, ?string $slug = null): string
    {
        // If slug provided directly, use it
        if ($slug !== null && $slug !== '') {
            return route('tenant.dashboard', ['business' => $slug]);
        }

        // Otherwise resolve from tenant ID
        if (! $tenantId || $tenantId <= 0) {
            return '/';
        }

        $slug = Tenant::query()->where('id', $tenantId)->value('slug');
        $slug = is_string($slug) ? $slug : '';

        if ($slug === '') {
            return '/';
        }

        return route('tenant.dashboard', ['business' => $slug]);
    }

    // ==================== Login ====================

    public function showLogin(Request $request)
    {
        $tenant = $this->getCurrentTenant();
        $tenantSlug = $this->getTenantSlugFromRequest($request);

        if (Auth::check()) {
            $user = $request->user();

            // Verify user belongs to this tenant
            if ($tenant && $user->tenant_id !== $tenant->id) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route($this->tenantRouteName($request, 'login'), ['business' => $tenantSlug])
                    ->withErrors(['email' => 'You are not authorized for this business.']);
            }

            $tenantId = is_numeric($user?->tenant_id) ? (int) $user->tenant_id : null;

            return redirect()->intended($this->dashboardFallbackUrl($tenantId, $tenantSlug));
        }

        $view = $tenantSlug ? 'tenant.auth.login' : 'auth.login';

        return view($view, [
            'tenant' => $tenant,
            'tenantSlug' => $tenantSlug,
        ]);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = (bool) $request->boolean('remember');
        $tenant = $this->getCurrentTenant();
        $tenantSlug = $this->getTenantSlugFromRequest($request);

        // First, find the user to check status
        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'auth' => ['Invalid credentials.'],
            ]);
        }

        // Validate user belongs to the tenant from URL
        if ($tenant && $user->tenant_id !== $tenant->id) {
            throw ValidationException::withMessages([
                'auth' => ['This account is not authorized for this business.'],
            ]);
        }

        // Check user status
        $status = $user->status ?? 'active';
        if ($status === 'pending') {
            throw ValidationException::withMessages([
                'auth' => ['Your account is pending activation. Please wait for an administrator to approve your account.'],
            ]);
        }

        if ($status === 'inactive') {
            throw ValidationException::withMessages([
                'auth' => ['Your account has been deactivated. Please contact an administrator.'],
            ]);
        }

        // Login the user
        Auth::login($user, $remember);

        $request->session()->regenerate();

        // Check if 2FA is enabled for this user
        if ($user->two_factor_enabled ?? false) {
            return redirect()->route($this->tenantRouteName($request, '2fa.show'), ['business' => $tenantSlug]);
        }

        $tenantId = is_numeric($user?->tenant_id) ? (int) $user->tenant_id : null;

        return redirect()->intended($this->dashboardFallbackUrl($tenantId, $tenantSlug));
    }

    // ==================== Register ====================

    public function showRegister(Request $request)
    {
        $tenant = $this->getCurrentTenant();
        $tenantSlug = $this->getTenantSlugFromRequest($request);

        if (Auth::check()) {
            $user = $request->user();

            // Verify user belongs to this tenant
            if ($tenant && $user->tenant_id !== $tenant->id) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route($this->tenantRouteName($request, 'login'), ['business' => $tenantSlug])
                    ->withErrors(['email' => 'You are not authorized for this business.']);
            }

            $tenantId = is_numeric($user?->tenant_id) ? (int) $user->tenant_id : null;

            return redirect()->intended($this->dashboardFallbackUrl($tenantId, $tenantSlug));
        }

        $view = $tenantSlug ? 'tenant.auth.register' : 'auth.register';

        return view($view, [
            'tenant' => $tenant,
            'tenantSlug' => $tenantSlug,
        ]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms' => ['required', 'accepted'],
        ]);

        $tenantSlug = $this->getTenantSlugFromRequest($request);
        $loginRoute = $this->tenantRouteName($request, 'login');

        // Check if the email is already registered (handles timeout-retry case)
        $existingUser = User::where('email', $validated['email'])->first();
        if ($existingUser) {
            if (is_null($existingUser->email_verified_at)) {
                // User registered but verification email never arrived (e.g. previous timeout).
                // Resend verification and show a clear message instead of a confusing error.
                event(new Registered($existingUser));

                return redirect()->route($loginRoute, ['business' => $tenantSlug])
                    ->with('status', 'A verification email has been resent to ' . $existingUser->email . '. Please check your inbox.');
            }

            return back()
                ->withInput($request->only('first_name', 'last_name', 'email'))
                ->withErrors(['email' => 'This email is already registered. Please log in or reset your password.']);
        }

        $tenant = $this->getCurrentTenant();
        $tenantId = $tenant?->id;

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'name' => trim($validated['first_name'] . ' ' . $validated['last_name']),
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'status' => 'pending',
            'tenant_id' => $tenantId,
        ]);

        event(new Registered($user));

        // Don't auto-login - user must wait for admin activation
        return redirect()->route($loginRoute, ['business' => $tenantSlug])
            ->with('status', 'Account created successfully! Please check your email to verify your address.');
    }

    // ==================== Forgot Password ====================

    public function showForgotPassword(Request $request)
    {
        $tenant = $this->getCurrentTenant();
        $tenantSlug = $this->getTenantSlugFromRequest($request);

        $view = $tenantSlug ? 'tenant.auth.forgot-password' : 'auth.forgot-password';

        return view($view, [
            'tenant' => $tenant,
            'tenantSlug' => $tenantSlug,
        ]);
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    // ==================== Reset Password ====================

    public function showResetPassword(Request $request)
    {
        $tenantSlug = $this->getTenantSlugFromRequest($request);

        return view('auth.reset-password', [
            'token' => $request->query('token'),
            'email' => $request->query('email'),
            'tenantSlug' => $tenantSlug,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        $tenantSlug = $this->getTenantSlugFromRequest($request);

        return $status === Password::PASSWORD_RESET
            ? redirect()->route($this->tenantRouteName($request, 'login'), ['business' => $tenantSlug])->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }

    // ==================== 2FA Verification ====================

    public function show2FA(Request $request)
    {
        $user = $request->user();
        $tenantSlug = $this->getTenantSlugFromRequest($request);

        if (! ($user->two_factor_enabled ?? false)) {
            $tenantId = is_numeric($user?->tenant_id) ? (int) $user->tenant_id : null;
            return redirect()->intended($this->dashboardFallbackUrl($tenantId, $tenantSlug));
        }

        return view('auth.verify-2fa', [
            'tenantSlug' => $tenantSlug,
        ]);
    }

    public function verify2FA(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();
        $code = $request->input('code');

        // Verify the 2FA code
        // This is a placeholder implementation - integrate with your 2FA provider
        $valid = $this->verifyTwoFactorCode($user, $code);

        if (! $valid) {
            throw ValidationException::withMessages([
                'code' => ['Invalid verification code.'],
            ]);
        }

        // Mark 2FA as verified in session
        $request->session()->put('2fa_verified', true);

        $tenantSlug = $this->getTenantSlugFromRequest($request);
        $tenantId = is_numeric($user?->tenant_id) ? (int) $user->tenant_id : null;

        return redirect()->intended($this->dashboardFallbackUrl($tenantId, $tenantSlug));
    }

    public function resend2FA(Request $request)
    {
        $user = $request->user();

        // Resend 2FA code - placeholder implementation
        $this->sendTwoFactorCode($user);

        return back()->with('status', 'A new verification code has been sent to your email.');
    }

    /**
     * Verify the 2FA code for the user.
     * Placeholder implementation - integrate with your 2FA provider.
     */
    protected function verifyTwoFactorCode(User $user, string $code): bool
    {
        // TODO: Integrate with actual 2FA provider (e.g., Google Authenticator, SMS, Email)
        // For now, accept any 6-digit code for development
        return preg_match('/^\d{6}$/', $code) === 1;
    }

    /**
     * Send a 2FA code to the user.
     * Placeholder implementation - integrate with your 2FA provider.
     */
    protected function sendTwoFactorCode(User $user): void
    {
        // TODO: Integrate with actual 2FA provider
        // For now, this is a placeholder
    }

    // ==================== Logout ====================

    public function logout(Request $request)
    {
        $user = $request->user();
        $tenantSlug = $this->getTenantSlugFromRequest($request);

        // If no tenant slug in URL, try to get it from the user's tenant
        if (!$tenantSlug && $user && $user->tenant_id) {
            $tenantSlug = Tenant::query()->where('id', $user->tenant_id)->value('slug');
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redirect to tenant-scoped login if we have a slug
        if ($tenantSlug) {
            return redirect()->route($this->tenantRouteName($request, 'login'), ['business' => $tenantSlug]);
        }

        return redirect('/login');
    }
}
