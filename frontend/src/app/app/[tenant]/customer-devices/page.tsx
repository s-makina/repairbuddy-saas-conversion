"use client";

import React from "react";
import { useParams } from "next/navigation";
import { Button } from "@/components/ui/Button";
import { Badge } from "@/components/ui/Badge";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { ListPageShell } from "@/components/shells/ListPageShell";
import { mockApi } from "@/mock/mockApi";
import type { Client, CustomerDevice, Device, DeviceBrand, DeviceType } from "@/mock/types";

type Row = {
  customerDevice: CustomerDevice;
  client: Client | null;
  device: Device | null;
  brand: DeviceBrand | null;
  type: DeviceType | null;
};

export default function TenantCustomerDevicesPage() {
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);

  const [customerDevices, setCustomerDevices] = React.useState<CustomerDevice[]>([]);
  const [clients, setClients] = React.useState<Client[]>([]);
  const [devices, setDevices] = React.useState<Device[]>([]);
  const [brands, setBrands] = React.useState<DeviceBrand[]>([]);
  const [types, setTypes] = React.useState<DeviceType[]>([]);

  React.useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);
        const [cd, c, d, b, t] = await Promise.all([
          mockApi.listCustomerDevices(),
          mockApi.listClients(),
          mockApi.listDevices(),
          mockApi.listDeviceBrands(),
          mockApi.listDeviceTypes(),
        ]);
        if (!alive) return;
        setCustomerDevices(Array.isArray(cd) ? cd : []);
        setClients(Array.isArray(c) ? c : []);
        setDevices(Array.isArray(d) ? d : []);
        setBrands(Array.isArray(b) ? b : []);
        setTypes(Array.isArray(t) ? t : []);
      } catch (e) {
        if (!alive) return;
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
  }, []);

  const clientById = React.useMemo(() => new Map(clients.map((c) => [c.id, c])), [clients]);
  const deviceById = React.useMemo(() => new Map(devices.map((d) => [d.id, d])), [devices]);
  const brandById = React.useMemo(() => new Map(brands.map((b) => [b.id, b])), [brands]);
  const typeById = React.useMemo(() => new Map(types.map((t) => [t.id, t])), [types]);

  const rows = React.useMemo<Row[]>(() => {
    return customerDevices.map((cd) => {
      const client = clientById.get(cd.client_id) ?? null;
      const device = deviceById.get(cd.device_id) ?? null;
      const brand = device ? brandById.get(device.brand_id) ?? null : null;
      const type = device ? typeById.get(device.type_id) ?? null : null;
      return { customerDevice: cd, client, device, brand, type };
    });
  }, [brandById, clientById, customerDevices, deviceById, typeById]);

  const columns = React.useMemo<Array<DataTableColumn<Row>>>(
    () => [
      {
        id: "device",
        header: "Device",
        cell: (row) => (
          <div className="min-w-0">
            <div className="truncate font-semibold text-[var(--rb-text)]">
              {row.device ? `${row.brand?.name ?? ""} ${row.device.model}`.trim() : row.customerDevice.device_id}
            </div>
            <div className="truncate text-xs text-zinc-600">{row.customerDevice.id}</div>
          </div>
        ),
        className: "min-w-[280px]",
      },
      {
        id: "client",
        header: "Client",
        cell: (row) => <div className="text-sm text-zinc-700">{row.client?.name ?? row.customerDevice.client_id}</div>,
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
        cell: (row) => <Badge variant="default">{row.customerDevice.serial_number ?? "—"}</Badge>,
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
      <DataTable
        title={typeof tenantSlug === "string" ? `Customer Devices · ${tenantSlug}` : "Customer Devices"}
        data={rows}
        loading={loading}
        emptyMessage="No customer devices."
        columns={columns}
        getRowId={(row) => row.customerDevice.id}
      />
    </ListPageShell>
  );
}
