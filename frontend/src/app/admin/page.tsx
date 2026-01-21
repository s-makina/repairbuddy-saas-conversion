"use client";

import { apiFetch } from "@/lib/api";
import { formatMoney } from "@/lib/money";
import { RequireAuth } from "@/components/RequireAuth";
import { Alert } from "@/components/ui/Alert";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { PageHeader } from "@/components/ui/PageHeader";
import { Skeleton } from "@/components/ui/Skeleton";
import React, { useEffect, useMemo, useState } from "react";

type AdminDashboardKpis = {
  generated_at: string;
  tenants: { total: number; by_status: Record<string, number> };
  users: { total: number; admins: number };
  subscriptions: { active_total: number; by_status: Record<string, number> };
  revenue: {
    paid_last_30d_by_currency: Record<string, number>;
    paid_ytd_by_currency: Record<string, number>;
  };
  mrr_by_currency: Record<string, number>;
};

type AdminSalesLast12Months = {
  generated_at: string;
  months: { key: string; label: string }[];
  totals_by_currency: Record<string, number[]>;
};

export default function AdminDashboardPage() {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [kpis, setKpis] = useState<AdminDashboardKpis | null>(null);
  const [salesLoading, setSalesLoading] = useState(true);
  const [salesError, setSalesError] = useState<string | null>(null);
  const [sales, setSales] = useState<AdminSalesLast12Months | null>(null);
  const [reloadNonce, setReloadNonce] = useState(0);

  useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setError(null);
        setLoading(true);
        const res = await apiFetch<AdminDashboardKpis>("/api/admin/dashboard/kpis");
        if (!alive) return;
        setKpis(res);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load dashboard KPIs.");
        setKpis(null);
      } finally {
        if (!alive) return;
        setLoading(false);
      }
    }

    async function loadSales() {
      try {
        setSalesError(null);
        setSalesLoading(true);
        const res = await apiFetch<AdminSalesLast12Months>("/api/admin/dashboard/sales-last-12-months");
        if (!alive) return;
        setSales(res);
      } catch (e) {
        if (!alive) return;
        setSalesError(e instanceof Error ? e.message : "Failed to load sales history.");
        setSales(null);
      } finally {
        if (!alive) return;
        setSalesLoading(false);
      }
    }

    void load();
    void loadSales();

    return () => {
      alive = false;
    };
  }, [reloadNonce]);

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

  function StatCardLoading({ label, glowClassName }: { label: string; glowClassName: string }) {
    return (
      <Card className="relative overflow-hidden">
        <div className={"pointer-events-none absolute -right-10 -top-10 h-28 w-28 rounded-full blur-2xl opacity-70 " + glowClassName} />
        <CardContent className="pt-5">
          <div className="flex items-start justify-between gap-3">
            <div className="min-w-0">
              <div className="text-xs font-medium tracking-wide text-zinc-500">{label}</div>
              <div className="mt-1">
                <Skeleton className="h-8 w-28 rounded-[var(--rb-radius-sm)]" />
              </div>
            </div>
            <Skeleton className="h-5 w-16 rounded-full" />
          </div>
          <div className="mt-3 h-px w-full bg-[var(--rb-border)]" />
        </CardContent>
      </Card>
    );
  }

  const currencies = useMemo(() => {
    const set = new Set<string>();
    for (const k of Object.keys(kpis?.mrr_by_currency ?? {})) set.add(k);
    for (const k of Object.keys(kpis?.revenue?.paid_last_30d_by_currency ?? {})) set.add(k);
    for (const k of Object.keys(kpis?.revenue?.paid_ytd_by_currency ?? {})) set.add(k);
    return Array.from(set).filter(Boolean).sort();
  }, [kpis]);

  const primaryCurrency = currencies[0] ?? null;

  const mrrPrimary = primaryCurrency ? (kpis?.mrr_by_currency?.[primaryCurrency] ?? 0) : 0;
  const paid30Primary = primaryCurrency ? (kpis?.revenue?.paid_last_30d_by_currency?.[primaryCurrency] ?? 0) : 0;
  const paidYtdPrimary = primaryCurrency ? (kpis?.revenue?.paid_ytd_by_currency?.[primaryCurrency] ?? 0) : 0;

  const formatMultiCurrency = (m: Record<string, number> | null | undefined) => {
    const entries = Object.entries(m ?? {}).filter(([c]) => Boolean(c));
    if (entries.length === 0) return "—";
    return entries
      .sort((a, b) => a[0].localeCompare(b[0]))
      .map(([currency, amountCents]) => formatMoney({ amountCents, currency, fallback: "—" }))
      .join(" · ");
  };

  const byStatus = kpis?.tenants?.by_status ?? {};
  const tenantStatuses = ["active", "trial", "past_due", "suspended", "closed"]
    .filter((s) => typeof byStatus?.[s] === "number")
    .map((s) => ({ status: s, count: byStatus[s] }));

  const chartCurrency = useMemo(() => {
    const keys = Object.keys(sales?.totals_by_currency ?? {}).filter(Boolean).sort();
    return primaryCurrency ?? keys[0] ?? null;
  }, [primaryCurrency, sales]);

  const chartMonths = sales?.months ?? [];
  const chartValues = chartCurrency ? (sales?.totals_by_currency?.[chartCurrency] ?? []) : [];

  function SalesLineChart({ values }: { values: number[] }) {
    const width = 600;
    const height = 160;
    const padX = 8;
    const padY = 10;

    const max = values.reduce((m, v) => Math.max(m, v), 0);
    const safeMax = max > 0 ? max : 1;
    const n = Math.max(values.length, 1);
    const innerW = width - padX * 2;
    const innerH = height - padY * 2;

    const points = values
      .map((v, i) => {
        const x = padX + (n === 1 ? innerW / 2 : (innerW * i) / (n - 1));
        const y = padY + innerH * (1 - v / safeMax);
        return `${x.toFixed(2)},${y.toFixed(2)}`;
      })
      .join(" ");

    const area = `${padX},${height - padY} ${points} ${width - padX},${height - padY}`;

    return (
      <svg viewBox={`0 0 ${width} ${height}`} className="h-40 w-full" role="img" aria-label="Sales chart">
        <defs>
          <linearGradient id="rb-sales-fill" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stopColor="var(--rb-blue)" stopOpacity="0.25" />
            <stop offset="100%" stopColor="var(--rb-blue)" stopOpacity="0" />
          </linearGradient>
        </defs>
        <rect x="0" y="0" width={width} height={height} fill="transparent" />
        <polyline points={area} fill="url(#rb-sales-fill)" stroke="none" />
        <polyline points={points} fill="none" stroke="var(--rb-blue)" strokeWidth="2" strokeLinejoin="round" strokeLinecap="round" />
      </svg>
    );
  }

  return (
    <RequireAuth requiredPermission="admin.access">
      <div className="space-y-6">
        <PageHeader
          title="Dashboard"
          description="Platform overview (admin)."
          actions={
            <Button variant="outline" size="sm" onClick={() => setReloadNonce((n) => n + 1)} disabled={loading}>
              Refresh
            </Button>
          }
        />

        {error ? (
          <Alert variant="danger" title="Could not load KPIs">
            {error}
          </Alert>
        ) : null}

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {loading ? (
            <>
              <StatCardLoading label="Tenants" glowClassName="bg-[color:color-mix(in_srgb,var(--rb-text),white_88%)]" />
              <StatCardLoading label="Active subscriptions" glowClassName="bg-[color:color-mix(in_srgb,var(--rb-blue),white_80%)]" />
              <StatCardLoading label="MRR" glowClassName="bg-[color:color-mix(in_srgb,#16a34a,white_75%)]" />
              <StatCardLoading label="Users" glowClassName="bg-[color:color-mix(in_srgb,var(--rb-border),white_55%)]" />
            </>
          ) : (
            <>
              <StatCard
                label="Tenants"
                value={String(kpis?.tenants?.total ?? 0)}
                badge="tenants"
                badgeVariant="default"
                glowClassName="bg-[color:color-mix(in_srgb,var(--rb-text),white_88%)]"
              />
              <StatCard
                label="Active subscriptions"
                value={String(kpis?.subscriptions?.active_total ?? 0)}
                badge="subs"
                badgeVariant="info"
                glowClassName="bg-[color:color-mix(in_srgb,var(--rb-blue),white_80%)]"
              />
              <StatCard
                label="MRR"
                value={primaryCurrency ? formatMoney({ amountCents: mrrPrimary, currency: primaryCurrency }) : "—"}
                badge={primaryCurrency ?? "—"}
                badgeVariant="success"
                glowClassName="bg-[color:color-mix(in_srgb,#16a34a,white_75%)]"
              />
              <StatCard
                label="Users"
                value={String(kpis?.users?.total ?? 0)}
                badge={`${kpis?.users?.admins ?? 0} admins`}
                badgeVariant="default"
                glowClassName="bg-[color:color-mix(in_srgb,var(--rb-border),white_55%)]"
              />
            </>
          )}
        </div>

        <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
          <Card>
            <CardContent className="pt-5">
              <div className="text-sm font-semibold text-[var(--rb-text)]">Tenant status</div>
              <div className="mt-3 flex flex-wrap gap-2">
                {loading ? (
                  <>
                    <Skeleton className="h-5 w-24 rounded-full" />
                    <Skeleton className="h-5 w-20 rounded-full" />
                    <Skeleton className="h-5 w-28 rounded-full" />
                    <Skeleton className="h-5 w-24 rounded-full" />
                  </>
                ) : (
                  <>
                    {tenantStatuses.length === 0 ? <div className="text-sm text-zinc-600">—</div> : null}
                    {tenantStatuses.map((s) => (
                      <Badge key={s.status} variant="default">
                        {s.status}: {String(s.count)}
                      </Badge>
                    ))}
                  </>
                )}
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="pt-5">
              <div className="text-sm font-semibold text-[var(--rb-text)]">Revenue</div>
              <div className="mt-3 space-y-2">
                <div className="flex items-center justify-between gap-3">
                  <div className="text-sm text-zinc-600">Paid (last 30d)</div>
                  <div className="text-sm font-semibold text-[var(--rb-text)]">
                    {loading ? <Skeleton className="h-4 w-44 rounded-[var(--rb-radius-sm)]" /> : formatMultiCurrency(kpis?.revenue?.paid_last_30d_by_currency)}
                  </div>
                </div>
                <div className="flex items-center justify-between gap-3">
                  <div className="text-sm text-zinc-600">Paid (YTD)</div>
                  <div className="text-sm font-semibold text-[var(--rb-text)]">
                    {loading ? <Skeleton className="h-4 w-44 rounded-[var(--rb-radius-sm)]" /> : formatMultiCurrency(kpis?.revenue?.paid_ytd_by_currency)}
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="pt-5">
              <div className="text-sm font-semibold text-[var(--rb-text)]">Billing</div>
              <div className="mt-3 space-y-2">
                <div className="flex items-center justify-between gap-3">
                  <div className="text-sm text-zinc-600">MRR (all currencies)</div>
                  <div className="text-sm font-semibold text-[var(--rb-text)]">
                    {loading ? <Skeleton className="h-4 w-44 rounded-[var(--rb-radius-sm)]" /> : formatMultiCurrency(kpis?.mrr_by_currency)}
                  </div>
                </div>
                <div className="flex items-center justify-between gap-3">
                  <div className="text-sm text-zinc-600">Primary currency</div>
                  <div className="text-sm font-semibold text-[var(--rb-text)]">
                    {loading ? <Skeleton className="h-4 w-16 rounded-[var(--rb-radius-sm)]" /> : (primaryCurrency ?? "—")}
                  </div>
                </div>
                <div className="flex items-center justify-between gap-3">
                  <div className="text-sm text-zinc-600">Paid (30d, primary)</div>
                  <div className="text-sm font-semibold text-[var(--rb-text)]">
                    {loading ? (
                      <Skeleton className="h-4 w-28 rounded-[var(--rb-radius-sm)]" />
                    ) : primaryCurrency ? (
                      formatMoney({ amountCents: paid30Primary, currency: primaryCurrency })
                    ) : (
                      "—"
                    )}
                  </div>
                </div>
                <div className="flex items-center justify-between gap-3">
                  <div className="text-sm text-zinc-600">Paid (YTD, primary)</div>
                  <div className="text-sm font-semibold text-[var(--rb-text)]">
                    {loading ? (
                      <Skeleton className="h-4 w-28 rounded-[var(--rb-radius-sm)]" />
                    ) : primaryCurrency ? (
                      formatMoney({ amountCents: paidYtdPrimary, currency: primaryCurrency })
                    ) : (
                      "—"
                    )}
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <Card>
          <CardContent className="pt-5">
            <div className="flex flex-wrap items-baseline justify-between gap-3">
              <div>
                <div className="text-sm font-semibold text-[var(--rb-text)]">Sales (last 12 months)</div>
                <div className="mt-1 text-sm text-zinc-600">Paid invoices grouped by month.</div>
              </div>
              <div className="text-sm font-semibold text-[var(--rb-text)]">{chartCurrency ?? "—"}</div>
            </div>

            {salesError ? (
              <div className="mt-4">
                <Alert variant="danger" title="Could not load sales history">
                  {salesError}
                </Alert>
              </div>
            ) : null}

            <div className="mt-4">
              {salesLoading ? (
                <Skeleton className="h-40 w-full rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)]" />
              ) : chartValues.length === 0 ? (
                <div className="h-40 w-full rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)] bg-white p-4 text-sm text-zinc-600">
                  —
                </div>
              ) : (
                <div>
                  <SalesLineChart values={chartValues} />
                  <div className="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-zinc-600">
                    <div>
                      {chartMonths[0]?.label ?? ""}
                      {chartMonths.length > 1 ? " → " + (chartMonths[chartMonths.length - 1]?.label ?? "") : ""}
                    </div>
                    <div>
                      Total: {chartCurrency ? formatMoney({ amountCents: chartValues.reduce((a, b) => a + b, 0), currency: chartCurrency }) : "—"}
                    </div>
                    <div>
                      Last month: {chartCurrency ? formatMoney({ amountCents: chartValues[chartValues.length - 1] ?? 0, currency: chartCurrency }) : "—"}
                    </div>
                  </div>
                </div>
              )}
            </div>
          </CardContent>
        </Card>
      </div>
    </RequireAuth>
  );
}
