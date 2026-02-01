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
import { ListPageShell } from "@/components/shells/ListPageShell";
import { Modal } from "@/components/ui/Modal";
import { apiFetch, ApiError } from "@/lib/api";
import { formatMoney } from "@/lib/money";

type ApiService = {
  id: number;
  name: string;
  description: string | null;
  service_type_id: number | null;
  service_code: string | null;
  time_required: string | null;
  warranty: string | null;
  pick_up_delivery_available: boolean;
  laptop_rental_available: boolean;
  base_price: { currency: string; amount_cents: number } | null;
  tax_id: number | null;
  is_active: boolean;
};

type ApiServiceType = {
  id: number;
  name: string;
  is_active: boolean;
};

export default function TenantServicesPage() {
  const auth = useAuth();
  const router = useRouter();
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [status, setStatus] = React.useState<string | null>(null);
  const [busy, setBusy] = React.useState(false);
  const [services, setServices] = React.useState<ApiService[]>([]);
  const [serviceTypes, setServiceTypes] = React.useState<ApiServiceType[]>([]);
  const [q, setQ] = React.useState("");

  const [pageIndex, setPageIndex] = React.useState(0);
  const [pageSize, setPageSize] = React.useState(10);

  const [editOpen, setEditOpen] = React.useState(false);
  const [editId, setEditId] = React.useState<number | null>(null);
  const [editName, setEditName] = React.useState("");
  const [editDescription, setEditDescription] = React.useState("");
  const [editServiceTypeId, setEditServiceTypeId] = React.useState<number | null>(null);
  const [editServiceCode, setEditServiceCode] = React.useState("");
  const [editTimeRequired, setEditTimeRequired] = React.useState("");
  const [editWarranty, setEditWarranty] = React.useState("");
  const [editPickupAvailable, setEditPickupAvailable] = React.useState(false);
  const [editLaptopRentalAvailable, setEditLaptopRentalAvailable] = React.useState(false);
  const [editBasePrice, setEditBasePrice] = React.useState("");
  const [editIsActive, setEditIsActive] = React.useState(true);

  const [confirmOpen, setConfirmOpen] = React.useState(false);
  const [confirmTitle, setConfirmTitle] = React.useState("");
  const [confirmMessage, setConfirmMessage] = React.useState<React.ReactNode>("");
  const [confirmAction, setConfirmAction] = React.useState<(() => Promise<void>) | null>(null);

  const canManage = auth.can("services.manage");

  React.useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);
        setStatus(null);

        if (typeof tenantSlug !== "string" || tenantSlug.length === 0) {
          throw new Error("Business is missing.");
        }

        const [servicesRes, typesRes] = await Promise.all([
          apiFetch<{ services: ApiService[] }>(`/api/${tenantSlug}/app/repairbuddy/services`),
          apiFetch<{ service_types: ApiServiceType[] }>(`/api/${tenantSlug}/app/repairbuddy/service-types?limit=200`),
        ]);
        if (!alive) return;

        setServices(Array.isArray(servicesRes?.services) ? servicesRes.services : []);
        setServiceTypes(Array.isArray(typesRes?.service_types) ? typesRes.service_types : []);
      } catch (e) {
        if (!alive) return;

        if (e instanceof ApiError && e.status === 428 && typeof tenantSlug === "string" && tenantSlug.length > 0) {
          const next = `/app/${tenantSlug}/services`;
          router.replace(`/app/${tenantSlug}/branches/select?next=${encodeURIComponent(next)}`);
          return;
        }

        setError(e instanceof Error ? e.message : "Failed to load services.");
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

  function openConfirm(args: { title: string; message: React.ReactNode; action: () => Promise<void> }) {
    setConfirmTitle(args.title);
    setConfirmMessage(args.message);
    setConfirmAction(() => args.action);
    setConfirmOpen(true);
  }

  function openCreate() {
    if (!canManage) return;
    setEditId(null);
    setEditName("");
    setEditDescription("");
    setEditServiceTypeId(null);
    setEditServiceCode("");
    setEditTimeRequired("");
    setEditWarranty("");
    setEditPickupAvailable(false);
    setEditLaptopRentalAvailable(false);
    setEditBasePrice("");
    setEditIsActive(true);
    setEditOpen(true);
    setError(null);
    setStatus(null);
  }

  function openEdit(row: ApiService) {
    if (!canManage) return;
    setEditId(row.id);
    setEditName(row.name);
    setEditDescription(row.description ?? "");
    setEditServiceTypeId(typeof row.service_type_id === "number" ? row.service_type_id : null);
    setEditServiceCode(row.service_code ?? "");
    setEditTimeRequired(row.time_required ?? "");
    setEditWarranty(row.warranty ?? "");
    setEditPickupAvailable(Boolean(row.pick_up_delivery_available));
    setEditLaptopRentalAvailable(Boolean(row.laptop_rental_available));
    setEditBasePrice(row.base_price ? String(row.base_price.amount_cents / 100) : "");
    setEditIsActive(Boolean(row.is_active));
    setEditOpen(true);
    setError(null);
    setStatus(null);
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
      const name = editName.trim();
      if (name.length === 0) {
        setError("Name is required.");
        return;
      }

      const rawPrice = editBasePrice.trim();
      const priceAmountCents = rawPrice.length > 0 ? Math.round(Number(rawPrice) * 100) : null;
      if (rawPrice.length > 0 && (!Number.isFinite(Number(rawPrice)) || priceAmountCents === null)) {
        setError("Base price is invalid.");
        return;
      }

      const payload: Record<string, unknown> = {
        name,
        description: editDescription.trim().length > 0 ? editDescription.trim() : null,
        service_type_id: typeof editServiceTypeId === "number" ? editServiceTypeId : null,
        service_code: editServiceCode.trim().length > 0 ? editServiceCode.trim() : null,
        time_required: editTimeRequired.trim().length > 0 ? editTimeRequired.trim() : null,
        warranty: editWarranty.trim().length > 0 ? editWarranty.trim() : null,
        pick_up_delivery_available: Boolean(editPickupAvailable),
        laptop_rental_available: Boolean(editLaptopRentalAvailable),
        base_price_amount_cents: typeof priceAmountCents === "number" ? priceAmountCents : null,
        is_active: Boolean(editIsActive),
      };

      if (editId) {
        const res = await apiFetch<{ service: ApiService }>(`/api/${tenantSlug}/app/repairbuddy/services/${editId}`, {
          method: "PATCH",
          body: payload,
        });
        setStatus("Service updated.");
        setServices((prev) => prev.map((s) => (s.id === editId ? (res.service ?? s) : s)));
      } else {
        const res = await apiFetch<{ service: ApiService }>(`/api/${tenantSlug}/app/repairbuddy/services`, {
          method: "POST",
          body: payload,
        });
        setStatus("Service created.");
        if (res.service) {
          setServices((prev) => [res.service, ...prev]);
        }
      }

      setEditOpen(false);
      setPageIndex(0);
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError(editId ? "Failed to update service." : "Failed to create service.");
      }
    } finally {
      setBusy(false);
    }
  }

  async function onDelete(row: ApiService) {
    if (busy) return;
    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
    if (!canManage) {
      setError("Forbidden.");
      return;
    }

    openConfirm({
      title: "Delete service",
      message: (
        <div className="space-y-2">
          <div>
            You are about to delete <span className="font-semibold">{row.name}</span>.
          </div>
          <div className="text-sm text-zinc-600">This action cannot be undone.</div>
        </div>
      ),
      action: async () => {
        setBusy(true);
        setError(null);
        setStatus(null);
        try {
          await apiFetch(`/api/${tenantSlug}/app/repairbuddy/services/${row.id}`, { method: "DELETE" });
          setServices((prev) => prev.filter((x) => x.id !== row.id));
          setStatus("Service deleted.");
          setPageIndex(0);
        } catch (err) {
          if (err instanceof ApiError) {
            setError(err.message);
          } else {
            setError("Failed to delete service.");
          }
        } finally {
          setBusy(false);
        }
      },
    });
  }

  const filtered = React.useMemo(() => {
    const needle = q.trim().toLowerCase();
    if (!needle) return services;
    return services.filter((s) => `${s.name} ${s.description ?? ""} ${s.id}`.toLowerCase().includes(needle));
  }, [q, services]);

  const totalRows = filtered.length;
  const pageRows = React.useMemo(() => {
    const start = pageIndex * pageSize;
    const end = start + pageSize;
    return filtered.slice(start, end);
  }, [filtered, pageIndex, pageSize]);

  const columns = React.useMemo<Array<DataTableColumn<ApiService>>>(
    () => [
      {
        id: "name",
        header: "Service",
        cell: (row) => (
          <div className="min-w-0">
            <div className="truncate font-semibold text-[var(--rb-text)]">{row.name}</div>
            <div className="truncate text-xs text-zinc-600">{row.id}</div>
          </div>
        ),
        className: "min-w-[260px]",
      },
      {
        id: "desc",
        header: "Description",
        cell: (row) => <div className="text-sm text-zinc-700">{row.description ?? "—"}</div>,
        className: "min-w-[320px]",
      },
      {
        id: "price",
        header: "Base price",
        cell: (row) => {
          if (!row.base_price) return <div className="text-sm text-zinc-600">—</div>;
          return (
            <div className="text-sm font-semibold text-[var(--rb-text)] whitespace-nowrap">
              {formatMoney({ amountCents: row.base_price.amount_cents, currency: row.base_price.currency })}
            </div>
          );
        },
        className: "whitespace-nowrap",
      },
      {
        id: "actions",
        header: "",
        cell: (row) => {
          if (!canManage) return null;
          return (
            <DropdownMenu
              trigger={({ toggle }) => (
                <Button size="sm" variant="outline" disabled={busy} onClick={toggle}>
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
                  >
                    Delete
                  </DropdownMenuItem>
                </>
              )}
            </DropdownMenu>
          );
        },
        className: "w-[1%] whitespace-nowrap",
      },
    ],
    [busy, canManage],
  );

  function onConfirm() {
    void (async () => {
      if (!confirmAction) return;
      await confirmAction();
      setConfirmOpen(false);
    })();
  }

  return (
    <RequireAuth>
      <ListPageShell
        title="Services"
        description="Service catalog used for estimates and jobs."
        actions={
          <Button onClick={openCreate} disabled={!canManage} variant="outline" size="sm">
            New service
          </Button>
        }
        filters={
          <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div className="text-sm font-semibold text-[var(--rb-text)]">Search</div>
            <input
              value={q}
              onChange={(e) => setQ(e.target.value)}
              placeholder="Search services..."
              className="h-10 w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 text-sm sm:max-w-[420px]"
            />
          </div>
        }
        loading={loading}
        error={error}
        empty={!loading && !error && filtered.length === 0}
        emptyTitle="No services"
        emptyDescription="Add services to standardize pricing."
      >
        <Card className="shadow-none">
          <CardContent className="pt-5">
            <DataTable
              title={typeof tenantSlug === "string" ? `Services · ${tenantSlug}` : "Services"}
              data={pageRows}
              loading={loading}
              emptyMessage="No services."
              columns={columns}
              getRowId={(row) => row.id}
              server={{
                query: q,
                onQueryChange: (value) => {
                  setQ(value);
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

        <Modal open={editOpen} onClose={() => setEditOpen(false)} title={editId ? "Edit service" : "New service"}>
          <form className="space-y-4" onSubmit={onSave}>
            <div className="space-y-2">
              <div className="text-sm font-semibold text-[var(--rb-text)]">Name</div>
              <input
                value={editName}
                onChange={(e) => setEditName(e.target.value)}
                placeholder="e.g., Screen replacement"
                className="h-10 w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 text-sm"
              />
            </div>

            <div className="space-y-2">
              <div className="text-sm font-semibold text-[var(--rb-text)]">Description</div>
              <textarea
                value={editDescription}
                onChange={(e) => setEditDescription(e.target.value)}
                rows={3}
                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
              />
            </div>

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <div className="space-y-2">
                <div className="text-sm font-semibold text-[var(--rb-text)]">Service type</div>
                <select
                  value={typeof editServiceTypeId === "number" ? String(editServiceTypeId) : ""}
                  onChange={(e) => {
                    const v = e.target.value;
                    setEditServiceTypeId(v ? Number(v) : null);
                  }}
                  className="h-10 w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 text-sm"
                >
                  <option value="">—</option>
                  {serviceTypes
                    .filter((t) => Boolean(t.is_active) || (typeof editServiceTypeId === "number" && t.id === editServiceTypeId))
                    .map((t) => (
                      <option key={t.id} value={t.id}>
                        {t.name}
                      </option>
                    ))}
                </select>
              </div>

              <div className="space-y-2">
                <div className="text-sm font-semibold text-[var(--rb-text)]">Service code</div>
                <input
                  value={editServiceCode}
                  onChange={(e) => setEditServiceCode(e.target.value)}
                  className="h-10 w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 text-sm"
                />
              </div>
            </div>

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <div className="space-y-2">
                <div className="text-sm font-semibold text-[var(--rb-text)]">Time required</div>
                <input
                  value={editTimeRequired}
                  onChange={(e) => setEditTimeRequired(e.target.value)}
                  placeholder="e.g., 30 min"
                  className="h-10 w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 text-sm"
                />
              </div>

              <div className="space-y-2">
                <div className="text-sm font-semibold text-[var(--rb-text)]">Warranty</div>
                <input
                  value={editWarranty}
                  onChange={(e) => setEditWarranty(e.target.value)}
                  placeholder="e.g., 90 days"
                  className="h-10 w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 text-sm"
                />
              </div>
            </div>

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <div className="space-y-2">
                <div className="text-sm font-semibold text-[var(--rb-text)]">Base price</div>
                <input
                  value={editBasePrice}
                  onChange={(e) => setEditBasePrice(e.target.value)}
                  placeholder="e.g., 49.99"
                  className="h-10 w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 text-sm"
                />
              </div>

              <div className="space-y-2">
                <div className="text-sm font-semibold text-[var(--rb-text)]">Status</div>
                <label className="flex h-10 items-center gap-2 rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 text-sm">
                  <input
                    type="checkbox"
                    checked={editIsActive}
                    onChange={(e) => setEditIsActive(e.target.checked)}
                    className="h-4 w-4"
                  />
                  Active
                </label>
              </div>
            </div>

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <label className="flex items-center gap-2 text-sm">
                <input
                  type="checkbox"
                  checked={editPickupAvailable}
                  onChange={(e) => setEditPickupAvailable(e.target.checked)}
                  className="h-4 w-4"
                />
                Pick-up / delivery available
              </label>

              <label className="flex items-center gap-2 text-sm">
                <input
                  type="checkbox"
                  checked={editLaptopRentalAvailable}
                  onChange={(e) => setEditLaptopRentalAvailable(e.target.checked)}
                  className="h-4 w-4"
                />
                Laptop rental available
              </label>
            </div>

            <div className="flex items-center justify-end gap-2">
              <Button type="button" variant="outline" onClick={() => setEditOpen(false)} disabled={busy}>
                Cancel
              </Button>
              <Button type="submit" disabled={busy}>
                {editId ? "Save" : "Create"}
              </Button>
            </div>
          </form>
        </Modal>

        <ConfirmDialog
          open={confirmOpen}
          title={confirmTitle}
          message={confirmMessage}
          confirmText="Confirm"
          busy={busy}
          onCancel={() => setConfirmOpen(false)}
          onConfirm={onConfirm}
        />

        {status ? <div className="mt-4 text-sm text-emerald-700">{status}</div> : null}
      </ListPageShell>
    </RequireAuth>
  );
}
