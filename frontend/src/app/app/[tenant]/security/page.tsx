"use client";

import { apiFetch, ApiError } from "@/lib/api";
import { useAuth } from "@/lib/auth";
import { Button } from "@/components/ui/Button";
import { Badge } from "@/components/ui/Badge";
import { Card, CardContent } from "@/components/ui/Card";
import { PageHeader } from "@/components/ui/PageHeader";
import QRCode from "react-qr-code";
import React, { useEffect, useMemo, useState } from "react";
import { useParams } from "next/navigation";

type OtpSetupPayload = {
  secret: string;
  otpauth_uri: string;
};

type SecuritySettingsPayload = {
  settings: {
    mfa_required_roles: number[];
    mfa_grace_period_days: number;
    mfa_enforce_after: string | null;
    session_idle_timeout_minutes: number;
    session_max_lifetime_days: number;
    lockout_max_attempts: number;
    lockout_duration_minutes: number;
  };
};

type CompliancePayload = {
  mfa: {
    enforce_after: string | null;
    total_in_scope: number;
    compliant: number;
    non_compliant: number;
    non_compliant_users: Array<{
      id: number;
      name: string;
      email: string;
      role_id?: number | null;
      otp_enabled?: boolean;
      otp_confirmed_at?: string | null;
      role_model?: { id: number; name: string } | null;
      roleModel?: { id: number; name: string } | null;
    }>;
  };
};

type AuditEvent = {
  source: "auth" | "platform";
  id: number;
  type: string;
  user_id: number | null;
  actor_user_id: number | null;
  email?: string | null;
  created_at: string | null;
  ip?: string | null;
  user_agent?: string | null;
  metadata?: unknown;
  reason?: string | null;
};

type AuditEventsPayload = {
  events: AuditEvent[];
  meta?: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
};

export default function SecurityPage() {
  const auth = useAuth();
  const params = useParams() as { tenant?: string; business?: string };
  const tenant = params.business ?? params.tenant;

  const otpEnabled = Boolean(auth.user?.otp_enabled && auth.user?.otp_confirmed_at);

  const canManage = auth.can("security.manage");

  const [settings, setSettings] = useState<SecuritySettingsPayload["settings"] | null>(null);
  const [settingsLoading, setSettingsLoading] = useState(false);
  const [settingsError, setSettingsError] = useState<string | null>(null);

  const [mfaRolesRaw, setMfaRolesRaw] = useState<string>("");
  const [mfaGraceDays, setMfaGraceDays] = useState<number>(7);
  const [idleMinutes, setIdleMinutes] = useState<number>(60);
  const [maxLifetimeDays, setMaxLifetimeDays] = useState<number>(30);
  const [lockoutAttempts, setLockoutAttempts] = useState<number>(10);
  const [lockoutMinutes, setLockoutMinutes] = useState<number>(15);

  const [compliance, setCompliance] = useState<CompliancePayload["mfa"] | null>(null);
  const [complianceLoading, setComplianceLoading] = useState(false);
  const [complianceError, setComplianceError] = useState<string | null>(null);

  const [auditEvents, setAuditEvents] = useState<AuditEvent[]>([]);
  const [auditLoading, setAuditLoading] = useState(false);
  const [auditError, setAuditError] = useState<string | null>(null);
  const [auditType, setAuditType] = useState<string>("");
  const [auditPage, setAuditPage] = useState<number>(1);
  const [auditTotalPages, setAuditTotalPages] = useState<number>(1);

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

  useEffect(() => {
    if (!canManage) return;
    if (typeof tenant !== "string" || tenant.length === 0) return;

    setSettingsError(null);
    setSettingsLoading(true);

    void apiFetch<SecuritySettingsPayload>(`/api/${tenant}/app/security-settings`)
      .then((res) => {
        setSettings(res.settings);
        setMfaRolesRaw((Array.isArray(res.settings.mfa_required_roles) ? res.settings.mfa_required_roles : []).join(","));
        setMfaGraceDays(res.settings.mfa_grace_period_days ?? 7);
        setIdleMinutes(res.settings.session_idle_timeout_minutes ?? 60);
        setMaxLifetimeDays(res.settings.session_max_lifetime_days ?? 30);
        setLockoutAttempts(res.settings.lockout_max_attempts ?? 10);
        setLockoutMinutes(res.settings.lockout_duration_minutes ?? 15);
      })
      .catch((err) => {
        setSettingsError(err instanceof Error ? err.message : "Failed to load security settings.");
      })
      .finally(() => setSettingsLoading(false));
  }, [canManage, tenant]);

  useEffect(() => {
    if (!canManage) return;
    if (typeof tenant !== "string" || tenant.length === 0) return;

    setComplianceError(null);
    setComplianceLoading(true);

    void apiFetch<CompliancePayload>(`/api/${tenant}/app/security/compliance`)
      .then((res) => setCompliance(res.mfa))
      .catch((err) => setComplianceError(err instanceof Error ? err.message : "Failed to load compliance."))
      .finally(() => setComplianceLoading(false));
  }, [canManage, tenant, otpEnabled]);

  useEffect(() => {
    if (!canManage) return;
    if (typeof tenant !== "string" || tenant.length === 0) return;

    setAuditError(null);
    setAuditLoading(true);

    const qs = new URLSearchParams();
    if (auditType.trim().length > 0) qs.set("type", auditType.trim());
    qs.set("page", String(auditPage));
    qs.set("per_page", "25");

    void apiFetch<AuditEventsPayload>(`/api/${tenant}/app/audit-events?${qs.toString()}`)
      .then((res) => {
        setAuditEvents(Array.isArray(res.events) ? res.events : []);
        setAuditTotalPages(typeof res.meta?.last_page === "number" ? res.meta.last_page : 1);
      })
      .catch((err) => setAuditError(err instanceof Error ? err.message : "Failed to load audit events."))
      .finally(() => setAuditLoading(false));
  }, [canManage, tenant, auditType, auditPage]);

  async function onSavePolicies(e: React.FormEvent) {
    e.preventDefault();
    if (!canManage) return;
    if (typeof tenant !== "string" || tenant.length === 0) return;

    setSettingsError(null);
    setSettingsLoading(true);

    const roleIds = mfaRolesRaw
      .split(",")
      .map((x) => x.trim())
      .filter(Boolean)
      .map((x) => Number(x))
      .filter((x) => Number.isFinite(x) && x > 0);

    try {
      const res = await apiFetch<SecuritySettingsPayload>(`/api/${tenant}/app/security-settings`, {
        method: "PUT",
        body: {
          mfa_required_roles: roleIds,
          mfa_grace_period_days: mfaGraceDays,
          session_idle_timeout_minutes: idleMinutes,
          session_max_lifetime_days: maxLifetimeDays,
          lockout_max_attempts: lockoutAttempts,
          lockout_duration_minutes: lockoutMinutes,
        },
      });

      setSettings(res.settings);
    } catch (err) {
      if (err instanceof ApiError) {
        setSettingsError(err.message);
      } else {
        setSettingsError("Failed to save security settings.");
      }
    } finally {
      setSettingsLoading(false);
    }
  }

  async function onForceLogout() {
    if (!canManage) return;
    if (typeof tenant !== "string" || tenant.length === 0) return;

    setSettingsError(null);
    setSettingsLoading(true);
    try {
      await apiFetch<{ status: "ok" }>(`/api/${tenant}/app/security/force-logout`, {
        method: "POST",
      });
    } catch (err) {
      if (err instanceof ApiError) {
        setSettingsError(err.message);
      } else {
        setSettingsError("Failed to force logout.");
      }
    } finally {
      setSettingsLoading(false);
    }
  }

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

                  {setup.otpauth_uri ? (
                    <div className="rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white p-4">
                      <div className="text-xs font-semibold text-zinc-500">Scan QR code</div>
                      <div className="mt-3 flex items-center justify-center">
                        <div className="rounded-[12px] bg-white p-3 ring-1 ring-[var(--rb-border)]">
                          <QRCode value={setup.otpauth_uri} size={176} />
                        </div>
                      </div>
                      <div className="mt-3 text-xs text-zinc-600">
                        If you can’t scan, you can still add it manually using the secret below.
                      </div>
                    </div>
                  ) : null}

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
                        onChange={(e) => setCode(e.target.value.replace(/\D/g, "").slice(0, 6))}
                        type="text"
                        inputMode="numeric"
                        pattern="[0-9]{6}"
                        maxLength={6}
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
                  onChange={(e) => setDisableCode(e.target.value.replace(/\D/g, "").slice(0, 6))}
                  type="text"
                  inputMode="numeric"
                  pattern="[0-9]{6}"
                  maxLength={6}
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

      {canManage ? (
        <Card className="shadow-none">
          <CardContent className="pt-5">
            <div className="text-sm font-semibold text-[var(--rb-text)]">Tenant security policies</div>
            <div className="mt-1 text-sm text-zinc-600">
              Configure enforcement rules for all users in this business.
            </div>

            {settingsError ? <div className="mt-3 text-sm text-red-600">{settingsError}</div> : null}

            <form className="mt-4 grid gap-3" onSubmit={onSavePolicies}>
              <div className="space-y-1">
                <label className="text-sm font-medium" htmlFor="mfa_roles">
                  MFA required roles (role IDs, comma-separated)
                </label>
                <input
                  id="mfa_roles"
                  className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                  value={mfaRolesRaw}
                  onChange={(e) => setMfaRolesRaw(e.target.value)}
                  placeholder="e.g. 1,2,3"
                  disabled={settingsLoading}
                />
                {settings?.mfa_enforce_after ? (
                  <div className="text-xs text-zinc-600">Enforce after: {settings.mfa_enforce_after}</div>
                ) : null}
              </div>

              <div className="grid gap-3 md:grid-cols-2">
                <div className="space-y-1">
                  <label className="text-sm font-medium" htmlFor="mfa_grace">
                    MFA grace period (days)
                  </label>
                  <input
                    id="mfa_grace"
                    className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                    value={String(mfaGraceDays)}
                    onChange={(e) => setMfaGraceDays(Number(e.target.value))}
                    type="number"
                    min={0}
                    max={365}
                    disabled={settingsLoading}
                  />
                </div>

                <div className="space-y-1">
                  <label className="text-sm font-medium" htmlFor="idle_timeout">
                    Session idle timeout (minutes)
                  </label>
                  <input
                    id="idle_timeout"
                    className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                    value={String(idleMinutes)}
                    onChange={(e) => setIdleMinutes(Number(e.target.value))}
                    type="number"
                    min={5}
                    max={1440}
                    disabled={settingsLoading}
                  />
                </div>

                <div className="space-y-1">
                  <label className="text-sm font-medium" htmlFor="max_life">
                    Session max lifetime (days)
                  </label>
                  <input
                    id="max_life"
                    className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                    value={String(maxLifetimeDays)}
                    onChange={(e) => setMaxLifetimeDays(Number(e.target.value))}
                    type="number"
                    min={1}
                    max={365}
                    disabled={settingsLoading}
                  />
                </div>

                <div className="space-y-1">
                  <label className="text-sm font-medium" htmlFor="lockout_attempts">
                    Lockout max attempts
                  </label>
                  <input
                    id="lockout_attempts"
                    className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                    value={String(lockoutAttempts)}
                    onChange={(e) => setLockoutAttempts(Number(e.target.value))}
                    type="number"
                    min={1}
                    max={100}
                    disabled={settingsLoading}
                  />
                </div>

                <div className="space-y-1">
                  <label className="text-sm font-medium" htmlFor="lockout_minutes">
                    Lockout duration (minutes)
                  </label>
                  <input
                    id="lockout_minutes"
                    className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                    value={String(lockoutMinutes)}
                    onChange={(e) => setLockoutMinutes(Number(e.target.value))}
                    type="number"
                    min={1}
                    max={1440}
                    disabled={settingsLoading}
                  />
                </div>
              </div>

              <div className="flex flex-wrap items-center gap-2">
                <Button type="submit" disabled={settingsLoading || settingsLoading}>
                  {settingsLoading ? "Saving..." : "Save policies"}
                </Button>
                <Button variant="outline" type="button" onClick={() => void onForceLogout()} disabled={settingsLoading}>
                  Force logout all users
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      ) : null}

      {canManage ? (
        <Card className="shadow-none">
          <CardContent className="pt-5">
            <div className="text-sm font-semibold text-[var(--rb-text)]">MFA compliance</div>
            <div className="mt-1 text-sm text-zinc-600">Users in required roles who have not confirmed OTP.</div>

            {complianceError ? <div className="mt-3 text-sm text-red-600">{complianceError}</div> : null}

            <div className="mt-3 flex flex-wrap items-center gap-2 text-sm">
              <Badge variant="default">In scope: {complianceLoading ? "…" : compliance?.total_in_scope ?? 0}</Badge>
              <Badge variant="success">Compliant: {complianceLoading ? "…" : compliance?.compliant ?? 0}</Badge>
              <Badge variant="danger">Non-compliant: {complianceLoading ? "…" : compliance?.non_compliant ?? 0}</Badge>
            </div>

            <div className="mt-4 overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="text-left text-xs text-zinc-500">
                    <th className="py-2 pr-3">User</th>
                    <th className="py-2 pr-3">Role</th>
                    <th className="py-2 pr-3">OTP</th>
                  </tr>
                </thead>
                <tbody>
                  {(Array.isArray(compliance?.non_compliant_users) ? compliance?.non_compliant_users : []).map((u) => {
                    const roleName = u.roleModel?.name ?? (u as unknown as { role_model?: { name?: string } }).role_model?.name ?? "(none)";
                    const compliant = Boolean(u.otp_enabled && u.otp_confirmed_at);
                    return (
                      <tr key={u.id} className="border-t border-[var(--rb-border)]">
                        <td className="py-2 pr-3">
                          <div className="font-semibold text-[var(--rb-text)]">{u.name}</div>
                          <div className="text-xs text-zinc-600">{u.email}</div>
                        </td>
                        <td className="py-2 pr-3 text-zinc-700">{roleName}</td>
                        <td className="py-2 pr-3">
                          {compliant ? <Badge variant="success">Enabled</Badge> : <Badge variant="danger">Missing</Badge>}
                        </td>
                      </tr>
                    );
                  })}
                  {!complianceLoading && (compliance?.non_compliant_users?.length ?? 0) === 0 ? (
                    <tr>
                      <td className="py-3 text-sm text-zinc-600" colSpan={3}>
                        No non-compliant users.
                      </td>
                    </tr>
                  ) : null}
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>
      ) : null}

      {canManage ? (
        <Card className="shadow-none">
          <CardContent className="pt-5">
            <div className="text-sm font-semibold text-[var(--rb-text)]">Security audit log</div>
            <div className="mt-1 text-sm text-zinc-600">Tenant-scoped authentication and admin actions.</div>

            {auditError ? <div className="mt-3 text-sm text-red-600">{auditError}</div> : null}

            <div className="mt-4 flex flex-wrap items-center gap-2">
              <input
                className="w-full max-w-[320px] rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={auditType}
                onChange={(e) => {
                  setAuditType(e.target.value);
                  setAuditPage(1);
                }}
                placeholder="Filter by type (e.g. login_success)"
              />

              <div className="ml-auto flex items-center gap-2">
                <Button
                  variant="outline"
                  type="button"
                  onClick={() => setAuditPage((p) => Math.max(1, p - 1))}
                  disabled={auditLoading || auditPage <= 1}
                >
                  Prev
                </Button>
                <div className="text-sm text-zinc-700">
                  Page {auditPage} / {auditTotalPages}
                </div>
                <Button
                  variant="outline"
                  type="button"
                  onClick={() => setAuditPage((p) => Math.min(auditTotalPages, p + 1))}
                  disabled={auditLoading || auditPage >= auditTotalPages}
                >
                  Next
                </Button>
              </div>
            </div>

            <div className="mt-4 overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="text-left text-xs text-zinc-500">
                    <th className="py-2 pr-3">When</th>
                    <th className="py-2 pr-3">Type</th>
                    <th className="py-2 pr-3">Source</th>
                    <th className="py-2 pr-3">User</th>
                    <th className="py-2 pr-3">IP</th>
                  </tr>
                </thead>
                <tbody>
                  {auditEvents.map((e) => (
                    <tr key={`${e.source}:${e.id}`} className="border-t border-[var(--rb-border)]">
                      <td className="py-2 pr-3 text-xs text-zinc-600">{e.created_at ?? ""}</td>
                      <td className="py-2 pr-3 font-semibold text-[var(--rb-text)]">{e.type}</td>
                      <td className="py-2 pr-3 text-zinc-700">{e.source}</td>
                      <td className="py-2 pr-3 text-zinc-700">{e.email ?? (e.user_id ? `#${e.user_id}` : "")}</td>
                      <td className="py-2 pr-3 text-zinc-700">{e.ip ?? ""}</td>
                    </tr>
                  ))}
                  {!auditLoading && auditEvents.length === 0 ? (
                    <tr>
                      <td className="py-3 text-sm text-zinc-600" colSpan={5}>
                        No events.
                      </td>
                    </tr>
                  ) : null}
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>
      ) : null}
    </div>
  );
}
