"use client";

import { ApiError } from "@/lib/api";
import { useAuth } from "@/lib/auth";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import React, { Suspense, useMemo, useState } from "react";

function VerifyEmailPageInner() {
  const auth = useAuth();
  const searchParams = useSearchParams();

  const initialEmail = useMemo(() => searchParams.get("email") || "", [searchParams]);
  const verified = useMemo(() => searchParams.get("verified") === "1", [searchParams]);

  const [email, setEmail] = useState(initialEmail);
  const [status, setStatus] = useState<string | null>(verified ? "Email verified. You can now sign in." : null);
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function onResend(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setStatus(null);
    setSubmitting(true);

    try {
      await auth.resendVerificationEmail(email);
      setStatus("Verification email sent. Check your inbox.");
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError("Failed to resend verification email.");
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="min-h-screen bg-zinc-50 flex items-center justify-center px-4">
      <div className="w-full max-w-md rounded-xl border bg-white p-6">
        <h1 className="text-lg font-semibold">Verify your email</h1>
        <p className="mt-1 text-sm text-zinc-500">
          We sent you a verification link. Click it to activate your account.
        </p>

        {status ? <div className="mt-4 text-sm text-green-700">{status}</div> : null}
        {error ? <div className="mt-4 text-sm text-red-600">{error}</div> : null}

        <form className="mt-6 space-y-4" onSubmit={onResend}>
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
            />
          </div>

          <button
            className="w-full rounded-md bg-zinc-900 px-3 py-2 text-sm font-medium text-white hover:bg-zinc-800 disabled:opacity-50"
            type="submit"
            disabled={submitting}
          >
            {submitting ? "Sending..." : "Resend verification email"}
          </button>
        </form>

        <div className="mt-4 text-sm text-zinc-600">
          <Link className="text-zinc-900 underline" href="/login">
            Go to login
          </Link>
        </div>

        {auth.isAuthenticated ? (
          <div className="mt-2 text-xs text-zinc-500">
            You are signed in. If verification is complete, refresh the page.
          </div>
        ) : null}
      </div>
    </div>
  );
}

export default function VerifyEmailPage() {
  return (
    <Suspense
      fallback={
        <div className="min-h-screen flex items-center justify-center text-sm text-zinc-500">Loading...</div>
      }
    >
      <VerifyEmailPageInner />
    </Suspense>
  );
}
