"use client";

import { SATopbar, SAButton, SAIconButton } from "../SATopbar";
import {
  Search,
  Plus,
  Download,
  CreditCard,
  Crown,
  Star,
  CheckCircle,
  Pencil,
  Settings,
} from "lucide-react";
import { useState } from "react";

/* ── static data ── */
const summaryCards = [
  {
    label: "Total Plans",
    value: 4,
    bg: "var(--sa-orange-bg)",
    color: "var(--sa-orange)",
    icon: <CreditCard />,
  },
  {
    label: "Active Plans",
    value: 3,
    bg: "var(--sa-green-bg)",
    color: "var(--sa-green)",
    icon: <Crown />,
  },
  {
    label: "Featured",
    value: 1,
    bg: "#fef3c7",
    color: "#d97706",
    icon: <Star />,
  },
];

interface PlanFeature {
  text: string;
}
interface Plan {
  name: string;
  code: string;
  status: "active" | "inactive";
  price: string;
  interval: string;
  desc: string;
  features: PlanFeature[];
  featured?: boolean;
}

const plans: Plan[] = [
  {
    name: "Basic",
    code: "plan_basic",
    status: "active",
    price: "$29",
    interval: "/month",
    desc: "Essential features for small repair shops just getting started.",
    features: [
      { text: "Up to 5 employees" },
      { text: "Basic inventory management" },
      { text: "100 repair tickets/month" },
      { text: "Email support" },
    ],
  },
  {
    name: "Professional",
    code: "plan_professional",
    status: "active",
    price: "$79",
    interval: "/month",
    desc: "Advanced tools for growing repair businesses.",
    featured: true,
    features: [
      { text: "Up to 25 employees" },
      { text: "Full inventory + POS" },
      { text: "Unlimited repair tickets" },
      { text: "Priority support" },
      { text: "API access" },
    ],
  },
  {
    name: "Enterprise",
    code: "plan_enterprise",
    status: "active",
    price: "$199",
    interval: "/month",
    desc: "Complete solution for multi-location repair chains.",
    features: [
      { text: "Unlimited employees" },
      { text: "White-label branding" },
      { text: "Dedicated account manager" },
      { text: "Custom integrations" },
      { text: "SLA guarantee" },
    ],
  },
  {
    name: "Starter (Legacy)",
    code: "plan_starter_v1",
    status: "inactive",
    price: "$19",
    interval: "/month",
    desc: "Discontinued plan kept for existing subscribers.",
    features: [
      { text: "Up to 2 employees" },
      { text: "Basic repairs only" },
      { text: "50 tickets/month" },
    ],
  },
];

export function SABillingPlansContent() {
  const [statusFilter, setStatusFilter] = useState("all");

  return (
    <>
      {/* Topbar */}
      <SATopbar
        breadcrumb="Billing & Subscriptions"
        title="Billing Plans"
        actions={
          <>
            <SAButton variant="ghost" icon={<Download />}>
              Export
            </SAButton>
            <SAButton variant="primary" icon={<Plus />}>
              Create Plan
            </SAButton>
          </>
        }
      />

      {/* Content */}
      <div className="sa-content">
        {/* Summary row */}
        <div className="sa-summary-row">
          {summaryCards.map((c) => (
            <div className="sa-scard" key={c.label}>
              <div
                className="sa-scard-icon"
                style={{ background: c.bg, color: c.color }}
              >
                {c.icon}
              </div>
              <div>
                <div className="sa-sc-val">{c.value}</div>
                <div className="sa-sc-lbl">{c.label}</div>
              </div>
            </div>
          ))}
        </div>

        {/* Filter bar */}
        <div className="sa-filter-bar" style={{ marginTop: 20 }}>
          <div className="sa-search-wrap">
            <Search />
            <input placeholder="Search plans..." />
          </div>
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
          >
            <option value="all">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
          <select>
            <option>All Types</option>
            <option>Standard</option>
            <option>Legacy</option>
          </select>
        </div>

        {/* Plan cards grid */}
        <div className="sa-plan-grid" style={{ marginTop: 20 }}>
          {plans.map((p) => (
            <div
              className={`sa-plan-card${p.featured ? " featured" : ""}`}
              key={p.code}
            >
              {/* header */}
              <div className="sa-pc-header">
                <div>
                  <div className="sa-pc-name">{p.name}</div>
                  <div className="sa-pc-code">{p.code}</div>
                </div>
                <span
                  className={`sa-pc-status ${
                    p.status === "active" ? "sa-pc-active" : "sa-pc-inactive"
                  }`}
                >
                  {p.status === "active" ? "Active" : "Inactive"}
                </span>
              </div>

              {/* body */}
              <div className="sa-pc-body">
                <div className="sa-pc-price">
                  {p.price}
                  <span>{p.interval}</span>
                </div>
                <div className="sa-pc-desc">{p.desc}</div>

                <div className="sa-pc-features">
                  {p.features.map((f, i) => (
                    <div className="sa-pc-feat" key={i}>
                      <CheckCircle />
                      {f.text}
                    </div>
                  ))}
                </div>
              </div>

              {/* footer */}
              <div className="sa-pc-footer">
                <SAButton variant="outline" icon={<Pencil />}>
                  Edit
                </SAButton>
                <SAButton variant="ghost" icon={<Settings />}>
                  Configure
                </SAButton>
              </div>
            </div>
          ))}
        </div>
      </div>
    </>
  );
}
