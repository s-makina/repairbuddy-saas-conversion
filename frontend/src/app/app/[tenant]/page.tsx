"use client";

import { apiFetch } from "@/lib/api";
import type { Tenant, User } from "@/lib/types";
import { Badge, type BadgeVariant } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { PageHeader } from "@/components/ui/PageHeader";
import { Skeleton, TableSkeleton } from "@/components/ui/Skeleton";
import { useParams } from "next/navigation";
import React, { useEffect, useState } from "react";

type DashboardPayload = {
  tenant: Tenant;
  user: User;
  metrics: {
    notes_count: number;
    active_jobs_count?: number;
    completed_jobs_count?: number;
    pending_estimates_count?: number;
    revenue_last_30d?: {
      currency?: string;
      amount_cents?: number;
    };
  };
  recent?: {
    jobs?: Array<{
      id: number;
      case_number: string;
      title: string;
      status: string;
      total_cents: number;
      currency: string;
      updated_at?: string | null;
    }>;
    estimates?: Array<{
      id: number;
      case_number: string;
      title: string;
      status: string;
      total_cents: number;
      currency: string;
      updated_at?: string | null;
    }>;
  };
  activity?: Array<{
    id: number;
    visibility: "public" | "private" | string;
    event_type: string;
    entity_type: string;
    entity_id: number;
    summary?: string | null;
    description?: string | null;
    actor_email?: string | null;
    created_at?: string | null;
  }>;
};

type RecentActivity = {
  id: string;
  type: "public" | "private";
  title: string;
  description: string;
  occurred_at: string;
};

type JobRow = {
  id: string;
  title: string;
  status: string;
  total: string;
  date: string;
};

type EstimateRow = {
  id: string;
  title: string;
  status: string;
  total: string;
  date: string;
};

function formatMoney(amountCents: number, currency?: string | null) {
  const c = typeof currency === "string" && currency.trim().length > 0 ? currency.toUpperCase() : "USD";
  const value = Number.isFinite(amountCents) ? amountCents : 0;

  try {
    return new Intl.NumberFormat(undefined, {
      style: "currency",
      currency: c,
      maximumFractionDigits: 2,
    }).format(value / 100);
  } catch {
    return `${c} ${(value / 100).toFixed(2)}`;
  }
}

function ChartPlaceholder({ title, subtitle }: { title: string; subtitle: string }) {
  return (
    <Card className="shadow-none">
      <CardContent className="pt-5">
        <div className="flex flex-wrap items-baseline justify-between gap-2">
          <div>
            <div className="text-sm font-semibold text-[var(--rb-text)]">{title}</div>
            <div className="mt-1 text-sm text-zinc-600">{subtitle}</div>
          </div>
          <Badge variant="default">Mock</Badge>
        </div>
        <div className="mt-4 h-40 w-full rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)] bg-[var(--rb-surface-muted)]" />
      </CardContent>
    </Card>
  );
}

function formatShortDate(iso: string | null | undefined) {
  if (!iso) return "—";
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return "—";
  try {
    return d.toLocaleDateString(undefined, { month: "short", day: "numeric" });
  } catch {
    return "—";
  }
}

function timeAgo(iso: string | null | undefined) {
  if (!iso) return "—";
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return "—";

  const diffMs = Date.now() - d.getTime();
  const diffSec = Math.floor(diffMs / 1000);

  if (diffSec < 60) return "Just now";
  const diffMin = Math.floor(diffSec / 60);
  if (diffMin < 60) return `${diffMin} min ago`;
  const diffHr = Math.floor(diffMin / 60);
  if (diffHr < 24) return `${diffHr} hr ago`;
  const diffDay = Math.floor(diffHr / 24);
  if (diffDay < 7) return `${diffDay} day${diffDay === 1 ? "" : "s"} ago`;
  return formatShortDate(iso);
}

function DashboardSkeleton() {
  return (
    <div className="space-y-6">
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {Array.from({ length: 4 }).map((_, idx) => (
          <Card key={idx} className="relative overflow-hidden shadow-none">
            <CardContent className="pt-5">
              <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                  <Skeleton className="h-3 w-24 rounded-[var(--rb-radius-sm)]" />
                  <div className="mt-2">
                    <Skeleton className="h-8 w-28 rounded-[var(--rb-radius-sm)]" />
                  </div>
                </div>
                <Skeleton className="h-5 w-16 rounded-full" />
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      <div className="grid gap-4 lg:grid-cols-3">
        <Card className="shadow-none lg:col-span-2">
          <CardContent className="pt-5">
            <div className="flex flex-wrap items-baseline justify-between gap-2">
              <div>
                <Skeleton className="h-4 w-36 rounded-[var(--rb-radius-sm)]" />
                <Skeleton className="mt-2 h-4 w-56 rounded-[var(--rb-radius-sm)]" />
              </div>
              <Skeleton className="h-5 w-14 rounded-full" />
            </div>
            <div className="mt-4">
              <Skeleton className="h-40 w-full rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)]" />
            </div>
          </CardContent>
        </Card>

        <Card className="shadow-none">
          <CardContent className="pt-5">
            <div className="flex flex-wrap items-baseline justify-between gap-2">
              <div>
                <Skeleton className="h-4 w-32 rounded-[var(--rb-radius-sm)]" />
                <Skeleton className="mt-2 h-4 w-48 rounded-[var(--rb-radius-sm)]" />
              </div>
              <Skeleton className="h-5 w-14 rounded-full" />
            </div>
            <div className="mt-4">
              <Skeleton className="h-40 w-full rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)]" />
            </div>
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-4 lg:grid-cols-3">
        <div className="lg:col-span-2 space-y-4">
          <TableSkeleton rows={6} columns={5} />
          <TableSkeleton rows={6} columns={5} />
        </div>

        <Card className="shadow-none">
          <CardContent className="pt-5">
            <div className="flex items-center justify-between gap-3">
              <div>
                <Skeleton className="h-4 w-36 rounded-[var(--rb-radius-sm)]" />
                <Skeleton className="mt-2 h-4 w-44 rounded-[var(--rb-radius-sm)]" />
              </div>
              <Skeleton className="h-9 w-20 rounded-[var(--rb-radius-sm)]" />
            </div>

            <div className="mt-4 space-y-3">
              {Array.from({ length: 5 }).map((_, idx) => (
                <div key={idx} className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-3">
                  <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0 flex-1">
                      <Skeleton className="h-4 w-3/4 rounded-[var(--rb-radius-sm)]" />
                      <Skeleton className="mt-2 h-4 w-full rounded-[var(--rb-radius-sm)]" />
                      <Skeleton className="mt-2 h-3 w-24 rounded-[var(--rb-radius-sm)]" />
                    </div>
                    <Skeleton className="h-5 w-16 rounded-full" />
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}

export default function TenantDashboardPage() {
  const params = useParams() as { tenant?: string; business?: string };
  const tenant = params.business ?? params.tenant;

  const [loading, setLoading] = useState(true);
  const [data, setData] = useState<DashboardPayload | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        const res = await apiFetch<DashboardPayload>(`/api/${tenant}/app/dashboard`);
        if (!alive) return;
        setData(res);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load dashboard.");
      } finally {
        if (!alive) return;
        setLoading(false);
      }
    }

    if (typeof tenant === "string" && tenant.length > 0) {
      void load();
    } else {
      setLoading(false);
      setError("Tenant is missing.");
    }

    return () => {
      alive = false;
    };
  }, [tenant]);

  const isCustomer = Boolean(data?.user?.role === "customer");

  const kpis = React.useMemo(() => {
    const metrics = data?.metrics;
    const activeJobs = Number.isFinite(metrics?.active_jobs_count) ? (metrics?.active_jobs_count ?? 0) : 0;
    const completedJobs = Number.isFinite(metrics?.completed_jobs_count) ? (metrics?.completed_jobs_count ?? 0) : 0;
    const pendingEstimates = Number.isFinite(metrics?.pending_estimates_count) ? (metrics?.pending_estimates_count ?? 0) : 0;

    const revenueCents = Number.isFinite(metrics?.revenue_last_30d?.amount_cents) ? (metrics?.revenue_last_30d?.amount_cents ?? 0) : 0;
    const revenueCurrency = metrics?.revenue_last_30d?.currency ?? data?.tenant?.currency ?? "USD";

    return {
      activeJobs: Math.max(0, activeJobs),
      completedJobs: Math.max(0, completedJobs),
      pendingEstimates: Math.max(0, pendingEstimates),
      revenue: formatMoney(revenueCents, revenueCurrency),
      revenueCurrency: typeof revenueCurrency === "string" ? revenueCurrency.toUpperCase() : (data?.tenant?.currency ?? "USD").toUpperCase(),
    };
  }, [data?.metrics, data?.tenant?.currency]);

  const myJobs = React.useMemo<JobRow[]>(() => {
    const raw = data?.recent?.jobs;
    if (!Array.isArray(raw)) return [];
    return raw
      .filter((j) => j && typeof j.id === "number")
      .map((j) => {
        const currency = typeof j.currency === "string" && j.currency ? j.currency : (data?.tenant?.currency ?? "USD");
        const cents = typeof j.total_cents === "number" && Number.isFinite(j.total_cents) ? j.total_cents : 0;
        return {
          id: typeof j.case_number === "string" && j.case_number ? j.case_number : `#${j.id}`,
          title: typeof j.title === "string" ? j.title : "",
          status: typeof j.status === "string" ? j.status : "",
          total: formatMoney(cents, currency),
          date: formatShortDate(j.updated_at),
        };
      });
  }, [data?.recent?.jobs, data?.tenant?.currency]);

  const myEstimates = React.useMemo<EstimateRow[]>(() => {
    const raw = data?.recent?.estimates;
    if (!Array.isArray(raw)) return [];
    return raw
      .filter((e) => e && typeof e.id === "number")
      .map((e) => {
        const currency = typeof e.currency === "string" && e.currency ? e.currency : (data?.tenant?.currency ?? "USD");
        const cents = typeof e.total_cents === "number" && Number.isFinite(e.total_cents) ? e.total_cents : 0;
        return {
          id: typeof e.case_number === "string" && e.case_number ? e.case_number : `#${e.id}`,
          title: typeof e.title === "string" ? e.title : "",
          status: typeof e.status === "string" ? e.status : "",
          total: formatMoney(cents, currency),
          date: formatShortDate(e.updated_at),
        };
      });
  }, [data?.recent?.estimates, data?.tenant?.currency]);

  const recentActivity = React.useMemo<RecentActivity[]>(() => {
    const raw = data?.activity;
    if (!Array.isArray(raw)) return [];

    const base = raw
      .filter((a) => a && typeof a.id === "number")
      .map((a) => {
        const title =
          (typeof a.summary === "string" && a.summary.trim().length > 0 ? a.summary.trim() : null) ??
          (typeof a.event_type === "string" && a.event_type.trim().length > 0 ? a.event_type.trim() : "Activity");

        const desc =
          (typeof a.description === "string" && a.description.trim().length > 0 ? a.description.trim() : null) ??
          (typeof a.entity_type === "string" && a.entity_type.trim().length > 0 ? `${a.entity_type} #${a.entity_id}` : null);

        const visibility = typeof a.visibility === "string" && a.visibility ? a.visibility : "public";
        const activityType: RecentActivity["type"] = visibility === "private" ? "private" : "public";
        return {
          id: String(a.id),
          type: activityType,
          title,
          description: desc ?? "",
          occurred_at: timeAgo(a.created_at),
        };
      });

    return isCustomer ? base.filter((x) => x.type === "public") : base;
  }, [data?.activity, isCustomer]);

  const jobColumns = React.useMemo<Array<DataTableColumn<JobRow>>>(
    () => [
      { id: "id", header: "Job", cell: (row) => <span className="font-medium text-[var(--rb-text)]">{row.id}</span> },
      { id: "title", header: "Title", cell: (row) => <span className="text-zinc-700">{row.title}</span> },
      {
        id: "status",
        header: "Status",
        cell: (row) => (
          <Badge variant={row.status.toLowerCase().includes("deliver") ? "success" : row.status.toLowerCase().includes("diagn") ? "info" : "default"}>
            {row.status}
          </Badge>
        ),
      },
      { id: "total", header: "Total", cell: (row) => <span className="text-zinc-700">{row.total}</span>, className: "text-right" },
      { id: "date", header: "Date", cell: (row) => <span className="text-zinc-500">{row.date}</span> },
    ],
    [],
  );

  const estimateColumns = React.useMemo<Array<DataTableColumn<EstimateRow>>>(
    () => [
      { id: "id", header: "Estimate", cell: (row) => <span className="font-medium text-[var(--rb-text)]">{row.id}</span> },
      { id: "title", header: "Title", cell: (row) => <span className="text-zinc-700">{row.title}</span> },
      {
        id: "status",
        header: "Status",
        cell: (row) => (
          <Badge variant={row.status.toLowerCase() === "approved" ? "success" : row.status.toLowerCase() === "rejected" ? "danger" : "default"}>
            {row.status}
          </Badge>
        ),
      },
      {
        id: "total",
        header: "Total",
        cell: (row) => <span className="text-zinc-700">{row.total}</span>,
        className: "text-right",
      },
      { id: "date", header: "Date", cell: (row) => <span className="text-zinc-500">{row.date}</span> },
    ],
    [],
  );

  function StatCard({
    label,
    value,
    badge,
    variant,
  }: {
    label: string;
    value: string;
    badge: string;
    variant: BadgeVariant;
  }) {
    const accents: Record<BadgeVariant, { card: string; dot: string }> = {
      default: {
        card: "bg-white border-t-4 border-t-[var(--rb-border)]",
        dot: "bg-[var(--rb-border)]",
      },
      info: {
        card: "bg-[color:color-mix(in_srgb,var(--rb-blue),white_92%)] border-t-4 border-t-[color:color-mix(in_srgb,var(--rb-blue),white_20%)]",
        dot: "bg-[var(--rb-blue)]",
      },
      success: {
        card: "bg-[color:color-mix(in_srgb,#16a34a,white_92%)] border-t-4 border-t-[color:color-mix(in_srgb,#16a34a,white_20%)]",
        dot: "bg-[#16a34a]",
      },
      warning: {
        card: "bg-[color:color-mix(in_srgb,var(--rb-orange),white_92%)] border-t-4 border-t-[color:color-mix(in_srgb,var(--rb-orange),white_20%)]",
        dot: "bg-[var(--rb-orange)]",
      },
      danger: {
        card: "bg-[color:color-mix(in_srgb,#dc2626,white_92%)] border-t-4 border-t-[color:color-mix(in_srgb,#dc2626,white_20%)]",
        dot: "bg-[#dc2626]",
      },
    };

    return (
      <Card className={`relative overflow-hidden shadow-none ${accents[variant].card}`}>
        <CardContent className="pt-5">
          <div className="flex items-start justify-between gap-3">
            <div className="min-w-0">
              <div className="flex items-center gap-2 text-xs font-semibold text-zinc-600 uppercase tracking-wider">
                <span className={`h-2 w-2 rounded-full ${accents[variant].dot}`} />
                <span>{label}</span>
              </div>
              <div className="mt-2 truncate text-2xl font-bold text-[var(--rb-text)]">{value}</div>
            </div>
            <Badge variant={variant}>{badge}</Badge>
          </div>
        </CardContent>
      </Card>
    );
  }

  if (loading && !data && !error) {
    return (
      <div className="space-y-6">
        <PageHeader
          title="Dashboard"
          description="Loading dashboard..."
          actions={
            <Button variant="outline" size="sm" disabled>
              Refresh
            </Button>
          }
        />
        <DashboardSkeleton />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Dashboard"
        description={isCustomer ? "Your repairs, estimates and updates." : "Business overview (mocked widgets)."}
        actions={
          <Button variant="outline" size="sm" onClick={() => void window.location.reload()} disabled={loading}>
            Refresh
          </Button>
        }
      />
      {error ? <div className="text-sm text-red-600">{error}</div> : null}

      {data ? (
        <>
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard label="Active Jobs" value={String(kpis.activeJobs)} badge="jobs" variant="info" />
            <StatCard label="Completed" value={String(kpis.completedJobs)} badge="done" variant="success" />
            <StatCard label="Pending Estimates" value={String(kpis.pendingEstimates)} badge="est" variant="warning" />
            <StatCard label="Revenue" value={kpis.revenue} badge={(data.tenant.currency ?? "USD").toUpperCase()} variant="default" />
          </div>

          <div className="grid gap-4 lg:grid-cols-3">
            <div className="lg:col-span-2">
              <ChartPlaceholder
                title={isCustomer ? "My Jobs Overview" : "Revenue Analytics"}
                subtitle={isCustomer ? "Recent activity trend (mock)." : "Weekly / Monthly / Yearly (mock)."}
              />
            </div>
            <div>
              <ChartPlaceholder title={isCustomer ? "My Active Jobs" : "Job Status"} subtitle="Distribution (mock)." />
            </div>
          </div>

          <div className="grid gap-4 lg:grid-cols-3">
            <div className="lg:col-span-2 space-y-4">
              {isCustomer ? (
                <>
                  <DataTable
                    title="My Jobs"
                    data={myJobs}
                    columns={jobColumns}
                    getRowId={(row) => row.id}
                    emptyMessage="No jobs found."
                  />
                  <DataTable
                    title="My Estimates"
                    data={myEstimates}
                    columns={estimateColumns}
                    getRowId={(row) => row.id}
                    emptyMessage="No estimates found."
                  />
                </>
              ) : (
                <>
                  <ChartPlaceholder title="Device Types" subtitle="Top devices (mock)." />
                  <ChartPlaceholder title="Performance" subtitle="Average repair time (mock)." />
                </>
              )}
            </div>

            <Card className="shadow-none">
              <CardContent className="pt-5">
                <div className="flex items-center justify-between gap-3">
                  <div>
                    <div className="text-sm font-semibold text-[var(--rb-text)]">Recent Activity</div>
                    <div className="mt-1 text-sm text-zinc-600">Latest updates (mock).</div>
                  </div>
                  <Button variant="outline" size="sm" disabled>
                    View All
                  </Button>
                </div>

                <div className="mt-4 space-y-3">
                  {recentActivity.length === 0 ? <div className="text-sm text-zinc-600">—</div> : null}
                  {recentActivity.map((a) => (
                    <div key={a.id} className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-3">
                      <div className="flex items-start justify-between gap-3">
                        <div className="min-w-0">
                          <div className="truncate text-sm font-semibold text-[var(--rb-text)]">{a.title}</div>
                          <div className="mt-1 text-sm text-zinc-600">{a.description}</div>
                          <div className="mt-2 text-xs text-zinc-500">{a.occurred_at}</div>
                        </div>
                        <Badge variant={a.type === "private" ? "warning" : "info"}>{a.type}</Badge>
                      </div>
                    </div>
                  ))}
                </div>

                <div className="mt-5 text-xs text-zinc-500">
                  Tenant: {data.tenant.slug} · Signed in as: {data.user.email}
                </div>
              </CardContent>
            </Card>
          </div>
        </>
      ) : null}
    </div>
  );
}
