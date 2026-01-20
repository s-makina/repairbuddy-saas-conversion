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
import { DataTable } from "@/components/ui/DataTable";
import { downloadInvoicePdf, getTenantInvoice } from "@/lib/billing";
import { formatDateTime } from "@/lib/datetime";
import { formatMoney } from "@/lib/money";
import type { Invoice, InvoiceLine, Tenant } from "@/lib/types";

export default function AdminTenantInvoiceDetailPage() {
  const params = useParams<{ tenant: string; invoiceId: string }>();
  const tenantId = Number(params.tenant);
  const invoiceId = Number(params.invoiceId);

  const dashboardHeader = useDashboardHeader();

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [tenant, setTenant] = useState<Tenant | null>(null);
  const [invoice, setInvoice] = useState<Invoice | null>(null);
  const [downloadBusy, setDownloadBusy] = useState(false);
  const [reloadNonce, setReloadNonce] = useState(0);

  useEffect(() => {
    dashboardHeader.setHeader({
      breadcrumb: "Admin / Tenants",
      title: invoice ? invoice.invoice_number : `Invoice ${invoiceId}`,
      subtitle: tenant ? `${tenant.name} • ${tenant.slug}` : undefined,
      actions: (
        <div className="flex items-center gap-2">
          <Link href={`/admin/tenants/${tenantId}/billing/invoices`}>
            <Button variant="outline" size="sm">
              Back
            </Button>
          </Link>
          <Button variant="outline" size="sm" onClick={() => setReloadNonce((v) => v + 1)} disabled={loading}>
            Refresh
          </Button>
          <Button
            variant="secondary"
            size="sm"
            onClick={async () => {
              if (!invoice) return;
              setDownloadBusy(true);
              try {
                await downloadInvoicePdf({ tenantId, invoiceId: invoice.id, invoiceNumber: invoice.invoice_number });
              } finally {
                setDownloadBusy(false);
              }
            }}
            disabled={downloadBusy || !invoice}
          >
            {downloadBusy ? "Downloading…" : "Download PDF"}
          </Button>
        </div>
      ),
    });

    return () => dashboardHeader.setHeader(null);
  }, [dashboardHeader, downloadBusy, invoice, invoiceId, loading, tenant, tenantId]);

  useEffect(() => {
    let alive = true;

    async function load() {
      if (!Number.isFinite(tenantId) || tenantId <= 0 || !Number.isFinite(invoiceId) || invoiceId <= 0) {
        setError("Invalid tenant/invoice id.");
        setLoading(false);
        return;
      }

      try {
        setLoading(true);
        setError(null);

        const res = await getTenantInvoice({ tenantId, invoiceId });
        if (!alive) return;

        setTenant(res.tenant);
        setInvoice(res.invoice);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load invoice.");
        setTenant(null);
        setInvoice(null);
      } finally {
        if (!alive) return;
        setLoading(false);
      }
    }

    void load();

    return () => {
      alive = false;
    };
  }, [invoiceId, reloadNonce, tenantId]);

  const lines = useMemo(() => (Array.isArray(invoice?.lines) ? invoice?.lines : []) as InvoiceLine[], [invoice]);

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
          <Alert variant="danger" title="Could not load invoice">
            {error}
          </Alert>
        ) : null}

        {invoice ? (
          <Card>
            <CardHeader className="flex flex-row items-start justify-between gap-4">
              <div className="min-w-0">
                <CardTitle className="truncate">Invoice summary</CardTitle>
                <div className="mt-1 text-sm text-zinc-600">Tenant: {tenant?.name ?? "—"}</div>
              </div>
              <Badge variant={statusVariant(invoice.status)}>{invoice.status}</Badge>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                  <div className="text-xs text-zinc-500">Subtotal</div>
                  <div className="mt-1 text-sm text-zinc-800">{formatMoney({ amountCents: invoice.subtotal_cents, currency: invoice.currency })}</div>
                </div>
                <div>
                  <div className="text-xs text-zinc-500">Tax</div>
                  <div className="mt-1 text-sm text-zinc-800">{formatMoney({ amountCents: invoice.tax_cents, currency: invoice.currency })}</div>
                </div>
                <div>
                  <div className="text-xs text-zinc-500">Total</div>
                  <div className="mt-1 text-sm font-semibold text-zinc-900">{formatMoney({ amountCents: invoice.total_cents, currency: invoice.currency })}</div>
                </div>
                <div>
                  <div className="text-xs text-zinc-500">Issued</div>
                  <div className="mt-1 text-sm text-zinc-800">{formatDateTime(invoice.issued_at ?? null)}</div>
                </div>
                <div>
                  <div className="text-xs text-zinc-500">Paid</div>
                  <div className="mt-1 text-sm text-zinc-800">{formatDateTime(invoice.paid_at ?? null)}</div>
                </div>
                <div>
                  <div className="text-xs text-zinc-500">Seller → Buyer</div>
                  <div className="mt-1 text-sm text-zinc-800">
                    {invoice.seller_country} → {invoice.billing_country ?? "—"}
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        ) : null}

        <DataTable
          title="Line items"
          data={lines}
          loading={loading}
          emptyMessage="No line items found."
          getRowId={(l) => l.id}
          columns={[
            {
              id: "desc",
              header: "Description",
              cell: (l) => <div className="text-sm text-zinc-800">{l.description}</div>,
              className: "min-w-[260px]",
            },
            {
              id: "qty",
              header: "Qty",
              cell: (l) => <div className="text-sm text-zinc-700">{l.quantity}</div>,
              className: "whitespace-nowrap",
            },
            {
              id: "unit",
              header: "Unit",
              cell: (l) => <div className="text-sm text-zinc-700">{formatMoney({ amountCents: l.unit_amount_cents, currency: invoice?.currency ?? null })}</div>,
              className: "whitespace-nowrap",
            },
            {
              id: "tax",
              header: "Tax",
              cell: (l) => <div className="text-sm text-zinc-700">{formatMoney({ amountCents: l.tax_cents, currency: invoice?.currency ?? null })}</div>,
              className: "whitespace-nowrap",
            },
            {
              id: "total",
              header: "Total",
              cell: (l) => <div className="text-sm text-zinc-700">{formatMoney({ amountCents: l.total_cents, currency: invoice?.currency ?? null })}</div>,
              className: "whitespace-nowrap",
            },
          ]}
        />

        <Card>
          <CardHeader>
            <CardTitle>VAT / tax snapshot</CardTitle>
          </CardHeader>
          <CardContent>
            <pre className="max-h-[320px] overflow-auto rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-[var(--rb-surface-muted)] p-3 text-xs text-zinc-700">
              {JSON.stringify(invoice?.tax_details_json ?? null, null, 2)}
            </pre>
          </CardContent>
        </Card>
      </div>
    </RequireAuth>
  );
}
