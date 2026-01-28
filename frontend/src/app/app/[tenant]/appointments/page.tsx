"use client";

import React from "react";
import { useParams } from "next/navigation";
import { Button } from "@/components/ui/Button";
import { Badge } from "@/components/ui/Badge";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { ListPageShell } from "@/components/shells/ListPageShell";
import { mockApi } from "@/mock/mockApi";
import type { Appointment } from "@/mock/types";

function statusVariant(status: Appointment["status"]): "default" | "info" | "success" | "warning" | "danger" {
  if (status === "confirmed") return "success";
  if (status === "cancelled") return "danger";
  return "warning";
}

export default function TenantAppointmentsPage() {
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [appointments, setAppointments] = React.useState<Appointment[]>([]);

  React.useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);
        const a = await mockApi.listAppointments();
        if (!alive) return;
        setAppointments(Array.isArray(a) ? a : []);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load appointments.");
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

  const columns = React.useMemo<Array<DataTableColumn<Appointment>>>(
    () => [
      {
        id: "when",
        header: "Scheduled",
        cell: (row) => <div className="text-sm text-zinc-700">{new Date(row.scheduled_at).toLocaleString()}</div>,
        className: "whitespace-nowrap",
      },
      {
        id: "status",
        header: "Status",
        cell: (row) => <Badge variant={statusVariant(row.status)}>{row.status}</Badge>,
        className: "whitespace-nowrap",
      },
      {
        id: "client",
        header: "Client",
        cell: (row) => (
          <div className="min-w-0">
            <div className="truncate font-semibold text-[var(--rb-text)]">{row.client_name}</div>
            <div className="truncate text-xs text-zinc-600">{row.id}</div>
          </div>
        ),
        className: "min-w-[240px]",
      },
      {
        id: "contact",
        header: "Contact",
        cell: (row) => (
          <div className="text-sm text-zinc-700">
            {row.client_email ?? "—"}
            {row.client_phone ? <span className="text-zinc-500"> · {row.client_phone}</span> : null}
          </div>
        ),
        className: "min-w-[260px]",
      },
    ],
    [],
  );

  return (
    <ListPageShell
      title="Appointments"
      description="Manage bookings and scheduled visits."
      actions={
        <Button disabled variant="outline" size="sm">
          New appointment
        </Button>
      }
      loading={loading}
      error={error}
      empty={!loading && !error && appointments.length === 0}
      emptyTitle="No appointments"
      emptyDescription="Appointments created from booking flows will show here."
    >
      <DataTable
        title={typeof tenantSlug === "string" ? `Appointments · ${tenantSlug}` : "Appointments"}
        data={appointments}
        loading={loading}
        emptyMessage="No appointments."
        columns={columns}
        getRowId={(row) => row.id}
      />
    </ListPageShell>
  );
}
