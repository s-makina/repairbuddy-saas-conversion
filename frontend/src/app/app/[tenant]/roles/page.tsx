"use client";

import { apiFetch, ApiError } from "@/lib/api";
import type { Permission, Role } from "@/lib/types";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { PageHeader } from "@/components/ui/PageHeader";
import { Skeleton } from "@/components/ui/Skeleton";
import { RequireAuth } from "@/components/RequireAuth";
import { useParams } from "next/navigation";
import React, { useEffect, useMemo, useState } from "react";

type RolesPayload = {
  roles: Role[];
};

type PermissionsPayload = {
  permissions: Permission[];
};

export default function TenantRolesPage() {
  const params = useParams() as { tenant?: string; business?: string };
  const tenant = params.business ?? params.tenant;

  const [loading, setLoading] = useState(true);
  const [roles, setRoles] = useState<Role[]>([]);
  const [permissions, setPermissions] = useState<Permission[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [status, setStatus] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  const [selectedRoleId, setSelectedRoleId] = useState<number | null>(null);

  const selectedRole = useMemo(() => {
    if (!selectedRoleId) return null;
    return roles.find((r) => r.id === selectedRoleId) ?? null;
  }, [roles, selectedRoleId]);

  const [editName, setEditName] = useState("");
  const [editPerms, setEditPerms] = useState<Record<string, boolean>>({});

  async function load() {
    if (typeof tenant !== "string" || tenant.length === 0) return;

    setError(null);
    setStatus(null);
    setLoading(true);

    try {
      const [rolesRes, permsRes] = await Promise.all([
        apiFetch<RolesPayload>(`/api/${tenant}/app/roles`),
        apiFetch<PermissionsPayload>(`/api/${tenant}/app/permissions`),
      ]);

      const nextRoles = Array.isArray(rolesRes.roles) ? rolesRes.roles : [];
      const nextPerms = Array.isArray(permsRes.permissions) ? permsRes.permissions : [];

      setRoles(nextRoles);
      setPermissions(nextPerms);

      const firstRoleId = nextRoles[0]?.id ?? null;
      setSelectedRoleId((prev) => prev ?? firstRoleId);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to load roles.");
    } finally {
      setLoading(false);
    }
  }

  function RolesSkeleton() {
    return (
      <Card className="shadow-none">
        <CardContent className="pt-5">
          <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
              <Skeleton className="h-4 w-24 rounded-[var(--rb-radius-sm)]" />
              <Skeleton className="mt-2 h-4 w-64 rounded-[var(--rb-radius-sm)]" />
            </div>
            <Skeleton className="h-9 w-28 rounded-[var(--rb-radius-sm)]" />
          </div>

          <div className="mt-4 grid gap-4 lg:grid-cols-3">
            <div className="space-y-2">
              {Array.from({ length: 6 }).map((_, idx) => (
                <div key={idx} className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white px-3 py-2">
                  <Skeleton className="h-4 w-40 rounded-[var(--rb-radius-sm)]" />
                  <Skeleton className="mt-2 h-3 w-24 rounded-[var(--rb-radius-sm)]" />
                </div>
              ))}
            </div>

            <div className="lg:col-span-2 space-y-4">
              <div className="space-y-1">
                <Skeleton className="h-4 w-24 rounded-[var(--rb-radius-sm)]" />
                <Skeleton className="h-9 w-full rounded-[var(--rb-radius-sm)]" />
              </div>
              <div className="space-y-2">
                <Skeleton className="h-4 w-28 rounded-[var(--rb-radius-sm)]" />
                <div className="grid gap-2 sm:grid-cols-2">
                  {Array.from({ length: 10 }).map((_, idx) => (
                    <div key={idx} className="rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2">
                      <Skeleton className="h-4 w-40 rounded-[var(--rb-radius-sm)]" />
                    </div>
                  ))}
                </div>
              </div>
              <div className="flex items-center gap-2">
                <Skeleton className="h-9 w-20 rounded-[var(--rb-radius-sm)]" />
                <Skeleton className="h-9 w-20 rounded-[var(--rb-radius-sm)]" />
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    );
  }

  useEffect(() => {
    void load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [tenant]);

  useEffect(() => {
    if (!selectedRole) return;
    setEditName(selectedRole.name);

    const map: Record<string, boolean> = {};
    const current = selectedRole.permissions ?? [];
    for (const p of current) map[p.name] = true;
    setEditPerms(map);
  }, [selectedRole]);

  async function onCreateRole() {
    setBusy(true);
    setError(null);
    setStatus(null);

    try {
      const roleName = window.prompt("Role name");
      if (!roleName) return;

      await apiFetch<{ role: Role }>(`/api/${tenant}/app/roles`, {
        method: "POST",
        body: { name: roleName, permission_names: [] },
      });

      setStatus("Role created.");
      await load();
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError("Failed to create role.");
      }
    } finally {
      setBusy(false);
    }
  }

  async function onSaveRole(e: React.FormEvent) {
    e.preventDefault();
    if (!selectedRole) return;

    setBusy(true);
    setError(null);
    setStatus(null);

    try {
      const names = Object.entries(editPerms)
        .filter(([, v]) => v)
        .map(([k]) => k);

      await apiFetch<{ role: Role }>(`/api/${tenant}/app/roles/${selectedRole.id}`, {
        method: "PUT",
        body: {
          name: editName,
          permission_names: names,
        },
      });

      setStatus("Role updated.");
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

  async function onDeleteRole() {
    if (!selectedRole) return;

    const ok = window.confirm(`Delete role "${selectedRole.name}"?`);
    if (!ok) return;

    setBusy(true);
    setError(null);
    setStatus(null);

    try {
      await apiFetch<{ status: "ok" }>(`/api/${tenant}/app/roles/${selectedRole.id}`, {
        method: "DELETE",
      });

      setSelectedRoleId(null);
      setStatus("Role deleted.");
      await load();
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError("Failed to delete role.");
      }
    } finally {
      setBusy(false);
    }
  }

  return (
    <RequireAuth requiredPermission="roles.manage">
      <div className="space-y-6">
        <PageHeader title="Roles" description="Create roles and assign permissions." />

        {status ? <div className="text-sm text-green-700">{status}</div> : null}
        {error ? <div className="text-sm text-red-600">{error}</div> : null}

        <Card className="shadow-none">
          <CardContent className="pt-5">
            <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
              <div>
                <div className="text-sm font-semibold text-[var(--rb-text)]">Roles</div>
                <div className="mt-1 text-sm text-zinc-600">Select a role to edit its permissions.</div>
              </div>
              <Button type="button" onClick={() => void onCreateRole()} disabled={busy}>
                Create role
              </Button>
            </div>

            {loading ? (
              <div className="mt-4">
                <RolesSkeleton />
              </div>
            ) : (
              <div className="mt-4 grid gap-4 lg:grid-cols-3">
                <div className="space-y-2">
                  {roles.length === 0 ? <div className="text-sm text-zinc-600">No roles.</div> : null}

                  {roles.map((r) => {
                    const active = r.id === selectedRoleId;
                    return (
                      <button
                        key={r.id}
                        type="button"
                        onClick={() => setSelectedRoleId(r.id)}
                        className={
                          "w-full rounded-[var(--rb-radius-md)] border px-3 py-2 text-left " +
                          (active
                            ? "border-[var(--rb-orange)] bg-[rgba(244,162,46,0.10)]"
                            : "border-[var(--rb-border)] bg-white hover:bg-[var(--rb-surface-muted)]")
                        }
                      >
                        <div className="text-sm font-semibold text-[var(--rb-text)]">{r.name}</div>
                        <div className="mt-1 text-xs text-zinc-600">{(r.permissions ?? []).length} permissions</div>
                      </button>
                    );
                  })}
                </div>

                <div className="lg:col-span-2">
                  {selectedRole ? (
                    <form className="space-y-4" onSubmit={onSaveRole}>
                      <div className="space-y-1">
                        <label className="text-sm font-medium" htmlFor="role_name">
                          Role name
                        </label>
                        <input
                          id="role_name"
                          className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                          value={editName}
                          onChange={(e) => setEditName(e.target.value)}
                          required
                        />
                      </div>

                      <div className="space-y-2">
                        <div className="text-sm font-medium">Permissions</div>
                        <div className="grid gap-2 sm:grid-cols-2">
                          {permissions.map((p) => {
                            const checked = Boolean(editPerms[p.name]);
                            return (
                              <label
                                key={p.id}
                                className="flex items-center gap-2 rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                              >
                                <input
                                  type="checkbox"
                                  checked={checked}
                                  onChange={(e) =>
                                    setEditPerms((prev) => ({
                                      ...prev,
                                      [p.name]: e.target.checked,
                                    }))
                                  }
                                />
                                <span className="font-mono text-[12px] text-zinc-700">{p.name}</span>
                              </label>
                            );
                          })}
                        </div>
                      </div>

                      <div className="flex items-center gap-2">
                        <Button type="submit" disabled={busy}>
                          {busy ? "Saving..." : "Save"}
                        </Button>
                        <Button type="button" variant="outline" onClick={() => void onDeleteRole()} disabled={busy}>
                          Delete
                        </Button>
                      </div>
                    </form>
                  ) : (
                    <div className="text-sm text-zinc-600">Select a role to edit.</div>
                  )}
                </div>
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </RequireAuth>
  );
}
