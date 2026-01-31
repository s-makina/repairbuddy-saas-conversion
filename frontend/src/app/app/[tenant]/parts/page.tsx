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

type ApiPart = {
  id: number;
  name: string;
  sku: string | null;
  part_type_id: number | null;
  part_brand_id: number | null;
  price: { currency: string; amount_cents: number } | null;
  stock: number | null;
  is_active: boolean;
};

type ApiPartType = {
  id: number;
  name: string;
  is_active: boolean;
};

type ApiPartBrand = {
  id: number;
  name: string;
  image_url: string | null;
  is_active: boolean;
};

type PartsPayload = {
  parts: ApiPart[];
  meta?: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
};

type PartTypesPayload = {
  part_types: ApiPartType[];
};

type PartBrandsPayload = {
  part_brands: ApiPartBrand[];
};

export default function TenantPartsPage() {
  const auth = useAuth();
  const router = useRouter();
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [status, setStatus] = React.useState<string | null>(null);
  const [busy, setBusy] = React.useState(false);
  const [parts, setParts] = React.useState<ApiPart[]>([]);

  const [partTypes, setPartTypes] = React.useState<ApiPartType[]>([]);
  const [partBrands, setPartBrands] = React.useState<ApiPartBrand[]>([]);

  const [editOpen, setEditOpen] = React.useState(false);
  const [editId, setEditId] = React.useState<number | null>(null);
  const [editName, setEditName] = React.useState("");
  const [editSku, setEditSku] = React.useState("");
  const [editTypeId, setEditTypeId] = React.useState<number | null>(null);
  const [editBrandId, setEditBrandId] = React.useState<number | null>(null);
  const [editPrice, setEditPrice] = React.useState("");
  const [editCurrency, setEditCurrency] = React.useState("EUR");
  const [editStock, setEditStock] = React.useState("");
  const [editIsActive, setEditIsActive] = React.useState(true);

  const [confirmOpen, setConfirmOpen] = React.useState(false);
  const [confirmTitle, setConfirmTitle] = React.useState("");
  const [confirmMessage, setConfirmMessage] = React.useState<React.ReactNode>("");
  const [confirmAction, setConfirmAction] = React.useState<(() => Promise<void>) | null>(null);

  const [query, setQuery] = React.useState("");

  const [pageIndex, setPageIndex] = React.useState(0);
  const [pageSize, setPageSize] = React.useState(10);

  const [totalRows, setTotalRows] = React.useState(0);
  const [sort, setSort] = React.useState<{ id: string; dir: "asc" | "desc" } | null>(null);

  const canManage = auth.can("parts.manage");

  const loadLookups = React.useCallback(async () => {
    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
    try {
      const [typesRes, brandsRes] = await Promise.all([
        apiFetch<PartTypesPayload>(`/api/${tenantSlug}/app/repairbuddy/part-types?limit=200`),
        apiFetch<PartBrandsPayload>(`/api/${tenantSlug}/app/repairbuddy/part-brands?limit=200`),
      ]);

      setPartTypes(Array.isArray(typesRes.part_types) ? typesRes.part_types : []);
      setPartBrands(Array.isArray(brandsRes.part_brands) ? brandsRes.part_brands : []);
    } catch {
      setPartTypes([]);
      setPartBrands([]);
    }
  }, [tenantSlug]);

  const load = React.useCallback(async () => {
    try {
      setLoading(true);
      setError(null);

      if (typeof tenantSlug !== "string" || tenantSlug.length === 0) {
        throw new Error("Business is missing.");
      }

      const qs = new URLSearchParams();
      if (query.trim().length > 0) qs.set("q", query.trim());
      qs.set("page", String(pageIndex + 1));
      qs.set("per_page", String(pageSize));
      if (sort?.id && sort?.dir) {
        qs.set("sort", sort.id);
        qs.set("dir", sort.dir);
      }

      const res = await apiFetch<PartsPayload>(`/api/${tenantSlug}/app/repairbuddy/parts?${qs.toString()}`);
      setParts(Array.isArray(res.parts) ? res.parts : []);
      setTotalRows(typeof res.meta?.total === "number" ? res.meta.total : 0);
      setPageSize(typeof res.meta?.per_page === "number" ? res.meta.per_page : pageSize);
    } catch (e) {
      if (e instanceof ApiError && e.status === 428 && typeof tenantSlug === "string" && tenantSlug.length > 0) {
        const next = `/app/${tenantSlug}/parts`;
        router.replace(`/app/${tenantSlug}/branches/select?next=${encodeURIComponent(next)}`);
        return;
      }

      setError(e instanceof Error ? e.message : "Failed to load parts.");
    } finally {
      setLoading(false);
    }
  }, [pageIndex, pageSize, query, router, sort?.dir, sort?.id, tenantSlug]);

  React.useEffect(() => {
    void load();
  }, [load]);

  React.useEffect(() => {
    void loadLookups();
  }, [loadLookups]);

  function openCreate() {
    if (!canManage) return;
    setEditId(null);
    setEditName("");
    setEditSku("");
    setEditTypeId(null);
    setEditBrandId(null);
    setEditPrice("");
    setEditCurrency("EUR");
    setEditStock("");
    setEditIsActive(true);
    setEditOpen(true);
    setError(null);
    setStatus(null);
  }

  function openEdit(row: ApiPart) {
    if (!canManage) return;
    setEditId(row.id);
    setEditName(row.name);
    setEditSku(row.sku ?? "");
    setEditTypeId(typeof row.part_type_id === "number" ? row.part_type_id : null);
    setEditBrandId(typeof row.part_brand_id === "number" ? row.part_brand_id : null);
    setEditPrice(row.price ? (row.price.amount_cents / 100).toFixed(2) : "");
    setEditCurrency(row.price?.currency ?? "EUR");
    setEditStock(typeof row.stock === "number" ? String(row.stock) : "");
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
      const name = editName.trim();
      if (name.length === 0) {
        setError("Name is required.");
        return;
      }

      const sku = editSku.trim().length > 0 ? editSku.trim() : null;

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

      const stockText = editStock.trim();
      let stock: number | null = null;
      if (stockText.length > 0) {
        const parsed = Number(stockText);
        if (!Number.isFinite(parsed)) {
          setError("Stock is invalid.");
          return;
        }
        stock = Math.trunc(parsed);
      }

      const payload: Record<string, unknown> = {
        name,
        sku,
        part_type_id: editTypeId,
        part_brand_id: editBrandId,
        price_amount_cents: priceAmountCents,
        price_currency: priceCurrency,
        stock,
        is_active: editIsActive,
      };

      if (editId) {
        await apiFetch<{ part: ApiPart }>(`/api/${tenantSlug}/app/repairbuddy/parts/${editId}`, {
          method: "PATCH",
          body: payload,
        });
        setStatus("Part updated.");
      } else {
        await apiFetch<{ part: ApiPart }>(`/api/${tenantSlug}/app/repairbuddy/parts`, {
          method: "POST",
          body: payload,
        });
        setStatus("Part created.");
      }

      setEditOpen(false);
      setPageIndex(0);
      await load();
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError(editId ? "Failed to update part." : "Failed to create part.");
      }
    } finally {
      setBusy(false);
    }
  }

  async function onDelete(row: ApiPart) {
    if (busy) return;
    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
    if (!canManage) {
      setError("Forbidden.");
      return;
    }

    openConfirm({
      title: "Delete part",
      message: (
        <div>
          Delete <span className="font-semibold">{row.name}</span>?
        </div>
      ),
      action: async () => {
        setBusy(true);
        setError(null);
        setStatus(null);
        try {
          await apiFetch<{ message: string }>(`/api/${tenantSlug}/app/repairbuddy/parts/${row.id}`, {
            method: "DELETE",
          });
          setStatus("Part deleted.");
          setPageIndex(0);
          await load();
        } catch (err) {
          if (err instanceof ApiError) {
            setError(err.message);
          } else {
            setError("Failed to delete part.");
          }
        } finally {
          setBusy(false);
        }
      },
    });
  }

  const pageRows = parts;

  const typeNameById = React.useMemo(() => {
    const map = new Map<number, string>();
    for (const t of partTypes) {
      if (typeof t.id === "number") {
        map.set(t.id, t.name);
      }
    }
    return map;
  }, [partTypes]);

  const brandNameById = React.useMemo(() => {
    const map = new Map<number, string>();
    for (const b of partBrands) {
      if (typeof b.id === "number") {
        map.set(b.id, b.name);
      }
    }
    return map;
  }, [partBrands]);

  const columns = React.useMemo<Array<DataTableColumn<ApiPart>>>(
    () => [
      {
        id: "name",
        header: "Part",
        sortId: "name",
        cell: (row) => (
          <div className="min-w-0">
            <div className="truncate font-semibold text-[var(--rb-text)]">{row.name}</div>
            <div className="truncate text-xs text-zinc-600">{row.id}</div>
          </div>
        ),
        className: "min-w-[280px]",
      },
      {
        id: "sku",
        header: "SKU",
        sortId: "sku",
        cell: (row) => <div className="text-sm text-zinc-700">{row.sku ?? "—"}</div>,
        className: "min-w-[200px]",
      },
      {
        id: "type",
        header: "Type",
        cell: (row) => {
          const name = typeof row.part_type_id === "number" ? typeNameById.get(row.part_type_id) : null;
          return <div className="text-sm text-zinc-700">{name ?? "—"}</div>;
        },
        className: "min-w-[160px]",
      },
      {
        id: "brand",
        header: "Brand",
        cell: (row) => {
          const name = typeof row.part_brand_id === "number" ? brandNameById.get(row.part_brand_id) : null;
          return <div className="text-sm text-zinc-700">{name ?? "—"}</div>;
        },
        className: "min-w-[160px]",
      },
      {
        id: "price",
        header: "Price",
        cell: (row) => {
          if (!row.price) return <div className="text-sm text-zinc-600">—</div>;
          return (
            <div className="text-sm font-semibold text-[var(--rb-text)] whitespace-nowrap">
              {formatMoney({ amountCents: row.price.amount_cents, currency: row.price.currency })}
            </div>
          );
        },
        className: "whitespace-nowrap",
      },
      {
        id: "stock",
        header: "Stock",
        sortId: "stock",
        cell: (row) => <div className="text-sm text-zinc-700">{row.stock ?? "—"}</div>,
        className: "whitespace-nowrap",
      },
      {
        id: "active",
        header: "Active",
        sortId: "is_active",
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
    [brandNameById, busy, canManage, typeNameById],
  );

  return (
    <RequireAuth requiredPermission="parts.view">
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
          title={editId ? "Edit part" : "New part"}
          footer={
            <div className="flex items-center justify-end gap-2">
              <Button variant="outline" onClick={() => setEditOpen(false)} disabled={busy}>
                Cancel
              </Button>
              <Button disabled={busy} type="submit" form="rb_part_form">
                {busy ? "Saving..." : "Save"}
              </Button>
            </div>
          }
        >
          <form id="rb_part_form" className="space-y-3" onSubmit={onSave}>
            <div className="space-y-1">
              <label className="text-sm font-medium" htmlFor="part_name">
                Name
              </label>
              <input
                id="part_name"
                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={editName}
                onChange={(e) => setEditName(e.target.value)}
                required
                disabled={busy}
              />
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium" htmlFor="part_sku">
                SKU
              </label>
              <input
                id="part_sku"
                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={editSku}
                onChange={(e) => setEditSku(e.target.value)}
                disabled={busy}
              />
            </div>

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <div className="space-y-1">
                <label className="text-sm font-medium" htmlFor="part_type">
                  Part type
                </label>
                <select
                  id="part_type"
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
                  <option value="">(none)</option>
                  {partTypes
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
                <label className="text-sm font-medium" htmlFor="part_brand">
                  Part brand
                </label>
                <select
                  id="part_brand"
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
                  <option value="">(none)</option>
                  {partBrands
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

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
              <div className="space-y-1 sm:col-span-2">
                <label className="text-sm font-medium" htmlFor="part_price">
                  Price
                </label>
                <input
                  id="part_price"
                  className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                  value={editPrice}
                  onChange={(e) => setEditPrice(e.target.value)}
                  disabled={busy}
                  inputMode="decimal"
                />
              </div>
              <div className="space-y-1">
                <label className="text-sm font-medium" htmlFor="part_currency">
                  Currency
                </label>
                <input
                  id="part_currency"
                  className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                  value={editCurrency}
                  onChange={(e) => setEditCurrency(e.target.value)}
                  disabled={busy}
                />
              </div>
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium" htmlFor="part_stock">
                Stock
              </label>
              <input
                id="part_stock"
                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={editStock}
                onChange={(e) => setEditStock(e.target.value)}
                disabled={busy}
                inputMode="numeric"
              />
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
          title="Parts"
          description="Inventory catalog and pricing for parts."
          actions={
            <Button variant="primary" size="sm" onClick={openCreate} disabled={!canManage || loading || busy}>
              New part
            </Button>
          }
          loading={loading}
          error={null}
          empty={!loading && !error && parts.length === 0}
          emptyTitle="No parts"
          emptyDescription="Add parts to track costs and availability."
        >
          <Card className="shadow-none">
            <CardContent className="pt-5">
              <DataTable
                title={typeof tenantSlug === "string" ? `Parts · ${tenantSlug}` : "Parts"}
                data={pageRows}
                loading={loading}
                emptyMessage="No parts."
                columns={columns}
                getRowId={(row) => row.id}
                search={{
                  placeholder: "Search parts...",
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
                  sort,
                  onSortChange: (next) => {
                    setSort(next);
                    setPageIndex(0);
                  },
                }}
              />
            </CardContent>
          </Card>
        </ListPageShell>
      </div>
    </RequireAuth>
  );
}
