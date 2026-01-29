"use client";

import { apiFetch, ApiError } from "@/lib/api";
import type { Branch, Role, User, UserStatus } from "@/lib/types";
import { Button } from "@/components/ui/Button";
import { Badge } from "@/components/ui/Badge";
import { Card, CardContent } from "@/components/ui/Card";
import { ConfirmDialog } from "@/components/ui/ConfirmDialog";
import { DataTable } from "@/components/ui/DataTable";
import { DropdownMenu, DropdownMenuItem, DropdownMenuSeparator } from "@/components/ui/DropdownMenu";
import { PageHeader } from "@/components/ui/PageHeader";
import { RequireAuth } from "@/components/RequireAuth";
import { useAuth } from "@/lib/auth";
import { notify } from "@/lib/notify";
import { useParams } from "next/navigation";
import React, { useEffect, useMemo, useRef, useState } from "react";

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

type BranchesPayload = {
  branches: Branch[];
};

export default function TenantUsersPage() {
  const auth = useAuth();
  const params = useParams() as { tenant?: string; business?: string };
  const tenant = params.business ?? params.tenant;

  const [loading, setLoading] = useState(true);
  const [users, setUsers] = useState<User[]>([]);
  const [roles, setRoles] = useState<Role[]>([]);
  const [branches, setBranches] = useState<Branch[]>([]);

  const [newName, setNewName] = useState("");
  const [newEmail, setNewEmail] = useState("");
  const [newPassword, setNewPassword] = useState("");
  const [newRoleId, setNewRoleId] = useState<number | null>(null);
  const [newShopQuery, setNewShopQuery] = useState<string>("");
  const [newShopSelected, setNewShopSelected] = useState<Record<number, boolean>>({});
  const [busy, setBusy] = useState(false);

  const [actionBusyUserId, setActionBusyUserId] = useState<number | null>(null);
  const [shopBusyUserId, setShopBusyUserId] = useState<number | null>(null);

  const [roleFilter, setRoleFilter] = useState<string>("all");
  const [statusFilter, setStatusFilter] = useState<string>("all");

  const [q, setQ] = useState<string>("");
  const [pageIndex, setPageIndex] = useState<number>(0);
  const [pageSize, setPageSize] = useState<number>(10);
  const [totalUsers, setTotalUsers] = useState<number>(0);
  const [sort, setSort] = useState<{ id: string; dir: "asc" | "desc" } | null>(null);

  const [editingUserId, setEditingUserId] = useState<number | null>(null);

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

  const branchOptions = useMemo(() => {
    const list = branches.slice().sort((a, b) => `${a.code} ${a.name}`.localeCompare(`${b.code} ${b.name}`));
    return list.map((b) => ({
      id: b.id,
      label: `${b.code} - ${b.name}${b.is_active ? "" : " (inactive)"}`,
      isActive: b.is_active,
    }));
  }, [branches]);

  const activeBranchOptions = useMemo(() => branchOptions.filter((b) => b.isActive), [branchOptions]);

  const shopDropdownOptions = useMemo(() => {
    const canManageBranches = auth.can("branches.manage");
    if (canManageBranches) return activeBranchOptions;

    const myBranchIds = new Set(
      (Array.isArray(auth.user?.branches) ? auth.user?.branches : [])
        .filter((b) => Boolean(b?.is_active))
        .map((b) => b.id),
    );

    if (myBranchIds.size === 0) return [];

    return activeBranchOptions.filter((b) => myBranchIds.has(b.id));
  }, [activeBranchOptions, auth]);

  const filteredNewShopOptions = useMemo(() => {
    const q = newShopQuery.trim().toLowerCase();
    if (!q) return activeBranchOptions;
    return activeBranchOptions.filter((b) => b.label.toLowerCase().includes(q));
  }, [activeBranchOptions, newShopQuery]);

  const newShopAllChecked = useMemo(() => {
    if (filteredNewShopOptions.length === 0) return false;
    return filteredNewShopOptions.every((b) => Boolean(newShopSelected[b.id]));
  }, [filteredNewShopOptions, newShopSelected]);

  const newShopSomeChecked = useMemo(() => {
    return filteredNewShopOptions.some((b) => Boolean(newShopSelected[b.id]));
  }, [filteredNewShopOptions, newShopSelected]);

  const newShopCheckAllRef = useRef<HTMLInputElement | null>(null);
  useEffect(() => {
    if (!newShopCheckAllRef.current) return;
    newShopCheckAllRef.current.indeterminate = newShopSomeChecked && !newShopAllChecked;
  }, [newShopAllChecked, newShopSomeChecked]);

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
        const [usersRes, rolesRes, branchesRes] = await Promise.all([
          apiFetch<UsersPayload>(usersPath),
          apiFetch<RolesPayload>(`/api/${tenant}/app/roles`),
          apiFetch<BranchesPayload>(`/api/${tenant}/app/branches`),
        ]);

        setUsers(Array.isArray(usersRes.users) ? usersRes.users : []);
        setTotalUsers(typeof usersRes.meta?.total === "number" ? usersRes.meta.total : 0);
        setPageSize(typeof usersRes.meta?.per_page === "number" ? usersRes.meta.per_page : pageSize);
        setRoles(Array.isArray(rolesRes.roles) ? rolesRes.roles : []);
        setBranches(Array.isArray(branchesRes.branches) ? branchesRes.branches : []);

        const firstRoleId = (Array.isArray(rolesRes.roles) ? rolesRes.roles : [])[0]?.id ?? null;
        setNewRoleId(firstRoleId);

        const defaultBranchId = auth.tenant?.default_branch_id ?? null;
        const branchList = Array.isArray(branchesRes.branches) ? branchesRes.branches : [];
        const activeBranchIds = branchList.filter((b) => b.is_active).map((b) => b.id);
        const seedIds =
          (defaultBranchId && activeBranchIds.includes(defaultBranchId) ? [defaultBranchId] : [])
            .concat(activeBranchIds.length > 0 ? [activeBranchIds[0]] : [])
            .filter((v, idx, arr) => arr.indexOf(v) === idx);

        setNewShopSelected((prev) => {
          if (Object.keys(prev).length > 0) return prev;
          const next: Record<number, boolean> = {};
          for (const id of seedIds) next[id] = true;
          return next;
        });
      } else {
        const usersRes = await apiFetch<UsersPayload>(usersPath);
        setUsers(Array.isArray(usersRes.users) ? usersRes.users : []);
        setTotalUsers(typeof usersRes.meta?.total === "number" ? usersRes.meta.total : 0);
        setPageSize(typeof usersRes.meta?.per_page === "number" ? usersRes.meta.per_page : pageSize);
      }
    } catch (err) {
      notify.error(err instanceof Error ? err.message : "Failed to load users.");
    } finally {
      setLoading(false);
    }
  }

  function getUserShopId(u: User): number | null {
    const bs = Array.isArray(u.branches) ? u.branches : [];
    const active = bs.find((b) => b.is_active);
    return (active?.id ?? bs[0]?.id ?? null) as number | null;
  }

  async function onSendPasswordReset(userId: number) {
    setActionBusyUserId(userId);

    try {
      await apiFetch<{ message: string }>(`/api/${tenant}/app/users/${userId}/reset-password`, {
        method: "POST",
      });

      notify.success("Password reset link sent.");
    } catch (err) {
      if (err instanceof ApiError) {
        notify.error(err.message);
      } else {
        notify.error("Failed to send password reset link.");
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

  async function onSaveUser(e: React.FormEvent) {
    e.preventDefault();
    if (!newRoleId) return;
    if (activeBranchOptions.length === 0) return;

    const selectedBranchIds = Object.entries(newShopSelected)
      .filter(([, v]) => v)
      .map(([k]) => Number(k))
      .filter((n) => Number.isFinite(n) && n > 0);

    if (selectedBranchIds.length === 0) {
      notify.error("Select at least one shop.");
      return;
    }

    setBusy(true);

    try {
      if (editingUserId) {
        await apiFetch<{ user: User }>(`/api/${tenant}/app/users/${editingUserId}`, {
          method: "PUT",
          body: {
            name: newName,
            email: newEmail,
            role_id: newRoleId,
            ...(newPassword.trim().length > 0 ? { password: newPassword } : {}),
          },
        });

        const shopsRes = await apiFetch<{ user: User }>(`/api/${tenant}/app/users/${editingUserId}/shop`, {
          method: "PATCH",
          body: { branch_ids: selectedBranchIds },
        });
        setUsers((prev) => prev.map((u) => (u.id === editingUserId ? shopsRes.user : u)));
        notify.success("User updated.");
      } else {
        await apiFetch<{ user: User }>(`/api/${tenant}/app/users`, {
          method: "POST",
          body: {
            name: newName,
            email: newEmail,
            password: newPassword,
            role_id: newRoleId,
            branch_ids: selectedBranchIds,
          },
        });
        notify.success("User created.");
      }

      setNewName("");
      setNewEmail("");
      setNewPassword("");
      setNewShopQuery("");
      setNewShopSelected({});
      setEditingUserId(null);
      setPageIndex(0);
      await load({ includeRoles: false });
      await auth.refresh();
    } catch (err) {
      if (err instanceof ApiError) {
        notify.error(err.message);
      } else {
        notify.error(editingUserId ? "Failed to update user." : "Failed to create user.");
      }
    } finally {
      setBusy(false);
    }
  }

  async function onUpdateUserStatus(userId: number, nextStatus: UserStatus) {
    setActionBusyUserId(userId);

    try {
      await apiFetch<{ user: User }>(`/api/${tenant}/app/users/${userId}/status`, {
        method: "PATCH",
        body: { status: nextStatus },
      });

      notify.success("User updated.");
      await load({ includeRoles: false });
    } catch (err) {
      if (err instanceof ApiError) {
        notify.error(err.message);
      } else {
        notify.error("Failed to update user.");
      }
    } finally {
      setActionBusyUserId(null);
    }
  }

  function openEdit(user: User) {
    const current = Array.isArray(user.branches) ? user.branches : [];
    const map: Record<number, boolean> = {};
    for (const b of current) {
      if (typeof b.id === "number") map[b.id] = true;
    }

    setEditingUserId(user.id);
    setNewName(user.name ?? "");
    setNewEmail(user.email ?? "");
    setNewPassword("");
    setNewRoleId(user.role_id ?? null);
    setNewShopSelected(map);
    setNewShopQuery("");

    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  function openConfirm(args: { title: string; message: React.ReactNode; action: () => Promise<void> }) {
    setConfirmTitle(args.title);
    setConfirmMessage(args.message);
    setConfirmAction(() => args.action);
    setConfirmOpen(true);
  }

  async function onChangeUserShop(userId: number, branchId: number) {
    if (typeof tenant !== "string" || tenant.length === 0) return;

    setShopBusyUserId(userId);

    try {
      const res = await apiFetch<{ user: User }>(`/api/${tenant}/app/users/${userId}/shop`, {
        method: "PATCH",
        body: { branch_ids: [branchId] },
      });

      setUsers((prev) => prev.map((u) => (u.id === userId ? res.user : u)));
      notify.success("Shop updated.");
    } catch (err) {
      if (err instanceof ApiError) {
        notify.error(err.message);
      } else {
        notify.error("Failed to update shop.");
      }
    } finally {
      setShopBusyUserId(null);
    }
  }

  return (
    <RequireAuth requiredPermission="users.manage">
      <div className="space-y-6">
        <PageHeader title="Users" description="Manage tenant users and their roles." />

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

        <div className="space-y-6">
          <div>
            <Card className="shadow-none">
              <CardContent className="pt-5">
                <div className="text-sm font-semibold text-[var(--rb-text)]">{editingUserId ? "Edit user" : "Create user"}</div>
                <form className="mt-4" onSubmit={onSaveUser}>
                  <div className="grid gap-6 lg:grid-cols-12">
                    <div className="lg:col-span-7">
                      <div className="grid gap-3">
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
                            required={!editingUserId}
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
                          <div className="flex items-center gap-2">
                            <Button
                              type="submit"
                              disabled={
                                busy ||
                                loading ||
                                roles.length === 0 ||
                                activeBranchOptions.length === 0 ||
                                Object.values(newShopSelected).filter(Boolean).length === 0
                              }
                            >
                              {busy ? "Saving..." : editingUserId ? "Save changes" : "Create user"}
                            </Button>
                            {editingUserId ? (
                              <Button
                                type="button"
                                variant="outline"
                                disabled={busy || loading}
                                onClick={() => {
                                  setEditingUserId(null);
                                  setNewName("");
                                  setNewEmail("");
                                  setNewPassword("");
                                  setNewShopQuery("");
                                  setNewShopSelected({});
                                }}
                              >
                                Cancel
                              </Button>
                            ) : null}
                          </div>
                          {roles.length === 0 ? (
                            <div className="mt-2 text-sm text-zinc-600">Create a role first before adding users.</div>
                          ) : null}
                          {activeBranchOptions.length === 0 ? (
                            <div className="mt-2 text-sm text-zinc-600">Create a shop first before adding users.</div>
                          ) : null}
                        </div>
                      </div>
                    </div>

                    <div className="lg:col-span-5">
                      <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-[var(--rb-surface-muted)] p-4">
                        <div className="flex items-start justify-between gap-3">
                          <div>
                            <div className="text-sm font-semibold text-[var(--rb-text)]">Shops</div>
                            <div className="mt-1 text-xs text-zinc-600">Select one or more shops this user can access.</div>
                          </div>
                          <div className="text-xs text-zinc-600">Selected: {Object.values(newShopSelected).filter(Boolean).length}</div>
                        </div>

                        <div className="mt-3 space-y-1">
                          <label className="text-sm font-medium" htmlFor="new_user_shop_search">
                            Search
                          </label>
                          <input
                            id="new_user_shop_search"
                            className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                            value={newShopQuery}
                            onChange={(e) => setNewShopQuery(e.target.value)}
                            placeholder="Search shops..."
                            disabled={busy || loading}
                          />
                        </div>

                        <div className="mt-3 max-h-[260px] overflow-y-auto pr-1">
                          <div className="grid gap-2">
                            <label className="flex items-center justify-between gap-3 rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2">
                              <div className="min-w-0">
                                <div className="truncate text-sm font-medium text-[var(--rb-text)]">Check all</div>
                              </div>
                              <input
                                ref={newShopCheckAllRef}
                                type="checkbox"
                                checked={newShopAllChecked}
                                onChange={(e) => {
                                  const next = e.target.checked;
                                  setNewShopSelected((prev) => {
                                    const copy = { ...prev };
                                    for (const b of filteredNewShopOptions) {
                                      copy[b.id] = next;
                                    }
                                    return copy;
                                  });
                                }}
                                disabled={busy || loading}
                              />
                            </label>

                            {filteredNewShopOptions.map((b) => {
                                const checked = Boolean(newShopSelected[b.id]);
                                return (
                                  <label
                                    key={b.id}
                                    className="flex items-center justify-between gap-3 rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2"
                                  >
                                    <div className="min-w-0">
                                      <div className="truncate text-sm font-medium text-[var(--rb-text)]">{b.label}</div>
                                    </div>
                                    <input
                                      type="checkbox"
                                      checked={checked}
                                      onChange={(e) =>
                                        setNewShopSelected((prev) => ({
                                          ...prev,
                                          [b.id]: e.target.checked,
                                        }))
                                      }
                                      disabled={busy || loading}
                                    />
                                  </label>
                                );
                              })}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </form>
              </CardContent>
            </Card>
          </div>

          <div>
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
                      id: "shop",
                      header: "Shops",
                      className: "min-w-[220px]",
                      cell: (u) => {
                        const shopId = getUserShopId(u);
                        const effectiveShopId =
                          typeof shopId === "number" && shopDropdownOptions.some((b) => b.id === shopId)
                            ? shopId
                            : shopDropdownOptions[0]?.id ?? null;
                        const disabled = busy || loading || shopBusyUserId === u.id || shopDropdownOptions.length === 0;
                        return (
                          <select
                            className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-2 py-1 text-sm"
                            value={effectiveShopId ?? ""}
                            onChange={(e) => {
                              const nextId = Number(e.target.value);
                              if (!Number.isFinite(nextId) || nextId <= 0) return;
                              void onChangeUserShop(u.id, nextId);
                            }}
                            disabled={disabled}
                          >
                            {shopDropdownOptions.map((b) => (
                              <option key={b.id} value={b.id}>
                                {b.label}
                              </option>
                            ))}
                          </select>
                        );
                      },
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
