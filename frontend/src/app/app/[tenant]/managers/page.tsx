"use client";

import React from "react";
import { useParams } from "next/navigation";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { ListPageShell } from "@/components/shells/ListPageShell";

type ManagerRow = {
  id: string;
  name: string;
  email: string;
  permissions: string[];
  status: "active" | "inactive";
};

export default function TenantManagersPage() {
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [query, setQuery] = React.useState("");
  const [pageIndex, setPageIndex] = React.useState(0);
  const [pageSize, setPageSize] = React.useState(10);

  const data = React.useMemo<ManagerRow[]>(
    () => [
      {
        id: "mgr_001",
        name: "Taylor Manager",
        email: "taylor.manager@example.com",
        permissions: ["jobs.manage", "clients.manage", "payments.view"],
        status: "active",
      },
      {
        id: "mgr_002",
        name: "Jordan Ops",
        email: "jordan.ops@example.com",
        permissions: ["appointments.manage", "reports.view"],
        status: "active",
      },
    ],
    [],
  );

  const columns = React.useMemo<Array<DataTableColumn<ManagerRow>>>(
    () => [
      {
        id: "name",
        header: "Manager",
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
        id: "permissions",
        header: "Permissions",
        cell: (row) => <div className="text-sm text-zinc-700">{row.permissions.join(", ")}</div>,
        className: "min-w-[340px]",
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
      title="Managers"
      description="Managers and supervisory roles (mock)."
      actions={
        <Button disabled variant="outline" size="sm">
          Add manager
        </Button>
      }
      loading={false}
      error={null}
      empty={data.length === 0}
      emptyTitle="No managers"
      emptyDescription="Assign manager roles once staff management is wired."
    >
      <Card className="shadow-none">
        <CardContent className="pt-5">
          <DataTable
            title={typeof tenantSlug === "string" ? `Managers · ${tenantSlug}` : "Managers"}
            data={data
              .filter((row) => {
                const needle = query.trim().toLowerCase();
                if (!needle) return true;
                const hay = `${row.id} ${row.name} ${row.email} ${row.permissions.join(" ")} ${row.status}`.toLowerCase();
                return hay.includes(needle);
              })
              .slice(pageIndex * pageSize, pageIndex * pageSize + pageSize)}
            columns={columns}
            getRowId={(row) => row.id}
            emptyMessage="No managers."
            search={{
              placeholder: "Search managers...",
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
                const hay = `${row.id} ${row.name} ${row.email} ${row.permissions.join(" ")} ${row.status}`.toLowerCase();
                return hay.includes(needle);
              }).length,
            }}
          />
        </CardContent>
      </Card>
    </ListPageShell>
  );
}
