"use client";

import React, { useEffect, useMemo, useState } from "react";
import Image from "next/image";
import { RequireAuth } from "@/components/RequireAuth";
import { useParams, useRouter } from "next/navigation";
import { Button } from "@/components/ui/Button";
import { Alert } from "@/components/ui/Alert";
import { Input } from "@/components/ui/Input";
import { Select } from "@/components/ui/Select";
import { Preloader } from "@/components/Preloader";
import { ApiError } from "@/lib/api";
import { computeGateRedirect } from "@/lib/gate";
import { getTenantBillingPlans, subscribeToPlan } from "@/lib/billingOnboarding";
import type { BillingPlan, BillingPlanVersion, BillingPrice } from "@/lib/types";

export default function PlansPage() {
  const params = useParams() as { business?: string };
  const business = params.business ?? "";
  const router = useRouter();

  const [loading, setLoading] = useState(true);
  const [plans, setPlans] = useState<BillingPlan[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  const [billingCountry, setBillingCountry] = useState("US");
  const [currency, setCurrency] = useState("USD");
  const [interval, setInterval] = useState<string>("month");
  const [vatNumber, setVatNumber] = useState("");

  useEffect(() => {
    let alive = true;
    if (!business) return;

    setLoading(true);
    setError(null);

    getTenantBillingPlans(business)
      .then((res) => {
        if (!alive) return;
        setPlans(Array.isArray(res.billing_plans) ? res.billing_plans : []);
      })
      .catch((e) => {
        if (!alive) return;
        if (e instanceof ApiError) {
          setError(e.message);
        } else {
          setError(e instanceof Error ? e.message : "Failed to load plans.");
        }
      })
      .finally(() => {
        if (!alive) return;
        setLoading(false);
      });

    return () => {
      alive = false;
    };
  }, [business]);

  const normalizeCurrency = (value: string) => value.trim().toUpperCase();
  const normalizeCountry = (value: string) => value.trim().toUpperCase();

  const activePlanVersions = useMemo(() => {
    const rows: Array<{ plan: BillingPlan; version: BillingPlanVersion }> = [];
    for (const plan of plans) {
      const versions = Array.isArray(plan.versions) ? plan.versions : [];
      const active = versions
        .filter((v) => v.status === "active")
        .sort((a, b) => (b.version ?? 0) - (a.version ?? 0));
      const top = active[0];
      if (top) rows.push({ plan, version: top });
    }
    return rows;
  }, [plans]);

  const activePrices = useMemo(() => {
    const rows: Array<{ plan: BillingPlan; version: BillingPlanVersion; price: BillingPrice }> = [];
    for (const { plan, version } of activePlanVersions) {
      const prices = Array.isArray(version.prices) ? version.prices : [];
      for (const price of prices) {
        rows.push({ plan, version, price });
      }
    }
    return rows;
  }, [activePlanVersions]);

  const availableCurrencies = useMemo(() => {
    const s = new Set<string>();
    for (const row of activePrices) {
      if (row.price.currency) s.add(String(row.price.currency).toUpperCase());
    }
    return Array.from(s).sort();
  }, [activePrices]);

  const availableIntervals = useMemo(() => {
    const s = new Set<string>();
    for (const row of activePrices) {
      if (row.price.interval) s.add(String(row.price.interval));
    }
    return Array.from(s).sort();
  }, [activePrices]);

  useEffect(() => {
    if (loading) return;

    if (availableCurrencies.length > 0) {
      setCurrency((prev) => {
        const next = normalizeCurrency(prev || "");
        return availableCurrencies.includes(next) ? next : availableCurrencies[0];
      });
    }

    if (availableIntervals.length > 0) {
      setInterval((prev) => (availableIntervals.includes(prev) ? prev : availableIntervals[0]));
    }
  }, [availableCurrencies, availableIntervals, loading]);

  const validateBillingBasics = (): string | null => {
    const c = normalizeCountry(billingCountry);
    const cur = normalizeCurrency(currency);
    if (!c || c.length !== 2) return "Billing country must be a 2-letter code (e.g. US).";
    if (!cur || cur.length !== 3) return "Currency must be a 3-letter code (e.g. USD).";
    return null;
  };

  const formatMoney = (amountCents: number, cur: string) => {
    const value = (typeof amountCents === "number" ? amountCents : 0) / 100;
    try {
      return new Intl.NumberFormat(undefined, {
        style: "currency",
        currency: cur,
        maximumFractionDigits: 2,
      }).format(value);
    } catch {
      return `${cur} ${value.toFixed(2)}`;
    }
  };

  const intervalLabel = (v: BillingPlanVersion, intervalCode: string) => {
    const prices = Array.isArray(v.prices) ? v.prices : [];
    const match = prices.find((p) => p.interval === intervalCode);
    const modelName = match?.interval_model?.name;
    if (modelName) return modelName;
    if (intervalCode === "month") return "Monthly";
    if (intervalCode === "year") return "Yearly";
    return intervalCode;
  };

  const selectPriceForPlan = (v: BillingPlanVersion) => {
    const prices = Array.isArray(v.prices) ? v.prices : [];
    const cur = normalizeCurrency(currency);
    const intervalCode = interval;

    const matching = prices.filter((p) => normalizeCurrency(p.currency) === cur && p.interval === intervalCode);
    const preferred = matching.find((p) => p.is_default) ?? matching[0];
    if (preferred) return preferred;

    const anyByCurrency = prices.filter((p) => normalizeCurrency(p.currency) === cur);
    return anyByCurrency.find((p) => p.is_default) ?? anyByCurrency[0] ?? prices.find((p) => p.is_default) ?? prices[0] ?? null;
  };

  const isRecommended = (price: BillingPrice | null) => {
    if (!price) return false;
    const amounts = activePlanVersions
      .map(({ version }) => selectPriceForPlan(version))
      .filter((p): p is BillingPrice => Boolean(p))
      .map((p) => p.amount_cents)
      .sort((a, b) => a - b);
    if (amounts.length < 2) return false;
    const median = amounts[Math.floor(amounts.length / 2)];
    return price.amount_cents === median;
  };

  const onSelectPrice = async (priceId: number) => {
    if (!business) return;
    const err = validateBillingBasics();
    if (err) {
      setError(err);
      return;
    }

    setSubmitting(true);
    setError(null);

    try {
      const res = await subscribeToPlan(business, {
        billing_price_id: priceId,
        billing_country: normalizeCountry(billingCountry),
        currency: normalizeCurrency(currency),
        billing_vat_number: vatNumber.trim() || null,
      });

      router.replace(computeGateRedirect(business, res.gate));
    } catch (e) {
      if (e instanceof ApiError) {
        setError(e.message);
      } else {
        setError(e instanceof Error ? e.message : "Failed to select plan.");
      }
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <RequireAuth>
      {loading ? (
        <Preloader label="Loading plans" />
      ) : (
        <div className="min-h-screen bg-[linear-gradient(180deg,color-mix(in_srgb,var(--rb-blue),white_94%),white)] px-6 py-10">
          <div className="mx-auto w-full max-w-6xl">
            <div className="flex items-center justify-between gap-4">
              <div className="flex items-center gap-3">
                <div className="h-10 w-10 overflow-hidden rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white shadow-[var(--rb-shadow)]">
                  <Image src="/brand/repair-buddy-logo.png" alt="Repair Buddy" width={40} height={40} priority />
                </div>
                <div>
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Repair Buddy</div>
                  <div className="text-xs text-zinc-600">Choose the plan that fits your shop</div>
                </div>
              </div>

              <Button variant="outline" disabled={!business || submitting} onClick={() => router.replace(`/app/${business}`)}>
                Back
              </Button>
            </div>

            <div className="mt-8 grid gap-6 lg:grid-cols-[1fr,360px]">
              <div>
                <div className="rounded-[var(--rb-radius-xl)] border border-[var(--rb-border)] bg-white p-6 shadow-[var(--rb-shadow)]">
                  <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                      <div className="text-xl font-semibold tracking-tight text-[var(--rb-text)]">Pricing</div>
                      <div className="mt-1 text-sm text-zinc-600">
                        Start with a trial, upgrade anytime. Your plan can be changed later.
                      </div>
                    </div>

                    <div className="mt-2 flex items-center gap-3 sm:mt-0">
                      <div className="min-w-[160px]">
                        <label className="text-xs font-medium text-zinc-600">Billing interval</label>
                        <div className="mt-1">
                          <Select value={interval} onChange={(e) => setInterval(e.target.value)} disabled={submitting}>
                            {availableIntervals.length === 0 ? <option value="month">Monthly</option> : null}
                            {availableIntervals.map((i) => (
                              <option key={i} value={i}>
                                {intervalLabel(activePlanVersions[0]?.version ?? ({} as BillingPlanVersion), i)}
                              </option>
                            ))}
                          </Select>
                        </div>
                      </div>

                      <div className="min-w-[140px]">
                        <label className="text-xs font-medium text-zinc-600">Currency</label>
                        <div className="mt-1">
                          <Select value={currency} onChange={(e) => setCurrency(e.target.value)} disabled={submitting}>
                            {availableCurrencies.length === 0 ? <option value="USD">USD</option> : null}
                            {availableCurrencies.map((c) => (
                              <option key={c} value={c}>
                                {c}
                              </option>
                            ))}
                          </Select>
                        </div>
                      </div>
                    </div>
                  </div>

                  {error ? (
                    <div className="mt-4">
                      <Alert variant="danger" title="Plan selection error">
                        {error}
                      </Alert>
                    </div>
                  ) : null}

                  <div className="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {activePlanVersions.length === 0 ? (
                      <div className="md:col-span-2 xl:col-span-3">
                        <div className="rounded-[var(--rb-radius-lg)] border border-dashed border-[var(--rb-border)] bg-[var(--rb-surface-muted)] p-8 text-center">
                          <div className="text-sm font-semibold text-[var(--rb-text)]">No plans available</div>
                          <div className="mt-1 text-sm text-zinc-600">Ask your admin to configure billing plans.</div>
                        </div>
                      </div>
                    ) : null}

                    {activePlanVersions.map(({ plan, version }) => {
                      const selected = selectPriceForPlan(version);
                      const trialDays = typeof selected?.trial_days === "number" ? selected.trial_days : null;
                      const recommended = isRecommended(selected);

                      const entitlements = Array.isArray(version.entitlements) ? version.entitlements : [];
                      const topEntitlements = entitlements
                        .filter((e) => e.definition?.name)
                        .slice(0, 6);

                      const canSelect = Boolean(selected) && !submitting;
                      const priceLabel = selected ? formatMoney(selected.amount_cents, normalizeCurrency(selected.currency)) : "Not available";

                      return (
                        <div
                          key={plan.id}
                          className={
                            "relative overflow-hidden rounded-[var(--rb-radius-xl)] border bg-white p-5 shadow-[var(--rb-shadow)] " +
                            (recommended
                              ? "border-[color:color-mix(in_srgb,var(--rb-orange),white_45%)] ring-2 ring-[color:color-mix(in_srgb,var(--rb-orange),white_70%)]"
                              : "border-[var(--rb-border)]")
                          }
                        >
                          {recommended ? (
                            <div className="absolute right-4 top-4 rounded-full bg-[color:color-mix(in_srgb,var(--rb-orange),white_20%)] px-2.5 py-1 text-[11px] font-semibold text-white">
                              Recommended
                            </div>
                          ) : null}

                          <div className="text-base font-semibold text-[var(--rb-text)]">{plan.name}</div>
                          {plan.description ? <div className="mt-1 text-sm text-zinc-600">{plan.description}</div> : null}

                          <div className="mt-4 flex items-end justify-between gap-3">
                            <div>
                              <div className="text-3xl font-semibold tracking-tight text-[var(--rb-text)]">{priceLabel}</div>
                              <div className="mt-1 text-xs text-zinc-600">
                                {selected ? `per ${intervalLabel(version, selected.interval).toLowerCase()}` : `Unavailable for ${currency} / ${interval}`}
                              </div>
                            </div>

                            <div className="text-right">
                              <div className="text-xs text-zinc-600">{trialDays && trialDays > 0 ? `${trialDays} day trial` : "No trial"}</div>
                            </div>
                          </div>

                          {topEntitlements.length > 0 ? (
                            <div className="mt-4">
                              <div className="text-xs font-medium text-zinc-600">What’s included</div>
                              <div className="mt-2 grid gap-2">
                                {topEntitlements.map((e) => (
                                  <div key={e.id} className="flex items-start gap-2 text-sm text-[var(--rb-text)]">
                                    <svg viewBox="0 0 24 24" className="mt-0.5 h-4 w-4 shrink-0 text-[var(--rb-orange)]" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                      <path d="M20 6L9 17l-5-5" />
                                    </svg>
                                    <div className="min-w-0">
                                      <div className="truncate">{e.definition?.name}</div>
                                      {e.definition?.description ? <div className="mt-0.5 text-xs text-zinc-600">{e.definition.description}</div> : null}
                                    </div>
                                  </div>
                                ))}
                              </div>
                            </div>
                          ) : (
                            <div className="mt-4 text-sm text-zinc-600">Everything you need to get started.</div>
                          )}

                          <div className="mt-5">
                            <Button
                              variant={recommended ? "secondary" : "primary"}
                              className="w-full"
                              disabled={!canSelect}
                              onClick={() => {
                                if (!selected) return;
                                void onSelectPrice(selected.id);
                              }}
                            >
                              {selected ? (trialDays && trialDays > 0 ? "Start trial" : "Continue") : "Unavailable"}
                            </Button>
                          </div>
                        </div>
                      );
                    })}
                  </div>
                </div>
              </div>

              <div className="rounded-[var(--rb-radius-xl)] border border-[var(--rb-border)] bg-white p-6 shadow-[var(--rb-shadow)]">
                <div className="text-sm font-semibold text-[var(--rb-text)]">Billing details</div>
                <div className="mt-1 text-sm text-zinc-600">Used to calculate taxes and invoices.</div>

                <div className="mt-5 grid gap-4">
                  <div>
                    <label className="text-sm font-medium text-[var(--rb-text)]">Billing country</label>
                    <div className="mt-1">
                      <Select value={billingCountry} onChange={(e) => setBillingCountry(e.target.value.toUpperCase())} disabled={submitting}>
                        <option value="US">US</option>
                        <option value="CA">CA</option>
                        <option value="GB">GB</option>
                        <option value="IE">IE</option>
                        <option value="DE">DE</option>
                        <option value="FR">FR</option>
                        <option value="ES">ES</option>
                        <option value="NL">NL</option>
                        <option value="ZA">ZA</option>
                      </Select>
                    </div>
                    <div className="mt-1 text-xs text-zinc-600">Don’t see yours? Type it below.</div>
                    <div className="mt-2">
                      <Input
                        value={billingCountry}
                        onChange={(e) => setBillingCountry(e.target.value.toUpperCase())}
                        maxLength={2}
                        placeholder="US"
                        disabled={submitting}
                      />
                    </div>
                  </div>

                  <details className="rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)] bg-[var(--rb-surface-muted)] p-4">
                    <summary className="cursor-pointer select-none text-sm font-medium text-[var(--rb-text)]">
                      VAT number (optional)
                    </summary>
                    <div className="mt-3">
                      <Input value={vatNumber} onChange={(e) => setVatNumber(e.target.value)} placeholder="EU123456789" disabled={submitting} />
                      <div className="mt-2 text-xs text-zinc-600">If you have a VAT number, enter it to apply the correct tax rules.</div>
                    </div>
                  </details>

                  <div className="rounded-[var(--rb-radius-lg)] border border-[color:color-mix(in_srgb,var(--rb-blue),white_75%)] bg-[color:color-mix(in_srgb,var(--rb-blue),white_93%)] p-4">
                    <div className="text-sm font-semibold text-[var(--rb-text)]">Tip</div>
                    <div className="mt-1 text-sm text-zinc-700">Pick your plan first. You’ll confirm the final checkout next.</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </RequireAuth>
  );
}
