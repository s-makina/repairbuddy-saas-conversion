"use client";

import React from "react";
import { useParams, useRouter } from "next/navigation";
import { RequireAuth } from "@/components/RequireAuth";
import { useAuth } from "@/lib/auth";
import { Button } from "@/components/ui/Button";
import { Badge } from "@/components/ui/Badge";
import { Card, CardContent } from "@/components/ui/Card";
import { ConfirmDialog } from "@/components/ui/ConfirmDialog";
import { DataGrid } from "@/components/ui/DataGrid";
import { DropdownMenu, DropdownMenuItem, DropdownMenuSeparator } from "@/components/ui/DropdownMenu";
import { ImagePickerWithPreview } from "@/components/ui/ImagePickerWithPreview";
import { Modal } from "@/components/ui/Modal";
import { TableSkeleton } from "@/components/ui/Skeleton";
import { ListPageShell } from "@/components/shells/ListPageShell";
import { useUrlDataGridState } from "@/components/ui/useUrlDataGridState";
import { apiFetch, ApiError } from "@/lib/api";

type ApiDeviceBrand = {
  id: number;
  name: string;
  image_url: string | null;
  is_active: boolean;
};

type DeviceBrandsPayload = {
  device_brands: ApiDeviceBrand[];
  meta?: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
};

export default function TenantDeviceBrandsPage() {
  const auth = useAuth();
  const router = useRouter();
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const grid = useUrlDataGridState({ defaultPageSize: 12 });
  const statusFilter = grid.getParam("is_active") ?? "all";

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [status, setStatus] = React.useState<string | null>(null);
  const [busy, setBusy] = React.useState(false);
  const [brands, setBrands] = React.useState<ApiDeviceBrand[]>([]);

  const [reloadNonce, setReloadNonce] = React.useState(0);

  const [editOpen, setEditOpen] = React.useState(false);
  const [editId, setEditId] = React.useState<number | null>(null);
  const [editName, setEditName] = React.useState("");
  const [editIsActive, setEditIsActive] = React.useState(true);
  const [editExistingImageUrl, setEditExistingImageUrl] = React.useState<string | null>(null);
  const [imageFile, setImageFile] = React.useState<File | null>(null);

  const [confirmOpen, setConfirmOpen] = React.useState(false);
  const [confirmTitle, setConfirmTitle] = React.useState("");
  const [confirmMessage, setConfirmMessage] = React.useState<React.ReactNode>("");
  const [confirmAction, setConfirmAction] = React.useState<(() => Promise<void>) | null>(null);

  const [totalRows, setTotalRows] = React.useState(0);

  const canManage = auth.can("device_brands.manage");

  const load = React.useCallback(async () => {
    try {
      setLoading(true);
      setError(null);

      if (typeof tenantSlug !== "string" || tenantSlug.length === 0) {
        throw new Error("Business is missing.");
      }

      const qs = new URLSearchParams();
      if (grid.query.trim().length > 0) qs.set("q", grid.query.trim());
      qs.set("page", String(grid.pageIndex + 1));
      qs.set("per_page", String(grid.pageSize));
      if (statusFilter !== "all") qs.set("is_active", statusFilter);

      const res = await apiFetch<DeviceBrandsPayload>(`/api/${tenantSlug}/app/repairbuddy/device-brands?${qs.toString()}`);
      setBrands(Array.isArray(res.device_brands) ? res.device_brands : []);
      setTotalRows(typeof res.meta?.total === "number" ? res.meta.total : 0);
    } catch (e) {
      if (e instanceof ApiError && e.status === 428 && typeof tenantSlug === "string" && tenantSlug.length > 0) {
        const next = `/app/${tenantSlug}/device-brands`;
        router.replace(`/app/${tenantSlug}/branches/select?next=${encodeURIComponent(next)}`);
        return;
      }

      setError(e instanceof Error ? e.message : "Failed to load device brands.");
    } finally {
      setLoading(false);
    }
  }, [grid.pageIndex, grid.pageSize, grid.query, reloadNonce, router, statusFilter, tenantSlug]);

  React.useEffect(() => {
    void load();
  }, [load]);

  function openCreate() {
    if (!canManage) return;
    setEditId(null);
    setEditName("");
    setEditIsActive(true);
    setEditExistingImageUrl(null);
    setImageFile(null);
    setEditOpen(true);
    setError(null);
    setStatus(null);
  }

  function openEdit(row: ApiDeviceBrand) {
    if (!canManage) return;
    setEditId(row.id);
    setEditName(row.name);
    setEditIsActive(Boolean(row.is_active));
    setEditExistingImageUrl(row.image_url ?? null);
    setImageFile(null);
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

      const payload = {
        name,
        is_active: editIsActive,
      };

      let saved: ApiDeviceBrand | null = null;

      if (editId) {
        const res = await apiFetch<{ device_brand: ApiDeviceBrand }>(`/api/${tenantSlug}/app/repairbuddy/device-brands/${editId}`, {
          method: "PATCH",
          body: payload,
        });
        saved = res.device_brand ?? null;
        setStatus("Device brand updated.");
      } else {
        const res = await apiFetch<{ device_brand: ApiDeviceBrand }>(`/api/${tenantSlug}/app/repairbuddy/device-brands`, {
          method: "POST",
          body: payload,
        });
        saved = res.device_brand ?? null;
        setStatus("Device brand created.");
      }

      const brandId = saved?.id ?? editId;

      if (brandId && imageFile) {
        const formData = new FormData();
        formData.append("image", imageFile);
        const imgRes = await apiFetch<{ device_brand: ApiDeviceBrand }>(`/api/${tenantSlug}/app/repairbuddy/device-brands/${brandId}/image`, {
          method: "POST",
          body: formData,
        });
        setEditExistingImageUrl(imgRes.device_brand?.image_url ?? null);
      }

      setEditOpen(false);
      grid.onPageIndexChange(0);
      setReloadNonce((n) => n + 1);
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError(editId ? "Failed to update device brand." : "Failed to create device brand.");
      }
    } finally {
      setBusy(false);
    }
  }

  async function onRemoveImage() {
    if (busy) return;
    if (!editId) return;
    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
    if (!canManage) return;

    setBusy(true);
    setError(null);
    setStatus(null);

    try {
      const res = await apiFetch<{ device_brand: ApiDeviceBrand }>(`/api/${tenantSlug}/app/repairbuddy/device-brands/${editId}/image`, {
        method: "DELETE",
      });
      setEditExistingImageUrl(res.device_brand?.image_url ?? null);
      setStatus("Image removed.");
      setImageFile(null);
      grid.onPageIndexChange(0);
      setReloadNonce((n) => n + 1);
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError("Failed to remove image.");
      }
    } finally {
      setBusy(false);
    }
  }

  async function onDelete(row: ApiDeviceBrand) {
    if (busy) return;
    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
    if (!canManage) {
      setError("Forbidden.");
      return;
    }

    openConfirm({
      title: "Delete device brand",
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
          await apiFetch<{ message: string }>(`/api/${tenantSlug}/app/repairbuddy/device-brands/${row.id}`, {
            method: "DELETE",
          });
          setStatus("Device brand deleted.");
          grid.onPageIndexChange(0);
          setReloadNonce((n) => n + 1);
        } catch (err) {
          if (err instanceof ApiError) {
            setError(err.message);
          } else {
            setError("Failed to delete device brand.");
          }
        } finally {
          setBusy(false);
        }
      },
    });
  }

  return (
    <RequireAuth requiredPermission="device_brands.view">
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
          title={editId ? "Edit device brand" : "New device brand"}
          footer={
            <div className="flex items-center justify-end gap-2">
              <Button variant="outline" onClick={() => setEditOpen(false)} disabled={busy}>
                Cancel
              </Button>
              <Button disabled={busy} type="submit" form="rb_device_brand_form">
                {busy ? "Saving..." : "Save"}
              </Button>
            </div>
          }
        >
          <form id="rb_device_brand_form" className="space-y-3" onSubmit={onSave}>
            <div className="space-y-1">
              <label className="text-sm font-medium" htmlFor="device_brand_name">
                Name
              </label>
              <input
                id="device_brand_name"
                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={editName}
                onChange={(e) => setEditName(e.target.value)}
                required
                disabled={busy}
              />
            </div>

            <ImagePickerWithPreview
              label="Image"
              file={imageFile}
              existingUrl={editExistingImageUrl}
              disabled={busy}
              onFileChange={(next) => {
                setError(null);
                setStatus(null);
                setImageFile(next);
              }}
              onRemoveExisting={editId ? () => void onRemoveImage() : undefined}
              onError={(message) => {
                setError(message);
                setStatus(null);
              }}
            />

            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" checked={editIsActive} onChange={(e) => setEditIsActive(e.target.checked)} disabled={busy} />
              Active
            </label>
          </form>
        </Modal>

        {status ? <div className="text-sm text-green-700">{status}</div> : null}
        {error ? <div className="text-sm text-red-600">{error}</div> : null}

        <ListPageShell
          title="Device Brands"
          description="Brand taxonomy for devices."
          actions={
            <Button variant="primary" size="sm" onClick={openCreate} disabled={!canManage || loading || busy}>
              New brand
            </Button>
          }
          filters={
            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <div className="space-y-1">
                <label className="text-sm font-medium" htmlFor="rb_device_brand_status">
                  Status
                </label>
                <select
                  id="rb_device_brand_status"
                  className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                  value={statusFilter}
                  onChange={(e) => {
                    const next = e.target.value;
                    grid.setParam("is_active", next === "all" ? null : next, { resetPage: true });
                  }}
                  disabled={loading || busy}
                >
                  <option value="all">All</option>
                  <option value="1">Active</option>
                  <option value="0">Inactive</option>
                </select>
              </div>
            </div>
          }
          loading={loading}
          loadingFallback={
            <Card className="shadow-none">
              <CardContent className="pt-5">
                <TableSkeleton rows={8} columns={4} className="shadow-none" />
              </CardContent>
            </Card>
          }
          error={null}
          empty={!loading && !error && brands.length === 0}
          emptyTitle="No brands"
          emptyDescription="Add brands to organize your devices."
        >
          <Card className="shadow-none">
            <CardContent className="pt-5">
              <DataGrid
                title={typeof tenantSlug === "string" ? `Device Brands · ${tenantSlug}` : "Device Brands"}
                data={brands}
                loading={loading}
                emptyMessage="No brands."
                getItemId={(row) => row.id}
                search={{ placeholder: "Search brands..." }}
                filters={[]}
                server={{
                  query: grid.query,
                  onQueryChange: grid.onQueryChange,
                  pageIndex: grid.pageIndex,
                  onPageIndexChange: grid.onPageIndexChange,
                  pageSize: grid.pageSize,
                  onPageSizeChange: grid.onPageSizeChange,
                  totalRows,
                }}
                renderItem={(row) => (
                  <Card className="overflow-hidden shadow-none">
                    <div className="flex h-32 items-center justify-center bg-[var(--rb-surface-muted)]">
                      {row.image_url ? (
                        <img src={row.image_url} alt={row.name} className="h-full w-full object-contain" />
                      ) : (
                        <div className="text-xs text-zinc-500">No image</div>
                      )}
                    </div>
                    <CardContent className="pt-4">
                      <div className="flex items-start justify-between gap-3">
                        <div className="min-w-0">
                          <div className="truncate text-sm font-semibold text-[var(--rb-text)]">{row.name}</div>
                          <div className="truncate text-xs text-zinc-600">{row.id}</div>
                        </div>

                        {canManage ? (
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
                        ) : null}
                      </div>

                      <div className="mt-3 flex items-center justify-between">
                        <Badge variant={row.is_active ? "success" : "default"}>{row.is_active ? "Active" : "Inactive"}</Badge>
                      </div>
                    </CardContent>
                  </Card>
                )}
              />
            </CardContent>
          </Card>
        </ListPageShell>
      </div>
    </RequireAuth>
  );
}
