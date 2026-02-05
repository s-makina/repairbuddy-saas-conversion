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
import { Input } from "@/components/ui/Input";
import { Modal } from "@/components/ui/Modal";
import { Skeleton } from "@/components/ui/Skeleton";
import { createDraftBillingPlanVersionFromActive, getBillingCatalog, updateBillingPlan } from "@/lib/billing";
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

function formatDate(value?: string | null) {
  if (!value) return "—";
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return "—";
  try {
    return new Intl.DateTimeFormat(undefined, {
      year: "numeric",
      month: "short",
      day: "2-digit",
    }).format(d);
  } catch {
    return d.toISOString().slice(0, 10);
  }
}

function BillingPlanDetailSkeleton() {
  return (
    <div className="space-y-6">
      <Card className="overflow-hidden">
        <div className="h-1.5 w-full bg-[var(--rb-border)]" />
        <CardHeader className="space-y-3">
          <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div className="min-w-0">
              <Skeleton className="h-3 w-24 rounded-[var(--rb-radius-sm)]" />
              <div className="mt-2 flex flex-wrap items-center gap-2">
                <Skeleton className="h-7 w-56 rounded-[var(--rb-radius-sm)]" />
                <Skeleton className="h-5 w-20 rounded-full" />
              </div>
              <Skeleton className="mt-2 h-4 w-80 rounded-[var(--rb-radius-sm)]" />
            </div>

            <div className="sm:text-right">
              <Skeleton className="ml-auto h-3 w-20 rounded-[var(--rb-radius-sm)]" />
              <div className="mt-2 flex items-baseline gap-2 sm:justify-end">
                <Skeleton className="h-8 w-28 rounded-[var(--rb-radius-sm)]" />
                <Skeleton className="h-4 w-12 rounded-[var(--rb-radius-sm)]" />
              </div>
              <Skeleton className="mt-2 ml-auto h-3 w-24 rounded-[var(--rb-radius-sm)]" />
            </div>
          </div>
        </CardHeader>

        <CardContent>
          <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
            {Array.from({ length: 3 }).map((_, idx) => (
              <div
                key={idx}
                className="rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)] bg-[var(--rb-surface-muted)] p-4"
              >
                <Skeleton className="h-3 w-24 rounded-[var(--rb-radius-sm)]" />
                <div className="mt-4 space-y-3">
                  <div>
                    <Skeleton className="h-3 w-16 rounded-[var(--rb-radius-sm)]" />
                    <Skeleton className="mt-2 h-4 w-28 rounded-[var(--rb-radius-sm)]" />
                  </div>
                  <div>
                    <Skeleton className="h-3 w-20 rounded-[var(--rb-radius-sm)]" />
                    <Skeleton className="mt-2 h-4 w-24 rounded-[var(--rb-radius-sm)]" />
                  </div>
                  <div>
                    <Skeleton className="h-3 w-24 rounded-[var(--rb-radius-sm)]" />
                    <Skeleton className="mt-2 h-4 w-20 rounded-[var(--rb-radius-sm)]" />
                  </div>
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      <div className="space-y-3">
        <div className="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
          <div className="min-w-0">
            <Skeleton className="h-5 w-40 rounded-[var(--rb-radius-sm)]" />
            <Skeleton className="mt-2 h-4 w-72 rounded-[var(--rb-radius-sm)]" />
          </div>

          <div className="flex flex-col gap-2 sm:flex-row sm:items-end">
            <Skeleton className="h-9 w-full rounded-[var(--rb-radius-sm)] sm:w-[260px]" />
            <div className="flex flex-wrap items-center gap-2">
              {Array.from({ length: 4 }).map((_, idx) => (
                <Skeleton key={idx} className="h-8 w-20 rounded-[var(--rb-radius-sm)]" />
              ))}
            </div>
          </div>
        </div>

        <div className="rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)] bg-white p-5">
          <div className="space-y-4">
            {Array.from({ length: 3 }).map((_, idx) => (
              <div key={idx} className="flex gap-4">
                <div className="relative flex w-5 flex-col items-center">
                  <div className="mt-1 h-3 w-3 rounded-full bg-[var(--rb-border)]" />
                  {idx < 2 ? <div className="mt-2 w-px flex-1 bg-[var(--rb-border)]" /> : null}
                </div>
                <div className="min-w-0 flex-1 rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)] bg-[var(--rb-surface-muted)] px-4 py-4">
                  <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div className="min-w-0">
                      <div className="flex flex-wrap items-center gap-2">
                        <Skeleton className="h-5 w-12 rounded-[var(--rb-radius-sm)]" />
                        <Skeleton className="h-5 w-16 rounded-full" />
                        <Skeleton className="h-3 w-14 rounded-[var(--rb-radius-sm)]" />
                      </div>
                      <Skeleton className="mt-2 h-3 w-40 rounded-[var(--rb-radius-sm)]" />
                      <div className="mt-3 flex flex-wrap items-center gap-2">
                        <Skeleton className="h-5 w-28 rounded-[var(--rb-radius-sm)]" />
                        <Skeleton className="h-5 w-16 rounded-[var(--rb-radius-sm)]" />
                        <Skeleton className="h-5 w-24 rounded-[var(--rb-radius-sm)]" />
                      </div>
                    </div>
                    <Skeleton className="h-8 w-16 rounded-[var(--rb-radius-sm)]" />
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}

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
  const [versionQuery, setVersionQuery] = useState("");
  const [statusFilter, setStatusFilter] = useState<"all" | "active" | "draft" | "retired" | "other">("all");

  const [editOpen, setEditOpen] = useState(false);
  const [editBusy, setEditBusy] = useState(false);
  const [editError, setEditError] = useState<string | null>(null);
  const [editName, setEditName] = useState("");
  const [editCode, setEditCode] = useState("");
  const [editDescription, setEditDescription] = useState("");
  const [editIsActive, setEditIsActive] = useState(true);

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
              variant="outline"
              size="sm"
              disabled={loading || editBusy || !plan}
              onClick={() => {
                if (!plan) return;
                setEditError(null);
                setEditName(plan.name ?? "");
                setEditCode(plan.code ?? "");
                setEditDescription(plan.description ?? "");
                setEditIsActive(Boolean(plan.is_active));
                setEditOpen(true);
              }}
            >
              Edit plan
            </Button>
          ) : null}
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
  }, [canWrite, dashboardHeader, draftBusy, loading, plan, planId, router]);

  async function onSavePlan() {
    if (!plan) return;

    const nextName = editName.trim();
    const nextCode = editCode.trim();

    if (!nextName) {
      setEditError("Name is required.");
      return;
    }
    if (!nextCode) {
      setEditError("Code is required.");
      return;
    }

    setEditBusy(true);
    setEditError(null);

    try {
      await updateBillingPlan({
        planId: plan.id,
        name: nextName,
        code: nextCode,
        description: editDescription,
        isActive: editIsActive,
      });

      setEditOpen(false);
      setReloadNonce((v) => v + 1);
    } catch (e) {
      setEditError(e instanceof Error ? e.message : "Failed to update plan.");
    } finally {
      setEditBusy(false);
    }
  }

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
        setPlan(null);
        setError(e instanceof Error ? e.message : "Failed to load plan.");
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
  const displayVersion = useMemo(() => (plan ? pickDisplayVersion(plan) : null), [plan]);
  const displayPrice = useMemo(() => pickDisplayPrice(displayVersion), [displayVersion]);
  const currencies = useMemo(() => {
    const allPrices = versions.flatMap((v) => (Array.isArray(v.prices) ? v.prices : []));
    return Array.from(new Set(allPrices.map((x) => x.currency).filter(Boolean)))
      .map((c) => String(c).toUpperCase())
      .sort();
  }, [versions]);

  const versionCounts = useMemo(() => {
    const base = { all: 0, active: 0, draft: 0, retired: 0, other: 0 };
    for (const v of versions) {
      base.all += 1;
      const status = String(v.status || "").toLowerCase();
      if (status === "active") base.active += 1;
      else if (status === "draft") base.draft += 1;
      else if (status === "retired") base.retired += 1;
      else base.other += 1;
    }
    return base;
  }, [versions]);

  const filteredVersions = useMemo(() => {
    const q = versionQuery.trim().toLowerCase();
    const rows = versions
      .slice()
      .sort((a, b) => (b.version ?? 0) - (a.version ?? 0))
      .filter((v) => {
        const status = String(v.status || "").toLowerCase();
        if (statusFilter === "active" && status !== "active") return false;
        if (statusFilter === "draft" && status !== "draft") return false;
        if (statusFilter === "retired" && status !== "retired") return false;
        if (statusFilter === "other" && (status === "active" || status === "draft" || status === "retired")) return false;
        if (!q) return true;

        const prices = Array.isArray(v.prices) ? v.prices : [];
        const priceText = prices
          .map((p) => `${p.currency} ${p.interval} ${p.amount_cents}`)
          .join(" ")
          .toLowerCase();

        return (
          `v${v.version} ${v.status} ${v.id}`.toLowerCase().includes(q) ||
          priceText.includes(q)
        );
      });
    return rows;
  }, [statusFilter, versionQuery, versions]);

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

        {loading && !plan && !error ? <BillingPlanDetailSkeleton /> : null}

        {plan ? (
          <Card className="overflow-hidden">
            <div className={"h-1.5 w-full " + (plan.is_active ? "bg-[var(--rb-blue)]" : "bg-[var(--rb-border)]")} />
            <CardHeader className="space-y-3">
              <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div className="min-w-0">
                  <div className="text-xs font-medium uppercase tracking-wide text-zinc-500">Billing plan</div>
                  <div className="mt-1 flex flex-wrap items-center gap-2">
                    <div className="truncate text-xl font-semibold tracking-tight text-[var(--rb-text)]">{plan.name}</div>
                    <Badge variant={plan.is_active ? "success" : "default"}>{plan.is_active ? "active" : "inactive"}</Badge>
                  </div>
                  <div className="mt-1 text-sm text-zinc-600">{plan.description ?? "—"}</div>
                </div>

                <div className="sm:text-right">
                  <div className="text-xs text-zinc-500">Starting at</div>
                  <div className="mt-1 flex items-baseline gap-2 sm:justify-end">
                    <div className="text-2xl font-semibold tracking-tight text-[var(--rb-text)]">
                      {displayPrice ? formatCents({ currency: displayPrice.currency, amountCents: displayPrice.amount_cents }) : "—"}
                    </div>
                    <div className="text-sm text-zinc-600">{displayPrice ? intervalLabel(displayPrice.interval) : ""}</div>
                  </div>
                  {displayPrice?.trial_days ? (
                    <div className="mt-1 text-xs text-zinc-500">{displayPrice.trial_days} day trial</div>
                  ) : null}
                </div>
              </div>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div className="rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)] bg-[var(--rb-surface-muted)] p-4">
                  <div className="text-xs font-medium uppercase tracking-wide text-zinc-500">Overview</div>
                  <div className="mt-3 space-y-3">
                    <div>
                      <div className="text-xs text-zinc-500">Code</div>
                      <div className="mt-1 text-sm font-medium text-zinc-800">{plan.code}</div>
                    </div>
                    <div>
                      <div className="text-xs text-zinc-500">Plan ID</div>
                      <div className="mt-1 text-sm font-medium text-zinc-800">{plan.id}</div>
                    </div>
                    <div>
                      <div className="text-xs text-zinc-500">Last updated</div>
                      <div className="mt-1 text-sm font-medium text-zinc-800">{formatDate(plan.updated_at)}</div>
                    </div>
                  </div>
                </div>

                <div className="rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)] bg-[var(--rb-surface-muted)] p-4">
                  <div className="text-xs font-medium uppercase tracking-wide text-zinc-500">Current version</div>
                  <div className="mt-3 space-y-2">
                    <div className="text-sm text-zinc-700">
                      {displayVersion ? (
                        <>
                          <span className="font-medium text-zinc-900">v{displayVersion.version}</span>
                          <span className="ml-2">
                            <Badge
                              variant={displayVersion.status === "active" ? "success" : displayVersion.status === "draft" ? "info" : "default"}
                            >
                              {displayVersion.status}
                            </Badge>
                          </span>
                        </>
                      ) : (
                        "—"
                      )}
                    </div>
                    <div className="text-xs text-zinc-500">Total versions: {versions.length}</div>
                    {displayVersion ? (
                      <div className="pt-1">
                        <Link href={`/admin/billing/plans/${planId}/versions/${displayVersion.id}`}>
                          <Button variant="outline" size="sm">
                            View current version
                          </Button>
                        </Link>
                      </div>
                    ) : null}
                  </div>
                </div>

                <div className="rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)] bg-[var(--rb-surface-muted)] p-4">
                  <div className="text-xs font-medium uppercase tracking-wide text-zinc-500">Coverage</div>
                  <div className="mt-3 space-y-3">
                    <div>
                      <div className="text-xs text-zinc-500">Currencies</div>
                      <div className="mt-1 text-sm font-medium text-zinc-800">{currencies.length > 0 ? currencies.join(", ") : "—"}</div>
                    </div>
                    <div>
                      <div className="text-xs text-zinc-500">Default currency</div>
                      <div className="mt-1 text-sm font-medium text-zinc-800">{displayPrice?.currency ? String(displayPrice.currency).toUpperCase() : "—"}</div>
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        ) : null}

        <Modal
          open={editOpen}
          onClose={() => {
            if (!editBusy) setEditOpen(false);
          }}
          title="Edit billing plan"
          footer={
            <div className="flex items-center justify-end gap-2">
              <Button
                variant="outline"
                onClick={() => {
                  if (!editBusy) setEditOpen(false);
                }}
                disabled={editBusy}
              >
                Cancel
              </Button>
              <Button variant="primary" onClick={() => void onSavePlan()} disabled={editBusy}>
                {editBusy ? "Saving…" : "Save"}
              </Button>
            </div>
          }
        >
          <div className="space-y-4">
            {editError ? (
              <Alert variant="danger" title="Cannot save">
                {editError}
              </Alert>
            ) : null}

            <div className="space-y-1">
              <label className="text-sm font-medium">Name</label>
              <Input value={editName} onChange={(e) => setEditName(e.target.value)} disabled={editBusy} />
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium">Code</label>
              <Input value={editCode} onChange={(e) => setEditCode(e.target.value)} disabled={editBusy} />
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium">Description</label>
              <textarea
                className="min-h-[90px] w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={editDescription}
                onChange={(e) => setEditDescription(e.target.value)}
                disabled={editBusy}
              />
            </div>

            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" checked={editIsActive} onChange={(e) => setEditIsActive(e.target.checked)} disabled={editBusy} />
              Active
            </label>
          </div>
        </Modal>

        <div className="space-y-3">
          <div className="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div className="min-w-0">
              <div className="text-sm font-semibold text-[var(--rb-text)]">Version history</div>
              <div className="mt-1 text-sm text-zinc-600">Track changes over time and inspect each version.</div>
            </div>

            <div className="flex flex-col gap-2 sm:flex-row sm:items-end">
              <div className="w-full sm:w-[260px]">
                <Input
                  value={versionQuery}
                  onChange={(e: React.ChangeEvent<HTMLInputElement>) => setVersionQuery(e.target.value)}
                  placeholder="Search versions…"
                />
              </div>
              <div className="flex flex-wrap items-center gap-2">
                <Button
                  variant={statusFilter === "all" ? "secondary" : "outline"}
                  size="sm"
                  onClick={() => setStatusFilter("all")}
                >
                  All ({versionCounts.all})
                </Button>
                <Button
                  variant={statusFilter === "active" ? "secondary" : "outline"}
                  size="sm"
                  onClick={() => setStatusFilter("active")}
                >
                  Active ({versionCounts.active})
                </Button>
                <Button
                  variant={statusFilter === "draft" ? "secondary" : "outline"}
                  size="sm"
                  onClick={() => setStatusFilter("draft")}
                >
                  Draft ({versionCounts.draft})
                </Button>
                <Button
                  variant={statusFilter === "retired" ? "secondary" : "outline"}
                  size="sm"
                  onClick={() => setStatusFilter("retired")}
                >
                  Retired ({versionCounts.retired})
                </Button>
                {(statusFilter !== "all" || versionQuery.trim().length > 0) ? (
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => {
                      setStatusFilter("all");
                      setVersionQuery("");
                    }}
                  >
                    Clear
                  </Button>
                ) : null}
              </div>
            </div>
          </div>

          {loading ? null : null}

          {!loading && filteredVersions.length === 0 ? (
            <div className="text-sm text-zinc-500">No versions found.</div>
          ) : null}

          {!loading && filteredVersions.length > 0 ? (
            <div className="rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)] bg-white p-5">
              <div className="mb-4 flex items-center justify-between gap-3">
                <div className="text-sm text-zinc-600">
                  Showing <span className="font-medium text-zinc-900">{filteredVersions.length}</span> of {versions.length}
                </div>
              </div>
              <div className="space-y-0">
                {filteredVersions.map((v, idx) => {
                  const prices = Array.isArray(v.prices) ? v.prices : [];
                  const ent = Array.isArray(v.entitlements) ? v.entitlements : [];
                  const defaultPrice = pickDisplayPrice(v);
                  const isLast = idx === filteredVersions.length - 1;

                  const status = String(v.status || "").toLowerCase();
                  const nodeColor =
                    status === "active"
                      ? "bg-[var(--rb-blue)]"
                      : status === "draft"
                        ? "bg-[var(--rb-orange)]"
                        : "bg-zinc-400";
                  const ringColor =
                    status === "active"
                      ? "ring-[color:color-mix(in_srgb,var(--rb-blue),white_70%)]"
                      : status === "draft"
                        ? "ring-[color:color-mix(in_srgb,var(--rb-orange),white_70%)]"
                        : "ring-[color:color-mix(in_srgb,var(--rb-border),white_70%)]";

                  return (
                    <div key={v.id} className={"relative flex gap-4 " + (isLast ? "" : "pb-6")}
                    >
                      <div className="relative flex w-5 flex-col items-center">
                        <div className={"mt-1 h-3 w-3 rounded-full ring-4 " + nodeColor + " " + ringColor} />
                        {!isLast ? <div className="mt-2 w-px flex-1 bg-[var(--rb-border)]" /> : null}
                      </div>

                      <div className="min-w-0 flex-1">
                        <div className="rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)] bg-[var(--rb-surface-muted)] px-4 py-4">
                          <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div className="min-w-0">
                              <div className="flex flex-wrap items-center gap-2">
                                <div className="text-base font-semibold text-[var(--rb-text)]">v{v.version}</div>
                                <Badge variant={status === "active" ? "success" : status === "draft" ? "info" : "default"}>
                                  {v.status}
                                </Badge>
                                <div className="text-xs text-zinc-500">ID {v.id}</div>
                              </div>

                              <div className="mt-1 text-xs text-zinc-500">
                                {status === "active" ? (
                                  <>Activated: {formatDate(v.activated_at ?? v.created_at)}</>
                                ) : status === "retired" ? (
                                  <>Retired: {formatDate(v.retired_at ?? v.created_at)}</>
                                ) : (
                                  <>Created: {formatDate(v.created_at)}</>
                                )}
                              </div>

                              <div className="mt-2 flex flex-wrap items-center gap-2">
                                {defaultPrice ? (
                                  <div className="text-sm font-medium text-zinc-900">
                                    {formatCents({ currency: defaultPrice.currency, amountCents: defaultPrice.amount_cents })}
                                    <span className="ml-1 text-sm font-normal text-zinc-600">{intervalLabel(defaultPrice.interval)}</span>
                                  </div>
                                ) : (
                                  <div className="text-sm text-zinc-600">—</div>
                                )}

                                <div className="text-xs text-zinc-500">•</div>
                                <div className="text-sm text-zinc-700">{prices.length} price(s)</div>
                                <div className="text-xs text-zinc-500">•</div>
                                <div className="text-sm text-zinc-700">{ent.length} entitlements</div>
                              </div>
                            </div>

                            <div className="flex items-center justify-between gap-3 sm:flex-col sm:items-end sm:justify-start">
                              <Link href={`/admin/billing/plans/${planId}/versions/${v.id}`}>
                                <Button variant="outline" size="sm">
                                  View
                                </Button>
                              </Link>
                            </div>
                          </div>

                          {prices.length > 0 ? (
                            <div className="mt-3 flex flex-wrap gap-2">
                              {prices.slice(0, 4).map((p) => (
                                <span
                                  key={p.id}
                                  className={
                                    "inline-flex items-center gap-2 rounded-full border px-2.5 py-1 text-xs font-medium " +
                                    (p.is_default
                                      ? "border-[color:color-mix(in_srgb,var(--rb-blue),white_60%)] bg-[color:color-mix(in_srgb,var(--rb-blue),white_92%)] text-[var(--rb-blue)]"
                                      : "border-[var(--rb-border)] bg-white text-[var(--rb-text)]")
                                  }
                                >
                                  <span>{String(p.currency).toUpperCase()}</span>
                                  <span>
                                    {formatCents({ currency: p.currency, amountCents: p.amount_cents })}
                                    {intervalLabel(p.interval)}
                                  </span>
                                  {p.is_default ? <span className="text-[10px] uppercase tracking-wide">default</span> : null}
                                </span>
                              ))}

                              {prices.length > 4 ? (
                                <span className="inline-flex items-center rounded-full border border-[var(--rb-border)] bg-white px-2.5 py-1 text-xs font-medium text-zinc-600">
                                  +{prices.length - 4} more
                                </span>
                              ) : null}
                            </div>
                          ) : null}
                        </div>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          ) : null}
        </div>
      </div>
    </RequireAuth>
  );
}
