"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { getPublicBillingPlans } from "@/lib/publicBilling";
import type { BillingPlan } from "@/lib/types";

interface PricingPreviewPlan {
  id: string;
  name: string;
  desc: string;
  amount: string;
  period: string;
  featured: boolean;
  cta: string;
  features: string[];
}

const staticPricingPlans: PricingPreviewPlan[] = [
  {
    id: "starter",
    name: "Starter", desc: "Perfect for solo technicians", amount: "$29", period: "Billed monthly",
    featured: false, cta: "Choose Starter",
    features: ["Up to 50 repairs/month", "1 user", "Online booking", "Basic invoicing", "Email support"],
  },
  {
    id: "professional",
    name: "Professional", desc: "Best for growing shops", amount: "$79", period: "Billed monthly",
    featured: true, cta: "Choose Professional",
    features: ["Unlimited repairs", "Up to 5 users", "Inventory management", "Customer portal", "Priority support"],
  },
  {
    id: "enterprise",
    name: "Enterprise", desc: "For multi-location businesses", amount: "$149", period: "Billed monthly",
    featured: false, cta: "Choose Enterprise",
    features: ["Everything in Professional", "Unlimited users", "Multi-location support", "Advanced analytics", "Dedicated account manager"],
  },
];

function mapApiPricingPreview(apiPlans: BillingPlan[]): PricingPreviewPlan[] {
  const sorted = [...apiPlans].sort((a, b) => {
    const aV = a.versions?.find(v => v.status === "active") ?? a.versions?.[0];
    const bV = b.versions?.find(v => v.status === "active") ?? b.versions?.[0];
    const aP = aV?.prices?.find(p => p.interval === "month") ?? aV?.prices?.[0];
    const bP = bV?.prices?.find(p => p.interval === "month") ?? bV?.prices?.[0];
    return (aP?.amount_cents ?? 0) - (bP?.amount_cents ?? 0);
  });

  const result: PricingPreviewPlan[] = [];
  for (let i = 0; i < sorted.length; i++) {
    const plan = sorted[i];
    const version = plan.versions?.find(v => v.status === "active") ?? plan.versions?.[0];
    if (!version) continue;
    const monthlyPrice = version.prices?.find(p => p.interval === "month") ?? version.prices?.[0];
    if (!monthlyPrice) continue;
    const amount = `$${Math.round(monthlyPrice.amount_cents / 100)}`;
    const features = (version.entitlements ?? [])
      .filter(e => e.definition?.value_type !== "boolean" || e.value_json === true || e.value_json === 1)
      .slice(0, 5)
      .map(e => {
        const def = e.definition;
        if (!def) return String(e.value_json);
        if (def.value_type === "boolean") return def.name;
        if (def.value_type === "integer") {
          const num = Number(e.value_json);
          if (num < 0) return `Unlimited ${def.name.toLowerCase()}`;
          return `Up to ${num} ${def.name.toLowerCase()}`;
        }
        return `${def.name}: ${String(e.value_json)}`;
      });
    result.push({
      id: plan.code,
      name: plan.name,
      desc: plan.description ?? "",
      amount,
      period: "Billed monthly",
      featured: i === Math.floor((sorted.length - 1) / 2),
      cta: `Choose ${plan.name}`,
      features: features.length > 0 ? features : [`${plan.name} plan`],
    });
  }
  return result;
}

const WrenchIcon = () => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
    <path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z" />
  </svg>
);

const CheckIcon = () => (
  <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7" />
  </svg>
);

const StarIcon = () => (
  <svg viewBox="0 0 24 24" fill="currentColor">
    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
  </svg>
);

export default function LandingV2() {
  const [scrolled, setScrolled] = useState(false);
  const [openFaq, setOpenFaq] = useState<number | null>(null);
  const [pricingPlans, setPricingPlans] = useState<PricingPreviewPlan[]>(staticPricingPlans);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 10);
    window.addEventListener("scroll", onScroll);
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  useEffect(() => {
    let alive = true;
    getPublicBillingPlans()
      .then((res) => {
        if (!alive) return;
        const mapped = mapApiPricingPreview(res.billing_plans ?? []);
        if (mapped.length > 0) setPricingPlans(mapped);
      })
      .catch(() => {
        // silently fall back to static plans
      });
    return () => { alive = false; };
  }, []);

  const faqs = [
    { q: "Is there a long-term contract?", a: "No contracts at all. 99SmartX is month-to-month. You can upgrade, downgrade, or cancel at any time — no questions asked." },
    { q: "Do I need a credit card for the free trial?", a: "No. Start your 14-day free trial without a credit card. We'll remind you before the trial ends." },
    { q: "Can I import data from my current system?", a: "Yes! We offer free data migration assistance for all plans. Our team will help you import customers, repair history, and inventory." },
    { q: "How many staff accounts can I create?", a: "Starter includes 1 staff account, Professional includes up to 5, and Enterprise allows unlimited staff members." },
  ];

  return (
    <>
      {/* NAVBAR */}
      <nav className={`navbar${scrolled ? " scrolled" : ""}`}>
        <div className="nav-inner">
          <Link href="/v2" className="nav-brand">
            <div className="logo-mark"><WrenchIcon /></div>
            <span className="brand-name">99SmartX</span>
          </Link>
          <ul className="nav-links">
            <li><a href="#features">Features</a></li>
            <li><a href="#how-it-works">How It Works</a></li>
            <li><a href="#pricing">Pricing</a></li>
            <li><a href="#testimonials">Testimonials</a></li>
          </ul>
          <div className="nav-actions">
            {/* <Link href="/v2/login" className="btn btn-ghost">Log In</Link> */}
            <Link href="/v2/plans" className="btn btn-primary">Get Started</Link>
          </div>
        </div>
      </nav>

      {/* HERO */}
      <section className="hero">
        <div className="hero-inner">
          <div className="hero-badge">
            <div className="hero-badge-dot" />
            Now with AI-powered diagnostics
          </div>
          <h1>Run Your Repair Shop<br /><span className="accent">Smarter, Not Harder</span></h1>
          <p className="hero-sub">
            The all-in-one platform for repair businesses. Manage appointments, track repairs,
            send invoices, and delight your customers — all from one place.
          </p>
          <div className="hero-ctas">
            <Link href="/v2/plans" className="btn btn-primary btn-lg">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style={{ width: 16, height: 16 }}>
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
              Start Free Trial
            </Link>
            <a href="#how-it-works" className="btn btn-outline btn-lg">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style={{ width: 16, height: 16 }}>
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              See How It Works
            </a>
          </div>
          <div className="hero-stats">
            {[
              { val: "1,200+", lbl: "Repair Shops" },
              { val: "50,000+", lbl: "Repairs Tracked" },
              { val: "99.9%", lbl: "Uptime" },
              { val: "4.9★", lbl: "Average Rating" },
            ].map((s) => (
              <div key={s.lbl}>
                <div className="hero-stat-val">{s.val}</div>
                <div className="hero-stat-lbl">{s.lbl}</div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* FEATURES */}
      <section className="section" id="features">
        <div className="section-header center">
          <div className="section-label" style={{ justifyContent: "center" }}>Features</div>
          <h2 className="section-title">Everything you need to<br />grow your repair business</h2>
          <p className="section-sub">
            From scheduling to invoicing, 99SmartX handles the heavy lifting so you can
            focus on what you do best — fixing things.
          </p>
        </div>
        <div className="features-grid">
          {[
            {
              bg: "var(--orange-bg)", color: "var(--orange)",
              icon: <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>,
              title: "Smart Scheduling", desc: "Let customers book online 24/7. Automatic reminders reduce no-shows by up to 40%."
            },
            {
              bg: "var(--blue-bg)", color: "var(--blue)",
              icon: <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>,
              title: "Repair Tracking", desc: "Track every repair from intake to delivery with status updates and photo documentation."
            },
            {
              bg: "var(--green-bg)", color: "var(--green)",
              icon: <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>,
              title: "Invoicing & Payments", desc: "Create professional invoices in seconds. Accept online payments and track revenue in real time."
            },
            {
              bg: "var(--purple-bg)", color: "var(--purple)",
              icon: <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>,
              title: "Inventory Management", desc: "Track parts and supplies with low-stock alerts. Know exactly what you have on hand."
            },
            {
              bg: "var(--orange-bg)", color: "var(--orange)",
              icon: <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>,
              title: "Customer Portal", desc: "Give customers a branded portal to track repair status, view history, and book new appointments."
            },
            {
              bg: "var(--blue-bg)", color: "var(--blue)",
              icon: <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>,
              title: "Reports & Analytics", desc: "Understand your business performance with detailed dashboards and exportable reports."
            },
          ].map((f) => (
            <div key={f.title} className="feat-card">
              <div className="feat-icon" style={{ background: f.bg, color: f.color }}>{f.icon}</div>
              <h3>{f.title}</h3>
              <p>{f.desc}</p>
            </div>
          ))}
        </div>
      </section>

      {/* HOW IT WORKS */}
      <div className="steps-bg" id="how-it-works">
        <section className="section">
          <div className="section-header center">
            <div className="section-label" style={{ justifyContent: "center" }}>How It Works</div>
            <h2 className="section-title">Up and running in minutes</h2>
            <p className="section-sub">Three simple steps to transform the way you manage your repair shop.</p>
          </div>
          <div className="steps-grid">
            {[
              { n: "1", title: "Create Your Account", desc: "Sign up and choose a plan that fits your shop. No credit card required for the free trial." },
              { n: "2", title: "Set Up Your Shop", desc: "Add your services, working hours, and team members with our guided setup wizard." },
              { n: "3", title: "Start Taking Repairs", desc: "Begin scheduling appointments, tracking repairs, and growing your business right away." },
            ].map((s) => (
              <div key={s.n} className="step-item">
                <div className="step-num">{s.n}</div>
                <h3>{s.title}</h3>
                <p>{s.desc}</p>
              </div>
            ))}
          </div>
        </section>
      </div>

      {/* PRICING PREVIEW */}
      <section className="section" id="pricing">
        <div className="section-header center">
          <div className="section-label" style={{ justifyContent: "center" }}>Pricing</div>
          <h2 className="section-title">Simple, transparent pricing</h2>
          <p className="section-sub">No hidden fees. No surprises. Choose the plan that grows with your business.</p>
        </div>
        <div className="pricing-grid">
          {pricingPlans.map((p) => (
            <div key={p.id} className={`price-card${p.featured ? " featured" : ""}`}>
              <div className="price-name">{p.name}</div>
              <div className="price-desc">{p.desc}</div>
              <div className="price-amount">{p.amount}<span>/mo</span></div>
              <div className="price-period">{p.period}</div>
              <ul className="price-features">
                {p.features.map((f) => (
                  <li key={f}><CheckIcon />{f}</li>
                ))}
              </ul>
              <Link href={`/v2/register?plan=${p.id}`} className={`btn ${p.featured ? "btn-primary" : "btn-outline"}`}>{p.cta}</Link>
            </div>
          ))}
        </div>
        <div style={{ textAlign: "center", marginTop: 32 }}>
          <Link href="/v2/plans" style={{ fontSize: 14, fontWeight: 700, color: "var(--orange)", transition: "opacity .2s" }}>
            Compare all plan features →
          </Link>
        </div>
      </section>

      {/* TESTIMONIALS */}
      <div className="testimonials-bg" id="testimonials">
        <section className="section">
          <div className="section-header center">
            <div className="section-label" style={{ justifyContent: "center" }}>Testimonials</div>
            <h2 className="section-title">Loved by repair shops worldwide</h2>
            <p className="section-sub">Don't just take our word for it — hear from shop owners who've transformed their business.</p>
          </div>
          <div className="test-grid">
            {[
              {
                quote: "99SmartX cut our admin time in half. We went from sticky notes and spreadsheets to a fully organized shop in just one week.",
                initials: "MK", name: "Mike Kowalski", role: "QuickFix Electronics",
                bg: "linear-gradient(135deg,#e8590c,#f76707)"
              },
              {
                quote: "The customer portal is a game-changer. Our clients love being able to track their device status in real time. Five stars, no question.",
                initials: "SR", name: "Sarah Rodriguez", role: "ProTech Repairs",
                bg: "linear-gradient(135deg,#7048e8,#9775fa)"
              },
              {
                quote: "We scaled from one location to three using 99SmartX. The multi-location features saved us from chaos during expansion.",
                initials: "TN", name: "Tom Nguyen", role: "FixItFast Chain",
                bg: "linear-gradient(135deg,#1971c2,#339af0)"
              },
            ].map((t) => (
              <div key={t.name} className="test-card">
                <div className="test-stars">
                  {[...Array(5)].map((_, i) => <StarIcon key={i} />)}
                </div>
                <p className="test-quote">"{t.quote}"</p>
                <div className="test-author">
                  <div className="test-avatar" style={{ background: t.bg }}>{t.initials}</div>
                  <div>
                    <div className="test-name">{t.name}</div>
                    <div className="test-role">{t.role}</div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </section>
      </div>

      {/* CTA BANNER */}
      <div className="cta-banner">
        <h2>Ready to transform your repair shop?</h2>
        <p>Join 1,200+ repair shops already using 99SmartX. Start your free 14-day trial today.</p>
        <Link href="/v2/plans" className="btn btn-primary" style={{ fontSize: 15, padding: "14px 36px", borderRadius: "var(--r-lg)", position: "relative", zIndex: 1 }}>
          Start Free Trial — No Card Required
        </Link>
      </div>

      {/* FAQ */}
      <div className="faq-section">
        <div className="faq-header">
          <h2>Frequently asked questions</h2>
          <p>Everything you need to know about 99SmartX</p>
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
      <footer className="footer">
        <div className="footer-inner">
          <div className="footer-brand">
            <Link href="/v2" className="nav-brand" style={{ marginBottom: 0 }}>
              <div className="logo-mark"><WrenchIcon /></div>
              <span className="brand-name">99SmartX</span>
            </Link>
            <p>The all-in-one platform for repair shops. Manage your business smarter.</p>
          </div>
          <div>
            <h4>Product</h4>
            <ul>
              <li><a href="#features">Features</a></li>
              <li><Link href="/v2/plans">Pricing</Link></li>
              <li><a href="#how-it-works">How It Works</a></li>
            </ul>
          </div>
          <div>
            <h4>Company</h4>
            <ul>
              <li><a href="#">About</a></li>
              <li><a href="#">Blog</a></li>
              <li><a href="#">Careers</a></li>
            </ul>
          </div>
          <div>
            <h4>Legal</h4>
            <ul>
              <li><a href="#">Privacy Policy</a></li>
              <li><a href="#">Terms of Service</a></li>
            </ul>
          </div>
        </div>
        <div className="footer-bottom">
          <span>© 2026 99SmartX. All rights reserved.</span>
          <span>Made with ♥ for repair shops</span>
        </div>
      </footer>
    </>
  );
}
