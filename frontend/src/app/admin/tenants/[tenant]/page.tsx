"use client";

import React, { useCallback, useEffect, useMemo, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { apiFetch } from "@/lib/api";
import type { Tenant, User } from "@/lib/types";
import { RequireAuth } from "@/components/RequireAuth";
import { useAuth } from "@/lib/auth";
import { formatDateTime } from "@/lib/datetime";
import { PageHeader } from "@/components/ui/PageHeader";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/Card";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Input } from "@/components/ui/Input";
import { Alert } from "@/components/ui/Alert";

type TenantDetailPayload = {
  tenant: Tenant;
  owner: User | null;
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

  const tenantId = Number(params.tenant);

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [tenant, setTenant] = useState<Tenant | null>(null);
  const [owner, setOwner] = useState<User | null>(null);

  const [actionBusy, setActionBusy] = useState<string | null>(null);

  const [reason, setReason] = useState<string>("");
  const [referenceId, setReferenceId] = useState<string>("");
  const [closeRetentionDays, setCloseRetentionDays] = useState<string>("");

  const [resetPasswordInput, setResetPasswordInput] = useState<string>("");
  const [resetResult, setResetResult] = useState<{ owner_user_id: number; password: string } | null>(null);
  const [copied, setCopied] = useState(false);

  const canImpersonate = !!tenant && !!owner && tenant.status !== "closed";
  const canSuspend = !!tenant && tenant.status !== "closed";
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
    } catch (e) {
      setError(e instanceof Error ? e.message : "Failed to load tenant.");
    } finally {
      setLoading(false);
    }
  }, [tenantId]);

  useEffect(() => {
    void load();
  }, [load]);

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
    await runAction("suspend", async () => {
      await apiFetch(`/api/admin/tenants/${tenantId}/suspend`, {
        method: "PATCH",
        body: { reason: reason.trim() || undefined },
      });
    });
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
        <PageHeader
          title={tenant ? tenant.name : "Tenant"}
          description={tenant ? `Tenant ID ${tenant.id} • ${tenant.slug}` : ""}
          actions={
            <>
              <Button variant="outline" size="sm" onClick={() => router.back()}>
                Back
              </Button>
              <Button variant="outline" size="sm" onClick={load} disabled={loading || !!actionBusy}>
                Refresh
              </Button>
            </>
          }
        />

        {loading ? <div className="text-sm text-zinc-500">Loading tenant…</div> : null}
        {error ? (
          <Alert variant="danger" title="Could not load tenant">
            {error}
          </Alert>
        ) : null}

        {tenant ? (
          <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div className="space-y-6 lg:col-span-2">
              <Card>
                <CardHeader className="flex flex-row items-start justify-between gap-4">
                  <div className="min-w-0">
                    <CardTitle className="truncate">Overview</CardTitle>
                    <div className="mt-1 text-sm text-zinc-600">Status and key account details</div>
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
                      <div className="mt-1 text-xs text-zinc-500">Required to start impersonation.</div>
                    </div>

                    <div>
                      <div className="text-sm font-medium text-[var(--rb-text)]">Close retention days</div>
                      <div className="mt-1">
                        <Input value={closeRetentionDays} onChange={(e) => setCloseRetentionDays(e.target.value)} placeholder="e.g. 30" inputMode="numeric" />
                      </div>
                      <div className="mt-1 text-xs text-zinc-500">Optional: overrides retention when closing the tenant.</div>
                    </div>
                  </div>

                  <div className="flex flex-wrap gap-2">
                    <Button variant="primary" size="sm" onClick={onStartImpersonation} disabled={!!actionBusy || !canImpersonate}>
                      {actionBusy === "impersonate" ? "Starting…" : "Impersonate owner"}
                    </Button>
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
      </div>
    </RequireAuth>
  );
}
