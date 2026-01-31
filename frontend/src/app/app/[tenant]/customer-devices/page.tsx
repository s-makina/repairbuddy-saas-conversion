"use client";

import React from "react";
import { useParams, useRouter } from "next/navigation";
import { Button } from "@/components/ui/Button";
import { Badge } from "@/components/ui/Badge";
import { Alert } from "@/components/ui/Alert";
import { Card, CardContent } from "@/components/ui/Card";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { Modal } from "@/components/ui/Modal";
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

type ApiCustomerDeviceExtraField = {
  field_definition_id: number;
  key: string;
  label: string;
  type: string;
  show_in_booking: boolean;
  show_in_invoice: boolean;
  show_in_portal: boolean;
  is_active: boolean;
  value_text: string | null;
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

  const [extraOpen, setExtraOpen] = React.useState(false);
  const [extraBusy, setExtraBusy] = React.useState(false);
  const [extraError, setExtraError] = React.useState<string | null>(null);
  const [extraStatus, setExtraStatus] = React.useState<string | null>(null);
  const [extraFor, setExtraFor] = React.useState<Row | null>(null);
  const [extraFields, setExtraFields] = React.useState<ApiCustomerDeviceExtraField[]>([]);

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

  async function openExtraFields(row: Row) {
    setExtraError(null);
    setExtraStatus(null);

    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) {
      setExtraError("Business is missing.");
      return;
    }

    setExtraFor(row);
    setExtraOpen(true);
    setExtraBusy(true);
    try {
      const res = await apiFetch<{ customer_device_id: number; extra_fields: ApiCustomerDeviceExtraField[] }>(
        `/api/${tenantSlug}/app/repairbuddy/customer-devices/${row.customerDevice.id}/extra-fields`,
      );
      setExtraFields(Array.isArray(res?.extra_fields) ? res.extra_fields : []);
    } catch (e) {
      if (e instanceof ApiError && e.status === 428 && typeof tenantSlug === "string" && tenantSlug.length > 0) {
        const next = `/app/${tenantSlug}/customer-devices`;
        router.replace(`/app/${tenantSlug}/branches/select?next=${encodeURIComponent(next)}`);
        return;
      }
      setExtraError(e instanceof Error ? e.message : "Failed to load extra fields.");
      setExtraFields([]);
    } finally {
      setExtraBusy(false);
    }
  }

  async function saveExtraFields() {
    setExtraError(null);
    setExtraStatus(null);

    if (!extraFor) {
      setExtraError("No customer device selected.");
      return;
    }

    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) {
      setExtraError("Business is missing.");
      return;
    }

    setExtraBusy(true);
    try {
      const res = await apiFetch<{ customer_device_id: number; extra_fields: ApiCustomerDeviceExtraField[] }>(
        `/api/${tenantSlug}/app/repairbuddy/customer-devices/${extraFor.customerDevice.id}/extra-fields`,
        {
          method: "PUT",
          body: {
            values: extraFields.map((f) => ({
              field_definition_id: f.field_definition_id,
              value_text: f.value_text,
            })),
          },
        },
      );
      setExtraFields(Array.isArray(res?.extra_fields) ? res.extra_fields : []);
      setExtraStatus("Saved.");
    } catch (e) {
      setExtraError(e instanceof Error ? e.message : "Failed to save extra fields.");
    } finally {
      setExtraBusy(false);
    }
  }

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
      {
        id: "actions",
        header: "",
        cell: (row) => (
          <div className="flex justify-end">
            <Button variant="outline" size="sm" onClick={() => void openExtraFields(row)}>
              Extra fields
            </Button>
          </div>
        ),
        className: "w-[160px]",
      },
    ],
    [tenantSlug],
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
      <Modal
        open={extraOpen}
        onClose={() => {
          if (!extraBusy) {
            setExtraOpen(false);
            setExtraFor(null);
            setExtraFields([]);
            setExtraError(null);
            setExtraStatus(null);
          }
        }}
        title={extraFor ? `Extra Fields · #${extraFor.customerDevice.id}` : "Extra Fields"}
        footer={
          <div className="flex items-center justify-end gap-2">
            <Button
              variant="outline"
              onClick={() => {
                if (!extraBusy) {
                  setExtraOpen(false);
                  setExtraFor(null);
                  setExtraFields([]);
                  setExtraError(null);
                  setExtraStatus(null);
                }
              }}
              disabled={extraBusy}
            >
              Close
            </Button>
            <Button onClick={() => void saveExtraFields()} disabled={extraBusy}>
              {extraBusy ? "Saving..." : "Save"}
            </Button>
          </div>
        }
      >
        {extraError ? (
          <Alert variant="danger" title="Could not load extra fields">
            {extraError}
          </Alert>
        ) : null}

        {extraStatus ? (
          <Alert variant="success" title="Success">
            {extraStatus}
          </Alert>
        ) : null}

        {extraBusy && extraFields.length === 0 ? <div className="text-sm text-zinc-500">Loading...</div> : null}

        {!extraBusy && extraFields.length === 0 ? <div className="text-sm text-zinc-600">No extra fields defined.</div> : null}

        {extraFields.length > 0 ? (
          <div className="space-y-3">
            {extraFields
              .filter((f) => f.is_active)
              .map((f) => (
                <div key={f.field_definition_id} className="space-y-1">
                  <div className="text-sm font-medium text-[var(--rb-text)]">{f.label}</div>
                  <input
                    className="h-10 w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 text-sm"
                    value={f.value_text ?? ""}
                    onChange={(e) => {
                      const next = e.target.value;
                      setExtraFields((prev) => prev.map((p) => (p.field_definition_id === f.field_definition_id ? { ...p, value_text: next } : p)));
                    }}
                    disabled={extraBusy}
                  />
                </div>
              ))}
          </div>
        ) : null}
      </Modal>

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
