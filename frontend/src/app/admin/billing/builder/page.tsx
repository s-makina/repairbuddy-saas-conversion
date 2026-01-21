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
  createDraftBillingPlanVersionFromActive,
  deleteBillingPrice,
  getBillingCatalog,
  syncBillingPlanVersionEntitlements,
  updateBillingPlan,
  updateBillingPrice,
  validateBillingPlanVersionDraft,
} from "@/lib/billing";
import { useAuth } from "@/lib/auth";
import type { BillingPlan, BillingPlanVersion, BillingPrice, EntitlementDefinition, PlanEntitlement } from "@/lib/types";

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

  const [plans, setPlans] = useState<BillingPlan[]>([]);
  const [definitions, setDefinitions] = useState<EntitlementDefinition[]>([]);

  const [selectedPlanId, setSelectedPlanId] = useState<number | null>(null);
  const [selectedVersionId, setSelectedVersionId] = useState<number | null>(null);

  const selectedPlan = useMemo(() => plans.find((p) => p.id === selectedPlanId) ?? null, [plans, selectedPlanId]);

  const selectedVersion = useMemo(() => {
    const versions = Array.isArray(selectedPlan?.versions) ? selectedPlan?.versions : [];
    return versions.find((v) => v.id === selectedVersionId) ?? null;
  }, [selectedPlan, selectedVersionId]);

  const [version, setVersion] = useState<BillingPlanVersion | null>(null);

  const canWrite = auth.can("admin.billing.write");

  const isReadOnly = useMemo(() => {
    if (!version) return true;
    if (version.status !== "draft") return true;
    if (version.locked_at) return true;
    if (version.activated_at || version.retired_at) return true;
    return false;
  }, [version]);

  const [planEditBusy, setPlanEditBusy] = useState(false);
  const [planEditError, setPlanEditError] = useState<string | null>(null);
  const [planName, setPlanName] = useState("");
  const [planCode, setPlanCode] = useState("");
  const [planDescription, setPlanDescription] = useState("");
  const [planActive, setPlanActive] = useState(true);

  const [priceModalOpen, setPriceModalOpen] = useState(false);
  const [priceBusy, setPriceBusy] = useState(false);
  const [priceError, setPriceError] = useState<string | null>(null);
  const [priceEditId, setPriceEditId] = useState<number | null>(null);
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

  const steps = ["Plan", "Version", "Prices", "Entitlements", "Review"];

  useEffect(() => {
    dashboardHeader.setHeader({
      breadcrumb: "Admin / Billing",
      title: "Plan builder",
      subtitle: "Create and manage plans and versions",
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

        const nextPlans = Array.isArray(res.billing_plans) ? res.billing_plans : [];
        const nextDefs = Array.isArray(res.entitlement_definitions) ? res.entitlement_definitions : [];

        setPlans(nextPlans);
        setDefinitions(nextDefs);

        setSelectedPlanId((prev) => {
          if (prev && nextPlans.some((p) => p.id === prev)) return prev;
          return nextPlans[0]?.id ?? null;
        });
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load billing catalog.");
        setPlans([]);
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

  useEffect(() => {
    if (!selectedPlan) return;

    setPlanEditError(null);
    setPlanName(selectedPlan.name ?? "");
    setPlanCode(selectedPlan.code ?? "");
    setPlanDescription(selectedPlan.description ?? "");
    setPlanActive(Boolean(selectedPlan.is_active));
  }, [selectedPlan]);

  useEffect(() => {
    if (!selectedPlan) {
      setSelectedVersionId(null);
      setVersion(null);
      return;
    }

    const versions = (Array.isArray(selectedPlan.versions) ? selectedPlan.versions : []) as BillingPlanVersion[];
    const editableDrafts = versions
      .filter((v) => String(v.status).toLowerCase() === "draft" && !v.locked_at && !v.activated_at && !v.retired_at)
      .slice()
      .sort((a, b) => (b.version ?? 0) - (a.version ?? 0));

    setSelectedVersionId((prev) => {
      if (prev && versions.some((v) => v.id === prev)) return prev;
      return editableDrafts[0]?.id ?? null;
    });
  }, [selectedPlan]);

  useEffect(() => {
    setVersion(selectedVersion);
    setValidateResult(null);
  }, [selectedVersion]);

  const prices = useMemo(() => (Array.isArray(version?.prices) ? (version?.prices as BillingPrice[]) : []), [version]);
  const entitlements = useMemo(
    () => (Array.isArray(version?.entitlements) ? (version?.entitlements as PlanEntitlement[]) : []),
    [version],
  );

  const entitlementsByDefId = useMemo(() => {
    const map: Record<number, PlanEntitlement> = {};
    for (const e of entitlements) {
      map[e.entitlement_definition_id] = e;
    }
    return map;
  }, [entitlements]);

  const allEntitlementDefinitions = useMemo(() => {
    return (Array.isArray(definitions) ? definitions : []).slice().sort((a, b) => (a.id ?? 0) - (b.id ?? 0));
  }, [definitions]);

  useEffect(() => {
    if (!version) return;

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
  }, [allEntitlementDefinitions, entitlementsByDefId, version]);

  const summary = useMemo(() => {
    const enabledEnts: BuilderEntitlement[] = [];
    for (const def of allEntitlementDefinitions) {
      const enabled = Boolean(entitlementEnabled[def.id]);
      if (!enabled) continue;
      enabledEnts.push({ entitlement_definition_id: def.id, value_json: entitlementValue[def.id] ?? null });
    }

    return {
      plan: selectedPlan
        ? {
            id: selectedPlan.id,
            name: selectedPlan.name,
            code: selectedPlan.code,
            description: selectedPlan.description ?? null,
            is_active: Boolean(selectedPlan.is_active),
          }
        : null,
      version: version
        ? {
            id: version.id,
            billing_plan_id: version.billing_plan_id,
            version: version.version,
            status: version.status,
          }
        : null,
      prices: prices.map((p) => ({
        id: p.id,
        currency: String(p.currency).toUpperCase(),
        interval: String(p.interval).toLowerCase(),
        amount_cents: p.amount_cents,
        trial_days: p.trial_days,
        is_default: Boolean(p.is_default),
      })),
      entitlements: enabledEnts,
    };
  }, [allEntitlementDefinitions, entitlementEnabled, entitlementValue, prices, selectedPlan, version]);

  function goNext() {
    setStep((s) => Math.min(s + 1, steps.length - 1));
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

  async function onSavePlan() {
    if (!canWrite) return;
    if (planEditBusy) return;

    const nextName = planName.trim();
    if (!nextName) {
      setPlanEditError("Name is required.");
      return;
    }

    setPlanEditBusy(true);
    setPlanEditError(null);
    setActionError(null);

    try {
      if (selectedPlan) {
        await updateBillingPlan({
          planId: selectedPlan.id,
          name: nextName,
          code: planCode.trim(),
          description: planDescription.trim() || undefined,
          isActive: planActive,
        });
        setReloadNonce((v) => v + 1);
      } else {
        const res = await createBillingPlan({
          name: nextName,
          code: planCode.trim() || undefined,
          description: planDescription.trim() || undefined,
          isActive: planActive,
        });
        setSelectedPlanId(res.plan.id);
        setReloadNonce((v) => v + 1);
      }
    } catch (e) {
      setPlanEditError(e instanceof Error ? e.message : "Failed to save plan.");
    } finally {
      setPlanEditBusy(false);
    }
  }

  async function onCreateDraftVersion() {
    if (!selectedPlan) return;
    if (!canWrite) return;
    setActionError(null);
    try {
      const res = await createDraftBillingPlanVersionFromActive({ planId: selectedPlan.id });
      setSelectedVersionId(res.version.id);
      setVersion(res.version);
      setStep((s) => Math.max(s, 1));
      setReloadNonce((v) => v + 1);
    } catch (e) {
      setActionError(e instanceof Error ? e.message : "Failed to create draft version.");
    }
  }

  async function onSavePrice() {
    if (!version) return;
    if (!canWrite || isReadOnly) return;
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

      if (priceEditId) {
        const res = await updateBillingPrice({
          priceId: priceEditId,
          amountCents,
          trialDays,
          isDefault: priceIsDefault,
        });
        setVersion(res.version);
      } else {
        const res = await createBillingPrice({
          versionId: version.id,
          currency,
          interval: priceInterval,
          amountCents,
          trialDays,
          isDefault: priceIsDefault,
        });
        setVersion(res.version);
      }

      setPriceModalOpen(false);
      setReloadNonce((v) => v + 1);
    } catch (e) {
      setPriceError(e instanceof Error ? e.message : "Failed to save price.");
    } finally {
      setPriceBusy(false);
    }
  }

  async function onDeletePrice(p: BillingPrice) {
    if (!version) return;
    if (!canWrite || isReadOnly) return;
    if (priceBusy) return;
    setPriceBusy(true);
    setActionError(null);
    try {
      const res = await deleteBillingPrice({ priceId: p.id });
      setVersion(res.version);
      setReloadNonce((v) => v + 1);
    } catch (e) {
      setActionError(e instanceof Error ? e.message : "Failed to delete price.");
    } finally {
      setPriceBusy(false);
    }
  }

  function onEditPrice(p: BillingPrice) {
    resetPriceForm();
    setPriceEditId(p.id);
    setPriceCurrency(String(p.currency).toUpperCase());
    setPriceInterval(String(p.interval).toLowerCase() === "year" ? "year" : "month");
    setPriceAmount(((p.amount_cents ?? 0) / 100).toFixed(2));
    setPriceTrialDays(typeof p.trial_days === "number" ? String(p.trial_days) : "");
    setPriceIsDefault(Boolean(p.is_default));
    setPriceModalOpen(true);
  }

  async function onSaveEntitlements() {
    if (!version) return;
    if (!canWrite || isReadOnly) return;
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

      const res = await syncBillingPlanVersionEntitlements({ versionId: version.id, entitlements: payload });
      setVersion(res.version);
      setValidateResult(null);
      setReloadNonce((v) => v + 1);
    } catch (e) {
      setActionError(e instanceof Error ? e.message : "Failed to save entitlements.");
    } finally {
      setEntitlementsBusy(false);
    }
  }

  async function onValidate() {
    if (!version) return;
    setValidateBusy(true);
    setValidateResult(null);
    setActionError(null);
    try {
      const res = await validateBillingPlanVersionDraft({ versionId: version.id });
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
    if (!version) return;
    if (!canWrite || isReadOnly) return;
    if (activateBusy) return;
    setActivateBusy(true);
    setActionError(null);
    try {
      const res = await activateBillingPlanVersion({ versionId: version.id, confirm: activateConfirm.trim() });
      setVersion(res.version);
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
                          onClick={() => setStep(i)}
                          className="flex items-center gap-3 rounded-[var(--rb-radius-sm)] px-2 py-1 text-left"
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
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div className="space-y-1 sm:col-span-2">
                  <label className="text-sm font-medium">Plan</label>
                  <select
                    className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                    value={selectedPlanId ?? ""}
                    onChange={(e) => {
                      const v = e.target.value;
                      const id = v.trim() === "" ? null : Number(v);
                      setSelectedPlanId(Number.isFinite(id ?? NaN) && (id ?? 0) > 0 ? (id as number) : null);
                      setSelectedVersionId(null);
                      setVersion(null);
                      setValidateResult(null);
                      setStep(0);
                    }}
                    disabled={loading || planEditBusy}
                  >
                    <option value="">New plan…</option>
                    {plans.map((p) => (
                      <option key={p.id} value={p.id}>
                        {p.name} ({p.code})
                      </option>
                    ))}
                  </select>
                </div>
                <div className="space-y-1">
                  <label className="text-sm font-medium">&nbsp;</label>
                  <Button
                    variant="secondary"
                    className="w-full"
                    disabled={!canWrite || loading || planEditBusy}
                    onClick={() => void onSavePlan()}
                  >
                    {planEditBusy ? "Saving…" : selectedPlan ? "Save plan" : "Create plan"}
                  </Button>
                </div>
              </div>

              {planEditError ? (
                <Alert variant="danger" title="Cannot save plan">
                  {planEditError}
                </Alert>
              ) : null}

              <div className="space-y-1">
                <label className="text-sm font-medium">Name</label>
                <Input value={planName} onChange={(e) => setPlanName(e.target.value)} disabled={planEditBusy} />
              </div>
              <div className="space-y-1">
                <label className="text-sm font-medium">Code</label>
                <Input value={planCode} onChange={(e) => setPlanCode(e.target.value)} disabled={planEditBusy} />
              </div>
              <div className="space-y-1">
                <label className="text-sm font-medium">Description</label>
                <textarea
                  className="min-h-[90px] w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                  value={planDescription}
                  onChange={(e) => setPlanDescription(e.target.value)}
                  disabled={planEditBusy}
                />
              </div>
              <label className="flex items-center gap-2 text-sm">
                <input type="checkbox" checked={planActive} onChange={(e) => setPlanActive(e.target.checked)} disabled={planEditBusy} />
                Active
              </label>

              {selectedPlan ? (
                <div className="text-xs text-zinc-500">
                  Tip: if you need to manage existing versions, use <Link className="underline" href={`/admin/billing/plans/${selectedPlan.id}`}>Plan details</Link>.
                </div>
              ) : null}
            </CardContent>
          </Card>
        ) : null}

        {step === 1 ? (
          <Card>
            <CardHeader>
              <CardTitle>Version</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              {!selectedPlan ? <Alert variant="warning" title="No plan selected">Select or create a plan first.</Alert> : null}

              {selectedPlan ? (
                <div className="space-y-3">
                  <div className="space-y-1">
                    <label className="text-sm font-medium">Draft version</label>
                    <select
                      className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                      value={selectedVersionId ?? ""}
                      onChange={(e) => {
                        const v = e.target.value;
                        const id = v.trim() === "" ? null : Number(v);
                        setSelectedVersionId(Number.isFinite(id ?? NaN) && (id ?? 0) > 0 ? (id as number) : null);
                        setValidateResult(null);
                      }}
                      disabled={loading}
                    >
                      <option value="">No draft selected</option>
                      {(Array.isArray(selectedPlan.versions) ? selectedPlan.versions : [])
                        .filter((v) => String(v.status).toLowerCase() === "draft")
                        .slice()
                        .sort((a, b) => (b.version ?? 0) - (a.version ?? 0))
                        .map((v) => (
                          <option key={v.id} value={v.id}>
                            v{v.version} (id {v.id})
                          </option>
                        ))}
                    </select>
                  </div>

                  {!version ? <Alert variant="warning" title="No draft version">Create a draft version to continue.</Alert> : null}

                  {version && isReadOnly ? (
                    <Alert variant="warning" title="Read-only">This version is immutable. Create a new draft to make changes.</Alert>
                  ) : null}

                  <div className="flex items-center gap-2">
                    {canWrite ? (
                      <Button variant="secondary" size="sm" onClick={() => void onCreateDraftVersion()} disabled={loading || !selectedPlan}>
                        Create draft from active
                      </Button>
                    ) : null}
                    {selectedPlan && version ? (
                      <Link href={`/admin/billing/plans/${selectedPlan.id}/versions/${version.id}`}>
                        <Button variant="outline" size="sm">Open advanced editor</Button>
                      </Link>
                    ) : null}
                  </div>
                </div>
              ) : null}
            </CardContent>
          </Card>
        ) : null}

        {step === 2 ? (
          <Card>
            <CardHeader className="flex flex-row items-start justify-between gap-4">
              <div className="min-w-0">
                <CardTitle>Prices</CardTitle>
                <div className="mt-1 text-sm text-zinc-600">Configure price points per currency + interval, including defaults.</div>
              </div>
              {canWrite && version && !isReadOnly ? (
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
              {!version ? <Alert variant="warning" title="No draft version">Go back and create/select a draft version first.</Alert> : null}
              <DataTable
                title="Price points"
                data={prices}
                loading={loading}
                emptyMessage="No prices configured."
                getRowId={(p) => p.id}
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
                      canWrite && version && !isReadOnly ? (
                        <div className="flex items-center justify-end gap-2">
                          <Button variant="outline" size="sm" onClick={() => onEditPrice(p)} disabled={priceBusy}>
                            Edit
                          </Button>
                          <Button variant="outline" size="sm" onClick={() => void onDeletePrice(p)} disabled={priceBusy}>
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

        {step === 3 ? (
          <Card>
            <CardHeader>
              <CardTitle>Entitlements</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              {!version ? <Alert variant="warning" title="No draft version">Go back and create/select a draft version first.</Alert> : null}

              {version && isReadOnly ? <Alert variant="warning" title="Read-only">This version is immutable.</Alert> : null}

              <div className="flex items-center justify-between gap-3">
                <div className="text-sm text-zinc-600">Toggle features/limits and configure values per type.</div>
                {canWrite && version && !isReadOnly ? (
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
                          disabled={!canWrite || !version || isReadOnly}
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
                              disabled={!canWrite || !version || isReadOnly}
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
                              disabled={!canWrite || !version || isReadOnly}
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
                              disabled={!canWrite || !version || isReadOnly}
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

        {step === 4 ? (
          <Card>
            <CardHeader className="flex flex-row items-start justify-between gap-4">
              <div className="min-w-0">
                <CardTitle>Review</CardTitle>
                <div className="mt-1 text-sm text-zinc-600">Validate the draft version and activate it when ready.</div>
              </div>
              <div className="flex items-center gap-2">
                {canWrite && version && !isReadOnly ? (
                  <Button variant="outline" size="sm" onClick={() => void onValidate()} disabled={validateBusy}>
                    {validateBusy ? "Validating…" : "Validate"}
                  </Button>
                ) : null}
                {canWrite && version && !isReadOnly ? (
                  <Button variant="secondary" size="sm" onClick={() => setActivateOpen(true)} disabled={activateBusy}>
                    Activate
                  </Button>
                ) : null}
              </div>
            </CardHeader>
            <CardContent className="space-y-4">
              {!version ? <Alert variant="warning" title="No draft version">Go back and create/select a draft version first.</Alert> : null}

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
            <Button variant="primary" onClick={goNext} disabled={step === steps.length - 1}>
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

            {!version ? <Alert variant="warning" title="No draft version">Create/select a draft version first.</Alert> : null}

            <div className="space-y-1">
              <label className="text-sm font-medium">Currency</label>
              <Input value={priceCurrency} onChange={(e) => setPriceCurrency(e.target.value)} disabled={priceBusy || !version || Boolean(priceEditId)} />
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium">Interval</label>
              <select
                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={priceInterval}
                onChange={(e) => setPriceInterval(e.target.value === "year" ? "year" : "month")}
                disabled={priceBusy || !version || Boolean(priceEditId)}
              >
                <option value="month">month</option>
                <option value="year">year</option>
              </select>
              {priceEditId ? <div className="text-xs text-zinc-500">Currency and interval are immutable for an existing price.</div> : null}
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium">Amount</label>
              <Input value={priceAmount} onChange={(e) => setPriceAmount(e.target.value)} disabled={priceBusy || !version} />
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium">Trial days (optional)</label>
              <Input value={priceTrialDays} onChange={(e) => setPriceTrialDays(e.target.value)} disabled={priceBusy || !version} />
            </div>

            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" checked={priceIsDefault} onChange={(e) => setPriceIsDefault(e.target.checked)} disabled={priceBusy || !version} />
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
