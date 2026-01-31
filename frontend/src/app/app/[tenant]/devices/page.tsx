"use client";

import React from "react";
import { useParams, useRouter } from "next/navigation";
import { RequireAuth } from "@/components/RequireAuth";
import { useAuth } from "@/lib/auth";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { ConfirmDialog } from "@/components/ui/ConfirmDialog";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { DropdownMenu, DropdownMenuItem, DropdownMenuSeparator } from "@/components/ui/DropdownMenu";
import { Modal } from "@/components/ui/Modal";
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
  const auth = useAuth();
  const router = useRouter();
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [status, setStatus] = React.useState<string | null>(null);
  const [busy, setBusy] = React.useState(false);

  const [devices, setDevices] = React.useState<ApiDevice[]>([]);
  const [brands, setBrands] = React.useState<ApiDeviceBrand[]>([]);
  const [types, setTypes] = React.useState<ApiDeviceType[]>([]);

  const [editOpen, setEditOpen] = React.useState(false);
  const [editId, setEditId] = React.useState<number | null>(null);
  const [editModel, setEditModel] = React.useState("");
  const [editTypeId, setEditTypeId] = React.useState<number | null>(null);
  const [editBrandId, setEditBrandId] = React.useState<number | null>(null);
  const [editParentId, setEditParentId] = React.useState<number | null>(null);
  const [editDisableInBooking, setEditDisableInBooking] = React.useState(false);
  const [editIsOther, setEditIsOther] = React.useState(false);
  const [editIsActive, setEditIsActive] = React.useState(true);

  const [confirmOpen, setConfirmOpen] = React.useState(false);
  const [confirmTitle, setConfirmTitle] = React.useState("");
  const [confirmMessage, setConfirmMessage] = React.useState<React.ReactNode>("");
  const [confirmAction, setConfirmAction] = React.useState<(() => Promise<void>) | null>(null);

  const [query, setQuery] = React.useState("");
  const [pageIndex, setPageIndex] = React.useState(0);
  const [pageSize, setPageSize] = React.useState(10);

  const canManage = auth.can("devices.manage");

  const load = React.useCallback(async () => {
    try {
      setLoading(true);
      setError(null);

      if (typeof tenantSlug !== "string" || tenantSlug.length === 0) {
        throw new Error("Business is missing.");
      }

      const [devicesRes, brandsRes, typesRes] = await Promise.all([
        apiFetch<{ devices: ApiDevice[] }>(`/api/${tenantSlug}/app/repairbuddy/devices?limit=200`),
        apiFetch<{ device_brands: ApiDeviceBrand[] }>(`/api/${tenantSlug}/app/repairbuddy/device-brands?limit=200`),
        apiFetch<{ device_types: ApiDeviceType[] }>(`/api/${tenantSlug}/app/repairbuddy/device-types?limit=200`),
      ]);

      setDevices(Array.isArray(devicesRes.devices) ? devicesRes.devices : []);
      setBrands(Array.isArray(brandsRes.device_brands) ? brandsRes.device_brands : []);
      setTypes(Array.isArray(typesRes.device_types) ? typesRes.device_types : []);
    } catch (e) {
      if (e instanceof ApiError && e.status === 428 && typeof tenantSlug === "string" && tenantSlug.length > 0) {
        const next = `/app/${tenantSlug}/devices`;
        router.replace(`/app/${tenantSlug}/branches/select?next=${encodeURIComponent(next)}`);
        return;
      }

      setError(e instanceof Error ? e.message : "Failed to load devices.");
    } finally {
      setLoading(false);
    }
  }, [router, tenantSlug]);

  React.useEffect(() => {
    void load();
  }, [load]);

  function openEdit(row: ApiDevice) {
    if (!canManage) return;
    setEditId(row.id);
    setEditModel(row.model);
    setEditTypeId(row.device_type_id);
    setEditBrandId(row.device_brand_id);
    setEditParentId(typeof row.parent_device_id === "number" ? row.parent_device_id : null);
    setEditDisableInBooking(Boolean(row.disable_in_booking_form));
    setEditIsOther(Boolean(row.is_other));
    setEditIsActive(Boolean(row.is_active));
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

  async function onSave(e: React.FormEvent) {
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
      const model = editModel.trim();
      if (model.length === 0) {
        setError("Model is required.");
        return;
      }

      if (typeof editTypeId !== "number") {
        setError("Device type is required.");
        return;
      }

      if (typeof editBrandId !== "number") {
        setError("Device brand is required.");
        return;
      }

      const payload = {
        model,
        device_type_id: editTypeId,
        device_brand_id: editBrandId,
        parent_device_id: editParentId,
        disable_in_booking_form: editDisableInBooking,
        is_other: editIsOther,
        is_active: editIsActive,
      };

      if (editId) {
        await apiFetch<{ device: ApiDevice }>(`/api/${tenantSlug}/app/repairbuddy/devices/${editId}`, {
          method: "PATCH",
          body: payload,
        });
        setStatus("Device updated.");
      } else {
        await apiFetch<{ device: ApiDevice }>(`/api/${tenantSlug}/app/repairbuddy/devices`, {
          method: "POST",
          body: payload,
        });
        setStatus("Device created.");
      }

      setEditOpen(false);
      setPageIndex(0);
      await load();
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError(editId ? "Failed to update device." : "Failed to create device.");
      }
    } finally {
      setBusy(false);
    }
  }

  async function onDelete(row: ApiDevice) {
    if (busy) return;
    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
    if (!canManage) {
      setError("Forbidden.");
      return;
    }

    openConfirm({
      title: "Delete device",
      message: (
        <div>
          Delete <span className="font-semibold">{row.model}</span>?
        </div>
      ),
      action: async () => {
        setBusy(true);
        setError(null);
        setStatus(null);
        try {
          await apiFetch<{ message: string }>(`/api/${tenantSlug}/app/repairbuddy/devices/${row.id}`, {
            method: "DELETE",
          });
          setStatus("Device deleted.");
          setPageIndex(0);
          await load();
        } catch (err) {
          if (err instanceof ApiError) {
            setError(err.message);
          } else {
            setError("Failed to delete device.");
          }
        } finally {
          setBusy(false);
        }
      },
    });
  }

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

  const parentOptions = React.useMemo(() => {
    const excludeId = editId ?? null;
    return devices
      .filter((d) => (excludeId ? d.id !== excludeId : true))
      .slice()
      .sort((a, b) => a.model.localeCompare(b.model));
  }, [devices, editId]);

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
      {
        id: "active",
        header: "Active",
        className: "whitespace-nowrap",
        cell: (row) => <div className="text-sm text-zinc-700">{row.is_active ? "Yes" : "No"}</div>,
      },
      {
        id: "actions",
        header: "",
        headerClassName: "text-right",
        className: "whitespace-nowrap text-right",
        cell: (row) => {
          if (!canManage) return null;
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
                  <DropdownMenuSeparator />
                  <DropdownMenuItem
                    destructive
                    onSelect={() => {
                      close();
                      void onDelete(row);
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
    [brandById, busy, canManage, typeById],
  );

  return (
    <RequireAuth requiredPermission="devices.view">
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
          title="Edit device"
          footer={
            <div className="flex items-center justify-end gap-2">
              <Button variant="outline" onClick={() => setEditOpen(false)} disabled={busy}>
                Cancel
              </Button>
              <Button disabled={busy} type="submit" form="rb_device_form">
                {busy ? "Saving..." : "Save"}
              </Button>
            </div>
          }
        >
          <form id="rb_device_form" className="space-y-3" onSubmit={onSave}>
            <div className="space-y-1">
              <label className="text-sm font-medium" htmlFor="device_model">
                Model
              </label>
              <input
                id="device_model"
                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={editModel}
                onChange={(e) => setEditModel(e.target.value)}
                required
                disabled={busy}
              />
            </div>

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <div className="space-y-1">
                <label className="text-sm font-medium" htmlFor="device_type">
                  Device type
                </label>
                <select
                  id="device_type"
                  className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                  value={editTypeId ?? ""}
                  onChange={(e) => {
                    const raw = e.target.value;
                    if (!raw) {
                      setEditTypeId(null);
                      return;
                    }
                    const n = Number(raw);
                    setEditTypeId(Number.isFinite(n) ? n : null);
                  }}
                  disabled={busy}
                >
                  {types
                    .slice()
                    .sort((a, b) => a.name.localeCompare(b.name))
                    .map((t) => (
                      <option key={t.id} value={t.id}>
                        {t.name}
                      </option>
                    ))}
                </select>
              </div>

              <div className="space-y-1">
                <label className="text-sm font-medium" htmlFor="device_brand">
                  Device brand
                </label>
                <select
                  id="device_brand"
                  className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                  value={editBrandId ?? ""}
                  onChange={(e) => {
                    const raw = e.target.value;
                    if (!raw) {
                      setEditBrandId(null);
                      return;
                    }
                    const n = Number(raw);
                    setEditBrandId(Number.isFinite(n) ? n : null);
                  }}
                  disabled={busy}
                >
                  {brands
                    .slice()
                    .sort((a, b) => a.name.localeCompare(b.name))
                    .map((b) => (
                      <option key={b.id} value={b.id}>
                        {b.name}
                      </option>
                    ))}
                </select>
              </div>
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium" htmlFor="device_parent">
                Parent device (variation base)
              </label>
              <select
                id="device_parent"
                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={editParentId ?? ""}
                onChange={(e) => {
                  const raw = e.target.value;
                  if (!raw) {
                    setEditParentId(null);
                    return;
                  }
                  const n = Number(raw);
                  setEditParentId(Number.isFinite(n) ? n : null);
                }}
                disabled={busy}
              >
                <option value="">(none)</option>
                {parentOptions.map((d) => (
                  <option key={d.id} value={d.id}>
                    {d.model}
                  </option>
                ))}
              </select>
            </div>

            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" checked={editDisableInBooking} onChange={(e) => setEditDisableInBooking(e.target.checked)} disabled={busy} />
              Disable in booking form
            </label>

            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" checked={editIsOther} onChange={(e) => setEditIsOther(e.target.checked)} disabled={busy} />
              Is "Other" device
            </label>

            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" checked={editIsActive} onChange={(e) => setEditIsActive(e.target.checked)} disabled={busy} />
              Active
            </label>
          </form>
        </Modal>

        {status ? <div className="text-sm text-green-700">{status}</div> : null}
        {error ? <div className="text-sm text-red-600">{error}</div> : null}

        <ListPageShell
          title="Devices"
          description="Supported device models for your repair catalog."
          actions={
            <Button
              variant="primary"
              size="sm"
              onClick={() => {
                if (!canManage) return;
                if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
                router.push(`/app/${tenantSlug}/devices/new`);
              }}
              disabled={!canManage || loading || busy}
            >
              New device
            </Button>
          }
          loading={loading}
          error={null}
          empty={!loading && !error && devices.length === 0}
          emptyTitle="No devices"
          emptyDescription="Add device models to standardize intake and quoting."
        >
          <Card className="shadow-none">
            <CardContent className="pt-5">
              <DataTable
                // title={typeof tenantSlug === "string" ? `Devices · ${tenantSlug}` : "Devices"}
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
      </div>
    </RequireAuth>
  );
}
