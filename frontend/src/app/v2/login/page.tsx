"use client";

import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import React, { Suspense, useState } from "react";
import { useAuth } from "@/lib/auth";
import { ApiError } from "@/lib/api";

const WrenchIcon = () => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
    <path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z" />
  </svg>
);

function LoginForm() {
  const auth = useAuth();
  const router = useRouter();
  const searchParams = useSearchParams();
  const next = searchParams.get("next") ?? "/";

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [rememberMe, setRememberMe] = useState(false);
  const [otpCode, setOtpCode] = useState("");
  const [otpLoginToken, setOtpLoginToken] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setSubmitting(true);
    try {
      if (otpLoginToken) {
        const otpRes = await auth.loginOtp(otpLoginToken, otpCode);
        if (otpRes.must_change_password) {
          router.replace(`/set-password?next=${encodeURIComponent(otpRes.is_admin ? "/superadmin" : next)}`);
        } else {
          router.replace(otpRes.is_admin ? "/superadmin" : next);
        }
      } else {
        const res = await auth.login(email, password);
        if (res.status === "otp_required") {
          setOtpLoginToken(res.otp_login_token ?? null);
        } else if (res.status === "verification_required") {
          router.replace(`/verify-email?email=${encodeURIComponent(email)}`);
        } else if (res.must_change_password) {
          router.replace(`/set-password?next=${encodeURIComponent(res.is_admin ? "/superadmin" : next)}`);
        } else {
          router.replace(res.is_admin ? "/superadmin" : next);
        }
      }
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Login failed. Please try again.");
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="auth-page">
      <Link href="/v2" className="brand-link">
        <div className="logo-mark-lg"><WrenchIcon /></div>
        <span className="brand-name-lg">99SmartX</span>
      </Link>

      <div className="auth-card">
        <div className="auth-header">
          <h1>{otpLoginToken ? "Two-Factor Auth" : "Welcome back"}</h1>
          <p>{otpLoginToken ? "Enter the code from your authenticator app" : "Sign in to manage your repair shop"}</p>
        </div>

        {error && <div className="alert-error">{error}</div>}

        <form onSubmit={onSubmit}>
          {otpLoginToken ? (
            <div className="form-group">
              <label className="form-label">Verification Code</label>
              <div className="input-wrap">
                <input
                  type="text"
                  className="form-input"
                  placeholder="000000"
                  value={otpCode}
                  onChange={(e) => setOtpCode(e.target.value)}
                  maxLength={6}
                  autoComplete="one-time-code"
                  required
                />
                <svg className="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
              </div>
            </div>
          ) : (
            <>
              {/* Email */}
              <div className="form-group">
                <label className="form-label" style={{ display: "block", marginBottom: 7 }}>Email Address</label>
                <div className="input-wrap">
                  <input
                    type="email"
                    className="form-input"
                    placeholder="john@repairshop.com"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    required
                    autoComplete="email"
                  />
                  <svg className="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                </div>
              </div>

              {/* Password */}
              <div className="form-group">
                <div className="form-label-row">
                  <label className="form-label" style={{ marginBottom: 0 }}>Password</label>
                  <Link href="/reset-password" className="forgot-link">Forgot password?</Link>
                </div>
                <div className="input-wrap">
                  <input
                    type={showPassword ? "text" : "password"}
                    className="form-input"
                    placeholder="Enter your password"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    required
                    autoComplete="current-password"
                  />
                  <svg className="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                  </svg>
                  <button
                    type="button"
                    className="pw-toggle"
                    onClick={() => setShowPassword(!showPassword)}
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

              {/* Remember me */}
              <div className="check-row">
                <div className="check-group" style={{ margin: 0 }}>
                  <input
                    type="checkbox"
                    id="remember"
                    checked={rememberMe}
                    onChange={(e) => setRememberMe(e.target.checked)}
                  />
                  <label htmlFor="remember" style={{ fontSize: 13, color: "var(--text-2)", fontWeight: 500 }}>Remember me</label>
                </div>
              </div>
            </>
          )}

          <button type="submit" className="btn-submit" disabled={submitting}>
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
            {submitting ? "Signing in…" : "Sign In"}
          </button>
        </form>

        {!otpLoginToken && (
          <>
            <div className="divider"><span>or continue with</span></div>
            <button type="button" className="social-btn">
              <svg viewBox="0 0 24 24" fill="none">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4" />
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853" />
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05" />
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335" />
              </svg>
              Continue with Google
            </button>
          </>
        )}

        <div className="auth-footer">
          {otpLoginToken ? (
            <button
              type="button"
              onClick={() => setOtpLoginToken(null)}
              style={{ background: "none", border: "none", color: "var(--orange)", fontWeight: 700, cursor: "pointer", fontSize: 14 }}
            >
              ← Back to sign in
            </button>
          ) : (
            <>Don't have an account? <Link href="/v2/register">Sign up free</Link></>
          )}
        </div>
      </div>

      <Link href="/v2" className="back-link">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Back to home
      </Link>
    </div>
  );
}

export default function LoginV2() {
  return (
    <Suspense>
      <LoginForm />
    </Suspense>
  );
}
