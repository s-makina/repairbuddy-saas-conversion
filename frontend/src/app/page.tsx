"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import React, { useEffect, useMemo, useState } from "react";
import { useAuth } from "@/lib/auth";
import { Button } from "@/components/ui/Button";
import { Badge } from "@/components/ui/Badge";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/Card";
import { Alert } from "@/components/ui/Alert";
import { BillingPlanCard } from "@/components/billing/BillingPlanCard";
import { ApiError } from "@/lib/api";
import { getPublicBillingPlans } from "@/lib/publicBilling";
import type { BillingPlan, BillingPlanVersion, BillingPrice } from "@/lib/types";

function CheckIcon(props: React.SVGProps<SVGSVGElement>) {
  const { className, ...rest } = props;
  return (
    <svg
      viewBox="0 0 20 20"
      fill="currentColor"
      aria-hidden="true"
      className={"h-5 w-5 " + (className ?? "")}
      {...rest}
    >
      <path
        fillRule="evenodd"
        d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.06 7.1a1 1 0 0 1-1.42.005L3.29 8.86a1 1 0 1 1 1.42-1.4l3.233 3.28 6.35-6.387a1 1 0 0 1 1.41-.01Z"
        clipRule="evenodd"
      />
    </svg>
  );
}

function SparkIcon(props: React.SVGProps<SVGSVGElement>) {
  const { className, ...rest } = props;
  return (
    <svg
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
      className={"h-5 w-5 " + (className ?? "")}
      {...rest}
    >
      <path d="M12 2l1.3 4.2L18 7.5l-3.9 2.8L15.4 15 12 12.6 8.6 15l1.3-4.7L6 7.5l4.7-1.3L12 2z" />
      <path d="M20 13l.8 2.6L23 16l-2.1 1.5.7 2.5L20 18.5 18.4 20l.6-2.5L17 16l2.2-.4L20 13z" />
    </svg>
  );
}

function BoltIcon(props: React.SVGProps<SVGSVGElement>) {
  const { className, ...rest } = props;
  return (
    <svg
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
      className={"h-5 w-5 " + (className ?? "")}
      {...rest}
    >
      <path d="M13 2L3 14h8l-1 8 10-12h-8l1-8z" />
    </svg>
  );
}

function ShieldIcon(props: React.SVGProps<SVGSVGElement>) {
  const { className, ...rest } = props;
  return (
    <svg
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
      className={"h-5 w-5 " + (className ?? "")}
      {...rest}
    >
      <path d="M12 2l8 4v6c0 5-3.4 9.4-8 10-4.6-.6-8-5-8-10V6l8-4z" />
      <path d="M9.5 12l1.8 1.8L14.8 10" />
    </svg>
  );
}

export default function Home() {
  const auth = useAuth();
  const router = useRouter();
  const [billing, setBilling] = useState<"monthly" | "annual">("monthly");

  const [plansLoading, setPlansLoading] = useState(true);
  const [plansError, setPlansError] = useState<string | null>(null);
  const [plans, setPlans] = useState<BillingPlan[]>([]);
  const [currency] = useState("USD");

  useEffect(() => {
    let alive = true;

    getPublicBillingPlans()
      .then((res) => {
        if (!alive) return;
        setPlans(Array.isArray(res.billing_plans) ? res.billing_plans : []);
      })
      .catch((e) => {
        if (!alive) return;
        if (e instanceof ApiError) {
          setPlansError(e.message);
        } else {
          setPlansError(e instanceof Error ? e.message : "Failed to load plans.");
        }
      })
      .finally(() => {
        if (!alive) return;
        setPlansLoading(false);
      });

    return () => {
      alive = false;
    };
  }, []);

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

  const resolvedCurrency = useMemo(() => {
    const next = normalizeCurrency(currency || "USD");
    if (availableCurrencies.length === 0) return next;
    return availableCurrencies.includes(next) ? next : availableCurrencies[0];
  }, [availableCurrencies, currency]);

  const interval = useMemo(() => {
    const desired = billing === "annual" ? "year" : "month";
    if (availableIntervals.includes(desired)) return desired;
    return availableIntervals[0] ?? desired;
  }, [availableIntervals, billing]);

  const hasYearly = availableIntervals.includes("year");

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
    const cur = normalizeCurrency(resolvedCurrency);
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

  useEffect(() => {
    if (auth.loading) return;
    if (!auth.isAuthenticated) return;

    if (auth.isAdmin) {
      router.replace("/admin");
      return;
    }

    router.replace("/app");
  }, [auth.isAdmin, auth.isAuthenticated, auth.loading, router]);

  return (
    <div className="min-h-screen text-[var(--rb-text)] [background:radial-gradient(1200px_circle_at_20%_0%,color-mix(in_srgb,var(--rb-blue),white_88%)_0%,transparent_55%),radial-gradient(900px_circle_at_80%_15%,color-mix(in_srgb,var(--rb-orange),white_86%)_0%,transparent_60%),var(--rb-surface)]">
      <header className="sticky top-0 z-20 border-b border-[var(--rb-border)] bg-white/70 backdrop-blur">
        <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
          <div className="flex items-center gap-3">
            <Link href="/" className="font-semibold tracking-tight text-[var(--rb-text)]">
              99smartx
            </Link>
            <Badge variant="info" className="hidden sm:inline-flex">
              Repair shop ops, modernized
            </Badge>
          </div>

          <nav className="hidden items-center gap-6 text-sm text-zinc-600 md:flex">
            <Link href="#features" className="hover:text-[var(--rb-text)]">
              Features
            </Link>
            <Link href="#pricing" className="hover:text-[var(--rb-text)]">
              Pricing
            </Link>
            <Link href="#faq" className="hover:text-[var(--rb-text)]">
              FAQ
            </Link>
          </nav>

          <div className="flex items-center gap-2">
            <Button variant="ghost" onClick={() => router.push("/login")}
              aria-label="Go to login"
            >
              Login
            </Button>
            <Button variant="primary" onClick={() => router.push("/register")}
              aria-label="Go to registration"
            >
              Start now
            </Button>
          </div>
        </div>
      </header>

      <main>
        <section className="mx-auto max-w-6xl px-4 pt-12 sm:pt-16">
          <div className="grid items-center gap-10 lg:grid-cols-2">
            <div>
              <div className="inline-flex items-center gap-2 rounded-full border border-[var(--rb-border)] bg-white/70 px-3 py-1 text-xs text-zinc-700">
                <span className="h-2 w-2 rounded-full bg-[var(--rb-orange)]" />
                Built for repair shops that hate chaos
              </div>

              <h1 className="mt-5 text-balance text-4xl font-semibold tracking-tight text-[var(--rb-text)] sm:text-5xl">
                A sharper system for tickets, devices, and customer trust.
              </h1>
              <p className="mt-4 max-w-xl text-pretty text-base text-zinc-700">
                99smartx brings intake, repair status, approvals, and updates into one clean workflow.
                Your team moves faster. Your customers feel informed.
              </p>

              <div className="mt-6 grid gap-3 text-sm text-zinc-700 sm:grid-cols-2">
                <div className="flex items-start gap-2">
                  <CheckIcon className="mt-0.5 text-[var(--rb-blue)]" />
                  <span>Customer portal with case-number access (no login required)</span>
                </div>
                <div className="flex items-start gap-2">
                  <CheckIcon className="mt-0.5 text-[var(--rb-blue)]" />
                  <span>Structured intake, checklists, and technician assignments</span>
                </div>
                <div className="flex items-start gap-2">
                  <CheckIcon className="mt-0.5 text-[var(--rb-blue)]" />
                  <span>Inventory + parts tracking that doesn’t fight you</span>
                </div>
                <div className="flex items-start gap-2">
                  <CheckIcon className="mt-0.5 text-[var(--rb-blue)]" />
                  <span>Automated updates that cut “Any news?” calls</span>
                </div>
              </div>

              <div className="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                <Button size="lg" variant="secondary" onClick={() => router.push("/register")}
                  aria-label="Create an account"
                >
                  Start your workspace
                </Button>
                <Button size="lg" variant="outline" onClick={() => router.push("/login")}
                  aria-label="Sign in"
                >
                  Sign in
                </Button>
                <div className="text-xs text-zinc-600">
                  Pricing below reflects your configured billing plans.
                </div>
              </div>
            </div>

            <div className="relative">
              <div className="absolute -inset-6 -z-10 rounded-[28px] bg-[radial-gradient(600px_circle_at_30%_20%,color-mix(in_srgb,var(--rb-blue),white_80%)_0%,transparent_60%),radial-gradient(600px_circle_at_70%_70%,color-mix(in_srgb,var(--rb-orange),white_82%)_0%,transparent_60%)] blur-[2px]" />
              <Card className="overflow-hidden border-[color:color-mix(in_srgb,var(--rb-border),transparent_20%)]">
                <CardHeader className="bg-[color:color-mix(in_srgb,white,var(--rb-blue)_4%)]">
                  <div className="flex items-start justify-between gap-6">
                    <div>
                      <CardTitle className="text-base">Today’s workload</CardTitle>
                      <CardDescription>See what matters, at a glance.</CardDescription>
                    </div>
                    <Badge variant="success">Live</Badge>
                  </div>
                </CardHeader>
                <CardContent>
                  <div className="grid gap-3">
                    <div className="rounded-lg border border-[var(--rb-border)] bg-white p-3 mt-4">
                      <div className="flex items-center justify-between gap-3">
                        <div className="min-w-0">
                          <div className="truncate text-sm font-medium text-[var(--rb-text)]">
                            MacBook Pro (Battery service)
                          </div>
                          <div className="mt-0.5 text-xs text-zinc-600">Case #RB-10482 · Waiting on approval</div>
                        </div>
                        <Badge variant="warning">Pending</Badge>
                      </div>
                    </div>
                    <div className="rounded-lg border border-[var(--rb-border)] bg-white p-3">
                      <div className="flex items-center justify-between gap-3">
                        <div className="min-w-0">
                          <div className="truncate text-sm font-medium text-[var(--rb-text)]">
                            iPhone 13 (Screen + seal)
                          </div>
                          <div className="mt-0.5 text-xs text-zinc-600">Case #RB-10476 · Assigned to Tech A</div>
                        </div>
                        <Badge variant="info">In progress</Badge>
                      </div>
                    </div>
                    <div className="rounded-lg border border-[var(--rb-border)] bg-white p-3">
                      <div className="flex items-center justify-between gap-3">
                        <div className="min-w-0">
                          <div className="truncate text-sm font-medium text-[var(--rb-text)]">
                            Custom PC (No boot)
                          </div>
                          <div className="mt-0.5 text-xs text-zinc-600">Case #RB-10463 · Ready for pickup</div>
                        </div>
                        <Badge variant="success">Done</Badge>
                      </div>
                    </div>
                    <div className="rounded-lg border border-[var(--rb-border)] bg-[var(--rb-surface-muted)] p-3">
                      <div className="flex items-center justify-between gap-3">
                        <div className="min-w-0">
                          <div className="truncate text-sm font-medium text-[var(--rb-text)]">Automations</div>
                          <div className="mt-0.5 text-xs text-zinc-600">
                            Status changes trigger customer updates automatically.
                          </div>
                        </div>
                        <BoltIcon className="text-[var(--rb-orange)]" />
                      </div>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>
          </div>
        </section>

        <section id="features" className="mx-auto max-w-6xl px-4 pt-14 sm:pt-20">
          <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
              <h2 className="text-2xl font-semibold text-[var(--rb-text)]">Features that feel like an unfair advantage</h2>
              <p className="mt-2 max-w-2xl text-sm text-zinc-700">
                Designed to reduce friction for your staff and uncertainty for customers.
              </p>
            </div>
            <div className="flex items-center gap-2">
              <Badge variant="info">Fast</Badge>
              <Badge variant="warning">Human</Badge>
              <Badge variant="success">Reliable</Badge>
            </div>
          </div>

          <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Card>
              <CardHeader>
                <div className="flex items-center gap-2 text-[var(--rb-blue)]">
                  <SparkIcon />
                  <CardTitle className="text-base">Intake that’s actually structured</CardTitle>
                </div>
                <CardDescription>Capture device details, symptoms, and condition with consistent checklists.</CardDescription>
              </CardHeader>
            </Card>

            <Card>
              <CardHeader>
                <div className="flex items-center gap-2 text-[var(--rb-blue)]">
                  <BoltIcon />
                  <CardTitle className="text-base">Status updates on autopilot</CardTitle>
                </div>
                <CardDescription>Trigger emails when status changes so customers stay calm and informed.</CardDescription>
              </CardHeader>
            </Card>

            <Card>
              <CardHeader>
                <div className="flex items-center gap-2 text-[var(--rb-blue)]">
                  <ShieldIcon />
                  <CardTitle className="text-base">Controlled access</CardTitle>
                </div>
                <CardDescription>Customer portal lets clients check progress with a case number—no password reset drama.</CardDescription>
              </CardHeader>
            </Card>

            <Card>
              <CardHeader>
                <div className="flex items-center gap-2 text-[var(--rb-blue)]">
                  <SparkIcon />
                  <CardTitle className="text-base">Parts and inventory tracking</CardTitle>
                </div>
                <CardDescription>Track parts used per job and know what’s low before it hurts lead times.</CardDescription>
              </CardHeader>
            </Card>

            <Card>
              <CardHeader>
                <div className="flex items-center gap-2 text-[var(--rb-blue)]">
                  <BoltIcon />
                  <CardTitle className="text-base">Assignments that stick</CardTitle>
                </div>
                <CardDescription>Clear ownership, internal notes, and handoffs without losing context.</CardDescription>
              </CardHeader>
            </Card>

            <Card>
              <CardHeader>
                <div className="flex items-center gap-2 text-[var(--rb-blue)]">
                  <ShieldIcon />
                  <CardTitle className="text-base">Brand-ready UX</CardTitle>
                </div>
                <CardDescription>Modern, accessible UI that feels premium the moment customers see it.</CardDescription>
              </CardHeader>
            </Card>
          </div>
        </section>

        <section id="pricing" className="mx-auto max-w-6xl px-4 pt-14 sm:pt-20">
          <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
              <h2 className="text-2xl font-semibold text-[var(--rb-text)]">Per-user pricing that scales with you</h2>
              <p className="mt-2 max-w-2xl text-sm text-zinc-700">
                Pricing below is based on your configured billing plans.
              </p>
            </div>
            <div className="flex items-center justify-between gap-2 rounded-full border border-[var(--rb-border)] bg-white/70 p-1 text-sm">
              <button
                type="button"
                onClick={() => setBilling("monthly")}
                className={
                  "rounded-full px-3 py-1 transition-colors " +
                  (billing === "monthly"
                    ? "bg-[var(--rb-blue)] text-white"
                    : "text-zinc-700 hover:bg-[var(--rb-surface-muted)]")
                }
              >
                Monthly
              </button>
              <button
                type="button"
                onClick={() => {
                  if (!hasYearly) return;
                  setBilling("annual");
                }}
                className={
                  "rounded-full px-3 py-1 transition-colors " +
                  (billing === "annual"
                    ? "bg-[var(--rb-blue)] text-white"
                    : "text-zinc-700 hover:bg-[var(--rb-surface-muted)]") +
                  (!hasYearly ? " opacity-50 cursor-not-allowed" : "")
                }
                aria-disabled={!hasYearly}
              >
                Annual
              </button>
              {hasYearly ? (
                <Badge variant="warning" className="mr-1">
                  2 months free
                </Badge>
              ) : null}
            </div>
          </div>

          {plansError ? (
            <div className="mt-6">
              <Alert variant="danger" title="Unable to load pricing">
                {plansError}
              </Alert>
            </div>
          ) : null}

          <div className="mt-8 grid gap-4 lg:grid-cols-3">
            {plansLoading ? (
              <div className="lg:col-span-3">
                <div className="rounded-[var(--rb-radius-lg)] border border-dashed border-[var(--rb-border)] bg-white/70 p-8 text-center">
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Loading plans...</div>
                </div>
              </div>
            ) : activePlanVersions.length === 0 ? (
              <div className="lg:col-span-3">
                <div className="rounded-[var(--rb-radius-lg)] border border-dashed border-[var(--rb-border)] bg-white/70 p-8 text-center">
                  <div className="text-sm font-semibold text-[var(--rb-text)]">No plans available</div>
                  <div className="mt-1 text-sm text-zinc-600">Ask your admin to configure billing plans.</div>
                </div>
              </div>
            ) : (
              activePlanVersions.map(({ plan, version }) => {
                const selected = selectPriceForPlan(version);
                const recommended = isRecommended(selected);
                const priceLabel = selected ? formatMoney(selected.amount_cents, normalizeCurrency(selected.currency)) : "Not available";
                const entitlements = Array.isArray(version.entitlements) ? version.entitlements : [];
                const visibleEntitlements = entitlements.filter((e) => e.definition?.name);

                return (
                  <BillingPlanCard
                    key={plan.id}
                    plan={plan}
                    version={version}
                    selectedPrice={selected}
                    currency={resolvedCurrency}
                    interval={interval}
                    recommended={recommended}
                    priceLabel={priceLabel}
                    intervalLabel={selected ? intervalLabel(version, selected.interval) : interval}
                    visibleEntitlements={visibleEntitlements}
                    submitting={false}
                    onSelect={() => router.push("/register")}
                    actionLabel="Start now"
                  />
                );
              })
            )}
          </div>
        </section>

        <section id="faq" className="mx-auto max-w-6xl px-4 pb-16 pt-14 sm:pb-24 sm:pt-20">
          <div className="grid gap-8 lg:grid-cols-2">
            <div>
              <h2 className="text-2xl font-semibold text-[var(--rb-text)]">FAQ</h2>
              <p className="mt-2 max-w-xl text-sm text-zinc-700">
                Quick answers to the questions customers and shop owners always ask.
              </p>
              <div className="mt-6 rounded-[var(--rb-radius-xl)] border border-[var(--rb-border)] bg-white/70 p-5">
                <div className="text-sm font-medium text-[var(--rb-text)]">Want to see it with your workflow?</div>
                <div className="mt-1 text-sm text-zinc-700">
                  Create a workspace and explore the dashboards.
                </div>
                <div className="mt-4 flex flex-col gap-2 sm:flex-row">
                  <Button variant="secondary" onClick={() => router.push("/register")}>Start now</Button>
                  <Button variant="outline" onClick={() => router.push("/login")}>Sign in</Button>
                </div>
              </div>
            </div>

            <div className="grid gap-4">
              <Card>
                <CardHeader>
                  <CardTitle className="text-base">Can customers check repair status without an account?</CardTitle>
                  <CardDescription>
                    Yes. The portal supports case-number-based access so customers can get updates quickly.
                  </CardDescription>
                </CardHeader>
              </Card>
              <Card>
                <CardHeader>
                  <CardTitle className="text-base">Is pricing per user?</CardTitle>
                  <CardDescription>
                    Yes. Pricing is shown per user per month, based on your configured billing plans.
                  </CardDescription>
                </CardHeader>
              </Card>
              <Card>
                <CardHeader>
                  <CardTitle className="text-base">Can we start simple and upgrade later?</CardTitle>
                  <CardDescription>
                    Absolutely. Your data and workflows stay intact as you move between plans.
                  </CardDescription>
                </CardHeader>
              </Card>
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
  );
}
