"use client";

import React from "react";
import { useParams, useRouter } from "next/navigation";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { ListPageShell } from "@/components/shells/ListPageShell";
import { apiFetch, ApiError } from "@/lib/api";

type ApiDeviceType = {
  id: number;
  name: string;
  is_active: boolean;
};

type ApiDeviceBrand = {
  id: number;
  name: string;
  image_path: string | null;
  is_active: boolean;
};

type ApiDevice = {
  id: number;
  model: string;
  device_type_id: number;
  device_brand_id: number;
  parent_device_id: number | null;
  disable_in_booking_form: boolean;
  is_other: boolean;
  is_active: boolean;
};

export default function TenantDevicesPage() {
  const router = useRouter();
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);

  const [devices, setDevices] = React.useState<ApiDevice[]>([]);
  const [brands, setBrands] = React.useState<ApiDeviceBrand[]>([]);
  const [types, setTypes] = React.useState<ApiDeviceType[]>([]);

  const [query, setQuery] = React.useState("");
  const [pageIndex, setPageIndex] = React.useState(0);
  const [pageSize, setPageSize] = React.useState(10);

  React.useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);

        if (typeof tenantSlug !== "string" || tenantSlug.length === 0) {
          throw new Error("Business is missing.");
        }

        const [devicesRes, brandsRes, typesRes] = await Promise.all([
          apiFetch<{ devices: ApiDevice[] }>(`/api/${tenantSlug}/app/repairbuddy/devices`),
          apiFetch<{ device_brands: ApiDeviceBrand[] }>(`/api/${tenantSlug}/app/repairbuddy/device-brands`),
          apiFetch<{ device_types: ApiDeviceType[] }>(`/api/${tenantSlug}/app/repairbuddy/device-types`),
        ]);
        if (!alive) return;
        setDevices(Array.isArray(devicesRes.devices) ? devicesRes.devices : []);
        setBrands(Array.isArray(brandsRes.device_brands) ? brandsRes.device_brands : []);
        setTypes(Array.isArray(typesRes.device_types) ? typesRes.device_types : []);
      } catch (e) {
        if (!alive) return;

        if (e instanceof ApiError && e.status === 428 && typeof tenantSlug === "string" && tenantSlug.length > 0) {
          const next = `/app/${tenantSlug}/devices`;
          router.replace(`/app/${tenantSlug}/branches/select?next=${encodeURIComponent(next)}`);
          return;
        }

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
  }, [router, tenantSlug]);

  const brandById = React.useMemo(() => new Map(brands.map((b) => [b.id, b])), [brands]);
  const typeById = React.useMemo(() => new Map(types.map((t) => [t.id, t])), [types]);

  const filtered = React.useMemo(() => {
    const needle = query.trim().toLowerCase();
    if (!needle) return devices;

    return devices.filter((d) => {
      const brand = brandById.get(d.device_brand_id)?.name ?? String(d.device_brand_id);
      const type = typeById.get(d.device_type_id)?.name ?? String(d.device_type_id);
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

  const columns = React.useMemo<Array<DataTableColumn<ApiDevice>>>(
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
        cell: (row) => <div className="text-sm text-zinc-700">{brandById.get(row.device_brand_id)?.name ?? String(row.device_brand_id)}</div>,
        className: "min-w-[200px]",
      },
      {
        id: "type",
        header: "Type",
        cell: (row) => <div className="text-sm text-zinc-700">{typeById.get(row.device_type_id)?.name ?? String(row.device_type_id)}</div>,
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
