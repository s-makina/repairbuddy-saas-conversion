"use client";

import React from "react";
import { useParams } from "next/navigation";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
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

  const [query, setQuery] = React.useState("");
  const [pageIndex, setPageIndex] = React.useState(0);
  const [pageSize, setPageSize] = React.useState(10);

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

  const filtered = React.useMemo(() => {
    const needle = query.trim().toLowerCase();
    if (!needle) return devices;

    return devices.filter((d) => {
      const brand = brandById.get(d.brand_id)?.name ?? d.brand_id;
      const type = typeById.get(d.type_id)?.name ?? d.type_id;
      const hay = `${d.id} ${d.model} ${brand} ${type}`.toLowerCase();
      return hay.includes(needle);
    });
  }, [brandById, devices, query, typeById]);

  const totalRows = filtered.length;
  const pageRows = React.useMemo(() => {
    const start = pageIndex * pageSize;
    const end = start + pageSize;
    return filtered.slice(start, end);
  }, [filtered, pageIndex, pageSize]);

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
      <Card className="shadow-none">
        <CardContent className="pt-5">
          <DataTable
            title={typeof tenantSlug === "string" ? `Devices · ${tenantSlug}` : "Devices"}
            data={pageRows}
            loading={loading}
            emptyMessage="No devices."
            columns={columns}
            getRowId={(row) => row.id}
            search={{
              placeholder: "Search devices...",
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
