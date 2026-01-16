"use client";

import { apiFetch, ApiError } from "@/lib/api";
import { AuthLayout } from "@/components/auth/AuthLayout";
import { Alert } from "@/components/ui/Alert";
import { Button } from "@/components/ui/Button";
import { Input } from "@/components/ui/Input";
import { useRouter, useSearchParams } from "next/navigation";
import React, { Suspense, useMemo, useState } from "react";
import Link from "next/link";

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

function ResetPasswordPageInner() {
    const router = useRouter();
    const searchParams = useSearchParams();

    const token = useMemo(() => searchParams.get("token"), [searchParams]);
    const email = useMemo(() => searchParams.get("email"), [searchParams]);
    void useMemo(() => searchParams.get("tenant"), [searchParams]);

    const [password, setPassword] = useState("");
    const [passwordConfirmation, setPasswordConfirmation] = useState("");
    const [showPassword, setShowPassword] = useState(false);
    const [showPasswordConfirmation, setShowPasswordConfirmation] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [status, setStatus] = useState<string | null>(null);
    const [submitting, setSubmitting] = useState(false);

    async function onSubmit(e: React.FormEvent) {
        e.preventDefault();
        setError(null);
        setStatus(null);

        if (password !== passwordConfirmation) {
            setError("Passwords do not match.");
            return;
        }

        if (!token || !email) {
            setError("Invalid reset link. Please request a new one.");
            return;
        }

        setSubmitting(true);

        try {
            await apiFetch<{ message: string }>("/api/auth/password/reset", {
                method: "POST",
                body: {
                    token,
                    email,
                    password,
                    password_confirmation: passwordConfirmation,
                },
            });

            setStatus("Your password has been reset successfully.");
            setTimeout(() => {
                router.push("/login");
            }, 3000);
        } catch (err) {
            if (err instanceof ApiError) {
                setError(err.message);
            } else {
                setError("Failed to reset password.");
            }
        } finally {
            setSubmitting(false);
        }
    }

    if (!token || !email) {
        return (
          <AuthLayout
            title="Invalid reset link"
            description="This password reset link is invalid or has expired."
            footer={
              <Link className="font-medium text-[var(--rb-text)] underline underline-offset-4" href="/login">
                Return to sign in
              </Link>
            }
          >
            <Alert variant="danger" title="Unable to reset password">
              Please request a new reset link from the sign-in page.
            </Alert>
          </AuthLayout>
        );
    }

    return (
      <AuthLayout
        title="Set a new password"
        description="Choose a strong password you don’t use elsewhere."
        footer={
          !status ? (
            <Link className="font-medium text-[var(--rb-text)] underline underline-offset-4" href="/login">
              Back to sign in
            </Link>
          ) : null
        }
      >
        <div className="space-y-4">
          {error ? <Alert variant="danger" title="Reset failed">{error}</Alert> : null}
          {status ? (
            <Alert variant="success" title="Password updated">
              {status}
            </Alert>
          ) : null}

          <form className="space-y-4" onSubmit={onSubmit}>
            <div className="space-y-1">
              <label className="text-sm font-medium" htmlFor="password">
                New password
              </label>
              <div className="relative">
                <Input
                  id="password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  type={showPassword ? "text" : "password"}
                  required
                  disabled={submitting || !!status}
                  autoComplete="new-password"
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

            <div className="space-y-1">
              <label className="text-sm font-medium" htmlFor="password_confirmation">
                Confirm new password
              </label>
              <div className="relative">
                <Input
                  id="password_confirmation"
                  value={passwordConfirmation}
                  onChange={(e) => setPasswordConfirmation(e.target.value)}
                  type={showPasswordConfirmation ? "text" : "password"}
                  required
                  disabled={submitting || !!status}
                  autoComplete="new-password"
                />
                <button
                  type="button"
                  className="absolute right-2 top-1/2 -translate-y-1/2 rounded-[var(--rb-radius-sm)] px-2 py-1 text-zinc-500 hover:bg-[var(--rb-surface-muted)] hover:text-[var(--rb-text)]"
                  onClick={() => setShowPasswordConfirmation((v) => !v)}
                  aria-label={showPasswordConfirmation ? "Hide password" : "Show password"}
                >
                  {showPasswordConfirmation ? <EyeOffIcon className="h-4 w-4" /> : <EyeIcon className="h-4 w-4" />}
                </button>
              </div>
            </div>

            <Button className="w-full" type="submit" disabled={submitting || !!status}>
              {submitting ? "Updating..." : "Update password"}
            </Button>
          </form>
        </div>
      </AuthLayout>
    );
}

export default function ResetPasswordPage() {
    return (
        <Suspense
            fallback={
                <div className="min-h-screen flex items-center justify-center text-sm text-zinc-500">
                    Loading...
                </div>
            }
        >
            <ResetPasswordPageInner />
        </Suspense>
    );
}
