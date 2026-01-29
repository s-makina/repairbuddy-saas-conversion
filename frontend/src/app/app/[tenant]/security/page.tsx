"use client";

import { apiFetch, ApiError } from "@/lib/api";
import { useAuth } from "@/lib/auth";
import { Button } from "@/components/ui/Button";
import { Badge } from "@/components/ui/Badge";
import { Card, CardContent } from "@/components/ui/Card";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { PageHeader } from "@/components/ui/PageHeader";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/Tabs";
import QRCode from "react-qr-code";
import React, { useEffect, useMemo, useState } from "react";
import { useParams } from "next/navigation";

type OtpSetupPayload = {
  secret: string;
  otpauth_uri: string;
};

type Role = {
  id: number;
  name: string;
};

type RolesPayload = {
  roles: Role[];
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

  const [roles, setRoles] = useState<Role[]>([]);
  const [rolesLoading, setRolesLoading] = useState(false);

  const [mfaRoleIds, setMfaRoleIds] = useState<number[]>([]);
  const [mfaGraceDays, setMfaGraceDays] = useState<number>(7);
  const [idleMinutes, setIdleMinutes] = useState<number>(60);
  const [maxLifetimeDays, setMaxLifetimeDays] = useState<number>(30);
  const [lockoutAttempts, setLockoutAttempts] = useState<number>(10);
  const [lockoutMinutes, setLockoutMinutes] = useState<number>(15);

  const [compliance, setCompliance] = useState<CompliancePayload["mfa"] | null>(null);
  const [complianceLoading, setComplianceLoading] = useState(false);
  const [complianceError, setComplianceError] = useState<string | null>(null);

  const [complianceQuery, setComplianceQuery] = useState("");
  const [compliancePageIndex, setCompliancePageIndex] = useState(0);
  const [compliancePageSize, setCompliancePageSize] = useState(10);

  const [auditEvents, setAuditEvents] = useState<AuditEvent[]>([]);
  const [auditLoading, setAuditLoading] = useState(false);
  const [auditError, setAuditError] = useState<string | null>(null);
  const [auditType, setAuditType] = useState<string>("");
  const [auditPage, setAuditPage] = useState<number>(1);
  const [auditPageSize, setAuditPageSize] = useState<number>(25);
  const [auditTotalPages, setAuditTotalPages] = useState<number>(1);
  const [auditTotalRows, setAuditTotalRows] = useState<number>(0);

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
        setMfaRoleIds(Array.isArray(res.settings.mfa_required_roles) ? res.settings.mfa_required_roles : []);
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

    setRolesLoading(true);
    void apiFetch<RolesPayload>(`/api/${tenant}/app/roles`)
      .then((res) => setRoles(Array.isArray(res.roles) ? res.roles : []))
      .catch(() => setRoles([]))
      .finally(() => setRolesLoading(false));
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
    qs.set("per_page", String(auditPageSize));

    void apiFetch<AuditEventsPayload>(`/api/${tenant}/app/audit-events?${qs.toString()}`)
      .then((res) => {
        setAuditEvents(Array.isArray(res.events) ? res.events : []);
        setAuditTotalPages(typeof res.meta?.last_page === "number" ? res.meta.last_page : 1);
        setAuditTotalRows(typeof res.meta?.total === "number" ? res.meta.total : (Array.isArray(res.events) ? res.events.length : 0));
      })
      .catch((err) => setAuditError(err instanceof Error ? err.message : "Failed to load audit events."))
      .finally(() => setAuditLoading(false));
  }, [canManage, tenant, auditType, auditPage, auditPageSize]);

  const complianceRows = useMemo(() => {
    const raw = Array.isArray(compliance?.non_compliant_users) ? compliance?.non_compliant_users : [];
    return raw.map((u) => {
      const roleName = u.roleModel?.name ?? (u as unknown as { role_model?: { name?: string } }).role_model?.name ?? "(none)";
      const compliant = Boolean(u.otp_enabled && u.otp_confirmed_at);
      return {
        id: u.id,
        name: u.name,
        email: u.email ?? "",
        roleName,
        compliant,
      };
    });
  }, [compliance?.non_compliant_users]);

  const complianceFiltered = useMemo(() => {
    const needle = complianceQuery.trim().toLowerCase();
    if (!needle) return complianceRows;
    return complianceRows.filter((row) => `${row.id} ${row.name} ${row.email} ${row.roleName}`.toLowerCase().includes(needle));
  }, [complianceQuery, complianceRows]);

  const complianceTotalRows = complianceFiltered.length;
  const compliancePageRows = useMemo(() => {
    const start = compliancePageIndex * compliancePageSize;
    const end = start + compliancePageSize;
    return complianceFiltered.slice(start, end);
  }, [complianceFiltered, compliancePageIndex, compliancePageSize]);

  const complianceColumns = useMemo<Array<DataTableColumn<(typeof complianceRows)[number]>>>(
    () => [
      {
        id: "user",
        header: "User",
        cell: (row) => (
          <div className="min-w-0">
            <div className="truncate font-semibold text-[var(--rb-text)]">{row.name}</div>
            <div className="truncate text-xs text-zinc-600">{row.email}</div>
          </div>
        ),
        className: "min-w-[240px]",
      },
      {
        id: "role",
        header: "Role",
        cell: (row) => <div className="text-sm text-zinc-700">{row.roleName}</div>,
        className: "min-w-[200px]",
      },
      {
        id: "otp",
        header: "OTP",
        cell: (row) => (row.compliant ? <Badge variant="success">Enabled</Badge> : <Badge variant="danger">Missing</Badge>),
        className: "whitespace-nowrap",
      },
    ],
    [],
  );

  const auditFiltered = useMemo(() => {
    return auditEvents;
  }, [auditEvents]);

  const auditColumns = useMemo<Array<DataTableColumn<AuditEvent>>>(
    () => [
      {
        id: "when",
        header: "When",
        cell: (row) => <div className="text-xs text-zinc-600">{row.created_at ?? ""}</div>,
        className: "whitespace-nowrap",
      },
      {
        id: "type",
        header: "Type",
        cell: (row) => <div className="font-semibold text-[var(--rb-text)]">{row.type}</div>,
        className: "min-w-[240px]",
      },
      {
        id: "source",
        header: "Source",
        cell: (row) => <div className="text-zinc-700">{row.source}</div>,
        className: "whitespace-nowrap",
      },
      {
        id: "user",
        header: "User",
        cell: (row) => <div className="text-zinc-700">{row.email ?? (row.user_id ? `#${row.user_id}` : "")}</div>,
        className: "min-w-[220px]",
      },
      {
        id: "ip",
        header: "IP",
        cell: (row) => <div className="text-zinc-700">{row.ip ?? ""}</div>,
        className: "whitespace-nowrap",
      },
    ],
    [],
  );

  async function onSavePolicies(e: React.FormEvent) {
    e.preventDefault();
    if (!canManage) return;
    if (typeof tenant !== "string" || tenant.length === 0) return;

    setSettingsError(null);
    setSettingsLoading(true);

    const roleIds = Array.isArray(mfaRoleIds)
      ? mfaRoleIds.filter((x) => Number.isFinite(x) && x > 0)
      : [];

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

      <Tabs defaultValue={canManage ? "policies" : "mfa"}>
        <TabsList>
          <TabsTrigger value="mfa">MFA / OTP</TabsTrigger>
          {canManage ? <TabsTrigger value="policies">Policies</TabsTrigger> : null}
          {canManage ? <TabsTrigger value="compliance">Compliance</TabsTrigger> : null}
          {canManage ? <TabsTrigger value="audit">Audit Log</TabsTrigger> : null}
        </TabsList>

        <TabsContent value="mfa">
          <Card className="shadow-none">
            <CardContent className="pt-5">
              <div className="text-sm font-semibold text-[var(--rb-text)]">Multi-factor authentication (OTP)</div>
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
        </TabsContent>

        {canManage ? (
          <TabsContent value="policies">
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
                      MFA required roles
                    </label>
                    <div className="rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white p-3">
                      {rolesLoading ? <div className="text-sm text-zinc-600">Loading roles...</div> : null}
                      {!rolesLoading && roles.length === 0 ? <div className="text-sm text-zinc-600">No roles found.</div> : null}

                      {!rolesLoading && roles.length > 0 ? (
                        <div className="grid gap-2 md:grid-cols-2">
                          {roles.map((r) => {
                            const checked = mfaRoleIds.includes(r.id);
                            return (
                              <label key={r.id} className="flex items-center gap-2 text-sm text-zinc-700">
                                <input
                                  type="checkbox"
                                  checked={checked}
                                  disabled={settingsLoading}
                                  onChange={(e) => {
                                    const next = e.target.checked
                                      ? Array.from(new Set([...mfaRoleIds, r.id]))
                                      : mfaRoleIds.filter((id) => id !== r.id);
                                    setMfaRoleIds(next);
                                  }}
                                />
                                <span className="min-w-0 truncate">{r.name}</span>
                              </label>
                            );
                          })}
                        </div>
                      ) : null}
                    </div>
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
                    <Button type="submit" disabled={settingsLoading}>
                      {settingsLoading ? "Saving..." : "Save policies"}
                    </Button>
                    <Button
                      variant="outline"
                      type="button"
                      onClick={() => void onForceLogout()}
                      disabled={settingsLoading}
                    >
                      Force logout all users
                    </Button>
                  </div>
                </form>
              </CardContent>
            </Card>
          </TabsContent>
        ) : null}

        {canManage ? (
          <TabsContent value="compliance">
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

                <div className="mt-4">
                  <DataTable
                    title="Non-compliant users"
                    data={compliancePageRows}
                    loading={complianceLoading}
                    emptyMessage="No non-compliant users."
                    columns={complianceColumns}
                    getRowId={(row) => String(row.id)}
                    search={{ placeholder: "Search users..." }}
                    server={{
                      query: complianceQuery,
                      onQueryChange: (value) => {
                        setComplianceQuery(value);
                        setCompliancePageIndex(0);
                      },
                      pageIndex: compliancePageIndex,
                      onPageIndexChange: setCompliancePageIndex,
                      pageSize: compliancePageSize,
                      onPageSizeChange: (value) => {
                        setCompliancePageSize(value);
                        setCompliancePageIndex(0);
                      },
                      totalRows: complianceTotalRows,
                    }}
                  />
                </div>
              </CardContent>
            </Card>
          </TabsContent>
        ) : null}

        {canManage ? (
          <TabsContent value="audit">
            <Card className="shadow-none">
              <CardContent className="pt-5">
                <div className="text-sm font-semibold text-[var(--rb-text)]">Security audit log</div>
                <div className="mt-1 text-sm text-zinc-600">Tenant-scoped authentication and admin actions.</div>

                {auditError ? <div className="mt-3 text-sm text-red-600">{auditError}</div> : null}

                <div className="mt-4">
                  <DataTable
                    title="Audit events"
                    data={auditFiltered}
                    loading={auditLoading}
                    emptyMessage="No events."
                    columns={auditColumns}
                    getRowId={(row) => `${row.source}:${row.id}`}
                    search={{ placeholder: "Filter by type (e.g. login_success)" }}
                    server={{
                      query: auditType,
                      onQueryChange: (value) => {
                        setAuditType(value);
                        setAuditPage(1);
                      },
                      pageIndex: auditPage - 1,
                      onPageIndexChange: (value) => setAuditPage(value + 1),
                      pageSize: auditPageSize,
                      onPageSizeChange: (value) => {
                        setAuditPageSize(value);
                        setAuditPage(1);
                      },
                      totalRows: auditTotalRows,
                    }}
                  />
                </div>
              </CardContent>
            </Card>
          </TabsContent>
        ) : null}
      </Tabs>
    </div>
  );
}
