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
import { ConfirmDialog } from "@/components/ui/ConfirmDialog";
import { Modal } from "@/components/ui/Modal";
import { Input } from "@/components/ui/Input";
import { formatDateTime } from "@/lib/datetime";
import { getBillingCatalog, getTenantSubscriptions, assignTenantSubscription, cancelTenantSubscription } from "@/lib/billing";
import { formatMoney } from "@/lib/money";
import type { BillingPlan, BillingPlanVersion, BillingPrice, Tenant, TenantSubscription } from "@/lib/types";

export default function AdminTenantBillingOverviewPage() {
  const params = useParams<{ tenant: string }>();
  const tenantId = Number(params.tenant);
  const dashboardHeader = useDashboardHeader();

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const [tenant, setTenant] = useState<Tenant | null>(null);
  const [subscriptions, setSubscriptions] = useState<TenantSubscription[]>([]);

  const [catalogLoading, setCatalogLoading] = useState(true);
  const [catalogError, setCatalogError] = useState<string | null>(null);
  const [plans, setPlans] = useState<BillingPlan[]>([]);

  const [reloadNonce, setReloadNonce] = useState(0);

  const [assignOpen, setAssignOpen] = useState(false);
  const [assignBusy, setAssignBusy] = useState(false);
  const [assignError, setAssignError] = useState<string | null>(null);

  const [selectedPlanId, setSelectedPlanId] = useState<string>("");
  const [selectedVersionId, setSelectedVersionId] = useState<string>("");
  const [selectedPriceId, setSelectedPriceId] = useState<string>("");
  const [reason, setReason] = useState<string>("");

  const [cancelConfirmOpen, setCancelConfirmOpen] = useState(false);
  const [cancelBusy, setCancelBusy] = useState(false);
  const [cancelAtPeriodEnd, setCancelAtPeriodEnd] = useState(true);

  useEffect(() => {
    dashboardHeader.setHeader({
      breadcrumb: "Admin / Tenants",
      title: tenant ? `${tenant.name} — Billing` : `Tenant ${tenantId} — Billing`,
      subtitle: tenant ? `Tenant ID ${tenant.id} • ${tenant.slug}` : undefined,
      actions: (
        <div className="flex items-center gap-2">
          <Link href={`/admin/tenants/${tenantId}`}>
            <Button variant="outline" size="sm">
              Back
            </Button>
          </Link>
          <Button variant="outline" size="sm" onClick={() => setReloadNonce((v) => v + 1)} disabled={loading || catalogLoading}>
            Refresh
          </Button>
        </div>
      ),
    });

    return () => dashboardHeader.setHeader(null);
  }, [catalogLoading, dashboardHeader, loading, tenant, tenantId]);

  useEffect(() => {
    let alive = true;

    async function loadTenantSubs() {
      if (!Number.isFinite(tenantId) || tenantId <= 0) {
        setError("Invalid tenant id.");
        setLoading(false);
        return;
      }

      try {
        setLoading(true);
        setError(null);

        const res = await getTenantSubscriptions(tenantId);
        if (!alive) return;

        setTenant(res.tenant);
        setSubscriptions(Array.isArray(res.subscriptions) ? res.subscriptions : []);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load subscriptions.");
        setTenant(null);
        setSubscriptions([]);
      } finally {
        if (!alive) return;
        setLoading(false);
      }
    }

    void loadTenantSubs();

    return () => {
      alive = false;
    };
  }, [tenantId, reloadNonce]);

  useEffect(() => {
    let alive = true;

    async function loadCatalog() {
      try {
        setCatalogLoading(true);
        setCatalogError(null);

        const res = await getBillingCatalog({ includeInactive: true });
        if (!alive) return;

        setPlans(Array.isArray(res.billing_plans) ? res.billing_plans : []);
      } catch (e) {
        if (!alive) return;
        setCatalogError(e instanceof Error ? e.message : "Failed to load billing catalog.");
        setPlans([]);
      } finally {
        if (!alive) return;
        setCatalogLoading(false);
      }
    }

    void loadCatalog();

    return () => {
      alive = false;
    };
  }, [reloadNonce]);

  const currentSubscription = useMemo(() => {
    const sorted = (Array.isArray(subscriptions) ? subscriptions : []).slice().sort((a, b) => (b.id ?? 0) - (a.id ?? 0));
    return sorted[0] ?? null;
  }, [subscriptions]);

  const tenantCurrency = (tenant?.currency ?? "").toUpperCase();

  const selectedPlan = useMemo(() => {
    const id = Number(selectedPlanId);
    return plans.find((p) => p.id === id) ?? null;
  }, [plans, selectedPlanId]);

  const versionsForSelectedPlan = useMemo(() => {
    return Array.isArray(selectedPlan?.versions) ? selectedPlan?.versions ?? [] : [];
  }, [selectedPlan]);

  const selectedVersion = useMemo(() => {
    const id = Number(selectedVersionId);
    return versionsForSelectedPlan.find((v) => v.id === id) ?? null;
  }, [selectedVersionId, versionsForSelectedPlan]);

  const pricesForSelectedVersion = useMemo(() => {
    const prices = Array.isArray(selectedVersion?.prices) ? selectedVersion?.prices ?? [] : [];
    if (tenantCurrency.length === 3) {
      return prices.filter((p) => String(p.currency).toUpperCase() === tenantCurrency);
    }
    return prices;
  }, [selectedVersion, tenantCurrency]);

  const selectedPrice = useMemo(() => {
    const id = Number(selectedPriceId);
    return pricesForSelectedVersion.find((p) => p.id === id) ?? null;
  }, [pricesForSelectedVersion, selectedPriceId]);

  function resetAssignForm() {
    setAssignError(null);
    setSelectedPlanId("");
    setSelectedVersionId("");
    setSelectedPriceId("");
  }

  async function onOpenAssign() {
    setAssignOpen(true);
    resetAssignForm();
  }

  async function onAssign() {
    if (!Number.isFinite(tenantId) || tenantId <= 0) return;
    if (!selectedVersion || !selectedPrice) {
      setAssignError("Please select a plan version and a price.");
      return;
    }

    setAssignBusy(true);
    setAssignError(null);

    try {
      await assignTenantSubscription({
        tenantId,
        billingPlanVersionId: selectedVersion.id,
        billingPriceId: selectedPrice.id,
        reason: reason.trim() || undefined,
      });

      setAssignOpen(false);
      setReloadNonce((v) => v + 1);
    } catch (e) {
      setAssignError(e instanceof Error ? e.message : "Failed to assign subscription.");
    } finally {
      setAssignBusy(false);
    }
  }

  async function onConfirmCancel() {
    if (!currentSubscription) return;
    if (!Number.isFinite(tenantId) || tenantId <= 0) return;

    setCancelBusy(true);

    try {
      await cancelTenantSubscription({
        tenantId,
        subscriptionId: currentSubscription.id,
        atPeriodEnd: cancelAtPeriodEnd,
        reason: reason.trim() || undefined,
      });

      setCancelConfirmOpen(false);
      setReloadNonce((v) => v + 1);
    } catch (e) {
      setError(e instanceof Error ? e.message : "Failed to cancel subscription.");
    } finally {
      setCancelBusy(false);
    }
  }

  return (
    <RequireAuth requiredPermission="admin.billing.read">
      <div className="space-y-6">
        {error ? (
          <Alert variant="danger" title="Billing could not be loaded">
            {error}
          </Alert>
        ) : null}

        {catalogError ? (
          <Alert variant="danger" title="Billing catalog could not be loaded">
            {catalogError}
          </Alert>
        ) : null}

        <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
          <Card className="lg:col-span-2">
            <CardHeader className="flex flex-row items-start justify-between gap-4">
              <div className="min-w-0">
                <CardTitle className="truncate">Subscription</CardTitle>
                <div className="mt-1 text-sm text-zinc-600">Current subscription and assignment actions</div>
              </div>
              <div className="flex items-center gap-2">
                <Link href={`/admin/tenants/${tenantId}/billing/invoices`}>
                  <Button variant="outline" size="sm">
                    Invoices
                  </Button>
                </Link>
                <Button variant="secondary" size="sm" onClick={onOpenAssign} disabled={catalogLoading || loading}>
                  Assign / Change
                </Button>
              </div>
            </CardHeader>
            <CardContent className="space-y-4">
              {loading ? <div className="text-sm text-zinc-500">Loading subscription…</div> : null}

              {!loading && !currentSubscription ? <div className="text-sm text-zinc-600">No subscription assigned yet.</div> : null}

              {currentSubscription ? (
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <div>
                    <div className="text-xs text-zinc-500">Plan</div>
                    <div className="mt-1 text-sm text-zinc-800">{currentSubscription.plan_version?.plan?.name ?? "—"}</div>
                    <div className="mt-1 text-xs text-zinc-500">Version ID: {currentSubscription.billing_plan_version_id}</div>
                  </div>

                  <div>
                    <div className="text-xs text-zinc-500">Status</div>
                    <div className="mt-1">
                      <Badge variant={currentSubscription.status === "active" ? "success" : "default"}>{currentSubscription.status}</Badge>
                    </div>
                    {currentSubscription.cancel_at_period_end ? (
                      <div className="mt-1 text-xs text-amber-700">Cancel at period end</div>
                    ) : null}
                  </div>

                  <div>
                    <div className="text-xs text-zinc-500">Price</div>
                    <div className="mt-1 text-sm text-zinc-800">
                      {currentSubscription.price
                        ? `${formatMoney({ amountCents: currentSubscription.price.amount_cents, currency: currentSubscription.price.currency })} / ${currentSubscription.price.interval}`
                        : "—"}
                    </div>
                    <div className="mt-1 text-xs text-zinc-500">Currency: {currentSubscription.currency ?? "—"}</div>
                  </div>

                  <div>
                    <div className="text-xs text-zinc-500">Period</div>
                    <div className="mt-1 text-sm text-zinc-800">
                      {formatDateTime(currentSubscription.current_period_start ?? null)} → {formatDateTime(currentSubscription.current_period_end ?? null)}
                    </div>
                  </div>

                  <div className="sm:col-span-2">
                    <div className="flex flex-wrap items-center gap-2">
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => setCancelConfirmOpen(true)}
                        disabled={cancelBusy}
                      >
                        Cancel subscription
                      </Button>
                    </div>
                  </div>
                </div>
              ) : null}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Tenant billing profile</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              <div>
                <div className="text-xs text-zinc-500">Currency</div>
                <div className="mt-1 text-sm text-zinc-800">{tenantCurrency || "—"}</div>
              </div>
              <div>
                <div className="text-xs text-zinc-500">Billing country</div>
                <div className="mt-1 text-sm text-zinc-800">{tenant?.billing_country ?? "—"}</div>
              </div>
              <div>
                <div className="text-xs text-zinc-500">VAT number</div>
                <div className="mt-1 text-sm text-zinc-800">{tenant?.billing_vat_number ?? "—"}</div>
              </div>
            </CardContent>
          </Card>
        </div>

        <Modal
          open={assignOpen}
          onClose={() => {
            if (!assignBusy) setAssignOpen(false);
          }}
          title="Assign / Change subscription"
          footer={
            <div className="flex items-center justify-end gap-2">
              <Button
                variant="outline"
                onClick={() => {
                  if (!assignBusy) setAssignOpen(false);
                }}
                disabled={assignBusy}
              >
                Cancel
              </Button>
              <Button variant="primary" onClick={onAssign} disabled={assignBusy}>
                {assignBusy ? "Saving…" : "Save"}
              </Button>
            </div>
          }
        >
          <div className="space-y-4">
            {assignError ? (
              <Alert variant="danger" title="Cannot assign subscription">
                {assignError}
              </Alert>
            ) : null}

            <div>
              <div className="text-sm font-medium text-[var(--rb-text)]">Tenant currency</div>
              <div className="mt-1 text-sm text-zinc-700">{tenantCurrency || "—"} (prices are filtered to match)</div>
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium">Plan</label>
              <select
                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={selectedPlanId}
                onChange={(e) => {
                  setSelectedPlanId(e.target.value);
                  setSelectedVersionId("");
                  setSelectedPriceId("");
                }}
                disabled={catalogLoading || assignBusy}
              >
                <option value="">— Select plan —</option>
                {plans.map((p) => (
                  <option key={p.id} value={String(p.id)}>
                    {p.name} ({p.code})
                  </option>
                ))}
              </select>
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium">Plan version</label>
              <select
                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={selectedVersionId}
                onChange={(e) => {
                  setSelectedVersionId(e.target.value);
                  setSelectedPriceId("");
                }}
                disabled={!selectedPlan || assignBusy}
              >
                <option value="">— Select version —</option>
                {versionsForSelectedPlan.map((v: BillingPlanVersion) => (
                  <option key={v.id} value={String(v.id)}>
                    v{v.version} ({v.status})
                  </option>
                ))}
              </select>
              {selectedPlan && versionsForSelectedPlan.length === 0 ? (
                <div className="text-xs text-zinc-500">No versions available for this plan.</div>
              ) : null}
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium">Price</label>
              <select
                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={selectedPriceId}
                onChange={(e) => setSelectedPriceId(e.target.value)}
                disabled={!selectedVersion || assignBusy}
              >
                <option value="">— Select price —</option>
                {pricesForSelectedVersion.map((p: BillingPrice) => (
                  <option key={p.id} value={String(p.id)}>
                    {formatMoney({ amountCents: p.amount_cents, currency: p.currency })} / {p.interval}
                    {p.is_default ? " (default)" : ""}
                  </option>
                ))}
              </select>
              {selectedVersion && pricesForSelectedVersion.length === 0 ? (
                <div className="text-xs text-amber-700">
                  No prices found for this version{tenantCurrency ? ` in ${tenantCurrency}` : ""}.
                </div>
              ) : null}
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium">Reason (optional)</label>
              <Input value={reason} onChange={(e) => setReason(e.target.value)} placeholder="Support / plan change reason…" />
            </div>
          </div>
        </Modal>

        <ConfirmDialog
          open={cancelConfirmOpen}
          title="Cancel subscription"
          message={
            <div className="space-y-3">
              <div>This will cancel the current subscription for this tenant.</div>
              <label className="flex items-center gap-2 text-sm">
                <input
                  type="checkbox"
                  checked={cancelAtPeriodEnd}
                  onChange={(e) => setCancelAtPeriodEnd(e.target.checked)}
                  disabled={cancelBusy}
                />
                Cancel at period end
              </label>
            </div>
          }
          confirmText="Cancel subscription"
          confirmVariant="secondary"
          busy={cancelBusy}
          onCancel={() => {
            if (!cancelBusy) setCancelConfirmOpen(false);
          }}
          onConfirm={() => void onConfirmCancel()}
        />
      </div>
    </RequireAuth>
  );
}
