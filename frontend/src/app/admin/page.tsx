"use client";

import { apiFetch } from "@/lib/api";
import { formatDateTime } from "@/lib/datetime";
import type { Tenant } from "@/lib/types";
 import { RequireAuth } from "@/components/RequireAuth";
import { Alert } from "@/components/ui/Alert";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { DataTable } from "@/components/ui/DataTable";
import { PageHeader } from "@/components/ui/PageHeader";
import Link from "next/link";
import React, { useEffect, useMemo, useState } from "react";

export default function AdminDashboardPage() {
  const [loading, setLoading] = useState(true);
  const [tenants, setTenants] = useState<Tenant[]>([]);
  const [error, setError] = useState<string | null>(null);

  const [actionError, setActionError] = useState<string | null>(null);
  const [rowActionBusy, setRowActionBusy] = useState<Record<number, string | null>>({});

  const [statsLoading, setStatsLoading] = useState(true);
  const [statsError, setStatsError] = useState<string | null>(null);
  const [stats, setStats] = useState<{ total: number; by_status: Record<string, number> } | null>(null);

  const [reloadNonce, setReloadNonce] = useState(0);

  const [q, setQ] = useState<string>("");
  const [statusFilter, setStatusFilter] = useState<string>("all");
  const [pageIndex, setPageIndex] = useState<number>(0);
  const [pageSize, setPageSize] = useState<number>(10);
  const [totalTenants, setTotalTenants] = useState<number>(0);
  const [sort, setSort] = useState<{ id: string; dir: "asc" | "desc" } | null>(null);

  const statusOptions = useMemo(() => {
    return [
      { label: "All statuses", value: "all" },
      { label: "Trial", value: "trial" },
      { label: "Active", value: "active" },
      { label: "Past due", value: "past_due" },
      { label: "Suspended", value: "suspended" },
      { label: "Closed", value: "closed" },
    ];
  }, []);

  const statusVariant = useMemo(() => {
    return (status: Tenant["status"]) => {
      if (status === "active") return "success" as const;
      if (status === "trial") return "info" as const;
      if (status === "past_due") return "warning" as const;
      if (status === "suspended") return "danger" as const;
      return "default" as const;
    };
  }, []);

  useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setError(null);
        setLoading(true);

        const qs = new URLSearchParams();
        if (q.trim().length > 0) qs.set("q", q.trim());
        if (statusFilter && statusFilter !== "all") qs.set("status", statusFilter);
        if (sort?.id && sort?.dir) {
          qs.set("sort", sort.id);
          qs.set("dir", sort.dir);
        }
        qs.set("page", String(pageIndex + 1));
        qs.set("per_page", String(pageSize));

        const res = await apiFetch<{
          tenants: Tenant[];
          meta?: { current_page: number; per_page: number; total: number; last_page: number };
        }>(`/api/admin/tenants?${qs.toString()}`);

        if (!alive) return;
        setTenants(Array.isArray(res.tenants) ? res.tenants : []);
        setTotalTenants(typeof res.meta?.total === "number" ? res.meta.total : 0);
        setPageSize(typeof res.meta?.per_page === "number" ? res.meta.per_page : pageSize);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load tenants.");
      } finally {
        if (!alive) return;
        setLoading(false);
      }
    }

    void load();

    return () => {
      alive = false;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [q, statusFilter, pageIndex, pageSize, sort?.id, sort?.dir, reloadNonce]);

  useEffect(() => {
    let alive = true;

    async function loadStats() {
      try {
        setStatsError(null);
        setStatsLoading(true);

        const qs = new URLSearchParams();
        if (q.trim().length > 0) qs.set("q", q.trim());
        if (statusFilter && statusFilter !== "all") qs.set("status", statusFilter);

        const res = await apiFetch<{ total: number; by_status: Record<string, number> }>(`/api/admin/tenants/stats?${qs.toString()}`);
        if (!alive) return;
        setStats(res);
      } catch (e) {
        if (!alive) return;
        setStatsError(e instanceof Error ? e.message : "Failed to load stats.");
        setStats(null);
      } finally {
        if (!alive) return;
        setStatsLoading(false);
      }
    }

    void loadStats();

    return () => {
      alive = false;
    };
  }, [q, statusFilter, reloadNonce]);

  function StatCard({
    label,
    value,
    badge,
    badgeVariant,
    glowClassName,
  }: {
    label: string;
    value: string;
    badge: string;
    badgeVariant: Parameters<typeof Badge>[0]["variant"];
    glowClassName: string;
  }) {
    return (
      <Card className="relative overflow-hidden">
        <div className={"pointer-events-none absolute -right-10 -top-10 h-28 w-28 rounded-full blur-2xl opacity-70 " + glowClassName} />
        <CardContent className="pt-5">
          <div className="flex items-start justify-between gap-3">
            <div className="min-w-0">
              <div className="text-xs font-medium tracking-wide text-zinc-500">{label}</div>
              <div className="mt-1 truncate text-2xl font-semibold text-[var(--rb-text)]">{value}</div>
            </div>
            <Badge variant={badgeVariant}>{badge}</Badge>
          </div>
          <div className="mt-3 h-px w-full bg-[var(--rb-border)]" />
        </CardContent>
      </Card>
    );
  }

  async function runRowAction(tenantId: number, name: string, fn: () => Promise<void>) {
    if (!Number.isFinite(tenantId) || tenantId <= 0) return;
    if (rowActionBusy[tenantId]) return;

    setActionError(null);
    setRowActionBusy((m) => ({ ...m, [tenantId]: name }));
    try {
      await fn();
      setReloadNonce((n) => n + 1);
    } catch (e) {
      setActionError(e instanceof Error ? e.message : "Action failed.");
    } finally {
      setRowActionBusy((m) => ({ ...m, [tenantId]: null }));
    }
  }

  async function onSuspend(t: Tenant) {
    const reason = window.prompt("Reason for suspension (optional):") ?? "";
    await runRowAction(t.id, "suspend", async () => {
      await apiFetch(`/api/admin/tenants/${t.id}/suspend`, {
        method: "PATCH",
        body: { reason: reason.trim() || undefined },
      });
    });
  }

  async function onUnsuspend(t: Tenant) {
    const reason = window.prompt("Reason for unsuspension (optional):") ?? "";
    await runRowAction(t.id, "unsuspend", async () => {
      await apiFetch(`/api/admin/tenants/${t.id}/unsuspend`, {
        method: "PATCH",
        body: { reason: reason.trim() || undefined },
      });
    });
  }

  async function onClose(t: Tenant) {
    const confirmed = window.confirm(`Close tenant “${t.name}”? This is a destructive action.`);
    if (!confirmed) return;

    const reason = window.prompt("Reason for closing (optional):") ?? "";
    const retentionDays = window.prompt("Data retention days override (optional, numeric):") ?? "";
    const parsedRetention = retentionDays.trim().length > 0 ? Number(retentionDays.trim()) : null;

    await runRowAction(t.id, "close", async () => {
      await apiFetch(`/api/admin/tenants/${t.id}/close`, {
        method: "PATCH",
        body: {
          reason: reason.trim() || undefined,
          data_retention_days: parsedRetention && Number.isFinite(parsedRetention) ? parsedRetention : undefined,
        },
      });
    });
  }

  return (
    <RequireAuth requiredPermission="admin.tenants.read">
      <div className="space-y-6">
        <PageHeader
          title="Tenants"
          description="Manage tenants (admin)."
          actions={
            <Button
              variant="outline"
              size="sm"
              onClick={() => setReloadNonce((n) => n + 1)}
              disabled={loading}
            >
              Refresh
            </Button>
          }
        />

        {error ? (
          <Alert variant="danger" title="Could not load tenants">
            {error}
          </Alert>
        ) : null}

        {actionError ? (
          <Alert variant="danger" title="Action failed">
            {actionError}
          </Alert>
        ) : null}

        {statsError ? (
          <Alert variant="danger" title="Could not load stats">
            {statsError}
          </Alert>
        ) : null}

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6">
          <StatCard
            label="Total tenants"
            value={statsLoading ? "—" : String(stats?.total ?? 0)}
            badge="all"
            badgeVariant="default"
            glowClassName="bg-[color:color-mix(in_srgb,var(--rb-text),white_88%)]"
          />
          <StatCard
            label="Active"
            value={statsLoading ? "—" : String(stats?.by_status?.active ?? 0)}
            badge="active"
            badgeVariant="success"
            glowClassName="bg-[color:color-mix(in_srgb,#16a34a,white_75%)]"
          />
          <StatCard
            label="Trial"
            value={statsLoading ? "—" : String(stats?.by_status?.trial ?? 0)}
            badge="trial"
            badgeVariant="info"
            glowClassName="bg-[color:color-mix(in_srgb,var(--rb-blue),white_80%)]"
          />
          <StatCard
            label="Past due"
            value={statsLoading ? "—" : String(stats?.by_status?.past_due ?? 0)}
            badge="past due"
            badgeVariant="warning"
            glowClassName="bg-[color:color-mix(in_srgb,var(--rb-orange),white_78%)]"
          />
          <StatCard
            label="Suspended"
            value={statsLoading ? "—" : String(stats?.by_status?.suspended ?? 0)}
            badge="suspended"
            badgeVariant="danger"
            glowClassName="bg-[color:color-mix(in_srgb,#dc2626,white_78%)]"
          />
          <StatCard
            label="Closed"
            value={statsLoading ? "—" : String(stats?.by_status?.closed ?? 0)}
            badge="closed"
            badgeVariant="default"
            glowClassName="bg-[color:color-mix(in_srgb,var(--rb-border),white_55%)]"
          />
        </div>

        <Card>
          <CardContent className="pt-5">
            <DataTable
              title="Tenants"
              data={tenants}
              loading={loading}
              emptyMessage="No tenants found."
              getRowId={(t) => t.id}
              search={{
                placeholder: "Search name, slug, or email...",
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
                totalRows: totalTenants,
                sort,
                onSortChange: (next) => {
                  setSort(next);
                  setPageIndex(0);
                },
              }}
              exportConfig={{
                url: "/api/admin/tenants/export",
                formats: ["csv", "xlsx", "pdf"],
                filename: ({ format }) => `tenants_export.${format}`,
              }}
              columnVisibilityKey="rb:datatable:admin:tenants"
              filters={[
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
                  id: "id",
                  header: "ID",
                  sortId: "id",
                  cell: (t) => <div className="text-sm text-zinc-700">{t.id}</div>,
                  className: "whitespace-nowrap",
                },
                {
                  id: "name",
                  header: "Name",
                  sortId: "name",
                  cell: (t) => (
                    <div className="min-w-0">
                      <div className="truncate font-semibold text-[var(--rb-text)]">{t.name}</div>
                      {t.contact_email ? <div className="truncate text-xs text-zinc-600">{t.contact_email}</div> : null}
                    </div>
                  ),
                  className: "max-w-[420px]",
                },
                {
                  id: "slug",
                  header: "Slug",
                  sortId: "slug",
                  cell: (t) => <div className="text-sm text-zinc-700">{t.slug}</div>,
                  className: "whitespace-nowrap",
                },
                {
                  id: "status",
                  header: "Status",
                  sortId: "status",
                  cell: (t) => <Badge variant={statusVariant(t.status)}>{t.status}</Badge>,
                  className: "whitespace-nowrap",
                },
                {
                  id: "contact_email",
                  header: "Contact Email",
                  sortId: "contact_email",
                  hiddenByDefault: true,
                  cell: (t) => <div className="text-sm text-zinc-700">{t.contact_email ?? ""}</div>,
                  className: "whitespace-nowrap",
                },
                {
                  id: "created_at",
                  header: "Created",
                  sortId: "created_at",
                  hiddenByDefault: true,
                  cell: (t) => <div className="text-sm text-zinc-700">{formatDateTime(t.created_at ?? null)}</div>,
                  className: "whitespace-nowrap",
                },
                {
                  id: "actions",
                  header: "",
                  cell: (t) => {
                    const busy = rowActionBusy[t.id];
                    const canSuspend = t.status !== "closed";
                    const canUnsuspend = t.status === "suspended";
                    const canClose = t.status !== "closed";

                    return (
                      <div className="flex items-center justify-end gap-2">
                        <Link className="text-sm text-[var(--rb-blue)] hover:underline" href={`/admin/tenants/${t.id}`}>
                          View
                        </Link>
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => void onSuspend(t)}
                          disabled={!!busy || !canSuspend}
                        >
                          {busy === "suspend" ? "…" : "Suspend"}
                        </Button>
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => void onUnsuspend(t)}
                          disabled={!!busy || !canUnsuspend}
                        >
                          {busy === "unsuspend" ? "…" : "Unsuspend"}
                        </Button>
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => void onClose(t)}
                          disabled={!!busy || !canClose}
                        >
                          {busy === "close" ? "…" : "Close"}
                        </Button>
                      </div>
                    );
                  },
                  className: "whitespace-nowrap text-right",
                  headerClassName: "text-right",
                },
              ]}
            />
          </CardContent>
        </Card>
      </div>
    </RequireAuth>
  );
}
