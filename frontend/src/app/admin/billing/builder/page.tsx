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
import { getBillingCatalog } from "@/lib/billing";
import type { EntitlementDefinition } from "@/lib/types";

type BuilderPrice = {
  id: number;
  currency: string;
  interval: "month" | "year";
  amountCents: number;
  trialDays: number | null;
  isDefault: boolean;
};

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

  const [step, setStep] = useState<number>(0);

  const [planName, setPlanName] = useState("Starter");
  const [planCode, setPlanCode] = useState("starter");
  const [planDescription, setPlanDescription] = useState("A simple starter plan.");
  const [planActive, setPlanActive] = useState(true);

  const [versionNumber, setVersionNumber] = useState<number>(1);
  const [versionStatus, setVersionStatus] = useState<"draft" | "active" | "retired">("draft");

  const [prices, setPrices] = useState<BuilderPrice[]>([
    { id: 1, currency: "EUR", interval: "month", amountCents: 2900, trialDays: 14, isDefault: true },
  ]);

  const [defsLoading, setDefsLoading] = useState(true);
  const [defsError, setDefsError] = useState<string | null>(null);
  const [definitions, setDefinitions] = useState<EntitlementDefinition[]>([]);

  const premiumDefinitions = useMemo(() => {
    return (Array.isArray(definitions) ? definitions : []).filter((d) => Boolean(d.is_premium));
  }, [definitions]);

  const [enabledEntitlements, setEnabledEntitlements] = useState<Record<number, boolean>>({});
  const [entitlementValue, setEntitlementValue] = useState<Record<number, unknown>>({});

  const [priceModalOpen, setPriceModalOpen] = useState(false);
  const [priceBusy, setPriceBusy] = useState(false);
  const [priceEditId, setPriceEditId] = useState<number | null>(null);
  const [priceCurrency, setPriceCurrency] = useState("EUR");
  const [priceInterval, setPriceInterval] = useState<"month" | "year">("month");
  const [priceAmount, setPriceAmount] = useState("29.00");
  const [priceTrialDays, setPriceTrialDays] = useState<string>("14");
  const [priceIsDefault, setPriceIsDefault] = useState(true);
  const [priceError, setPriceError] = useState<string | null>(null);

  const [activateOpen, setActivateOpen] = useState(false);
  const [activateConfirm, setActivateConfirm] = useState("");
  const [activated, setActivated] = useState(false);

  const steps = ["Plan", "Version", "Prices", "Entitlements", "Review"];

  useEffect(() => {
    dashboardHeader.setHeader({
      breadcrumb: "Admin / Billing",
      title: "Plan builder (mock)",
      subtitle: "Draft a plan/version configuration before wiring backend",
      actions: (
        <div className="flex items-center gap-2">
          <Link href="/admin/billing/plans">
            <Button variant="outline" size="sm">
              Back to plans
            </Button>
          </Link>
          <Button variant="outline" size="sm" onClick={() => setStep(0)}>
            Restart
          </Button>
        </div>
      ),
    });

    return () => dashboardHeader.setHeader(null);
  }, [dashboardHeader]);

  useEffect(() => {
    let alive = true;

    async function loadDefs() {
      try {
        setDefsLoading(true);
        setDefsError(null);

        const res = await getBillingCatalog({ includeInactive: true });
        if (!alive) return;
        setDefinitions(Array.isArray(res.entitlement_definitions) ? res.entitlement_definitions : []);
      } catch (e) {
        if (!alive) return;
        setDefsError(e instanceof Error ? e.message : "Failed to load entitlement definitions.");
        setDefinitions([]);
      } finally {
        if (!alive) return;
        setDefsLoading(false);
      }
    }

    void loadDefs();

    return () => {
      alive = false;
    };
  }, []);

  useEffect(() => {
    if (!Array.isArray(premiumDefinitions)) return;

    setEnabledEntitlements((prev) => {
      const next = { ...prev };
      for (const d of premiumDefinitions) {
        if (typeof next[d.id] === "undefined") next[d.id] = false;
      }
      return next;
    });

    setEntitlementValue((prev) => {
      const next = { ...prev };
      for (const d of premiumDefinitions) {
        if (typeof next[d.id] === "undefined") {
          if (d.value_type === "boolean") next[d.id] = false;
          else if (d.value_type === "integer") next[d.id] = 0;
          else next[d.id] = null;
        }
      }
      return next;
    });
  }, [premiumDefinitions]);

  const validationErrors = useMemo(() => {
    const errs: string[] = [];

    if (!planName.trim()) errs.push("Plan name is required.");
    if (!planCode.trim()) errs.push("Plan code is required.");

    if (prices.length === 0) errs.push("At least one price is required.");

    const defaults: Record<string, number> = {};
    for (const p of prices) {
      const key = `${String(p.currency).toUpperCase()}|${p.interval}`;
      if (p.isDefault) defaults[key] = (defaults[key] ?? 0) + 1;
    }

    for (const [key, count] of Object.entries(defaults)) {
      if (count > 1) errs.push(`Multiple default prices configured for ${key}.`);
    }

    return errs;
  }, [planCode, planName, prices]);

  const summary = useMemo(() => {
    const entitlements: BuilderEntitlement[] = [];
    for (const def of premiumDefinitions) {
      if (!enabledEntitlements[def.id]) continue;
      entitlements.push({ entitlement_definition_id: def.id, value_json: entitlementValue[def.id] ?? null });
    }

    return {
      plan: {
        name: planName.trim(),
        code: planCode.trim(),
        description: planDescription.trim() || null,
        is_active: planActive,
      },
      version: {
        version: versionNumber,
        status: versionStatus,
      },
      prices: prices.map((p) => ({
        currency: String(p.currency).toUpperCase(),
        interval: p.interval,
        amount_cents: p.amountCents,
        trial_days: p.trialDays,
        is_default: p.isDefault,
      })),
      entitlements,
    };
  }, [enabledEntitlements, entitlementValue, planActive, planCode, planDescription, planName, premiumDefinitions, prices, versionNumber, versionStatus]);

  function goNext() {
    setActivated(false);
    setStep((s) => Math.min(s + 1, steps.length - 1));
  }

  function goBack() {
    setActivated(false);
    setStep((s) => Math.max(s - 1, 0));
  }

  function resetPriceForm() {
    setPriceEditId(null);
    setPriceCurrency("EUR");
    setPriceInterval("month");
    setPriceAmount("29.00");
    setPriceTrialDays("14");
    setPriceIsDefault(true);
    setPriceError(null);
  }

  function parseAmountCents(input: string): number {
    const normalized = input.trim().replace(",", ".");
    const n = Number.parseFloat(normalized);
    if (!Number.isFinite(n) || n < 0) return 0;
    return Math.round(n * 100);
  }

  async function onSavePrice() {
    if (priceBusy) return;
    setPriceBusy(true);
    setPriceError(null);

    try {
      const currency = priceCurrency.trim().toUpperCase();
      if (currency.length !== 3) {
        setPriceError("Currency must be 3 letters (e.g. EUR).");
        return;
      }

      const amountCents = parseAmountCents(priceAmount);
      const trialDays = priceTrialDays.trim() === "" ? null : Number(priceTrialDays);
      const trialDaysValue = typeof trialDays === "number" && Number.isFinite(trialDays) ? Math.max(0, Math.trunc(trialDays)) : null;

      setPrices((prev) => {
        const next = prev.slice();

        const nextItem: BuilderPrice = {
          id: priceEditId ?? Date.now(),
          currency,
          interval: priceInterval,
          amountCents,
          trialDays: trialDaysValue,
          isDefault: priceIsDefault,
        };

        if (nextItem.isDefault) {
          for (const p of next) {
            if (String(p.currency).toUpperCase() === currency && p.interval === priceInterval) {
              p.isDefault = false;
            }
          }
        }

        const idx = next.findIndex((p) => p.id === nextItem.id);
        if (idx >= 0) next[idx] = nextItem;
        else next.push(nextItem);

        return next;
      });

      setPriceModalOpen(false);
    } finally {
      setPriceBusy(false);
    }
  }

  function onEditPrice(p: BuilderPrice) {
    setPriceEditId(p.id);
    setPriceCurrency(String(p.currency).toUpperCase());
    setPriceInterval(p.interval);
    setPriceAmount((p.amountCents / 100).toFixed(2));
    setPriceTrialDays(typeof p.trialDays === "number" ? String(p.trialDays) : "");
    setPriceIsDefault(Boolean(p.isDefault));
    setPriceError(null);
    setPriceModalOpen(true);
  }

  function onDeletePrice(p: BuilderPrice) {
    setPrices((prev) => prev.filter((x) => x.id !== p.id));
  }

  return (
    <RequireAuth requiredPermission="admin.billing.read">
      <div className="space-y-6">
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

        {activated ? (
          <Alert variant="success" title="Activated (mock)">This is a mock activation. Next we will wire it to backend endpoints.</Alert>
        ) : null}

        {step === 0 ? (
          <Card>
            <CardHeader>
              <CardTitle>Plan details</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-1">
                <label className="text-sm font-medium">Name</label>
                <Input value={planName} onChange={(e) => setPlanName(e.target.value)} />
              </div>
              <div className="space-y-1">
                <label className="text-sm font-medium">Code</label>
                <Input value={planCode} onChange={(e) => setPlanCode(e.target.value)} />
              </div>
              <div className="space-y-1">
                <label className="text-sm font-medium">Description</label>
                <textarea
                  className="min-h-[90px] w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                  value={planDescription}
                  onChange={(e) => setPlanDescription(e.target.value)}
                />
              </div>
              <label className="flex items-center gap-2 text-sm">
                <input type="checkbox" checked={planActive} onChange={(e) => setPlanActive(e.target.checked)} />
                Active
              </label>
            </CardContent>
          </Card>
        ) : null}

        {step === 1 ? (
          <Card>
            <CardHeader>
              <CardTitle>Version</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div className="space-y-1">
                  <label className="text-sm font-medium">Version number</label>
                  <Input
                    type="number"
                    value={String(versionNumber)}
                    onChange={(e) => setVersionNumber(Number(e.target.value) || 1)}
                    min={1}
                  />
                </div>
                <div className="space-y-1">
                  <label className="text-sm font-medium">Status</label>
                  <select
                    className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                    value={versionStatus}
                    onChange={(e) => setVersionStatus(e.target.value === "active" ? "active" : e.target.value === "retired" ? "retired" : "draft")}
                  >
                    <option value="draft">draft</option>
                    <option value="active">active</option>
                    <option value="retired">retired</option>
                  </select>
                  <div className="text-xs text-zinc-500">In the real flow only draft is editable; activation is an explicit action.</div>
                </div>
              </div>
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
              <Button
                variant="secondary"
                size="sm"
                onClick={() => {
                  resetPriceForm();
                  setPriceModalOpen(true);
                }}
              >
                Add price
              </Button>
            </CardHeader>
            <CardContent>
              <DataTable
                title="Price points"
                data={prices}
                loading={false}
                emptyMessage="No prices yet."
                getRowId={(p) => p.id}
                columns={[
                  {
                    id: "pair",
                    header: "Currency / interval",
                    cell: (p) => (
                      <div className="text-sm text-zinc-800">
                        {String(p.currency).toUpperCase()} / {p.interval}
                      </div>
                    ),
                    className: "whitespace-nowrap",
                  },
                  {
                    id: "amount",
                    header: "Amount",
                    cell: (p) => <div className="text-sm text-zinc-700">{formatCents(p.amountCents, p.currency)}</div>,
                    className: "whitespace-nowrap",
                  },
                  {
                    id: "trial",
                    header: "Trial",
                    cell: (p) => <div className="text-sm text-zinc-700">{typeof p.trialDays === "number" ? `${p.trialDays} days` : "—"}</div>,
                    className: "whitespace-nowrap",
                  },
                  {
                    id: "default",
                    header: "Default",
                    cell: (p) => (p.isDefault ? <Badge variant="success">default</Badge> : <Badge variant="default">—</Badge>),
                    className: "whitespace-nowrap",
                  },
                  {
                    id: "actions",
                    header: "",
                    cell: (p) => (
                      <div className="flex items-center justify-end gap-2">
                        <Button variant="outline" size="sm" onClick={() => onEditPrice(p)}>
                          Edit
                        </Button>
                        <Button variant="outline" size="sm" onClick={() => onDeletePrice(p)}>
                          Delete
                        </Button>
                      </div>
                    ),
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
              {defsError ? (
                <Alert variant="danger" title="Could not load entitlement definitions">
                  {defsError}
                </Alert>
              ) : null}

              {defsLoading ? <div className="text-sm text-zinc-600">Loading features…</div> : null}

              {!defsLoading && premiumDefinitions.length === 0 ? (
                <Alert variant="warning" title="No premium features">
                  Mark features as premium in Admin / Billing / Entitlements to make them available in the builder.
                </Alert>
              ) : null}

              {premiumDefinitions.map((def) => {
                const enabled = Boolean(enabledEntitlements[def.id]);
                const value = entitlementValue[def.id];

                return (
                  <div key={def.id} className="rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white p-3">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                      <div className="min-w-0">
                        <div className="text-sm font-semibold text-zinc-900">
                          {def.name} <span className="text-xs font-normal text-zinc-500">({def.code})</span>
                        </div>
                        <div className="mt-1 text-xs text-zinc-500">Type: {def.value_type}</div>
                        {def.description ? <div className="mt-1 text-xs text-zinc-500">{def.description}</div> : null}
                      </div>
                      <label className="flex items-center gap-2 text-sm">
                        <input
                          type="checkbox"
                          checked={enabled}
                          onChange={(e) => setEnabledEntitlements((prev) => ({ ...prev, [def.id]: e.target.checked }))}
                        />
                        Enabled
                      </label>
                    </div>

                    {enabled ? (
                      <div className="mt-3">
                        {def.value_type === "boolean" ? (
                          <label className="flex items-center gap-2 text-sm">
                            <input
                              type="checkbox"
                              checked={Boolean(value)}
                              onChange={(e) => setEntitlementValue((prev) => ({ ...prev, [def.id]: e.target.checked }))}
                            />
                            Value
                          </label>
                        ) : def.value_type === "integer" ? (
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
                              value={JSON.stringify(value ?? null, null, 2)}
                              onChange={(e) => {
                                try {
                                  const parsed = JSON.parse(e.target.value);
                                  setEntitlementValue((prev) => ({ ...prev, [def.id]: parsed }));
                                } catch {
                                  setEntitlementValue((prev) => ({ ...prev, [def.id]: prev[def.id] }));
                                }
                              }}
                            />
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
                <div className="mt-1 text-sm text-zinc-600">This is the configuration payload we will later persist via backend APIs.</div>
              </div>
              <Button variant="secondary" size="sm" onClick={() => setActivateOpen(true)}>
                Activate (mock)
              </Button>
            </CardHeader>
            <CardContent className="space-y-4">
              {validationErrors.length > 0 ? (
                <Alert variant="warning" title="Validation warnings">
                  <div className="space-y-1">
                    {validationErrors.map((e, idx) => (
                      <div key={idx}>{e}</div>
                    ))}
                  </div>
                </Alert>
              ) : (
                <Alert variant="success" title="Looks good">No validation warnings.</Alert>
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
          title="Activate (mock)"
          message={
            <div className="space-y-3">
              <div>This will simulate activation. Next we will call the backend activation endpoint.</div>
              <div className="space-y-1">
                <label className="text-sm font-medium">Type ACTIVATE to confirm</label>
                <Input value={activateConfirm} onChange={(e) => setActivateConfirm(e.target.value)} />
              </div>
            </div>
          }
          confirmText="Activate"
          confirmVariant="secondary"
          busy={false}
          onCancel={() => setActivateOpen(false)}
          onConfirm={() => {
            if (activateConfirm.trim() === "ACTIVATE") {
              setActivated(true);
              setActivateOpen(false);
              setActivateConfirm("");
            }
          }}
        />
      </div>
    </RequireAuth>
  );
}
