"use client";

import React, { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { RequireAuth } from "@/components/RequireAuth";
import { useDashboardHeader } from "@/components/DashboardShell";
import { Alert } from "@/components/ui/Alert";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { DataTable } from "@/components/ui/DataTable";
import { getBillingCatalog } from "@/lib/billing";
import type { BillingPlan } from "@/lib/types";

export default function AdminBillingPlansPage() {
  const dashboardHeader = useDashboardHeader();

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [plans, setPlans] = useState<BillingPlan[]>([]);
  const [reloadNonce, setReloadNonce] = useState(0);

  useEffect(() => {
    dashboardHeader.setHeader({
      breadcrumb: "Admin / Billing",
      title: "Billing plans",
      subtitle: "View billing catalog (plans, versions, prices)",
      actions: (
        <Button
          variant="outline"
          size="sm"
          onClick={() => setReloadNonce((v) => v + 1)}
          disabled={loading}
        >
          Refresh
        </Button>
      ),
    });

    return () => dashboardHeader.setHeader(null);
  }, [dashboardHeader, loading]);

  useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);

        const res = await getBillingCatalog({ includeInactive: true });
        if (!alive) return;

        setPlans(Array.isArray(res.billing_plans) ? res.billing_plans : []);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load billing catalog.");
        setPlans([]);
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

  const rows = useMemo(() => plans, [plans]);

  const activeVersionLabel = (p: BillingPlan) => {
    const versions = Array.isArray(p.versions) ? p.versions : [];
    const active = versions.find((v) => v.status === "active");
    const latest = versions.slice().sort((a, b) => (b.version ?? 0) - (a.version ?? 0))[0];
    const v = active ?? latest;
    return v ? `v${v.version} (${v.status})` : "—";
  };

  const currenciesLabel = (p: BillingPlan) => {
    const versions = Array.isArray(p.versions) ? p.versions : [];
    const allPrices = versions.flatMap((v) => (Array.isArray(v.prices) ? v.prices : []));
    const currencies = Array.from(new Set(allPrices.map((x) => x.currency).filter(Boolean)))
      .map((c) => String(c).toUpperCase())
      .sort();
    return currencies.length > 0 ? currencies.join(", ") : "—";
  };

  return (
    <RequireAuth requiredPermission="admin.billing.read">
      <div className="space-y-6">
        {error ? (
          <Alert variant="danger" title="Could not load billing plans">
            {error}
          </Alert>
        ) : null}

        <DataTable
          title="Plans"
          data={rows}
          loading={loading}
          emptyMessage="No billing plans found."
          getRowId={(p) => p.id}
          search={{
            placeholder: "Search by name or code…",
            getSearchText: (p) => `${p.name} ${p.code}`,
          }}
          columns={[
            {
              id: "name",
              header: "Plan",
              cell: (p) => (
                <div className="min-w-0">
                  <div className="truncate text-sm font-medium text-zinc-800">{p.name}</div>
                  <div className="truncate text-xs text-zinc-500">{p.code}</div>
                </div>
              ),
              className: "min-w-[220px]",
            },
            {
              id: "status",
              header: "Status",
              cell: (p) => (
                <Badge variant={p.is_active ? "success" : "default"}>{p.is_active ? "active" : "inactive"}</Badge>
              ),
              className: "whitespace-nowrap",
            },
            {
              id: "current_version",
              header: "Current version",
              cell: (p) => <div className="text-sm text-zinc-700">{activeVersionLabel(p)}</div>,
              className: "whitespace-nowrap",
            },
            {
              id: "currencies",
              header: "Currencies",
              cell: (p) => <div className="text-sm text-zinc-700">{currenciesLabel(p)}</div>,
            },
            {
              id: "actions",
              header: "",
              cell: (p) => (
                <div className="flex items-center justify-end">
                  <Link href={`/admin/billing/plans/${p.id}`}>
                    <Button variant="outline" size="sm">
                      View
                    </Button>
                  </Link>
                </div>
              ),
              className: "whitespace-nowrap",
            },
          ]}
        />

        <div className="text-sm text-zinc-500">
          Entitlements definitions are available under{" "}
          <Link className="underline" href="/admin/billing/entitlements">
            Billing / Entitlements
          </Link>
          .
        </div>
      </div>
    </RequireAuth>
  );
}
