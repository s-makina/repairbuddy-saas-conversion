"use client";

import { apiFetch, ApiError } from "@/lib/api";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { useRouter, useSearchParams } from "next/navigation";
import React, { Suspense, useMemo, useState } from "react";
import Link from "next/link";

function ResetPasswordPageInner() {
    const router = useRouter();
    const searchParams = useSearchParams();

    const token = useMemo(() => searchParams.get("token"), [searchParams]);
    const email = useMemo(() => searchParams.get("email"), [searchParams]);
    const tenant = useMemo(() => searchParams.get("tenant"), [searchParams]);

    const [password, setPassword] = useState("");
    const [passwordConfirmation, setPasswordConfirmation] = useState("");
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
            <div className="min-h-screen bg-zinc-50 flex items-center justify-center px-4">
                <div className="w-full max-w-md rounded-xl border bg-white p-6 text-center">
                    <h1 className="text-lg font-semibold text-red-600">Invalid Link</h1>
                    <p className="mt-2 text-sm text-zinc-500">
                        This password reset link is invalid or has expired.
                    </p>
                    <div className="mt-6">
                        <Link href="/login" className="text-sm font-medium text-zinc-900 underline">
                            Return to login
                        </Link>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-zinc-50 flex items-center justify-center px-4">
            <div className="w-full max-w-md">
                <div className="rounded-xl border bg-white p-6 shadow-sm">
                    <h1 className="text-xl font-semibold">Reset Password</h1>
                    <p className="mt-1 text-sm text-zinc-500">Enter your new password below.</p>

                    {error ? (
                        <div className="mt-4 p-3 rounded-md bg-red-50 text-sm text-red-600 border border-red-100">
                            {error}
                        </div>
                    ) : null}

                    {status ? (
                        <div className="mt-4 p-3 rounded-md bg-green-50 text-sm text-green-700 border border-green-100">
                            {status}
                        </div>
                    ) : null}

                    <form className="mt-6 space-y-4" onSubmit={onSubmit}>
                        <div className="space-y-1">
                            <label className="text-sm font-medium" htmlFor="password">
                                New Password
                            </label>
                            <input
                                className="w-full rounded-md border px-3 py-2 text-sm"
                                id="password"
                                value={password}
                                onChange={(e) => setPassword(e.target.value)}
                                type="password"
                                required
                                disabled={submitting || !!status}
                                autoComplete="new-password"
                            />
                        </div>

                        <div className="space-y-1">
                            <label className="text-sm font-medium" htmlFor="password_confirmation">
                                Confirm New Password
                            </label>
                            <input
                                className="w-full rounded-md border px-3 py-2 text-sm"
                                id="password_confirmation"
                                value={passwordConfirmation}
                                onChange={(e) => setPasswordConfirmation(e.target.value)}
                                type="password"
                                required
                                disabled={submitting || !!status}
                                autoComplete="new-password"
                            />
                        </div>

                        <Button className="w-full" type="submit" disabled={submitting || !!status}>
                            {submitting ? "Resetting..." : "Reset Password"}
                        </Button>
                    </form>

                    {!status && (
                        <div className="mt-6 text-center">
                            <Link href="/login" className="text-sm text-zinc-600 hover:text-zinc-900">
                                Back to login
                            </Link>
                        </div>
                    )}
                </div>
            </div>
        </div>
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
