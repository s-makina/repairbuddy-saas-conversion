"use client";

import React, { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { RequireAuth } from "@/components/RequireAuth";
import { useDashboardHeader } from "@/components/DashboardShell";
import { Alert } from "@/components/ui/Alert";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/Card";
import { ConfirmDialog } from "@/components/ui/ConfirmDialog";
import { DataTable } from "@/components/ui/DataTable";
import { Input } from "@/components/ui/Input";
import { Modal } from "@/components/ui/Modal";
import {
  activateBillingPlanVersion,
  createBillingPlan,
  createBillingPrice,
  getBillingCatalog,
  syncBillingPlanVersionEntitlements,
  validateBillingPlanVersionDraft,
} from "@/lib/billing";
import { useAuth } from "@/lib/auth";
import type { BillingPlan, BillingPlanVersion, EntitlementDefinition, PlanEntitlement } from "@/lib/types";

type BuilderEntitlement = {
  entitlement_definition_id: number;
  value_json: unknown;
};

function formatCents(cents: number, currency: string) {
  const c = Number.isFinite(cents) ? cents : 0;
  const cur = (currency || "").toUpperCase() || "XXX";
  return `${(c / 100).toFixed(2)} ${cur}`;
}

export default function AdminBillingBuilderPage() {
  const dashboardHeader = useDashboardHeader();
  const auth = useAuth();

  const [step, setStep] = useState<number>(0);

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [actionError, setActionError] = useState<string | null>(null);
  const [reloadNonce, setReloadNonce] = useState(0);

  const [definitions, setDefinitions] = useState<EntitlementDefinition[]>([]);

  type DraftPriceRow = {
    key: string;
    currency: string;
    interval: "month" | "year";
    amount_cents: number;
    trial_days: number | null;
    is_default: boolean;
  };

  const [pricesDraft, setPricesDraft] = useState<DraftPriceRow[]>([]);

  const [createdPlan, setCreatedPlan] = useState<BillingPlan | null>(null);
  const [createdVersion, setCreatedVersion] = useState<BillingPlanVersion | null>(null);
  const [createBusy, setCreateBusy] = useState(false);
  const [createError, setCreateError] = useState<string | null>(null);

  const canWrite = auth.can("admin.billing.write");

  const [planEditError, setPlanEditError] = useState<string | null>(null);
  const [planName, setPlanName] = useState("");
  const [planCode, setPlanCode] = useState("");
  const [planDescription, setPlanDescription] = useState("");
  const [planActive, setPlanActive] = useState(true);

  const [priceModalOpen, setPriceModalOpen] = useState(false);
  const [priceBusy, setPriceBusy] = useState(false);
  const [priceError, setPriceError] = useState<string | null>(null);
  const [priceEditId, setPriceEditId] = useState<string | null>(null);
  const [priceCurrency, setPriceCurrency] = useState("EUR");
  const [priceInterval, setPriceInterval] = useState<"month" | "year">("month");
  const [priceAmount, setPriceAmount] = useState("0.00");
  const [priceTrialDays, setPriceTrialDays] = useState<string>("");
  const [priceIsDefault, setPriceIsDefault] = useState(false);

  const [entitlementsBusy, setEntitlementsBusy] = useState(false);
  const [entitlementEnabled, setEntitlementEnabled] = useState<Record<number, boolean>>({});
  const [entitlementValue, setEntitlementValue] = useState<Record<number, unknown>>({});
  const [entitlementJsonText, setEntitlementJsonText] = useState<Record<number, string>>({});
  const [entitlementJsonError, setEntitlementJsonError] = useState<Record<number, string | null>>({});

  const [validateBusy, setValidateBusy] = useState(false);
  const [validateResult, setValidateResult] = useState<{ ok: boolean; errors: string[] } | null>(null);

  const [activateOpen, setActivateOpen] = useState(false);
  const [activateBusy, setActivateBusy] = useState(false);
  const [activateConfirm, setActivateConfirm] = useState("");

  const steps = ["Details", "Prices", "Entitlements", "Review"];

  useEffect(() => {
    dashboardHeader.setHeader({
      breadcrumb: "Admin / Billing",
      title: "Plan builder",
      subtitle: "Build a plan and create it at the end",
      actions: (
        <div className="flex items-center gap-2">
          <Link href="/admin/billing/plans">
            <Button variant="outline" size="sm">
              Back to plans
            </Button>
          </Link>
          <Button variant="outline" size="sm" onClick={() => setReloadNonce((v) => v + 1)} disabled={loading}>
            Refresh
          </Button>
        </div>
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
        setActionError(null);
        const res = await getBillingCatalog({ includeInactive: true });
        if (!alive) return;

        const nextDefs = Array.isArray(res.entitlement_definitions) ? res.entitlement_definitions : [];
        setDefinitions(nextDefs);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load billing catalog.");
        setDefinitions([]);
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

  const entitlements: PlanEntitlement[] = useMemo(() => [], []);
  const entitlementsByDefId = useMemo(() => {
    const map: Record<number, PlanEntitlement> = {};
    void entitlements;
    return map;
  }, [entitlements]);

  const allEntitlementDefinitions = useMemo(() => {
    return (Array.isArray(definitions) ? definitions : []).slice().sort((a, b) => (a.id ?? 0) - (b.id ?? 0));
  }, [definitions]);

  useEffect(() => {
    const nextEnabled: Record<number, boolean> = {};
    const nextValue: Record<number, unknown> = {};
    const nextJsonText: Record<number, string> = {};
    const nextJsonError: Record<number, string | null> = {};

    for (const def of allEntitlementDefinitions) {
      const row = entitlementsByDefId[def.id] ?? null;
      nextEnabled[def.id] = Boolean(row);
      nextValue[def.id] = row ? row.value_json : null;

      const type = String(def.value_type ?? "json");
      if (type !== "boolean" && type !== "integer") {
        nextJsonText[def.id] = JSON.stringify(row ? row.value_json ?? null : null, null, 2);
        nextJsonError[def.id] = null;
      }
    }

    setEntitlementEnabled(nextEnabled);
    setEntitlementValue(nextValue);
    setEntitlementJsonText(nextJsonText);
    setEntitlementJsonError(nextJsonError);
  }, [allEntitlementDefinitions, entitlementsByDefId]);

  const summary = useMemo(() => {
    const enabledEnts: BuilderEntitlement[] = [];
    for (const def of allEntitlementDefinitions) {
      const enabled = Boolean(entitlementEnabled[def.id]);
      if (!enabled) continue;
      enabledEnts.push({ entitlement_definition_id: def.id, value_json: entitlementValue[def.id] ?? null });
    }

    return {
      plan: {
        name: planName.trim(),
        code: planCode.trim() || null,
        description: planDescription.trim() || null,
        is_active: Boolean(planActive),
      },
      prices: pricesDraft.map((p) => ({
        currency: String(p.currency).toUpperCase(),
        interval: String(p.interval).toLowerCase(),
        amount_cents: p.amount_cents,
        trial_days: p.trial_days,
        is_default: Boolean(p.is_default),
      })),
      entitlements: enabledEnts,
    };
  }, [allEntitlementDefinitions, entitlementEnabled, entitlementValue, planActive, planCode, planDescription, planName, pricesDraft]);

  const pricesValidation = useMemo(() => {
    const normalized = (Array.isArray(pricesDraft) ? pricesDraft : []).map((p) => ({
      ...p,
      currency: String(p.currency).trim().toUpperCase(),
      interval: p.interval === "year" ? "year" : "month",
      amount_cents: Number.isFinite(p.amount_cents) ? Math.max(0, Math.trunc(p.amount_cents)) : 0,
    }));

    if (normalized.length === 0) {
      return { ok: false, message: "Add at least one price." };
    }

    const pairCounts: Record<string, { total: number; defaults: number }> = {};
    for (const p of normalized) {
      const key = `${p.currency}|${p.interval}`;
      if (!pairCounts[key]) pairCounts[key] = { total: 0, defaults: 0 };
      pairCounts[key].total += 1;
      if (p.is_default) pairCounts[key].defaults += 1;
    }

    for (const [pair, info] of Object.entries(pairCounts)) {
      if (info.defaults === 0) return { ok: false, message: `Set a default price for ${pair}.` };
      if (info.defaults > 1) return { ok: false, message: `Only one default price allowed for ${pair}.` };
    }

    return { ok: true, message: null as string | null };
  }, [pricesDraft]);

  const maxStepAllowed = useMemo(() => {
    if (planName.trim().length === 0) return 0;
    if (!pricesValidation.ok) return 1;
    return steps.length - 1;
  }, [planName, pricesValidation.ok, steps.length]);

  function goNext() {
    setStep((s) => Math.min(s + 1, maxStepAllowed));
  }

  function goBack() {
    setStep((s) => Math.max(s - 1, 0));
  }

  function resetPriceForm() {
    setPriceError(null);
    setPriceEditId(null);
    setPriceCurrency("EUR");
    setPriceInterval("month");
    setPriceAmount("0.00");
    setPriceTrialDays("");
    setPriceIsDefault(false);
  }

  function parseAmountCents(input: string): number {
    const normalized = input.trim().replace(",", ".");
    const n = Number.parseFloat(normalized);
    if (!Number.isFinite(n) || n < 0) return 0;
    return Math.round(n * 100);
  }

  function normalizeDraftPriceRow(p: DraftPriceRow): DraftPriceRow {
    return {
      ...p,
      currency: String(p.currency).trim().toUpperCase(),
      interval: p.interval === "year" ? "year" : "month",
      amount_cents: Number.isFinite(p.amount_cents) ? Math.max(0, Math.trunc(p.amount_cents)) : 0,
      trial_days: typeof p.trial_days === "number" && Number.isFinite(p.trial_days) ? Math.max(0, Math.trunc(p.trial_days)) : null,
      is_default: Boolean(p.is_default),
    };
  }

  function enforceSingleDefault(next: DraftPriceRow[]) {
    const out = next.slice();
    const counts: Record<string, number> = {};
    for (const p of out) {
      if (!p.is_default) continue;
      const key = `${p.currency}|${p.interval}`;
      counts[key] = (counts[key] ?? 0) + 1;
      if (counts[key] > 1) {
        p.is_default = false;
      }
    }
    return out;
  }

  async function onCreatePlanAtEnd() {
    if (!canWrite) return;
    if (createBusy) return;

    const nextName = planName.trim();
    if (!nextName) {
      setPlanEditError("Name is required.");
      setStep(0);
      return;
    }

    if (!pricesValidation.ok) {
      setActionError(pricesValidation.message ?? "Invalid prices.");
      setStep(1);
      return;
    }

    setCreateBusy(true);
    setCreateError(null);
    setActionError(null);

    try {
      const res = await createBillingPlan({
        name: nextName,
        code: planCode.trim() || undefined,
        description: planDescription.trim() || undefined,
        isActive: planActive,
      });

      const plan = res.plan;
      const drafts = (Array.isArray(plan.versions) ? plan.versions : [])
        .filter((v) => String(v.status).toLowerCase() === "draft")
        .slice()
        .sort((a, b) => (b.version ?? 0) - (a.version ?? 0));
      const draft = (drafts[0] as BillingPlanVersion | undefined) ?? null;
      if (!draft) {
        throw new Error("Plan created but no draft version was returned.");
      }

      setCreatedPlan(plan);
      setCreatedVersion(draft);

      let currentVersion: BillingPlanVersion = draft;

      const normalizedPrices = enforceSingleDefault(pricesDraft.map(normalizeDraftPriceRow));

      for (const p of normalizedPrices) {
        const priceRes = await createBillingPrice({
          versionId: currentVersion.id,
          currency: p.currency,
          interval: p.interval,
          amountCents: p.amount_cents,
          trialDays: p.trial_days,
          isDefault: p.is_default,
        });
        currentVersion = priceRes.version;
        setCreatedVersion(priceRes.version);
      }

      const payload: Array<{ entitlement_definition_id: number; value_json: unknown }> = [];
      for (const def of allEntitlementDefinitions) {
        const enabled = Boolean(entitlementEnabled[def.id]);
        if (!enabled) continue;

        const type = String(def.value_type ?? "json");
        if (type !== "boolean" && type !== "integer") {
          const raw = entitlementJsonText[def.id] ?? "";
          const parsed = raw.trim() === "" ? null : JSON.parse(raw);
          payload.push({ entitlement_definition_id: def.id, value_json: parsed });
          continue;
        }

        payload.push({ entitlement_definition_id: def.id, value_json: entitlementValue[def.id] ?? null });
      }

      const entRes = await syncBillingPlanVersionEntitlements({ versionId: currentVersion.id, entitlements: payload });
      setCreatedVersion(entRes.version);
      setValidateResult(null);
      setReloadNonce((v) => v + 1);
    } catch (e) {
      setCreateError(e instanceof Error ? e.message : "Failed to create plan.");
    } finally {
      setCreateBusy(false);
    }
  }

  async function onSavePrice() {
    if (!canWrite) return;
    if (priceBusy) return;

    setPriceBusy(true);
    setPriceError(null);
    setActionError(null);

    try {
      const currency = priceCurrency.trim().toUpperCase();
      if (currency.length !== 3) {
        setPriceError("Currency must be 3 letters (e.g. EUR).");
        return;
      }

      const amountCents = parseAmountCents(priceAmount);
      const trialDaysRaw = priceTrialDays.trim() === "" ? null : Number(priceTrialDays);
      const trialDays = typeof trialDaysRaw === "number" && Number.isFinite(trialDaysRaw) ? Math.max(0, Math.trunc(trialDaysRaw)) : null;

      const base: DraftPriceRow = {
        key: priceEditId ?? `${Date.now()}_${Math.random().toString(16).slice(2)}`,
        currency,
        interval: priceInterval,
        amount_cents: amountCents,
        trial_days: trialDays,
        is_default: priceIsDefault,
      };

      setPricesDraft((prev) => {
        const normalized = normalizeDraftPriceRow(base);
        const next = prev.some((p) => p.key === normalized.key)
          ? prev.map((p) => (p.key === normalized.key ? normalized : p))
          : [...prev, normalized];

        if (normalized.is_default) {
          return next.map((p) => {
            if (p.key === normalized.key) return p;
            if (`${p.currency}|${p.interval}` === `${normalized.currency}|${normalized.interval}`) {
              return { ...p, is_default: false };
            }
            return p;
          });
        }

        return next;
      });

      setPriceModalOpen(false);
    } catch (e) {
      setPriceError(e instanceof Error ? e.message : "Failed to save price.");
    } finally {
      setPriceBusy(false);
    }
  }

  function onDeletePrice(key: string) {
    setPricesDraft((prev) => prev.filter((p) => p.key !== key));
  }

  function onEditPrice(p: DraftPriceRow) {
    resetPriceForm();
    setPriceEditId(p.key);
    setPriceCurrency(String(p.currency).toUpperCase());
    setPriceInterval(p.interval === "year" ? "year" : "month");
    setPriceAmount(((p.amount_cents ?? 0) / 100).toFixed(2));
    setPriceTrialDays(typeof p.trial_days === "number" ? String(p.trial_days) : "");
    setPriceIsDefault(Boolean(p.is_default));
    setPriceModalOpen(true);
  }

  async function onSaveEntitlements() {
    if (!canWrite) return;
    if (entitlementsBusy) return;

    setEntitlementsBusy(true);
    setActionError(null);

    try {
      const payload: Array<{ entitlement_definition_id: number; value_json: unknown }> = [];

      for (const def of allEntitlementDefinitions) {
        const enabled = Boolean(entitlementEnabled[def.id]);
        if (!enabled) continue;

        const type = String(def.value_type ?? "json");
        if (type !== "boolean" && type !== "integer") {
          const raw = entitlementJsonText[def.id] ?? "";
          try {
            const parsed = raw.trim() === "" ? null : JSON.parse(raw);
            payload.push({ entitlement_definition_id: def.id, value_json: parsed });
            setEntitlementJsonError((prev) => ({ ...prev, [def.id]: null }));
          } catch {
            setEntitlementJsonError((prev) => ({ ...prev, [def.id]: "Invalid JSON." }));
            return;
          }
          continue;
        }

        payload.push({ entitlement_definition_id: def.id, value_json: entitlementValue[def.id] ?? null });
      }

      void payload;
      setValidateResult(null);
    } catch (e) {
      setActionError(e instanceof Error ? e.message : "Failed to save entitlements.");
    } finally {
      setEntitlementsBusy(false);
    }
  }

  async function onValidate() {
    if (!createdVersion) return;
    setValidateBusy(true);
    setValidateResult(null);
    setActionError(null);
    try {
      const res = await validateBillingPlanVersionDraft({ versionId: createdVersion.id });
      const ok = "status" in res && res.status === "ok";
      const errors = "errors" in res && Array.isArray(res.errors) ? res.errors.map(String) : [];
      setValidateResult({ ok, errors });
    } catch (e) {
      setActionError(e instanceof Error ? e.message : "Failed to validate.");
    } finally {
      setValidateBusy(false);
    }
  }

  async function onActivate() {
    if (!createdVersion) return;
    if (!canWrite) return;
    if (activateBusy) return;
    setActivateBusy(true);
    setActionError(null);
    try {
      const res = await activateBillingPlanVersion({ versionId: createdVersion.id, confirm: activateConfirm.trim() });
      setCreatedVersion(res.version);
      setActivateOpen(false);
      setActivateConfirm("");
      setReloadNonce((v) => v + 1);
      setValidateResult(null);
    } catch (e) {
      setActionError(e instanceof Error ? e.message : "Failed to activate.");
    } finally {
      setActivateBusy(false);
    }
  }

  return (
    <RequireAuth requiredPermission="admin.billing.read">
      <div className="space-y-6">
        {error ? (
          <Alert variant="danger" title="Could not load billing catalog">
            {error}
          </Alert>
        ) : null}

        {actionError ? (
          <Alert variant="danger" title="Action failed">
            {actionError}
          </Alert>
        ) : null}

        <Card>
          <CardHeader>
            <CardTitle>Builder steps</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              <div className="flex items-center justify-between gap-3">
                <div className="text-sm text-zinc-600">
                  Step {step + 1} of {steps.length}
                </div>
                <Badge variant={step === steps.length - 1 ? "info" : "default"}>{steps[step]}</Badge>
              </div>

              <div className="overflow-x-auto">
                <div className="flex min-w-[780px] items-center">
                  {steps.map((label, i) => {
                    const isActive = i === step;
                    const isDone = i < step;
                    const isLocked = i > maxStepAllowed;

                    const circleClass = isActive
                      ? "border-[color:color-mix(in_srgb,var(--rb-blue),white_40%)] bg-[color:color-mix(in_srgb,var(--rb-blue),white_88%)] text-[var(--rb-blue)]"
                      : isDone
                        ? "border-[color:color-mix(in_srgb,#16a34a,white_40%)] bg-[color:color-mix(in_srgb,#16a34a,white_88%)] text-[#166534]"
                        : "border-[var(--rb-border)] bg-white text-zinc-600";

                    const labelClass = isActive ? "text-zinc-900" : isDone ? "text-zinc-800" : "text-zinc-500";

                    const connectorClass = i < step ? "bg-[#16a34a]" : "bg-[var(--rb-border)]";

                    return (
                      <React.Fragment key={label}>
                        <button
                          type="button"
                          onClick={() => {
                            if (!isLocked) setStep(i);
                          }}
                          disabled={isLocked}
                          className="flex items-center gap-3 rounded-[var(--rb-radius-sm)] px-2 py-1 text-left disabled:cursor-not-allowed disabled:opacity-60"
                        >
                          <div className={`flex h-8 w-8 items-center justify-center rounded-full border text-sm font-semibold ${circleClass}`}>
                            {i + 1}
                          </div>
                          <div className={`text-sm font-medium ${labelClass}`}>{label}</div>
                        </button>

                        {i < steps.length - 1 ? (
                          <div className="flex-1 px-2">
                            <div className={`h-[2px] w-full ${connectorClass}`} />
                          </div>
                        ) : null}
                      </React.Fragment>
                    );
                  })}
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        {step === 0 ? (
          <Card>
            <CardHeader>
              <CardTitle>Plan details</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-[var(--rb-surface-muted)] p-3 text-sm text-zinc-700">
                Configure the plan here and click Next. The plan will be created on the Review step.
              </div>

              {planEditError ? (
                <Alert variant="danger" title="Cannot save plan">
                  {planEditError}
                </Alert>
              ) : null}

              <div className="space-y-1">
                <label className="text-sm font-medium">Name</label>
                <Input value={planName} onChange={(e) => setPlanName(e.target.value)} disabled={loading || createBusy} />
              </div>
              <div className="space-y-1">
                <label className="text-sm font-medium">Code</label>
                <Input value={planCode} onChange={(e) => setPlanCode(e.target.value)} disabled={loading || createBusy} />
                <div className="text-xs text-zinc-500">Leave blank to auto-generate from name.</div>
              </div>
              <div className="space-y-1">
                <label className="text-sm font-medium">Description</label>
                <textarea
                  className="min-h-[90px] w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                  value={planDescription}
                  onChange={(e) => setPlanDescription(e.target.value)}
                  disabled={loading || createBusy}
                />
              </div>
              <label className="flex items-center gap-2 text-sm">
                <input type="checkbox" checked={planActive} onChange={(e) => setPlanActive(e.target.checked)} disabled={loading || createBusy} />
                Active
              </label>

              <div className="text-xs text-zinc-500">
                Need to edit existing plans? Use <Link className="underline" href="/admin/billing/plans">Billing plans</Link>.
              </div>
            </CardContent>
          </Card>
        ) : null}

        {step === 1 ? (
          <Card>
            <CardHeader className="flex flex-row items-start justify-between gap-4">
              <div className="min-w-0">
                <CardTitle>Prices</CardTitle>
                <div className="mt-1 text-sm text-zinc-600">Configure price points per currency + interval, including defaults.</div>
              </div>
              {canWrite ? (
                <Button
                  variant="secondary"
                  size="sm"
                  onClick={() => {
                    resetPriceForm();
                    setPriceModalOpen(true);
                  }}
                  disabled={loading || priceBusy}
                >
                  Add price
                </Button>
              ) : null}
            </CardHeader>
            <CardContent>
              {pricesValidation.message ? <Alert variant="warning" title="Prices">{pricesValidation.message}</Alert> : null}
              <DataTable
                title="Price points"
                data={pricesDraft}
                loading={loading}
                emptyMessage="No prices configured."
                getRowId={(p) => p.key}
                columns={[
                  {
                    id: "pair",
                    header: "Currency / interval",
                    cell: (p) => (
                      <div className="text-sm text-zinc-800">
                        {String(p.currency).toUpperCase()} / {String(p.interval).toLowerCase()}
                      </div>
                    ),
                    className: "whitespace-nowrap",
                  },
                  {
                    id: "amount",
                    header: "Amount",
                    cell: (p) => <div className="text-sm text-zinc-700">{formatCents(p.amount_cents ?? 0, p.currency)}</div>,
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
                  {
                    id: "actions",
                    header: "",
                    cell: (p) =>
                      canWrite ? (
                        <div className="flex items-center justify-end gap-2">
                          <Button variant="outline" size="sm" onClick={() => onEditPrice(p)} disabled={priceBusy}>
                            Edit
                          </Button>
                          <Button variant="outline" size="sm" onClick={() => onDeletePrice(p.key)} disabled={priceBusy}>
                            Delete
                          </Button>
                        </div>
                      ) : null,
                    className: "whitespace-nowrap",
                  },
                ]}
              />
            </CardContent>
          </Card>
        ) : null}

        {step === 2 ? (
          <Card>
            <CardHeader>
              <CardTitle>Entitlements</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              <div className="flex items-center justify-between gap-3">
                <div className="text-sm text-zinc-600">Toggle features/limits and configure values per type.</div>
                {canWrite ? (
                  <Button variant="secondary" size="sm" onClick={() => void onSaveEntitlements()} disabled={entitlementsBusy}>
                    {entitlementsBusy ? "Saving…" : "Save entitlements"}
                  </Button>
                ) : null}
              </div>

              {allEntitlementDefinitions.length === 0 ? <Alert variant="warning" title="No entitlements">Create entitlement definitions first.</Alert> : null}

              {allEntitlementDefinitions.map((def) => {
                const enabled = Boolean(entitlementEnabled[def.id]);
                const value = entitlementValue[def.id];
                const type = String(def.value_type ?? "json");
                const jsonError = entitlementJsonError[def.id] ?? null;

                return (
                  <div key={def.id} className="rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white p-3">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                      <div className="min-w-0">
                        <div className="text-sm font-semibold text-zinc-900">
                          {def.name} <span className="text-xs font-normal text-zinc-500">({def.code})</span>
                        </div>
                        <div className="mt-1 text-xs text-zinc-500">Type: {type}</div>
                        {def.description ? <div className="mt-1 text-xs text-zinc-500">{def.description}</div> : null}
                        {def.is_premium ? <div className="mt-1"><Badge variant="warning">premium</Badge></div> : null}
                      </div>
                      <label className="flex items-center gap-2 text-sm">
                        <input
                          type="checkbox"
                          checked={enabled}
                          disabled={!canWrite}
                          onChange={(e) => setEntitlementEnabled((prev) => ({ ...prev, [def.id]: e.target.checked }))}
                        />
                        Enabled
                      </label>
                    </div>

                    {enabled ? (
                      <div className="mt-3">
                        {type === "boolean" ? (
                          <label className="flex items-center gap-2 text-sm">
                            <input
                              type="checkbox"
                              checked={Boolean(value)}
                              disabled={!canWrite}
                              onChange={(e) => setEntitlementValue((prev) => ({ ...prev, [def.id]: e.target.checked }))}
                            />
                            Value
                          </label>
                        ) : type === "integer" ? (
                          <div className="space-y-1">
                            <label className="text-sm font-medium">Value</label>
                            <Input
                              type="number"
                              value={value === null || typeof value === "undefined" ? "" : String(value)}
                              disabled={!canWrite}
                              onChange={(e) => {
                                const raw = e.target.value;
                                if (raw.trim() === "") {
                                  setEntitlementValue((prev) => ({ ...prev, [def.id]: null }));
                                  return;
                                }
                                const n = Number(raw);
                                if (!Number.isFinite(n)) return;
                                setEntitlementValue((prev) => ({ ...prev, [def.id]: Math.trunc(n) }));
                              }}
                            />
                          </div>
                        ) : (
                          <div className="space-y-1">
                            <label className="text-sm font-medium">Value (JSON)</label>
                            <textarea
                              className="min-h-[110px] w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                              value={entitlementJsonText[def.id] ?? ""}
                              disabled={!canWrite}
                              onChange={(e) => {
                                setEntitlementJsonText((prev) => ({ ...prev, [def.id]: e.target.value }));
                                setEntitlementJsonError((prev) => ({ ...prev, [def.id]: null }));
                              }}
                            />
                            {jsonError ? <div className="text-xs text-red-600">{jsonError}</div> : null}
                          </div>
                        )}
                      </div>
                    ) : null}
                  </div>
                );
              })}
            </CardContent>
          </Card>
        ) : null}

        {step === 3 ? (
          <Card>
            <CardHeader className="flex flex-row items-start justify-between gap-4">
              <div className="min-w-0">
                <CardTitle>Review</CardTitle>
                <div className="mt-1 text-sm text-zinc-600">Create the plan from your configuration, then validate/activate the draft.</div>
              </div>
              <div className="flex items-center gap-2">
                {canWrite ? (
                  <Button variant="secondary" size="sm" onClick={() => void onCreatePlanAtEnd()} disabled={createBusy}>
                    {createBusy ? "Creating…" : "Create plan"}
                  </Button>
                ) : null}
                {canWrite && createdVersion ? (
                  <Button variant="outline" size="sm" onClick={() => void onValidate()} disabled={validateBusy}>
                    {validateBusy ? "Validating…" : "Validate"}
                  </Button>
                ) : null}
                {canWrite && createdVersion ? (
                  <Button variant="secondary" size="sm" onClick={() => setActivateOpen(true)} disabled={activateBusy}>
                    Activate
                  </Button>
                ) : null}
              </div>
            </CardHeader>
            <CardContent className="space-y-4">
              {createError ? <Alert variant="danger" title="Create failed">{createError}</Alert> : null}

              {createdPlan && createdVersion ? (
                <Alert variant="success" title="Plan created">
                  Plan <span className="font-mono">{createdPlan.code}</span> created. Draft version id {createdVersion.id}.
                </Alert>
              ) : (
                <Alert variant="info" title="Not created yet">Click Create plan to persist this configuration.</Alert>
              )}

              {validateResult ? (
                validateResult.ok ? (
                  <Alert variant="success" title="Validation passed">This draft version is ready to activate.</Alert>
                ) : (
                  <Alert variant="warning" title="Validation failed">
                    <div className="space-y-1">
                      {validateResult.errors.map((e, idx) => (
                        <div key={idx}>{e}</div>
                      ))}
                    </div>
                  </Alert>
                )
              ) : (
                <Alert variant="info" title="Not validated">Click Validate to run server-side checks.</Alert>
              )}

              <pre className="max-h-[420px] overflow-auto rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-[var(--rb-surface-muted)] p-3 text-xs text-zinc-700">
                {JSON.stringify(summary, null, 2)}
              </pre>
            </CardContent>
          </Card>
        ) : null}

        <div className="flex items-center justify-between">
          <Button variant="outline" onClick={goBack} disabled={step === 0}>
            Back
          </Button>
          <div className="flex items-center gap-2">
            <div className="text-sm text-zinc-500">
              Step {step + 1} of {steps.length}
            </div>
            <Button variant="primary" onClick={goNext} disabled={step === steps.length - 1 || step >= maxStepAllowed}>
              Next
            </Button>
          </div>
        </div>

        <Modal
          open={priceModalOpen}
          onClose={() => {
            if (!priceBusy) setPriceModalOpen(false);
          }}
          title={priceEditId ? "Edit price" : "Add price"}
          footer={
            <div className="flex items-center justify-end gap-2">
              <Button variant="outline" onClick={() => (!priceBusy ? setPriceModalOpen(false) : null)} disabled={priceBusy}>
                Cancel
              </Button>
              <Button variant="primary" onClick={() => void onSavePrice()} disabled={priceBusy}>
                {priceBusy ? "Saving…" : "Save"}
              </Button>
            </div>
          }
        >
          <div className="space-y-4">
            {priceError ? (
              <Alert variant="danger" title="Cannot save price">
                {priceError}
              </Alert>
            ) : null}

            <div className="space-y-1">
              <label className="text-sm font-medium">Currency</label>
              <Input value={priceCurrency} onChange={(e) => setPriceCurrency(e.target.value)} disabled={priceBusy || Boolean(priceEditId)} />
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium">Interval</label>
              <select
                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={priceInterval}
                onChange={(e) => setPriceInterval(e.target.value === "year" ? "year" : "month")}
                disabled={priceBusy || Boolean(priceEditId)}
              >
                <option value="month">month</option>
                <option value="year">year</option>
              </select>
              {priceEditId ? <div className="text-xs text-zinc-500">Currency and interval are immutable for an existing price.</div> : null}
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium">Amount</label>
              <Input value={priceAmount} onChange={(e) => setPriceAmount(e.target.value)} disabled={priceBusy} />
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium">Trial days (optional)</label>
              <Input value={priceTrialDays} onChange={(e) => setPriceTrialDays(e.target.value)} disabled={priceBusy} />
            </div>

            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" checked={priceIsDefault} onChange={(e) => setPriceIsDefault(e.target.checked)} disabled={priceBusy} />
              Default for this currency + interval
            </label>
          </div>
        </Modal>

        <ConfirmDialog
          open={activateOpen}
          title="Activate"
          message={
            <div className="space-y-3">
              <div>This will activate the selected draft version and automatically retire the current active version (if any).</div>
              <div className="space-y-1">
                <label className="text-sm font-medium">Type ACTIVATE to confirm</label>
                <Input value={activateConfirm} onChange={(e) => setActivateConfirm(e.target.value)} />
              </div>
            </div>
          }
          confirmText="Activate"
          confirmVariant="secondary"
          busy={activateBusy}
          onCancel={() => setActivateOpen(false)}
          onConfirm={() => {
            void onActivate();
          }}
        />
      </div>
    </RequireAuth>
  );
}
