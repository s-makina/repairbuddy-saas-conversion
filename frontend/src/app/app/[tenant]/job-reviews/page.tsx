"use client";

import React from "react";
import { useParams } from "next/navigation";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { ListPageShell } from "@/components/shells/ListPageShell";
import { mockApi } from "@/mock/mockApi";
import type { Client, Job, Review } from "@/mock/types";

export default function TenantJobReviewsPage() {
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [reviews, setReviews] = React.useState<Review[]>([]);
  const [jobs, setJobs] = React.useState<Job[]>([]);
  const [clients, setClients] = React.useState<Client[]>([]);

  const [query, setQuery] = React.useState("");
  const [pageIndex, setPageIndex] = React.useState(0);
  const [pageSize, setPageSize] = React.useState(10);

  React.useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);
        const [r, j, c] = await Promise.all([mockApi.listReviews(), mockApi.listJobs(), mockApi.listClients()]);
        if (!alive) return;
        setReviews(Array.isArray(r) ? r : []);
        setJobs(Array.isArray(j) ? j : []);
        setClients(Array.isArray(c) ? c : []);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load reviews.");
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

  const filtered = React.useMemo(() => {
    const needle = query.trim().toLowerCase();
    if (!needle) return reviews;
    return reviews.filter((r) => {
      const jobCase = jobById.get(r.job_id)?.case_number ?? r.job_id;
      const clientName = clientById.get(r.client_id)?.name ?? r.client_id;
      const hay = `${r.id} ${jobCase} ${clientName} ${r.rating} ${r.comment}`.toLowerCase();
      return hay.includes(needle);
    });
  }, [clientById, jobById, query, reviews]);

  const totalRows = filtered.length;
  const pageRows = React.useMemo(() => {
    const start = pageIndex * pageSize;
    const end = start + pageSize;
    return filtered.slice(start, end);
  }, [filtered, pageIndex, pageSize]);

  const columns = React.useMemo<Array<DataTableColumn<Review>>>(
    () => [
      {
        id: "rating",
        header: "Rating",
        cell: (row) => <Badge variant={row.rating >= 4 ? "success" : row.rating >= 3 ? "warning" : "danger"}>{row.rating} / 5</Badge>,
        className: "whitespace-nowrap",
      },
      {
        id: "job",
        header: "Job",
        cell: (row) => (
          <div className="min-w-0">
            <div className="truncate font-semibold text-[var(--rb-text)]">{jobById.get(row.job_id)?.case_number ?? row.job_id}</div>
            <div className="truncate text-xs text-zinc-600">{row.id}</div>
          </div>
        ),
        className: "min-w-[240px]",
      },
      {
        id: "client",
        header: "Client",
        cell: (row) => <div className="text-sm text-zinc-700">{clientById.get(row.client_id)?.name ?? row.client_id}</div>,
        className: "min-w-[220px]",
      },
      {
        id: "comment",
        header: "Comment",
        cell: (row) => <div className="text-sm text-zinc-700">{row.comment}</div>,
        className: "min-w-[360px]",
      },
      {
        id: "created",
        header: "Created",
        cell: (row) => <div className="text-sm text-zinc-600">{new Date(row.created_at).toLocaleString()}</div>,
        className: "whitespace-nowrap",
      },
    ],
    [clientById, jobById],
  );

  return (
    <ListPageShell
      title="Job Reviews"
      description="Customer feedback for completed jobs."
      actions={
        <Button disabled variant="outline" size="sm">
          Request review
        </Button>
      }
      loading={loading}
      error={error}
      empty={!loading && !error && reviews.length === 0}
      emptyTitle="No reviews"
      emptyDescription="Reviews submitted from the portal will show here."
    >
      <Card className="shadow-none">
        <CardContent className="pt-5">
          <DataTable
            title={typeof tenantSlug === "string" ? `Job Reviews · ${tenantSlug}` : "Job Reviews"}
            data={pageRows}
            loading={loading}
            emptyMessage="No reviews."
            columns={columns}
            getRowId={(row) => row.id}
            search={{
              placeholder: "Search reviews...",
            }}
            server={{
              query,
              onQueryChange: (value) => {
                setQuery(value);
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
          />
        </CardContent>
      </Card>
    </ListPageShell>
  );
}
