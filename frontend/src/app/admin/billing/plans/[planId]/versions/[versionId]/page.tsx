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
import { DataTable } from "@/components/ui/DataTable";
import { Input } from "@/components/ui/Input";
import { Modal } from "@/components/ui/Modal";
import {
  activateBillingPlanVersion,
  createBillingPrice,
  deleteBillingPrice,
  getBillingCatalog,
  syncBillingPlanVersionEntitlements,
  updateBillingPrice,
  validateBillingPlanVersionDraft,
} from "@/lib/billing";
import { useAuth } from "@/lib/auth";
import { formatDate, formatDateTime } from "@/lib/datetime";
import { formatMoney } from "@/lib/money";
import type { BillingPlan, BillingPlanVersion, BillingPrice, EntitlementDefinition, PlanEntitlement } from "@/lib/types";

export default function AdminBillingPlanVersionDetailPage() {
  const params = useParams<{ planId: string; versionId: string }>();
  const dashboardHeader = useDashboardHeader();
  const auth = useAuth();

  const planId = Number(params.planId);
  const versionId = Number(params.versionId);

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [actionError, setActionError] = useState<string | null>(null);
  const [plan, setPlan] = useState<BillingPlan | null>(null);
  const [version, setVersion] = useState<BillingPlanVersion | null>(null);
  const [definitions, setDefinitions] = useState<EntitlementDefinition[]>([]);
  const [reloadNonce, setReloadNonce] = useState(0);

  const canWrite = auth.can("admin.billing.write");

  const isReadOnly = useMemo(() => {
    if (!version) return true;
    if (version.status !== "draft") return true;
    if (version.locked_at) return true;
    if (version.activated_at || version.retired_at) return true;
    return false;
  }, [version]);

  function intervalLabel(interval?: string | null) {
    const v = String(interval || "").toLowerCase();
    if (!v) return "";
    if (v === "month" || v === "monthly") return "/mo";
    if (v === "year" || v === "yearly" || v === "annual") return "/yr";
    return `/${v}`;
  }

  function statusVariant(status?: string | null) {
    const s = String(status || "").toLowerCase();
    if (s === "active") return "success" as const;
    if (s === "draft") return "info" as const;
    if (s === "retired") return "default" as const;
    return "default" as const;
  }

  useEffect(() => {
    dashboardHeader.setHeader({
      breadcrumb: "Admin / Billing / Plans",
      title: version ? `${plan?.name ?? "Plan"} v${version.version}` : "Plan version",
      subtitle: version
        ? `${String(version.status || "").toUpperCase()}${version.status === "draft" && !isReadOnly ? " • editable" : ""}`
        : undefined,
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
  }, [dashboardHeader, isReadOnly, loading, plan?.name, planId, version, version?.status, version?.version]);

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
        setDefinitions(Array.isArray(res.entitlement_definitions) ? res.entitlement_definitions : []);

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

  function resetPriceForm() {
    setPriceError(null);
    setPriceCurrency("EUR");
    setPriceInterval("month");
    setPriceAmount("0.00");
    setPriceTrialDays("");
    setPriceIsDefault(false);
  }

  function toAmountCents(input: string): number {
    const normalized = input.trim().replace(",", ".");
    const n = Number.parseFloat(normalized);
    if (!Number.isFinite(n)) return 0;
    return Math.max(0, Math.round(n * 100));
  }

  async function onValidate() {
    if (!version) return;
    if (validateBusy) return;
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

  async function onSaveEntitlements() {
    if (!version) return;
    if (entitlementsBusy) return;
    setEntitlementsBusy(true);
    setActionError(null);

    try {
      const payload: Array<{ entitlement_definition_id: number; value_json: unknown }> = [];

      for (const def of allEntitlementDefinitions) {
        const enabled = Boolean(entitlementEnabled[def.id]);
        if (!enabled) continue;
        payload.push({ entitlement_definition_id: def.id, value_json: entitlementValue[def.id] ?? null });
      }

      const res = await syncBillingPlanVersionEntitlements({ versionId: version.id, entitlements: payload });
      setVersion(res.version);
    } catch (e) {
      setActionError(e instanceof Error ? e.message : "Failed to save entitlements.");
    } finally {
      setEntitlementsBusy(false);
    }
  }

  async function onCreatePrice() {
    if (!version) return;
    if (priceBusy) return;
    setPriceBusy(true);
    setPriceError(null);

    try {
      const trialDays = priceTrialDays.trim() === "" ? null : Number(priceTrialDays);
      const res = await createBillingPrice({
        versionId: version.id,
        currency: priceCurrency.trim().toUpperCase(),
        interval: priceInterval,
        amountCents: toAmountCents(priceAmount),
        trialDays: typeof trialDays === "number" && Number.isFinite(trialDays) ? trialDays : null,
        isDefault: priceIsDefault,
      });
      setVersion(res.version);
      setPriceCreateOpen(false);
    } catch (e) {
      setPriceError(e instanceof Error ? e.message : "Failed to create price.");
    } finally {
      setPriceBusy(false);
    }
  }

  async function onUpdatePrice() {
    if (!selectedPrice) return;
    if (priceBusy) return;
    setPriceBusy(true);
    setPriceError(null);
    try {
      const trialDays = priceTrialDays.trim() === "" ? null : Number(priceTrialDays);
      const res = await updateBillingPrice({
        priceId: selectedPrice.id,
        amountCents: toAmountCents(priceAmount),
        trialDays: typeof trialDays === "number" && Number.isFinite(trialDays) ? trialDays : null,
        isDefault: priceIsDefault,
      });
      setVersion(res.version);
      setPriceEditOpen(false);
      setSelectedPrice(null);
    } catch (e) {
      setPriceError(e instanceof Error ? e.message : "Failed to update price.");
    } finally {
      setPriceBusy(false);
    }
  }

  async function onDeletePrice(price: BillingPrice) {
    if (priceBusy) return;
    setPriceBusy(true);
    setActionError(null);
    try {
      const res = await deleteBillingPrice({ priceId: price.id });
      setVersion(res.version);
    } catch (e) {
      setActionError(e instanceof Error ? e.message : "Failed to delete price.");
    } finally {
      setPriceBusy(false);
    }
  }

  async function onActivate() {
    if (!version) return;
    if (activateBusy) return;
    setActivateBusy(true);
    setActionError(null);
    try {
      const res = await activateBillingPlanVersion({ versionId: version.id, confirm: activateConfirm.trim() });
      setVersion(res.version);
      setActivateOpen(false);
      setActivateConfirm("");
    } catch (e) {
      setActionError(e instanceof Error ? e.message : "Failed to activate.");
    } finally {
      setActivateBusy(false);
    }
  }

  const prices = useMemo(() => (Array.isArray(version?.prices) ? version?.prices : []) as BillingPrice[], [version]);
  const entitlements = useMemo(() => (Array.isArray(version?.entitlements) ? version?.entitlements : []) as PlanEntitlement[], [version]);

  const defaultPrice = useMemo(() => {
    const p = prices.find((x) => x.is_default) ?? prices[0] ?? null;
    return p;
  }, [prices]);

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

  const [entitlementEnabled, setEntitlementEnabled] = useState<Record<number, boolean>>({});
  const [entitlementValue, setEntitlementValue] = useState<Record<number, unknown>>({});
  const [entitlementJsonText, setEntitlementJsonText] = useState<Record<number, string>>({});
  const [entitlementJsonError, setEntitlementJsonError] = useState<Record<number, string | null>>({});
  const [entitlementsBusy, setEntitlementsBusy] = useState(false);

  const [validateBusy, setValidateBusy] = useState(false);
  const [validateResult, setValidateResult] = useState<{ ok: boolean; errors: string[] } | null>(null);

  const [activateOpen, setActivateOpen] = useState(false);
  const [activateBusy, setActivateBusy] = useState(false);
  const [activateConfirm, setActivateConfirm] = useState("");

  const [priceCreateOpen, setPriceCreateOpen] = useState(false);
  const [priceEditOpen, setPriceEditOpen] = useState(false);
  const [priceBusy, setPriceBusy] = useState(false);
  const [priceError, setPriceError] = useState<string | null>(null);
  const [selectedPrice, setSelectedPrice] = useState<BillingPrice | null>(null);
  const [priceCurrency, setPriceCurrency] = useState("EUR");
  const [priceInterval, setPriceInterval] = useState<"month" | "year">("month");
  const [priceAmount, setPriceAmount] = useState("0.00");
  const [priceTrialDays, setPriceTrialDays] = useState<string>("");
  const [priceIsDefault, setPriceIsDefault] = useState(false);

  return (
    <RequireAuth requiredPermission="admin.billing.read">
      <div className="space-y-6">
        {error ? (
          <Alert variant="danger" title="Could not load version">
            {error}
          </Alert>
        ) : null}

        {actionError ? (
          <Alert variant="danger" title="Action failed">
            {actionError}
          </Alert>
        ) : null}


        {version && isReadOnly ? (
          <Alert variant="warning" title="Read-only">
            This plan version is immutable.
          </Alert>
        ) : null}

        {!loading && !error && !version ? (
          <Alert variant="warning" title="Version not found">
            The requested plan version does not exist.
          </Alert>
        ) : null}

        <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
          <div className="space-y-6 lg:col-span-2">
            {version ? (
              <Card className="overflow-hidden">
                <div className={"h-1.5 w-full " + (version.status === "active" ? "bg-[var(--rb-blue)]" : version.status === "draft" ? "bg-[var(--rb-orange)]" : "bg-[var(--rb-border)]")} />
                <CardHeader className="space-y-3">
                  <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div className="min-w-0">
                      <div className="text-xs font-medium uppercase tracking-wide text-zinc-500">Plan version</div>
                      <div className="mt-1 flex flex-wrap items-center gap-2">
                        <div className="truncate text-xl font-semibold tracking-tight text-[var(--rb-text)]">
                          {plan?.name ?? "Plan"} <span className="text-zinc-600">v{version.version}</span>
                        </div>
                        <Badge variant={statusVariant(version.status)}>{String(version.status || "").toLowerCase()}</Badge>
                        {isReadOnly ? <Badge variant="default">read-only</Badge> : null}
                      </div>
                      <div className="mt-1 text-sm text-zinc-600">
                        {plan?.code ? <span className="font-medium text-zinc-800">{plan.code}</span> : null}
                        {plan?.code ? <span className="mx-2 text-zinc-400">•</span> : null}
                        <span className="text-zinc-600">Version ID {version.id}</span>
                      </div>
                    </div>

                    <div className="sm:text-right">
                      <div className="text-xs text-zinc-500">Starting at</div>
                      <div className="mt-1 flex items-baseline gap-2 sm:justify-end">
                        <div className="text-2xl font-semibold tracking-tight text-[var(--rb-text)]">
                          {defaultPrice ? formatMoney({ amountCents: defaultPrice.amount_cents, currency: defaultPrice.currency }) : "—"}
                        </div>
                        <div className="text-sm text-zinc-600">{defaultPrice ? intervalLabel(defaultPrice.interval) : ""}</div>
                      </div>
                      {defaultPrice?.trial_days ? <div className="mt-1 text-xs text-zinc-500">{defaultPrice.trial_days} day trial</div> : null}
                    </div>
                  </div>
                </CardHeader>
                <CardContent>
                  <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div className="rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)] bg-[var(--rb-surface-muted)] p-4">
                      <div className="text-xs font-medium uppercase tracking-wide text-zinc-500">Lifecycle</div>
                      <div className="mt-3 space-y-3">
                        <div>
                          <div className="text-xs text-zinc-500">Created</div>
                          <div className="mt-1 text-sm font-medium text-zinc-800">{formatDateTime(version.created_at)}</div>
                        </div>
                        <div>
                          <div className="text-xs text-zinc-500">Updated</div>
                          <div className="mt-1 text-sm font-medium text-zinc-800">{formatDateTime(version.updated_at)}</div>
                        </div>
                      </div>
                    </div>

                    <div className="rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)] bg-[var(--rb-surface-muted)] p-4">
                      <div className="text-xs font-medium uppercase tracking-wide text-zinc-500">Activation</div>
                      <div className="mt-3 space-y-3">
                        <div>
                          <div className="text-xs text-zinc-500">Activated</div>
                          <div className="mt-1 text-sm font-medium text-zinc-800">{formatDate(version.activated_at)}</div>
                        </div>
                        <div>
                          <div className="text-xs text-zinc-500">Retired</div>
                          <div className="mt-1 text-sm font-medium text-zinc-800">{formatDate(version.retired_at)}</div>
                        </div>
                      </div>
                    </div>

                    <div className="rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)] bg-[var(--rb-surface-muted)] p-4">
                      <div className="text-xs font-medium uppercase tracking-wide text-zinc-500">Coverage</div>
                      <div className="mt-3 space-y-3">
                        <div>
                          <div className="text-xs text-zinc-500">Prices</div>
                          <div className="mt-1 text-sm font-medium text-zinc-800">{prices.length}</div>
                        </div>
                        <div>
                          <div className="text-xs text-zinc-500">Entitlements enabled</div>
                          <div className="mt-1 text-sm font-medium text-zinc-800">{entitlements.length}</div>
                        </div>
                      </div>
                    </div>
                  </div>
                </CardContent>
              </Card>
            ) : null}

            <DataTable
              title={
                <div className="flex items-center justify-between gap-3">
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Prices</div>
                  {canWrite && !isReadOnly ? (
                    <Button
                      variant="secondary"
                      size="sm"
                      onClick={() => {
                        resetPriceForm();
                        setPriceCreateOpen(true);
                      }}
                      disabled={loading}
                    >
                      Add price
                    </Button>
                  ) : null}
                </div>
              }
              data={prices}
              loading={loading}
              emptyMessage="No prices configured for this version."
              getRowId={(p) => p.id}
              search={{
                placeholder: "Search prices…",
                getSearchText: (p) => `${p.currency} ${p.interval} ${p.amount_cents} ${p.is_default ? "default" : ""}`,
              }}
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
                {
                  id: "actions",
                  header: "",
                  cell: (p) => (
                    <div className="flex items-center justify-end gap-2">
                      {canWrite && !isReadOnly ? (
                        <>
                          <Button
                            variant="outline"
                            size="sm"
                            onClick={() => {
                              setSelectedPrice(p);
                              setPriceError(null);
                              setPriceAmount(((p.amount_cents ?? 0) / 100).toFixed(2));
                              setPriceTrialDays(typeof p.trial_days === "number" ? String(p.trial_days) : "");
                              setPriceIsDefault(Boolean(p.is_default));
                              setPriceEditOpen(true);
                            }}
                            disabled={priceBusy}
                          >
                            Edit
                          </Button>
                          <Button variant="outline" size="sm" onClick={() => void onDeletePrice(p)} disabled={priceBusy}>
                            Delete
                          </Button>
                        </>
                      ) : null}
                    </div>
                  ),
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
              search={{
                placeholder: "Search entitlements…",
                getSearchText: (e) => `${e.definition?.code ?? ""} ${e.definition?.name ?? ""} ${e.entitlement_definition_id}`,
              }}
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

            {version ? (
              <Card>
                <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                  <div className="min-w-0">
                    <CardTitle className="truncate">Edit entitlements</CardTitle>
                    <div className="mt-1 text-sm text-zinc-600">Configure entitlements for this plan version.</div>
                  </div>
                  {canWrite && !isReadOnly ? (
                    <Button variant="primary" size="sm" onClick={() => void onSaveEntitlements()} disabled={entitlementsBusy}>
                      {entitlementsBusy ? "Saving…" : "Save"}
                    </Button>
                  ) : null}
                </CardHeader>
                <CardContent className="space-y-4">
                  {allEntitlementDefinitions.length === 0 ? (
                    <div className="text-sm text-zinc-600">No entitlement definitions found on this version.</div>
                  ) : (
                    <div className="space-y-3">
                      {allEntitlementDefinitions.map((def) => {
                        const enabled = Boolean(entitlementEnabled[def.id]);
                        const type = String(def.value_type ?? "json");
                        const value = entitlementValue[def.id];
                        const jsonText = entitlementJsonText[def.id] ?? JSON.stringify(value ?? null, null, 2);
                        const jsonError = entitlementJsonError[def.id] ?? null;

                        return (
                          <div key={def.id} className="rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white p-4">
                            <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                              <div className="min-w-0">
                                <div className="text-sm font-semibold text-zinc-900">
                                  {def.name} <span className="text-xs font-normal text-zinc-500">({def.code})</span>
                                </div>
                                <div className="mt-1 text-xs text-zinc-500">Type: {type}</div>
                                {def.description ? <div className="mt-1 text-xs text-zinc-500">{def.description}</div> : null}
                              </div>
                              <label className="flex items-center gap-2 text-sm">
                                <input
                                  type="checkbox"
                                  checked={enabled}
                                  onChange={(e) => {
                                    const next = e.target.checked;
                                    setEntitlementEnabled((prev) => ({ ...prev, [def.id]: next }));
                                    if (!next) {
                                      setEntitlementValue((prev) => ({ ...prev, [def.id]: null }));
                                      setEntitlementJsonError((prev) => ({ ...prev, [def.id]: null }));
                                    }
                                  }}
                                  disabled={isReadOnly || !canWrite}
                                />
                                Enabled
                              </label>
                            </div>

                            {enabled ? (
                              <div className="mt-4">
                                {type === "boolean" ? (
                                  <label className="flex items-center gap-2 text-sm">
                                    <input
                                      type="checkbox"
                                      checked={Boolean(value)}
                                      onChange={(e) => setEntitlementValue((prev) => ({ ...prev, [def.id]: e.target.checked }))}
                                      disabled={isReadOnly || !canWrite}
                                    />
                                    Value
                                  </label>
                                ) : type === "integer" ? (
                                  <div className="space-y-1">
                                    <label className="text-sm font-medium">Value</label>
                                    <Input
                                      type="number"
                                      value={value === null || typeof value === "undefined" ? "" : String(value)}
                                      onChange={(e) => {
                                        const raw = e.target.value;
                                        if (raw.trim() === "") {
                                          setEntitlementValue((prev) => ({ ...prev, [def.id]: null }));
                                          return;
                                        }
                                        const n = Number(raw);
                                        setEntitlementValue((prev) => ({ ...prev, [def.id]: Number.isFinite(n) ? Math.trunc(n) : prev[def.id] }));
                                      }}
                                      disabled={isReadOnly || !canWrite}
                                    />
                                  </div>
                                ) : (
                                  <div className="space-y-2">
                                    <label className="text-sm font-medium">Value (JSON)</label>
                                    <textarea
                                      className={
                                        "min-h-[110px] w-full rounded-[var(--rb-radius-sm)] border bg-white px-3 py-2 font-mono text-xs outline-none transition " +
                                        (jsonError
                                          ? "border-[color:color-mix(in_srgb,#dc2626,white_60%)] focus-visible:ring-2 focus-visible:ring-[color:color-mix(in_srgb,#dc2626,white_65%)]"
                                          : "border-[var(--rb-border)] focus-visible:ring-2 focus-visible:ring-[color:color-mix(in_srgb,var(--rb-orange),white_65%)]")
                                      }
                                      value={jsonText}
                                      onChange={(e) => {
                                        const raw = e.target.value;
                                        setEntitlementJsonText((prev) => ({ ...prev, [def.id]: raw }));
                                        try {
                                          const parsed = JSON.parse(raw);
                                          setEntitlementValue((prev) => ({ ...prev, [def.id]: parsed }));
                                          setEntitlementJsonError((prev) => ({ ...prev, [def.id]: null }));
                                        } catch (err) {
                                          setEntitlementJsonError((prev) => ({
                                            ...prev,
                                            [def.id]: err instanceof Error ? err.message : "Invalid JSON",
                                          }));
                                        }
                                      }}
                                      disabled={isReadOnly || !canWrite}
                                    />
                                    {jsonError ? <div className="text-xs text-red-600">{jsonError}</div> : null}
                                  </div>
                                )}
                              </div>
                            ) : null}
                          </div>
                        );
                      })}
                    </div>
                  )}
                </CardContent>
              </Card>
            ) : null}
          </div>

          <div className="space-y-6 lg:sticky lg:top-6 lg:self-start">
            {version && canWrite && !isReadOnly ? (
              <Card>
                <CardHeader className="space-y-1">
                  <CardTitle className="truncate">Draft checklist</CardTitle>
                  <div className="text-sm text-zinc-600">Validate and activate this draft when you’re ready.</div>
                </CardHeader>
                <CardContent className="space-y-3">
                  <div className="flex flex-col gap-2">
                    <Button variant="outline" size="sm" onClick={() => void onValidate()} disabled={validateBusy}>
                      {validateBusy ? "Validating…" : "Validate draft"}
                    </Button>
                    <Button variant="secondary" size="sm" onClick={() => setActivateOpen(true)} disabled={activateBusy}>
                      Activate
                    </Button>
                  </div>

                  {validateResult ? (
                    validateResult.ok ? (
                      <Alert variant="success" title="Draft is valid">Ready to activate.</Alert>
                    ) : (
                      <Alert variant="danger" title="Draft validation failed">
                        <div className="space-y-1">
                          {validateResult.errors.map((msg, idx) => (
                            <div key={idx}>{msg}</div>
                          ))}
                        </div>
                      </Alert>
                    )
                  ) : null}
                </CardContent>
              </Card>
            ) : null}

            {version ? (
              <Card>
                <CardHeader className="space-y-1">
                  <CardTitle className="truncate">Details</CardTitle>
                  <div className="text-sm text-zinc-600">Reference information for audits and support.</div>
                </CardHeader>
                <CardContent>
                  <div className="space-y-3">
                    <div>
                      <div className="text-xs text-zinc-500">Plan</div>
                      <div className="mt-1 text-sm font-medium text-zinc-800">{plan?.name ?? "—"}</div>
                    </div>
                    <div>
                      <div className="text-xs text-zinc-500">Plan ID</div>
                      <div className="mt-1 text-sm font-medium text-zinc-800">{plan?.id ?? "—"}</div>
                    </div>
                    <div>
                      <div className="text-xs text-zinc-500">Locked</div>
                      <div className="mt-1 text-sm font-medium text-zinc-800">{formatDate(version.locked_at)}</div>
                    </div>
                  </div>
                </CardContent>
              </Card>
            ) : null}
          </div>
        </div>

        <Modal
          open={priceCreateOpen}
          onClose={() => {
            if (!priceBusy) setPriceCreateOpen(false);
          }}
          title="Add price"
          footer={
            <div className="flex items-center justify-end gap-2">
              <Button variant="outline" onClick={() => (!priceBusy ? setPriceCreateOpen(false) : null)} disabled={priceBusy}>
                Cancel
              </Button>
              <Button variant="primary" onClick={() => void onCreatePrice()} disabled={priceBusy}>
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
              <Input value={priceCurrency} onChange={(e) => setPriceCurrency(e.target.value)} disabled={priceBusy} />
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium">Interval</label>
              <select
                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={priceInterval}
                onChange={(e) => setPriceInterval(e.target.value === "year" ? "year" : "month")}
                disabled={priceBusy}
              >
                <option value="month">month</option>
                <option value="year">year</option>
              </select>
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium">Amount</label>
              <Input value={priceAmount} onChange={(e) => setPriceAmount(e.target.value)} disabled={priceBusy} />
              <div className="text-xs text-zinc-500">Example: 29.00</div>
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

        <Modal
          open={priceEditOpen}
          onClose={() => {
            if (!priceBusy) setPriceEditOpen(false);
          }}
          title={selectedPrice ? `Edit price #${selectedPrice.id}` : "Edit price"}
          footer={
            <div className="flex items-center justify-end gap-2">
              <Button variant="outline" onClick={() => (!priceBusy ? setPriceEditOpen(false) : null)} disabled={priceBusy}>
                Cancel
              </Button>
              <Button variant="primary" onClick={() => void onUpdatePrice()} disabled={priceBusy || !selectedPrice}>
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

            {selectedPrice ? (
              <div className="text-sm text-zinc-700">
                {String(selectedPrice.currency).toUpperCase()} / {selectedPrice.interval} (currently {formatMoney({ amountCents: selectedPrice.amount_cents, currency: selectedPrice.currency })})
              </div>
            ) : null}

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
          title="Activate plan version"
          message={
            <div className="space-y-3">
              <div>This will activate this draft and retire the currently active version (if any). This action is irreversible.</div>
              <div className="space-y-1">
                <label className="text-sm font-medium">Type ACTIVATE to confirm</label>
                <Input value={activateConfirm} onChange={(e) => setActivateConfirm(e.target.value)} disabled={activateBusy} />
              </div>
            </div>
          }
          confirmText="Activate"
          confirmVariant="secondary"
          busy={activateBusy}
          onCancel={() => {
            if (!activateBusy) setActivateOpen(false);
          }}
          onConfirm={() => void onActivate()}
        />
      </div>
    </RequireAuth>
  );
}
