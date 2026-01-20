"use client";

import React, { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { useParams } from "next/navigation";
import { RequireAuth } from "@/components/RequireAuth";
import { useDashboardHeader } from "@/components/DashboardShell";
import { Alert } from "@/components/ui/Alert";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/Card";
import { ConfirmDialog } from "@/components/ui/ConfirmDialog";
import { DataTable } from "@/components/ui/DataTable";
import {
  createInvoiceFromSubscription,
  downloadInvoicePdf,
  getTenantInvoices,
  getTenantSubscriptions,
  issueInvoice,
  markInvoicePaid,
} from "@/lib/billing";
import { formatDateTime } from "@/lib/datetime";
import { formatMoney } from "@/lib/money";
import type { Invoice, Tenant, TenantSubscription } from "@/lib/types";

export default function AdminTenantInvoicesPage() {
  const params = useParams<{ tenant: string }>();
  const tenantId = Number(params.tenant);
  const dashboardHeader = useDashboardHeader();

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [tenant, setTenant] = useState<Tenant | null>(null);
  const [invoices, setInvoices] = useState<Invoice[]>([]);

  const [subsLoading, setSubsLoading] = useState(true);
  const [subsError, setSubsError] = useState<string | null>(null);
  const [subscriptions, setSubscriptions] = useState<TenantSubscription[]>([]);

  const [reloadNonce, setReloadNonce] = useState(0);

  const [busyRow, setBusyRow] = useState<Record<number, string | null>>({});

  const [createDraftBusy, setCreateDraftBusy] = useState(false);
  const [createDraftError, setCreateDraftError] = useState<string | null>(null);

  const [confirmIssue, setConfirmIssue] = useState<{ open: boolean; invoiceId: number | null }>({ open: false, invoiceId: null });
  const [confirmPaid, setConfirmPaid] = useState<{ open: boolean; invoiceId: number | null }>({ open: false, invoiceId: null });

  useEffect(() => {
    dashboardHeader.setHeader({
      breadcrumb: "Admin / Tenants",
      title: tenant ? `${tenant.name} — Invoices` : `Tenant ${tenantId} — Invoices`,
      subtitle: tenant ? `Tenant ID ${tenant.id} • ${tenant.slug}` : undefined,
      actions: (
        <div className="flex items-center gap-2">
          <Link href={`/admin/tenants/${tenantId}/billing`}>
            <Button variant="outline" size="sm">
              Back
            </Button>
          </Link>
          <Button variant="outline" size="sm" onClick={() => setReloadNonce((v) => v + 1)} disabled={loading || subsLoading}>
            Refresh
          </Button>
        </div>
      ),
    });

    return () => dashboardHeader.setHeader(null);
  }, [dashboardHeader, loading, subsLoading, tenant, tenantId]);

  const currentSubscription = useMemo(() => {
    const sorted = (Array.isArray(subscriptions) ? subscriptions : []).slice().sort((a, b) => (b.id ?? 0) - (a.id ?? 0));
    return sorted[0] ?? null;
  }, [subscriptions]);

  useEffect(() => {
    let alive = true;

    async function load() {
      if (!Number.isFinite(tenantId) || tenantId <= 0) {
        setError("Invalid tenant id.");
        setLoading(false);
        return;
      }

      try {
        setLoading(true);
        setError(null);

        const res = await getTenantInvoices({ tenantId, limit: 100 });
        if (!alive) return;

        setTenant(res.tenant);
        setInvoices(Array.isArray(res.invoices) ? res.invoices : []);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load invoices.");
        setInvoices([]);
      } finally {
        if (!alive) return;
        setLoading(false);
      }
    }

    void load();

    return () => {
      alive = false;
    };
  }, [tenantId, reloadNonce]);

  useEffect(() => {
    let alive = true;

    async function loadSubs() {
      if (!Number.isFinite(tenantId) || tenantId <= 0) return;

      try {
        setSubsLoading(true);
        setSubsError(null);

        const res = await getTenantSubscriptions(tenantId);
        if (!alive) return;

        setSubscriptions(Array.isArray(res.subscriptions) ? res.subscriptions : []);
      } catch (e) {
        if (!alive) return;
        setSubsError(e instanceof Error ? e.message : "Failed to load subscriptions.");
        setSubscriptions([]);
      } finally {
        if (!alive) return;
        setSubsLoading(false);
      }
    }

    void loadSubs();

    return () => {
      alive = false;
    };
  }, [tenantId, reloadNonce]);

  async function onCreateDraft() {
    if (!currentSubscription) {
      setCreateDraftError("No active subscription found. Assign a subscription first.");
      return;
    }

    if (createDraftBusy) return;

    setCreateDraftBusy(true);
    setCreateDraftError(null);

    try {
      await createInvoiceFromSubscription({ tenantId, tenantSubscriptionId: currentSubscription.id });
      setReloadNonce((v) => v + 1);
    } catch (e) {
      setCreateDraftError(e instanceof Error ? e.message : "Failed to create invoice draft.");
    } finally {
      setCreateDraftBusy(false);
    }
  }

  async function onDownload(inv: Invoice) {
    const id = inv.id;
    setBusyRow((prev) => ({ ...prev, [id]: "download" }));
    try {
      await downloadInvoicePdf({ tenantId, invoiceId: inv.id, invoiceNumber: inv.invoice_number });
    } finally {
      setBusyRow((prev) => ({ ...prev, [id]: null }));
    }
  }

  async function onIssue(invoiceId: number) {
    setBusyRow((prev) => ({ ...prev, [invoiceId]: "issue" }));
    try {
      await issueInvoice({ tenantId, invoiceId });
      setReloadNonce((v) => v + 1);
    } catch (e) {
      setError(e instanceof Error ? e.message : "Failed to issue invoice.");
    } finally {
      setBusyRow((prev) => ({ ...prev, [invoiceId]: null }));
    }
  }

  async function onPaid(invoiceId: number) {
    setBusyRow((prev) => ({ ...prev, [invoiceId]: "paid" }));
    try {
      await markInvoicePaid({ tenantId, invoiceId });
      setReloadNonce((v) => v + 1);
    } catch (e) {
      setError(e instanceof Error ? e.message : "Failed to mark invoice as paid.");
    } finally {
      setBusyRow((prev) => ({ ...prev, [invoiceId]: null }));
    }
  }

  const rows = useMemo(() => invoices, [invoices]);

  const statusVariant = (status: string) => {
    if (status === "paid") return "success" as const;
    if (status === "issued") return "info" as const;
    if (status === "draft") return "default" as const;
    return "default" as const;
  };

  return (
    <RequireAuth requiredPermission="admin.billing.read">
      <div className="space-y-6">
        {error ? (
          <Alert variant="danger" title="Could not load invoices">
            {error}
          </Alert>
        ) : null}

        {subsError ? (
          <Alert variant="danger" title="Could not load subscriptions">
            {subsError}
          </Alert>
        ) : null}

        <Card>
          <CardHeader className="flex flex-row items-start justify-between gap-4">
            <div className="min-w-0">
              <CardTitle className="truncate">Invoice actions</CardTitle>
              <div className="mt-1 text-sm text-zinc-600">Create drafts from the current subscription, then issue/mark paid.</div>
            </div>
            <Button variant="secondary" size="sm" onClick={() => void onCreateDraft()} disabled={createDraftBusy || subsLoading}>
              {createDraftBusy ? "Creating…" : "Create invoice draft"}
            </Button>
          </CardHeader>
          <CardContent>
            {createDraftError ? <div className="text-sm text-red-600">{createDraftError}</div> : null}
            <div className="mt-2 text-sm text-zinc-600">
              Current subscription: {currentSubscription ? `#${currentSubscription.id} (${currentSubscription.status})` : "—"}
            </div>
          </CardContent>
        </Card>

        <DataTable
          title="Invoices"
          data={rows}
          loading={loading}
          emptyMessage="No invoices yet."
          getRowId={(inv) => inv.id}
          columns={[
            {
              id: "number",
              header: "Invoice",
              cell: (inv) => (
                <div className="min-w-0">
                  <div className="truncate text-sm font-medium text-zinc-800">{inv.invoice_number}</div>
                  <div className="truncate text-xs text-zinc-500">ID: {inv.id}</div>
                </div>
              ),
              className: "min-w-[240px]",
            },
            {
              id: "status",
              header: "Status",
              cell: (inv) => <Badge variant={statusVariant(inv.status)}>{inv.status}</Badge>,
              className: "whitespace-nowrap",
            },
            {
              id: "issued",
              header: "Issued",
              cell: (inv) => <div className="text-sm text-zinc-700">{formatDateTime(inv.issued_at ?? null)}</div>,
              className: "whitespace-nowrap",
            },
            {
              id: "total",
              header: "Total",
              cell: (inv) => <div className="text-sm text-zinc-700">{formatMoney({ amountCents: inv.total_cents, currency: inv.currency })}</div>,
              className: "whitespace-nowrap",
            },
            {
              id: "actions",
              header: "",
              cell: (inv) => {
                const busy = busyRow[inv.id] ?? null;
                const canIssue = inv.status === "draft";
                const canPaid = inv.status === "issued";

                return (
                  <div className="flex items-center justify-end gap-2">
                    <Link href={`/admin/tenants/${tenantId}/billing/invoices/${inv.id}`}>
                      <Button variant="outline" size="sm">
                        View
                      </Button>
                    </Link>
                    <Button variant="outline" size="sm" onClick={() => void onDownload(inv)} disabled={busy === "download"}>
                      {busy === "download" ? "Downloading…" : "PDF"}
                    </Button>
                    <Button
                      variant="outline"
                      size="sm"
                      disabled={!canIssue || busy === "issue"}
                      onClick={() => setConfirmIssue({ open: true, invoiceId: inv.id })}
                    >
                      {busy === "issue" ? "Issuing…" : "Issue"}
                    </Button>
                    <Button
                      variant="primary"
                      size="sm"
                      disabled={!canPaid || busy === "paid"}
                      onClick={() => setConfirmPaid({ open: true, invoiceId: inv.id })}
                    >
                      {busy === "paid" ? "Marking…" : "Paid"}
                    </Button>
                  </div>
                );
              },
              className: "whitespace-nowrap",
            },
          ]}
        />

        <ConfirmDialog
          open={confirmIssue.open}
          title="Issue invoice"
          message="This will transition the invoice from draft to issued."
          confirmText="Issue"
          confirmVariant="secondary"
          busy={!!confirmIssue.invoiceId && busyRow[confirmIssue.invoiceId] === "issue"}
          onCancel={() => setConfirmIssue({ open: false, invoiceId: null })}
          onConfirm={() => {
            const id = confirmIssue.invoiceId;
            setConfirmIssue({ open: false, invoiceId: null });
            if (id) void onIssue(id);
          }}
        />

        <ConfirmDialog
          open={confirmPaid.open}
          title="Mark invoice as paid"
          message="This will mark the invoice as paid."
          confirmText="Mark paid"
          confirmVariant="primary"
          busy={!!confirmPaid.invoiceId && busyRow[confirmPaid.invoiceId] === "paid"}
          onCancel={() => setConfirmPaid({ open: false, invoiceId: null })}
          onConfirm={() => {
            const id = confirmPaid.invoiceId;
            setConfirmPaid({ open: false, invoiceId: null });
            if (id) void onPaid(id);
          }}
        />
      </div>
    </RequireAuth>
  );
}
