"use client";

import React, { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { RequireAuth } from "@/components/RequireAuth";
import { useDashboardHeader } from "@/components/DashboardShell";
import { Alert } from "@/components/ui/Alert";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/Card";
import { Skeleton } from "@/components/ui/Skeleton";
import { Modal } from "@/components/ui/Modal";
import { Input } from "@/components/ui/Input";
import { createBillingPlan, getBillingCatalog } from "@/lib/billing";
import { useAuth } from "@/lib/auth";
import type { BillingPlan, BillingPlanVersion, BillingPrice } from "@/lib/types";

function formatCents(args: { currency?: string | null; amountCents?: number | null }) {
  const currency = (args.currency || "usd").toUpperCase();
  const amountCents = typeof args.amountCents === "number" ? args.amountCents : 0;
  const amount = amountCents / 100;

  try {
    return new Intl.NumberFormat(undefined, {
      style: "currency",
      currency,
      maximumFractionDigits: 2,
    }).format(amount);
  } catch {
    return `${currency} ${amount.toFixed(2)}`;
  }
}

function intervalLabel(interval?: string | null) {
  const v = String(interval || "").toLowerCase();
  if (!v) return "";
  if (v === "month" || v === "monthly") return "/mo";
  if (v === "year" || v === "yearly" || v === "annual") return "/yr";
  return `/${v}`;
}

function pickDisplayVersion(plan: BillingPlan): BillingPlanVersion | null {
  const versions = Array.isArray(plan.versions) ? plan.versions : [];
  const active = versions.find((v) => v.status === "active") ?? null;
  if (active) return active;
  const latest = versions.slice().sort((a, b) => (b.version ?? 0) - (a.version ?? 0))[0] ?? null;
  return latest;
}

function pickDisplayPrice(version: BillingPlanVersion | null): BillingPrice | null {
  if (!version) return null;
  const prices = Array.isArray(version.prices) ? version.prices : [];
  const preferred = prices.find((p) => p.is_default) ?? null;
  return preferred ?? prices[0] ?? null;
}

function CheckIcon({ className }: { className?: string }) {
  return (
    <svg viewBox="0 0 24 24" className={className} fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M20 6L9 17l-5-5" />
    </svg>
  );
}

function planEntitlementList(plan: BillingPlan): string[] {
  const version = pickDisplayVersion(plan);
  const entitlements = Array.isArray(version?.entitlements) ? version?.entitlements : [];
  const names = entitlements
    .map((e) => (e && typeof e === "object" && "definition" in e ? (e as { definition?: { name?: unknown } }).definition?.name : undefined))
    .filter(Boolean)
    .map((n) => String(n));
  return Array.from(new Set(names));
}

function BillingPlanCardSkeleton() {
  return (
    <Card className="relative overflow-hidden">
      <div className="h-1.5 w-full bg-[var(--rb-border)]" />

      <CardHeader className="pb-3">
        <div className="flex items-start justify-between gap-3">
          <div className="min-w-0">
            <Skeleton className="h-5 w-40 rounded-[var(--rb-radius-sm)]" />
            <div className="mt-2 flex flex-wrap items-center gap-2">
              <Skeleton className="h-3 w-16 rounded-[var(--rb-radius-sm)]" />
              <Skeleton className="h-5 w-24 rounded-full" />
            </div>
          </div>
          <div className="text-right">
            <Skeleton className="ml-auto h-8 w-28 rounded-[var(--rb-radius-sm)]" />
            <div className="mt-2 flex items-center justify-end gap-2">
              <Skeleton className="h-5 w-16 rounded-full" />
            </div>
          </div>
        </div>
      </CardHeader>

      <CardContent className="space-y-4">
        <div className="space-y-2">
          <Skeleton className="h-3 w-full rounded-[var(--rb-radius-sm)]" />
          <Skeleton className="h-3 w-4/5 rounded-[var(--rb-radius-sm)]" />
        </div>

        <div className="space-y-2">
          <Skeleton className="h-3 w-44 rounded-[var(--rb-radius-sm)]" />
          <div className="space-y-2">
            <div className="flex items-center gap-2">
              <Skeleton className="h-4 w-4 rounded-[var(--rb-radius-sm)]" />
              <Skeleton className="h-3 w-2/3 rounded-[var(--rb-radius-sm)]" />
            </div>
            <div className="flex items-center gap-2">
              <Skeleton className="h-4 w-4 rounded-[var(--rb-radius-sm)]" />
              <Skeleton className="h-3 w-1/2 rounded-[var(--rb-radius-sm)]" />
            </div>
            <div className="flex items-center gap-2">
              <Skeleton className="h-4 w-4 rounded-[var(--rb-radius-sm)]" />
              <Skeleton className="h-3 w-3/5 rounded-[var(--rb-radius-sm)]" />
            </div>
          </div>
        </div>

        <div className="grid grid-cols-2 gap-3 border-t border-[var(--rb-border)] pt-4">
          <div>
            <Skeleton className="h-3 w-24 rounded-[var(--rb-radius-sm)]" />
            <div className="mt-2">
              <Skeleton className="h-4 w-28 rounded-[var(--rb-radius-sm)]" />
            </div>
          </div>
          <div>
            <Skeleton className="h-3 w-20 rounded-[var(--rb-radius-sm)]" />
            <div className="mt-2">
              <Skeleton className="h-4 w-24 rounded-[var(--rb-radius-sm)]" />
            </div>
          </div>
        </div>

        <div className="flex items-center justify-end gap-2 pt-1">
          <Skeleton className="h-9 w-24 rounded-[var(--rb-radius-sm)]" />
        </div>
      </CardContent>
    </Card>
  );
}

export default function AdminBillingPlansPage() {
  const auth = useAuth();
  const dashboardHeader = useDashboardHeader();

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [plans, setPlans] = useState<BillingPlan[]>([]);
  const [reloadNonce, setReloadNonce] = useState(0);
  const [query, setQuery] = useState("");

  const [createOpen, setCreateOpen] = useState(false);
  const [createBusy, setCreateBusy] = useState(false);
  const [createError, setCreateError] = useState<string | null>(null);
  const [name, setName] = useState("");
  const [code, setCode] = useState("");
  const [description, setDescription] = useState("");
  const [isActive, setIsActive] = useState(true);

  const canWrite = auth.can("admin.billing.write");

  function resetCreateForm() {
    setCreateError(null);
    setName("");
    setCode("");
    setDescription("");
    setIsActive(true);
  }

  useEffect(() => {
    dashboardHeader.setHeader({
      breadcrumb: "Admin / Billing",
      title: "Billing plans",
      subtitle: "View billing catalog (plans, versions, prices)",
      actions: (
        <div className="flex items-center gap-2">
          {canWrite ? (
            <Link href="/admin/billing/builder">
            <Button variant="outline" size="sm" disabled={loading}>
              New plan
            </Button>
          </Link>
          ) : null}
          <Button
            variant="outline"
            size="sm"
            onClick={() => setReloadNonce((v) => v + 1)}
            disabled={loading}
          >
            Refresh
          </Button>
        </div>
      ),
    });

    return () => dashboardHeader.setHeader(null);
  }, [canWrite, dashboardHeader, loading]);

  async function onCreate() {
    const nextName = name.trim();
    if (!nextName) {
      setCreateError("Name is required.");
      return;
    }

    setCreateBusy(true);
    setCreateError(null);

    try {
      await createBillingPlan({
        name: nextName,
        code,
        description,
        isActive,
      });

      setCreateOpen(false);
      setReloadNonce((v) => v + 1);
    } catch (e) {
      setCreateError(e instanceof Error ? e.message : "Failed to create billing plan.");
    } finally {
      setCreateBusy(false);
    }
  }

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

  const filteredPlans = useMemo(() => {
    const q = query.trim().toLowerCase();
    if (!q) return plans;
    return plans.filter((p) => `${p.name} ${p.code}`.toLowerCase().includes(q));
  }, [plans, query]);

  return (
    <RequireAuth requiredPermission="admin.billing.read">
      <div className="space-y-6">
        {error ? (
          <Alert variant="danger" title="Could not load billing plans">
            {error}
          </Alert>
        ) : null}

        <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
          <div className="min-w-0">
            <div className="text-sm font-semibold text-[var(--rb-text)]">Plans</div>
            <div className="mt-1 text-sm text-zinc-600">Browse plans, pricing and current versions.</div>
          </div>
          <div className="w-full sm:w-[360px]">
            <Input value={query} onChange={(e) => setQuery(e.target.value)} placeholder="Search by name or code…" />
          </div>
        </div>

        {loading ? (
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            {Array.from({ length: 6 }).map((_, idx) => (
              <BillingPlanCardSkeleton key={idx} />
            ))}
          </div>
        ) : null}

        {!loading && filteredPlans.length === 0 ? <div className="text-sm text-zinc-500">No billing plans found.</div> : null}

        {!loading && filteredPlans.length > 0 ? (
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            {filteredPlans.map((p) => {
              const version = pickDisplayVersion(p);
              const price = pickDisplayPrice(version);
              const versions = Array.isArray(p.versions) ? p.versions : [];
              const allPrices = versions.flatMap((v) => (Array.isArray(v.prices) ? v.prices : []));
              const currencies = Array.from(new Set(allPrices.map((x) => x.currency).filter(Boolean)))
                .map((c) => String(c).toUpperCase())
                .sort();
              const isFeatured = String(p.code || "").toLowerCase() === "pro";
              const features = planEntitlementList(p);

              return (
                <Card
                  key={p.id}
                  className={
                    "relative overflow-hidden transition-all hover:-translate-y-0.5 hover:shadow-[var(--rb-shadow)] " +
                    (isFeatured ? "border-[color:color-mix(in_srgb,var(--rb-orange),white_35%)] shadow-[var(--rb-shadow)]" : "")
                  }
                >
                  <div
                    className={
                      "h-1.5 w-full " +
                      (isFeatured ? "bg-[var(--rb-orange)]" : p.is_active ? "bg-[var(--rb-blue)]" : "bg-[var(--rb-border)]")
                    }
                  />

                  <CardHeader className="pb-3">
                    <div className="flex items-start justify-between gap-3">
                      <div className="min-w-0">
                        <CardTitle className="truncate">{p.name}</CardTitle>
                        <div className="mt-1 flex flex-wrap items-center gap-2">
                          <div className="truncate text-xs text-zinc-500">{p.code}</div>
                          {isFeatured ? <Badge variant="warning">Most popular</Badge> : null}
                        </div>
                      </div>
                      <div className="text-right">
                        <div className="flex items-center justify-end gap-2">
                          <div className="text-2xl font-semibold tracking-tight text-[var(--rb-text)]">
                            {price ? formatCents({ currency: price.currency, amountCents: price.amount_cents }) : "—"}
                          </div>
                          <div className="pt-1 text-sm text-zinc-600">{price ? intervalLabel(price.interval) : ""}</div>
                        </div>
                        <div className="mt-1 flex items-center justify-end gap-2">
                          <Badge variant={p.is_active ? "success" : "default"}>{p.is_active ? "active" : "inactive"}</Badge>
                        </div>
                      </div>
                    </div>
                  </CardHeader>

                  <CardContent className="space-y-4">
                    <div className="text-sm text-zinc-700">{p.description ?? "—"}</div>

                    <div className="space-y-2">
                      {price?.trial_days ? <div className="text-xs text-zinc-500">{price.trial_days} day trial included</div> : null}
                      <ul className="space-y-1">
                        {features.length > 0 ? (
                          features.map((f) => (
                            <li key={f} className="flex items-start gap-2 text-sm text-zinc-700">
                              <CheckIcon className="mt-0.5 h-4 w-4 shrink-0 text-[var(--rb-blue)]" />
                              <span className="min-w-0">{f}</span>
                            </li>
                          ))
                        ) : (
                          <li className="text-sm text-zinc-500">No entitlements configured.</li>
                        )}
                      </ul>
                    </div>

                    <div className="grid grid-cols-2 gap-3 border-t border-[var(--rb-border)] pt-4">
                      <div>
                        <div className="text-xs text-zinc-500">Current version</div>
                        <div className="mt-1 text-sm font-medium text-zinc-800">{version ? `v${version.version} (${version.status})` : "—"}</div>
                      </div>
                      <div>
                        <div className="text-xs text-zinc-500">Currencies</div>
                        <div className="mt-1 text-sm font-medium text-zinc-800">{currencies.length > 0 ? currencies.join(", ") : "—"}</div>
                      </div>
                    </div>

                    <div className="flex items-center justify-end gap-2 pt-1">
                      <Link href={`/admin/billing/plans/${p.id}`}>
                        <Button variant={isFeatured ? "secondary" : "outline"} size="sm">
                          View plan
                        </Button>
                      </Link>
                    </div>
                  </CardContent>
                </Card>
              );
            })}
          </div>
        ) : null}

        <div className="text-sm text-zinc-500">
          Entitlements definitions are available under{" "}
          <Link className="underline" href="/admin/billing/entitlements">
            Billing / Entitlements
          </Link>
          .
        </div>

        <Modal
          open={createOpen}
          onClose={() => {
            if (!createBusy) setCreateOpen(false);
          }}
          title="Create billing plan"
          footer={
            <div className="flex items-center justify-end gap-2">
              <Button
                variant="outline"
                onClick={() => {
                  if (!createBusy) setCreateOpen(false);
                }}
                disabled={createBusy}
              >
                Cancel
              </Button>
              <Button variant="primary" onClick={() => void onCreate()} disabled={createBusy}>
                {createBusy ? "Creating…" : "Create"}
              </Button>
            </div>
          }
        >
          <div className="space-y-4">
            {createError ? (
              <Alert variant="danger" title="Cannot create plan">
                {createError}
              </Alert>
            ) : null}

            <div className="space-y-1">
              <label className="text-sm font-medium">Name</label>
              <Input value={name} onChange={(e) => setName(e.target.value)} placeholder="e.g. Starter" />
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium">Code (optional)</label>
              <Input value={code} onChange={(e) => setCode(e.target.value)} placeholder="e.g. starter" />
              <div className="text-xs text-zinc-500">If empty, it will be generated from the name.</div>
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium">Description (optional)</label>
              <textarea
                className="min-h-[90px] w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={description}
                onChange={(e) => setDescription(e.target.value)}
                placeholder="Short description shown to admins…"
              />
            </div>

            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" checked={isActive} onChange={(e) => setIsActive(e.target.checked)} />
              Active
            </label>

            <div className="text-xs text-zinc-500">A draft version v1 will be created automatically.</div>
          </div>
        </Modal>
      </div>
    </RequireAuth>
  );
}
