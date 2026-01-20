"use client";

import React, { useEffect, useMemo, useState } from "react";
import { RequireAuth } from "@/components/RequireAuth";
import { useDashboardHeader } from "@/components/DashboardShell";
import { Alert } from "@/components/ui/Alert";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { ConfirmDialog } from "@/components/ui/ConfirmDialog";
import { DataTable } from "@/components/ui/DataTable";
import { Input } from "@/components/ui/Input";
import { Modal } from "@/components/ui/Modal";
import {
  createEntitlementDefinition,
  deleteEntitlementDefinition,
  getBillingCatalog,
  updateEntitlementDefinition,
} from "@/lib/billing";
import { useAuth } from "@/lib/auth";
import type { EntitlementDefinition } from "@/lib/types";

export default function AdminBillingEntitlementsPage() {
  const auth = useAuth();
  const dashboardHeader = useDashboardHeader();

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [definitions, setDefinitions] = useState<EntitlementDefinition[]>([]);
  const [reloadNonce, setReloadNonce] = useState(0);

  const [editOpen, setEditOpen] = useState(false);
  const [editBusy, setEditBusy] = useState(false);
  const [editError, setEditError] = useState<string | null>(null);
  const [editId, setEditId] = useState<number | null>(null);
  const [code, setCode] = useState("");
  const [name, setName] = useState("");
  const [valueType, setValueType] = useState("boolean");
  const [description, setDescription] = useState("");
  const [isPremium, setIsPremium] = useState(false);

  const [deleteOpen, setDeleteOpen] = useState(false);
  const [deleteBusy, setDeleteBusy] = useState(false);
  const [deleteTarget, setDeleteTarget] = useState<EntitlementDefinition | null>(null);

  const canWrite = auth.can("admin.billing.write");

  function resetForm() {
    setEditError(null);
    setEditId(null);
    setCode("");
    setName("");
    setValueType("boolean");
    setDescription("");
    setIsPremium(false);
  }

  useEffect(() => {
    dashboardHeader.setHeader({
      breadcrumb: "Admin / Billing",
      title: "Entitlements",
      subtitle: "Entitlement definitions used by billing plan versions",
      actions: (
        <div className="flex items-center gap-2">
          {canWrite ? (
            <Button
              variant="secondary"
              size="sm"
              onClick={() => {
                resetForm();
                setEditOpen(true);
              }}
              disabled={loading}
            >
              New feature
            </Button>
          ) : null}
          <Button variant="outline" size="sm" onClick={() => setReloadNonce((v) => v + 1)} disabled={loading}>
            Refresh
          </Button>
        </div>
      ),
    });

    return () => dashboardHeader.setHeader(null);
  }, [canWrite, dashboardHeader, loading]);

  useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);

        const res = await getBillingCatalog({ includeInactive: true });
        if (!alive) return;

        setDefinitions(Array.isArray(res.entitlement_definitions) ? res.entitlement_definitions : []);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load entitlement definitions.");
        setDefinitions([]);
      } finally {
        if (!alive) return;
        setLoading(false);
      }
    }

    void load();

    return () => {
      alive = false;
    };
  }, [reloadNonce]);

  const rows = useMemo(() => definitions, [definitions]);

  async function onSave() {
    const nextName = name.trim();
    if (!nextName) {
      setEditError("Name is required.");
      return;
    }

    setEditBusy(true);
    setEditError(null);

    try {
      if (editId) {
        await updateEntitlementDefinition({
          id: editId,
          code: code.trim(),
          name: nextName,
          valueType,
          description,
          isPremium,
        });
      } else {
        await createEntitlementDefinition({
          code: code.trim() || undefined,
          name: nextName,
          valueType,
          description,
          isPremium,
        });
      }

      setEditOpen(false);
      setReloadNonce((v) => v + 1);
    } catch (e) {
      setEditError(e instanceof Error ? e.message : "Failed to save entitlement definition.");
    } finally {
      setEditBusy(false);
    }
  }

  return (
    <RequireAuth requiredPermission="admin.billing.read">
      <div className="space-y-6">
        {error ? (
          <Alert variant="danger" title="Could not load entitlements">
            {error}
          </Alert>
        ) : null}

        <Card>
          <CardContent className="pt-5">
            <DataTable
              title="Entitlement definitions"
              data={rows}
              loading={loading}
              emptyMessage="No entitlement definitions found."
              getRowId={(d) => d.id}
              search={{
                placeholder: "Search by name or code…",
                getSearchText: (d) => `${d.name} ${d.code} ${d.value_type}`,
              }}
              columns={[
                {
                  id: "name",
                  header: "Name",
                  cell: (d) => (
                    <div className="min-w-0">
                      <div className="truncate text-sm font-medium text-zinc-800">{d.name}</div>
                      <div className="truncate text-xs text-zinc-500">{d.code}</div>
                    </div>
                  ),
                  className: "min-w-[220px]",
                },
                {
                  id: "type",
                  header: "Type",
                  cell: (d) => <div className="text-sm text-zinc-700">{d.value_type}</div>,
                  className: "whitespace-nowrap",
                },
                {
                  id: "premium",
                  header: "Premium",
                  cell: (d) => (d.is_premium ? <Badge variant="warning">premium</Badge> : <Badge variant="default">—</Badge>),
                  className: "whitespace-nowrap",
                },
                {
                  id: "description",
                  header: "Description",
                  cell: (d) => <div className="text-sm text-zinc-700">{d.description ?? "—"}</div>,
                },
                {
                  id: "actions",
                  header: "",
                  cell: (d) =>
                    canWrite ? (
                      <div className="flex items-center justify-end gap-2">
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => {
                            setEditError(null);
                            setEditId(d.id);
                            setCode(d.code);
                            setName(d.name);
                            setValueType(d.value_type);
                            setDescription(d.description ?? "");
                            setIsPremium(Boolean(d.is_premium));
                            setEditOpen(true);
                          }}
                        >
                          Edit
                        </Button>
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => {
                            setDeleteTarget(d);
                            setDeleteOpen(true);
                          }}
                        >
                          Delete
                        </Button>
                      </div>
                    ) : null,
                  className: "whitespace-nowrap",
                },
              ]}
            />
          </CardContent>
        </Card>

        <Modal
          open={editOpen}
          onClose={() => {
            if (!editBusy) setEditOpen(false);
          }}
          title={editId ? "Edit feature" : "New feature"}
          footer={
            <div className="flex items-center justify-end gap-2">
              <Button variant="outline" onClick={() => (!editBusy ? setEditOpen(false) : null)} disabled={editBusy}>
                Cancel
              </Button>
              <Button variant="primary" onClick={() => void onSave()} disabled={editBusy}>
                {editBusy ? "Saving…" : "Save"}
              </Button>
            </div>
          }
        >
          <div className="space-y-4">
            {editError ? (
              <Alert variant="danger" title="Cannot save">
                {editError}
              </Alert>
            ) : null}

            <div className="space-y-1">
              <label className="text-sm font-medium">Name</label>
              <Input value={name} onChange={(e) => setName(e.target.value)} disabled={editBusy} />
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium">Code</label>
              <Input value={code} onChange={(e) => setCode(e.target.value)} disabled={editBusy} />
              {!editId ? <div className="text-xs text-zinc-500">Optional. If empty, code will be auto-generated.</div> : null}
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium">Value type</label>
              <select
                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={valueType}
                onChange={(e) => setValueType(e.target.value)}
                disabled={editBusy}
              >
                <option value="boolean">boolean</option>
                <option value="integer">integer</option>
                <option value="json">json</option>
              </select>
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium">Description</label>
              <textarea
                className="min-h-[90px] w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={description}
                onChange={(e) => setDescription(e.target.value)}
                disabled={editBusy}
              />
            </div>

            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" checked={isPremium} onChange={(e) => setIsPremium(e.target.checked)} disabled={editBusy} />
              Premium (show in Plan Builder)
            </label>
          </div>
        </Modal>

        <ConfirmDialog
          open={deleteOpen}
          title="Delete feature"
          message={
            <div className="space-y-1">
              <div>Delete this entitlement definition?</div>
              <div className="text-xs text-zinc-500">{deleteTarget ? `${deleteTarget.name} (${deleteTarget.code})` : ""}</div>
            </div>
          }
          confirmText="Delete"
          confirmVariant="outline"
          busy={deleteBusy}
          onCancel={() => {
            if (deleteBusy) return;
            setDeleteOpen(false);
            setDeleteTarget(null);
          }}
          onConfirm={async () => {
            if (!deleteTarget || deleteBusy) return;
            try {
              setDeleteBusy(true);
              await deleteEntitlementDefinition({ id: deleteTarget.id });
              setDeleteOpen(false);
              setDeleteTarget(null);
              setReloadNonce((v) => v + 1);
            } catch (e) {
              setError(e instanceof Error ? e.message : "Failed to delete entitlement definition.");
            } finally {
              setDeleteBusy(false);
            }
          }}
        />
      </div>
    </RequireAuth>
  );
}
