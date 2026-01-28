"use client";

import React from "react";
import { useParams } from "next/navigation";
import { Button } from "@/components/ui/Button";
import { Badge } from "@/components/ui/Badge";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { ListPageShell } from "@/components/shells/ListPageShell";
import { mockApi } from "@/mock/mockApi";
import type { Job, Payment } from "@/mock/types";
import { formatMoney } from "@/lib/money";

function statusVariant(status: Payment["status"]): "default" | "info" | "success" | "warning" | "danger" {
  if (status === "paid") return "success";
  if (status === "refunded") return "warning";
  if (status === "failed") return "danger";
  return "default";
}

export default function TenantPaymentsPage() {
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [payments, setPayments] = React.useState<Payment[]>([]);
  const [jobs, setJobs] = React.useState<Job[]>([]);

  React.useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);
        const [p, j] = await Promise.all([mockApi.listPayments(), mockApi.listJobs()]);
        if (!alive) return;
        setPayments(Array.isArray(p) ? p : []);
        setJobs(Array.isArray(j) ? j : []);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load payments.");
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

  const jobById = React.useMemo(() => new Map(jobs.map((j) => [j.id, j])), [jobs]);

  const columns = React.useMemo<Array<DataTableColumn<Payment>>>(
    () => [
      {
        id: "id",
        header: "Payment",
        cell: (row) => (
          <div className="min-w-0">
            <div className="truncate font-semibold text-[var(--rb-text)]">{row.id}</div>
            <div className="truncate text-xs text-zinc-600">{jobById.get(row.job_id)?.case_number ?? row.job_id}</div>
          </div>
        ),
        className: "min-w-[240px]",
      },
      {
        id: "amount",
        header: "Amount",
        cell: (row) => (
          <div className="text-sm font-semibold text-[var(--rb-text)] whitespace-nowrap">
            {formatMoney({ amountCents: row.amount.amount_cents, currency: row.amount.currency })}
          </div>
        ),
        className: "whitespace-nowrap",
      },
      {
        id: "method",
        header: "Method",
        cell: (row) => <div className="text-sm text-zinc-700">{row.method.replace(/_/g, " ")}</div>,
        className: "whitespace-nowrap",
      },
      {
        id: "status",
        header: "Status",
        cell: (row) => <Badge variant={statusVariant(row.status)}>{row.status}</Badge>,
        className: "whitespace-nowrap",
      },
      {
        id: "created",
        header: "Created",
        cell: (row) => <div className="text-sm text-zinc-600">{new Date(row.created_at).toLocaleString()}</div>,
        className: "whitespace-nowrap",
      },
    ],
    [jobById],
  );

  return (
    <ListPageShell
      title="Payments"
      description="Record and track payments for jobs."
      actions={
        <Button disabled variant="outline" size="sm">
          New payment
        </Button>
      }
      loading={loading}
      error={error}
      empty={!loading && !error && payments.length === 0}
      emptyTitle="No payments"
      emptyDescription="Payments recorded against jobs will show here."
    >
      <DataTable
        title={typeof tenantSlug === "string" ? `Payments · ${tenantSlug}` : "Payments"}
        data={payments}
        loading={loading}
        emptyMessage="No payments."
        columns={columns}
        getRowId={(row) => row.id}
      />
    </ListPageShell>
  );
}
