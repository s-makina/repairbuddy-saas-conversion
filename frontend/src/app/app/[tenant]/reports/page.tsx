"use client";

import React from "react";
import { useParams } from "next/navigation";
import { Button } from "@/components/ui/Button";
import { Badge } from "@/components/ui/Badge";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { ListPageShell } from "@/components/shells/ListPageShell";
import { mockApi } from "@/mock/mockApi";
import type { Expense, Job, Payment, TimeLog } from "@/mock/types";

type ReportRow = {
  key: string;
  label: string;
  value: number;
  meta?: string;
};

export default function TenantReportsPage() {
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [rows, setRows] = React.useState<ReportRow[]>([]);

  React.useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);

        const [jobs, payments, expenses, timeLogs] = await Promise.all([
          mockApi.listJobs(),
          mockApi.listPayments(),
          mockApi.listExpenses(),
          mockApi.listTimeLogs(),
        ]);

        if (!alive) return;

        const j = Array.isArray(jobs) ? (jobs as Job[]) : [];
        const p = Array.isArray(payments) ? (payments as Payment[]) : [];
        const e = Array.isArray(expenses) ? (expenses as Expense[]) : [];
        const t = Array.isArray(timeLogs) ? (timeLogs as TimeLog[]) : [];

        const totalPaidCents = p.filter((x) => x.status === "paid").reduce((sum, x) => sum + x.amount.amount_cents, 0);
        const totalExpenseCents = e.reduce((sum, x) => sum + x.amount.amount_cents, 0);
        const totalMinutes = t.reduce((sum, x) => sum + x.minutes, 0);

        setRows([
          { key: "jobs", label: "Jobs", value: j.length },
          { key: "payments", label: "Payments", value: p.length, meta: "count" },
          { key: "paid", label: "Paid (cents)", value: totalPaidCents, meta: "sum" },
          { key: "expenses", label: "Expenses (cents)", value: totalExpenseCents, meta: "sum" },
          { key: "time", label: "Time logged (minutes)", value: totalMinutes, meta: "sum" },
        ]);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load reports.");
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

  const columns = React.useMemo<Array<DataTableColumn<ReportRow>>>(
    () => [
      {
        id: "label",
        header: "Metric",
        cell: (row) => (
          <div className="min-w-0">
            <div className="truncate font-semibold text-[var(--rb-text)]">{row.label}</div>
            <div className="truncate text-xs text-zinc-600">{row.key}</div>
          </div>
        ),
        className: "min-w-[260px]",
      },
      {
        id: "value",
        header: "Value",
        cell: (row) => <div className="text-sm font-semibold text-[var(--rb-text)]">{row.value.toLocaleString()}</div>,
        className: "whitespace-nowrap",
      },
      {
        id: "meta",
        header: "Type",
        cell: (row) => <Badge variant="default">{row.meta ?? "—"}</Badge>,
        className: "whitespace-nowrap",
      },
    ],
    [],
  );

  return (
    <ListPageShell
      title="Reports"
      description="Lightweight overview metrics (mock)."
      actions={
        <Button disabled variant="outline" size="sm">
          Export
        </Button>
      }
      loading={loading}
      error={error}
      empty={!loading && !error && rows.length === 0}
      emptyTitle="No report data"
      emptyDescription="Mock data will populate once fixtures exist."
    >
      <DataTable
        title={typeof tenantSlug === "string" ? `Reports · ${tenantSlug}` : "Reports"}
        data={rows}
        loading={loading}
        emptyMessage="No report rows."
        columns={columns}
        getRowId={(row) => row.key}
      />
    </ListPageShell>
  );
}
