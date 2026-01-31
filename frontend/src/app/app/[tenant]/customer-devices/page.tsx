"use client";

import React from "react";
import { useParams, useRouter } from "next/navigation";
import { RequireAuth } from "@/components/RequireAuth";
import { useAuth } from "@/lib/auth";
import { Button } from "@/components/ui/Button";
import { Badge } from "@/components/ui/Badge";
import { Alert } from "@/components/ui/Alert";
import { Card, CardContent } from "@/components/ui/Card";
import { ConfirmDialog } from "@/components/ui/ConfirmDialog";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { DropdownMenu, DropdownMenuItem, DropdownMenuSeparator } from "@/components/ui/DropdownMenu";
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
  pin: string | null;
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
  const auth = useAuth();
  const router = useRouter();
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [status, setStatus] = React.useState<string | null>(null);
  const [busy, setBusy] = React.useState(false);

  const [customerDevices, setCustomerDevices] = React.useState<ApiCustomerDevice[]>([]);
  const [clients, setClients] = React.useState<ApiClient[]>([]);
  const [devices, setDevices] = React.useState<ApiDevice[]>([]);
  const [brands, setBrands] = React.useState<ApiDeviceBrand[]>([]);
  const [types, setTypes] = React.useState<ApiDeviceType[]>([]);

  const [query, setQuery] = React.useState("");
  const [pageIndex, setPageIndex] = React.useState(0);
  const [pageSize, setPageSize] = React.useState(10);

  const [editOpen, setEditOpen] = React.useState(false);
  const [editId, setEditId] = React.useState<number | null>(null);
  const [editCustomerId, setEditCustomerId] = React.useState<number | null>(null);
  const [editDeviceId, setEditDeviceId] = React.useState<number | null>(null);
  const [editLabel, setEditLabel] = React.useState("");
  const [editSerial, setEditSerial] = React.useState("");
  const [editPin, setEditPin] = React.useState("");
  const [editNotes, setEditNotes] = React.useState("");

  const [confirmOpen, setConfirmOpen] = React.useState(false);
  const [confirmTitle, setConfirmTitle] = React.useState("");
  const [confirmMessage, setConfirmMessage] = React.useState<React.ReactNode>("");
  const [confirmAction, setConfirmAction] = React.useState<(() => Promise<void>) | null>(null);

  const [extraOpen, setExtraOpen] = React.useState(false);
  const [extraBusy, setExtraBusy] = React.useState(false);
  const [extraError, setExtraError] = React.useState<string | null>(null);
  const [extraStatus, setExtraStatus] = React.useState<string | null>(null);
  const [extraFor, setExtraFor] = React.useState<Row | null>(null);
  const [extraFields, setExtraFields] = React.useState<ApiCustomerDeviceExtraField[]>([]);

  const canManage = auth.can("customer_devices.manage");

  const load = React.useCallback(async () => {
    try {
      setLoading(true);
      setError(null);

      if (typeof tenantSlug !== "string" || tenantSlug.length === 0) {
        throw new Error("Business is missing.");
      }

      const [cdRes, clientsRes, devicesRes, brandsRes, typesRes] = await Promise.all([
        apiFetch<{ customer_devices: ApiCustomerDevice[] }>(`/api/${tenantSlug}/app/repairbuddy/customer-devices?limit=200`),
        apiFetch<{ clients: ApiClient[] }>(`/api/${tenantSlug}/app/clients?limit=200`),
        apiFetch<{ devices: ApiDevice[] }>(`/api/${tenantSlug}/app/repairbuddy/devices?limit=200`),
        apiFetch<{ device_brands: ApiDeviceBrand[] }>(`/api/${tenantSlug}/app/repairbuddy/device-brands?limit=200`),
        apiFetch<{ device_types: ApiDeviceType[] }>(`/api/${tenantSlug}/app/repairbuddy/device-types?limit=200`),
      ]);

      setCustomerDevices(Array.isArray(cdRes?.customer_devices) ? cdRes.customer_devices : []);
      setClients(Array.isArray(clientsRes?.clients) ? clientsRes.clients : []);
      setDevices(Array.isArray(devicesRes?.devices) ? devicesRes.devices : []);
      setBrands(Array.isArray(brandsRes?.device_brands) ? brandsRes.device_brands : []);
      setTypes(Array.isArray(typesRes?.device_types) ? typesRes.device_types : []);
    } catch (e) {
      if (e instanceof ApiError && e.status === 428 && typeof tenantSlug === "string" && tenantSlug.length > 0) {
        const next = `/app/${tenantSlug}/customer-devices`;
        router.replace(`/app/${tenantSlug}/branches/select?next=${encodeURIComponent(next)}`);
        return;
      }

      setError(e instanceof Error ? e.message : "Failed to load customer devices.");
    } finally {
      setLoading(false);
    }
  }, [router, tenantSlug]);

  React.useEffect(() => {
    void load();
  }, [load]);

  function openCreate() {
    if (!canManage) return;
    setEditId(null);
    setEditCustomerId(clients.length > 0 ? clients[0].id : null);
    setEditDeviceId(null);
    setEditLabel("");
    setEditSerial("");
    setEditPin("");
    setEditNotes("");
    setEditOpen(true);
    setError(null);
    setStatus(null);
  }

  function openEdit(row: Row) {
    if (!canManage) return;
    setEditId(row.customerDevice.id);
    setEditCustomerId(row.customerDevice.customer_id);
    setEditDeviceId(typeof row.customerDevice.device_id === "number" ? row.customerDevice.device_id : null);
    setEditLabel(row.customerDevice.label ?? "");
    setEditSerial(row.customerDevice.serial ?? "");
    setEditPin(row.customerDevice.pin ?? "");
    setEditNotes(row.customerDevice.notes ?? "");
    setEditOpen(true);
    setError(null);
    setStatus(null);
  }

  function openConfirm(args: { title: string; message: React.ReactNode; action: () => Promise<void> }) {
    setConfirmTitle(args.title);
    setConfirmMessage(args.message);
    setConfirmAction(() => args.action);
    setConfirmOpen(true);
  }

  async function onSaveCustomerDevice(e: React.FormEvent) {
    e.preventDefault();
    if (busy) return;
    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
    if (!canManage) {
      setError("Forbidden.");
      return;
    }

    setBusy(true);
    setError(null);
    setStatus(null);

    try {
      if (typeof editCustomerId !== "number") {
        setError("Client is required.");
        return;
      }

      const label = editLabel.trim();
      if (label.length === 0) {
        setError("Label is required.");
        return;
      }

      const payload = {
        customer_id: editCustomerId,
        device_id: editDeviceId,
        label,
        serial: editSerial.trim().length > 0 ? editSerial.trim() : null,
        pin: editPin.trim().length > 0 ? editPin.trim() : null,
        notes: editNotes.trim().length > 0 ? editNotes.trim() : null,
      };

      if (editId) {
        await apiFetch<{ customer_device: ApiCustomerDevice }>(`/api/${tenantSlug}/app/repairbuddy/customer-devices/${editId}`, {
          method: "PATCH",
          body: payload,
        });
        setStatus("Customer device updated.");
      } else {
        await apiFetch<{ customer_device: ApiCustomerDevice }>(`/api/${tenantSlug}/app/repairbuddy/customer-devices`, {
          method: "POST",
          body: payload,
        });
        setStatus("Customer device created.");
      }

      setEditOpen(false);
      setPageIndex(0);
      await load();
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError(editId ? "Failed to update customer device." : "Failed to create customer device.");
      }
    } finally {
      setBusy(false);
    }
  }

  async function onDeleteCustomerDevice(row: Row) {
    if (busy) return;
    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
    if (!canManage) {
      setError("Forbidden.");
      return;
    }

    const deviceLabel = row.device ? `${row.brand?.name ?? ""} ${row.device.model}`.trim() : row.customerDevice.label;

    openConfirm({
      title: "Delete customer device",
      message: (
        <div>
          Delete <span className="font-semibold">{deviceLabel}</span>?
        </div>
      ),
      action: async () => {
        setBusy(true);
        setError(null);
        setStatus(null);
        try {
          await apiFetch<{ message: string }>(`/api/${tenantSlug}/app/repairbuddy/customer-devices/${row.customerDevice.id}`, {
            method: "DELETE",
          });
          setStatus("Customer device deleted.");
          setPageIndex(0);
          await load();
        } catch (err) {
          if (err instanceof ApiError) {
            setError(err.message);
          } else {
            setError("Failed to delete customer device.");
          }
        } finally {
          setBusy(false);
        }
      },
    });
  }

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
        headerClassName: "text-right",
        className: "w-[160px] whitespace-nowrap text-right",
        cell: (row) => {
          if (!canManage) {
            return (
              <div className="flex justify-end">
                <Button variant="outline" size="sm" onClick={() => void openExtraFields(row)}>
                  Extra fields
                </Button>
              </div>
            );
          }

          return (
            <DropdownMenu
              align="right"
              trigger={({ toggle }) => (
                <Button variant="ghost" size="sm" onClick={toggle} disabled={busy} className="px-2">
                  Actions
                </Button>
              )}
            >
              {({ close }) => (
                <>
                  <DropdownMenuItem
                    onSelect={() => {
                      close();
                      openEdit(row);
                    }}
                    disabled={busy}
                  >
                    Edit
                  </DropdownMenuItem>
                  <DropdownMenuItem
                    onSelect={() => {
                      close();
                      void openExtraFields(row);
                    }}
                    disabled={busy}
                  >
                    Extra fields
                  </DropdownMenuItem>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem
                    destructive
                    onSelect={() => {
                      close();
                      void onDeleteCustomerDevice(row);
                    }}
                    disabled={busy}
                  >
                    Delete
                  </DropdownMenuItem>
                </>
              )}
            </DropdownMenu>
          );
        },
      },
    ],
    [busy, canManage, tenantSlug],
  );

  return (
    <RequireAuth requiredPermission="customer_devices.view">
      <div className="space-y-4">
        <ConfirmDialog
          open={confirmOpen}
          title={confirmTitle}
          message={confirmMessage}
          busy={busy}
          confirmText="Delete"
          confirmVariant="secondary"
          onCancel={() => {
            if (busy) return;
            setConfirmOpen(false);
            setConfirmAction(null);
          }}
          onConfirm={() => {
            if (!confirmAction) return;
            void (async () => {
              await confirmAction();
              setConfirmOpen(false);
              setConfirmAction(null);
            })();
          }}
        />

        <Modal
          open={editOpen}
          onClose={() => {
            if (busy) return;
            setEditOpen(false);
          }}
          title={editId ? "Edit customer device" : "Add customer device"}
          footer={
            <div className="flex items-center justify-end gap-2">
              <Button variant="outline" onClick={() => setEditOpen(false)} disabled={busy}>
                Cancel
              </Button>
              <Button disabled={busy} type="submit" form="rb_customer_device_form">
                {busy ? "Saving..." : "Save"}
              </Button>
            </div>
          }
        >
          <form id="rb_customer_device_form" className="space-y-3" onSubmit={onSaveCustomerDevice}>
            <div className="space-y-1">
              <label className="text-sm font-medium" htmlFor="cd_customer">
                Client
              </label>
              <select
                id="cd_customer"
                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={editCustomerId ?? ""}
                onChange={(e) => {
                  const raw = e.target.value;
                  if (!raw) {
                    setEditCustomerId(null);
                    return;
                  }
                  const n = Number(raw);
                  setEditCustomerId(Number.isFinite(n) ? n : null);
                }}
                disabled={busy}
              >
                {clients
                  .slice()
                  .sort((a, b) => a.name.localeCompare(b.name))
                  .map((c) => (
                    <option key={c.id} value={c.id}>
                      {c.name}
                    </option>
                  ))}
              </select>
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium" htmlFor="cd_device">
                Device model (optional)
              </label>
              <select
                id="cd_device"
                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={editDeviceId ?? ""}
                onChange={(e) => {
                  const raw = e.target.value;
                  if (!raw) {
                    setEditDeviceId(null);
                    return;
                  }
                  const n = Number(raw);
                  setEditDeviceId(Number.isFinite(n) ? n : null);
                }}
                disabled={busy}
              >
                <option value="">(none)</option>
                {devices
                  .slice()
                  .sort((a, b) => a.model.localeCompare(b.model))
                  .map((d) => (
                    <option key={d.id} value={d.id}>
                      {d.model}
                    </option>
                  ))}
              </select>
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium" htmlFor="cd_label">
                Label
              </label>
              <input
                id="cd_label"
                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={editLabel}
                onChange={(e) => setEditLabel(e.target.value)}
                disabled={busy}
                required
              />
            </div>

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <div className="space-y-1">
                <label className="text-sm font-medium" htmlFor="cd_serial">
                  Serial
                </label>
                <input
                  id="cd_serial"
                  className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                  value={editSerial}
                  onChange={(e) => setEditSerial(e.target.value)}
                  disabled={busy}
                />
              </div>

              <div className="space-y-1">
                <label className="text-sm font-medium" htmlFor="cd_pin">
                  PIN
                </label>
                <input
                  id="cd_pin"
                  className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                  value={editPin}
                  onChange={(e) => setEditPin(e.target.value)}
                  disabled={busy}
                />
              </div>
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium" htmlFor="cd_notes">
                Notes
              </label>
              <textarea
                id="cd_notes"
                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={editNotes}
                onChange={(e) => setEditNotes(e.target.value)}
                disabled={busy}
                rows={4}
              />
            </div>
          </form>
        </Modal>

        {status ? <div className="text-sm text-green-700">{status}</div> : null}
        {error ? <div className="text-sm text-red-600">{error}</div> : null}

        <ListPageShell
          title="Customer Devices"
          description="Devices owned by clients (intake inventory)."
          actions={
            <Button variant="primary" size="sm" onClick={openCreate} disabled={!canManage || loading || busy}>
              Add device
            </Button>
          }
          loading={loading}
          error={null}
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
      </div>
    </RequireAuth>
  );
}
