"use client";

import React from "react";
import { useParams } from "next/navigation";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { ListPageShell } from "@/components/shells/ListPageShell";

type TechRow = {
  id: string;
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

  const data = React.useMemo<TechRow[]>(
    () => [
      { id: "tech_001", name: "Alex Technician", email: "alex.tech@example.com", phone: "+1 555 0101", status: "active" },
      { id: "tech_002", name: "Sam Repair", email: "sam.repair@example.com", phone: "+1 555 0102", status: "active" },
      { id: "tech_003", name: "Jamie Bench", email: "jamie.bench@example.com", status: "inactive" },
    ],
    [],
  );

  const columns = React.useMemo<Array<DataTableColumn<TechRow>>>(
    () => [
      {
        id: "name",
        header: "Technician",
        cell: (row) => (
          <div className="min-w-0">
            <div className="truncate font-semibold text-[var(--rb-text)]">{row.name}</div>
            <div className="truncate text-xs text-zinc-600">{row.id}</div>
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
    <ListPageShell
      title="Technicians"
      description="Technician roster (mock)."
      actions={
        <Button disabled variant="outline" size="sm">
          Add technician
        </Button>
      }
      loading={false}
      error={null}
      empty={data.length === 0}
      emptyTitle="No technicians"
      emptyDescription="Assign technicians to jobs once staff management is wired."
    >
      <Card className="shadow-none">
        <CardContent className="pt-5">
          <DataTable
            title={typeof tenantSlug === "string" ? `Technicians · ${tenantSlug}` : "Technicians"}
            data={data
              .filter((row) => {
                const needle = query.trim().toLowerCase();
                if (!needle) return true;
                const hay = `${row.id} ${row.name} ${row.email} ${row.phone ?? ""} ${row.status}`.toLowerCase();
                return hay.includes(needle);
              })
              .slice(pageIndex * pageSize, pageIndex * pageSize + pageSize)}
            columns={columns}
            getRowId={(row) => row.id}
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
              totalRows: data.filter((row) => {
                const needle = query.trim().toLowerCase();
                if (!needle) return true;
                const hay = `${row.id} ${row.name} ${row.email} ${row.phone ?? ""} ${row.status}`.toLowerCase();
                return hay.includes(needle);
              }).length,
            }}
          />
        </CardContent>
      </Card>
    </ListPageShell>
  );
}
