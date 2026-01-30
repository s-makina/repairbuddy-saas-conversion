"use client";

import React from "react";
import { useParams, useRouter } from "next/navigation";
import { Button } from "@/components/ui/Button";
import { Badge } from "@/components/ui/Badge";
import { Card, CardContent } from "@/components/ui/Card";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { ListPageShell } from "@/components/shells/ListPageShell";
import { apiFetch, ApiError } from "@/lib/api";

type ApiClient = {
  id: number;
  name: string;
};

type ApiDeviceBrand = {
  id: number;
  name: string;
};

type ApiDeviceType = {
  id: number;
  name: string;
};

type ApiDevice = {
  id: number;
  model: string;
  device_type_id: number;
  device_brand_id: number;
  parent_device_id: number | null;
  disable_in_booking_form: boolean;
  is_other: boolean;
};

type ApiCustomerDevice = {
  id: number;
  customer_id: number;
  device_id: number | null;
  label: string;
  serial: string | null;
  notes: string | null;
};

type Row = {
  customerDevice: ApiCustomerDevice;
  client: ApiClient | null;
  device: ApiDevice | null;
  brand: ApiDeviceBrand | null;
  type: ApiDeviceType | null;
};

export default function TenantCustomerDevicesPage() {
  const router = useRouter();
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);

  const [customerDevices, setCustomerDevices] = React.useState<ApiCustomerDevice[]>([]);
  const [clients, setClients] = React.useState<ApiClient[]>([]);
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

        const [cdRes, clientsRes, devicesRes, brandsRes, typesRes] = await Promise.all([
          apiFetch<{ customer_devices: ApiCustomerDevice[] }>(`/api/${tenantSlug}/app/repairbuddy/customer-devices`),
          apiFetch<{ clients: ApiClient[] }>(`/api/${tenantSlug}/app/clients`),
          apiFetch<{ devices: ApiDevice[] }>(`/api/${tenantSlug}/app/repairbuddy/devices`),
          apiFetch<{ device_brands: ApiDeviceBrand[] }>(`/api/${tenantSlug}/app/repairbuddy/device-brands`),
          apiFetch<{ device_types: ApiDeviceType[] }>(`/api/${tenantSlug}/app/repairbuddy/device-types`),
        ]);

        if (!alive) return;
        setCustomerDevices(Array.isArray(cdRes?.customer_devices) ? cdRes.customer_devices : []);
        setClients(Array.isArray(clientsRes?.clients) ? clientsRes.clients : []);
        setDevices(Array.isArray(devicesRes?.devices) ? devicesRes.devices : []);
        setBrands(Array.isArray(brandsRes?.device_brands) ? brandsRes.device_brands : []);
        setTypes(Array.isArray(typesRes?.device_types) ? typesRes.device_types : []);
      } catch (e) {
        if (!alive) return;

        if (e instanceof ApiError && e.status === 428 && typeof tenantSlug === "string" && tenantSlug.length > 0) {
          const next = `/app/${tenantSlug}/customer-devices`;
          router.replace(`/app/${tenantSlug}/branches/select?next=${encodeURIComponent(next)}`);
          return;
        }

        setError(e instanceof Error ? e.message : "Failed to load customer devices.");
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

  const clientById = React.useMemo(() => new Map(clients.map((c) => [c.id, c])), [clients]);
  const deviceById = React.useMemo(() => new Map(devices.map((d) => [d.id, d])), [devices]);
  const brandById = React.useMemo(() => new Map(brands.map((b) => [b.id, b])), [brands]);
  const typeById = React.useMemo(() => new Map(types.map((t) => [t.id, t])), [types]);

  const rows = React.useMemo<Row[]>(() => {
    return customerDevices.map((cd) => {
      const client = clientById.get(cd.customer_id) ?? null;
      const device = typeof cd.device_id === "number" ? deviceById.get(cd.device_id) ?? null : null;
      const brand = device ? brandById.get(device.device_brand_id) ?? null : null;
      const type = device ? typeById.get(device.device_type_id) ?? null : null;
      return { customerDevice: cd, client, device, brand, type };
    });
  }, [brandById, clientById, customerDevices, deviceById, typeById]);

  const filtered = React.useMemo(() => {
    const needle = query.trim().toLowerCase();
    if (!needle) return rows;

    return rows.filter((r) => {
      const label = r.device ? `${r.brand?.name ?? ""} ${r.device.model}`.trim() : r.customerDevice.label;
      const hay = `${r.customerDevice.id} ${label} ${r.client?.name ?? ""} ${r.customerDevice.serial ?? ""}`.toLowerCase();
      return hay.includes(needle);
    });
  }, [query, rows]);

  const totalRows = filtered.length;
  const pageRows = React.useMemo(() => {
    const start = pageIndex * pageSize;
    const end = start + pageSize;
    return filtered.slice(start, end);
  }, [filtered, pageIndex, pageSize]);

  const columns = React.useMemo<Array<DataTableColumn<Row>>>(
    () => [
      {
        id: "device",
        header: "Device",
        cell: (row) => (
          <div className="min-w-0">
            <div className="truncate font-semibold text-[var(--rb-text)]">
              {row.device ? `${row.brand?.name ?? ""} ${row.device.model}`.trim() : row.customerDevice.label}
            </div>
            <div className="truncate text-xs text-zinc-600">{row.customerDevice.id}</div>
          </div>
        ),
        className: "min-w-[280px]",
      },
      {
        id: "client",
        header: "Client",
        cell: (row) => <div className="text-sm text-zinc-700">{row.client?.name ?? row.customerDevice.customer_id}</div>,
        className: "min-w-[220px]",
      },
      {
        id: "type",
        header: "Type",
        cell: (row) => <div className="text-sm text-zinc-700">{row.type?.name ?? "—"}</div>,
        className: "min-w-[160px]",
      },
      {
        id: "serial",
        header: "Serial",
        cell: (row) => <Badge variant="default">{row.customerDevice.serial ?? "—"}</Badge>,
        className: "whitespace-nowrap",
      },
    ],
    [],
  );

  return (
    <ListPageShell
      title="Customer Devices"
      description="Devices owned by clients (intake inventory)."
      actions={
        <Button disabled variant="outline" size="sm">
          Add device
        </Button>
      }
      loading={loading}
      error={error}
      empty={!loading && !error && rows.length === 0}
      emptyTitle="No customer devices"
      emptyDescription="Customer devices will appear when linked to clients."
    >
      <Card className="shadow-none">
        <CardContent className="pt-5">
          <DataTable
            title={typeof tenantSlug === "string" ? `Customer Devices · ${tenantSlug}` : "Customer Devices"}
            data={pageRows}
            loading={loading}
            emptyMessage="No customer devices."
            columns={columns}
            getRowId={(row) => row.customerDevice.id}
            search={{
              placeholder: "Search devices, clients, serial...",
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
