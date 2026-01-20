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
      const ok = (res as any)?.status === "ok";
      const errors = Array.isArray((res as any)?.errors) ? (res as any).errors.map(String) : [];
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

  const isReadOnly = useMemo(() => {
    if (!version) return true;
    if (version.status !== "draft") return true;
    if (version.locked_at) return true;
    if (version.activated_at || version.retired_at) return true;
    return false;
  }, [version]);

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

    for (const def of allEntitlementDefinitions) {
      const row = entitlementsByDefId[def.id] ?? null;
      nextEnabled[def.id] = Boolean(row);
      nextValue[def.id] = row ? row.value_json : null;
    }

    setEntitlementEnabled(nextEnabled);
    setEntitlementValue(nextValue);
  }, [allEntitlementDefinitions, entitlementsByDefId, version]);

  const [entitlementEnabled, setEntitlementEnabled] = useState<Record<number, boolean>>({});
  const [entitlementValue, setEntitlementValue] = useState<Record<number, unknown>>({});
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

        {version && canWrite && !isReadOnly ? (
          <Card>
            <CardHeader className="flex flex-row items-start justify-between gap-4">
              <div className="min-w-0">
                <CardTitle className="truncate">Draft actions</CardTitle>
                <div className="mt-1 text-sm text-zinc-600">Validate and activate this draft plan version.</div>
              </div>
              <div className="flex items-center gap-2">
                <Button variant="outline" size="sm" onClick={() => void onValidate()} disabled={validateBusy}>
                  {validateBusy ? "Validating…" : "Validate"}
                </Button>
                <Button variant="secondary" size="sm" onClick={() => setActivateOpen(true)} disabled={activateBusy}>
                  Activate
                </Button>
              </div>
            </CardHeader>
            <CardContent>
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

        {canWrite && !isReadOnly ? (
          <div className="flex items-center justify-end">
            <Button
              variant="secondary"
              onClick={() => {
                resetPriceForm();
                setPriceCreateOpen(true);
              }}
              disabled={loading}
            >
              Add price
            </Button>
          </div>
        ) : null}

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

        {version ? (
          <Card>
            <CardHeader className="flex flex-row items-start justify-between gap-4">
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

                    return (
                      <div key={def.id} className="rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white p-3">
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
                              onChange={(e) => setEntitlementEnabled((prev) => ({ ...prev, [def.id]: e.target.checked }))}
                              disabled={isReadOnly || !canWrite}
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
                                  onChange={(e) => setEntitlementValue((prev) => ({ ...prev, [def.id]: e.target.checked }))}
                                  disabled={isReadOnly || !canWrite}
                                />
                                Value
                              </label>
                            ) : type === "integer" ? (
                              <div className="space-y-1">
                                <label className="text-sm font-medium">Value</label>
                                <Input
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
                              <div className="space-y-1">
                                <label className="text-sm font-medium">Value (JSON)</label>
                                <textarea
                                  className="min-h-[90px] w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                                  value={JSON.stringify(value ?? null, null, 2)}
                                  onChange={(e) => {
                                    const raw = e.target.value;
                                    try {
                                      const parsed = JSON.parse(raw);
                                      setEntitlementValue((prev) => ({ ...prev, [def.id]: parsed }));
                                    } catch {
                                      setEntitlementValue((prev) => ({ ...prev, [def.id]: prev[def.id] }));
                                    }
                                  }}
                                  disabled={isReadOnly || !canWrite}
                                />
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
