"use client";

import { apiFetch, ApiError } from "@/lib/api";
import type { Role, User } from "@/lib/types";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { DataTable } from "@/components/ui/DataTable";
import { PageHeader } from "@/components/ui/PageHeader";
import { RequireAuth } from "@/components/RequireAuth";
import { useAuth } from "@/lib/auth";
import { useParams } from "next/navigation";
import React, { useEffect, useMemo, useState } from "react";

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
  const params = useParams<{ tenant: string }>();
  const tenant = params.tenant;

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

  const [roleFilter, setRoleFilter] = useState<string>("all");

  const [q, setQ] = useState<string>("");
  const [pageIndex, setPageIndex] = useState<number>(0);
  const [pageSize, setPageSize] = useState<number>(10);
  const [totalUsers, setTotalUsers] = useState<number>(0);

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

  async function load({ includeRoles }: { includeRoles: boolean }) {
    if (typeof tenant !== "string" || tenant.length === 0) return;

    setError(null);
    setStatus(null);
    setLoading(true);

    try {
      const qs = new URLSearchParams();
      if (q.trim().length > 0) qs.set("q", q.trim());
      if (roleFilter && roleFilter !== "all") qs.set("role", roleFilter);
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

  useEffect(() => {
    setPageIndex(0);
    void load({ includeRoles: true });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [tenant]);

  useEffect(() => {
    if (typeof tenant !== "string" || tenant.length === 0) return;
    void load({ includeRoles: false });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [tenant, q, roleFilter, pageIndex, pageSize]);

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
                  }}
                  filters={[
                    {
                      id: "role",
                      label: "Role",
                      value: roleFilter,
                      options: roleOptions,
                      onChange: (value: string) => {
                        setRoleFilter(value);
                        setPageIndex(0);
                      },
                    },
                  ]}
                  columns={[
                    {
                      id: "name",
                      header: "Name",
                      cell: (u) => (
                        <div className="min-w-0">
                          <div className="truncate font-semibold text-[var(--rb-text)]">{u.name}</div>
                          <div className="truncate text-xs text-zinc-600">{u.email}</div>
                        </div>
                      ),
                      className: "max-w-[340px]",
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
                      id: "actions",
                      header: "",
                      headerClassName: "text-right",
                      className: "whitespace-nowrap text-right",
                      cell: (u) => {
                        const currentRoleId = u.role_id ?? null;
                        return (
                          <select
                            className="rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                            value={currentRoleId ?? ""}
                            onChange={(e) => void onChangeUserRole(u.id, Number(e.target.value))}
                            disabled={busy}
                          >
                            {roles.map((r) => (
                              <option key={r.id} value={r.id}>
                                {r.name}
                              </option>
                            ))}
                          </select>
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
