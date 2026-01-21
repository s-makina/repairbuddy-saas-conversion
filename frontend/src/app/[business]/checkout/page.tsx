"use client";

import React, { useEffect, useState } from "react";
import { RequireAuth } from "@/components/RequireAuth";
import { useParams, useRouter } from "next/navigation";
import Link from "next/link";
import { Button } from "@/components/ui/Button";
import { Preloader } from "@/components/Preloader";
import { Alert } from "@/components/ui/Alert";
import { ApiError } from "@/lib/api";
import { Badge } from "@/components/ui/Badge";
import { getCheckoutSnapshot } from "@/lib/billingOnboarding";

export default function CheckoutPage() {
  const params = useParams() as { business?: string };
  const business = params.business ?? "";
  const router = useRouter();

  const [loading, setLoading] = useState(true);
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
          const cur = sub.currency ?? sub.price?.currency ?? "";
          const interval = sub.price?.interval ?? "";
          const priceText = amount ? `${cur} ${amount} / ${interval}` : cur;
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

  return (
    <RequireAuth>
      {loading ? (
        <Preloader label="Loading checkout" />
      ) : (
        <div className="min-h-screen text-[var(--rb-text)] [background:radial-gradient(1200px_circle_at_20%_0%,color-mix(in_srgb,var(--rb-blue),white_88%)_0%,transparent_55%),radial-gradient(900px_circle_at_80%_15%,color-mix(in_srgb,var(--rb-orange),white_86%)_0%,transparent_60%),var(--rb-surface)]">
          <header className="sticky top-0 z-20 border-b border-[var(--rb-border)] bg-white/70 backdrop-blur">
            <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
              <div className="flex items-center gap-3">
                <Link href="/" className="font-semibold tracking-tight text-[var(--rb-text)]">
                  99smartx
                </Link>
                <Badge variant="info" className="hidden sm:inline-flex">
                  Checkout
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
                <Button variant="outline" onClick={() => router.replace(`/${business}/plans`)}>
                  Back
                </Button>
              </div>
            </div>
          </header>

          <main>
            <section className="mx-auto w-full max-w-6xl px-4 py-10">
              <div className="overflow-x-auto">
                <div className="min-w-[820px]">
                  <div className="grid grid-cols-5 items-start gap-3">
                    <div className="flex flex-col items-center text-center">
                      <div className="flex h-8 w-8 items-center justify-center rounded-full bg-[var(--rb-blue)] text-sm font-semibold text-white">1</div>
                      <div className="mt-2 text-xs font-semibold text-[var(--rb-text)]">Plan</div>
                      <div className="mt-1 text-[11px] text-zinc-600">Select a plan</div>
                    </div>

                    <div className="flex flex-col items-center text-center">
                      <div className="flex h-8 w-8 items-center justify-center rounded-full bg-[var(--rb-blue)] text-sm font-semibold text-white">2</div>
                      <div className="mt-2 text-xs font-semibold text-[var(--rb-text)]">Billing</div>
                      <div className="mt-1 text-[11px] text-zinc-600">Country, VAT, address</div>
                    </div>

                    <div className="flex flex-col items-center text-center">
                      <div className="flex h-8 w-8 items-center justify-center rounded-full bg-[var(--rb-blue)] text-sm font-semibold text-white">3</div>
                      <div className="mt-2 text-xs font-semibold text-[var(--rb-text)]">Payment</div>
                      <div className="mt-1 text-[11px] text-zinc-600">Provider session</div>
                    </div>

                    <div className="flex flex-col items-center text-center">
                      <div className="flex h-8 w-8 items-center justify-center rounded-full bg-[var(--rb-blue)] text-sm font-semibold text-white">4</div>
                      <div className="mt-2 text-xs font-semibold text-[var(--rb-text)]">Confirm</div>
                      <div className="mt-1 text-[11px] text-zinc-600">Webhook/callback</div>
                    </div>

                    <div className="flex flex-col items-center text-center">
                      <div className="flex h-8 w-8 items-center justify-center rounded-full bg-[var(--rb-blue)] text-sm font-semibold text-white">5</div>
                      <div className="mt-2 text-xs font-semibold text-[var(--rb-text)]">Access</div>
                      <div className="mt-1 text-[11px] text-zinc-600">Redirect into app</div>
                    </div>
                  </div>

                  <div className="mt-3 grid grid-cols-5 items-center gap-3">
                    <div className="h-px bg-transparent" />
                    <div className="h-px bg-[color:color-mix(in_srgb,var(--rb-blue),white_70%)]" />
                    <div className="h-px bg-[color:color-mix(in_srgb,var(--rb-blue),white_70%)]" />
                    <div className="h-px bg-[color:color-mix(in_srgb,var(--rb-blue),white_70%)]" />
                    <div className="h-px bg-transparent" />
                  </div>
                </div>
              </div>

              <div className="mt-10 flex justify-center">
                <div className="w-full max-w-lg rounded-[var(--rb-radius-xl)] border border-[var(--rb-border)] bg-white p-7 shadow-[var(--rb-shadow)]">
                  <div className="flex items-start justify-between gap-4">
                    <div>
                      <div className="text-base font-semibold text-[var(--rb-text)]">Checkout</div>
                      <div className="mt-1 text-sm text-zinc-600">
                        {hasPending ? "Integration placeholder" : "No pending checkout found"}
                      </div>
                    </div>

                    <Badge variant="info" className="shrink-0">
                      Coming soon
                    </Badge>
                  </div>

                  <div className="mt-6 grid gap-4">
                    <div className="rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)] bg-[var(--rb-surface-muted)] p-4">
                      <div className="text-xs font-semibold uppercase tracking-wide text-zinc-600">Selected plan</div>
                      <div className="mt-1 text-sm text-[var(--rb-text)]">
                        {subscriptionLabel ? subscriptionLabel : "—"}
                      </div>
                      {!subscriptionLabel ? <div className="mt-1 text-xs text-zinc-600">Go back and pick a plan to continue.</div> : null}
                    </div>

                    <div className="rounded-[var(--rb-radius-lg)] border border-[color:color-mix(in_srgb,var(--rb-blue),white_75%)] bg-[color:color-mix(in_srgb,var(--rb-blue),white_93%)] p-4">
                      <div className="text-sm font-semibold text-[var(--rb-text)]">Integration status</div>
                      <div className="mt-1 text-sm text-zinc-700">
                        Checkout + payment provider integration is not wired yet. This page shows the intended flow.
                      </div>
                    </div>
                  </div>

            {error ? (
              <div className="mt-4">
                <Alert variant="danger" title="Checkout error">
                  {error}
                </Alert>
              </div>
            ) : null}

                  <div className="mt-6 flex flex-col gap-2">
                    <Button variant="primary" disabled>
                      Integration pending
                    </Button>

                    <Button variant="outline" onClick={() => router.replace(`/${business}/plans`)}>
                      Change plan
                    </Button>
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
