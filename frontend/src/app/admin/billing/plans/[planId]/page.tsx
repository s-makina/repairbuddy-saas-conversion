"use client";

import React, { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { useParams } from "next/navigation";
import { useRouter } from "next/navigation";
import { RequireAuth } from "@/components/RequireAuth";
import { useDashboardHeader } from "@/components/DashboardShell";
import { Alert } from "@/components/ui/Alert";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/Card";
import { DataTable } from "@/components/ui/DataTable";
import { createDraftBillingPlanVersionFromActive, getBillingCatalog } from "@/lib/billing";
import { useAuth } from "@/lib/auth";
import type { BillingPlan, BillingPlanVersion } from "@/lib/types";

export default function AdminBillingPlanDetailPage() {
  const params = useParams<{ planId: string }>();
  const dashboardHeader = useDashboardHeader();
  const router = useRouter();
  const auth = useAuth();

  const planId = Number(params.planId);

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [actionError, setActionError] = useState<string | null>(null);
  const [draftBusy, setDraftBusy] = useState(false);
  const [plan, setPlan] = useState<BillingPlan | null>(null);
  const [reloadNonce, setReloadNonce] = useState(0);

  const canWrite = auth.can("admin.billing.write");

  useEffect(() => {
    dashboardHeader.setHeader({
      breadcrumb: "Admin / Billing / Plans",
      title: plan ? plan.name : Number.isFinite(planId) ? `Plan ${planId}` : "Plan",
      subtitle: plan ? plan.code : undefined,
      actions: (
        <div className="flex items-center gap-2">
          <Link href="/admin/billing/plans">
            <Button variant="outline" size="sm">
              Back
            </Button>
          </Link>
          {canWrite ? (
            <Button
              variant="secondary"
              size="sm"
              disabled={loading || draftBusy || !Number.isFinite(planId) || planId <= 0}
              onClick={async () => {
                if (draftBusy) return;
                try {
                  setDraftBusy(true);
                  setActionError(null);
                  const res = await createDraftBillingPlanVersionFromActive({ planId });
                  router.push(`/admin/billing/plans/${planId}/versions/${res.version.id}`);
                } catch (e) {
                  setActionError(e instanceof Error ? e.message : "Failed to create draft version.");
                } finally {
                  setDraftBusy(false);
                }
              }}
            >
              {draftBusy ? "Creating…" : "Create draft"}
            </Button>
          ) : null}
          <Button variant="outline" size="sm" onClick={() => setReloadNonce((v) => v + 1)} disabled={loading}>
            Refresh
          </Button>
        </div>
      ),
    });

    return () => dashboardHeader.setHeader(null);
  }, [dashboardHeader, loading, plan, planId]);

  useEffect(() => {
    let alive = true;

    async function load() {
      if (!Number.isFinite(planId) || planId <= 0) {
        setError("Invalid plan id.");
        setLoading(false);
        return;
      }

      try {
        setLoading(true);
        setError(null);

        const res = await getBillingCatalog({ includeInactive: true });
        if (!alive) return;

        const found = (Array.isArray(res.billing_plans) ? res.billing_plans : []).find((p) => p.id === planId) ?? null;
        setPlan(found);

        if (!found) {
          setError("Plan not found.");
        }
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load plan.");
        setPlan(null);
      } finally {
        if (!alive) return;
        setLoading(false);
      }
    }

    void load();

    return () => {
      alive = false;
    };
  }, [planId, reloadNonce]);

  const versions = useMemo(() => (Array.isArray(plan?.versions) ? plan?.versions : []) as BillingPlanVersion[], [plan]);

  return (
    <RequireAuth requiredPermission="admin.billing.read">
      <div className="space-y-6">
        {error ? (
          <Alert variant="danger" title="Could not load plan">
            {error}
          </Alert>
        ) : null}

        {actionError ? (
          <Alert variant="danger" title="Action failed">
            {actionError}
          </Alert>
        ) : null}

        {plan ? (
          <Card>
            <CardHeader className="flex flex-row items-start justify-between gap-4">
              <div className="min-w-0">
                <CardTitle className="truncate">Plan details</CardTitle>
                <div className="mt-1 text-sm text-zinc-600">{plan.description ?? "—"}</div>
              </div>
              <Badge variant={plan.is_active ? "success" : "default"}>{plan.is_active ? "active" : "inactive"}</Badge>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                  <div className="text-xs text-zinc-500">Code</div>
                  <div className="mt-1 text-sm text-zinc-800">{plan.code}</div>
                </div>
                <div>
                  <div className="text-xs text-zinc-500">Plan ID</div>
                  <div className="mt-1 text-sm text-zinc-800">{plan.id}</div>
                </div>
              </div>
            </CardContent>
          </Card>
        ) : null}

        <DataTable
          title="Versions"
          data={versions}
          loading={loading}
          emptyMessage="No versions found for this plan."
          getRowId={(v) => v.id}
          columns={[
            {
              id: "version",
              header: "Version",
              cell: (v) => <div className="text-sm font-medium text-zinc-800">v{v.version}</div>,
              className: "whitespace-nowrap",
            },
            {
              id: "status",
              header: "Status",
              cell: (v) => <Badge variant={v.status === "active" ? "success" : v.status === "draft" ? "info" : "default"}>{v.status}</Badge>,
              className: "whitespace-nowrap",
            },
            {
              id: "prices",
              header: "Prices",
              cell: (v) => {
                const prices = Array.isArray(v.prices) ? v.prices : [];
                const currencies = Array.from(new Set(prices.map((p) => p.currency).filter(Boolean)))
                  .map((c) => String(c).toUpperCase())
                  .sort();
                return <div className="text-sm text-zinc-700">{currencies.length > 0 ? currencies.join(", ") : "—"}</div>;
              },
            },
            {
              id: "entitlements",
              header: "Entitlements",
              cell: (v) => {
                const ent = Array.isArray(v.entitlements) ? v.entitlements : [];
                return <div className="text-sm text-zinc-700">{ent.length}</div>;
              },
              className: "whitespace-nowrap",
            },
            {
              id: "actions",
              header: "",
              cell: (v) => (
                <div className="flex items-center justify-end">
                  <Link href={`/admin/billing/plans/${planId}/versions/${v.id}`}>
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
      </div>
    </RequireAuth>
  );
}
