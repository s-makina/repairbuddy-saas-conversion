"use client";

import { useAuth } from "@/lib/auth";
import { apiFetch, ApiError } from "@/lib/api";
import { PublicPageShell } from "@/components/PublicPageShell";
import { Alert } from "@/components/ui/Alert";
import { Button } from "@/components/ui/Button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/Card";
import { Input } from "@/components/ui/Input";
import { Preloader } from "@/components/Preloader";
import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import React, { Suspense, useMemo, useState } from "react";

function EyeIcon({ className }: { className?: string }) {
  return (
    <svg
      viewBox="0 0 24 24"
      className={className}
      fill="none"
      stroke="currentColor"
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
    >
      <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z" />
      <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" />
    </svg>
  );
}

function EyeOffIcon({ className }: { className?: string }) {
  return (
    <svg
      viewBox="0 0 24 24"
      className={className}
      fill="none"
      stroke="currentColor"
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
    >
      <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8" />
      <path d="M9.9 4.2A10.4 10.4 0 0 1 12 4c6.5 0 10 8 10 8a18.2 18.2 0 0 1-2.2 3.2" />
      <path d="M6.1 6.1A18.5 18.5 0 0 0 2 12s3.5 7 10 7a10.7 10.7 0 0 0 5.1-1.2" />
      <path d="M2 2l20 20" />
    </svg>
  );
}

function LoginPageInner() {
  const auth = useAuth();
  const router = useRouter();
  const searchParams = useSearchParams();

  const next = useMemo(() => searchParams.get("next"), [searchParams]);

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [otpCode, setOtpCode] = useState("");
  const [otpLoginToken, setOtpLoginToken] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [status, setStatus] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setStatus(null);
    setSubmitting(true);

    try {
      if (otpLoginToken) {
        const otpRes = await auth.loginOtp(otpLoginToken, otpCode);
        if (otpRes.must_change_password) {
          router.replace(`/set-password?next=${encodeURIComponent(next || "/")}`);
        } else {
          router.replace(next || "/");
        }
        return;
      }

      const res = await auth.login(email, password);

      if (res.status === "ok") {
        if (res.must_change_password) {
          router.replace(`/set-password?next=${encodeURIComponent(next || "/")}`);
        } else {
          router.replace(next || "/");
        }
        return;
      }

      if (res.status === "verification_required") {
        router.replace(`/verify-email?email=${encodeURIComponent(email)}`);
        return;
      }

      setOtpLoginToken(res.otp_login_token);
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError("Login failed.");
      }
    } finally {
      setSubmitting(false);
    }
  }

  const [forgotPassword, setForgotPassword] = useState(false);
  const [resetSent, setResetSent] = useState(false);

  async function onForgotPassword(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setStatus(null);
    setSubmitting(true);

    try {
      await apiFetch<{ message: string }>("/api/auth/password/email", {
        method: "POST",
        body: { email },
      });
      setResetSent(true);
      setStatus("If that email exists, we sent a reset link.");
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError("Failed to send reset link.");
      }
    } finally {
      setSubmitting(false);
    }
  }

  if (forgotPassword) {
    return (
      <PublicPageShell badge="Reset password" centerContent>
        <section className="mx-auto w-full max-w-6xl px-4 py-10">
          <div className="flex justify-center">
            <div className="w-full max-w-md">
              <Card className="bg-white/70">
                <CardHeader>
                  <CardTitle className="text-base">Reset your password</CardTitle>
                  <CardDescription>
                    {resetSent
                      ? "Check your inbox for the reset link."
                      : "Enter your email address and we’ll send you a secure reset link."}
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    {error ? <Alert variant="danger" title="Something went wrong">{error}</Alert> : null}
                    {status ? <Alert variant="success" title="Check your email">{status}</Alert> : null}

                    {!resetSent ? (
                      <form className="space-y-4" onSubmit={onForgotPassword}>
                        <div className="space-y-1">
                          <label className="text-sm font-medium" htmlFor="reset_email">
                            Email
                          </label>
                          <Input
                            id="reset_email"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            type="email"
                            required
                            autoComplete="email"
                            placeholder="you@company.com"
                            disabled={submitting}
                          />
                        </div>

                        <Button className="w-full" type="submit" disabled={submitting}>
                          {submitting ? "Sending..." : "Send reset link"}
                        </Button>
                      </form>
                    ) : null}
                  </div>
                </CardContent>
              </Card>

              <div className="mt-5 text-center text-sm text-zinc-600">
                <button
                  type="button"
                  className="font-medium text-[var(--rb-text)] underline underline-offset-4"
                  onClick={() => {
                    setForgotPassword(false);
                    setResetSent(false);
                    setError(null);
                    setStatus(null);
                  }}
                >
                  Back to sign in
                </button>
              </div>
            </div>
          </div>
        </section>
      </PublicPageShell>
    );
  }

  return (
    <PublicPageShell badge={otpLoginToken ? "Verification" : "Sign in"} centerContent>
      <section className="mx-auto w-full max-w-6xl px-4 py-10">
        <div className="flex justify-center">
          <div className="w-full max-w-md">
            <Card className="bg-white/70">
              <CardHeader>
                <CardTitle className="text-base">{otpLoginToken ? "Enter verification code" : "Sign in"}</CardTitle>
                <CardDescription>
                  {otpLoginToken
                    ? "For your security, enter the 6-digit code to complete sign-in."
                    : "Sign in to access your dashboard."}
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div className="space-y-4">
                  {error ? <Alert variant="danger" title="Sign in failed">{error}</Alert> : null}

                  <form className="space-y-4" onSubmit={onSubmit}>
                    <div className="space-y-1">
                      <label className="text-sm font-medium" htmlFor="email">
                        Email
                      </label>
                      <Input
                        id="email"
                        autoComplete="email"
                        value={email}
                        onChange={(e) => setEmail(e.target.value)}
                        type="email"
                        required
                        disabled={Boolean(otpLoginToken) || submitting}
                        placeholder="you@company.com"
                      />
                    </div>

                    {!otpLoginToken ? (
                      <div className="space-y-1">
                        <div className="flex items-center justify-between gap-3">
                          <label className="text-sm font-medium" htmlFor="password">
                            Password
                          </label>
                          <button
                            type="button"
                            className="text-xs font-medium text-zinc-600 hover:text-[var(--rb-text)]"
                            onClick={() => {
                              setForgotPassword(true);
                              setResetSent(false);
                              setError(null);
                              setStatus(null);
                            }}
                          >
                            Forgot password?
                          </button>
                        </div>

                        <div className="relative">
                          <Input
                            id="password"
                            autoComplete="current-password"
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            type={showPassword ? "text" : "password"}
                            required
                            disabled={submitting}
                          />
                          <button
                            type="button"
                            className="absolute right-2 top-1/2 -translate-y-1/2 rounded-[var(--rb-radius-sm)] px-2 py-1 text-zinc-500 hover:bg-[var(--rb-surface-muted)] hover:text-[var(--rb-text)]"
                            onClick={() => setShowPassword((v) => !v)}
                            aria-label={showPassword ? "Hide password" : "Show password"}
                          >
                            {showPassword ? <EyeOffIcon className="h-4 w-4" /> : <EyeIcon className="h-4 w-4" />}
                          </button>
                        </div>
                      </div>
                    ) : (
                      <div className="space-y-2">
                        <div className="space-y-1">
                          <label className="text-sm font-medium" htmlFor="otp">
                            Verification code
                          </label>
                          <Input
                            id="otp"
                            value={otpCode}
                            onChange={(e) => setOtpCode(e.target.value.replace(/\D/g, "").slice(0, 6))}
                            type="text"
                            inputMode="numeric"
                            pattern="[0-9]{6}"
                            maxLength={6}
                            placeholder="6-digit code"
                            autoComplete="one-time-code"
                            required
                            disabled={submitting}
                          />
                        </div>

                        <div className="flex items-center justify-between gap-3">
                          <div className="text-xs text-zinc-600">Didn’t mean to use this account?</div>
                          <button
                            type="button"
                            className="text-xs font-medium text-[var(--rb-text)] underline underline-offset-4"
                            onClick={() => {
                              setOtpLoginToken(null);
                              setOtpCode("");
                              setPassword("");
                              setError(null);
                              setStatus(null);
                            }}
                            disabled={submitting}
                          >
                            Use different email
                          </button>
                        </div>
                      </div>
                    )}

                    <Button className="w-full" type="submit" disabled={submitting}>
                      {submitting ? "Signing in..." : otpLoginToken ? "Verify code" : "Sign in"}
                    </Button>
                  </form>
                </div>
              </CardContent>
            </Card>

            <div className="mt-5 text-center text-sm text-zinc-600">
              Don’t have an account?{" "}
              <Link className="font-medium text-[var(--rb-text)] underline underline-offset-4" href="/register">
                Create one
              </Link>
            </div>
          </div>
        </div>
      </section>
    </PublicPageShell>
  );
}

export default function LoginPage() {
  return (
    <Suspense
      fallback={
        <Preloader />
      }
    >
      <LoginPageInner />
    </Suspense>
  );
}
