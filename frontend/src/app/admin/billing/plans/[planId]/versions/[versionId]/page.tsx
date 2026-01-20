"use client";

import React, { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { useParams } from "next/navigation";
import { RequireAuth } from "@/components/RequireAuth";
import { useDashboardHeader } from "@/components/DashboardShell";
import { Alert } from "@/components/ui/Alert";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/Card";
import { DataTable } from "@/components/ui/DataTable";
import { getBillingCatalog } from "@/lib/billing";
import { formatMoney } from "@/lib/money";
import type { BillingPlan, BillingPlanVersion, BillingPrice, PlanEntitlement } from "@/lib/types";

export default function AdminBillingPlanVersionDetailPage() {
  const params = useParams<{ planId: string; versionId: string }>();
  const dashboardHeader = useDashboardHeader();

  const planId = Number(params.planId);
  const versionId = Number(params.versionId);

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [plan, setPlan] = useState<BillingPlan | null>(null);
  const [version, setVersion] = useState<BillingPlanVersion | null>(null);
  const [reloadNonce, setReloadNonce] = useState(0);

  useEffect(() => {
    dashboardHeader.setHeader({
      breadcrumb: "Admin / Billing / Plans",
      title: version ? `${plan?.name ?? "Plan"} v${version.version}` : "Plan version",
      subtitle: version ? `Status: ${version.status}` : undefined,
      actions: (
        <div className="flex items-center gap-2">
          <Link href={`/admin/billing/plans/${planId}`}>
            <Button variant="outline" size="sm">
              Back
            </Button>
          </Link>
          <Button variant="outline" size="sm" onClick={() => setReloadNonce((v) => v + 1)} disabled={loading}>
            Refresh
          </Button>
        </div>
      ),
    });

    return () => dashboardHeader.setHeader(null);
  }, [dashboardHeader, loading, plan?.name, planId, version, version?.status, version?.version]);

  useEffect(() => {
    let alive = true;

    async function load() {
      if (!Number.isFinite(planId) || planId <= 0 || !Number.isFinite(versionId) || versionId <= 0) {
        setError("Invalid plan/version id.");
        setLoading(false);
        return;
      }

      try {
        setLoading(true);
        setError(null);

        const res = await getBillingCatalog({ includeInactive: true });
        if (!alive) return;

        const foundPlan = (Array.isArray(res.billing_plans) ? res.billing_plans : []).find((p) => p.id === planId) ?? null;
        const foundVersion = (Array.isArray(foundPlan?.versions) ? foundPlan?.versions : []).find((v) => v.id === versionId) ?? null;

        setPlan(foundPlan);
        setVersion(foundVersion);

        if (!foundPlan) setError("Plan not found.");
        else if (!foundVersion) setError("Version not found.");
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load version.");
        setPlan(null);
        setVersion(null);
      } finally {
        if (!alive) return;
        setLoading(false);
      }
    }

    void load();

    return () => {
      alive = false;
    };
  }, [planId, reloadNonce, versionId]);

  const prices = useMemo(() => (Array.isArray(version?.prices) ? version?.prices : []) as BillingPrice[], [version]);
  const entitlements = useMemo(() => (Array.isArray(version?.entitlements) ? version?.entitlements : []) as PlanEntitlement[], [version]);

  return (
    <RequireAuth requiredPermission="admin.billing.read">
      <div className="space-y-6">
        {error ? (
          <Alert variant="danger" title="Could not load version">
            {error}
          </Alert>
        ) : null}

        {version ? (
          <Card>
            <CardHeader className="flex flex-row items-start justify-between gap-4">
              <div className="min-w-0">
                <CardTitle className="truncate">Version summary</CardTitle>
                <div className="mt-1 text-sm text-zinc-600">Plan: {plan?.name ?? "—"}</div>
              </div>
              <Badge variant={version.status === "active" ? "success" : version.status === "draft" ? "info" : "default"}>{version.status}</Badge>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                  <div className="text-xs text-zinc-500">Version</div>
                  <div className="mt-1 text-sm text-zinc-800">v{version.version}</div>
                </div>
                <div>
                  <div className="text-xs text-zinc-500">Activated</div>
                  <div className="mt-1 text-sm text-zinc-800">{version.activated_at ?? "—"}</div>
                </div>
                <div>
                  <div className="text-xs text-zinc-500">Retired</div>
                  <div className="mt-1 text-sm text-zinc-800">{version.retired_at ?? "—"}</div>
                </div>
              </div>
            </CardContent>
          </Card>
        ) : null}

        <DataTable
          title="Prices"
          data={prices}
          loading={loading}
          emptyMessage="No prices configured for this version."
          getRowId={(p) => p.id}
          columns={[
            {
              id: "currency",
              header: "Currency",
              cell: (p) => <div className="text-sm font-medium text-zinc-800">{String(p.currency).toUpperCase()}</div>,
              className: "whitespace-nowrap",
            },
            {
              id: "interval",
              header: "Interval",
              cell: (p) => <div className="text-sm text-zinc-700">{p.interval}</div>,
              className: "whitespace-nowrap",
            },
            {
              id: "amount",
              header: "Amount",
              cell: (p) => <div className="text-sm text-zinc-700">{formatMoney({ amountCents: p.amount_cents, currency: p.currency })}</div>,
              className: "whitespace-nowrap",
            },
            {
              id: "trial",
              header: "Trial",
              cell: (p) => <div className="text-sm text-zinc-700">{typeof p.trial_days === "number" ? `${p.trial_days} days` : "—"}</div>,
              className: "whitespace-nowrap",
            },
            {
              id: "default",
              header: "Default",
              cell: (p) => (p.is_default ? <Badge variant="success">default</Badge> : <Badge variant="default">—</Badge>),
              className: "whitespace-nowrap",
            },
          ]}
        />

        <DataTable
          title="Entitlements"
          data={entitlements}
          loading={loading}
          emptyMessage="No entitlements configured for this version."
          getRowId={(e) => e.id}
          columns={[
            {
              id: "code",
              header: "Code",
              cell: (e) => <div className="text-sm font-medium text-zinc-800">{e.definition?.code ?? `#${e.entitlement_definition_id}`}</div>,
              className: "whitespace-nowrap",
            },
            {
              id: "name",
              header: "Name",
              cell: (e) => <div className="text-sm text-zinc-700">{e.definition?.name ?? "—"}</div>,
            },
            {
              id: "value",
              header: "Value",
              cell: (e) => (
                <pre className="max-w-[520px] overflow-auto rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-[var(--rb-surface-muted)] p-2 text-xs text-zinc-700">
                  {JSON.stringify(e.value_json ?? null, null, 2)}
                </pre>
              ),
            },
          ]}
        />
      </div>
    </RequireAuth>
  );
}
