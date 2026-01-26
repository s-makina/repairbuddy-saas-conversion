"use client";

import React, { useEffect, useMemo, useState } from "react";
import { useParams } from "next/navigation";
import { apiFetch, ApiError } from "@/lib/api";
import type { Branch, User } from "@/lib/types";
import { RequireAuth } from "@/components/RequireAuth";
import { useAuth } from "@/lib/auth";
import { PageHeader } from "@/components/ui/PageHeader";
import { Card, CardContent } from "@/components/ui/Card";
import { Button } from "@/components/ui/Button";
import { DataTable } from "@/components/ui/DataTable";
import { Modal } from "@/components/ui/Modal";
import { ConfirmDialog } from "@/components/ui/ConfirmDialog";

type BranchesPayload = {
  branches: Branch[];
};

type BranchUsersPayload = {
  users: Pick<User, "id" | "name" | "email" | "status">[];
  assigned_user_ids: number[];
};

function EditIcon({ className }: { className?: string }) {
  return (
    <svg viewBox="0 0 24 24" className={className} fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M12 20h9" />
      <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" />
    </svg>
  );
}

export default function TenantBranchesPage() {
  const auth = useAuth();
  const params = useParams() as { tenant?: string; business?: string };
  const tenant = params.business ?? params.tenant;

  const [loading, setLoading] = useState(true);
  const [branches, setBranches] = useState<Branch[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [status, setStatus] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  const defaultBranchId = auth.tenant?.default_branch_id ?? null;

  const [editOpen, setEditOpen] = useState(false);
  const [editBranchId, setEditBranchId] = useState<number | null>(null);

  const [formName, setFormName] = useState("");
  const [formCode, setFormCode] = useState("");
  const [formIsActive, setFormIsActive] = useState(true);

  const [assignOpen, setAssignOpen] = useState(false);
  const [assignBranchId, setAssignBranchId] = useState<number | null>(null);
  const [assignUsersLoading, setAssignUsersLoading] = useState(false);
  const [assignUsers, setAssignUsers] = useState<BranchUsersPayload["users"]>([]);
  const [assignSelected, setAssignSelected] = useState<Record<number, boolean>>({});

  const [confirmOpen, setConfirmOpen] = useState(false);
  const [confirmTitle, setConfirmTitle] = useState("");
  const [confirmMessage, setConfirmMessage] = useState<React.ReactNode>("");
  const [confirmAction, setConfirmAction] = useState<(() => Promise<void>) | null>(null);

  async function load() {
    if (typeof tenant !== "string" || tenant.length === 0) return;

    setLoading(true);
    setError(null);

    try {
      const res = await apiFetch<BranchesPayload>(`/api/${tenant}/app/branches`);
      setBranches(Array.isArray(res.branches) ? res.branches : []);
    } catch (e) {
      setError(e instanceof Error ? e.message : "Failed to load branches.");
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    void load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [tenant]);

  function openCreate() {
    setEditBranchId(null);
    setFormName("");
    setFormCode("");
    setFormIsActive(true);
    setEditOpen(true);
  }

  function openEdit(b: Branch) {
    setEditBranchId(b.id);
    setFormName(b.name ?? "");
    setFormCode(b.code ?? "");
    setFormIsActive(Boolean(b.is_active));
    setEditOpen(true);
  }

  async function onSaveBranch(e?: React.FormEvent) {
    e?.preventDefault();
    if (typeof tenant !== "string" || tenant.length === 0) return;

    setBusy(true);
    setError(null);
    setStatus(null);

    try {
      const payload = {
        name: formName,
        code: formCode,
        is_active: formIsActive,
      };

      if (editBranchId) {
        await apiFetch<{ branch: Branch }>(`/api/${tenant}/app/branches/${editBranchId}`, {
          method: "PUT",
          body: payload,
        });
        setStatus("Branch updated.");
      } else {
        await apiFetch<{ branch: Branch }>(`/api/${tenant}/app/branches`, {
          method: "POST",
          body: payload,
        });
        setStatus("Branch created.");
      }

      setEditOpen(false);
      await load();
      await auth.refresh();
    } catch (e) {
      if (e instanceof ApiError) {
        setError(e.message);
      } else {
        setError("Failed to save branch.");
      }
    } finally {
      setBusy(false);
    }
  }

  async function onSetDefault(branchId: number) {
    if (typeof tenant !== "string" || tenant.length === 0) return;

    setBusy(true);
    setError(null);
    setStatus(null);

    try {
      await apiFetch(`/api/${tenant}/app/branches/${branchId}/default`, {
        method: "POST",
        body: {},
      });
      setStatus("Default branch updated.");
      await auth.refresh();
      await load();
    } catch (e) {
      if (e instanceof ApiError) {
        setError(e.message);
      } else {
        setError("Failed to set default branch.");
      }
    } finally {
      setBusy(false);
    }
  }

  async function openAssignUsers(branchId: number) {
    if (typeof tenant !== "string" || tenant.length === 0) return;

    setAssignOpen(true);
    setAssignBranchId(branchId);
    setAssignUsers([]);
    setAssignSelected({});
    setAssignUsersLoading(true);
    setError(null);

    try {
      const res = await apiFetch<BranchUsersPayload>(`/api/${tenant}/app/branches/${branchId}/users`);
      const list = Array.isArray(res.users) ? res.users : [];
      const assigned = Array.isArray(res.assigned_user_ids) ? res.assigned_user_ids : [];

      const map: Record<number, boolean> = {};
      for (const id of assigned) {
        if (typeof id === "number") map[id] = true;
      }

      setAssignUsers(list);
      setAssignSelected(map);
    } catch (e) {
      if (e instanceof ApiError) {
        setError(e.message);
      } else {
        setError("Failed to load branch users.");
      }
    } finally {
      setAssignUsersLoading(false);
    }
  }

  async function onSaveAssignments() {
    if (typeof tenant !== "string" || tenant.length === 0) return;
    if (!assignBranchId) return;

    setBusy(true);
    setError(null);
    setStatus(null);

    try {
      const userIds = Object.entries(assignSelected)
        .filter(([, v]) => v)
        .map(([k]) => Number(k))
        .filter((n) => Number.isFinite(n) && n > 0);

      await apiFetch(`/api/${tenant}/app/branches/${assignBranchId}/assign-users`, {
        method: "POST",
        body: { user_ids: userIds },
      });

      setAssignOpen(false);
      setStatus("Assignments updated.");
      await load();
    } catch (e) {
      if (e instanceof ApiError) {
        setError(e.message);
      } else {
        setError("Failed to save assignments.");
      }
    } finally {
      setBusy(false);
    }
  }

  function confirmSetDefault(b: Branch) {
    setConfirmTitle("Set default branch");
    setConfirmMessage(
      <div>
        Set <span className="font-semibold">{b.code} - {b.name}</span> as the default branch?
      </div>,
    );
    setConfirmAction(() => async () => onSetDefault(b.id));
    setConfirmOpen(true);
  }

  const rows = useMemo(() => branches.slice().sort((a, b) => a.name.localeCompare(b.name)), [branches]);

  return (
    <RequireAuth requiredPermission="branches.manage">
      <div className="space-y-6">
        <PageHeader
          title="Branches"
          description="Manage shops (branches), set the default branch, and assign staff."
          actions={
            <Button size="sm" variant="outline" onClick={() => void load()} disabled={loading || busy}>
              Refresh
            </Button>
          }
        />

        {status ? <div className="text-sm text-green-700">{status}</div> : null}
        {error ? <div className="text-sm text-red-600">{error}</div> : null}

        <Modal
          open={editOpen}
          onClose={() => {
            if (busy) return;
            setEditOpen(false);
          }}
          title={editBranchId ? "Edit branch" : "Create branch"}
          footer={
            <div className="flex items-center justify-end gap-2">
              <Button variant="outline" onClick={() => setEditOpen(false)} disabled={busy}>
                Cancel
              </Button>
              <Button onClick={() => void onSaveBranch()} disabled={busy}>
                {busy ? "Saving..." : "Save"}
              </Button>
            </div>
          }
        >
          <form className="grid gap-3" onSubmit={onSaveBranch}>
            <div className="space-y-1">
              <label className="text-sm font-medium" htmlFor="branch_name">
                Name
              </label>
              <input
                id="branch_name"
                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={formName}
                onChange={(e) => setFormName(e.target.value)}
                required
                disabled={busy}
              />
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium" htmlFor="branch_code">
                Code
              </label>
              <input
                id="branch_code"
                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={formCode}
                onChange={(e) => setFormCode(e.target.value)}
                required
                maxLength={16}
                disabled={busy}
              />
            </div>

            <label className="flex items-center gap-2 rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm">
              <input type="checkbox" checked={formIsActive} onChange={(e) => setFormIsActive(e.target.checked)} disabled={busy} />
              <span>Active</span>
            </label>
          </form>
        </Modal>

        <Modal
          open={assignOpen}
          onClose={() => {
            if (busy) return;
            setAssignOpen(false);
          }}
          title="Assign users"
          footer={
            <div className="flex items-center justify-end gap-2">
              <Button variant="outline" onClick={() => setAssignOpen(false)} disabled={busy}>
                Cancel
              </Button>
              <Button onClick={() => void onSaveAssignments()} disabled={busy || assignUsersLoading}>
                {busy ? "Saving..." : "Save"}
              </Button>
            </div>
          }
        >
          {assignUsersLoading ? <div className="text-sm text-zinc-600">Loading users...</div> : null}

          {!assignUsersLoading ? (
            <div className="grid gap-2">
              {assignUsers.length === 0 ? <div className="text-sm text-zinc-600">No users found.</div> : null}

              {assignUsers.map((u) => {
                const checked = Boolean(assignSelected[u.id]);
                return (
                  <label
                    key={u.id}
                    className="flex items-center justify-between gap-3 rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2"
                  >
                    <div className="min-w-0">
                      <div className="truncate text-sm font-medium text-[var(--rb-text)]">{u.name}</div>
                      <div className="truncate text-xs text-zinc-600">{u.email}</div>
                    </div>
                    <input
                      type="checkbox"
                      checked={checked}
                      onChange={(e) =>
                        setAssignSelected((prev) => ({
                          ...prev,
                          [u.id]: e.target.checked,
                        }))
                      }
                      disabled={busy}
                    />
                  </label>
                );
              })}
            </div>
          ) : null}
        </Modal>

        <ConfirmDialog
          open={confirmOpen}
          title={confirmTitle}
          message={confirmMessage}
          busy={busy}
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

        <div className="grid gap-6 lg:grid-cols-12">
          <div className="lg:col-span-4">
            <Card className="shadow-none">
              <CardContent className="pt-5">
                <div className="flex items-center justify-between gap-2">
                  <div>
                    <div className="text-sm font-semibold text-[var(--rb-text)]">Create branch</div>
                    <div className="mt-1 text-sm text-zinc-600">Add another location under this business.</div>
                  </div>
                  <Button onClick={openCreate} disabled={busy}>
                    New
                  </Button>
                </div>

                <div className="mt-4 text-sm text-zinc-600">
                  Tip: Keep the branch <span className="font-mono">code</span> short (used in invoice numbers).
                </div>
              </CardContent>
            </Card>
          </div>

          <div className="lg:col-span-8">
            <Card className="shadow-none">
              <CardContent className="pt-5">
                <DataTable
                  title="Branches"
                  data={rows}
                  loading={loading}
                  emptyMessage="No branches yet."
                  getRowId={(b) => b.id}
                  columnVisibilityKey={`rb:datatable:${tenant}:branches`}
                  columns={[
                    {
                      id: "name",
                      header: "Branch",
                      cell: (b) => (
                        <div className="min-w-0">
                          <div className="truncate font-semibold text-[var(--rb-text)]">{b.code} - {b.name}</div>
                          <div className="mt-1 text-xs text-zinc-600">
                            {defaultBranchId === b.id ? "Default branch" : b.is_active ? "Active" : "Inactive"}
                          </div>
                        </div>
                      ),
                      className: "max-w-[420px]",
                    },
                    {
                      id: "actions",
                      header: "",
                      headerClassName: "text-right",
                      className: "whitespace-nowrap text-right",
                      cell: (b) => {
                        const rowBusy = busy;
                        return (
                          <div className="flex items-center justify-end gap-2">
                            <Button
                              variant="outline"
                              size="sm"
                              onClick={() => void openAssignUsers(b.id)}
                              disabled={rowBusy}
                            >
                              Assign
                            </Button>
                            <Button
                              variant="outline"
                              size="sm"
                              onClick={() => confirmSetDefault(b)}
                              disabled={rowBusy || defaultBranchId === b.id}
                            >
                              Set default
                            </Button>
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => openEdit(b)}
                              disabled={rowBusy}
                              aria-label="Edit branch"
                              title="Edit"
                              className="px-2"
                            >
                              <EditIcon className="h-4 w-4" />
                            </Button>
                          </div>
                        );
                      },
                    },
                  ]}
                />
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </RequireAuth>
  );
}
