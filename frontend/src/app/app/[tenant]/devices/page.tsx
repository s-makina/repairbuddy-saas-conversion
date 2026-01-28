"use client";

import React from "react";
import { useParams } from "next/navigation";
import { Button } from "@/components/ui/Button";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { ListPageShell } from "@/components/shells/ListPageShell";
import { mockApi } from "@/mock/mockApi";
import type { Device, DeviceBrand, DeviceType } from "@/mock/types";

export default function TenantDevicesPage() {
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);

  const [devices, setDevices] = React.useState<Device[]>([]);
  const [brands, setBrands] = React.useState<DeviceBrand[]>([]);
  const [types, setTypes] = React.useState<DeviceType[]>([]);

  React.useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);
        const [d, b, t] = await Promise.all([mockApi.listDevices(), mockApi.listDeviceBrands(), mockApi.listDeviceTypes()]);
        if (!alive) return;
        setDevices(Array.isArray(d) ? d : []);
        setBrands(Array.isArray(b) ? b : []);
        setTypes(Array.isArray(t) ? t : []);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load devices.");
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

  const brandById = React.useMemo(() => new Map(brands.map((b) => [b.id, b])), [brands]);
  const typeById = React.useMemo(() => new Map(types.map((t) => [t.id, t])), [types]);

  const columns = React.useMemo<Array<DataTableColumn<Device>>>(
    () => [
      {
        id: "model",
        header: "Device",
        cell: (row) => (
          <div className="min-w-0">
            <div className="truncate font-semibold text-[var(--rb-text)]">{row.model}</div>
            <div className="truncate text-xs text-zinc-600">{row.id}</div>
          </div>
        ),
        className: "min-w-[240px]",
      },
      {
        id: "brand",
        header: "Brand",
        cell: (row) => <div className="text-sm text-zinc-700">{brandById.get(row.brand_id)?.name ?? row.brand_id}</div>,
        className: "min-w-[200px]",
      },
      {
        id: "type",
        header: "Type",
        cell: (row) => <div className="text-sm text-zinc-700">{typeById.get(row.type_id)?.name ?? row.type_id}</div>,
        className: "min-w-[200px]",
      },
    ],
    [brandById, typeById],
  );

  return (
    <ListPageShell
      title="Devices"
      description="Supported device models for your repair catalog."
      actions={
        <Button disabled variant="outline" size="sm">
          New device
        </Button>
      }
      loading={loading}
      error={error}
      empty={!loading && !error && devices.length === 0}
      emptyTitle="No devices"
      emptyDescription="Add device models to standardize intake and quoting."
    >
      <DataTable
        title={typeof tenantSlug === "string" ? `Devices · ${tenantSlug}` : "Devices"}
        data={devices}
        loading={loading}
        emptyMessage="No devices."
        columns={columns}
        getRowId={(row) => row.id}
      />
    </ListPageShell>
  );
}
