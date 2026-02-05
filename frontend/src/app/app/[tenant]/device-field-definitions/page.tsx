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
import { TableSkeleton } from "@/components/ui/Skeleton";
import { ListPageShell } from "@/components/shells/ListPageShell";
import { apiFetch, ApiError } from "@/lib/api";

type ApiDeviceFieldDefinition = {
  id: number;
  key: string;
  label: string;
  type: string;
  show_in_booking: boolean;
  show_in_invoice: boolean;
  show_in_portal: boolean;
  is_active: boolean;
};

export default function TenantDeviceFieldDefinitionsPage() {
  const auth = useAuth();
  const router = useRouter();
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [status, setStatus] = React.useState<string | null>(null);
  const [busy, setBusy] = React.useState(false);

  const [items, setItems] = React.useState<ApiDeviceFieldDefinition[]>([]);

  const [editOpen, setEditOpen] = React.useState(false);
  const [editId, setEditId] = React.useState<number | null>(null);
  const [editKey, setEditKey] = React.useState("");
  const [editLabel, setEditLabel] = React.useState("");
  const [editShowInBooking, setEditShowInBooking] = React.useState(false);
  const [editShowInInvoice, setEditShowInInvoice] = React.useState(false);
  const [editShowInPortal, setEditShowInPortal] = React.useState(false);
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

      const res = await apiFetch<{ device_field_definitions: ApiDeviceFieldDefinition[] }>(
        `/api/${tenantSlug}/app/repairbuddy/device-field-definitions?limit=200`,
      );

      setItems(Array.isArray(res.device_field_definitions) ? res.device_field_definitions : []);
    } catch (e) {
      if (e instanceof ApiError && e.status === 428 && typeof tenantSlug === "string" && tenantSlug.length > 0) {
        const next = `/app/${tenantSlug}/device-field-definitions`;
        router.replace(`/app/${tenantSlug}/branches/select?next=${encodeURIComponent(next)}`);
        return;
      }
      setError(e instanceof Error ? e.message : "Failed to load device field definitions.");
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
    setEditKey("");
    setEditLabel("");
    setEditShowInBooking(false);
    setEditShowInInvoice(false);
    setEditShowInPortal(false);
    setEditIsActive(true);
    setEditOpen(true);
    setError(null);
    setStatus(null);
  }

  function openEdit(row: ApiDeviceFieldDefinition) {
    if (!canManage) return;
    setEditId(row.id);
    setEditKey(row.key);
    setEditLabel(row.label);
    setEditShowInBooking(Boolean(row.show_in_booking));
    setEditShowInInvoice(Boolean(row.show_in_invoice));
    setEditShowInPortal(Boolean(row.show_in_portal));
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
      const label = editLabel.trim();
      if (label.length === 0) {
        setError("Label is required.");
        return;
      }

      const payload: Record<string, unknown> = {
        label,
        show_in_booking: editShowInBooking,
        show_in_invoice: editShowInInvoice,
        show_in_portal: editShowInPortal,
        is_active: editIsActive,
      };

      if (editId) {
        await apiFetch<{ device_field_definition: ApiDeviceFieldDefinition }>(
          `/api/${tenantSlug}/app/repairbuddy/device-field-definitions/${editId}`,
          {
            method: "PATCH",
            body: payload,
          },
        );
        setStatus("Field updated.");
      } else {
        const key = editKey.trim();
        if (!/^[a-z0-9_]+$/.test(key)) {
          setError("Key must be lowercase and contain only letters, numbers, and underscores.");
          return;
        }

        await apiFetch<{ device_field_definition: ApiDeviceFieldDefinition }>(
          `/api/${tenantSlug}/app/repairbuddy/device-field-definitions`,
          {
            method: "POST",
            body: {
              ...payload,
              key,
            },
          },
        );
        setStatus("Field created.");
      }

      setEditOpen(false);
      setPageIndex(0);
      await load();
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError(editId ? "Failed to update field." : "Failed to create field.");
      }
    } finally {
      setBusy(false);
    }
  }

  async function onDelete(row: ApiDeviceFieldDefinition) {
    if (busy) return;
    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
    if (!canManage) {
      setError("Forbidden.");
      return;
    }

    openConfirm({
      title: "Delete device field",
      message: (
        <div>
          Delete <span className="font-semibold">{row.label}</span>?
        </div>
      ),
      action: async () => {
        setBusy(true);
        setError(null);
        setStatus(null);
        try {
          await apiFetch<{ message: string }>(`/api/${tenantSlug}/app/repairbuddy/device-field-definitions/${row.id}`, {
            method: "DELETE",
          });
          setStatus("Field deleted.");
          setPageIndex(0);
          await load();
        } catch (err) {
          if (err instanceof ApiError) {
            setError(err.message);
          } else {
            setError("Failed to delete field.");
          }
        } finally {
          setBusy(false);
        }
      },
    });
  }

  const filtered = React.useMemo(() => {
    const needle = query.trim().toLowerCase();
    if (!needle) return items;
    return items.filter((d) => `${d.id} ${d.key} ${d.label}`.toLowerCase().includes(needle));
  }, [items, query]);

  const totalRows = filtered.length;
  const pageRows = React.useMemo(() => {
    const start = pageIndex * pageSize;
    const end = start + pageSize;
    return filtered.slice(start, end);
  }, [filtered, pageIndex, pageSize]);

  const columns = React.useMemo<Array<DataTableColumn<ApiDeviceFieldDefinition>>>(
    () => [
      {
        id: "label",
        header: "Field",
        cell: (row) => (
          <div className="min-w-0">
            <div className="truncate font-semibold text-[var(--rb-text)]">{row.label}</div>
            <div className="truncate text-xs text-zinc-600">{row.key}</div>
          </div>
        ),
        className: "min-w-[280px]",
      },
      {
        id: "booking",
        header: "Booking",
        className: "whitespace-nowrap",
        cell: (row) => <div className="text-sm text-zinc-700">{row.show_in_booking ? "Yes" : "No"}</div>,
      },
      {
        id: "invoice",
        header: "Invoice",
        className: "whitespace-nowrap",
        cell: (row) => <div className="text-sm text-zinc-700">{row.show_in_invoice ? "Yes" : "No"}</div>,
      },
      {
        id: "portal",
        header: "Portal",
        className: "whitespace-nowrap",
        cell: (row) => <div className="text-sm text-zinc-700">{row.show_in_portal ? "Yes" : "No"}</div>,
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
    [busy, canManage],
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
          title={editId ? "Edit device field" : "New device field"}
          footer={
            <div className="flex items-center justify-end gap-2">
              <Button variant="outline" onClick={() => setEditOpen(false)} disabled={busy}>
                Cancel
              </Button>
              <Button disabled={busy} type="submit" form="rb_device_field_def_form">
                {busy ? "Saving..." : "Save"}
              </Button>
            </div>
          }
        >
          <form id="rb_device_field_def_form" className="space-y-3" onSubmit={onSave}>
            {!editId ? (
              <div className="space-y-1">
                <label className="text-sm font-medium" htmlFor="field_key">
                  Key
                </label>
                <input
                  id="field_key"
                  className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                  value={editKey}
                  onChange={(e) => setEditKey(e.target.value)}
                  required
                  disabled={busy}
                  placeholder="e.g. password"
                />
              </div>
            ) : (
              <div className="space-y-1">
                <label className="text-sm font-medium" htmlFor="field_key_ro">
                  Key
                </label>
                <input
                  id="field_key_ro"
                  className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                  value={editKey}
                  disabled
                />
              </div>
            )}

            <div className="space-y-1">
              <label className="text-sm font-medium" htmlFor="field_label">
                Label
              </label>
              <input
                id="field_label"
                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={editLabel}
                onChange={(e) => setEditLabel(e.target.value)}
                required
                disabled={busy}
                placeholder="e.g. Password"
              />
            </div>

            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" checked={editShowInBooking} onChange={(e) => setEditShowInBooking(e.target.checked)} disabled={busy} />
              Show in booking
            </label>
            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" checked={editShowInInvoice} onChange={(e) => setEditShowInInvoice(e.target.checked)} disabled={busy} />
              Show in invoice
            </label>
            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" checked={editShowInPortal} onChange={(e) => setEditShowInPortal(e.target.checked)} disabled={busy} />
              Show in portal
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
          title="Device Field Definitions"
          description="Define additional device fields for intake, invoices, and portal."
          actions={
            <Button variant="primary" size="sm" onClick={openCreate} disabled={!canManage || loading || busy}>
              New field
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
          empty={!loading && !error && items.length === 0}
          emptyTitle="No device fields"
          emptyDescription="Create fields like Password, PIN, or Accessories." 
        >
          <Card className="shadow-none">
            <CardContent className="pt-5">
              <DataTable
                title={typeof tenantSlug === "string" ? `Device Fields · ${tenantSlug}` : "Device Fields"}
                data={pageRows}
                loading={loading}
                emptyMessage="No fields."
                columns={columns}
                getRowId={(row) => row.id}
                search={{ placeholder: "Search fields..." }}
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
