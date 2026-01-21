"use client";

import React, { useEffect, useMemo, useState } from "react";
import { RequireAuth } from "@/components/RequireAuth";
import { useParams, useRouter } from "next/navigation";
import { Button } from "@/components/ui/Button";
import { Alert } from "@/components/ui/Alert";
import { Input } from "@/components/ui/Input";
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

  const activePrices = useMemo(() => {
    const rows: Array<{ plan: BillingPlan; version: BillingPlanVersion; price: BillingPrice }> = [];
    for (const plan of plans) {
      const versions = Array.isArray(plan.versions) ? plan.versions : [];
      for (const v of versions) {
        if (v.status !== "active") continue;
        const prices = Array.isArray(v.prices) ? v.prices : [];
        for (const p of prices) {
          rows.push({ plan, version: v, price: p });
        }
      }
    }

    return rows;
  }, [plans]);

  const normalizeCurrency = (value: string) => value.trim().toUpperCase();
  const normalizeCountry = (value: string) => value.trim().toUpperCase();

  const validateBillingBasics = (): string | null => {
    const c = normalizeCountry(billingCountry);
    const cur = normalizeCurrency(currency);
    if (!c || c.length !== 2) return "Billing country must be a 2-letter code (e.g. US).";
    if (!cur || cur.length !== 3) return "Currency must be a 3-letter code (e.g. USD).";
    return null;
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
        <div className="min-h-screen flex items-center justify-center px-6">
          <div className="w-full max-w-2xl rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)] bg-white p-6 shadow-[var(--rb-shadow)]">
            <div className="text-base font-semibold text-[var(--rb-text)]">Choose a plan</div>
            <div className="mt-2 text-sm text-zinc-600">
              Pick a plan to start your trial or continue to checkout.
            </div>

            {error ? (
              <div className="mt-4">
                <Alert variant="danger" title="Plan selection error">
                  {error}
                </Alert>
              </div>
            ) : null}

            <div className="mt-5 grid gap-4 sm:grid-cols-3">
              <div>
                <label className="text-sm font-medium text-[var(--rb-text)]">Billing country</label>
                <div className="mt-1">
                  <Input
                    value={billingCountry}
                    onChange={(e) => setBillingCountry(e.target.value.toUpperCase())}
                    maxLength={2}
                    placeholder="US"
                  />
                </div>
              </div>

              <div>
                <label className="text-sm font-medium text-[var(--rb-text)]">Currency</label>
                <div className="mt-1">
                  <Input
                    value={currency}
                    onChange={(e) => setCurrency(e.target.value.toUpperCase())}
                    maxLength={3}
                    placeholder="USD"
                  />
                </div>
              </div>

              <div>
                <label className="text-sm font-medium text-[var(--rb-text)]">VAT number (optional)</label>
                <div className="mt-1">
                  <Input value={vatNumber} onChange={(e) => setVatNumber(e.target.value)} placeholder="EU123456789" />
                </div>
              </div>
            </div>

            <div className="mt-6 grid gap-3 sm:grid-cols-2">
              {activePrices.length === 0 ? (
                <div className="sm:col-span-2 text-sm text-zinc-600">
                  No active plans are available yet.
                </div>
              ) : null}

              {activePrices.map(({ plan, price }) => {
                const trialDays = typeof price.trial_days === "number" ? price.trial_days : null;
                const label = `${plan.name} (${price.currency} ${(price.amount_cents / 100).toFixed(2)} / ${price.interval})`;
                const subtitle = trialDays && trialDays > 0 ? `${trialDays} day trial` : "Payment required";

                return (
                  <button
                    key={price.id}
                    type="button"
                    disabled={submitting}
                    onClick={() => {
                      void onSelectPrice(price.id);
                    }}
                    className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] p-4 text-left hover:bg-[var(--rb-surface-muted)] disabled:opacity-60"
                  >
                    <div className="text-sm font-semibold text-[var(--rb-text)]">{label}</div>
                    <div className="mt-1 text-xs text-zinc-600">{subtitle}</div>
                  </button>
                );
              })}
            </div>

            <div className="mt-6">
              <Button variant="outline" disabled={!business || submitting} onClick={() => router.replace(`/app/${business}`)}>
                Back
              </Button>
            </div>
          </div>
        </div>
      )}
    </RequireAuth>
  );
}
