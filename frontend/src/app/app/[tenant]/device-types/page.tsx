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
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { DropdownMenu, DropdownMenuItem, DropdownMenuSeparator } from "@/components/ui/DropdownMenu";
import { DataViewToggle, type DataViewMode } from "@/components/ui/DataViewToggle";
import { ImagePickerWithPreview } from "@/components/ui/ImagePickerWithPreview";
import { Modal } from "@/components/ui/Modal";
import { TableSkeleton } from "@/components/ui/Skeleton";
import { ListPageShell } from "@/components/shells/ListPageShell";
import { useUrlDataGridState } from "@/components/ui/useUrlDataGridState";
import { apiFetch, ApiError } from "@/lib/api";
import { MoreHorizontal } from "lucide-react";

type ApiDeviceType = {
  id: number;
  parent_id: number | null;
  name: string;
  description: string | null;
  image_url: string | null;
  is_active: boolean;
};

type DeviceTypesPayload = {
  device_types: ApiDeviceType[];
  meta?: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
};

export default function TenantDeviceTypesPage() {
  const auth = useAuth();
  const router = useRouter();
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const grid = useUrlDataGridState({ defaultPageSize: 12 });
  const viewParam = grid.getParam("view");
  const view: DataViewMode = viewParam === "grid" ? "grid" : "table";

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [status, setStatus] = React.useState<string | null>(null);
  const [busy, setBusy] = React.useState(false);
  const [types, setTypes] = React.useState<ApiDeviceType[]>([]);

  const [reloadNonce, setReloadNonce] = React.useState(0);

  const [editOpen, setEditOpen] = React.useState(false);
  const [editId, setEditId] = React.useState<number | null>(null);
  const [editName, setEditName] = React.useState("");
  const [editParentId, setEditParentId] = React.useState<number | null>(null);
  const [editDescription, setEditDescription] = React.useState("");
  const [editIsActive, setEditIsActive] = React.useState(true);

  const [parentOptions, setParentOptions] = React.useState<ApiDeviceType[]>([]);
  const [imageFile, setImageFile] = React.useState<File | null>(null);

  const [confirmOpen, setConfirmOpen] = React.useState(false);
  const [confirmTitle, setConfirmTitle] = React.useState("");
  const [confirmMessage, setConfirmMessage] = React.useState<React.ReactNode>("");
  const [confirmAction, setConfirmAction] = React.useState<(() => Promise<void>) | null>(null);

  const [totalRows, setTotalRows] = React.useState(0);
  const [sort, setSort] = React.useState<{ id: string; dir: "asc" | "desc" } | null>(null);

  const canManage = auth.can("device_types.manage");

  const loadParentOptions = React.useCallback(async () => {
    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
    try {
      const res = await apiFetch<DeviceTypesPayload>(`/api/${tenantSlug}/app/repairbuddy/device-types?limit=200`);
      setParentOptions(Array.isArray(res.device_types) ? res.device_types : []);
    } catch {
      setParentOptions([]);
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
      if (grid.query.trim().length > 0) qs.set("q", grid.query.trim());
      qs.set("page", String(grid.pageIndex + 1));
      qs.set("per_page", String(grid.pageSize));
      if (sort?.id && sort?.dir) {
        qs.set("sort", sort.id);
        qs.set("dir", sort.dir);
      }

      const res = await apiFetch<DeviceTypesPayload>(`/api/${tenantSlug}/app/repairbuddy/device-types?${qs.toString()}`);
      setTypes(Array.isArray(res.device_types) ? res.device_types : []);
      setTotalRows(typeof res.meta?.total === "number" ? res.meta.total : 0);
    } catch (e) {
      if (e instanceof ApiError && e.status === 428 && typeof tenantSlug === "string" && tenantSlug.length > 0) {
        const next = `/app/${tenantSlug}/device-types`;
        router.replace(`/app/${tenantSlug}/branches/select?next=${encodeURIComponent(next)}`);
        return;
      }

      setError(e instanceof Error ? e.message : "Failed to load device types.");
    } finally {
      setLoading(false);
    }
  }, [grid.pageIndex, grid.pageSize, grid.query, reloadNonce, router, sort?.dir, sort?.id, tenantSlug]);

  React.useEffect(() => {
    void load();
  }, [load]);

  React.useEffect(() => {
    if (!editOpen) return;
    void loadParentOptions();
  }, [editOpen, loadParentOptions]);

  function openCreate() {
    if (!canManage) return;
    setEditId(null);
    setEditName("");
    setEditParentId(null);
    setEditDescription("");
    setEditIsActive(true);
    setImageFile(null);
    setEditOpen(true);
    setError(null);
    setStatus(null);
  }

  function openEdit(row: ApiDeviceType) {
    if (!canManage) return;
    setEditId(row.id);
    setEditName(row.name);
    setEditParentId(typeof row.parent_id === "number" ? row.parent_id : null);
    setEditDescription(typeof row.description === "string" ? row.description : "");
    setEditIsActive(Boolean(row.is_active));
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
        parent_id: editParentId,
        description: editDescription.trim().length > 0 ? editDescription.trim() : null,
        is_active: editIsActive,
      };

      let saved: ApiDeviceType | null = null;

      if (editId) {
        const res = await apiFetch<{ device_type: ApiDeviceType }>(`/api/${tenantSlug}/app/repairbuddy/device-types/${editId}`, {
          method: "PATCH",
          body: payload,
        });
        saved = res.device_type ?? null;
        setStatus("Device type updated.");
      } else {
        const res = await apiFetch<{ device_type: ApiDeviceType }>(`/api/${tenantSlug}/app/repairbuddy/device-types`, {
          method: "POST",
          body: payload,
        });
        saved = res.device_type ?? null;
        setStatus("Device type created.");
      }

      const typeId = saved?.id ?? editId;

      if (typeId && imageFile) {
        const formData = new FormData();
        formData.append("image", imageFile);
        await apiFetch<{ device_type: ApiDeviceType }>(`/api/${tenantSlug}/app/repairbuddy/device-types/${typeId}/image`, {
          method: "POST",
          body: formData,
        });
      }

      setEditOpen(false);
      grid.onPageIndexChange(0);
      setReloadNonce((n) => n + 1);
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError(editId ? "Failed to update device type." : "Failed to create device type.");
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
      await apiFetch<{ device_type: ApiDeviceType }>(`/api/${tenantSlug}/app/repairbuddy/device-types/${editId}/image`, {
        method: "DELETE",
      });

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

  async function onDelete(row: ApiDeviceType) {
    if (busy) return;
    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
    if (!canManage) {
      setError("Forbidden.");
      return;
    }

    openConfirm({
      title: "Delete device type",
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
          await apiFetch<{ message: string }>(`/api/${tenantSlug}/app/repairbuddy/device-types/${row.id}`, {
            method: "DELETE",
          });
          setStatus("Device type deleted.");
          grid.onPageIndexChange(0);
          setReloadNonce((n) => n + 1);
        } catch (err) {
          if (err instanceof ApiError) {
            setError(err.message);
          } else {
            setError("Failed to delete device type.");
          }
        } finally {
          setBusy(false);
        }
      },
    });
  }

  const pageRows = types;

  const parentSelectOptions = React.useMemo(() => {
    const excludeId = editId ?? null;
    return parentOptions
      .filter((t) => (excludeId ? t.id !== excludeId : true))
      .slice()
      .sort((a, b) => a.name.localeCompare(b.name));
  }, [editId, parentOptions]);

  const columns = React.useMemo<Array<DataTableColumn<ApiDeviceType>>>(
    () => [
      {
        id: "name",
        header: "Device type",
        sortId: "name",
        cell: (row) => (
          <div className="min-w-0">
            <div className="truncate font-semibold text-[var(--rb-text)]">{row.name}</div>
            <div className="truncate text-xs text-zinc-600">{row.id}</div>
          </div>
        ),
        className: "min-w-[260px]",
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
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={toggle}
                  disabled={busy}
                  className="px-2"
                  aria-label="Actions"
                  title="Actions"
                >
                  <MoreHorizontal className="h-4 w-4" />
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
    [busy, canManage],
  );

  return (
    <RequireAuth requiredPermission="device_types.view">
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
          title={editId ? "Edit device type" : "New device type"}
          footer={
            <div className="flex items-center justify-end gap-2">
              <Button
                variant="outline"
                onClick={() => setEditOpen(false)}
                disabled={busy}
              >
                Cancel
              </Button>
              <Button disabled={busy} type="submit" form="rb_device_type_form">
                {busy ? "Saving..." : "Save"}
              </Button>
            </div>
          }
        >
          <form id="rb_device_type_form" className="space-y-3" onSubmit={onSave}>
            <div className="space-y-1">
              <label className="text-sm font-medium" htmlFor="device_type_name">
                Name
              </label>
              <input
                id="device_type_name"
                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={editName}
                onChange={(e) => setEditName(e.target.value)}
                required
                disabled={busy}
              />
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium" htmlFor="device_type_parent">
                Parent device type
              </label>
              <select
                id="device_type_parent"
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
                {parentSelectOptions.map((t) => (
                  <option key={t.id} value={t.id}>
                    {t.name}
                  </option>
                ))}
              </select>
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium" htmlFor="device_type_description">
                Description
              </label>
              <textarea
                id="device_type_description"
                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={editDescription}
                onChange={(e) => setEditDescription(e.target.value)}
                rows={4}
                disabled={busy}
              />
            </div>

            <ImagePickerWithPreview
              label="Image"
              file={imageFile}
              existingUrl={editId ? types.find((t) => t.id === editId)?.image_url ?? null : null}
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
              <input
                type="checkbox"
                checked={editIsActive}
                onChange={(e) => setEditIsActive(e.target.checked)}
                disabled={busy}
              />
              Active
            </label>
          </form>
        </Modal>

        {status ? <div className="text-sm text-green-700">{status}</div> : null}
        {error ? <div className="text-sm text-red-600">{error}</div> : null}

        <ListPageShell
          title="Device Types"
          description="Type taxonomy for devices (laptop, desktop, phone, etc.)."
          actions={
            <>
              <DataViewToggle
                value={view}
                onChange={(next) => {
                  grid.setParam("view", next === "table" ? null : next);
                }}
                disabled={loading || busy}
              />
              <Button variant="primary" size="sm" onClick={openCreate} disabled={!canManage || loading || busy}>
                New device type
              </Button>
            </>
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
          empty={!loading && !error && types.length === 0}
          emptyTitle="No device types"
          emptyDescription="Add types to categorize devices."
        >
          <Card className="shadow-none">
            <CardContent className="pt-5">
              {view === "table" ? (
                <DataTable
                  title={typeof tenantSlug === "string" ? `Device Types · ${tenantSlug}` : "Device Types"}
                  data={pageRows}
                  loading={loading}
                  emptyMessage="No device types."
                  columns={columns}
                  getRowId={(row) => row.id}
                  search={{
                    placeholder: "Search types...",
                  }}
                  server={{
                    query: grid.query,
                    onQueryChange: grid.onQueryChange,
                    pageIndex: grid.pageIndex,
                    onPageIndexChange: grid.onPageIndexChange,
                    pageSize: grid.pageSize,
                    onPageSizeChange: grid.onPageSizeChange,
                    totalRows,
                    sort,
                    onSortChange: (next) => {
                      setSort(next);
                      grid.onPageIndexChange(0);
                    },
                  }}
                />
              ) : (
                <DataGrid
                  title={typeof tenantSlug === "string" ? `Device Types · ${tenantSlug}` : "Device Types"}
                  data={pageRows}
                  loading={loading}
                  emptyMessage="No device types."
                  getItemId={(row) => row.id}
                  search={{ placeholder: "Search types..." }}
                  server={{
                    query: grid.query,
                    onQueryChange: grid.onQueryChange,
                    pageIndex: grid.pageIndex,
                    onPageIndexChange: grid.onPageIndexChange,
                    pageSize: grid.pageSize,
                    onPageSizeChange: grid.onPageSizeChange,
                    totalRows,
                  }}
                  onItemClick={canManage ? openEdit : undefined}
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
                                <Button
                                  variant="ghost"
                                  size="sm"
                                  onClick={(e) => {
                                    e.stopPropagation();
                                    toggle();
                                  }}
                                  disabled={busy}
                                  className="px-2"
                                  aria-label="Actions"
                                  title="Actions"
                                >
                                  <MoreHorizontal className="h-4 w-4" />
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
              )}
            </CardContent>
          </Card>
        </ListPageShell>
      </div>
    </RequireAuth>
  );
}
