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
import { formatMoney } from "@/lib/money";

type ApiService = {
  id: number;
  name: string;
  base_price: { currency: string; amount_cents: number } | null;
  is_active: boolean;
};

type ApiTax = {
  id: number;
  name: string;
  rate: string;
  is_default: boolean;
};

type ApiDeviceType = {
  id: number;
  name: string;
  is_active: boolean;
};

type ApiDeviceBrand = {
  id: number;
  name: string;
  is_active: boolean;
};

type ApiDevice = {
  id: number;
  model: string;
  device_type_id: number;
  device_brand_id: number;
  parent_device_id: number | null;
  is_active: boolean;
};

type ApiServicePriceOverride = {
  id: number;
  service_id: number;
  scope_type: "device" | "brand" | "type";
  scope_ref_id: number;
  price: { currency: string; amount_cents: number } | null;
  tax_id: number | null;
  is_active: boolean;
};

export default function TenantServicePriceOverridesPage() {
  const auth = useAuth();
  const router = useRouter();
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [status, setStatus] = React.useState<string | null>(null);
  const [busy, setBusy] = React.useState(false);

  const [overrides, setOverrides] = React.useState<ApiServicePriceOverride[]>([]);
  const [services, setServices] = React.useState<ApiService[]>([]);
  const [taxes, setTaxes] = React.useState<ApiTax[]>([]);
  const [devices, setDevices] = React.useState<ApiDevice[]>([]);
  const [brands, setBrands] = React.useState<ApiDeviceBrand[]>([]);
  const [types, setTypes] = React.useState<ApiDeviceType[]>([]);

  const [editOpen, setEditOpen] = React.useState(false);
  const [editId, setEditId] = React.useState<number | null>(null);
  const [editServiceId, setEditServiceId] = React.useState<number | null>(null);
  const [editScopeType, setEditScopeType] = React.useState<ApiServicePriceOverride["scope_type"]>("device");
  const [editScopeRefId, setEditScopeRefId] = React.useState<number | null>(null);
  const [editPrice, setEditPrice] = React.useState("");
  const [editCurrency, setEditCurrency] = React.useState("USD");
  const [editTaxId, setEditTaxId] = React.useState<number | null>(null);
  const [editIsActive, setEditIsActive] = React.useState(true);

  const [confirmOpen, setConfirmOpen] = React.useState(false);
  const [confirmTitle, setConfirmTitle] = React.useState("");
  const [confirmMessage, setConfirmMessage] = React.useState<React.ReactNode>("");
  const [confirmAction, setConfirmAction] = React.useState<(() => Promise<void>) | null>(null);

  const [query, setQuery] = React.useState("");
  const [pageIndex, setPageIndex] = React.useState(0);
  const [pageSize, setPageSize] = React.useState(10);

  const canManage = auth.can("settings.manage");

  const load = React.useCallback(async () => {
    try {
      setLoading(true);
      setError(null);

      if (typeof tenantSlug !== "string" || tenantSlug.length === 0) {
        throw new Error("Business is missing.");
      }

      const [overrideRes, serviceRes, taxRes, deviceRes, brandRes, typeRes] = await Promise.all([
        apiFetch<{ service_price_overrides: ApiServicePriceOverride[] }>(`/api/${tenantSlug}/app/repairbuddy/service-price-overrides?limit=200`),
        apiFetch<{ services: ApiService[] }>(`/api/${tenantSlug}/app/repairbuddy/services?limit=200`),
        apiFetch<{ taxes: ApiTax[] }>(`/api/${tenantSlug}/app/repairbuddy/taxes?limit=200`),
        apiFetch<{ devices: ApiDevice[] }>(`/api/${tenantSlug}/app/repairbuddy/devices?limit=200`),
        apiFetch<{ device_brands: ApiDeviceBrand[] }>(`/api/${tenantSlug}/app/repairbuddy/device-brands?limit=200`),
        apiFetch<{ device_types: ApiDeviceType[] }>(`/api/${tenantSlug}/app/repairbuddy/device-types?limit=200`),
      ]);

      setOverrides(Array.isArray(overrideRes.service_price_overrides) ? overrideRes.service_price_overrides : []);
      setServices(Array.isArray(serviceRes.services) ? serviceRes.services : []);
      setTaxes(Array.isArray(taxRes.taxes) ? taxRes.taxes : []);
      setDevices(Array.isArray(deviceRes.devices) ? deviceRes.devices : []);
      setBrands(Array.isArray(brandRes.device_brands) ? brandRes.device_brands : []);
      setTypes(Array.isArray(typeRes.device_types) ? typeRes.device_types : []);
    } catch (e) {
      if (e instanceof ApiError && e.status === 428 && typeof tenantSlug === "string" && tenantSlug.length > 0) {
        const next = `/app/${tenantSlug}/service-price-overrides`;
        router.replace(`/app/${tenantSlug}/branches/select?next=${encodeURIComponent(next)}`);
        return;
      }
      setError(e instanceof Error ? e.message : "Failed to load service price overrides.");
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
    setEditServiceId(services.length > 0 ? services[0].id : null);
    setEditScopeType("device");
    setEditScopeRefId(devices.length > 0 ? devices[0].id : null);
    setEditPrice("");
    setEditCurrency((auth.tenant?.currency ?? "USD").toUpperCase());
    setEditTaxId(null);
    setEditIsActive(true);
    setEditOpen(true);
    setError(null);
    setStatus(null);
  }

  function openEdit(row: ApiServicePriceOverride) {
    if (!canManage) return;
    setEditId(row.id);
    setEditServiceId(row.service_id);
    setEditScopeType(row.scope_type);
    setEditScopeRefId(row.scope_ref_id);
    setEditPrice(row.price ? (row.price.amount_cents / 100).toFixed(2) : "");
    setEditCurrency(row.price?.currency ?? (auth.tenant?.currency ?? "USD").toUpperCase());
    setEditTaxId(typeof row.tax_id === "number" ? row.tax_id : null);
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

  const serviceById = React.useMemo(() => new Map(services.map((s) => [s.id, s])), [services]);
  const deviceById = React.useMemo(() => new Map(devices.map((d) => [d.id, d])), [devices]);
  const brandById = React.useMemo(() => new Map(brands.map((b) => [b.id, b])), [brands]);
  const typeById = React.useMemo(() => new Map(types.map((t) => [t.id, t])), [types]);
  const taxById = React.useMemo(() => new Map(taxes.map((t) => [t.id, t])), [taxes]);

  const scopeLabel = React.useCallback(
    (o: ApiServicePriceOverride) => {
      if (o.scope_type === "device") return deviceById.get(o.scope_ref_id)?.model ?? `Device #${o.scope_ref_id}`;
      if (o.scope_type === "brand") return brandById.get(o.scope_ref_id)?.name ?? `Brand #${o.scope_ref_id}`;
      return typeById.get(o.scope_ref_id)?.name ?? `Type #${o.scope_ref_id}`;
    },
    [brandById, deviceById, typeById],
  );

  const filtered = React.useMemo(() => {
    const needle = query.trim().toLowerCase();
    if (!needle) return overrides;

    return overrides.filter((o) => {
      const s = serviceById.get(o.service_id)?.name ?? String(o.service_id);
      const scope = scopeLabel(o);
      return `${o.id} ${s} ${o.scope_type} ${scope}`.toLowerCase().includes(needle);
    });
  }, [overrides, query, scopeLabel, serviceById]);

  const totalRows = filtered.length;
  const pageRows = React.useMemo(() => {
    const start = pageIndex * pageSize;
    const end = start + pageSize;
    return filtered.slice(start, end);
  }, [filtered, pageIndex, pageSize]);

  const scopeOptions = React.useMemo(() => {
    if (editScopeType === "device") return devices.slice().sort((a, b) => a.model.localeCompare(b.model)).map((d) => ({ id: d.id, label: d.model }));
    if (editScopeType === "brand") return brands.slice().sort((a, b) => a.name.localeCompare(b.name)).map((b) => ({ id: b.id, label: b.name }));
    return types.slice().sort((a, b) => a.name.localeCompare(b.name)).map((t) => ({ id: t.id, label: t.name }));
  }, [brands, devices, editScopeType, types]);

  React.useEffect(() => {
    if (!editOpen) return;
    if (typeof editScopeRefId === "number") return;
    setEditScopeRefId(scopeOptions.length > 0 ? scopeOptions[0].id : null);
  }, [editOpen, editScopeRefId, scopeOptions]);

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
      if (typeof editServiceId !== "number") {
        setError("Service is required.");
        return;
      }

      if (typeof editScopeRefId !== "number") {
        setError("Scope value is required.");
        return;
      }

      const priceText = editPrice.trim();
      const currency = editCurrency.trim().toUpperCase();

      let priceAmountCents: number | null = null;
      let priceCurrency: string | null = null;

      if (priceText.length > 0) {
        const parsed = Number(priceText);
        if (!Number.isFinite(parsed)) {
          setError("Price is invalid.");
          return;
        }
        priceAmountCents = Math.round(parsed * 100);
        priceCurrency = currency.length > 0 ? currency : null;
      }

      const payload: Record<string, unknown> = {
        service_id: editServiceId,
        scope_type: editScopeType,
        scope_ref_id: editScopeRefId,
        price_amount_cents: priceAmountCents,
        price_currency: priceCurrency,
        tax_id: editTaxId,
        is_active: editIsActive,
      };

      if (editId) {
        await apiFetch<{ service_price_override: ApiServicePriceOverride }>(`/api/${tenantSlug}/app/repairbuddy/service-price-overrides/${editId}`,
          {
            method: "PATCH",
            body: payload,
          },
        );
        setStatus("Override updated.");
      } else {
        await apiFetch<{ service_price_override: ApiServicePriceOverride }>(`/api/${tenantSlug}/app/repairbuddy/service-price-overrides`, {
          method: "POST",
          body: payload,
        });
        setStatus("Override created.");
      }

      setEditOpen(false);
      setPageIndex(0);
      await load();
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError(editId ? "Failed to update override." : "Failed to create override.");
      }
    } finally {
      setBusy(false);
    }
  }

  async function onDelete(row: ApiServicePriceOverride) {
    if (busy) return;
    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
    if (!canManage) {
      setError("Forbidden.");
      return;
    }

    openConfirm({
      title: "Delete override",
      message: (
        <div>
          Delete override <span className="font-semibold">#{row.id}</span>?
        </div>
      ),
      action: async () => {
        setBusy(true);
        setError(null);
        setStatus(null);
        try {
          await apiFetch<{ message: string }>(`/api/${tenantSlug}/app/repairbuddy/service-price-overrides/${row.id}`, {
            method: "DELETE",
          });
          setStatus("Override deleted.");
          setPageIndex(0);
          await load();
        } catch (err) {
          if (err instanceof ApiError) {
            setError(err.message);
          } else {
            setError("Failed to delete override.");
          }
        } finally {
          setBusy(false);
        }
      },
    });
  }

  const columns = React.useMemo<Array<DataTableColumn<ApiServicePriceOverride>>>(
    () => [
      {
        id: "service",
        header: "Service",
        cell: (row) => (
          <div className="min-w-0">
            <div className="truncate font-semibold text-[var(--rb-text)]">{serviceById.get(row.service_id)?.name ?? row.service_id}</div>
            <div className="truncate text-xs text-zinc-600">#{row.id}</div>
          </div>
        ),
        className: "min-w-[260px]",
      },
      {
        id: "scope",
        header: "Scope",
        cell: (row) => (
          <div className="min-w-0">
            <div className="text-sm text-zinc-700">{row.scope_type}</div>
            <div className="truncate text-xs text-zinc-500">{scopeLabel(row)}</div>
          </div>
        ),
        className: "min-w-[220px]",
      },
      {
        id: "price",
        header: "Price",
        cell: (row) => {
          if (!row.price) return <div className="text-sm text-zinc-600">—</div>;
          return <div className="text-sm font-semibold text-[var(--rb-text)] whitespace-nowrap">{formatMoney({ amountCents: row.price.amount_cents, currency: row.price.currency })}</div>;
        },
        className: "whitespace-nowrap",
      },
      {
        id: "tax",
        header: "Tax",
        cell: (row) => {
          if (typeof row.tax_id !== "number") return <div className="text-sm text-zinc-600">—</div>;
          const t = taxById.get(row.tax_id);
          return <div className="text-sm text-zinc-700">{t ? t.name : row.tax_id}</div>;
        },
        className: "min-w-[160px]",
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
    [busy, canManage, scopeLabel, serviceById, taxById],
  );

  return (
    <RequireAuth requiredPermission="settings.manage">
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
          title={editId ? "Edit override" : "New override"}
          footer={
            <div className="flex items-center justify-end gap-2">
              <Button variant="outline" onClick={() => setEditOpen(false)} disabled={busy}>
                Cancel
              </Button>
              <Button disabled={busy} type="submit" form="rb_service_override_form">
                {busy ? "Saving..." : "Save"}
              </Button>
            </div>
          }
        >
          <form id="rb_service_override_form" className="space-y-3" onSubmit={onSave}>
            <div className="space-y-1">
              <label className="text-sm font-medium" htmlFor="override_service">
                Service
              </label>
              <select
                id="override_service"
                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={editServiceId ?? ""}
                onChange={(e) => {
                  const raw = e.target.value;
                  if (!raw) {
                    setEditServiceId(null);
                    return;
                  }
                  const n = Number(raw);
                  setEditServiceId(Number.isFinite(n) ? n : null);
                }}
                disabled={busy}
              >
                {services
                  .slice()
                  .sort((a, b) => a.name.localeCompare(b.name))
                  .map((s) => (
                    <option key={s.id} value={s.id}>
                      {s.name}
                    </option>
                  ))}
              </select>
            </div>

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <div className="space-y-1">
                <label className="text-sm font-medium" htmlFor="override_scope_type">
                  Scope type
                </label>
                <select
                  id="override_scope_type"
                  className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                  value={editScopeType}
                  onChange={(e) => {
                    const v = e.target.value as ApiServicePriceOverride["scope_type"];
                    setEditScopeType(v);
                    setEditScopeRefId(null);
                  }}
                  disabled={busy}
                >
                  <option value="device">device</option>
                  <option value="brand">brand</option>
                  <option value="type">type</option>
                </select>
              </div>

              <div className="space-y-1">
                <label className="text-sm font-medium" htmlFor="override_scope_ref">
                  Scope value
                </label>
                <select
                  id="override_scope_ref"
                  className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                  value={editScopeRefId ?? ""}
                  onChange={(e) => {
                    const raw = e.target.value;
                    if (!raw) {
                      setEditScopeRefId(null);
                      return;
                    }
                    const n = Number(raw);
                    setEditScopeRefId(Number.isFinite(n) ? n : null);
                  }}
                  disabled={busy}
                >
                  {scopeOptions.map((o) => (
                    <option key={`${editScopeType}:${o.id}`} value={o.id}>
                      {o.label}
                    </option>
                  ))}
                </select>
              </div>
            </div>

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
              <div className="space-y-1 sm:col-span-2">
                <label className="text-sm font-medium" htmlFor="override_price">
                  Override price
                </label>
                <input
                  id="override_price"
                  className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                  value={editPrice}
                  onChange={(e) => setEditPrice(e.target.value)}
                  disabled={busy}
                  inputMode="decimal"
                  placeholder="Leave blank to use base"
                />
              </div>
              <div className="space-y-1">
                <label className="text-sm font-medium" htmlFor="override_currency">
                  Currency
                </label>
                <input
                  id="override_currency"
                  className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                  value={editCurrency}
                  onChange={(e) => setEditCurrency(e.target.value)}
                  disabled={busy}
                />
              </div>
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium" htmlFor="override_tax">
                Tax
              </label>
              <select
                id="override_tax"
                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={editTaxId ?? ""}
                onChange={(e) => {
                  const raw = e.target.value;
                  if (!raw) {
                    setEditTaxId(null);
                    return;
                  }
                  const n = Number(raw);
                  setEditTaxId(Number.isFinite(n) ? n : null);
                }}
                disabled={busy}
              >
                <option value="">(none)</option>
                {taxes
                  .slice()
                  .sort((a, b) => a.name.localeCompare(b.name))
                  .map((t) => (
                    <option key={t.id} value={t.id}>
                      {t.name}
                    </option>
                  ))}
              </select>
            </div>

            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" checked={editIsActive} onChange={(e) => setEditIsActive(e.target.checked)} disabled={busy} />
              Active
            </label>
          </form>
        </Modal>

        {status ? <div className="text-sm text-green-700">{status}</div> : null}
        {error ? <div className="text-sm text-red-600">{error}</div> : null}

        <ListPageShell
          title="Service Price Overrides"
          description="Override service pricing by device, brand, or type."
          actions={
            <Button variant="primary" size="sm" onClick={openCreate} disabled={!canManage || loading || busy}>
              New override
            </Button>
          }
          loading={loading}
          error={null}
          empty={!loading && !error && overrides.length === 0}
          emptyTitle="No overrides"
          emptyDescription="Create overrides to adjust service pricing per device/type/brand." 
        >
          <Card className="shadow-none">
            <CardContent className="pt-5">
              <DataTable
                title={typeof tenantSlug === "string" ? `Service Price Overrides · ${tenantSlug}` : "Service Price Overrides"}
                data={pageRows}
                loading={loading}
                emptyMessage="No overrides."
                columns={columns}
                getRowId={(row) => row.id}
                search={{ placeholder: "Search overrides..." }}
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
