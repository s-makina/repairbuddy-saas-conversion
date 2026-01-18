"use client";

import React, { useCallback, useEffect, useMemo, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { apiFetch } from "@/lib/api";
import type { Tenant, User } from "@/lib/types";
import { RequireAuth } from "@/components/RequireAuth";
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

export default function AdminTenantDetailPage() {
  const params = useParams<{ tenant: string }>();
  const router = useRouter();

  const tenantId = Number(params.tenant);

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [tenant, setTenant] = useState<Tenant | null>(null);
  const [owner, setOwner] = useState<User | null>(null);

  const [actionBusy, setActionBusy] = useState<string | null>(null);

  const [reason, setReason] = useState<string>("");
  const [closeRetentionDays, setCloseRetentionDays] = useState<string>("");

  const [resetPasswordInput, setResetPasswordInput] = useState<string>("");
  const [resetResult, setResetResult] = useState<{ owner_user_id: number; password: string } | null>(null);
  const [copied, setCopied] = useState(false);

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
          title={tenant ? `Tenant: ${tenant.name}` : "Tenant"}
          description={tenant ? `ID ${tenant.id} • ${tenant.slug}` : ""}
        />

        {loading ? <div className="text-sm text-zinc-500">Loading tenant...</div> : null}
        {error ? <div className="text-sm text-red-600">{error}</div> : null}

        {tenant ? (
          <div className="space-y-6">
            <Card className="shadow-none">
              <CardHeader className="flex flex-row items-center justify-between gap-3">
                <div className="min-w-0">
                  <CardTitle className="truncate">Tenant details</CardTitle>
                </div>
                <Badge variant={statusVariant(tenant.status)}>{tenant.status}</Badge>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                  <div>
                    <div className="text-xs text-zinc-500">Contact email</div>
                    <div className="text-sm text-[var(--rb-text)]">{tenant.contact_email ?? "—"}</div>
                  </div>
                  <div>
                    <div className="text-xs text-zinc-500">Created</div>
                    <div className="text-sm text-[var(--rb-text)]">{tenant.created_at ?? "—"}</div>
                  </div>
                  <div>
                    <div className="text-xs text-zinc-500">Activated</div>
                    <div className="text-sm text-[var(--rb-text)]">{tenant.activated_at ?? "—"}</div>
                  </div>
                  <div>
                    <div className="text-xs text-zinc-500">Suspended</div>
                    <div className="text-sm text-[var(--rb-text)]">{tenant.suspended_at ?? "—"}</div>
                  </div>
                  <div>
                    <div className="text-xs text-zinc-500">Closed</div>
                    <div className="text-sm text-[var(--rb-text)]">{tenant.closed_at ?? "—"}</div>
                  </div>
                </div>

                <div className="pt-2">
                  <Button variant="outline" size="sm" onClick={() => router.back()}>
                    Back
                  </Button>
                </div>
              </CardContent>
            </Card>

            <Card className="shadow-none">
              <CardHeader>
                <CardTitle>Owner</CardTitle>
              </CardHeader>
              <CardContent>
                {owner ? (
                  <div className="space-y-1">
                    <div className="text-sm font-medium text-[var(--rb-text)]">{owner.name}</div>
                    <div className="text-sm text-zinc-600">{owner.email}</div>
                    <div className="text-xs text-zinc-500">User ID: {owner.id}</div>
                  </div>
                ) : (
                  <div className="text-sm text-zinc-600">No owner found.</div>
                )}
              </CardContent>
            </Card>

            <Card className="shadow-none">
              <CardHeader>
                <CardTitle>Actions</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                  <div>
                    <div className="text-sm font-medium text-[var(--rb-text)]">Reason (optional)</div>
                    <div className="mt-1">
                      <Input value={reason} onChange={(e) => setReason(e.target.value)} placeholder="Ticket ID / reason" />
                    </div>
                  </div>

                  <div>
                    <div className="text-sm font-medium text-[var(--rb-text)]">Close retention days (optional)</div>
                    <div className="mt-1">
                      <Input value={closeRetentionDays} onChange={(e) => setCloseRetentionDays(e.target.value)} placeholder="e.g. 30" inputMode="numeric" />
                    </div>
                  </div>
                </div>

                <div className="flex flex-wrap gap-2">
                  <Button variant="secondary" size="sm" onClick={onSuspend} disabled={!!actionBusy || tenant.status === "closed"}>
                    {actionBusy === "suspend" ? "Suspending..." : "Suspend"}
                  </Button>
                  <Button variant="outline" size="sm" onClick={onUnsuspend} disabled={!!actionBusy || tenant.status !== "suspended"}>
                    {actionBusy === "unsuspend" ? "Unsuspending..." : "Unsuspend"}
                  </Button>
                  <Button variant="outline" size="sm" onClick={onClose} disabled={!!actionBusy || tenant.status === "closed"}>
                    {actionBusy === "close" ? "Closing..." : "Close"}
                  </Button>
                </div>

                <div className="border-t border-[var(--rb-border)] pt-4">
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Force-reset owner password</div>
                  <div className="mt-1 text-sm text-zinc-600">
                    This will immediately change the owner password. The new password is shown once below. Treat it as a secret.
                  </div>

                  <div className="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div>
                      <div className="text-sm font-medium text-[var(--rb-text)]">Set a specific password (optional)</div>
                      <div className="mt-1">
                        <Input
                          value={resetPasswordInput}
                          onChange={(e) => setResetPasswordInput(e.target.value)}
                          placeholder="Leave blank to generate"
                          type="password"
                        />
                      </div>
                    </div>

                    <div className="flex items-end">
                      <Button
                        variant="primary"
                        size="sm"
                        onClick={onResetOwnerPassword}
                        disabled={!!actionBusy || !owner || tenant.status === "closed"}
                      >
                        {actionBusy === "reset-owner-password" ? "Resetting..." : "Reset owner password"}
                      </Button>
                    </div>
                  </div>

                  {resetResult ? (
                    <div className="mt-4 space-y-2">
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
                    </div>
                  ) : null}
                </div>
              </CardContent>
            </Card>
          </div>
        ) : null}
      </div>
    </RequireAuth>
  );
}
