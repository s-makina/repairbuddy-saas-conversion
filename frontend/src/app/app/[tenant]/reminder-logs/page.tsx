"use client";

import React from "react";
import { useParams } from "next/navigation";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { ListPageShell } from "@/components/shells/ListPageShell";
import { ApiError } from "@/lib/api";
import { listMaintenanceReminderLogs, type MaintenanceReminderLogRow } from "@/lib/repairbuddy-maintenance-reminder-logs";

function statusVariant(status: string): "default" | "info" | "success" | "warning" | "danger" {
  const s = String(status || "").toLowerCase();
  if (s === "sent") return "success";
  if (s === "failed") return "danger";
  if (s === "skipped") return "info";
  return "warning";
}

export default function TenantReminderLogsPage() {
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [query, setQuery] = React.useState("");
  const [pageIndex, setPageIndex] = React.useState(0);
  const [pageSize, setPageSize] = React.useState(10);

  const [loading, setLoading] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);
  const [rows, setRows] = React.useState<MaintenanceReminderLogRow[]>([]);
  const [totalRows, setTotalRows] = React.useState(0);
  const [reminderIdFilter, setReminderIdFilter] = React.useState<number | null>(null);

  React.useEffect(() => {
    if (typeof window === "undefined") return;
    const url = new URL(window.location.href);
    const rid = url.searchParams.get("reminder_id");
    const parsed = rid && /^\d+$/.test(rid) ? Number(rid) : null;
    setReminderIdFilter(parsed);
  }, []);

  const refresh = React.useCallback(async () => {
    if (typeof tenantSlug !== "string" || !tenantSlug) return;
    setLoading(true);
    setError(null);
    try {
      const res = await listMaintenanceReminderLogs(String(tenantSlug), {
        q: query.trim() || undefined,
        reminder_id: reminderIdFilter ?? undefined,
        page: pageIndex + 1,
        per_page: pageSize,
      });
      setRows(Array.isArray(res.logs) ? res.logs : []);
      setTotalRows(typeof res.meta?.total === "number" ? res.meta.total : 0);
    } catch (e) {
      const msg = e instanceof ApiError ? e.message : e instanceof Error ? e.message : "Failed to load reminder logs.";
      setError(msg);
    } finally {
      setLoading(false);
    }
  }, [pageIndex, pageSize, query, reminderIdFilter, tenantSlug]);

  React.useEffect(() => {
    void refresh();
  }, [refresh]);

  const columns = React.useMemo<Array<DataTableColumn<MaintenanceReminderLogRow>>>(
    () => [
      {
        id: "target",
        header: "Target",
        cell: (row) => (
          <div className="min-w-0">
            <div className="truncate font-semibold text-[var(--rb-text)]">{row.job?.case_number ?? "—"}</div>
            <div className="truncate text-xs text-zinc-600">{row.reminder?.name ?? "Reminder"}</div>
          </div>
        ),
        className: "min-w-[240px]",
      },
      {
        id: "to",
        header: "To",
        cell: (row) => <div className="text-sm text-zinc-700">{row.to_address ?? "—"}</div>,
        className: "min-w-[220px]",
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
        id: "created",
        header: "Created",
        cell: (row) => <div className="text-sm text-zinc-700">{new Date(row.created_at).toLocaleString()}</div>,
        className: "whitespace-nowrap",
      },
    ],
    [],
  );

  return (
    <ListPageShell
      title="Reminder Logs"
      description="Automated reminder delivery history."
      actions={
        <Button variant="outline" size="sm" onClick={() => void refresh()} disabled={loading}>
          Refresh
        </Button>
      }
      loading={loading}
      error={error}
      empty={!loading && !error && rows.length === 0}
      emptyTitle="No reminders"
      emptyDescription="Reminders will be generated from job status changes and timelines."
    >
      <Card className="shadow-none">
        <CardContent className="pt-5">
          <DataTable
            title={typeof tenantSlug === "string" ? `Reminder Logs · ${tenantSlug}` : "Reminder Logs"}
            data={rows}
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
              totalRows,
            }}
          />
        </CardContent>
      </Card>
    </ListPageShell>
  );
}
