"use client";

import React, { Suspense, useEffect, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { useAuth } from "@/lib/auth";
import { ApiError } from "@/lib/api";

const WrenchIcon = () => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
    <path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z" />
  </svg>
);

function SuperAdminLoginForm() {
  const auth = useAuth();
  const router = useRouter();
  const searchParams = useSearchParams();
  const next = searchParams.get("next") ?? "/superadmin";
  const errorParam = searchParams.get("error");

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [otpCode, setOtpCode] = useState("");
  const [otpLoginToken, setOtpLoginToken] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(
    errorParam === "access_denied" ? "Access denied. This portal is for platform administrators only." : null
  );
  const [submitting, setSubmitting] = useState(false);

  // If already authenticated as admin, redirect immediately
  useEffect(() => {
    if (!auth.loading && auth.isAuthenticated && auth.isAdmin) {
      router.replace(next);
    }
  }, [auth.loading, auth.isAuthenticated, auth.isAdmin, next, router]);

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setSubmitting(true);

    try {
      if (otpLoginToken) {
        const otpRes = await auth.loginOtp(otpLoginToken, otpCode);
        if (!otpRes.is_admin) {
          await auth.logout();
          setError("Access denied. This portal is for platform administrators only.");
          setOtpLoginToken(null);
          return;
        }
        if (otpRes.must_change_password) {
          router.replace(`/set-password?next=${encodeURIComponent(next)}`);
        } else {
          router.replace(next);
        }
      } else {
        const res = await auth.login(email, password);

        if (res.status === "otp_required") {
          setOtpLoginToken(res.otp_login_token ?? null);
          return;
        }

        if (res.status === "verification_required") {
          setError("Please verify your email address before signing in.");
          return;
        }

        // status === "ok" — now check admin
        if (!res.is_admin) {
          await auth.logout();
          setError("Access denied. This portal is for platform administrators only.");
          return;
        }

        if (res.must_change_password) {
          router.replace(`/set-password?next=${encodeURIComponent(next)}`);
        } else {
          router.replace(next);
        }
      }
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Sign in failed. Please check your credentials and try again.");
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="sa-login-page">
      {/* Brand mark */}
      <div className="sa-login-brand">
        <div className="sa-login-logo">
          <WrenchIcon />
        </div>
        <span className="sa-login-brand-name">99SmartX</span>
        <span className="sa-login-badge">Platform Admin</span>
      </div>

      <div className="sa-login-card">
        <div className="sa-login-header">
          <h1>{otpLoginToken ? "Two-Factor Auth" : "Admin Sign In"}</h1>
          <p>
            {otpLoginToken
              ? "Enter the code from your authenticator app"
              : "Platform administrator access only"}
          </p>
        </div>

        {error && (
          <div className="sa-login-alert">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
            </svg>
            {error}
          </div>
        )}

        <form onSubmit={onSubmit} className="sa-login-form">
          {otpLoginToken ? (
            <div className="sa-form-group">
              <label className="sa-form-label">Verification Code</label>
              <div className="sa-input-wrap">
                <svg className="sa-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                <input
                  type="text"
                  className="sa-form-input"
                  placeholder="000000"
                  value={otpCode}
                  onChange={(e) => setOtpCode(e.target.value)}
                  maxLength={6}
                  autoComplete="one-time-code"
                  autoFocus
                  required
                />
              </div>
            </div>
          ) : (
            <>
              <div className="sa-form-group">
                <label className="sa-form-label">Email Address</label>
                <div className="sa-input-wrap">
                  <svg className="sa-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                  <input
                    type="email"
                    className="sa-form-input"
                    placeholder="admin@platform.com"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    required
                    autoComplete="email"
                    autoFocus
                  />
                </div>
              </div>

              <div className="sa-form-group">
                <label className="sa-form-label">Password</label>
                <div className="sa-input-wrap">
                  <svg className="sa-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                  </svg>
                  <input
                    type={showPassword ? "text" : "password"}
                    className="sa-form-input sa-form-input--pw"
                    placeholder="Enter your password"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    required
                    autoComplete="current-password"
                  />
                  <button
                    type="button"
                    className="sa-pw-toggle"
                    onClick={() => setShowPassword((v) => !v)}
                    aria-label={showPassword ? "Hide password" : "Show password"}
                  >
                    {showPassword ? (
                      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                      </svg>
                    ) : (
                      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    )}
                  </button>
                </div>
              </div>
            </>
          )}

          <button type="submit" className="sa-login-btn" disabled={submitting}>
            {submitting ? (
              <>
                <svg className="sa-login-btn-spinner" viewBox="0 0 24 24" fill="none">
                  <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="3" strokeOpacity="0.25" />
                  <path d="M12 2a10 10 0 010 20" stroke="currentColor" strokeWidth="3" strokeLinecap="round" />
                </svg>
                Signing in…
              </>
            ) : (
              <>
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
                Sign In to Admin Panel
              </>
            )}
          </button>
        </form>

        {otpLoginToken && (
          <div className="sa-login-footer">
            <button
              type="button"
              className="sa-login-back"
              onClick={() => { setOtpLoginToken(null); setOtpCode(""); }}
            >
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              Back to sign in
            </button>
          </div>
        )}
      </div>

      <p className="sa-login-notice">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
        Restricted access — authorised personnel only
      </p>
    </div>
  );
}

export default function SuperAdminLoginPage() {
  return (
    <Suspense>
      <SuperAdminLoginForm />
    </Suspense>
  );
}
