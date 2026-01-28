"use client";

import React from "react";
import { useParams } from "next/navigation";
import { Button } from "@/components/ui/Button";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { ListPageShell } from "@/components/shells/ListPageShell";
import { mockApi } from "@/mock/mockApi";
import type { DeviceType } from "@/mock/types";

export default function TenantDeviceTypesPage() {
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [types, setTypes] = React.useState<DeviceType[]>([]);

  React.useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);
        const res = await mockApi.listDeviceTypes();
        if (!alive) return;
        setTypes(Array.isArray(res) ? res : []);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load device types.");
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

  const columns = React.useMemo<Array<DataTableColumn<DeviceType>>>(
    () => [
      {
        id: "name",
        header: "Device type",
        cell: (row) => (
          <div className="min-w-0">
            <div className="truncate font-semibold text-[var(--rb-text)]">{row.name}</div>
            <div className="truncate text-xs text-zinc-600">{row.id}</div>
          </div>
        ),
        className: "min-w-[260px]",
      },
    ],
    [],
  );

  return (
    <ListPageShell
      title="Device Types"
      description="Type taxonomy for devices (laptop, desktop, phone, etc.)."
      actions={
        <Button disabled variant="outline" size="sm">
          New device type
        </Button>
      }
      loading={loading}
      error={error}
      empty={!loading && !error && types.length === 0}
      emptyTitle="No device types"
      emptyDescription="Add types to categorize devices."
    >
      <DataTable
        title={typeof tenantSlug === "string" ? `Device Types · ${tenantSlug}` : "Device Types"}
        data={types}
        loading={loading}
        emptyMessage="No device types."
        columns={columns}
        getRowId={(row) => row.id}
      />
    </ListPageShell>
  );
}
