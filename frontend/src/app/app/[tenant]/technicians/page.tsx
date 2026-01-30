"use client";

import React from "react";
import { useParams } from "next/navigation";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { ListPageShell } from "@/components/shells/ListPageShell";
import { RequireAuth } from "@/components/RequireAuth";
import { apiFetch } from "@/lib/api";
import type { User } from "@/lib/types";

type TechRow = {
  id: number;
  name: string;
  email: string;
  phone?: string;
  status: "active" | "inactive";
};

export default function TenantTechniciansPage() {
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [query, setQuery] = React.useState("");
  const [pageIndex, setPageIndex] = React.useState(0);
  const [pageSize, setPageSize] = React.useState(10);

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [rows, setRows] = React.useState<TechRow[]>([]);
  const [totalRows, setTotalRows] = React.useState(0);

  React.useEffect(() => {
    let alive = true;

    async function load() {
      if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;

      setLoading(true);
      setError(null);

      try {
        const qs = new URLSearchParams();
        if (query.trim().length > 0) qs.set("q", query.trim());
        qs.set("page", String(pageIndex + 1));
        qs.set("per_page", String(pageSize));

        const res = await apiFetch<{ users: User[]; meta?: { total?: number; per_page?: number } }>(
          `/api/${tenantSlug}/app/technicians?${qs.toString()}`,
        );

        if (!alive) return;

        const list = Array.isArray(res.users) ? res.users : [];
        setRows(
          list.map((u) => ({
            id: u.id,
            name: u.name,
            email: u.email,
            phone: u.phone ?? undefined,
            status: u.status === "active" ? "active" : "inactive",
          })),
        );

        setTotalRows(typeof res.meta?.total === "number" ? res.meta.total : list.length);
        setPageSize(typeof res.meta?.per_page === "number" ? res.meta.per_page : pageSize);
      } catch (err) {
        if (!alive) return;
        setError(err instanceof Error ? err.message : "Failed to load technicians.");
      } finally {
        if (!alive) return;
        setLoading(false);
      }
    }

    void load();

    return () => {
      alive = false;
    };
  }, [pageIndex, pageSize, query, tenantSlug]);

  const columns = React.useMemo<Array<DataTableColumn<TechRow>>>(
    () => [
      {
        id: "name",
        header: "Technician",
        cell: (row) => (
          <div className="min-w-0">
            <div className="truncate font-semibold text-[var(--rb-text)]">{row.name}</div>
            <div className="truncate text-xs text-zinc-600">#{row.id}</div>
          </div>
        ),
        className: "min-w-[260px]",
      },
      {
        id: "email",
        header: "Email",
        cell: (row) => <div className="text-sm text-zinc-700">{row.email}</div>,
        className: "min-w-[260px]",
      },
      {
        id: "phone",
        header: "Phone",
        cell: (row) => <div className="text-sm text-zinc-700">{row.phone ?? "—"}</div>,
        className: "min-w-[160px]",
      },
      {
        id: "status",
        header: "Status",
        cell: (row) => <Badge variant={row.status === "active" ? "success" : "default"}>{row.status}</Badge>,
        className: "whitespace-nowrap",
      },
    ],
    [],
  );

  return (
    <RequireAuth requiredPermission="technicians.view">
      <ListPageShell
        title="Technicians"
        description="Technician roster."
        actions={
          <Button disabled variant="outline" size="sm">
            Add technician
          </Button>
        }
        loading={loading}
        error={error}
        empty={!loading && !error && rows.length === 0}
        emptyTitle="No technicians"
        emptyDescription="No users are currently assigned the Technician role."
      >
        <Card className="shadow-none">
          <CardContent className="pt-5">
            <DataTable
              title={typeof tenantSlug === "string" ? `Technicians · ${tenantSlug}` : "Technicians"}
              data={rows}
              columns={columns}
              getRowId={(row) => String(row.id)}
              emptyMessage="No technicians."
              search={{
                placeholder: "Search technicians...",
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
    </RequireAuth>
  );
}
