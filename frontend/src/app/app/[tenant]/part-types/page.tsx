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
import { ImagePickerWithPreview } from "@/components/ui/ImagePickerWithPreview";
import { Modal } from "@/components/ui/Modal";
import { TableSkeleton } from "@/components/ui/Skeleton";
import { ListPageShell } from "@/components/shells/ListPageShell";
import { apiFetch, ApiError } from "@/lib/api";

type ApiPartType = {
  id: number;
  name: string;
  description: string | null;
  image_url: string | null;
  is_active: boolean;
};

type PartTypesPayload = {
  part_types: ApiPartType[];
  meta?: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
};

export default function TenantPartTypesPage() {
  const auth = useAuth();
  const router = useRouter();
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [status, setStatus] = React.useState<string | null>(null);
  const [busy, setBusy] = React.useState(false);
  const [types, setTypes] = React.useState<ApiPartType[]>([]);

  const [editOpen, setEditOpen] = React.useState(false);
  const [editId, setEditId] = React.useState<number | null>(null);
  const [editName, setEditName] = React.useState("");
  const [editDescription, setEditDescription] = React.useState("");
  const [editIsActive, setEditIsActive] = React.useState(true);

  const [editExistingImageUrl, setEditExistingImageUrl] = React.useState<string | null>(null);
  const [imageFile, setImageFile] = React.useState<File | null>(null);

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

      const res = await apiFetch<PartTypesPayload>(`/api/${tenantSlug}/app/repairbuddy/part-types?${qs.toString()}`);
      setTypes(Array.isArray(res.part_types) ? res.part_types : []);
      setTotalRows(typeof res.meta?.total === "number" ? res.meta.total : 0);
      setPageSize(typeof res.meta?.per_page === "number" ? res.meta.per_page : pageSize);
    } catch (e) {
      if (e instanceof ApiError && e.status === 428 && typeof tenantSlug === "string" && tenantSlug.length > 0) {
        const next = `/app/${tenantSlug}/part-types`;
        router.replace(`/app/${tenantSlug}/branches/select?next=${encodeURIComponent(next)}`);
        return;
      }
      setError(e instanceof Error ? e.message : "Failed to load part types.");
    } finally {
      setLoading(false);
    }
  }, [pageIndex, pageSize, query, router, sort?.dir, sort?.id, tenantSlug]);

  React.useEffect(() => {
    void load();
  }, [load]);

  const onRemoveImage = React.useCallback(async () => {
    if (busy) return;
    if (!editId) return;
    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
    if (!canManage) return;

    setBusy(true);
    setError(null);
    setStatus(null);

    try {
      const res = await apiFetch<{ part_type: ApiPartType }>(`/api/${tenantSlug}/app/repairbuddy/part-types/${editId}/image`, {
        method: "DELETE",
      });
      setEditExistingImageUrl(res.part_type?.image_url ?? null);
      setStatus("Image removed.");
      setImageFile(null);
      setPageIndex(0);
      await load();
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError("Failed to remove image.");
      }
    } finally {
      setBusy(false);
    }
  }, [busy, canManage, editId, load, tenantSlug]);

  function openCreate() {
    if (!canManage) return;
    setEditId(null);
    setEditName("");
    setEditDescription("");
    setEditIsActive(true);
    setEditExistingImageUrl(null);
    setImageFile(null);
    setEditOpen(true);
    setError(null);
    setStatus(null);
  }

  function openEdit(row: ApiPartType) {
    if (!canManage) return;
    setEditId(row.id);
    setEditName(row.name);
    setEditDescription(row.description ?? "");
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

      const payload = { name, is_active: editIsActive };
      const payloadWithDesc = { ...payload, description: editDescription.trim().length > 0 ? editDescription.trim() : null };

      let saved: ApiPartType | null = null;

      if (editId) {
        const res = await apiFetch<{ part_type: ApiPartType }>(`/api/${tenantSlug}/app/repairbuddy/part-types/${editId}`, {
          method: "PATCH",
          body: payloadWithDesc,
        });
        saved = res.part_type ?? null;
        setStatus("Part type updated.");
      } else {
        const res = await apiFetch<{ part_type: ApiPartType }>(`/api/${tenantSlug}/app/repairbuddy/part-types`, {
          method: "POST",
          body: payloadWithDesc,
        });
        saved = res.part_type ?? null;
        setStatus("Part type created.");
      }

      const typeId = saved?.id ?? editId;

      if (typeId && imageFile) {
        const formData = new FormData();
        formData.append("image", imageFile);
        const imgRes = await apiFetch<{ part_type: ApiPartType }>(`/api/${tenantSlug}/app/repairbuddy/part-types/${typeId}/image`, {
          method: "POST",
          body: formData,
        });
        setEditExistingImageUrl(imgRes.part_type?.image_url ?? null);
      }

      setEditOpen(false);
      setPageIndex(0);
      await load();
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError(editId ? "Failed to update part type." : "Failed to create part type.");
      }
    } finally {
      setBusy(false);
    }
  }

  async function onDelete(row: ApiPartType) {
    if (busy) return;
    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
    if (!canManage) {
      setError("Forbidden.");
      return;
    }

    openConfirm({
      title: "Delete part type",
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
          await apiFetch<{ message: string }>(`/api/${tenantSlug}/app/repairbuddy/part-types/${row.id}`, {
            method: "DELETE",
          });
          setStatus("Part type deleted.");
          setPageIndex(0);
          await load();
        } catch (err) {
          if (err instanceof ApiError) {
            setError(err.message);
          } else {
            setError("Failed to delete part type.");
          }
        } finally {
          setBusy(false);
        }
      },
    });
  }

  const columns = React.useMemo<Array<DataTableColumn<ApiPartType>>>(
    () => [
      {
        id: "name",
        header: "Part type",
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
        id: "description",
        header: "Description",
        cell: (row) => <div className="text-sm text-zinc-700">{row.description ?? "—"}</div>,
        className: "min-w-[320px]",
      },
      {
        id: "image",
        header: "Image",
        cell: (row) => <div className="text-sm text-zinc-700">{row.image_url ? "Yes" : "—"}</div>,
        className: "whitespace-nowrap",
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
    [busy, canManage],
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
          title={editId ? "Edit part type" : "New part type"}
          footer={
            <div className="flex items-center justify-end gap-2">
              <Button variant="outline" onClick={() => setEditOpen(false)} disabled={busy}>
                Cancel
              </Button>
              <Button disabled={busy} type="submit" form="rb_part_type_form">
                {busy ? "Saving..." : "Save"}
              </Button>
            </div>
          }
        >
          <form id="rb_part_type_form" className="space-y-3" onSubmit={onSave}>
            <div className="space-y-1">
              <label className="text-sm font-medium" htmlFor="part_type_name">
                Name
              </label>
              <input
                id="part_type_name"
                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={editName}
                onChange={(e) => setEditName(e.target.value)}
                required
                disabled={busy}
              />
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium" htmlFor="part_type_description">
                Description
              </label>
              <textarea
                id="part_type_description"
                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={editDescription}
                onChange={(e) => setEditDescription(e.target.value)}
                disabled={busy}
                rows={3}
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
          title="Part Types"
          description="Categories for parts (battery, screen, charger, etc.)."
          actions={
            <Button variant="primary" size="sm" onClick={openCreate} disabled={!canManage || loading || busy}>
              New part type
            </Button>
          }
          loading={loading}
          loadingFallback={
            <Card className="shadow-none">
              <CardContent className="pt-5">
                <TableSkeleton rows={8} columns={6} className="shadow-none" />
              </CardContent>
            </Card>
          }
          error={null}
          empty={!loading && !error && types.length === 0}
          emptyTitle="No part types"
          emptyDescription="Add part types to organize your inventory."
        >
          <Card className="shadow-none">
            <CardContent className="pt-5">
              <DataTable
                title={typeof tenantSlug === "string" ? `Part Types · ${tenantSlug}` : "Part Types"}
                data={types}
                loading={loading}
                emptyMessage="No part types."
                columns={columns}
                getRowId={(row) => row.id}
                search={{
                  placeholder: "Search part types...",
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
