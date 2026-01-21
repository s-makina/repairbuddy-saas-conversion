"use client";

import QRCode from "react-qr-code";
import React, { useEffect, useMemo, useState } from "react";
import { apiFetch, ApiError } from "@/lib/api";
import { useAuth } from "@/lib/auth";
import { Alert } from "@/components/ui/Alert";
import { Button } from "@/components/ui/Button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/Card";
import { Input } from "@/components/ui/Input";
import { PageHeader } from "@/components/ui/PageHeader";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/Tabs";

type AdminSettingsPayload = {
  app: {
    name: string;
    env: string;
    url: string;
    debug: boolean;
  };
  tenancy: {
    resolution: string;
    route_param: string;
    header: string;
  };
  billing: {
    seller_country: string;
  };
  mail: {
    default: string;
    from_address: string;
    from_name: string;
  };
};

type OtpSetupPayload = {
  secret: string;
  otpauth_uri: string;
};

export default function AdminSettingsPage() {
  const auth = useAuth();

  const [loading, setLoading] = useState(true);
  const [settings, setSettings] = useState<AdminSettingsPayload | null>(null);
  const [error, setError] = useState<string | null>(null);

  const [otpSetup, setOtpSetup] = useState<OtpSetupPayload | null>(null);
  const [otpCode, setOtpCode] = useState("");
  const [disablePassword, setDisablePassword] = useState("");
  const [disableCode, setDisableCode] = useState("");
  const [busy, setBusy] = useState(false);
  const [status, setStatus] = useState<string | null>(null);

  const otpEnabled = Boolean(auth.user?.otp_enabled && auth.user?.otp_confirmed_at);

  useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setError(null);
        setLoading(true);
        const res = await apiFetch<AdminSettingsPayload>("/api/admin/settings");
        if (!alive) return;
        setSettings(res);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load settings.");
        setSettings(null);
      } finally {
        if (!alive) return;
        setLoading(false);
      }
    }

    void load();

    return () => {
      alive = false;
    };
  }, []);

  useEffect(() => {
    setOtpSetup(null);
    setOtpCode("");
    setDisablePassword("");
    setDisableCode("");
    setStatus(null);
    setError(null);
  }, [otpEnabled]);

  const otpauthUriForLink = useMemo(() => {
    if (!otpSetup?.otpauth_uri) return null;
    return otpSetup.otpauth_uri;
  }, [otpSetup?.otpauth_uri]);

  async function onStartOtpSetup() {
    setError(null);
    setStatus(null);
    setBusy(true);

    try {
      const res = await apiFetch<OtpSetupPayload>("/api/auth/otp/setup", {
        method: "POST",
      });
      setOtpSetup(res);
      setStatus("OTP setup started. Scan the QR code and confirm the 6-digit code.");
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

  async function onConfirmOtp(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setStatus(null);
    setBusy(true);

    try {
      await apiFetch<{ status: "ok" }>("/api/auth/otp/confirm", {
        method: "POST",
        body: { code: otpCode },
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

  async function onDisableOtp(e: React.FormEvent) {
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
      <PageHeader title="Settings" description="Global admin settings and platform configuration." />

      {error ? (
        <Alert variant="danger" title="Something went wrong">
          {error}
        </Alert>
      ) : null}

      {status ? (
        <Alert variant="success" title="Status">
          {status}
        </Alert>
      ) : null}

      {loading ? <div className="text-sm text-zinc-500">Loading settings...</div> : null}

      {settings ? (
        <Tabs defaultValue="platform">
          <TabsList>
            <TabsTrigger value="platform">Platform</TabsTrigger>
            <TabsTrigger value="security">Security</TabsTrigger>
          </TabsList>

          <TabsContent value="platform">
            <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
              <Card>
                <CardHeader>
                  <CardTitle>Platform</CardTitle>
                  <CardDescription>Read-only config snapshot.</CardDescription>
                </CardHeader>
                <CardContent className="space-y-3">
                  <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                      <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">App</div>
                      <div className="mt-1 text-sm text-[var(--rb-text)]">{settings.app.name}</div>
                    </div>
                    <div>
                      <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Environment</div>
                      <div className="mt-1 text-sm text-[var(--rb-text)]">{settings.app.env}</div>
                    </div>
                    <div>
                      <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">URL</div>
                      <div className="mt-1 text-sm text-[var(--rb-text)] break-all">{settings.app.url}</div>
                    </div>
                    <div>
                      <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Debug</div>
                      <div className="mt-1 text-sm text-[var(--rb-text)]">{settings.app.debug ? "true" : "false"}</div>
                    </div>
                  </div>

                  <div className="h-px bg-[var(--rb-border)]" />

                  <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                      <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Tenancy resolution</div>
                      <div className="mt-1 text-sm text-[var(--rb-text)]">{settings.tenancy.resolution}</div>
                    </div>
                    <div>
                      <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Business route param</div>
                      <div className="mt-1 text-sm text-[var(--rb-text)]">{settings.tenancy.route_param}</div>
                    </div>
                    <div>
                      <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Business header</div>
                      <div className="mt-1 text-sm text-[var(--rb-text)]">{settings.tenancy.header}</div>
                    </div>
                    <div>
                      <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Billing seller country</div>
                      <div className="mt-1 text-sm text-[var(--rb-text)]">{settings.billing.seller_country}</div>
                    </div>
                  </div>

                  <div className="h-px bg-[var(--rb-border)]" />

                  <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                      <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Mail driver</div>
                      <div className="mt-1 text-sm text-[var(--rb-text)]">{settings.mail.default}</div>
                    </div>
                    <div>
                      <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Mail from</div>
                      <div className="mt-1 text-sm text-[var(--rb-text)] break-all">
                        {settings.mail.from_name} {settings.mail.from_address ? `<${settings.mail.from_address}>` : ""}
                      </div>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>
          </TabsContent>

          <TabsContent value="security">
            <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
              <Card>
                <CardHeader>
                  <CardTitle>Account security</CardTitle>
                  <CardDescription>One-time password (TOTP) setup for this admin account.</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="text-sm text-zinc-600">Status: {otpEnabled ? "Enabled" : "Disabled"}</div>

              {!otpEnabled ? (
                <div className="space-y-4">
                  {!otpSetup ? (
                    <Button type="button" onClick={() => void onStartOtpSetup()} disabled={busy}>
                      {busy ? "Starting..." : "Enable OTP"}
                    </Button>
                  ) : (
                    <div className="space-y-3">
                      {otpSetup.otpauth_uri ? (
                        <div className="rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white p-4">
                          <div className="text-xs font-semibold text-zinc-500">Scan QR code</div>
                          <div className="mt-3 flex items-center justify-center">
                            <div className="rounded-[12px] bg-white p-3 ring-1 ring-[var(--rb-border)]">
                              <QRCode value={otpSetup.otpauth_uri} size={176} />
                            </div>
                          </div>
                        </div>
                      ) : null}

                      <div className="rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-[var(--rb-surface-muted)] p-3">
                        <div className="text-xs font-semibold text-zinc-500">Secret</div>
                        <div className="mt-1 font-mono text-sm break-all">{otpSetup.secret}</div>
                      </div>

                      {otpauthUriForLink ? (
                        <a className="text-sm text-[var(--rb-text)] underline" href={otpauthUriForLink}>
                          Open in authenticator
                        </a>
                      ) : null}

                      <form className="space-y-2" onSubmit={onConfirmOtp}>
                        <div className="space-y-1">
                          <label className="text-sm font-medium" htmlFor="otp_code">
                            Enter 6-digit code
                          </label>
                          <Input
                            id="otp_code"
                            value={otpCode}
                            onChange={(e) => setOtpCode(e.target.value.replace(/\D/g, "").slice(0, 6))}
                            type="text"
                            inputMode="numeric"
                            pattern="[0-9]{6}"
                            maxLength={6}
                            required
                            disabled={busy}
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
                <form className="space-y-3" onSubmit={onDisableOtp}>
                  <div className="text-sm text-zinc-700">To disable OTP, confirm your password and an OTP code.</div>

                  <div className="space-y-1">
                    <label className="text-sm font-medium" htmlFor="disable_password">
                      Password
                    </label>
                    <Input
                      id="disable_password"
                      value={disablePassword}
                      onChange={(e) => setDisablePassword(e.target.value)}
                      type="password"
                      autoComplete="current-password"
                      required
                      disabled={busy}
                    />
                  </div>

                  <div className="space-y-1">
                    <label className="text-sm font-medium" htmlFor="disable_code">
                      OTP code
                    </label>
                    <Input
                      id="disable_code"
                      value={disableCode}
                      onChange={(e) => setDisableCode(e.target.value.replace(/\D/g, "").slice(0, 6))}
                      type="text"
                      inputMode="numeric"
                      pattern="[0-9]{6}"
                      maxLength={6}
                      required
                      disabled={busy}
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
          </TabsContent>
        </Tabs>
      ) : null}
    </div>
  );
}
