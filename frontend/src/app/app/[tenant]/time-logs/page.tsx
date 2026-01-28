"use client";

import React from "react";
import { useParams } from "next/navigation";
import { Button } from "@/components/ui/Button";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { ListPageShell } from "@/components/shells/ListPageShell";
import { mockApi } from "@/mock/mockApi";
import type { Job, TimeLog } from "@/mock/types";
import { formatMoney } from "@/lib/money";

type Row = {
  timeLog: TimeLog;
  job: Job | null;
  totalCents: number;
  currency: string;
};

export default function TenantTimeLogsPage() {
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [timeLogs, setTimeLogs] = React.useState<TimeLog[]>([]);
  const [jobs, setJobs] = React.useState<Job[]>([]);

  React.useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);
        const [t, j] = await Promise.all([mockApi.listTimeLogs(), mockApi.listJobs()]);
        if (!alive) return;
        setTimeLogs(Array.isArray(t) ? t : []);
        setJobs(Array.isArray(j) ? j : []);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load time logs.");
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

  const rows = React.useMemo<Row[]>(() => {
    return timeLogs.map((tl) => {
      const job = jobById.get(tl.job_id) ?? null;
      const totalCents = Math.round((tl.minutes / 60) * tl.rate.amount_cents);
      return {
        timeLog: tl,
        job,
        totalCents,
        currency: tl.rate.currency,
      };
    });
  }, [jobById, timeLogs]);

  const columns = React.useMemo<Array<DataTableColumn<Row>>>(
    () => [
      {
        id: "job",
        header: "Job",
        cell: (row) => (
          <div className="min-w-0">
            <div className="truncate font-semibold text-[var(--rb-text)]">{row.job?.case_number ?? row.timeLog.job_id}</div>
            <div className="truncate text-xs text-zinc-600">{row.timeLog.id}</div>
          </div>
        ),
        className: "min-w-[240px]",
      },
      {
        id: "user",
        header: "User",
        cell: (row) => <div className="text-sm text-zinc-700">{row.timeLog.user_label}</div>,
        className: "min-w-[200px]",
      },
      {
        id: "minutes",
        header: "Minutes",
        cell: (row) => <div className="text-sm text-zinc-700">{row.timeLog.minutes}</div>,
        className: "whitespace-nowrap",
      },
      {
        id: "rate",
        header: "Rate",
        cell: (row) => (
          <div className="text-sm font-semibold text-[var(--rb-text)] whitespace-nowrap">
            {formatMoney({ amountCents: row.timeLog.rate.amount_cents, currency: row.timeLog.rate.currency })} / hr
          </div>
        ),
        className: "whitespace-nowrap",
      },
      {
        id: "total",
        header: "Total",
        cell: (row) => (
          <div className="text-sm font-semibold text-[var(--rb-text)] whitespace-nowrap">
            {formatMoney({ amountCents: row.totalCents, currency: row.currency })}
          </div>
        ),
        className: "whitespace-nowrap",
      },
      {
        id: "created",
        header: "Created",
        cell: (row) => <div className="text-sm text-zinc-600">{new Date(row.timeLog.created_at).toLocaleString()}</div>,
        className: "whitespace-nowrap",
      },
    ],
    [],
  );

  return (
    <ListPageShell
      title="Time Logs"
      description="Logged time entries for jobs."
      actions={
        <Button disabled variant="outline" size="sm">
          Add time log
        </Button>
      }
      loading={loading}
      error={error}
      empty={!loading && !error && rows.length === 0}
      emptyTitle="No time logs"
      emptyDescription="Time entries created by technicians will show here."
    >
      <DataTable
        title={typeof tenantSlug === "string" ? `Time Logs · ${tenantSlug}` : "Time Logs"}
        data={rows}
        loading={loading}
        emptyMessage="No time logs."
        columns={columns}
        getRowId={(row) => row.timeLog.id}
      />
    </ListPageShell>
  );
}
