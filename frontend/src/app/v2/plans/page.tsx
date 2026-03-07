"use client";

import Link from "next/link";
import { useState } from "react";

const WrenchIcon = () => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
    <path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z" />
  </svg>
);

const CheckIcon = ({ className }: { className?: string }) => (
  <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" className={className}>
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7" />
  </svg>
);

const MinusIcon = () => (
  <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M18 12H6" />
  </svg>
);

interface Plan {
  id: string;
  name: string;
  desc: string;
  monthly: number;
  annual: number;
  featured: boolean;
  iconBg: string;
  iconColor: string;
  icon: React.ReactNode;
  features: { label: string; included: boolean }[];
}

const plans: Plan[] = [
  {
    id: "starter",
    name: "Starter",
    desc: "Perfect for solo technicians and small shops just getting started.",
    monthly: 29, annual: 23,
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
    monthly: 79, annual: 63,
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
    monthly: 149, annual: 119,
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

const comparisonRows = [
  { feature: "Monthly Repairs", starter: "50", professional: "Unlimited", enterprise: "Unlimited" },
  { feature: "Staff Members", starter: "1", professional: "5", enterprise: "Unlimited" },
  { feature: "Online Booking", starter: true, professional: true, enterprise: true },
  { feature: "SMS Reminders", starter: false, professional: true, enterprise: true },
  { feature: "Invoicing", starter: "Basic", professional: "Advanced", enterprise: "Advanced" },
  { feature: "Online Payments", starter: false, professional: true, enterprise: true },
  { feature: "Inventory Management", starter: false, professional: true, enterprise: true },
  { feature: "Customer Portal", starter: false, professional: true, enterprise: true },
  { feature: "Reports & Analytics", starter: "Basic", professional: "Standard", enterprise: "Advanced" },
  { feature: "Multi-location", starter: false, professional: false, enterprise: true },
  { feature: "API Access", starter: false, professional: false, enterprise: true },
  { feature: "Custom Branding", starter: false, professional: false, enterprise: true },
  { feature: "Support", starter: "Email", professional: "Priority", enterprise: "Dedicated Manager" },
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

  return (
    <>
      {/* NAVBAR */}
      <nav className="sticky-nav">
        <div className="nav-inner">
          <Link href="/v2" className="nav-brand">
            <div className="logo-mark"><WrenchIcon /></div>
            <span className="brand-name">99SmartX</span>
          </Link>
          <div className="nav-actions">
            <Link href="/v2/login" className="btn btn-ghost">Log In</Link>
            <Link href="/v2" className="btn btn-outline" style={{ fontSize: 13, padding: "8px 16px" }}>← Back to Home</Link>
          </div>
        </div>
      </nav>

      {/* HEADER */}
      <div className="page-header">
        <h1>Choose the right plan<br />for your shop</h1>
        <p>All plans come with a 14-day free trial. No credit card required. Upgrade, downgrade, or cancel anytime.</p>

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
      </div>

      {/* PRICING CARDS */}
      <div className="pricing-section">
        <div className="plans-grid">
          {plans.map((p) => (
            <div key={p.id} className={`pc-card${p.featured ? " featured" : ""}`}>
              {p.featured && <div className="pc-popular">⭐ MOST POPULAR</div>}
              <div className="pc-body">
                <div className="pc-icon" style={{ background: p.iconBg, color: p.iconColor }}>{p.icon}</div>
                <div className="pc-name">{p.name}</div>
                <div className="pc-desc">{p.desc}</div>
                <div className="pc-price">
                  <span className="pc-amount">${annual ? p.annual : p.monthly}</span>
                  <span className="pc-period">/month</span>
                </div>
                <div className="pc-annual-note">
                  {annual ? `$${p.annual * 12}/year billed annually` : "Billed monthly"}
                </div>
                <Link
                  href={`/v2/register?plan=${p.id}`}
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
      </div>

      {/* COMPARISON TABLE */}
      <div className="comparison-section">
        <div className="comparison-header">
          <h2>Detailed feature comparison</h2>
          <p>See exactly what's included in each plan</p>
        </div>
        <table className="comp-table">
          <thead>
            <tr>
              <th>Feature</th>
              <th>Starter</th>
              <th className="featured-col">Professional</th>
              <th>Enterprise</th>
            </tr>
          </thead>
          <tbody>
            {comparisonRows.map((row) => (
              <tr key={row.feature}>
                <td>{row.feature}</td>
                <td><CellValue value={row.starter} /></td>
                <td className="feat-col"><CellValue value={row.professional} /></td>
                <td><CellValue value={row.enterprise} /></td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

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
