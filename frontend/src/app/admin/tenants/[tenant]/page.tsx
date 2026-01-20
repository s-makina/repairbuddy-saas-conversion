"use client";

import React, { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import Link from "next/link";
import { apiFetch } from "@/lib/api";
import type { AuthEvent, Plan, PlatformAuditLog, Tenant, User } from "@/lib/types";
import { RequireAuth } from "@/components/RequireAuth";
import { useDashboardHeader } from "@/components/DashboardShell";
import { useAuth } from "@/lib/auth";
import { formatDateTime } from "@/lib/datetime";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/Card";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Input } from "@/components/ui/Input";
import { Alert } from "@/components/ui/Alert";
import { DataTable } from "@/components/ui/DataTable";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/Tabs";
import { ConfirmDialog } from "@/components/ui/ConfirmDialog";

type TenantDetailPayload = {
  tenant: Tenant;
  owner: User | null;
};

type AdminTenantEntitlementsPayload = {
  tenant: Tenant;
  plan: Plan | null;
  plan_entitlements: Record<string, unknown>;
  entitlement_overrides: Record<string, unknown>;
  effective_entitlements: Record<string, unknown>;
};

type AdminTenantAuditPayload = {
  tenant: Tenant;
  audit: PlatformAuditLog[];
};

type AdminTenantDiagnosticsPayload = {
  tenant: Tenant;
  recent_auth_events: AuthEvent[];
  recent_failed_jobs: unknown[];
  recent_outbound_communications: unknown[];
  recent_platform_audit: PlatformAuditLog[];
  capabilities: {
    failed_jobs_supported: boolean;
    outbound_communications_supported: boolean;
  };
};

type AdminPlansPayload = {
  plans: Plan[];
};

type ImpersonationStartPayload = {
  session: {
    id: number;
    tenant_id: number;
    target_user_id: number;
    expires_at?: string | null;
  };
};

export default function AdminTenantDetailPage() {
  const params = useParams<{ tenant: string }>();
  const router = useRouter();
  const auth = useAuth();
  const dashboardHeader = useDashboardHeader();

  const tenantId = Number(params.tenant);

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [tenant, setTenant] = useState<Tenant | null>(null);
  const [owner, setOwner] = useState<User | null>(null);

  const [plans, setPlans] = useState<Plan[]>([]);
  const [plansLoading, setPlansLoading] = useState(false);
  const [plansError, setPlansError] = useState<string | null>(null);
  const plansFetchInFlight = useRef(false);

  const [entitlementsLoading, setEntitlementsLoading] = useState(false);
  const [entitlementsError, setEntitlementsError] = useState<string | null>(null);
  const [entitlements, setEntitlements] = useState<AdminTenantEntitlementsPayload | null>(null);
  const [entitlementOverridesDraft, setEntitlementOverridesDraft] = useState<string>("");
  const [entitlementOverridesDraftError, setEntitlementOverridesDraftError] = useState<string | null>(null);

  const [selectedPlanId, setSelectedPlanId] = useState<string>("");

  const [auditLoading, setAuditLoading] = useState(false);
  const [auditError, setAuditError] = useState<string | null>(null);
  const [auditRows, setAuditRows] = useState<PlatformAuditLog[]>([]);

  const [diagnosticsLoading, setDiagnosticsLoading] = useState(false);
  const [diagnosticsError, setDiagnosticsError] = useState<string | null>(null);
  const [diagnostics, setDiagnostics] = useState<AdminTenantDiagnosticsPayload | null>(null);

  const [actionBusy, setActionBusy] = useState<string | null>(null);

  const [reason, setReason] = useState<string>("");
  const [referenceId, setReferenceId] = useState<string>("");
  const [closeRetentionDays, setCloseRetentionDays] = useState<string>("");

  const [confirmSuspendOpen, setConfirmSuspendOpen] = useState(false);
  const [confirmCloseOpen, setConfirmCloseOpen] = useState(false);

  const [resetPasswordInput, setResetPasswordInput] = useState<string>("");
  const [resetResult, setResetResult] = useState<{ owner_user_id: number; password: string } | null>(null);
  const [copied, setCopied] = useState(false);

  const canImpersonate = !!tenant && !!owner && tenant.status !== "closed";
  const canSuspend = !!tenant && tenant.status !== "closed" && tenant.status !== "suspended";
  const canUnsuspend = !!tenant && tenant.status === "suspended";
  const canClose = !!tenant && tenant.status !== "closed";
  const canResetOwnerPassword = !!tenant && !!owner && tenant.status !== "closed";

  const statusVariant = useMemo(() => {
    return (status: Tenant["status"]) => {
      if (status === "active") return "success" as const;
      if (status === "trial") return "info" as const;
      if (status === "past_due") return "warning" as const;
      if (status === "suspended") return "danger" as const;
      return "default" as const;
    };
  }, []);

  const load = useCallback(async () => {
    if (!Number.isFinite(tenantId) || tenantId <= 0) {
      setError("Invalid tenant id.");
      setLoading(false);
      return;
    }

    try {
      setLoading(true);
      setError(null);

      const res = await apiFetch<TenantDetailPayload>(`/api/admin/tenants/${tenantId}`);
      setTenant(res.tenant);
      setOwner(res.owner);
      setSelectedPlanId(typeof res.tenant.plan_id === "number" ? String(res.tenant.plan_id) : "");
    } catch (e) {
      setError(e instanceof Error ? e.message : "Failed to load tenant.");
    } finally {
      setLoading(false);
    }
  }, [tenantId]);

  const loadPlans = useCallback(async () => {
    if (plansFetchInFlight.current) return;
    plansFetchInFlight.current = true;
    setPlansError(null);
    setPlansLoading(true);
    try {
      const res = await apiFetch<AdminPlansPayload>("/api/admin/plans");
      setPlans(Array.isArray(res.plans) ? res.plans : []);
    } catch (e) {
      setPlansError(e instanceof Error ? e.message : "Failed to load plans.");
      setPlans([]);
    } finally {
      plansFetchInFlight.current = false;
      setPlansLoading(false);
    }
  }, []);

  const loadEntitlements = useCallback(async () => {
    if (!Number.isFinite(tenantId) || tenantId <= 0) return;
    if (entitlementsLoading) return;

    setEntitlementsError(null);
    setEntitlementsLoading(true);
    try {
      const res = await apiFetch<AdminTenantEntitlementsPayload>(`/api/admin/tenants/${tenantId}/entitlements`);
      setEntitlements(res);
      setEntitlementOverridesDraft(JSON.stringify(res.entitlement_overrides ?? {}, null, 2));
      setEntitlementOverridesDraftError(null);
    } catch (e) {
      setEntitlementsError(e instanceof Error ? e.message : "Failed to load entitlements.");
      setEntitlements(null);
    } finally {
      setEntitlementsLoading(false);
    }
  }, [entitlementsLoading, tenantId]);

  const loadAudit = useCallback(async () => {
    if (!Number.isFinite(tenantId) || tenantId <= 0) return;
    if (auditLoading) return;

    setAuditError(null);
    setAuditLoading(true);
    try {
      const res = await apiFetch<AdminTenantAuditPayload>(`/api/admin/tenants/${tenantId}/audit?limit=100`);
      setAuditRows(Array.isArray(res.audit) ? res.audit : []);
    } catch (e) {
      setAuditError(e instanceof Error ? e.message : "Failed to load audit log.");
      setAuditRows([]);
    } finally {
      setAuditLoading(false);
    }
  }, [auditLoading, tenantId]);

  const loadDiagnostics = useCallback(async () => {
    if (!Number.isFinite(tenantId) || tenantId <= 0) return;
    if (diagnosticsLoading) return;

    setDiagnosticsError(null);
    setDiagnosticsLoading(true);
    try {
      const res = await apiFetch<AdminTenantDiagnosticsPayload>(`/api/admin/tenants/${tenantId}/diagnostics`);
      setDiagnostics(res);
    } catch (e) {
      setDiagnosticsError(e instanceof Error ? e.message : "Failed to load diagnostics.");
      setDiagnostics(null);
    } finally {
      setDiagnosticsLoading(false);
    }
  }, [diagnosticsLoading, tenantId]);

  useEffect(() => {
    void load();
  }, [load]);

  useEffect(() => {
    dashboardHeader.setHeader({
      breadcrumb: "Admin / Tenants",
      title: tenant ? tenant.name : `Tenant ${tenantId}`,
      subtitle: tenant ? `Tenant ID ${tenant.id} • ${tenant.slug}` : `Tenant ID ${tenantId}`,
      actions: (
        <>
          <Link href={`/admin/tenants/${tenantId}/billing`}>
            <Button variant="outline" size="sm">
              Billing
            </Button>
          </Link>
          <Button variant="outline" size="sm" onClick={() => router.back()}>
            Back
          </Button>
          <Button variant="outline" size="sm" onClick={load} disabled={loading || !!actionBusy}>
            Refresh
          </Button>
        </>
      ),
    });

    return () => {
      dashboardHeader.setHeader(null);
    };
  }, [actionBusy, dashboardHeader, load, loading, router, tenant, tenantId]);

  const runAction = useCallback(
    async (name: string, fn: () => Promise<void>) => {
      if (actionBusy) return;
      setActionBusy(name);
      setCopied(false);
      try {
        await fn();
        await load();
      } finally {
        setActionBusy(null);
      }
    },
    [actionBusy, load],
  );

  useEffect(() => {
    void loadPlans();
  }, [loadPlans]);

  async function onStartImpersonation() {
    if (!tenant || !owner) return;

    await runAction("impersonate", async () => {
      const res = await apiFetch<ImpersonationStartPayload>("/api/admin/impersonation", {
        method: "POST",
        body: {
          tenant_id: tenant.id,
          target_user_id: owner.id,
          reason: reason.trim() || "support",
          reference_id: referenceId.trim() || `tenant-${tenant.id}`,
          duration_minutes: 60,
        },
      });

      if (res.session?.id) {
        await auth.refresh();
        router.push(`/app/${tenant.slug}`);
      }
    });
  }

  async function onSuspend() {
    setConfirmSuspendOpen(true);
  }

  async function onUnsuspend() {
    await runAction("unsuspend", async () => {
      await apiFetch(`/api/admin/tenants/${tenantId}/unsuspend`, {
        method: "PATCH",
        body: { reason: reason.trim() || undefined },
      });
    });
  }

  async function onClose() {
    setConfirmCloseOpen(true);
  }

  async function onConfirmSuspend() {
    setConfirmSuspendOpen(false);
    await runAction("suspend", async () => {
      await apiFetch(`/api/admin/tenants/${tenantId}/suspend`, {
        method: "PATCH",
        body: { reason: reason.trim() || undefined },
      });
    });
  }

  async function onConfirmClose() {
    setConfirmCloseOpen(false);
    await runAction("close", async () => {
      const parsedRetention = closeRetentionDays.trim().length > 0 ? Number(closeRetentionDays.trim()) : null;
      await apiFetch(`/api/admin/tenants/${tenantId}/close`, {
        method: "PATCH",
        body: {
          reason: reason.trim() || undefined,
          data_retention_days: parsedRetention && Number.isFinite(parsedRetention) ? parsedRetention : undefined,
        },
      });
    });
  }

  async function onSavePlan() {
    if (!Number.isFinite(tenantId) || tenantId <= 0) return;
    if (actionBusy) return;

    const planId = selectedPlanId.trim().length > 0 ? Number(selectedPlanId) : null;
    await runAction("set-plan", async () => {
      await apiFetch(`/api/admin/tenants/${tenantId}/plan`, {
        method: "PUT",
        body: {
          plan_id: planId && Number.isFinite(planId) ? planId : null,
          reason: reason.trim() || undefined,
        },
      });
      await loadEntitlements();
    });
  }

  async function onSaveEntitlementOverrides() {
    if (!Number.isFinite(tenantId) || tenantId <= 0) return;
    if (actionBusy) return;

    setEntitlementOverridesDraftError(null);
    let parsed: unknown;
    try {
      parsed = entitlementOverridesDraft.trim().length > 0 ? JSON.parse(entitlementOverridesDraft) : {};
    } catch {
      setEntitlementOverridesDraftError("Invalid JSON.");
      return;
    }

    if (parsed !== null && typeof parsed !== "object") {
      setEntitlementOverridesDraftError("Overrides must be a JSON object.");
      return;
    }

    await runAction("set-entitlements", async () => {
      await apiFetch(`/api/admin/tenants/${tenantId}/entitlements`, {
        method: "PUT",
        body: {
          entitlement_overrides: parsed,
          reason: reason.trim() || undefined,
        },
      });
      await loadEntitlements();
    });
  }

  async function onResetOwnerPassword() {
    await runAction("reset-owner-password", async () => {
      const res = await apiFetch<{ owner_user_id: number; password: string }>(`/api/admin/tenants/${tenantId}/owner/reset-password`, {
        method: "POST",
        body: {
          reason: reason.trim() || undefined,
          password: resetPasswordInput.trim() || undefined,
        },
      });
      setResetResult(res);
      setCopied(false);
      setResetPasswordInput("");
    });
  }

  async function onCopyPassword() {
    if (!resetResult?.password) return;

    try {
      await navigator.clipboard.writeText(resetResult.password);
      setCopied(true);
    } catch {
      setCopied(false);
    }
  }

  return (
    <RequireAuth requiredPermission="admin.tenants.read">
      <div className="space-y-6">
        {loading ? <div className="text-sm text-zinc-500">Loading tenant…</div> : null}
        {error ? (
          <Alert variant="danger" title="Could not load tenant">
            {error}
          </Alert>
        ) : null}

        {tenant ? (
          <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div className="space-y-6 lg:col-span-2">
              <Tabs defaultValue="overview">
                <TabsList>
                  <TabsTrigger value="overview">Overview</TabsTrigger>
                  <TabsTrigger
                    value="plan"
                    onClick={() => {
                      void loadEntitlements();
                    }}
                  >
                    Plan &amp; Entitlements
                  </TabsTrigger>
                  <TabsTrigger value="diagnostics" onClick={() => void loadDiagnostics()}>
                    Diagnostics
                  </TabsTrigger>
                  <TabsTrigger value="audit" onClick={() => void loadAudit()}>
                    Audit Log
                  </TabsTrigger>
                  <TabsTrigger value="actions">Actions</TabsTrigger>
                </TabsList>

                <TabsContent value="overview">
                  <div className="space-y-6">
                    <Card>
                      <CardHeader className="flex flex-row items-start justify-between gap-4">
                        <div className="min-w-0">
                          <CardTitle className="truncate">Overview</CardTitle>
                          <div className="mt-1 text-sm text-zinc-600">Status, plan, and key account details</div>
                        </div>
                        <Badge className="shrink-0" variant={statusVariant(tenant.status)}>
                          {tenant.status}
                        </Badge>
                      </CardHeader>
                      <CardContent>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                          <div>
                            <div className="text-xs text-zinc-500">Contact email</div>
                            <div className="mt-1 text-sm text-[var(--rb-text)]">{tenant.contact_email ?? "—"}</div>
                          </div>
                          <div>
                            <div className="text-xs text-zinc-500">Plan</div>
                            <div className="mt-1 text-sm text-[var(--rb-text)]">{tenant.plan?.name ?? "—"}</div>
                            {tenant.plan?.code ? <div className="mt-1 text-xs text-zinc-500">Code: {tenant.plan.code}</div> : null}
                          </div>
                          <div>
                            <div className="text-xs text-zinc-500">Created</div>
                            <div className="mt-1 text-sm text-[var(--rb-text)]">{formatDateTime(tenant.created_at ?? null)}</div>
                          </div>
                          <div>
                            <div className="text-xs text-zinc-500">Activated</div>
                            <div className="mt-1 text-sm text-[var(--rb-text)]">{formatDateTime(tenant.activated_at ?? null)}</div>
                          </div>
                          <div>
                            <div className="text-xs text-zinc-500">Suspended</div>
                            <div className="mt-1 text-sm text-[var(--rb-text)]">{formatDateTime(tenant.suspended_at ?? null)}</div>
                            {tenant.suspension_reason ? (
                              <div className="mt-1 text-xs text-zinc-500">Reason: {tenant.suspension_reason}</div>
                            ) : null}
                          </div>
                          <div>
                            <div className="text-xs text-zinc-500">Closed</div>
                            <div className="mt-1 text-sm text-[var(--rb-text)]">{formatDateTime(tenant.closed_at ?? null)}</div>
                            {tenant.closed_reason ? (
                              <div className="mt-1 text-xs text-zinc-500">Reason: {tenant.closed_reason}</div>
                            ) : null}
                          </div>
                          <div>
                            <div className="text-xs text-zinc-500">Data retention</div>
                            <div className="mt-1 text-sm text-[var(--rb-text)]">
                              {typeof tenant.data_retention_days === "number" ? `${tenant.data_retention_days} days` : "—"}
                            </div>
                          </div>
                        </div>
                      </CardContent>
                    </Card>
                  </div>
                </TabsContent>

                <TabsContent value="plan">
                  <div className="space-y-6">
                    {plansError ? (
                      <Alert variant="danger" title="Could not load plans">
                        {plansError}
                      </Alert>
                    ) : null}

                    <Card>
                      <CardHeader>
                        <div className="min-w-0">
                          <CardTitle>Plan</CardTitle>
                          <CardDescription>Assign a plan to control default entitlements.</CardDescription>
                        </div>
                      </CardHeader>
                      <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                          <div>
                            <div className="text-sm font-medium text-[var(--rb-text)]">Current plan</div>
                            <div className="mt-1 text-sm text-zinc-700">{tenant.plan?.name ?? "—"}</div>
                          </div>

                          <div>
                            <div className="text-sm font-medium text-[var(--rb-text)]">Assign plan</div>
                            <div className="mt-1">
                              <select
                                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                                value={selectedPlanId}
                                onChange={(e) => setSelectedPlanId(e.target.value)}
                                disabled={plansLoading || !!actionBusy}
                              >
                                <option value="">— No plan —</option>
                                {plans.map((p) => (
                                  <option key={p.id} value={String(p.id)}>
                                    {p.name} ({p.code})
                                  </option>
                                ))}
                              </select>
                            </div>
                          </div>
                        </div>

                        <div className="flex items-center gap-2">
                          <Button variant="primary" size="sm" onClick={onSavePlan} disabled={!!actionBusy}>
                            {actionBusy === "set-plan" ? "Saving…" : "Save plan"}
                          </Button>
                          <Button variant="outline" size="sm" onClick={() => void loadEntitlements()} disabled={entitlementsLoading || !!actionBusy}>
                            Refresh entitlements
                          </Button>
                        </div>
                      </CardContent>
                    </Card>

                    {entitlementsError ? (
                      <Alert variant="danger" title="Could not load entitlements">
                        {entitlementsError}
                      </Alert>
                    ) : null}

                    <Card>
                      <CardHeader>
                        <div className="min-w-0">
                          <CardTitle>Entitlements</CardTitle>
                          <CardDescription>Plan defaults, overrides, and effective entitlements for this tenant.</CardDescription>
                        </div>
                      </CardHeader>
                      <CardContent className="space-y-4">
                        {entitlementsLoading ? <div className="text-sm text-zinc-500">Loading entitlements…</div> : null}

                        {!entitlements && !entitlementsLoading ? (
                          <div className="text-sm text-zinc-600">No entitlements loaded yet.</div>
                        ) : null}

                        {entitlements ? (
                          <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                            <Card className="bg-[var(--rb-surface-muted)]">
                              <CardContent className="pt-5">
                                <div className="text-xs font-medium tracking-wide text-zinc-500">Plan entitlements</div>
                                <pre className="mt-3 max-h-[240px] overflow-auto rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white p-3 text-xs text-zinc-700">
                                  {JSON.stringify(entitlements.plan_entitlements ?? {}, null, 2)}
                                </pre>
                              </CardContent>
                            </Card>

                            <Card className="bg-[var(--rb-surface-muted)]">
                              <CardContent className="pt-5">
                                <div className="text-xs font-medium tracking-wide text-zinc-500">Effective entitlements</div>
                                <pre className="mt-3 max-h-[240px] overflow-auto rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white p-3 text-xs text-zinc-700">
                                  {JSON.stringify(entitlements.effective_entitlements ?? {}, null, 2)}
                                </pre>
                              </CardContent>
                            </Card>

                            <Card className="bg-[var(--rb-surface-muted)]">
                              <CardContent className="pt-5">
                                <div className="text-xs font-medium tracking-wide text-zinc-500">Overrides</div>
                                <div className="mt-3 space-y-2">
                                  <textarea
                                    className="min-h-[240px] w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white p-3 font-mono text-xs text-zinc-800"
                                    value={entitlementOverridesDraft}
                                    onChange={(e) => {
                                      setEntitlementOverridesDraft(e.target.value);
                                      setEntitlementOverridesDraftError(null);
                                    }}
                                    spellCheck={false}
                                  />
                                  {entitlementOverridesDraftError ? <div className="text-sm text-red-600">{entitlementOverridesDraftError}</div> : null}
                                  <div className="flex items-center gap-2">
                                    <Button variant="primary" size="sm" onClick={onSaveEntitlementOverrides} disabled={!!actionBusy}>
                                      {actionBusy === "set-entitlements" ? "Saving…" : "Save overrides"}
                                    </Button>
                                  </div>
                                </div>
                              </CardContent>
                            </Card>
                          </div>
                        ) : null}
                      </CardContent>
                    </Card>
                  </div>
                </TabsContent>

                <TabsContent value="diagnostics">
                  <div className="space-y-6">
                    {diagnosticsError ? (
                      <Alert variant="danger" title="Could not load diagnostics">
                        {diagnosticsError}
                      </Alert>
                    ) : null}

                    <div className="flex items-start justify-between gap-3">
                      <div className="min-w-0">
                        <div className="text-sm font-semibold text-[var(--rb-text)]">Diagnostics</div>
                        <div className="mt-1 text-sm text-zinc-600">Recent auth activity and platform actions for this tenant.</div>
                      </div>
                      <Button variant="outline" size="sm" onClick={() => void loadDiagnostics()} disabled={diagnosticsLoading || !!actionBusy}>
                        Refresh
                      </Button>
                    </div>

                    {diagnosticsLoading ? <div className="text-sm text-zinc-500">Loading diagnostics…</div> : null}

                    {diagnostics ? (
                      <div className="space-y-6">
                        <Card>
                          <CardHeader>
                            <CardTitle>Recent auth events</CardTitle>
                          </CardHeader>
                          <CardContent>
                            <DataTable
                              data={Array.isArray(diagnostics.recent_auth_events) ? diagnostics.recent_auth_events : []}
                              loading={false}
                              emptyMessage="No auth events found."
                              getRowId={(e) => e.id}
                              search={{
                                placeholder: "Search event type or email…",
                                getSearchText: (e) => `${e.event_type} ${e.email ?? ""} ${e.ip ?? ""}`,
                              }}
                              columns={[
                                {
                                  id: "time",
                                  header: "Time",
                                  cell: (e) => <div className="text-sm text-zinc-700">{formatDateTime(e.created_at ?? null)}</div>,
                                  className: "whitespace-nowrap",
                                },
                                {
                                  id: "type",
                                  header: "Type",
                                  cell: (e) => <div className="text-sm font-medium text-zinc-700">{e.event_type}</div>,
                                  className: "whitespace-nowrap",
                                },
                                {
                                  id: "email",
                                  header: "Email",
                                  cell: (e) => <div className="text-sm text-zinc-700">{e.email ?? "—"}</div>,
                                  className: "whitespace-nowrap",
                                },
                                {
                                  id: "ip",
                                  header: "IP",
                                  cell: (e) => <div className="text-sm text-zinc-700">{e.ip ?? "—"}</div>,
                                  className: "whitespace-nowrap",
                                },
                              ]}
                            />
                          </CardContent>
                        </Card>

                        <Card>
                          <CardHeader>
                            <CardTitle>Recent platform actions</CardTitle>
                          </CardHeader>
                          <CardContent>
                            <DataTable
                              data={Array.isArray(diagnostics.recent_platform_audit) ? diagnostics.recent_platform_audit : []}
                              loading={false}
                              emptyMessage="No platform audit events found."
                              getRowId={(a) => a.id}
                              search={{
                                placeholder: "Search action or actor…",
                                getSearchText: (a) => `${a.action} ${a.actor?.email ?? ""} ${a.reason ?? ""}`,
                              }}
                              columns={[
                                {
                                  id: "time",
                                  header: "Time",
                                  cell: (a) => <div className="text-sm text-zinc-700">{formatDateTime(a.created_at ?? null)}</div>,
                                  className: "whitespace-nowrap",
                                },
                                {
                                  id: "action",
                                  header: "Action",
                                  cell: (a) => <div className="text-sm font-medium text-zinc-700">{a.action}</div>,
                                  className: "whitespace-nowrap",
                                },
                                {
                                  id: "actor",
                                  header: "Actor",
                                  cell: (a) => <div className="text-sm text-zinc-700">{a.actor?.email ?? a.actor_user_id ?? "—"}</div>,
                                  className: "whitespace-nowrap",
                                },
                                {
                                  id: "reason",
                                  header: "Reason",
                                  cell: (a) => <div className="text-sm text-zinc-700">{a.reason ?? "—"}</div>,
                                  className: "max-w-[360px] truncate",
                                },
                              ]}
                            />
                          </CardContent>
                        </Card>
                      </div>
                    ) : (
                      <div className="text-sm text-zinc-600">No diagnostics loaded yet.</div>
                    )}
                  </div>
                </TabsContent>

                <TabsContent value="audit">
                  <div className="space-y-6">
                    {auditError ? (
                      <Alert variant="danger" title="Could not load audit log">
                        {auditError}
                      </Alert>
                    ) : null}

                    <Card>
                      <CardHeader>
                        <div className="flex items-start justify-between gap-3">
                          <div className="min-w-0">
                            <CardTitle>Audit Log</CardTitle>
                            <CardDescription>Tenant-scoped platform audit events.</CardDescription>
                          </div>
                          <Button variant="outline" size="sm" onClick={() => void loadAudit()} disabled={auditLoading || !!actionBusy}>
                            Refresh
                          </Button>
                        </div>
                      </CardHeader>
                      <CardContent>
                        <DataTable
                          data={auditRows}
                          loading={auditLoading}
                          emptyMessage="No audit events."
                          getRowId={(a) => a.id}
                          search={{
                            placeholder: "Search action, reason, or actor…",
                            getSearchText: (a) => `${a.action} ${a.reason ?? ""} ${a.actor?.email ?? ""}`,
                          }}
                          columns={[
                            {
                              id: "time",
                              header: "Time",
                              cell: (a) => <div className="text-sm text-zinc-700">{formatDateTime(a.created_at ?? null)}</div>,
                              className: "whitespace-nowrap",
                            },
                            {
                              id: "action",
                              header: "Action",
                              cell: (a) => <div className="text-sm font-medium text-zinc-700">{a.action}</div>,
                              className: "whitespace-nowrap",
                            },
                            {
                              id: "actor",
                              header: "Actor",
                              cell: (a) => <div className="text-sm text-zinc-700">{a.actor?.email ?? a.actor_user_id ?? "—"}</div>,
                              className: "whitespace-nowrap",
                            },
                            {
                              id: "reason",
                              header: "Reason",
                              cell: (a) => <div className="text-sm text-zinc-700">{a.reason ?? "—"}</div>,
                              className: "max-w-[420px] truncate",
                            },
                            {
                              id: "ip",
                              header: "IP",
                              cell: (a) => <div className="text-sm text-zinc-700">{a.ip ?? "—"}</div>,
                              className: "whitespace-nowrap",
                              headerClassName: "whitespace-nowrap",
                            },
                          ]}
                        />
                      </CardContent>
                    </Card>
                  </div>
                </TabsContent>

                <TabsContent value="actions">
                  <div className="space-y-6">
                    <Card>
                      <CardHeader>
                        <CardTitle>Support context</CardTitle>
                      </CardHeader>
                      <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                          <div>
                            <div className="text-sm font-medium text-[var(--rb-text)]">Reason</div>
                            <div className="mt-1">
                              <Input value={reason} onChange={(e) => setReason(e.target.value)} placeholder="Ticket ID / reason (optional)" />
                            </div>
                            <div className="mt-1 text-xs text-zinc-500">Used for audit trails when performing admin actions.</div>
                          </div>

                          <div>
                            <div className="text-sm font-medium text-[var(--rb-text)]">Reference ID</div>
                            <div className="mt-1">
                              <Input value={referenceId} onChange={(e) => setReferenceId(e.target.value)} placeholder="e.g. ticket-1234" />
                            </div>
                            <div className="mt-1 text-xs text-zinc-500">Optional. If empty, defaults to tenant-{tenant.id}.</div>
                          </div>

                          <div>
                            <div className="text-sm font-medium text-[var(--rb-text)]">Close retention days</div>
                            <div className="mt-1">
                              <Input value={closeRetentionDays} onChange={(e) => setCloseRetentionDays(e.target.value)} placeholder="e.g. 30" inputMode="numeric" />
                            </div>
                            <div className="mt-1 text-xs text-zinc-500">Optional: overrides retention when closing the tenant.</div>
                          </div>
                        </div>
                      </CardContent>
                    </Card>

                    <Card>
                      <CardHeader>
                        <div className="flex items-start justify-between gap-4">
                          <div className="min-w-0">
                            <CardTitle>Tenant actions</CardTitle>
                            <CardDescription>Operational actions that affect tenant access and security.</CardDescription>
                          </div>
                          <Badge className="shrink-0" variant={statusVariant(tenant.status)}>
                            {tenant.status}
                          </Badge>
                        </div>
                      </CardHeader>
                      <CardContent className="space-y-4">
                        <div className="space-y-2">
                          <div className="text-sm font-medium text-[var(--rb-text)]">Impersonation</div>
                          <div className="flex flex-wrap items-center gap-2">
                            <Button variant="primary" size="sm" onClick={onStartImpersonation} disabled={!!actionBusy || !canImpersonate}>
                              {actionBusy === "impersonate" ? "Starting…" : "Impersonate owner"}
                            </Button>
                          </div>
                          <div className="text-xs text-zinc-500">Starts a temporary session as the tenant owner for support.</div>
                        </div>

                        <div className="space-y-2">
                          <div className="text-sm font-medium text-[var(--rb-text)]">Access / lifecycle</div>
                          <div className="flex flex-wrap gap-2">
                            <Button variant="secondary" size="sm" onClick={onSuspend} disabled={!!actionBusy || !canSuspend}>
                              {actionBusy === "suspend" ? "Suspending…" : "Suspend"}
                            </Button>
                            <Button variant="outline" size="sm" onClick={onUnsuspend} disabled={!!actionBusy || !canUnsuspend}>
                              {actionBusy === "unsuspend" ? "Unsuspending…" : "Unsuspend"}
                            </Button>
                            <Button variant="outline" size="sm" onClick={onClose} disabled={!!actionBusy || !canClose}>
                              {actionBusy === "close" ? "Closing…" : "Close tenant"}
                            </Button>
                          </div>
                          <div className="text-xs text-zinc-500">Suspend blocks access. Close is destructive and final.</div>
                        </div>
                      </CardContent>
                    </Card>

                    <Card>
                      <CardHeader>
                        <CardTitle>Security</CardTitle>
                      </CardHeader>
                      <CardContent className="space-y-4">
                        <Alert variant="warning" title="Force-reset owner password">
                          This will immediately change the owner password. The new password is shown once below. Treat it as a secret.
                        </Alert>

                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                          <div>
                            <div className="text-sm font-medium text-[var(--rb-text)]">Set a specific password</div>
                            <div className="mt-1">
                              <Input
                                value={resetPasswordInput}
                                onChange={(e) => setResetPasswordInput(e.target.value)}
                                placeholder="Leave blank to generate"
                                type="password"
                              />
                            </div>
                            <div className="mt-1 text-xs text-zinc-500">Optional. If empty, a secure password is generated.</div>
                          </div>

                          <div className="flex items-end">
                            <Button variant="primary" size="sm" onClick={onResetOwnerPassword} disabled={!!actionBusy || !canResetOwnerPassword}>
                              {actionBusy === "reset-owner-password" ? "Resetting…" : "Reset owner password"}
                            </Button>
                          </div>
                        </div>

                        {resetResult ? (
                          <Alert variant="warning" title="New password generated">
                            <div className="space-y-2">
                              <div className="break-all font-mono text-sm text-[var(--rb-text)]">{resetResult.password}</div>
                              <div className="flex flex-wrap gap-2">
                                <Button variant="outline" size="sm" onClick={onCopyPassword}>
                                  {copied ? "Copied" : "Copy"}
                                </Button>
                                <Button
                                  variant="ghost"
                                  size="sm"
                                  onClick={() => {
                                    setResetResult(null);
                                    setCopied(false);
                                  }}
                                >
                                  Hide
                                </Button>
                              </div>
                            </div>
                          </Alert>
                        ) : null}
                      </CardContent>
                    </Card>
                  </div>
                </TabsContent>
              </Tabs>
            </div>

            <div className="space-y-6">
              <Card>
                <CardHeader>
                  <CardTitle>Owner</CardTitle>
                </CardHeader>
                <CardContent>
                  {owner ? (
                    <div className="space-y-2">
                      <div className="text-sm font-medium text-[var(--rb-text)]">{owner.name}</div>
                      <div className="text-sm text-zinc-600">{owner.email}</div>
                      <div className="text-xs text-zinc-500">User ID: {owner.id}</div>
                    </div>
                  ) : (
                    <div className="text-sm text-zinc-600">No owner found.</div>
                  )}
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle>Quick actions</CardTitle>
                </CardHeader>
                <CardContent className="space-y-2">
                  <Button variant="primary" size="sm" onClick={onStartImpersonation} disabled={!!actionBusy || !canImpersonate} className="w-full">
                    {actionBusy === "impersonate" ? "Starting…" : "Impersonate owner"}
                  </Button>
                  <Button variant="outline" size="sm" onClick={onSuspend} disabled={!!actionBusy || !canSuspend} className="w-full">
                    {actionBusy === "suspend" ? "Suspending…" : "Suspend"}
                  </Button>
                  <Button variant="outline" size="sm" onClick={onUnsuspend} disabled={!!actionBusy || !canUnsuspend} className="w-full">
                    {actionBusy === "unsuspend" ? "Unsuspending…" : "Unsuspend"}
                  </Button>
                  <Button variant="outline" size="sm" onClick={onClose} disabled={!!actionBusy || !canClose} className="w-full">
                    {actionBusy === "close" ? "Closing…" : "Close tenant"}
                  </Button>
                </CardContent>
              </Card>
            </div>
          </div>
        ) : null}

        <ConfirmDialog
          open={confirmSuspendOpen}
          title="Suspend tenant"
          message={
            <div className="space-y-2">
              <div>This will suspend the tenant and prevent normal access.</div>
              <div className="text-xs text-zinc-500">Reason (optional): {reason.trim().length > 0 ? reason.trim() : "—"}</div>
            </div>
          }
          confirmText="Suspend"
          confirmVariant="secondary"
          busy={actionBusy === "suspend"}
          onCancel={() => setConfirmSuspendOpen(false)}
          onConfirm={() => void onConfirmSuspend()}
        />

        <ConfirmDialog
          open={confirmCloseOpen}
          title="Close tenant"
          message={
            <div className="space-y-2">
              <div>This is a destructive action. The tenant will be closed.</div>
              <div className="text-xs text-zinc-500">Reason (optional): {reason.trim().length > 0 ? reason.trim() : "—"}</div>
              <div className="text-xs text-zinc-500">
                Retention override (optional): {closeRetentionDays.trim().length > 0 ? closeRetentionDays.trim() : "—"}
              </div>
            </div>
          }
          confirmText="Close tenant"
          confirmVariant="secondary"
          busy={actionBusy === "close"}
          onCancel={() => setConfirmCloseOpen(false)}
          onConfirm={() => void onConfirmClose()}
        />
      </div>
    </RequireAuth>
  );
}
