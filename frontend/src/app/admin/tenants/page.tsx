"use client";

import { apiFetch } from "@/lib/api";
import { formatDateTime } from "@/lib/datetime";
import type { Tenant } from "@/lib/types";
import { RequireAuth } from "@/components/RequireAuth";
import { Alert } from "@/components/ui/Alert";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { ConfirmDialog } from "@/components/ui/ConfirmDialog";
import { DataTable } from "@/components/ui/DataTable";
import { DropdownMenu, DropdownMenuItem, DropdownMenuSeparator } from "@/components/ui/DropdownMenu";
import { Input } from "@/components/ui/Input";
import { PageHeader } from "@/components/ui/PageHeader";
import { ResultDialog, type ResultDialogStatus } from "@/components/ui/ResultDialog";
import { useRouter } from "next/navigation";
import React, { useEffect, useMemo, useState } from "react";

export default function AdminTenantsPage() {
  const router = useRouter();

  const [loading, setLoading] = useState(true);
  const [tenants, setTenants] = useState<Tenant[]>([]);
  const [error, setError] = useState<string | null>(null);

  const [actionError, setActionError] = useState<string | null>(null);
  const [rowActionBusy, setRowActionBusy] = useState<Record<number, string | null>>({});

  const [confirmOpen, setConfirmOpen] = useState(false);
  const [confirmTenant, setConfirmTenant] = useState<Tenant | null>(null);
  const [confirmAction, setConfirmAction] = useState<"suspend" | "unsuspend" | null>(null);
  const [confirmReason, setConfirmReason] = useState<string>("");

  const [confirmCloseOpen, setConfirmCloseOpen] = useState(false);
  const [confirmCloseTenant, setConfirmCloseTenant] = useState<Tenant | null>(null);
  const [confirmCloseReason, setConfirmCloseReason] = useState<string>("");
  const [confirmCloseRetentionDays, setConfirmCloseRetentionDays] = useState<string>("");

  const [resultOpen, setResultOpen] = useState(false);
  const [resultStatus, setResultStatus] = useState<ResultDialogStatus>("info");
  const [resultTitle, setResultTitle] = useState<string>("");
  const [resultMessage, setResultMessage] = useState<React.ReactNode | null>(null);

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
        }>(`/api/admin/businesses?${qs.toString()}`);

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

        const res = await apiFetch<{ total: number; by_status: Record<string, number> }>(`/api/admin/businesses/stats?${qs.toString()}`);
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

  async function runRowAction(
    tenantId: number,
    name: string,
    fn: () => Promise<void>,
  ): Promise<{ ok: true } | { ok: false; error: string } | null> {
    if (!Number.isFinite(tenantId) || tenantId <= 0) return null;
    if (rowActionBusy[tenantId]) return null;

    setActionError(null);
    setRowActionBusy((m) => ({ ...m, [tenantId]: name }));
    try {
      await fn();
      setReloadNonce((n) => n + 1);
      return { ok: true };
    } catch (e) {
      const msg = e instanceof Error ? e.message : "Action failed.";
      setActionError(msg);
      return { ok: false, error: msg };
    } finally {
      setRowActionBusy((m) => ({ ...m, [tenantId]: null }));
    }
  }

  async function onSuspend(t: Tenant) {
    setConfirmTenant(t);
    setConfirmAction("suspend");
    setConfirmReason("");
    setConfirmOpen(true);
  }

  async function onUnsuspend(t: Tenant) {
    setConfirmTenant(t);
    setConfirmAction("unsuspend");
    setConfirmReason("");
    setConfirmOpen(true);
  }

  async function onClose(t: Tenant) {
    setConfirmCloseTenant(t);
    setConfirmCloseReason("");
    setConfirmCloseRetentionDays("");
    setConfirmCloseOpen(true);
  }

  const confirmBusy = !!confirmTenant && !!confirmAction && rowActionBusy[confirmTenant.id] === confirmAction;
  const confirmCloseBusy = !!confirmCloseTenant && rowActionBusy[confirmCloseTenant.id] === "close";

  async function onConfirmSuspendOrUnsuspend() {
    if (!confirmTenant || !confirmAction) return;

    const t = confirmTenant;
    const action = confirmAction;
    const reason = confirmReason.trim();

    const endpoint = action === "suspend" ? "suspend" : "unsuspend";
    const res = await runRowAction(t.id, action, async () => {
      await apiFetch(`/api/admin/businesses/${t.id}/${endpoint}`, {
        method: "PATCH",
        body: { reason: reason || undefined },
      });
    });

    setConfirmOpen(false);
    setConfirmTenant(null);
    setConfirmAction(null);
    setConfirmReason("");

    if (!res) return;

    if (res?.ok) {
      setResultStatus("success");
      setResultTitle(action === "suspend" ? "Business suspended" : "Business unsuspended");
      setResultMessage(<div>“{t.name}” was updated successfully.</div>);
      setResultOpen(true);
    } else if (res && !res.ok) {
      setResultStatus("error");
      setResultTitle("Action failed");
      setResultMessage(res.error);
      setResultOpen(true);
    }
  }

  async function onConfirmClose() {
    if (!confirmCloseTenant) return;

    const t = confirmCloseTenant;
    const reason = confirmCloseReason.trim();
    const retentionDays = confirmCloseRetentionDays.trim();
    const parsedRetention = retentionDays.length > 0 ? Number(retentionDays) : null;

    const res = await runRowAction(t.id, "close", async () => {
      await apiFetch(`/api/admin/businesses/${t.id}/close`, {
        method: "PATCH",
        body: {
          reason: reason || undefined,
          data_retention_days: parsedRetention && Number.isFinite(parsedRetention) ? parsedRetention : undefined,
        },
      });
    });

    setConfirmCloseOpen(false);
    setConfirmCloseTenant(null);
    setConfirmCloseReason("");
    setConfirmCloseRetentionDays("");

    if (!res) return;

    if (res.ok) {
      setResultStatus("success");
      setResultTitle("Business closed");
      setResultMessage(<div>“{t.name}” was closed successfully.</div>);
      setResultOpen(true);
    } else {
      setResultStatus("error");
      setResultTitle("Action failed");
      setResultMessage(res.error);
      setResultOpen(true);
    }
  }

  return (
    <RequireAuth requiredPermission="admin.tenants.read">
      <div className="space-y-6">
        <PageHeader
          title="Businesses"
          description="Manage businesses (admin)."
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
          <Alert variant="danger" title="Could not load businesses">
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
            label="Total businesses"
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
              title="Businesses"
              data={tenants}
              loading={loading}
              emptyMessage="No businesses found."
              getRowId={(t) => t.id}
              onRowClick={(t) => {
                router.push(`/admin/businesses/${t.id}`);
              }}
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
                url: "/api/admin/businesses/export",
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
                      <div className="flex items-center justify-end">
                        <DropdownMenu
                          align="right"
                          trigger={({ toggle }) => (
                            <Button
                              variant="ghost"
                              size="sm"
                              aria-label="Actions"
                              title="Actions"
                              className="px-2"
                              onClick={(e) => {
                                e.stopPropagation();
                                toggle();
                              }}
                            >
                              <svg viewBox="0 0 24 24" className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                                <circle cx="12" cy="5" r="1.5" />
                                <circle cx="12" cy="12" r="1.5" />
                                <circle cx="12" cy="19" r="1.5" />
                              </svg>
                            </Button>
                          )}
                        >
                          {({ close }) => (
                            <>
                              <DropdownMenuItem
                                onSelect={() => {
                                  close();
                                  router.push(`/admin/businesses/${t.id}`);
                                }}
                              >
                                View
                              </DropdownMenuItem>
                              <DropdownMenuSeparator />
                              <DropdownMenuItem
                                onSelect={() => {
                                  close();
                                  void onSuspend(t);
                                }}
                                disabled={!!busy || !canSuspend}
                              >
                                {busy === "suspend" ? "…" : "Suspend"}
                              </DropdownMenuItem>
                              <DropdownMenuItem
                                onSelect={() => {
                                  close();
                                  void onUnsuspend(t);
                                }}
                                disabled={!!busy || !canUnsuspend}
                              >
                                {busy === "unsuspend" ? "…" : "Unsuspend"}
                              </DropdownMenuItem>
                              <DropdownMenuItem
                                onSelect={() => {
                                  close();
                                  void onClose(t);
                                }}
                                disabled={!!busy || !canClose}
                                destructive
                              >
                                {busy === "close" ? "…" : "Close"}
                              </DropdownMenuItem>
                            </>
                          )}
                        </DropdownMenu>
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

        <ConfirmDialog
          open={confirmOpen}
          title={confirmAction === "suspend" ? "Suspend business" : "Unsuspend business"}
          message={
            <div className="space-y-3">
              <div>
                {confirmAction === "suspend"
                  ? "This will suspend the business and prevent normal access."
                  : "This will restore access for the business."}
              </div>
              <div className="space-y-1">
                <div className="text-xs font-medium text-zinc-600">Reason (optional)</div>
                <Input
                  value={confirmReason}
                  onChange={(e) => setConfirmReason(e.target.value)}
                  placeholder="e.g. Non-payment, abuse, user request"
                />
              </div>
            </div>
          }
          confirmText={confirmAction === "suspend" ? "Suspend" : "Unsuspend"}
          confirmVariant="secondary"
          busy={confirmBusy}
          onCancel={() => {
            setConfirmOpen(false);
            setConfirmTenant(null);
            setConfirmAction(null);
            setConfirmReason("");
          }}
          onConfirm={() => void onConfirmSuspendOrUnsuspend()}
        />

        <ConfirmDialog
          open={confirmCloseOpen}
          title="Close business"
          message={
            <div className="space-y-3">
              <div>This is a destructive action. The business will be closed.</div>
              <div className="space-y-1">
                <div className="text-xs font-medium text-zinc-600">Reason (optional)</div>
                <Input
                  value={confirmCloseReason}
                  onChange={(e) => setConfirmCloseReason(e.target.value)}
                  placeholder="e.g. customer request, duplicate tenant"
                />
              </div>
              <div className="space-y-1">
                <div className="text-xs font-medium text-zinc-600">Data retention days override (optional)</div>
                <Input
                  type="number"
                  inputMode="numeric"
                  value={confirmCloseRetentionDays}
                  onChange={(e) => setConfirmCloseRetentionDays(e.target.value)}
                  placeholder="e.g. 30"
                />
              </div>
            </div>
          }
          confirmText="Close"
          confirmVariant="secondary"
          busy={confirmCloseBusy}
          onCancel={() => {
            setConfirmCloseOpen(false);
            setConfirmCloseTenant(null);
            setConfirmCloseReason("");
            setConfirmCloseRetentionDays("");
          }}
          onConfirm={() => void onConfirmClose()}
        />

        <ResultDialog
          open={resultOpen}
          status={resultStatus}
          title={resultTitle}
          message={resultMessage}
          onClose={() => {
            setResultOpen(false);
            setResultMessage(null);
            setResultTitle("");
            setResultStatus("info");
          }}
        />
      </div>
    </RequireAuth>
  );
}
