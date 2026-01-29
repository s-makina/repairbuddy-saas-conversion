"use client";

import React from "react";
import { useParams, useRouter } from "next/navigation";
import { Button } from "@/components/ui/Button";
import { Badge } from "@/components/ui/Badge";
import { Card, CardContent } from "@/components/ui/Card";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { ListPageShell } from "@/components/shells/ListPageShell";
import { apiFetch } from "@/lib/api";

type JobStatusKey = string;

type ApiJob = {
  id: number;
  case_number: string;
  title: string;
  status: JobStatusKey;
  updated_at: string;
};

type ApiJobStatus = {
  id: number;
  slug: string;
  label: string;
};

function statusBadgeVariant(status: JobStatusKey): "default" | "info" | "success" | "warning" | "danger" {
  if (status === "delivered" || status === "completed") return "success";
  if (status === "ready") return "warning";
  if (status === "cancelled") return "danger";
  if (status === "in_process") return "info";
  return "default";
}

export default function TenantJobsPage() {
  const router = useRouter();
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [jobs, setJobs] = React.useState<ApiJob[]>([]);
  const [q, setQ] = React.useState<string>("");
  const [statusLabels, setStatusLabels] = React.useState<Record<string, string>>({});

  const [pageIndex, setPageIndex] = React.useState(0);
  const [pageSize, setPageSize] = React.useState(10);

  React.useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);

        if (typeof tenantSlug !== "string" || tenantSlug.length === 0) {
          throw new Error("Business is missing.");
        }

        const [jobsRes, statusesRes] = await Promise.all([
          apiFetch<{ jobs: ApiJob[] }>(`/api/${tenantSlug}/app/repairbuddy/jobs`),
          apiFetch<{ job_statuses: ApiJobStatus[] }>(`/api/${tenantSlug}/app/repairbuddy/job-statuses`),
        ]);
        if (!alive) return;

        setJobs(Array.isArray(jobsRes.jobs) ? jobsRes.jobs : []);
        const next: Record<string, string> = {};

        for (const s of Array.isArray(statusesRes.job_statuses) ? statusesRes.job_statuses : []) {
          next[s.slug] = s.label;
        }
        setStatusLabels(next);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load jobs.");
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

  const filtered = React.useMemo(() => {
    const needle = q.trim().toLowerCase();
    if (!needle) return jobs;
    return jobs.filter((j) => {
      const hay = `${j.case_number} ${j.id} ${j.title}`.toLowerCase();
      return hay.includes(needle);
    });
  }, [jobs, q]);

  const totalRows = filtered.length;
  const pageRows = React.useMemo(() => {
    const start = pageIndex * pageSize;
    const end = start + pageSize;
    return filtered.slice(start, end);
  }, [filtered, pageIndex, pageSize]);

  const columns = React.useMemo<Array<DataTableColumn<ApiJob>>>(
    () => [
      {
        id: "case",
        header: "Case",
        cell: (row) => (
          <div className="min-w-0">
            <div className="truncate font-semibold text-[var(--rb-text)]">{row.case_number}</div>
            <div className="truncate text-xs text-zinc-600">{row.id}</div>
          </div>
        ),
        className: "max-w-[220px]",
      },
      {
        id: "title",
        header: "Title",
        cell: (row) => <div className="text-sm text-zinc-700">{row.title}</div>,
        className: "min-w-[240px]",
      },
      {
        id: "status",
        header: "Status",
        cell: (row) => (
          <Badge variant={statusBadgeVariant(row.status)}>
            {statusLabels[row.status] ?? row.status.replace(/_/g, " ")}
          </Badge>
        ),
        className: "whitespace-nowrap",
      },
      {
        id: "updated",
        header: "Updated",
        cell: (row) => <div className="text-sm text-zinc-600">{new Date(row.updated_at).toLocaleString()}</div>,
        className: "whitespace-nowrap",
      },
    ],
    [statusLabels],
  );

  return (
    <ListPageShell
      title="Jobs"
      description="Track, update, and communicate on repair jobs."
      actions={
        <Button disabled variant="outline" size="sm">
          New job
        </Button>
      }
      filters={
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div className="text-sm font-semibold text-[var(--rb-text)]">Search</div>
          <input
            value={q}
            onChange={(e) => setQ(e.target.value)}
            placeholder="Search case number, ID, or title..."
            className="h-10 w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 text-sm sm:max-w-[420px]"
          />
        </div>
      }
      loading={loading}
      error={error}
      empty={!loading && !error && filtered.length === 0}
      emptyTitle="No jobs found"
      emptyDescription="Try adjusting your search."
    >
      <Card className="shadow-none">
        <CardContent className="pt-5">
          <DataTable
            title={typeof tenantSlug === "string" ? `Jobs · ${tenantSlug}` : "Jobs"}
            data={pageRows}
            loading={loading}
            emptyMessage="No jobs."
            columns={columns}
            getRowId={(row) => row.id}
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
              router.push(`/app/${tenantSlug}/jobs/${row.id}`);
            }}
          />
        </CardContent>
      </Card>
    </ListPageShell>
  );
}
