"use client";

import { useAuth } from "@/lib/auth";
import { ApiError } from "@/lib/api";
import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import React, { Suspense, useMemo, useState } from "react";

function LoginPageInner() {
  const auth = useAuth();
  const router = useRouter();
  const searchParams = useSearchParams();

  const next = useMemo(() => searchParams.get("next"), [searchParams]);

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
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
        await auth.loginOtp(otpLoginToken, otpCode);
        router.replace(next || "/");
        return;
      }

      const res = await auth.login(email, password);

      if (res.status === "ok") {
        router.replace(next || "/");
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
    setSubmitting(true);

    try {
      await apiFetch<{ message: string }>("/api/auth/password/email", {
        method: "POST",
        body: { email },
      });
      setResetSent(true);
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
      <div className="min-h-screen bg-zinc-50 flex items-center justify-center px-4">
        <div className="w-full max-w-md rounded-xl border bg-white p-6 shadow-sm">
          <h1 className="text-lg font-semibold">Forgot Password</h1>
          <p className="mt-1 text-sm text-zinc-500">
            {resetSent
              ? "Check your email for a link to reset your password."
              : "Enter your email address and we'll send you a link to reset your password."}
          </p>

          {error ? <div className="mt-4 text-sm text-red-600">{error}</div> : null}

          {!resetSent ? (
            <form className="mt-6 space-y-4" onSubmit={onForgotPassword}>
              <div className="space-y-1">
                <label className="text-sm font-medium" htmlFor="reset_email">
                  Email
                </label>
                <input
                  className="w-full rounded-md border px-3 py-2 text-sm"
                  id="reset_email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  type="email"
                  required
                />
              </div>

              <button
                className="w-full rounded-md bg-zinc-900 px-3 py-2 text-sm font-medium text-white hover:bg-zinc-800 disabled:opacity-50"
                type="submit"
                disabled={submitting}
              >
                {submitting ? "Sending..." : "Send Reset Link"}
              </button>
            </form>
          ) : null}

          <div className="mt-4 text-center">
            <button
              className="text-sm text-zinc-600 hover:text-zinc-900 underline"
              onClick={() => {
                setForgotPassword(false);
                setResetSent(false);
                setError(null);
              }}
            >
              Back to login
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-zinc-50 flex items-center justify-center px-4">
      <div className="w-full max-w-md rounded-xl border bg-white p-6">
        <h1 className="text-lg font-semibold">Login</h1>
        <p className="mt-1 text-sm text-zinc-500">Sign in to access your dashboard.</p>

        {error ? <div className="mt-4 text-sm text-red-600">{error}</div> : null}

        <form className="mt-6 space-y-4" onSubmit={onSubmit}>
          <div className="space-y-1">
            <label className="text-sm font-medium" htmlFor="email">
              Email
            </label>
            <input
              className="w-full rounded-md border px-3 py-2 text-sm"
              id="email"
              autoComplete="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              type="email"
              required
              disabled={Boolean(otpLoginToken)}
            />
          </div>

          {!otpLoginToken ? (
            <div className="space-y-1">
              <div className="flex items-center justify-between">
                <label className="text-sm font-medium" htmlFor="password">
                  Password
                </label>
                <button
                  type="button"
                  className="text-xs text-zinc-500 hover:text-zinc-900"
                  onClick={() => setForgotPassword(true)}
                >
                  Forgot password?
                </button>
              </div>
              <input
                className="w-full rounded-md border px-3 py-2 text-sm"
                id="password"
                autoComplete="current-password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                type="password"
                required
              />
            </div>
          ) : (
            <div className="space-y-1">
              <label className="text-sm font-medium" htmlFor="otp">
                OTP code
              </label>
              <input
                className="w-full rounded-md border px-3 py-2 text-sm"
                id="otp"
                value={otpCode}
                onChange={(e) => setOtpCode(e.target.value)}
                type="text"
                inputMode="numeric"
                pattern="\\d{6}"
                placeholder="6-digit code"
                required
              />
            </div>
          )}

          <button
            className="w-full rounded-md bg-zinc-900 px-3 py-2 text-sm font-medium text-white hover:bg-zinc-800 disabled:opacity-50"
            type="submit"
            disabled={submitting}
          >
            {submitting ? "Signing in..." : otpLoginToken ? "Verify code" : "Sign in"}
          </button>
        </form>

        <div className="mt-4 text-sm text-zinc-600">
          Don’t have an account?{" "}
          <Link className="text-zinc-900 underline" href="/register">
            Register
          </Link>
        </div>
      </div>
    </div>
  );
}

export default function LoginPage() {
  return (
    <Suspense
      fallback={
        <div className="min-h-screen flex items-center justify-center text-sm text-zinc-500">Loading...</div>
      }
    >
      <LoginPageInner />
    </Suspense>
  );
}
