"use client";

import { apiFetch, ApiError } from "@/lib/api";
import type { Role, User, UserStatus } from "@/lib/types";
import { Button } from "@/components/ui/Button";
import { Badge } from "@/components/ui/Badge";
import { Card, CardContent } from "@/components/ui/Card";
import { ConfirmDialog } from "@/components/ui/ConfirmDialog";
import { DataTable } from "@/components/ui/DataTable";
import { DropdownMenu, DropdownMenuItem, DropdownMenuSeparator } from "@/components/ui/DropdownMenu";
import { Modal } from "@/components/ui/Modal";
import { PageHeader } from "@/components/ui/PageHeader";
import { RequireAuth } from "@/components/RequireAuth";
import { useAuth } from "@/lib/auth";
import { useParams } from "next/navigation";
import React, { useEffect, useMemo, useState } from "react";

function PencilIcon(props: { className?: string }) {
  return (
    <svg
      viewBox="0 0 24 24"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
      className={props.className}
      aria-hidden="true"
    >
      <path
        d="M12 20h9"
        stroke="currentColor"
        strokeWidth="2"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
      <path
        d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"
        stroke="currentColor"
        strokeWidth="2"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    </svg>
  );
}

type UsersPayload = {
  users: User[];
  meta?: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
};

type RolesPayload = {
  roles: Role[];
};

export default function TenantUsersPage() {
  const auth = useAuth();
  const params = useParams() as { tenant?: string; business?: string };
  const tenant = params.business ?? params.tenant;

  const [loading, setLoading] = useState(true);
  const [users, setUsers] = useState<User[]>([]);
  const [roles, setRoles] = useState<Role[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [status, setStatus] = useState<string | null>(null);

  const [newName, setNewName] = useState("");
  const [newEmail, setNewEmail] = useState("");
  const [newPassword, setNewPassword] = useState("");
  const [newRoleId, setNewRoleId] = useState<number | null>(null);
  const [busy, setBusy] = useState(false);

  const [actionBusyUserId, setActionBusyUserId] = useState<number | null>(null);

  const [roleFilter, setRoleFilter] = useState<string>("all");
  const [statusFilter, setStatusFilter] = useState<string>("all");

  const [q, setQ] = useState<string>("");
  const [pageIndex, setPageIndex] = useState<number>(0);
  const [pageSize, setPageSize] = useState<number>(10);
  const [totalUsers, setTotalUsers] = useState<number>(0);
  const [sort, setSort] = useState<{ id: string; dir: "asc" | "desc" } | null>(null);

  const [editOpen, setEditOpen] = useState(false);
  const [editUserId, setEditUserId] = useState<number | null>(null);
  const [editName, setEditName] = useState("");
  const [editEmail, setEditEmail] = useState("");
  const [editRoleId, setEditRoleId] = useState<number | null>(null);

  const [confirmOpen, setConfirmOpen] = useState(false);
  const [confirmTitle, setConfirmTitle] = useState("");
  const [confirmMessage, setConfirmMessage] = useState<React.ReactNode>("");
  const [confirmAction, setConfirmAction] = useState<(() => Promise<void>) | null>(null);

  const roleNameById = useMemo(() => {
    const map = new Map<number, string>();
    for (const r of roles) {
      map.set(r.id, r.name);
    }
    return map;
  }, [roles]);

  const roleOptions = useMemo(() => {
    return [
      { label: "All roles", value: "all" },
      { label: "(none)", value: "none" },
      ...roles.map((r) => ({ label: r.name, value: String(r.id) })),
    ];
  }, [roles]);

  const statusOptions = useMemo(() => {
    return [
      { label: "All statuses", value: "all" },
      { label: "Pending", value: "pending" },
      { label: "Active", value: "active" },
      { label: "Inactive", value: "inactive" },
      { label: "Suspended", value: "suspended" },
    ];
  }, []);

  const statusBadgeVariant = useMemo(() => {
    return new Map<UserStatus, "default" | "info" | "success" | "warning" | "danger">([
      ["pending", "warning"],
      ["active", "success"],
      ["inactive", "default"],
      ["suspended", "danger"],
    ]);
  }, []);

  async function load({ includeRoles }: { includeRoles: boolean }) {
    if (typeof tenant !== "string" || tenant.length === 0) return;

    setError(null);
    setStatus(null);
    setLoading(true);

    try {
      const qs = new URLSearchParams();
      if (q.trim().length > 0) qs.set("q", q.trim());
      if (roleFilter && roleFilter !== "all") qs.set("role", roleFilter);
      if (statusFilter && statusFilter !== "all") qs.set("status", statusFilter);
      if (sort?.id && sort?.dir) {
        qs.set("sort", sort.id);
        qs.set("dir", sort.dir);
      }
      qs.set("page", String(pageIndex + 1));
      qs.set("per_page", String(pageSize));

      const usersPath = `/api/${tenant}/app/users?${qs.toString()}`;

      if (includeRoles) {
        const [usersRes, rolesRes] = await Promise.all([
          apiFetch<UsersPayload>(usersPath),
          apiFetch<RolesPayload>(`/api/${tenant}/app/roles`),
        ]);

        setUsers(Array.isArray(usersRes.users) ? usersRes.users : []);
        setTotalUsers(typeof usersRes.meta?.total === "number" ? usersRes.meta.total : 0);
        setPageSize(typeof usersRes.meta?.per_page === "number" ? usersRes.meta.per_page : pageSize);
        setRoles(Array.isArray(rolesRes.roles) ? rolesRes.roles : []);
        const firstRoleId = (Array.isArray(rolesRes.roles) ? rolesRes.roles : [])[0]?.id ?? null;
        setNewRoleId(firstRoleId);
      } else {
        const usersRes = await apiFetch<UsersPayload>(usersPath);
        setUsers(Array.isArray(usersRes.users) ? usersRes.users : []);
        setTotalUsers(typeof usersRes.meta?.total === "number" ? usersRes.meta.total : 0);
        setPageSize(typeof usersRes.meta?.per_page === "number" ? usersRes.meta.per_page : pageSize);
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to load users.");
    } finally {
      setLoading(false);
    }
  }

  async function onSendPasswordReset(userId: number) {
    setActionBusyUserId(userId);
    setError(null);
    setStatus(null);

    try {
      await apiFetch<{ message: string }>(`/api/${tenant}/app/users/${userId}/reset-password`, {
        method: "POST",
      });

      setStatus("Password reset link sent.");
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError("Failed to send password reset link.");
      }
    } finally {
      setActionBusyUserId(null);
    }
  }

  useEffect(() => {
    setPageIndex(0);
    setSort(null);
    void load({ includeRoles: true });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [tenant]);

  useEffect(() => {
    if (typeof tenant !== "string" || tenant.length === 0) return;
    void load({ includeRoles: false });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [q, roleFilter, statusFilter, pageIndex, pageSize, sort?.id, sort?.dir]);

  async function onCreateUser(e: React.FormEvent) {
    e.preventDefault();
    if (!newRoleId) return;

    setBusy(true);
    setError(null);
    setStatus(null);

    try {
      await apiFetch<{ user: User }>(`/api/${tenant}/app/users`, {
        method: "POST",
        body: {
          name: newName,
          email: newEmail,
          password: newPassword,
          role_id: newRoleId,
        },
      });

      setNewName("");
      setNewEmail("");
      setNewPassword("");
      setStatus("User created.");
      setPageIndex(0);
      await load({ includeRoles: false });
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError("Failed to create user.");
      }
    } finally {
      setBusy(false);
    }
  }

  async function onUpdateUserStatus(userId: number, nextStatus: UserStatus) {
    setActionBusyUserId(userId);
    setError(null);
    setStatus(null);

    try {
      await apiFetch<{ user: User }>(`/api/${tenant}/app/users/${userId}/status`, {
        method: "PATCH",
        body: { status: nextStatus },
      });

      setStatus("User updated.");
      await load({ includeRoles: false });
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError("Failed to update user.");
      }
    } finally {
      setActionBusyUserId(null);
    }
  }

  function openEdit(user: User) {
    setEditUserId(user.id);
    setEditName(user.name ?? "");
    setEditEmail(user.email ?? "");
    setEditRoleId(user.role_id ?? null);
    setEditOpen(true);
  }

  async function onSaveEdit() {
    if (!editUserId) return;

    setActionBusyUserId(editUserId);
    setError(null);
    setStatus(null);

    try {
      await apiFetch<{ user: User }>(`/api/${tenant}/app/users/${editUserId}`, {
        method: "PUT",
        body: {
          name: editName,
          email: editEmail,
          role_id: editRoleId,
        },
      });

      setEditOpen(false);
      setStatus("User updated.");
      await auth.refresh();
      await load({ includeRoles: false });
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError("Failed to update user.");
      }
    } finally {
      setActionBusyUserId(null);
    }
  }

  function openConfirm(args: { title: string; message: React.ReactNode; action: () => Promise<void> }) {
    setConfirmTitle(args.title);
    setConfirmMessage(args.message);
    setConfirmAction(() => args.action);
    setConfirmOpen(true);
  }

  async function onChangeUserRole(userId: number, roleId: number) {
    setBusy(true);
    setError(null);
    setStatus(null);

    try {
      await apiFetch<{ user: User }>(`/api/${tenant}/app/users/${userId}/role`, {
        method: "PATCH",
        body: { role_id: roleId },
      });

      setStatus("Role updated.");
      await auth.refresh();
      await load({ includeRoles: false });
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError("Failed to update role.");
      }
    } finally {
      setBusy(false);
    }
  }

  return (
    <RequireAuth requiredPermission="users.manage">
      <div className="space-y-6">
        <PageHeader title="Users" description="Manage tenant users and their roles." />

        {status ? <div className="text-sm text-green-700">{status}</div> : null}
        {error ? <div className="text-sm text-red-600">{error}</div> : null}

        <Modal
          open={editOpen}
          onClose={() => {
            if (actionBusyUserId) return;
            setEditOpen(false);
          }}
          title="Edit user"
          footer={
            <div className="flex items-center justify-end gap-2">
              <Button variant="outline" onClick={() => setEditOpen(false)} disabled={!!actionBusyUserId}>
                Cancel
              </Button>
              <Button onClick={() => void onSaveEdit()} disabled={!!actionBusyUserId}>
                {actionBusyUserId ? "Saving..." : "Save"}
              </Button>
            </div>
          }
        >
          <div className="grid gap-3">
            <div className="space-y-1">
              <label className="text-sm font-medium" htmlFor="edit_user_name">
                Name
              </label>
              <input
                id="edit_user_name"
                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={editName}
                onChange={(e) => setEditName(e.target.value)}
                required
                disabled={!!actionBusyUserId}
              />
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium" htmlFor="edit_user_email">
                Email
              </label>
              <input
                id="edit_user_email"
                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={editEmail}
                onChange={(e) => setEditEmail(e.target.value)}
                type="email"
                required
                disabled={!!actionBusyUserId}
              />
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium" htmlFor="edit_user_role">
                Role
              </label>
              <select
                id="edit_user_role"
                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={editRoleId ?? ""}
                onChange={(e) => setEditRoleId(Number(e.target.value))}
                disabled={!!actionBusyUserId}
              >
                {roles.map((r) => (
                  <option key={r.id} value={r.id}>
                    {r.name}
                  </option>
                ))}
              </select>
            </div>
          </div>
        </Modal>

        <ConfirmDialog
          open={confirmOpen}
          title={confirmTitle}
          message={confirmMessage}
          busy={!!actionBusyUserId}
          onCancel={() => {
            if (actionBusyUserId) return;
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
                <div className="text-sm font-semibold text-[var(--rb-text)]">Create user</div>
                <form className="mt-4 grid gap-3" onSubmit={onCreateUser}>
                  <div className="space-y-1">
                    <label className="text-sm font-medium" htmlFor="user_name">
                      Name
                    </label>
                    <input
                      id="user_name"
                      className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                      value={newName}
                      onChange={(e) => setNewName(e.target.value)}
                      required
                    />
                  </div>

                  <div className="space-y-1">
                    <label className="text-sm font-medium" htmlFor="user_email">
                      Email
                    </label>
                    <input
                      id="user_email"
                      className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                      value={newEmail}
                      onChange={(e) => setNewEmail(e.target.value)}
                      type="email"
                      required
                    />
                  </div>

                  <div className="space-y-1">
                    <label className="text-sm font-medium" htmlFor="user_password">
                      Password
                    </label>
                    <input
                      id="user_password"
                      className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                      value={newPassword}
                      onChange={(e) => setNewPassword(e.target.value)}
                      type="password"
                      autoComplete="new-password"
                      required
                    />
                  </div>

                  <div className="space-y-1">
                    <label className="text-sm font-medium" htmlFor="user_role">
                      Role
                    </label>
                    <select
                      id="user_role"
                      className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                      value={newRoleId ?? ""}
                      onChange={(e) => setNewRoleId(Number(e.target.value))}
                      required
                    >
                      {roles.map((r) => (
                        <option key={r.id} value={r.id}>
                          {r.name}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div>
                    <Button type="submit" disabled={busy || loading || roles.length === 0}>
                      {busy ? "Saving..." : "Create user"}
                    </Button>
                    {roles.length === 0 ? (
                      <div className="mt-2 text-sm text-zinc-600">
                        Create a role first before adding users.
                      </div>
                    ) : null}
                  </div>
                </form>
              </CardContent>
            </Card>
          </div>

          <div className="lg:col-span-8">
            <Card className="shadow-none">
              <CardContent className="pt-5">
                <DataTable
                  title="Users"
                  data={users}
                  loading={loading}
                  emptyMessage="No users yet."
                  getRowId={(u) => u.id}
                  search={{
                    placeholder: "Search name or email...",
                  }}
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
                    totalRows: totalUsers,
                    sort,
                    onSortChange: (next) => {
                      setSort(next);
                      setPageIndex(0);
                    },
                  }}
                  exportConfig={{
                    url: `/api/${tenant}/app/users/export`,
                    formats: ["csv", "xlsx", "pdf"],
                    filename: ({ format }) => `users_export.${format}`,
                  }}
                  columnVisibilityKey={`rb:datatable:${tenant}:users`}
                  filters={[
                    {
                      id: "role",
                      label: "Role",
                      value: roleFilter,
                      options: roleOptions,
                      onChange: (value) => {
                        setRoleFilter(String(value));
                        setPageIndex(0);
                      },
                    },
                    {
                      id: "status",
                      label: "Status",
                      value: statusFilter,
                      options: statusOptions,
                      onChange: (value) => {
                        setStatusFilter(String(value));
                        setPageIndex(0);
                      },
                    },
                  ]}
                  columns={[
                    {
                      id: "name",
                      header: "Name",
                      sortId: "name",
                      cell: (u) => (
                        <div className="min-w-0">
                          <div className="truncate font-semibold text-[var(--rb-text)]">{u.name}</div>
                          <div className="truncate text-xs text-zinc-600">{u.email}</div>
                        </div>
                      ),
                      className: "max-w-[340px]",
                    },
                    {
                      id: "email",
                      header: "Email",
                      sortId: "email",
                      hiddenByDefault: true,
                      cell: (u) => <div className="text-sm text-zinc-700">{u.email}</div>,
                      className: "whitespace-nowrap",
                    },
                    {
                      id: "role",
                      header: "Role",
                      cell: (u) => {
                        const currentRoleId = u.role_id ?? null;
                        return (
                          <div className="text-sm text-zinc-700">
                            {currentRoleId ? roleNameById.get(currentRoleId) ?? "(unknown)" : "(none)"}
                          </div>
                        );
                      },
                      className: "whitespace-nowrap",
                    },
                    {
                      id: "status",
                      header: "Status",
                      sortId: "status",
                      className: "whitespace-nowrap",
                      cell: (u) => {
                        const s = (u.status ?? "active") as UserStatus;
                        return <Badge variant={statusBadgeVariant.get(s) ?? "default"}>{s}</Badge>;
                      },
                    },
                    {
                      id: "actions",
                      header: "",
                      headerClassName: "text-right",
                      className: "whitespace-nowrap text-right",
                      cell: (u) => {
                        const s = (u.status ?? "active") as UserStatus;
                        const rowBusy = actionBusyUserId === u.id || busy;
                        return (
                          <DropdownMenu
                            align="right"
                            trigger={({ toggle }) => (
                              <Button
                                variant="ghost"
                                size="sm"
                                onClick={toggle}
                                disabled={rowBusy}
                                aria-label="User actions"
                                title="User actions"
                                className="px-2"
                              >
                                <PencilIcon className="h-4 w-4" />
                              </Button>
                            )}
                          >
                            {({ close }) => (
                              <>
                                <DropdownMenuItem
                                  onSelect={() => {
                                    close();
                                    openEdit(u);
                                  }}
                                  disabled={rowBusy}
                                >
                                  Edit
                                </DropdownMenuItem>

                                <DropdownMenuSeparator />

                                <DropdownMenuItem
                                  onSelect={() => {
                                    close();
                                    openConfirm({
                                      title: "Send password reset",
                                      message: (
                                        <div>
                                          Send a password reset link to <span className="font-semibold">{u.email}</span>?
                                        </div>
                                      ),
                                      action: async () => onSendPasswordReset(u.id),
                                    });
                                  }}
                                  disabled={rowBusy}
                                >
                                  Reset password
                                </DropdownMenuItem>

                                <DropdownMenuSeparator />

                                <DropdownMenuItem
                                  onSelect={() => {
                                    close();
                                    openConfirm({
                                      title: "Suspend user",
                                      message: (
                                        <div>
                                          Suspend <span className="font-semibold">{u.email}</span>? They may lose access
                                          immediately.
                                        </div>
                                      ),
                                      action: async () => onUpdateUserStatus(u.id, "suspended"),
                                    });
                                  }}
                                  destructive
                                  disabled={rowBusy || s === "suspended"}
                                >
                                  Suspend
                                </DropdownMenuItem>

                                <DropdownMenuItem
                                  onSelect={() => {
                                    close();
                                    openConfirm({
                                      title: "Set user inactive",
                                      message: (
                                        <div>
                                          Set <span className="font-semibold">{u.email}</span> to inactive? They will not
                                          be able to sign in.
                                        </div>
                                      ),
                                      action: async () => onUpdateUserStatus(u.id, "inactive"),
                                    });
                                  }}
                                  destructive
                                  disabled={rowBusy || s === "inactive"}
                                >
                                  Set inactive
                                </DropdownMenuItem>

                                <DropdownMenuItem
                                  onSelect={() => {
                                    close();
                                    void onUpdateUserStatus(u.id, "active");
                                  }}
                                  disabled={rowBusy || s === "active"}
                                >
                                  Activate
                                </DropdownMenuItem>
                              </>
                            )}
                          </DropdownMenu>
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
