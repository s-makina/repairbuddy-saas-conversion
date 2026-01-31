"use client";

import { useAuth } from "@/lib/auth";
import { apiFetch, ApiError } from "@/lib/api";
import { PublicPageShell } from "@/components/PublicPageShell";
import { Alert } from "@/components/ui/Alert";
import { Button } from "@/components/ui/Button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/Card";
import { Input } from "@/components/ui/Input";
import { Preloader } from "@/components/Preloader";
import { useRouter, useSearchParams } from "next/navigation";
import React, { Suspense, useMemo, useState } from "react";

function SetPasswordPageInner() {
  const auth = useAuth();
  const router = useRouter();
  const searchParams = useSearchParams();

  const next = useMemo(() => searchParams.get("next"), [searchParams]);

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

    setSubmitting(true);

    try {
      await apiFetch<{ status: "ok" }>("/api/auth/password/change", {
        method: "POST",
        body: {
          password,
          password_confirmation: passwordConfirmation,
        },
      });

      await auth.refresh();

      setStatus("Password updated.");

      router.replace(next || "/app");
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError("Failed to update password.");
      }
    } finally {
      setSubmitting(false);
    }
  }

  if (auth.loading) {
    return <Preloader />;
  }

  if (!auth.isAuthenticated) {
    router.replace("/login");
    return null;
  }

  if (!auth.user?.must_change_password) {
    router.replace(next || "/app");
    return null;
  }

  return (
    <PublicPageShell badge="Set password" centerContent>
      <section className="mx-auto w-full max-w-6xl px-4 py-10">
        <div className="flex justify-center">
          <div className="w-full max-w-md">
            <Card className="bg-white/70">
              <CardHeader>
                <CardTitle className="text-base">Set a new password</CardTitle>
                <CardDescription>You must set a new password before continuing.</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="space-y-4">
                  {error ? <Alert variant="danger" title="Unable to update password">{error}</Alert> : null}
                  {status ? <Alert variant="success" title="Password updated">{status}</Alert> : null}

                  <form className="space-y-4" onSubmit={onSubmit}>
                    <div className="space-y-1">
                      <label className="text-sm font-medium" htmlFor="password">
                        New password
                      </label>
                      <Input
                        id="password"
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        type="password"
                        required
                        disabled={submitting}
                        autoComplete="new-password"
                      />
                    </div>

                    <div className="space-y-1">
                      <label className="text-sm font-medium" htmlFor="password_confirmation">
                        Confirm new password
                      </label>
                      <Input
                        id="password_confirmation"
                        value={passwordConfirmation}
                        onChange={(e) => setPasswordConfirmation(e.target.value)}
                        type="password"
                        required
                        disabled={submitting}
                        autoComplete="new-password"
                      />
                    </div>

                    <Button className="w-full" type="submit" disabled={submitting}>
                      {submitting ? "Updating..." : "Update password"}
                    </Button>
                  </form>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
      </section>
    </PublicPageShell>
  );
}

export default function SetPasswordPage() {
  return (
    <Suspense fallback={<Preloader />}>
      <SetPasswordPageInner />
    </Suspense>
  );
}
