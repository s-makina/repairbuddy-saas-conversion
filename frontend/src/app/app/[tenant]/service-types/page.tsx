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

type ApiServiceType = {
  id: number;
  name: string;
  is_active: boolean;
};

type ServiceTypesPayload = {
  service_types: ApiServiceType[];
  meta?: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
};

export default function TenantServiceTypesPage() {
  const auth = useAuth();
  const router = useRouter();
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [status, setStatus] = React.useState<string | null>(null);
  const [busy, setBusy] = React.useState(false);
  const [types, setTypes] = React.useState<ApiServiceType[]>([]);

  const [editOpen, setEditOpen] = React.useState(false);
  const [editId, setEditId] = React.useState<number | null>(null);
  const [editName, setEditName] = React.useState("");
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

  const canManage = auth.can("service_types.manage");

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

      const res = await apiFetch<ServiceTypesPayload>(`/api/${tenantSlug}/app/repairbuddy/service-types?${qs.toString()}`);
      setTypes(Array.isArray(res.service_types) ? res.service_types : []);
      setTotalRows(typeof res.meta?.total === "number" ? res.meta.total : 0);
      setPageSize(typeof res.meta?.per_page === "number" ? res.meta.per_page : pageSize);
    } catch (e) {
      if (e instanceof ApiError && e.status === 428 && typeof tenantSlug === "string" && tenantSlug.length > 0) {
        const next = `/app/${tenantSlug}/service-types`;
        router.replace(`/app/${tenantSlug}/branches/select?next=${encodeURIComponent(next)}`);
        return;
      }
      setError(e instanceof Error ? e.message : "Failed to load service types.");
    } finally {
      setLoading(false);
    }
  }, [pageIndex, pageSize, query, router, sort?.dir, sort?.id, tenantSlug]);

  React.useEffect(() => {
    void load();
  }, [load]);

  function openCreate() {
    if (!canManage) return;
    setEditId(null);
    setEditName("");
    setEditIsActive(true);
    setEditOpen(true);
    setError(null);
    setStatus(null);
  }

  function openEdit(row: ApiServiceType) {
    if (!canManage) return;
    setEditId(row.id);
    setEditName(row.name);
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

      const payload = { name, is_active: editIsActive };

      if (editId) {
        await apiFetch<{ service_type: ApiServiceType }>(`/api/${tenantSlug}/app/repairbuddy/service-types/${editId}`, {
          method: "PATCH",
          body: payload,
        });
        setStatus("Service type updated.");
      } else {
        await apiFetch<{ service_type: ApiServiceType }>(`/api/${tenantSlug}/app/repairbuddy/service-types`, {
          method: "POST",
          body: payload,
        });
        setStatus("Service type created.");
      }

      setEditOpen(false);
      setPageIndex(0);
      await load();
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError(editId ? "Failed to update service type." : "Failed to create service type.");
      }
    } finally {
      setBusy(false);
    }
  }

  async function onDelete(row: ApiServiceType) {
    if (busy) return;
    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
    if (!canManage) {
      setError("Forbidden.");
      return;
    }

    openConfirm({
      title: "Delete service type",
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
          await apiFetch(`/api/${tenantSlug}/app/repairbuddy/service-types/${row.id}`, { method: "DELETE" });
          setStatus("Service type deleted.");
          setPageIndex(0);
          await load();
        } catch (err) {
          if (err instanceof ApiError) {
            setError(err.message);
          } else {
            setError("Failed to delete service type.");
          }
        } finally {
          setBusy(false);
        }
      },
    });
  }

  const columns = React.useMemo<Array<DataTableColumn<ApiServiceType>>>(
    () => [
      {
        id: "name",
        header: "Type",
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
        header: "Status",
        cell: (row) => (
          <div className={row.is_active ? "text-sm text-emerald-700" : "text-sm text-zinc-600"}>
            {row.is_active ? "Active" : "Inactive"}
          </div>
        ),
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
        title="Service Types"
        description="Organize your service catalog."
        actions={
          <Button onClick={openCreate} disabled={!canManage} variant="outline" size="sm">
            New type
          </Button>
        }
        loading={loading}
        error={error}
        empty={!loading && !error && types.length === 0}
        emptyTitle="No service types"
        emptyDescription="Add types to categorize services."
      >
        <Card className="shadow-none">
          <CardContent className="pt-5">
            <DataTable
              title={typeof tenantSlug === "string" ? `Service Types · ${tenantSlug}` : "Service Types"}
              data={types}
              loading={loading}
              emptyMessage="No service types."
              columns={columns}
              getRowId={(row) => row.id}
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
                onSortChange: setSort,
              }}
            />
          </CardContent>
        </Card>

        <Modal
          open={editOpen}
          onClose={() => setEditOpen(false)}
          title={editId ? "Edit service type" : "New service type"}
        >
          <form className="space-y-4" onSubmit={onSave}>
            <div className="space-y-2">
              <div className="text-sm font-semibold text-[var(--rb-text)]">Name</div>
              <input
                value={editName}
                onChange={(e) => setEditName(e.target.value)}
                placeholder="e.g., Diagnostics"
                className="h-10 w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 text-sm"
              />
            </div>

            <label className="flex items-center gap-2 text-sm">
              <input
                type="checkbox"
                checked={editIsActive}
                onChange={(e) => setEditIsActive(e.target.checked)}
                className="h-4 w-4"
              />
              Active
            </label>

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

        {status ? (
          <div className="mt-4 text-sm text-emerald-700">{status}</div>
        ) : null}
      </ListPageShell>
    </RequireAuth>
  );
}
