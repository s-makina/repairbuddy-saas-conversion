"use client";

import React, { useEffect, useState } from "react";
import { RequireAuth } from "@/components/RequireAuth";
import { useParams, useRouter } from "next/navigation";
import { Button } from "@/components/ui/Button";
import { Preloader } from "@/components/Preloader";
import { Alert } from "@/components/ui/Alert";
import { ApiError } from "@/lib/api";
import { computeGateRedirect } from "@/lib/gate";
import { confirmCheckout, getCheckoutSnapshot } from "@/lib/billingOnboarding";

export default function CheckoutPage() {
  const params = useParams() as { business?: string };
  const business = params.business ?? "";
  const router = useRouter();

  const [loading, setLoading] = useState(true);
  const [confirming, setConfirming] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [subscriptionLabel, setSubscriptionLabel] = useState<string | null>(null);
  const [hasPending, setHasPending] = useState(false);

  useEffect(() => {
    let alive = true;
    if (!business) return;

    setLoading(true);
    setError(null);

    getCheckoutSnapshot(business)
      .then((res) => {
        if (!alive) return;
        const sub = res.subscription;
        setHasPending(Boolean(sub));

        if (sub) {
          const planName = sub.plan_version?.plan?.name ?? "Plan";
          const amount = typeof sub.price?.amount_cents === "number" ? (sub.price.amount_cents / 100).toFixed(2) : null;
          const currency = sub.currency ?? sub.price?.currency ?? "";
          const interval = sub.price?.interval ?? "";
          const priceText = amount ? `${currency} ${amount} / ${interval}` : currency;
          setSubscriptionLabel(`${planName} (${priceText})`);
        } else {
          setSubscriptionLabel(null);
        }
      })
      .catch((e) => {
        if (!alive) return;
        if (e instanceof ApiError) {
          setError(e.message);
        } else {
          setError(e instanceof Error ? e.message : "Failed to load checkout.");
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

  const onConfirm = async () => {
    if (!business) return;
    setConfirming(true);
    setError(null);
    try {
      const res = await confirmCheckout(business);
      router.replace(computeGateRedirect(business, res.gate));
    } catch (e) {
      if (e instanceof ApiError) {
        setError(e.message);
      } else {
        setError(e instanceof Error ? e.message : "Failed to confirm checkout.");
      }
    } finally {
      setConfirming(false);
    }
  };

  return (
    <RequireAuth>
      {loading ? (
        <Preloader label="Loading checkout" />
      ) : (
        <div className="min-h-screen flex items-center justify-center px-6">
          <div className="w-full max-w-md rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)] bg-white p-6 shadow-[var(--rb-shadow)]">
            <div className="text-base font-semibold text-[var(--rb-text)]">Checkout</div>
            <div className="mt-2 text-sm text-zinc-600">
              {hasPending ? "Confirm your subscription to continue." : "No pending checkout found."}
            </div>

            {subscriptionLabel ? (
              <div className="mt-3 text-sm text-zinc-700">Selected: {subscriptionLabel}</div>
            ) : null}

            {error ? (
              <div className="mt-4">
                <Alert variant="danger" title="Checkout error">
                  {error}
                </Alert>
              </div>
            ) : null}

            <div className="mt-6 flex flex-col gap-2">
              <Button
                variant="primary"
                disabled={!hasPending || confirming || !business}
                onClick={() => {
                  void onConfirm();
                }}
              >
                {confirming ? "Confirming..." : "Confirm payment"}
              </Button>

              <Button variant="outline" disabled={confirming} onClick={() => router.replace(`/${business}/plans`)}>
                Back to plans
              </Button>
            </div>
          </div>
        </div>
      )}
    </RequireAuth>
  );
}
