"use client";

import { ApiError } from "@/lib/api";
import { useAuth } from "@/lib/auth";
import { PublicPageShell } from "@/components/PublicPageShell";
import { Alert } from "@/components/ui/Alert";
import { Button } from "@/components/ui/Button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/Card";
import { Input } from "@/components/ui/Input";
import { Preloader } from "@/components/Preloader";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import React, { Suspense, useMemo, useState } from "react";

function VerifyEmailPageInner() {
  const auth = useAuth();
  const searchParams = useSearchParams();

  const initialEmail = useMemo(() => searchParams.get("email") || "", [searchParams]);
  const verified = useMemo(() => searchParams.get("verified") === "1", [searchParams]);

  const [email, setEmail] = useState(initialEmail);
  const [verificationEmailSent, setVerificationEmailSent] = useState(!verified && Boolean(initialEmail));
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
      setVerificationEmailSent(true);
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
    <PublicPageShell badge="Verify email" centerContent>
      <section className="mx-auto w-full max-w-6xl px-4 py-10">
        <div className="flex justify-center">
          <div className="w-full max-w-md">
            <Card className="bg-white/70">
              <CardHeader>
                <CardTitle className="text-base">
                  <div className="flex items-center gap-2">
                    <span>Verify your email</span>
                    {verificationEmailSent ? (
                      <span className="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                        <svg
                          viewBox="0 0 24 24"
                          className="h-3.5 w-3.5"
                          fill="none"
                          stroke="currentColor"
                          strokeWidth={2}
                          strokeLinecap="round"
                          strokeLinejoin="round"
                          aria-hidden="true"
                        >
                          <path d="M20 6L9 17l-5-5" />
                        </svg>
                      </span>
                    ) : null}
                  </div>
                </CardTitle>
                <CardDescription>
                  {verified
                    ? "Your email is verified. You can now sign in."
                    : "We sent you a verification link. Click it to activate your account."}
                </CardDescription>
              </CardHeader>
              <CardContent>
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
              </CardContent>
            </Card>

            <div className="mt-5 text-center text-sm text-zinc-600">
              <Link className="font-medium text-[var(--rb-text)] underline underline-offset-4" href="/login">
                Go to sign in
              </Link>
            </div>
          </div>
        </div>
      </section>
    </PublicPageShell>
  );
}

export default function VerifyEmailPage() {
  return (
    <Suspense
      fallback={
        <Preloader />
      }
    >
      <VerifyEmailPageInner />
    </Suspense>
  );
}
