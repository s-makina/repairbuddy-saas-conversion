"use client";

import React from "react";
import { useParams } from "next/navigation";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { ListPageShell } from "@/components/shells/ListPageShell";

type Row = {
  id: string;
  target: string;
  channel: "sms" | "email";
  status: "queued" | "sent" | "failed";
  scheduled_at: string;
};

function statusVariant(status: Row["status"]): "default" | "info" | "success" | "warning" | "danger" {
  if (status === "sent") return "success";
  if (status === "failed") return "danger";
  return "warning";
}

export default function TenantReminderLogsPage() {
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [query, setQuery] = React.useState("");
  const [pageIndex, setPageIndex] = React.useState(0);
  const [pageSize, setPageSize] = React.useState(10);

  const data = React.useMemo<Row[]>(
    () => [
      { id: "rem_001", target: "RB-10421", channel: "sms", status: "sent", scheduled_at: "2025-01-12T09:00:00.000Z" },
      { id: "rem_002", target: "RB-10419", channel: "email", status: "queued", scheduled_at: "2025-01-12T10:00:00.000Z" },
      { id: "rem_003", target: "RB-10411", channel: "sms", status: "failed", scheduled_at: "2025-01-12T08:00:00.000Z" },
    ],
    [],
  );

  const columns = React.useMemo<Array<DataTableColumn<Row>>>(
    () => [
      {
        id: "target",
        header: "Target",
        cell: (row) => (
          <div className="min-w-0">
            <div className="truncate font-semibold text-[var(--rb-text)]">{row.target}</div>
            <div className="truncate text-xs text-zinc-600">{row.id}</div>
          </div>
        ),
        className: "min-w-[240px]",
      },
      {
        id: "channel",
        header: "Channel",
        cell: (row) => <Badge variant="default">{row.channel}</Badge>,
        className: "whitespace-nowrap",
      },
      {
        id: "status",
        header: "Status",
        cell: (row) => <Badge variant={statusVariant(row.status)}>{row.status}</Badge>,
        className: "whitespace-nowrap",
      },
      {
        id: "scheduled",
        header: "Scheduled",
        cell: (row) => <div className="text-sm text-zinc-700">{new Date(row.scheduled_at).toLocaleString()}</div>,
        className: "whitespace-nowrap",
      },
    ],
    [],
  );

  return (
    <ListPageShell
      title="Reminder Logs"
      description="Automated reminder delivery history (mock)."
      actions={
        <Button disabled variant="outline" size="sm">
          Queue reminder
        </Button>
      }
      loading={false}
      error={null}
      empty={data.length === 0}
      emptyTitle="No reminders"
      emptyDescription="Reminders will be generated from job status changes and timelines."
    >
      <Card className="shadow-none">
        <CardContent className="pt-5">
          <DataTable
            title={typeof tenantSlug === "string" ? `Reminder Logs · ${tenantSlug}` : "Reminder Logs"}
            data={data
              .filter((row) => {
                const needle = query.trim().toLowerCase();
                if (!needle) return true;
                const hay = `${row.id} ${row.target} ${row.channel} ${row.status}`.toLowerCase();
                return hay.includes(needle);
              })
              .slice(pageIndex * pageSize, pageIndex * pageSize + pageSize)}
            columns={columns}
            getRowId={(row) => row.id}
            emptyMessage="No reminder logs."
            search={{
              placeholder: "Search reminders...",
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
                const hay = `${row.id} ${row.target} ${row.channel} ${row.status}`.toLowerCase();
                return hay.includes(needle);
              }).length,
            }}
          />
        </CardContent>
      </Card>
    </ListPageShell>
  );
}
