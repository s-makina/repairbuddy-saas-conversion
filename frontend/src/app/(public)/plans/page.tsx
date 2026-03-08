"use client";

import Link from "next/link";
import { useEffect, useMemo, useState } from "react";
import { getPublicBillingPlans } from "@/lib/publicBilling";
import type { BillingPlan, PlanEntitlement } from "@/lib/types";

const WrenchIcon = () => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
    <path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z" />
  </svg>
);

interface Plan {
  id: string;
  name: string;
  desc: string;
  monthly: number;
  annual: number;
  hasAnnual: boolean;
  featured: boolean;
  iconBg: string;
  iconColor: string;
  icon: React.ReactNode;
  features: { label: string; included: boolean }[];
}

interface CompRow {
  feature: string;
  values: (string | boolean)[];
}

const ICON_STYLES: { bg: string; color: string; icon: React.ReactNode }[] = [
  {
    bg: "var(--blue-bg)", color: "var(--blue)",
    icon: <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>,
  },
  {
    bg: "var(--orange-bg)", color: "var(--orange)",
    icon: <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>,
  },
  {
    bg: "var(--purple-bg)", color: "var(--purple)",
    icon: <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>,
  },
];

function formatEntitlement(e: PlanEntitlement): { label: string; included: boolean } {
  const def = e.definition;
  if (!def) return { label: String(e.value_json), included: true };
  const val = e.value_json;
  if (def.value_type === "boolean") {
    return { label: def.name, included: val === true || val === 1 };
  }
  if (def.value_type === "integer") {
    const num = Number(val);
    if (num < 0) return { label: `Unlimited ${def.name.toLowerCase()}`, included: true };
    return { label: `Up to ${num} ${def.name.toLowerCase()}`, included: true };
  }
  return { label: `${def.name}: ${String(val)}`, included: true };
}

function sortPlansByPrice(apiPlans: BillingPlan[]): BillingPlan[] {
  return [...apiPlans].sort((a, b) => {
    const aV = a.versions?.find(v => v.status === "active") ?? a.versions?.[0];
    const bV = b.versions?.find(v => v.status === "active") ?? b.versions?.[0];
    const aP = aV?.prices?.find(p => p.interval === "month") ?? aV?.prices?.[0];
    const bP = bV?.prices?.find(p => p.interval === "month") ?? bV?.prices?.[0];
    return (aP?.amount_cents ?? 0) - (bP?.amount_cents ?? 0);
  });
}

/** Returns display plans and the filtered+sorted raw API plans in matching order */
function processApiPlans(sorted: BillingPlan[]): { plans: Plan[]; raw: BillingPlan[] } {
  const plans: Plan[] = [];
  const raw: BillingPlan[] = [];

  for (let i = 0; i < sorted.length; i++) {
    const apiPlan = sorted[i];
    const version = apiPlan.versions?.find(v => v.status === "active") ?? apiPlan.versions?.[0];
    if (!version) continue;
    const monthlyPrice = version.prices?.find(p => p.interval === "month") ?? version.prices?.[0];
    const annualPrice = version.prices?.find(p => p.interval === "year");
    if (!monthlyPrice) continue;

    const monthly = monthlyPrice.amount_cents / 100;
    const annualTotal = annualPrice?.amount_cents ?? null;
    const annualMonthly = annualTotal !== null ? Math.round(annualTotal / 100 / 12) : Math.round(monthly * 0.8);
    const features = (version.entitlements ?? []).map(formatEntitlement);
    const style = ICON_STYLES[plans.length % ICON_STYLES.length];

    plans.push({
      id: apiPlan.code,
      name: apiPlan.name,
      desc: apiPlan.description ?? "",
      monthly,
      annual: annualMonthly,
      hasAnnual: annualPrice != null,
      featured: false,
      iconBg: style.bg,
      iconColor: style.color,
      icon: style.icon,
      features,
    });
    raw.push(apiPlan);
  }

  // Mark the middle plan as featured
  if (plans.length > 0) {
    plans[Math.floor((plans.length - 1) / 2)].featured = true;
  }
  return { plans, raw };
}

/**
 * Builds a comparison matrix from sorted raw API plans.
 * rows = union of all entitlement definitions (in first-appearance order)
 * values[i] = cell value for raw[i]
 */
function buildCompRows(raw: BillingPlan[]): CompRow[] {
  // Collect definition codes in order of first appearance across all plans
  const defOrder: string[] = [];
  const defNames = new Map<string, string>(); // code → display name
  // Per-plan entitlement lookup: planIndex → Map<defCode, entitlement>
  const planEntMaps: Map<string, PlanEntitlement>[] = raw.map(() => new Map());

  for (let i = 0; i < raw.length; i++) {
    const version = raw[i].versions?.find(v => v.status === "active") ?? raw[i].versions?.[0];
    for (const e of version?.entitlements ?? []) {
      if (!e.definition) continue;
      const code = e.definition.code;
      planEntMaps[i].set(code, e);
      if (!defNames.has(code)) {
        defOrder.push(code);
        defNames.set(code, e.definition.name);
      }
    }
  }

  return defOrder.map(code => ({
    feature: defNames.get(code)!,
    values: raw.map((_, i) => {
      const e = planEntMaps[i].get(code);
      if (!e || !e.definition) return false as string | boolean;
      const val = e.value_json;
      const def = e.definition;
      if (def.value_type === "boolean") return val === true || val === 1;
      if (def.value_type === "integer") {
        const num = Number(val);
        return num < 0 ? "Unlimited" : String(num);
      }
      return String(val);
    }),
  }));
}

const staticPlans: Plan[] = [
  {
    id: "starter",
    name: "Starter",
    desc: "Perfect for solo technicians and small shops just getting started.",
    monthly: 29, annual: 23, hasAnnual: true,
    featured: false,
    iconBg: "var(--blue-bg)", iconColor: "var(--blue)",
    icon: <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>,
    features: [
      { label: "Up to 50 repairs/month", included: true },
      { label: "1 staff member", included: true },
      { label: "Online booking page", included: true },
      { label: "Basic invoicing", included: true },
      { label: "Email notifications", included: true },
      { label: "Email support", included: true },
      { label: "Inventory management", included: false },
      { label: "Customer portal", included: false },
    ],
  },
  {
    id: "professional",
    name: "Professional",
    desc: "Best for growing repair shops that want to scale operations.",
    monthly: 79, annual: 63, hasAnnual: true,
    featured: true,
    iconBg: "var(--orange-bg)", iconColor: "var(--orange)",
    icon: <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>,
    features: [
      { label: "Unlimited repairs", included: true },
      { label: "Up to 5 staff members", included: true },
      { label: "Online booking + SMS reminders", included: true },
      { label: "Advanced invoicing + payments", included: true },
      { label: "Inventory management", included: true },
      { label: "Customer portal", included: true },
      { label: "Reports & analytics", included: true },
      { label: "Priority support", included: true },
    ],
  },
  {
    id: "enterprise",
    name: "Enterprise",
    desc: "For multi-location businesses that need full control and customisation.",
    monthly: 149, annual: 119, hasAnnual: true,
    featured: false,
    iconBg: "var(--purple-bg)", iconColor: "var(--purple)",
    icon: <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>,
    features: [
      { label: "Everything in Professional", included: true },
      { label: "Unlimited staff members", included: true },
      { label: "Multi-location management", included: true },
      { label: "Advanced analytics & exports", included: true },
      { label: "API access", included: true },
      { label: "Custom branding", included: true },
      { label: "SSO / SAML integration", included: true },
      { label: "Dedicated account manager", included: true },
    ],
  },
];

// Static fallback comparison rows (used when API data unavailable)
const staticCompRows: CompRow[] = [
  { feature: "Monthly Repairs",       values: ["50",     "Unlimited", "Unlimited"] },
  { feature: "Staff Members",         values: ["1",      "5",         "Unlimited"] },
  { feature: "Online Booking",        values: [true,     true,        true] },
  { feature: "SMS Reminders",         values: [false,    true,        true] },
  { feature: "Invoicing",             values: ["Basic",  "Advanced",  "Advanced"] },
  { feature: "Online Payments",       values: [false,    true,        true] },
  { feature: "Inventory Management",  values: [false,    true,        true] },
  { feature: "Customer Portal",       values: [false,    true,        true] },
  { feature: "Reports & Analytics",   values: ["Basic",  "Standard",  "Advanced"] },
  { feature: "Multi-location",        values: [false,    false,       true] },
  { feature: "API Access",            values: [false,    false,       true] },
  { feature: "Custom Branding",       values: [false,    false,       true] },
  { feature: "Support",               values: ["Email",  "Priority",  "Dedicated Manager"] },
];

const faqs = [
  { q: "Is there a free trial?", a: "Yes! All plans come with a 14-day free trial. No credit card required to start." },
  { q: "Can I change my plan later?", a: "Absolutely. Upgrade or downgrade anytime — changes take effect on your next billing cycle." },
  { q: "What payment methods do you accept?", a: "We accept all major credit and debit cards (Visa, Mastercard, Amex) as well as ACH bank transfers for annual plans." },
  { q: "Is there a setup fee?", a: "No setup fees, ever. You only pay the listed monthly or annual price." },
  { q: "Can I cancel anytime?", a: "Yes. Cancel anytime with no penalties. If you cancel, you'll retain access until the end of your billing period." },
];

function CellValue({ value }: { value: string | boolean }) {
  if (typeof value === "boolean") {
    return value
      ? <span className="check">✓</span>
      : <span className="cross">—</span>;
  }
  return <>{value}</>;
}

export default function PlansV2() {
  const [annual, setAnnual] = useState(false);
  const [openFaq, setOpenFaq] = useState<number | null>(null);
  const [plans, setPlans] = useState<Plan[]>(staticPlans);
  const [rawForComp, setRawForComp] = useState<BillingPlan[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let alive = true;
    getPublicBillingPlans()
      .then((res) => {
        if (!alive) return;
        const sorted = sortPlansByPrice(res.billing_plans ?? []);
        const { plans: mapped, raw } = processApiPlans(sorted);
        if (mapped.length > 0) {
          setPlans(mapped);
          setRawForComp(raw);
        }
      })
      .catch(() => {
        // silently fall back to static plans
      })
      .finally(() => {
        if (alive) setLoading(false);
      });
    return () => { alive = false; };
  }, []);

  const hasAnnualOption = plans.some(p => p.hasAnnual);

  const compRows = useMemo<CompRow[]>(() => {
    if (rawForComp.length > 0) return buildCompRows(rawForComp);
    return staticCompRows;
  }, [rawForComp]);

  return (
    <>
      {/* NAVBAR */}
      <nav className="sticky-nav">
        <div className="nav-inner">
          <Link href="/" className="nav-brand">
            <div className="logo-mark"><WrenchIcon /></div>
            <span className="brand-name">99SmartX</span>
          </Link>
          <div className="nav-actions">
            <Link href="/login" className="btn btn-ghost">Log In</Link>
            <Link href="/" className="btn btn-outline" style={{ fontSize: 13, padding: "8px 16px" }}>← Back to Home</Link>
          </div>
        </div>
      </nav>

      {/* HEADER */}
      <div className="page-header">
        <h1>Choose the right plan<br />for your shop</h1>
        <p>All plans come with a 14-day free trial. No credit card required. Upgrade, downgrade, or cancel anytime.</p>

        {hasAnnualOption && (
          <div className="toggle-wrap">
            <span
              className={`toggle-label${!annual ? " active" : ""}`}
              onClick={() => setAnnual(false)}
            >
              Monthly
            </span>
            <button
              className={`toggle-switch${annual ? " on" : ""}`}
              onClick={() => setAnnual(!annual)}
              aria-pressed={annual}
              aria-label="Toggle annual billing"
            />
            <span
              className={`toggle-label${annual ? " active" : ""}`}
              onClick={() => setAnnual(true)}
            >
              Annual
            </span>
            <span className="save-badge">Save 20%</span>
          </div>
        )}
      </div>

      {/* PRICING CARDS */}
      <div className="pricing-section">
        {loading ? (
          <div className="plans-grid">
            {[1, 2, 3].map((i) => (
              <div key={i} className="pc-card" style={{ minHeight: 420, opacity: 0.4, background: "var(--surface-2)", borderRadius: "var(--r-lg)" }} />
            ))}
          </div>
        ) : (
          <div className="plans-grid">
            {plans.map((p) => (
              <div key={p.id} className={`pc-card${p.featured ? " featured" : ""}`}>
                {p.featured && <div className="pc-popular">⭐ MOST POPULAR</div>}
                <div className="pc-body">
                  <div className="pc-icon" style={{ background: p.iconBg, color: p.iconColor }}>{p.icon}</div>
                  <div className="pc-name">{p.name}</div>
                  <div className="pc-desc">{p.desc}</div>
                  <div className="pc-price">
                    <span className="pc-amount">${annual && p.hasAnnual ? p.annual : p.monthly}</span>
                    <span className="pc-period">/month</span>
                  </div>
                  <div className="pc-annual-note">
                    {annual && p.hasAnnual
                      ? `$${p.annual * 12}/year billed annually`
                      : "Billed monthly"}
                  </div>
                  <Link
                    href={`/register?plan=${p.id}`}
                    className={`btn pc-cta ${p.featured ? "btn-primary" : "btn-outline"}`}
                  >
                    Start Free Trial
                  </Link>
                  <ul className="pc-features">
                    {p.features.map((f) => (
                      <li key={f.label} className={f.included ? "" : "disabled"}>
                        {f.included ? (
                          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7" />
                          </svg>
                        ) : (
                          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M18 12H6" />
                          </svg>
                        )}
                        {f.label}
                      </li>
                    ))}
                  </ul>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* COMPARISON TABLE */}
      {compRows.length > 0 && (
        <div className="comparison-section">
          <div className="comparison-header">
            <h2>Detailed feature comparison</h2>
            <p>See exactly what's included in each plan</p>
          </div>
          <table className="comp-table">
            <thead>
              <tr>
                <th>Feature</th>
                {plans.map((p) => (
                  <th key={p.id} className={p.featured ? "featured-col" : ""}>{p.name}</th>
                ))}
              </tr>
            </thead>
            <tbody>
              {compRows.map((row) => (
                <tr key={row.feature}>
                  <td>{row.feature}</td>
                  {row.values.map((val, i) => (
                    <td key={i} className={plans[i]?.featured ? "feat-col" : ""}>
                      <CellValue value={val} />
                    </td>
                  ))}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* FAQ */}
      <div className="faq-section">
        <div className="faq-header">
          <h2>Frequently asked questions</h2>
          <p>Everything you need to know about our plans</p>
        </div>
        {faqs.map((f, i) => (
          <div key={i} className="faq-item">
            <button
              className={`faq-q${openFaq === i ? " open" : ""}`}
              onClick={() => setOpenFaq(openFaq === i ? null : i)}
            >
              {f.q}
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div className={`faq-a${openFaq === i ? " open" : ""}`}>
              <div className="faq-a-inner">{f.a}</div>
            </div>
          </div>
        ))}
      </div>

      {/* FOOTER */}
      <footer className="footer" style={{ textAlign: "center" }}>
        <p style={{ fontSize: 13, color: "var(--text-3)" }}>
          © 2026 99SmartX. All rights reserved. |{" "}
          <a href="#" style={{ color: "var(--orange)", fontWeight: 600 }}>Privacy Policy</a>{" "}·{" "}
          <a href="#" style={{ color: "var(--orange)", fontWeight: 600 }}>Terms of Service</a>
        </p>
      </footer>
    </>
  );
}
