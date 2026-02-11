<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected function dashboardFallbackUrl(?int $tenantId): string
    {
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

    public function showLogin(Request $request)
    {
        if (Auth::check()) {
            $user = $request->user();
            $tenantId = is_numeric($user?->tenant_id) ? (int) $user->tenant_id : null;

            return redirect()->intended($this->dashboardFallbackUrl($tenantId));
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = (bool) $request->boolean('remember');

        if (! Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']], $remember)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();
        $tenantId = is_numeric($user?->tenant_id) ? (int) $user->tenant_id : null;

        return redirect()->intended($this->dashboardFallbackUrl($tenantId));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
