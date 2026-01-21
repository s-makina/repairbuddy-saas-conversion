"use client";

import React, { useEffect, useMemo, useState } from "react";
import { RequireAuth } from "@/components/RequireAuth";
import { useDashboardHeader } from "@/components/DashboardShell";
import { Alert } from "@/components/ui/Alert";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/Card";
import { ConfirmDialog } from "@/components/ui/ConfirmDialog";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { Modal } from "@/components/ui/Modal";
import { Input } from "@/components/ui/Input";
import { ApiError } from "@/lib/api";
import { createBillingInterval, listBillingIntervals, setBillingIntervalActive, updateBillingInterval } from "@/lib/billing";
import { useAuth } from "@/lib/auth";
import type { BillingInterval } from "@/lib/types";

export default function AdminBillingIntervalsPage() {
  const auth = useAuth();
  const dashboardHeader = useDashboardHeader();

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [status, setStatus] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const [intervals, setIntervals] = useState<BillingInterval[]>([]);

  const canWrite = auth.can("admin.billing.write");

  const [editOpen, setEditOpen] = useState(false);
  const [editError, setEditError] = useState<string | null>(null);
  const [editId, setEditId] = useState<number | null>(null);
  const [editName, setEditName] = useState("");
  const [editCode, setEditCode] = useState("");
  const [editMonths, setEditMonths] = useState("1");
  const [editActive, setEditActive] = useState(true);

  const [confirmOpen, setConfirmOpen] = useState(false);
  const [confirmTarget, setConfirmTarget] = useState<BillingInterval | null>(null);

  useEffect(() => {
    dashboardHeader.setHeader({
      title: "Billing Intervals",
      subtitle: "Configure the available subscription billing periods.",
    });

    return () => {
      dashboardHeader.setHeader(null);
    };
  }, [dashboardHeader]);

  useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);
        const res = await listBillingIntervals({ includeInactive: true });
        if (!alive) return;
        const next = Array.isArray(res.billing_intervals) ? res.billing_intervals : [];
        setIntervals(next);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load billing intervals.");
        setIntervals([]);
      } finally {
        if (!alive) return;
        setLoading(false);
      }
    }

    void load();

    return () => {
      alive = false;
    };
  }, []);

  const columns = useMemo<DataTableColumn<BillingInterval>[]>(
    () => [
      {
        id: "name",
        header: "Name",
        cell: (r) => (
          <div className="min-w-0">
            <div className="truncate font-medium">{r.name}</div>
            <div className="mt-0.5 text-xs text-zinc-500">{r.code}</div>
          </div>
        ),
      },
      {
        id: "months",
        header: "Months",
        cell: (r) => <div className="text-sm">{Number.isFinite(r.months) ? r.months : "-"}</div>,
      },
      {
        id: "active",
        header: "Status",
        cell: (r) => (
          <Badge variant={r.is_active ? "success" : "default"}>{r.is_active ? "Active" : "Inactive"}</Badge>
        ),
      },
      {
        id: "actions",
        header: "Actions",
        cell: (r) => (
          <div className="flex items-center justify-end gap-2">
            <Button
              size="sm"
              variant="outline"
              onClick={() => {
                setEditError(null);
                setEditId(r.id);
                setEditName(String(r.name ?? ""));
                setEditCode(String(r.code ?? ""));
                setEditMonths(String(Number.isFinite(r.months) ? r.months : 1));
                setEditActive(Boolean(r.is_active));
                setEditOpen(true);
              }}
              disabled={!canWrite || busy}
            >
              Edit
            </Button>

            <Button
              size="sm"
              variant={r.is_active ? "ghost" : "secondary"}
              onClick={() => {
                setConfirmTarget(r);
                setConfirmOpen(true);
              }}
              disabled={!canWrite || busy}
            >
              {r.is_active ? "Deactivate" : "Activate"}
            </Button>
          </div>
        ),
      },
    ],
    [busy, canWrite],
  );

  function resetEditForm() {
    setEditError(null);
    setEditId(null);
    setEditName("");
    setEditCode("");
    setEditMonths("1");
    setEditActive(true);
  }

  async function reload() {
    setLoading(true);
    setError(null);
    try {
      const res = await listBillingIntervals({ includeInactive: true });
      const next = Array.isArray(res.billing_intervals) ? res.billing_intervals : [];
      setIntervals(next);
    } catch (e) {
      setError(e instanceof Error ? e.message : "Failed to load billing intervals.");
    } finally {
      setLoading(false);
    }
  }

  async function onSaveInterval() {
    if (!canWrite) return;
    if (busy) return;

    setEditError(null);
    setStatus(null);
    setBusy(true);

    try {
      const name = editName.trim();
      const code = editCode.trim();
      const monthsRaw = Number(editMonths);
      const months = Number.isFinite(monthsRaw) ? Math.max(1, Math.trunc(monthsRaw)) : 1;

      if (!name) {
        setEditError("Name is required.");
        return;
      }

      if (editId) {
        if (!code) {
          setEditError("Code is required.");
          return;
        }

        await updateBillingInterval({
          intervalId: editId,
          code,
          name,
          months,
          isActive: editActive,
        });
        setStatus("Interval updated.");
      } else {
        await createBillingInterval({
          code: code || undefined,
          name,
          months,
          isActive: editActive,
        });
        setStatus("Interval created.");
      }

      setEditOpen(false);
      resetEditForm();
      await reload();
    } catch (e) {
      if (e instanceof ApiError) {
        const data: unknown = e.data;
        if (data && typeof data === "object" && "message" in (data as Record<string, unknown>)) {
          setEditError(String((data as Record<string, unknown>).message));
        } else {
          setEditError(e.message);
        }
      } else {
        setEditError(e instanceof Error ? e.message : "Failed to save interval.");
      }
    } finally {
      setBusy(false);
    }
  }

  async function onConfirmToggleActive() {
    if (!canWrite) return;
    if (busy) return;
    if (!confirmTarget) return;

    setStatus(null);
    setError(null);
    setBusy(true);

    try {
      await setBillingIntervalActive({
        intervalId: confirmTarget.id,
        isActive: !confirmTarget.is_active,
      });

      setConfirmOpen(false);
      setConfirmTarget(null);
      setStatus(!confirmTarget.is_active ? "Interval activated." : "Interval deactivated.");
      await reload();
    } catch (e) {
      setError(e instanceof Error ? e.message : "Failed to update interval.");
    } finally {
      setBusy(false);
    }
  }

  return (
    <RequireAuth adminOnly requiredPermission="admin.billing.read">
      <div className="space-y-4">
        {error ? (
          <Alert variant="danger" title="Billing intervals">
            {error}
          </Alert>
        ) : null}

        {status ? (
          <Alert variant="success" title="Billing intervals">
            {status}
          </Alert>
        ) : null}

        {!canWrite ? (
          <Alert variant="info" title="Read-only">
            You have read access. Interval management requires admin billing write permission.
          </Alert>
        ) : null}

        <Card>
          <CardHeader className="flex flex-row items-start justify-between gap-4">
            <div className="min-w-0">
              <CardTitle>Intervals</CardTitle>
              <div className="mt-1 text-sm text-zinc-600">Used when defining price points (monthly, yearly, etc.).</div>
            </div>
            {canWrite ? (
              <Button
                size="sm"
                variant="secondary"
                onClick={() => {
                  resetEditForm();
                  setEditOpen(true);
                }}
                disabled={busy}
              >
                Add interval
              </Button>
            ) : null}
          </CardHeader>
          <CardContent>
            <DataTable
              title="Billing intervals"
              data={intervals}
              loading={loading}
              emptyMessage="No intervals found."
              getRowId={(r) => String(r.id)}
              columns={columns}
            />
          </CardContent>
        </Card>

        <Modal
          open={editOpen}
          onClose={() => {
            if (!busy) {
              setEditOpen(false);
              resetEditForm();
            }
          }}
          title={editId ? "Edit interval" : "Add interval"}
          footer={
            <div className="flex items-center justify-end gap-2">
              <Button
                variant="outline"
                onClick={() => {
                  setEditOpen(false);
                  resetEditForm();
                }}
                disabled={busy}
              >
                Cancel
              </Button>
              <Button variant="primary" onClick={onSaveInterval} disabled={busy}>
                {busy ? "Saving..." : "Save"}
              </Button>
            </div>
          }
        >
          {editError ? (
            <Alert variant="danger" title="Cannot save">
              {editError}
            </Alert>
          ) : null}

          <div className="space-y-3">
            <div className="space-y-1">
              <label className="text-sm font-medium">Name</label>
              <Input value={editName} onChange={(e) => setEditName(e.target.value)} disabled={busy} />
              <div className="text-xs text-zinc-500">Example: Monthly, Quarterly, Yearly</div>
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium">Code</label>
              <Input value={editCode} onChange={(e) => setEditCode(e.target.value)} disabled={busy} />
              <div className="text-xs text-zinc-500">Used internally. Leave blank to auto-generate on create.</div>
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium">Months</label>
              <Input value={editMonths} onChange={(e) => setEditMonths(e.target.value)} disabled={busy} />
            </div>

            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" checked={editActive} onChange={(e) => setEditActive(e.target.checked)} disabled={busy} />
              Active
            </label>
          </div>
        </Modal>

        <ConfirmDialog
          open={confirmOpen}
          title={confirmTarget?.is_active ? "Deactivate interval" : "Activate interval"}
          message={
            <div className="space-y-2">
              <div>
                {confirmTarget?.is_active
                  ? "This will hide the interval from selection for new prices. Existing prices using it will remain unchanged."
                  : "This will make the interval available for new prices."}
              </div>
              <div className="text-xs text-zinc-500">{confirmTarget ? `${confirmTarget.name} (${confirmTarget.months} months)` : null}</div>
            </div>
          }
          confirmText={confirmTarget?.is_active ? "Deactivate" : "Activate"}
          confirmVariant={confirmTarget?.is_active ? "outline" : "primary"}
          busy={busy}
          onCancel={() => {
            if (!busy) {
              setConfirmOpen(false);
              setConfirmTarget(null);
            }
          }}
          onConfirm={onConfirmToggleActive}
        />
      </div>
    </RequireAuth>
  );
}
