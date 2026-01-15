"use client";

import { apiFetch, ApiError } from "@/lib/api";
import { useAuth } from "@/lib/auth";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { PageHeader } from "@/components/ui/PageHeader";
import React, { useEffect, useMemo, useState } from "react";

type OtpSetupPayload = {
  secret: string;
  otpauth_uri: string;
};

export default function SecurityPage() {
  const auth = useAuth();

  const otpEnabled = Boolean(auth.user?.otp_enabled && auth.user?.otp_confirmed_at);

  const [setup, setSetup] = useState<OtpSetupPayload | null>(null);
  const [code, setCode] = useState("");
  const [disablePassword, setDisablePassword] = useState("");
  const [disableCode, setDisableCode] = useState("");

  const [status, setStatus] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  const otpauthUriForLink = useMemo(() => {
    if (!setup?.otpauth_uri) return null;
    return setup.otpauth_uri;
  }, [setup?.otpauth_uri]);

  useEffect(() => {
    setSetup(null);
    setCode("");
    setDisablePassword("");
    setDisableCode("");
    setStatus(null);
    setError(null);
  }, [otpEnabled]);

  async function onStartSetup() {
    setError(null);
    setStatus(null);
    setBusy(true);

    try {
      const res = await apiFetch<OtpSetupPayload>("/api/auth/otp/setup", {
        method: "POST",
      });
      setSetup(res);
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError("Failed to start OTP setup.");
      }
    } finally {
      setBusy(false);
    }
  }

  async function onConfirm(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setStatus(null);
    setBusy(true);

    try {
      await apiFetch<{ status: "ok" }>("/api/auth/otp/confirm", {
        method: "POST",
        body: { code },
      });

      setStatus("OTP enabled.");
      await auth.refresh();
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError("Failed to confirm OTP.");
      }
    } finally {
      setBusy(false);
    }
  }

  async function onDisable(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setStatus(null);
    setBusy(true);

    try {
      await apiFetch<{ status: "ok" }>("/api/auth/otp/disable", {
        method: "POST",
        body: { password: disablePassword, code: disableCode },
      });

      setStatus("OTP disabled.");
      await auth.refresh();
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError("Failed to disable OTP.");
      }
    } finally {
      setBusy(false);
    }
  }

  return (
    <div className="space-y-6">
      <PageHeader title="Security" description="Manage multi-factor authentication (OTP)." />

      {status ? <div className="text-sm text-green-700">{status}</div> : null}
      {error ? <div className="text-sm text-red-600">{error}</div> : null}

      <Card className="shadow-none">
        <CardContent className="pt-5">
          <div className="text-sm font-semibold text-[var(--rb-text)]">One-time passwords (TOTP)</div>
          <div className="mt-1 text-sm text-zinc-600">Status: {otpEnabled ? "Enabled" : "Disabled"}</div>

          {!otpEnabled ? (
            <div className="mt-4 space-y-4">
              {!setup ? (
                <Button type="button" onClick={() => void onStartSetup()} disabled={busy}>
                  {busy ? "Starting..." : "Enable OTP"}
                </Button>
              ) : (
                <div className="space-y-3">
                  <div className="text-sm text-zinc-700">
                    Add this account in your authenticator app using the secret below.
                  </div>

                  <div className="rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-[var(--rb-surface-muted)] p-3">
                    <div className="text-xs font-semibold text-zinc-500">Secret</div>
                    <div className="mt-1 font-mono text-sm break-all">{setup.secret}</div>
                  </div>

                  {otpauthUriForLink ? (
                    <a className="text-sm text-[var(--rb-text)] underline" href={otpauthUriForLink}>
                      Open in authenticator
                    </a>
                  ) : null}

                  <form className="space-y-2" onSubmit={onConfirm}>
                    <div className="space-y-1">
                      <label className="text-sm font-medium" htmlFor="otp_code">
                        Enter 6-digit code
                      </label>
                      <input
                        className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                        id="otp_code"
                        value={code}
                        onChange={(e) => setCode(e.target.value)}
                        type="text"
                        inputMode="numeric"
                        pattern="\\d{6}"
                        required
                      />
                    </div>

                    <Button type="submit" disabled={busy}>
                      {busy ? "Confirming..." : "Confirm OTP"}
                    </Button>
                  </form>
                </div>
              )}
            </div>
          ) : (
            <form className="mt-4 space-y-3" onSubmit={onDisable}>
              <div className="text-sm text-zinc-700">To disable OTP, confirm your password and an OTP code.</div>

              <div className="space-y-1">
                <label className="text-sm font-medium" htmlFor="disable_password">
                  Password
                </label>
                <input
                  className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                  id="disable_password"
                  value={disablePassword}
                  onChange={(e) => setDisablePassword(e.target.value)}
                  type="password"
                  autoComplete="current-password"
                  required
                />
              </div>

              <div className="space-y-1">
                <label className="text-sm font-medium" htmlFor="disable_code">
                  OTP code
                </label>
                <input
                  className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                  id="disable_code"
                  value={disableCode}
                  onChange={(e) => setDisableCode(e.target.value)}
                  type="text"
                  inputMode="numeric"
                  pattern="\\d{6}"
                  required
                />
              </div>

              <Button variant="outline" type="submit" disabled={busy}>
                {busy ? "Disabling..." : "Disable OTP"}
              </Button>
            </form>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
