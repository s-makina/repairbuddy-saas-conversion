"use client";

import { apiFetch } from "@/lib/api";
import type { Tenant, User } from "@/lib/types";
import { Badge, type BadgeVariant } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { PageHeader } from "@/components/ui/PageHeader";
import { useParams } from "next/navigation";
import React, { useEffect, useState } from "react";

type DashboardPayload = {
  tenant: Tenant;
  user: User;
  metrics: {
    notes_count: number;
  };
};

type MockRecentActivity = {
  id: string;
  type: "public" | "private";
  title: string;
  description: string;
  occurred_at: string;
};

type MockJobRow = {
  id: string;
  title: string;
  status: string;
  total: string;
  date: string;
};

type MockEstimateRow = {
  id: string;
  title: string;
  status: string;
  total: string;
  date: string;
};

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
    const seed = data?.metrics?.notes_count ?? 0;
    return {
      activeJobs: Math.max(0, seed + 6),
      completedJobs: Math.max(0, seed + 2),
      pendingEstimates: Math.max(0, Math.floor(seed / 2) + 1),
      revenue: "$12,480",
    };
  }, [data?.metrics?.notes_count]);

  const recentActivity = React.useMemo<MockRecentActivity[]>(() => {
    const base: MockRecentActivity[] = [
      {
        id: "a1",
        type: "public",
        title: "Customer updated job notes",
        description: "Added a message to Job #RB-10421",
        occurred_at: "2 hours ago",
      },
      {
        id: "a2",
        type: "private",
        title: "Technician status change",
        description: "Moved Job #RB-10418 to Diagnosing",
        occurred_at: "Yesterday",
      },
      {
        id: "a3",
        type: "public",
        title: "Estimate sent",
        description: "Estimate #E-883 sent to customer",
        occurred_at: "2 days ago",
      },
      {
        id: "a4",
        type: "public",
        title: "Payment recorded",
        description: "Receipt for Job #RB-10412",
        occurred_at: "Last week",
      },
    ];

    return isCustomer ? base.filter((x) => x.type === "public") : base;
  }, [isCustomer]);

  const myJobs = React.useMemo<MockJobRow[]>(() => {
    return [
      { id: "RB-10421", title: "iPhone 13 battery replacement", status: "In service", total: "$89.00", date: "Jan 26" },
      { id: "RB-10418", title: "Laptop diagnostics", status: "Diagnosing", total: "$45.00", date: "Jan 25" },
      { id: "RB-10412", title: "Screen repair", status: "Delivered", total: "$129.00", date: "Jan 20" },
    ];
  }, []);

  const myEstimates = React.useMemo<MockEstimateRow[]>(() => {
    return [
      { id: "E-883", title: "MacBook keyboard replacement", status: "Pending", total: "$220.00", date: "Jan 26" },
      { id: "E-879", title: "iPad charging port", status: "Approved", total: "$140.00", date: "Jan 23" },
    ];
  }, []);

  const jobColumns = React.useMemo<Array<DataTableColumn<MockJobRow>>>(
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

  const estimateColumns = React.useMemo<Array<DataTableColumn<MockEstimateRow>>>(
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
      { id: "total", header: "Total", cell: (row) => <span className="text-zinc-700">{row.total}</span>, className: "text-right" },
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

      {loading ? <div className="text-sm text-zinc-500">Loading dashboard...</div> : null}
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
