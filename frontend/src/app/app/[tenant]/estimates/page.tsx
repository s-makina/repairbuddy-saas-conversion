"use client";

import React from "react";
import { useParams, useRouter } from "next/navigation";
import { Button } from "@/components/ui/Button";
import { Badge } from "@/components/ui/Badge";
import { Card, CardContent } from "@/components/ui/Card";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { ListPageShell } from "@/components/shells/ListPageShell";
import { mockApi } from "@/mock/mockApi";
import type { Client, Estimate, Job } from "@/mock/types";
import { formatMoney } from "@/lib/money";

function estimateBadgeVariant(status: Estimate["status"]): "default" | "success" | "warning" | "danger" {
  if (status === "approved") return "success";
  if (status === "rejected") return "danger";
  return "warning";
}

export default function TenantEstimatesPage() {
  const router = useRouter();
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [estimates, setEstimates] = React.useState<Estimate[]>([]);
  const [jobs, setJobs] = React.useState<Job[]>([]);
  const [clients, setClients] = React.useState<Client[]>([]);
  const [q, setQ] = React.useState<string>("");

  const [pageIndex, setPageIndex] = React.useState(0);
  const [pageSize, setPageSize] = React.useState(10);

  React.useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);
        const [e, j, c] = await Promise.all([mockApi.listEstimates(), mockApi.listJobs(), mockApi.listClients()]);
        if (!alive) return;
        setEstimates(Array.isArray(e) ? e : []);
        setJobs(Array.isArray(j) ? j : []);
        setClients(Array.isArray(c) ? c : []);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load estimates.");
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
  const clientById = React.useMemo(() => new Map(clients.map((c) => [c.id, c])), [clients]);

  const rows = React.useMemo(() => {
    const needle = q.trim().toLowerCase();
    const mapped = estimates.map((e) => {
      const job = jobById.get(e.job_id) ?? null;
      const client = clientById.get(e.client_id) ?? null;
      const totalCents = mockApi.computeEstimateTotalCents(e);
      const currency = e.lines[0]?.unit_price.currency ?? "USD";
      return {
        estimate: e,
        job,
        client,
        totalCents,
        currency,
      };
    });

    if (!needle) return mapped;

    return mapped.filter((r) => {
      const hay = `${r.estimate.id} ${r.job?.case_number ?? ""} ${r.client?.name ?? ""} ${r.estimate.status}`.toLowerCase();
      return hay.includes(needle);
    });
  }, [clientById, estimates, jobById, q]);

  const totalRows = rows.length;
  const pageRows = React.useMemo(() => {
    const start = pageIndex * pageSize;
    const end = start + pageSize;
    return rows.slice(start, end);
  }, [pageIndex, pageSize, rows]);

  const columns = React.useMemo<
    Array<
      DataTableColumn<{
        estimate: Estimate;
        job: Job | null;
        client: Client | null;
        totalCents: number;
        currency: string;
      }>
    >
  >(
    () => [
      {
        id: "id",
        header: "Estimate",
        cell: (row) => (
          <div className="min-w-0">
            <div className="truncate font-semibold text-[var(--rb-text)]">{row.estimate.id}</div>
            <div className="truncate text-xs text-zinc-600">{row.job?.case_number ?? row.estimate.job_id}</div>
          </div>
        ),
        className: "min-w-[220px]",
      },
      {
        id: "client",
        header: "Client",
        cell: (row) => <div className="text-sm text-zinc-700">{row.client?.name ?? row.estimate.client_id}</div>,
        className: "min-w-[220px]",
      },
      {
        id: "status",
        header: "Status",
        cell: (row) => <Badge variant={estimateBadgeVariant(row.estimate.status)}>{row.estimate.status}</Badge>,
        className: "whitespace-nowrap",
      },
      {
        id: "total",
        header: "Total",
        cell: (row) => <div className="text-sm font-semibold text-[var(--rb-text)]">{formatMoney({ amountCents: row.totalCents, currency: row.currency })}</div>,
        className: "whitespace-nowrap",
      },
      {
        id: "updated",
        header: "Updated",
        cell: (row) => <div className="text-sm text-zinc-600">{new Date(row.estimate.updated_at).toLocaleString()}</div>,
        className: "whitespace-nowrap",
      },
    ],
    [],
  );

  return (
    <ListPageShell
      title="Estimates"
      description="Create, send, and track customer approvals."
      actions={
        <Button disabled variant="outline" size="sm">
          New estimate
        </Button>
      }
      filters={
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div className="text-sm font-semibold text-[var(--rb-text)]">Search</div>
          <input
            value={q}
            onChange={(e) => setQ(e.target.value)}
            placeholder="Search estimate, case, client, status..."
            className="h-10 w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 text-sm sm:max-w-[420px]"
          />
        </div>
      }
      loading={loading}
      error={error}
      empty={!loading && !error && rows.length === 0}
      emptyTitle="No estimates found"
      emptyDescription="Try adjusting your search."
    >
      <Card className="shadow-none">
        <CardContent className="pt-5">
          <DataTable
            title={typeof tenantSlug === "string" ? `Estimates · ${tenantSlug}` : "Estimates"}
            data={pageRows}
            loading={loading}
            emptyMessage="No estimates."
            columns={columns}
            getRowId={(row) => row.estimate.id}
            server={{
              query: q,
              onQueryChange: (value) => {
                setQ(value);
                setPageIndex(0);
              },
              pageIndex,
              onPageIndexChange: setPageIndex,
              pageSize,
              onPageSizeChange: (value) => {
                setPageSize(value);
                setPageIndex(0);
              },
              totalRows,
            }}
            onRowClick={(row) => {
              if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
              router.push(`/app/${tenantSlug}/estimates/${row.estimate.id}`);
            }}
          />
        </CardContent>
      </Card>
    </ListPageShell>
  );
}
