"use client";

import React from "react";
import { useParams, useRouter } from "next/navigation";
import { Button } from "@/components/ui/Button";
import { Badge } from "@/components/ui/Badge";
import { Card, CardContent } from "@/components/ui/Card";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { ListPageShell } from "@/components/shells/ListPageShell";
import { mockApi } from "@/mock/mockApi";
import type { Client, Job } from "@/mock/types";

export default function TenantClientsPage() {
  const router = useRouter();
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [clients, setClients] = React.useState<Client[]>([]);
  const [jobs, setJobs] = React.useState<Job[]>([]);
  const [q, setQ] = React.useState<string>("");

  const [pageIndex, setPageIndex] = React.useState(0);
  const [pageSize, setPageSize] = React.useState(10);

  React.useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);
        const [c, j] = await Promise.all([mockApi.listClients(), mockApi.listJobs()]);
        if (!alive) return;
        setClients(Array.isArray(c) ? c : []);
        setJobs(Array.isArray(j) ? j : []);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load clients.");
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

  const jobsByClientId = React.useMemo(() => {
    const map = new Map<string, number>();
    for (const j of jobs) {
      map.set(j.client_id, (map.get(j.client_id) ?? 0) + 1);
    }
    return map;
  }, [jobs]);

  const filtered = React.useMemo(() => {
    const needle = q.trim().toLowerCase();
    if (!needle) return clients;
    return clients.filter((c) => {
      const hay = `${c.name} ${c.email ?? ""} ${c.phone ?? ""} ${c.id}`.toLowerCase();
      return hay.includes(needle);
    });
  }, [clients, q]);

  const totalRows = filtered.length;
  const pageRows = React.useMemo(() => {
    const start = pageIndex * pageSize;
    const end = start + pageSize;
    return filtered.slice(start, end);
  }, [filtered, pageIndex, pageSize]);

  const columns = React.useMemo<Array<DataTableColumn<Client>>>(
    () => [
      {
        id: "name",
        header: "Client",
        cell: (row) => (
          <div className="min-w-0">
            <div className="truncate font-semibold text-[var(--rb-text)]">{row.name}</div>
            <div className="truncate text-xs text-zinc-600">{row.id}</div>
          </div>
        ),
        className: "min-w-[240px]",
      },
      {
        id: "email",
        header: "Email",
        cell: (row) => <div className="text-sm text-zinc-700">{row.email ?? "—"}</div>,
        className: "min-w-[220px]",
      },
      {
        id: "phone",
        header: "Phone",
        cell: (row) => <div className="text-sm text-zinc-700">{row.phone ?? "—"}</div>,
        className: "min-w-[160px]",
      },
      {
        id: "jobs",
        header: "Jobs",
        cell: (row) => <Badge variant={((jobsByClientId.get(row.id) ?? 0) > 0) ? "info" : "default"}>{jobsByClientId.get(row.id) ?? 0}</Badge>,
        className: "whitespace-nowrap",
      },
      {
        id: "created",
        header: "Created",
        cell: (row) => <div className="text-sm text-zinc-600">{new Date(row.created_at).toLocaleDateString()}</div>,
        className: "whitespace-nowrap",
      },
    ],
    [jobsByClientId],
  );

  return (
    <ListPageShell
      title="Clients"
      description="Your customer list and contact details."
      actions={
        <Button disabled variant="outline" size="sm">
          New client
        </Button>
      }
      filters={
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div className="text-sm font-semibold text-[var(--rb-text)]">Search</div>
          <input
            value={q}
            onChange={(e) => setQ(e.target.value)}
            placeholder="Search name, email, phone, or ID..."
            className="h-10 w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 text-sm sm:max-w-[420px]"
          />
        </div>
      }
      loading={loading}
      error={error}
      empty={!loading && !error && filtered.length === 0}
      emptyTitle="No clients found"
      emptyDescription="Try adjusting your search."
    >
      <Card className="shadow-none">
        <CardContent className="pt-5">
          <DataTable
            title={typeof tenantSlug === "string" ? `Clients · ${tenantSlug}` : "Clients"}
            data={pageRows}
            loading={loading}
            emptyMessage="No clients."
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
              router.push(`/app/${tenantSlug}/clients/${row.id}`);
            }}
          />
        </CardContent>
      </Card>
    </ListPageShell>
  );
}
