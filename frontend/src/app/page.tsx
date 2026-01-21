"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import React, { useEffect, useMemo, useState } from "react";
import { useAuth } from "@/lib/auth";
import { Button } from "@/components/ui/Button";
import { Badge } from "@/components/ui/Badge";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/Card";

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

  const pricing = useMemo(
    () => [
      {
        name: "Starter",
        blurb: "For solo techs and small teams starting to scale.",
        pricePerUserMonthly: 9,
        highlight: false,
        items: [
          "Tickets & customer profiles",
          "Device intake + checklists",
          "Email updates",
          "Customer portal (case-number access)",
        ],
      },
      {
        name: "Pro",
        blurb: "The sweet spot for busy shops with multiple techs.",
        pricePerUserMonthly: 19,
        highlight: true,
        items: [
          "Everything in Starter",
          "Smart statuses + SLA reminders",
          "Internal notes + assignments",
          "Inventory & parts tracking",
          "Automations & templates",
        ],
      },
      {
        name: "Scale",
        blurb: "For high-volume operations and multi-location workflows.",
        pricePerUserMonthly: 39,
        highlight: false,
        items: [
          "Everything in Pro",
          "Advanced permissions",
          "Custom fields & workflows",
          "Priority support",
          "SSO (planned)",
        ],
      },
    ],
    [],
  );

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
                  Sample pricing below. Update anytime.
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
                Simple tiers. Clear value. Swap the sample prices later.
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
                onClick={() => setBilling("annual")}
                className={
                  "rounded-full px-3 py-1 transition-colors " +
                  (billing === "annual"
                    ? "bg-[var(--rb-blue)] text-white"
                    : "text-zinc-700 hover:bg-[var(--rb-surface-muted)]")
                }
              >
                Annual
              </button>
              <Badge variant="warning" className="mr-1">
                2 months free
              </Badge>
            </div>
          </div>

          <div className="mt-8 grid gap-4 lg:grid-cols-3">
            {pricing.map((tier) => {
              const monthly = tier.pricePerUserMonthly;
              const displayed = billing === "annual" ? Math.round((monthly * 10 * 100) / 12) / 100 : monthly;
              const priceLabel = billing === "annual" ? "per user / month (billed annually)" : "per user / month";

              return (
                <Card
                  key={tier.name}
                  className={
                    tier.highlight
                      ? "relative border-[color:color-mix(in_srgb,var(--rb-orange),white_30%)] shadow-[0_12px_30px_rgba(6,62,112,0.12)]"
                      : ""
                  }
                >
                  {tier.highlight ? (
                    <div className="absolute right-4 top-4">
                      <Badge variant="warning">Most popular</Badge>
                    </div>
                  ) : null}

                  <CardHeader>
                    <CardTitle className="text-base">{tier.name}</CardTitle>
                    <CardDescription>{tier.blurb}</CardDescription>
                  </CardHeader>
                  <CardContent>
                    <div className="flex items-end gap-2">
                      <div className="text-4xl font-semibold tracking-tight text-[var(--rb-text)]">
                        ${displayed}
                      </div>
                      <div className="pb-1 text-xs text-zinc-600">{priceLabel}</div>
                    </div>

                    <div className="mt-4 grid gap-2 text-sm text-zinc-700">
                      {tier.items.map((it) => (
                        <div key={it} className="flex items-start gap-2">
                          <CheckIcon className="mt-0.5 text-[var(--rb-orange)]" />
                          <span>{it}</span>
                        </div>
                      ))}
                    </div>

                    <div className="mt-6 grid gap-2">
                      <Button
                        variant={tier.highlight ? "secondary" : "primary"}
                        onClick={() => router.push("/register")}
                        className="w-full"
                      >
                        Choose {tier.name}
                      </Button>
                      <Button variant="outline" onClick={() => router.push("/login")}
                        className="w-full"
                      >
                        I already have an account
                      </Button>
                    </div>

                    <div className="mt-4 text-xs text-zinc-600">
                      Pricing shown is placeholder. Replace with your real pricing later.
                    </div>
                  </CardContent>
                </Card>
              );
            })}
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
                    Yes. Pricing is shown per user per month. The values on this page are placeholders.
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
