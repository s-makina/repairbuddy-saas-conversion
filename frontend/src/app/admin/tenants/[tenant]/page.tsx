"use client";

import React, { useCallback, useEffect, useMemo, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { apiFetch } from "@/lib/api";
import type { Tenant, User } from "@/lib/types";
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

type MockSubscriptionStatus = "trial" | "active" | "past_due" | "canceled";

type MockInvoiceStatus = "paid" | "open" | "past_due" | "void";

type MockInvoice = {
  id: string;
  number: string;
  status: MockInvoiceStatus;
  amount_cents: number;
  currency: string;
  issued_at: string;
  due_at: string | null;
};

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
  const dashboardHeader = useDashboardHeader();

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

  const formatMoney = useMemo(() => {
    return (amountCents: number, currency: string) => {
      const amount = (Number.isFinite(amountCents) ? amountCents : 0) / 100;
      try {
        return new Intl.NumberFormat(undefined, {
          style: "currency",
          currency: currency || "USD",
        }).format(amount);
      } catch {
        return `${amount.toFixed(2)} ${currency || "USD"}`;
      }
    };
  }, []);

  const billing = useMemo(() => {
    const subscription: {
      status: MockSubscriptionStatus;
      plan_name: string;
      seats_included: number;
      seats_used: number;
      current_period_start: string;
      current_period_end: string;
      renewal_amount_cents: number;
      currency: string;
      cancel_at_period_end: boolean;
    } = {
      status: "active",
      plan_name: "Pro",
      seats_included: 5,
      seats_used: 3,
      current_period_start: "2026-01-01T00:00:00Z",
      current_period_end: "2026-02-01T00:00:00Z",
      renewal_amount_cents: 4900,
      currency: "USD",
      cancel_at_period_end: false,
    };

    const paymentMethod: {
      brand: string;
      last4: string;
      exp_month: number;
      exp_year: number;
      billing_email: string;
    } = {
      brand: "Visa",
      last4: "4242",
      exp_month: 10,
      exp_year: 2027,
      billing_email: tenant?.contact_email ?? "billing@company.com",
    };

    const invoices: MockInvoice[] = [
      {
        id: `inv_${tenantId}_1003`,
        number: `RB-${tenantId}-1003`,
        status: "open",
        amount_cents: 4900,
        currency: "USD",
        issued_at: "2026-01-01T00:00:00Z",
        due_at: "2026-01-10T00:00:00Z",
      },
      {
        id: `inv_${tenantId}_1002`,
        number: `RB-${tenantId}-1002`,
        status: "paid",
        amount_cents: 4900,
        currency: "USD",
        issued_at: "2025-12-01T00:00:00Z",
        due_at: null,
      },
      {
        id: `inv_${tenantId}_1001`,
        number: `RB-${tenantId}-1001`,
        status: "paid",
        amount_cents: 4900,
        currency: "USD",
        issued_at: "2025-11-01T00:00:00Z",
        due_at: null,
      },
    ];

    const upcomingInvoice = {
      subtotal_cents: 4900,
      tax_cents: 0,
      total_cents: 4900,
      currency: "USD",
      next_attempt_at: "2026-02-01T00:00:00Z",
    };

    return { subscription, paymentMethod, invoices, upcomingInvoice };
  }, [tenant?.contact_email, tenantId]);

  const billingStatusBadgeVariant = useMemo(() => {
    return (s: MockSubscriptionStatus) => {
      if (s === "active") return "success" as const;
      if (s === "trial") return "info" as const;
      if (s === "past_due") return "warning" as const;
      return "default" as const;
    };
  }, []);

  const invoiceStatusBadgeVariant = useMemo(() => {
    return (s: MockInvoiceStatus) => {
      if (s === "paid") return "success" as const;
      if (s === "open") return "info" as const;
      if (s === "past_due") return "warning" as const;
      if (s === "void") return "default" as const;
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

  useEffect(() => {
    dashboardHeader.setHeader({
      breadcrumb: "Admin / Tenants",
      title: tenant ? tenant.name : `Tenant ${tenantId}`,
      subtitle: tenant ? `Tenant ID ${tenant.id} • ${tenant.slug}` : `Tenant ID ${tenantId}`,
      actions: (
        <>
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
                  <TabsTrigger value="billing">Billing</TabsTrigger>
                  <TabsTrigger value="support">Support</TabsTrigger>
                  <TabsTrigger value="security">Security</TabsTrigger>
                </TabsList>

                <TabsContent value="overview">
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
                </TabsContent>

                <TabsContent value="billing">
                  <Card>
                    <CardHeader>
                      <div className="flex items-start justify-between gap-3">
                        <div className="min-w-0">
                          <CardTitle>Subscription &amp; Billing</CardTitle>
                          <CardDescription>Mock billing information (UI only, no live provider yet).</CardDescription>
                        </div>
                        <div className="flex items-center gap-2">
                          <Button variant="outline" size="sm" onClick={() => window.alert("Billing portal (mock)")}>
                            Open billing portal
                          </Button>
                        </div>
                      </div>
                    </CardHeader>
                    <CardContent>
                      <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                        <Card className="bg-[var(--rb-surface-muted)]">
                          <CardContent className="pt-5">
                            <div className="flex items-start justify-between gap-3">
                              <div className="min-w-0">
                                <div className="text-xs font-medium tracking-wide text-zinc-500">Subscription</div>
                                <div className="mt-1 truncate text-xl font-semibold text-[var(--rb-text)]">{billing.subscription.plan_name}</div>
                                <div className="mt-1 text-sm text-zinc-600">
                                  {billing.subscription.seats_used}/{billing.subscription.seats_included} seats used
                                </div>
                              </div>
                              <Badge variant={billingStatusBadgeVariant(billing.subscription.status)}>{billing.subscription.status}</Badge>
                            </div>
                            <div className="mt-4 grid grid-cols-2 gap-3">
                              <div>
                                <div className="text-xs font-medium text-zinc-500">Period start</div>
                                <div className="text-sm text-zinc-700">{formatDateTime(billing.subscription.current_period_start)}</div>
                              </div>
                              <div>
                                <div className="text-xs font-medium text-zinc-500">Period end</div>
                                <div className="text-sm text-zinc-700">{formatDateTime(billing.subscription.current_period_end)}</div>
                              </div>
                              <div>
                                <div className="text-xs font-medium text-zinc-500">Renewal</div>
                                <div className="text-sm text-zinc-700">
                                  {formatMoney(billing.subscription.renewal_amount_cents, billing.subscription.currency)} / month
                                </div>
                              </div>
                              <div>
                                <div className="text-xs font-medium text-zinc-500">Cancel</div>
                                <div className="text-sm text-zinc-700">{billing.subscription.cancel_at_period_end ? "At period end" : "No"}</div>
                              </div>
                            </div>
                            <div className="mt-4 flex items-center gap-2">
                              <Button variant="outline" size="sm" onClick={() => window.alert("Change plan (mock)")}>Change plan</Button>
                              <Button variant="outline" size="sm" onClick={() => window.alert("Cancel subscription (mock)")}>Cancel</Button>
                            </div>
                          </CardContent>
                        </Card>

                        <Card className="bg-[var(--rb-surface-muted)]">
                          <CardContent className="pt-5">
                            <div className="flex items-start justify-between gap-3">
                              <div className="min-w-0">
                                <div className="text-xs font-medium tracking-wide text-zinc-500">Payment method</div>
                                <div className="mt-1 truncate text-xl font-semibold text-[var(--rb-text)]">
                                  {billing.paymentMethod.brand} •••• {billing.paymentMethod.last4}
                                </div>
                                <div className="mt-1 text-sm text-zinc-600">
                                  Expires {String(billing.paymentMethod.exp_month).padStart(2, "0")}/{billing.paymentMethod.exp_year}
                                </div>
                              </div>
                              <Badge variant="default">default</Badge>
                            </div>
                            <div className="mt-4">
                              <div className="text-xs font-medium text-zinc-500">Billing email</div>
                              <div className="text-sm text-zinc-700">{billing.paymentMethod.billing_email}</div>
                            </div>
                            <div className="mt-4 flex items-center gap-2">
                              <Button variant="outline" size="sm" onClick={() => window.alert("Update payment method (mock)")}>Update</Button>
                              <Button variant="outline" size="sm" onClick={() => window.alert("Add payment method (mock)")}>Add</Button>
                            </div>
                          </CardContent>
                        </Card>

                        <Card className="bg-[var(--rb-surface-muted)]">
                          <CardContent className="pt-5">
                            <div className="flex items-start justify-between gap-3">
                              <div className="min-w-0">
                                <div className="text-xs font-medium tracking-wide text-zinc-500">Upcoming invoice</div>
                                <div className="mt-1 truncate text-xl font-semibold text-[var(--rb-text)]">
                                  {formatMoney(billing.upcomingInvoice.total_cents, billing.upcomingInvoice.currency)}
                                </div>
                                <div className="mt-1 text-sm text-zinc-600">Next charge: {formatDateTime(billing.upcomingInvoice.next_attempt_at)}</div>
                              </div>
                              <Badge variant="info">preview</Badge>
                            </div>

                            <div className="mt-4 grid grid-cols-2 gap-3">
                              <div>
                                <div className="text-xs font-medium text-zinc-500">Subtotal</div>
                                <div className="text-sm text-zinc-700">{formatMoney(billing.upcomingInvoice.subtotal_cents, billing.upcomingInvoice.currency)}</div>
                              </div>
                              <div>
                                <div className="text-xs font-medium text-zinc-500">Tax</div>
                                <div className="text-sm text-zinc-700">{formatMoney(billing.upcomingInvoice.tax_cents, billing.upcomingInvoice.currency)}</div>
                              </div>
                            </div>

                            <div className="mt-4 flex items-center gap-2">
                              <Button variant="outline" size="sm" onClick={() => window.alert("Download upcoming invoice (mock)")}>Download</Button>
                              <Button variant="outline" size="sm" onClick={() => window.alert("Update billing details (mock)")}>Billing details</Button>
                            </div>
                          </CardContent>
                        </Card>
                      </div>

                      <div className="mt-4">
                        <DataTable
                          title="Invoices"
                          data={billing.invoices}
                          loading={false}
                          emptyMessage="No invoices."
                          getRowId={(i) => i.id}
                          search={{
                            placeholder: "Search invoice number...",
                            getSearchText: (i) => `${i.number} ${i.status}`,
                          }}
                          columns={[
                            {
                              id: "number",
                              header: "Invoice",
                              cell: (i) => (
                                <div className="min-w-0">
                                  <div className="truncate font-semibold text-[var(--rb-text)]">{i.number}</div>
                                  <div className="truncate text-xs text-zinc-600">Issued {formatDateTime(i.issued_at)}</div>
                                </div>
                              ),
                              className: "max-w-[320px]",
                            },
                            {
                              id: "status",
                              header: "Status",
                              cell: (i) => <Badge variant={invoiceStatusBadgeVariant(i.status)}>{i.status}</Badge>,
                              className: "whitespace-nowrap",
                            },
                            {
                              id: "due",
                              header: "Due",
                              cell: (i) => <div className="text-sm text-zinc-700">{i.due_at ? formatDateTime(i.due_at) : "—"}</div>,
                              className: "whitespace-nowrap",
                            },
                            {
                              id: "amount",
                              header: "Amount",
                              cell: (i) => <div className="text-sm font-medium text-zinc-700">{formatMoney(i.amount_cents, i.currency)}</div>,
                              className: "whitespace-nowrap",
                              headerClassName: "whitespace-nowrap",
                            },
                            {
                              id: "actions",
                              header: "",
                              cell: (i) => (
                                <div className="flex items-center justify-end gap-2">
                                  <Button variant="outline" size="sm" onClick={() => window.alert(`View invoice ${i.number} (mock)`)}>View</Button>
                                  <Button variant="outline" size="sm" onClick={() => window.alert(`Download invoice ${i.number} (mock)`)}>Download</Button>
                                </div>
                              ),
                              className: "whitespace-nowrap text-right",
                              headerClassName: "text-right",
                            },
                          ]}
                        />
                      </div>
                    </CardContent>
                  </Card>
                </TabsContent>

                <TabsContent value="support">
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
                </TabsContent>

                <TabsContent value="security">
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
      </div>
    </RequireAuth>
  );
}
