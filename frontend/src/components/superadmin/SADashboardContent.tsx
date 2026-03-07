"use client";

import React from "react";
import { SATopbar, SAIconButton, SAButton } from "@/components/superadmin";

/* ── KPI Data ── */
const kpis = [
  {
    color: "var(--sa-orange)",
    bgColor: "var(--sa-orange-bg)",
    value: "1,248",
    label: "Total Businesses",
    tag: "▲ +12% this month",
    tagClass: "sa-tag-up",
    icon: (
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
    ),
  },
  {
    color: "var(--sa-green)",
    bgColor: "var(--sa-green-bg)",
    value: "912",
    label: "Active Subscriptions",
    tag: "73% conversion",
    tagClass: "sa-tag-info",
    icon: (
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
    ),
  },
  {
    color: "var(--sa-purple)",
    bgColor: "var(--sa-purple-bg)",
    value: "$42,850",
    label: "Estimated MRR",
    tag: "▲ +6% vs last month",
    tagClass: "sa-tag-up",
    icon: (
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    ),
  },
  {
    color: "var(--sa-amber)",
    bgColor: "var(--sa-amber-bg)",
    value: "8,421",
    label: "Total Platform Users",
    tag: "▲ +142 today",
    tagClass: "sa-tag-up",
    icon: (
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
    ),
  },
];

/* ── Latest Registrations Data ── */
const registrations = [
  { name: "QuickFix Electronics", date: "Mar 12, 2024", status: "Active", statusClass: "sa-b-green", plan: "Professional" },
  { name: "Metro Auto Care", date: "Feb 28, 2024", status: "Trial", statusClass: "sa-b-blue", plan: "Basic" },
  { name: "Pioneer Appliance", date: "Jan 15, 2024", status: "Suspended", statusClass: "sa-b-red", plan: "Enterprise" },
  { name: "TechStar Services", date: "Dec 22, 2023", status: "Past Due", statusClass: "sa-b-amber", plan: "Professional" },
];

/* ── Revenue Breakdown Data ── */
const revenueBreakdown = [
  { label: "MRR (USD)", value: "$42,850", className: "gr" },
  { label: "MRR (GBP)", value: "£12,400", className: "gr" },
  { label: "Paid (30d)", value: "$38,240", className: "" },
  { label: "Paid YTD", value: "$84,120", className: "" },
  { label: "Past Due", value: "$3,720", className: "am" },
  { label: "Active Plans", value: "4", className: "" },
];

/* ── Platform Health Data ── */
const healthMetrics = [
  { label: "API Uptime (30d)", value: "99.97%", className: "gr" },
  { label: "Avg. Response", value: "142ms", className: "" },
  { label: "Active Sessions", value: "1,204", className: "" },
  { label: "Failed Logins (24h)", value: "27", className: "am" },
  { label: "Storage", value: "68%", className: "am" },
  { label: "Queue Depth", value: "0", className: "gr" },
];

export default function SADashboardPage() {
  return (
    <>
      {/* Topbar */}
      <SATopbar
        breadcrumb={<>Admin › <b>Dashboard</b></>}
        title="Platform Overview"
        actions={
          <>
            <SAIconButton hasNotification>
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
              </svg>
            </SAIconButton>
            <SAButton variant="ghost">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
              Export
            </SAButton>
            <SAButton variant="primary">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" />
              </svg>
              New Tenant
            </SAButton>
          </>
        }
      />

      <div className="sa-content">
        {/* ── KPI CARDS ── */}
        <div className="sa-kpi-row">
          {kpis.map((kpi) => (
            <div className="sa-kc" key={kpi.label}>
              <div className="sa-kc-stripe" style={{ background: kpi.color }} />
              <div className="sa-kc-icon" style={{ background: kpi.bgColor, color: kpi.color }}>
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">{kpi.icon}</svg>
              </div>
              <div>
                <div className="sa-kc-val">{kpi.value}</div>
                <div className="sa-kc-lbl">{kpi.label}</div>
                <div className={`sa-kc-tag ${kpi.tagClass}`}>{kpi.tag}</div>
              </div>
            </div>
          ))}
        </div>

        {/* ── CHART + STATUS ── */}
        <div className="sa-g2">
          {/* Revenue Trend */}
          <div className="sa-panel">
            <div className="sa-ph">
              <div>
                <div className="sa-ph-t">Revenue Trend</div>
                <div className="sa-ph-s">Monthly sales — last 12 months (USD)</div>
              </div>
              <div className="sa-tab-row">
                <button className="sa-tab-btn" type="button">Quarterly</button>
                <button className="sa-tab-btn active" type="button">Monthly</button>
              </div>
            </div>
            <div className="sa-pb">
              <svg className="sa-chart-svg" viewBox="0 0 700 210" preserveAspectRatio="none">
                <line x1="0" y1="52" x2="700" y2="52" stroke="var(--sa-border)" strokeWidth="1" />
                <line x1="0" y1="105" x2="700" y2="105" stroke="var(--sa-border)" strokeWidth="1" />
                <line x1="0" y1="158" x2="700" y2="158" stroke="var(--sa-border)" strokeWidth="1" />
                <path
                  d="M0,185 L58,170 L116,177 L174,160 L232,143 L290,135 L348,115 L406,125 L464,95 L522,103 L580,70 L638,50 L696,35 L696,210 L0,210Z"
                  fill="url(#saAreaFill)" opacity=".6" />
                <path
                  d="M0,185 L58,170 L116,177 L174,160 L232,143 L290,135 L348,115 L406,125 L464,95 L522,103 L580,70 L638,50 L696,35"
                  fill="none" stroke="var(--sa-orange)" strokeWidth="2.5" strokeLinecap="round"
                  strokeLinejoin="round" />
                <circle cx="696" cy="35" r="5" fill="var(--sa-orange)" />
                <circle cx="638" cy="50" r="3.5" fill="var(--sa-orange)" />
                <defs>
                  <linearGradient id="saAreaFill" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor="var(--sa-orange)" stopOpacity=".3" />
                    <stop offset="100%" stopColor="var(--sa-orange)" stopOpacity="0" />
                  </linearGradient>
                </defs>
              </svg>
              <div className="sa-chart-months">
                <span>Mar &apos;25</span><span>Apr</span><span>May</span><span>Jun</span>
                <span>Jul</span><span>Aug</span><span>Sep</span><span>Oct</span>
                <span>Nov</span><span>Dec</span><span>Jan</span><span>Feb &apos;26</span>
              </div>
              <div className="sa-stat-row">
                <div>
                  <div className="sa-stat-label">This Month</div>
                  <div className="sa-stat-value" style={{ color: "var(--sa-orange)" }}>$38,000</div>
                </div>
                <div>
                  <div className="sa-stat-label">Last Month</div>
                  <div className="sa-stat-value" style={{ color: "var(--sa-text-2)" }}>$31,000</div>
                </div>
                <div>
                  <div className="sa-stat-label">YTD</div>
                  <div className="sa-stat-value" style={{ color: "var(--sa-text)" }}>$84,120</div>
                </div>
              </div>
            </div>
          </div>

          {/* Tenant Status */}
          <div className="sa-panel">
            <div className="sa-ph">
              <div>
                <div className="sa-ph-t">Tenant Status</div>
                <div className="sa-ph-s">1,248 total businesses</div>
              </div>
            </div>
            <div className="sa-pb">
              <div className="sa-stacked-wrap">
                <div className="sa-stacked-bar">
                  <div className="sa-stacked-seg" style={{ flex: 59, background: "var(--sa-green)" }} />
                  <div className="sa-stacked-seg" style={{ flex: 10, background: "var(--sa-blue)" }} />
                  <div className="sa-stacked-seg" style={{ flex: 3, background: "var(--sa-amber)" }} />
                  <div className="sa-stacked-seg" style={{ flex: 1, background: "var(--sa-red)" }} />
                  <div className="sa-stacked-seg" style={{ flex: 26, background: "var(--sa-border-2)" }} />
                </div>
                <div className="sa-stacked-legend">
                  <div className="sa-leg"><div className="sa-leg-dot" style={{ background: "var(--sa-green)" }} />Active <b>742</b></div>
                  <div className="sa-leg"><div className="sa-leg-dot" style={{ background: "var(--sa-blue)" }} />Trialing <b>128</b></div>
                  <div className="sa-leg"><div className="sa-leg-dot" style={{ background: "var(--sa-amber)" }} />Past Due <b>42</b></div>
                  <div className="sa-leg"><div className="sa-leg-dot" style={{ background: "var(--sa-red)" }} />Suspended <b>12</b></div>
                  <div className="sa-leg"><div className="sa-leg-dot" style={{ background: "var(--sa-border-2)" }} />Closed <b>324</b></div>
                </div>
              </div>
              <div style={{ borderTop: "1px solid var(--sa-border)", paddingTop: 16 }}>
                <div className="sa-mrow">
                  <div className="sa-ml">Trial → Paid Conv.</div>
                  <div className="sa-mv gr">73%</div>
                </div>
                <div className="sa-mrow">
                  <div className="sa-ml">Avg. Lifetime</div>
                  <div className="sa-mv">14 months</div>
                </div>
                <div className="sa-mrow">
                  <div className="sa-ml">Churn Rate (30d)</div>
                  <div className="sa-mv am">2.1%</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* ── BOTTOM 3-COL ── */}
        <div className="sa-g3">
          {/* Latest Registrations */}
          <div className="sa-panel">
            <div className="sa-ph">
              <div className="sa-ph-t">Latest Registrations</div>
              <a href="#" className="sa-link">See all →</a>
            </div>
            <div className="sa-pb" style={{ padding: "0 20px" }}>
              <table className="sa-mt">
                <thead>
                  <tr>
                    <th>Business</th>
                    <th>Status</th>
                    <th>Plan</th>
                  </tr>
                </thead>
                <tbody>
                  {registrations.map((r) => (
                    <tr key={r.name}>
                      <td>
                        <div className="sa-tdb">{r.name}</div>
                        <div className="sa-tds">{r.date}</div>
                      </td>
                      <td><span className={`sa-badge ${r.statusClass}`}>{r.status}</span></td>
                      <td>{r.plan}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>

          {/* Revenue Breakdown */}
          <div className="sa-panel">
            <div className="sa-ph">
              <div className="sa-ph-t">Revenue Breakdown</div>
            </div>
            <div className="sa-pb">
              {revenueBreakdown.map((m) => (
                <div className="sa-mrow" key={m.label}>
                  <div className="sa-ml">{m.label}</div>
                  <div className={`sa-mv${m.className ? ` ${m.className}` : ""}`}>{m.value}</div>
                </div>
              ))}
            </div>
          </div>

          {/* Platform Health */}
          <div className="sa-panel">
            <div className="sa-ph">
              <div className="sa-ph-t">Platform Health</div>
              <div className="sa-health-indicator">
                <div className="sa-health-dot" />
                Operational
              </div>
            </div>
            <div className="sa-pb">
              {healthMetrics.map((m) => (
                <div className="sa-mrow" key={m.label}>
                  <div className="sa-ml">{m.label}</div>
                  <div className={`sa-mv${m.className ? ` ${m.className}` : ""}`}>{m.value}</div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
