"use client";

import React, { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { RequireAuth } from "@/components/RequireAuth";
import { useParams, useRouter } from "next/navigation";
import { Button } from "@/components/ui/Button";
import { Badge } from "@/components/ui/Badge";
import { Alert } from "@/components/ui/Alert";
import { Select } from "@/components/ui/Select";
import { Preloader } from "@/components/Preloader";
import { BillingPlanCard } from "@/components/billing/BillingPlanCard";
import { ApiError } from "@/lib/api";
import { getTenantBillingPlans, subscribeToPlan } from "@/lib/billingOnboarding";
import { computeGateRedirect } from "@/lib/gate";
import type { BillingPlan, BillingPlanVersion, BillingPrice } from "@/lib/types";

export default function PlansPage() {
  const params = useParams() as { business?: string };
  const business = params.business ?? "";
  const router = useRouter();

  const [loading, setLoading] = useState(true);
  const [plans, setPlans] = useState<BillingPlan[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  const [currency, setCurrency] = useState("USD");
  const [interval, setInterval] = useState<string>("month");

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

  const onSelectPrice = async (priceId: number, opts?: { startTrial?: boolean }) => {
    if (!business) return;
    setSubmitting(true);
    setError(null);

    try {
      const res = await subscribeToPlan(business, { billing_price_id: priceId });
      if (opts?.startTrial) {
        router.replace(computeGateRedirect(business, res.gate));
      } else {
        router.replace(`/${business}/checkout`);
      }
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
        <div className="min-h-screen text-[var(--rb-text)] [background:radial-gradient(1200px_circle_at_20%_0%,color-mix(in_srgb,var(--rb-blue),white_88%)_0%,transparent_55%),radial-gradient(900px_circle_at_80%_15%,color-mix(in_srgb,var(--rb-orange),white_86%)_0%,transparent_60%),var(--rb-surface)]">
          <header className="sticky top-0 z-20 border-b border-[var(--rb-border)] bg-white/70 backdrop-blur">
            <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
              <div className="flex items-center gap-3">
                <Link href="/" className="font-semibold tracking-tight text-[var(--rb-text)]">
                  99smartx
                </Link>
                <Badge variant="info" className="hidden sm:inline-flex">
                  Plans
                </Badge>
              </div>

              <nav className="hidden items-center gap-6 text-sm text-zinc-600 md:flex">
                <Link href="/#features" className="hover:text-[var(--rb-text)]">
                  Features
                </Link>
                <Link href="/#pricing" className="hover:text-[var(--rb-text)]">
                  Pricing
                </Link>
                <Link href="/#faq" className="hover:text-[var(--rb-text)]">
                  FAQ
                </Link>
              </nav>

              <div className="flex items-center gap-2">
                <Button variant="outline" disabled={!business || submitting} onClick={() => router.replace(`/app/${business}`)}>
                  Back
                </Button>
              </div>
            </div>
          </header>

          <main>
            <section className="mx-auto w-full max-w-6xl px-4 py-10">
              <div className="grid gap-6">
              <div>
                <div>
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
                      const trialDays = typeof selected?.trial_days === "number" ? selected.trial_days : 0;
                      const recommended = isRecommended(selected);

                      const entitlements = Array.isArray(version.entitlements) ? version.entitlements : [];
                      const visibleEntitlements = entitlements.filter((e) => e.definition?.name);

                      const canSelect = Boolean(selected) && !submitting;
                      const priceLabel = selected ? formatMoney(selected.amount_cents, normalizeCurrency(selected.currency)) : "Not available";

                      return (
                        <BillingPlanCard
                          key={plan.id}
                          plan={plan}
                          version={version}
                          selectedPrice={selected}
                          currency={currency}
                          interval={interval}
                          recommended={recommended}
                          priceLabel={priceLabel}
                          intervalLabel={selected ? intervalLabel(version, selected.interval) : interval}
                          visibleEntitlements={visibleEntitlements}
                          submitting={submitting}
                          onSelect={
                            selected && canSelect
                              ? () => {
                                  void onSelectPrice(selected.id, { startTrial: trialDays > 0 });
                                }
                              : null
                          }
                          actionLabel={selected ? (trialDays > 0 ? "Start trial" : "Continue") : "Unavailable"}
                        />
                      );
                    })}
                  </div>
                </div>
              </div>
            </div>
              <footer className="mt-12 border-t border-[var(--rb-border)] pt-8 text-xs text-zinc-600">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                  <div>© {new Date().getFullYear()} 99smartx</div>
                  <div className="flex items-center gap-4">
                    <Link href="/login" className="hover:text-[var(--rb-text)]">
                      Login
                    </Link>
                    <Link href="/register" className="hover:text-[var(--rb-text)]">
                      Register
                    </Link>
                  </div>
                </div>
              </footer>
            </section>
          </main>
        </div>
      )}
    </RequireAuth>
  );
}
