"use client";

import React from "react";
import { useParams } from "next/navigation";
import { Button } from "@/components/ui/Button";
import { Badge } from "@/components/ui/Badge";
import { Card, CardContent } from "@/components/ui/Card";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { ListPageShell } from "@/components/shells/ListPageShell";
import { formatMoney } from "@/lib/money";

type Row = {
  id: string;
  user_label: string;
  rate_cents: number;
  currency: string;
  status: "default" | "override";
};

export default function TenantHourlyRatesPage() {
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [query, setQuery] = React.useState("");
  const [pageIndex, setPageIndex] = React.useState(0);
  const [pageSize, setPageSize] = React.useState(10);

  const data = React.useMemo<Row[]>(
    () => [
      { id: "rate_001", user_label: "Alex Technician", rate_cents: 4500, currency: "USD", status: "override" },
      { id: "rate_002", user_label: "Sam Repair", rate_cents: 4000, currency: "USD", status: "override" },
      { id: "rate_003", user_label: "Default", rate_cents: 3500, currency: "USD", status: "default" },
    ],
    [],
  );

  const columns = React.useMemo<Array<DataTableColumn<Row>>>(
    () => [
      {
        id: "user",
        header: "User",
        cell: (row) => (
          <div className="min-w-0">
            <div className="truncate font-semibold text-[var(--rb-text)]">{row.user_label}</div>
            <div className="truncate text-xs text-zinc-600">{row.id}</div>
          </div>
        ),
        className: "min-w-[260px]",
      },
      {
        id: "rate",
        header: "Hourly rate",
        cell: (row) => (
          <div className="text-sm font-semibold text-[var(--rb-text)] whitespace-nowrap">
            {formatMoney({ amountCents: row.rate_cents, currency: row.currency })} / hr
          </div>
        ),
        className: "whitespace-nowrap",
      },
      {
        id: "status",
        header: "Type",
        cell: (row) => <Badge variant={row.status === "default" ? "default" : "info"}>{row.status}</Badge>,
        className: "whitespace-nowrap",
      },
    ],
    [],
  );

  return (
    <ListPageShell
      title="Manage Hourly Rates"
      description="Configure labor rates per user (mock)."
      actions={
        <Button disabled variant="outline" size="sm">
          Set rate
        </Button>
      }
      loading={false}
      error={null}
      empty={data.length === 0}
      emptyTitle="No hourly rates"
      emptyDescription="Rates will apply to time logs and labor charges."
    >
      <Card className="shadow-none">
        <CardContent className="pt-5">
          <DataTable
            title={typeof tenantSlug === "string" ? `Hourly Rates · ${tenantSlug}` : "Hourly Rates"}
            data={data
              .filter((row) => {
                const needle = query.trim().toLowerCase();
                if (!needle) return true;
                const hay = `${row.id} ${row.user_label} ${row.currency} ${row.status}`.toLowerCase();
                return hay.includes(needle);
              })
              .slice(pageIndex * pageSize, pageIndex * pageSize + pageSize)}
            columns={columns}
            getRowId={(row) => row.id}
            emptyMessage="No hourly rates."
            search={{
              placeholder: "Search rates...",
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
                const hay = `${row.id} ${row.user_label} ${row.currency} ${row.status}`.toLowerCase();
                return hay.includes(needle);
              }).length,
            }}
          />
        </CardContent>
      </Card>
    </ListPageShell>
  );
}
