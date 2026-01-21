"use client";

import { apiFetch } from "@/lib/api";
import { formatMoney } from "@/lib/money";
import { RequireAuth } from "@/components/RequireAuth";
import { Alert } from "@/components/ui/Alert";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { PageHeader } from "@/components/ui/PageHeader";
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

export default function AdminDashboardPage() {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [kpis, setKpis] = useState<AdminDashboardKpis | null>(null);
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

    void load();

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
          <StatCard
            label="Tenants"
            value={loading ? "—" : String(kpis?.tenants?.total ?? 0)}
            badge="tenants"
            badgeVariant="default"
            glowClassName="bg-[color:color-mix(in_srgb,var(--rb-text),white_88%)]"
          />
          <StatCard
            label="Active subscriptions"
            value={loading ? "—" : String(kpis?.subscriptions?.active_total ?? 0)}
            badge="subs"
            badgeVariant="info"
            glowClassName="bg-[color:color-mix(in_srgb,var(--rb-blue),white_80%)]"
          />
          <StatCard
            label="MRR"
            value={loading ? "—" : primaryCurrency ? formatMoney({ amountCents: mrrPrimary, currency: primaryCurrency }) : "—"}
            badge={primaryCurrency ?? "—"}
            badgeVariant="success"
            glowClassName="bg-[color:color-mix(in_srgb,#16a34a,white_75%)]"
          />
          <StatCard
            label="Users"
            value={loading ? "—" : String(kpis?.users?.total ?? 0)}
            badge={loading ? "—" : `${kpis?.users?.admins ?? 0} admins`}
            badgeVariant="default"
            glowClassName="bg-[color:color-mix(in_srgb,var(--rb-border),white_55%)]"
          />
        </div>

        <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
          <Card>
            <CardContent className="pt-5">
              <div className="text-sm font-semibold text-[var(--rb-text)]">Tenant status</div>
              <div className="mt-3 flex flex-wrap gap-2">
                {tenantStatuses.length === 0 ? <div className="text-sm text-zinc-600">—</div> : null}
                {tenantStatuses.map((s) => (
                  <Badge key={s.status} variant="default">
                    {s.status}: {loading ? "—" : String(s.count)}
                  </Badge>
                ))}
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
                    {loading ? "—" : formatMultiCurrency(kpis?.revenue?.paid_last_30d_by_currency)}
                  </div>
                </div>
                <div className="flex items-center justify-between gap-3">
                  <div className="text-sm text-zinc-600">Paid (YTD)</div>
                  <div className="text-sm font-semibold text-[var(--rb-text)]">
                    {loading ? "—" : formatMultiCurrency(kpis?.revenue?.paid_ytd_by_currency)}
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
                    {loading ? "—" : formatMultiCurrency(kpis?.mrr_by_currency)}
                  </div>
                </div>
                <div className="flex items-center justify-between gap-3">
                  <div className="text-sm text-zinc-600">Primary currency</div>
                  <div className="text-sm font-semibold text-[var(--rb-text)]">{primaryCurrency ?? "—"}</div>
                </div>
                <div className="flex items-center justify-between gap-3">
                  <div className="text-sm text-zinc-600">Paid (30d, primary)</div>
                  <div className="text-sm font-semibold text-[var(--rb-text)]">
                    {loading ? "—" : primaryCurrency ? formatMoney({ amountCents: paid30Primary, currency: primaryCurrency }) : "—"}
                  </div>
                </div>
                <div className="flex items-center justify-between gap-3">
                  <div className="text-sm text-zinc-600">Paid (YTD, primary)</div>
                  <div className="text-sm font-semibold text-[var(--rb-text)]">
                    {loading ? "—" : primaryCurrency ? formatMoney({ amountCents: paidYtdPrimary, currency: primaryCurrency }) : "—"}
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </RequireAuth>
  );
}
