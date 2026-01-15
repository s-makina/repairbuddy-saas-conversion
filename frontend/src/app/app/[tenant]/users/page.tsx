"use client";

import { apiFetch, ApiError } from "@/lib/api";
import type { Role, User } from "@/lib/types";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { PageHeader } from "@/components/ui/PageHeader";
import { RequireAuth } from "@/components/RequireAuth";
import { useAuth } from "@/lib/auth";
import { useParams } from "next/navigation";
import React, { useEffect, useMemo, useState } from "react";

type UsersPayload = {
  users: User[];
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

  const roleNameById = useMemo(() => {
    const map = new Map<number, string>();
    for (const r of roles) {
      map.set(r.id, r.name);
    }
    return map;
  }, [roles]);

  async function load() {
    if (typeof tenant !== "string" || tenant.length === 0) return;

    setError(null);
    setStatus(null);
    setLoading(true);

    try {
      const [usersRes, rolesRes] = await Promise.all([
        apiFetch<UsersPayload>(`/api/${tenant}/app/users`),
        apiFetch<RolesPayload>(`/api/${tenant}/app/roles`),
      ]);

      setUsers(Array.isArray(usersRes.users) ? usersRes.users : []);
      setRoles(Array.isArray(rolesRes.roles) ? rolesRes.roles : []);
      const firstRoleId = (Array.isArray(rolesRes.roles) ? rolesRes.roles : [])[0]?.id ?? null;
      setNewRoleId(firstRoleId);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to load users.");
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    void load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [tenant]);

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
      await load();
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
      await load();
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

        <Card className="shadow-none">
          <CardContent className="pt-5">
            <div className="text-sm font-semibold text-[var(--rb-text)]">Create user</div>
            <form className="mt-4 grid gap-3 md:grid-cols-4" onSubmit={onCreateUser}>
              <div className="space-y-1 md:col-span-1">
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

              <div className="space-y-1 md:col-span-1">
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

              <div className="space-y-1 md:col-span-1">
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

              <div className="space-y-1 md:col-span-1">
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

              <div className="md:col-span-4">
                <Button type="submit" disabled={busy || loading || roles.length === 0}>
                  {busy ? "Saving..." : "Create user"}
                </Button>
                {roles.length === 0 ? (
                  <div className="mt-2 text-sm text-zinc-600">Create a role first before adding users.</div>
                ) : null}
              </div>
            </form>
          </CardContent>
        </Card>

        <Card className="shadow-none">
          <CardContent className="pt-5">
            <div className="text-sm font-semibold text-[var(--rb-text)]">Users</div>
            {loading ? <div className="mt-3 text-sm text-zinc-600">Loading...</div> : null}

            {!loading ? (
              <div className="mt-4 space-y-3">
                {users.length === 0 ? <div className="text-sm text-zinc-600">No users yet.</div> : null}

                {users.map((u) => {
                  const currentRoleId = u.role_id ?? null;

                  return (
                    <div
                      key={u.id}
                      className="flex flex-col gap-2 rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-3 md:flex-row md:items-center md:justify-between"
                    >
                      <div className="min-w-0">
                        <div className="truncate text-sm font-semibold text-[var(--rb-text)]">{u.name}</div>
                        <div className="truncate text-xs text-zinc-600">{u.email}</div>
                        <div className="mt-1 text-xs text-zinc-500">
                          Role: {currentRoleId ? roleNameById.get(currentRoleId) ?? "(unknown)" : "(none)"}
                        </div>
                      </div>

                      <div className="flex items-center gap-2">
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
                      </div>
                    </div>
                  );
                })}
              </div>
            ) : null}
          </CardContent>
        </Card>
      </div>
    </RequireAuth>
  );
}
