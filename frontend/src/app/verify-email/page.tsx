"use client";

import { ApiError } from "@/lib/api";
import { useAuth } from "@/lib/auth";
import { AuthLayout } from "@/components/auth/AuthLayout";
import { Alert } from "@/components/ui/Alert";
import { Button } from "@/components/ui/Button";
import { Input } from "@/components/ui/Input";
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
    <AuthLayout
      title="Verify your email"
      description={
        verified
          ? "Your email is verified. You can now sign in."
          : "We sent you a verification link. Click it to activate your account."
      }
      footer={
        <Link className="font-medium text-[var(--rb-text)] underline underline-offset-4" href="/login">
          Go to sign in
        </Link>
      }
    >
      <div className="space-y-4">
        {status ? <Alert variant="success" title="Status">{status}</Alert> : null}
        {error ? <Alert variant="danger" title="Unable to resend">{error}</Alert> : null}

        {!verified ? (
          <form className="space-y-4" onSubmit={onResend}>
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
                disabled={submitting}
                placeholder="you@company.com"
              />
            </div>

            <Button className="w-full" type="submit" disabled={submitting}>
              {submitting ? "Sending..." : "Resend verification email"}
            </Button>
          </form>
        ) : null}

        {auth.isAuthenticated ? (
          <div className="text-xs text-zinc-600">
            You are signed in. If verification just completed, refresh the page.
          </div>
        ) : null}
      </div>
    </AuthLayout>
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
