"use client";

import React, { useState, useEffect, useCallback } from "react";
import { useRouter } from "next/navigation";
import { Loader2 } from "lucide-react";
import { SATopbar, SAIconButton, SAButton } from "@/components/superadmin";
import { getDashboardKpis, getDashboardSales, listAdminBusinesses } from "@/lib/superadmin";
import type { AdminDashboardKpis, AdminSalesResponse, Tenant } from "@/lib/types";

/* ── Helpers ── */

/** Format cents (integer) to a dollar string like "$1,234.56" */
function centsToDollars(cents: number, currency?: string): string {
  const dollars = cents / 100;
  const symbol = currencySymbol(currency);
  return `${symbol}${dollars.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

/** Return a currency symbol for common codes, fallback to the code itself */
function currencySymbol(code?: string): string {
  if (!code) return "$";
  const map: Record<string, string> = {
    USD: "$",
    GBP: "\u00a3",
    EUR: "\u20ac",
    CAD: "CA$",
    AUD: "A$",
  };
  return map[code.toUpperCase()] ?? `${code.toUpperCase()} `;
}

/** Format a number with commas */
function fmtNum(n: number): string {
  return n.toLocaleString();
}

/** Format an ISO date string for display */
function fmtDate(iso?: string): string {
  if (!iso) return "";
  const d = new Date(iso);
  return d.toLocaleDateString(undefined, { month: "short", day: "numeric", year: "numeric" });
}

/** Map tenant status to badge CSS class */
function statusBadgeClass(status: string): string {
  const map: Record<string, string> = {
    active: "sa-b-green",
    trial: "sa-b-blue",
    past_due: "sa-b-amber",
    suspended: "sa-b-red",
    closed: "sa-b-gray",
  };
  return map[status] ?? "sa-b-gray";
}

/** Capitalize a status label */
function statusLabel(status: string): string {
  return status
    .split("_")
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
    .join(" ");
}

/** Build an SVG polyline/area path from an array of values mapped into a viewBox */
function buildChartPaths(
  values: number[],
  width: number,
  height: number,
  paddingTop: number,
  paddingBottom: number
): { linePath: string; areaPath: string } {
  if (values.length === 0) return { linePath: "", areaPath: "" };

  const max = Math.max(...values, 1); // avoid division by zero
  const usableHeight = height - paddingTop - paddingBottom;
  const stepX = values.length > 1 ? width / (values.length - 1) : 0;

  const points = values.map((v, i) => {
    const x = Math.round(i * stepX);
    const y = Math.round(paddingTop + usableHeight - (v / max) * usableHeight);
    return { x, y };
  });

  const linePath = points.map((p, i) => `${i === 0 ? "M" : "L"}${p.x},${p.y}`).join(" ");
  const areaPath = `${linePath} L${points[points.length - 1].x},${height} L${points[0].x},${height}Z`;

  return { linePath, areaPath };
}

/* ── KPI card icon paths (static SVG fragments) ── */
const KPI_ICONS = {
  businesses: (
    <path
      strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
    />
  ),
  subscriptions: (
    <path
      strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
    />
  ),
  mrr: (
    <path
      strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
      d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
    />
  ),
  users: (
    <path
      strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
    />
  ),
};

/* ── Component ── */

export default function SADashboardPage() {
  const router = useRouter();

  const [kpis, setKpis] = useState<AdminDashboardKpis | null>(null);
  const [sales, setSales] = useState<AdminSalesResponse | null>(null);
  const [recentTenants, setRecentTenants] = useState<Tenant[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchData = useCallback(async (signal: AbortSignal) => {
    setLoading(true);
    setError(null);

    try {
      const [kpisResult, salesResult, tenantsResult] = await Promise.allSettled([
        getDashboardKpis(),
        getDashboardSales(),
        listAdminBusinesses({ sort: "newest", per_page: 5, page: 1 }),
      ]);

      if (signal.aborted) return;

      if (kpisResult.status === "fulfilled") {
        setKpis(kpisResult.value);
      } else {
        console.error("Failed to load KPIs:", kpisResult.reason);
      }

      if (salesResult.status === "fulfilled") {
        setSales(salesResult.value);
      } else {
        console.error("Failed to load sales:", salesResult.reason);
      }

      if (tenantsResult.status === "fulfilled") {
        setRecentTenants(tenantsResult.value.data);
      } else {
        console.error("Failed to load recent tenants:", tenantsResult.reason);
      }

      // If ALL three failed, show an error
      if (
        kpisResult.status === "rejected" &&
        salesResult.status === "rejected" &&
        tenantsResult.status === "rejected"
      ) {
        setError("Failed to load dashboard data. Please try again.");
      }
    } catch (err) {
      if (!signal.aborted) {
        setError(err instanceof Error ? err.message : "An unexpected error occurred.");
      }
    } finally {
      if (!signal.aborted) {
        setLoading(false);
      }
    }
  }, []);

  useEffect(() => {
    const controller = new AbortController();
    fetchData(controller.signal);
    return () => controller.abort();
  }, [fetchData]);

  /* ── Derived values ── */

  // MRR display: show each currency
  const mrrEntries = kpis
    ? Object.entries(kpis.mrr_by_currency).filter(([, cents]) => cents !== 0)
    : [];
  const primaryMrrCurrency = mrrEntries.length > 0 ? mrrEntries[0][0] : "USD";
  const totalMrrCents = mrrEntries.reduce((sum, [, cents]) => sum + cents, 0);

  // Sales chart data
  const primarySalesCurrency = sales
    ? Object.keys(sales.totals_by_currency)[0] ?? "USD"
    : "USD";
  const salesValues = sales?.totals_by_currency[primarySalesCurrency] ?? [];
  const chartWidth = 696;
  const chartHeight = 210;
  const { linePath, areaPath } = buildChartPaths(salesValues, chartWidth, chartHeight, 20, 30);

  // Sales stat values
  const thisMonthSales = salesValues.length > 0 ? salesValues[salesValues.length - 1] : 0;
  const lastMonthSales = salesValues.length > 1 ? salesValues[salesValues.length - 2] : 0;
  const ytdCents = kpis
    ? Object.values(kpis.revenue.paid_ytd_by_currency).reduce((s, v) => s + v, 0)
    : 0;

  // Tenant status bar
  const tenantStatuses = kpis
    ? [
        { key: "active", label: "Active", count: kpis.tenants.by_status.active, color: "var(--sa-green)" },
        { key: "trial", label: "Trialing", count: kpis.tenants.by_status.trial, color: "var(--sa-blue)" },
        { key: "past_due", label: "Past Due", count: kpis.tenants.by_status.past_due, color: "var(--sa-amber)" },
        { key: "suspended", label: "Suspended", count: kpis.tenants.by_status.suspended, color: "var(--sa-red)" },
        { key: "closed", label: "Closed", count: kpis.tenants.by_status.closed, color: "var(--sa-border-2)" },
      ]
    : [];
  const tenantTotal = tenantStatuses.reduce((s, t) => s + t.count, 0);

  // Revenue breakdown rows
  const revenueRows: Array<{ label: string; value: string; className: string }> = [];
  if (kpis) {
    // MRR by currency
    for (const [cur, cents] of Object.entries(kpis.mrr_by_currency)) {
      revenueRows.push({ label: `MRR (${cur.toUpperCase()})`, value: centsToDollars(cents, cur), className: "gr" });
    }
    // Paid last 30d by currency
    for (const [cur, cents] of Object.entries(kpis.revenue.paid_last_30d_by_currency)) {
      revenueRows.push({ label: `Paid 30d (${cur.toUpperCase()})`, value: centsToDollars(cents, cur), className: "" });
    }
    // Paid YTD by currency
    for (const [cur, cents] of Object.entries(kpis.revenue.paid_ytd_by_currency)) {
      revenueRows.push({ label: `Paid YTD (${cur.toUpperCase()})`, value: centsToDollars(cents, cur), className: "" });
    }
  }
  // If no revenue data at all, show a placeholder
  if (revenueRows.length === 0 && !loading) {
    revenueRows.push({ label: "No revenue data", value: "--", className: "" });
  }

  // Subscription status rows (replaces Platform Health)
  const subscriptionStatuses = kpis
    ? [
        { label: "Trial", value: fmtNum(kpis.subscriptions.by_status.trial), className: "" },
        { label: "Active", value: fmtNum(kpis.subscriptions.by_status.active), className: "gr" },
        { label: "Past Due", value: fmtNum(kpis.subscriptions.by_status.past_due), className: kpis.subscriptions.by_status.past_due > 0 ? "am" : "" },
        { label: "Canceled", value: fmtNum(kpis.subscriptions.by_status.canceled), className: kpis.subscriptions.by_status.canceled > 0 ? "am" : "" },
        { label: "Active Total", value: fmtNum(kpis.subscriptions.active_total), className: "gr" },
        { label: "Total Users", value: fmtNum(kpis.users.total), className: "" },
      ]
    : [];

  /* ── KPI card definitions ── */
  const kpiCards = kpis
    ? [
        {
          color: "var(--sa-orange)",
          bgColor: "var(--sa-orange-bg)",
          value: fmtNum(kpis.tenants.total),
          label: "Total Businesses",
          tag: `${fmtNum(kpis.tenants.by_status.active)} active`,
          tagClass: "sa-tag-info",
          icon: KPI_ICONS.businesses,
        },
        {
          color: "var(--sa-green)",
          bgColor: "var(--sa-green-bg)",
          value: fmtNum(kpis.subscriptions.active_total),
          label: "Active Subscriptions",
          tag: kpis.tenants.total > 0
            ? `${Math.round((kpis.subscriptions.active_total / kpis.tenants.total) * 100)}% of tenants`
            : "0%",
          tagClass: "sa-tag-info",
          icon: KPI_ICONS.subscriptions,
        },
        {
          color: "var(--sa-purple)",
          bgColor: "var(--sa-purple-bg)",
          value: mrrEntries.length <= 1
            ? centsToDollars(totalMrrCents, primaryMrrCurrency)
            : mrrEntries.map(([cur, cents]) => centsToDollars(cents, cur)).join(" / "),
          label: "Estimated MRR",
          tag: mrrEntries.length > 1
            ? `${mrrEntries.length} currencies`
            : "",
          tagClass: "sa-tag-info",
          icon: KPI_ICONS.mrr,
        },
        {
          color: "var(--sa-amber)",
          bgColor: "var(--sa-amber-bg)",
          value: fmtNum(kpis.users.total),
          label: "Total Platform Users",
          tag: `${fmtNum(kpis.users.admins)} admins`,
          tagClass: "sa-tag-info",
          icon: KPI_ICONS.users,
        },
      ]
    : [];

  /* ── Loading state ── */
  if (loading) {
    return (
      <>
        <SATopbar
          breadcrumb={<>Admin &rsaquo; <b>Dashboard</b></>}
          title="Platform Overview"
          actions={null}
        />
        <div className="sa-content" style={{ flexDirection: "row", alignItems: "center", justifyContent: "center", minHeight: 400 }}>
          <div style={{ display: "flex", flexDirection: "column", alignItems: "center" }}>
            <Loader2 className="sa-spin" style={{ width: 36, height: 36, color: "var(--sa-orange)" }} />
            <div style={{ marginTop: 12, color: "var(--sa-text-2)" }}>Loading dashboard...</div>
          </div>
        </div>
      </>
    );
  }

  /* ── Error state ── */
  if (error && !kpis && !sales && recentTenants.length === 0) {
    return (
      <>
        <SATopbar
          breadcrumb={<>Admin &rsaquo; <b>Dashboard</b></>}
          title="Platform Overview"
          actions={null}
        />
        <div className="sa-content" style={{ display: "flex", alignItems: "center", justifyContent: "center", minHeight: 400 }}>
          <div style={{ textAlign: "center", maxWidth: 420 }}>
            <div style={{ fontSize: 18, fontWeight: 600, color: "var(--sa-red)", marginBottom: 8 }}>
              Failed to load dashboard
            </div>
            <div style={{ color: "var(--sa-text-2)", marginBottom: 16 }}>{error}</div>
            <SAButton variant="primary" onClick={() => { const c = new AbortController(); fetchData(c.signal); }}>
              Retry
            </SAButton>
          </div>
        </div>
      </>
    );
  }

  /* ── Main render ── */
  return (
    <>
      {/* Topbar */}
      <SATopbar
        breadcrumb={<>Admin &rsaquo; <b>Dashboard</b></>}
        title="Platform Overview"
        actions={
          <>
            <SAIconButton hasNotification>
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                />
              </svg>
            </SAIconButton>
            <SAButton variant="primary" onClick={() => router.push("/superadmin/businesses/new")}>
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
          {kpiCards.map((kpi) => (
            <div className="sa-kc" key={kpi.label}>
              <div className="sa-kc-stripe" style={{ background: kpi.color }} />
              <div className="sa-kc-icon" style={{ background: kpi.bgColor, color: kpi.color }}>
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">{kpi.icon}</svg>
              </div>
              <div>
                <div className="sa-kc-val">{kpi.value}</div>
                <div className="sa-kc-lbl">{kpi.label}</div>
                {kpi.tag && <div className={`sa-kc-tag ${kpi.tagClass}`}>{kpi.tag}</div>}
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
                <div className="sa-ph-s">
                  Monthly sales &mdash; last 12 months
                  {sales && Object.keys(sales.totals_by_currency).length > 0 && ` (${primarySalesCurrency.toUpperCase()})`}
                </div>
              </div>
              {sales && Object.keys(sales.totals_by_currency).length > 1 && (
                <div className="sa-ph-s" style={{ fontSize: 11, color: "var(--sa-text-3)" }}>
                  Showing: {primarySalesCurrency.toUpperCase()}
                </div>
              )}
            </div>
            <div className="sa-pb">
              {sales && salesValues.length > 0 ? (
                <>
                  <svg className="sa-chart-svg" viewBox="0 0 700 210" preserveAspectRatio="none">
                    <line x1="0" y1="52" x2="700" y2="52" stroke="var(--sa-border)" strokeWidth="1" />
                    <line x1="0" y1="105" x2="700" y2="105" stroke="var(--sa-border)" strokeWidth="1" />
                    <line x1="0" y1="158" x2="700" y2="158" stroke="var(--sa-border)" strokeWidth="1" />
                    <path d={areaPath} fill="url(#saAreaFill)" opacity=".6" />
                    <path
                      d={linePath}
                      fill="none" stroke="var(--sa-orange)" strokeWidth="2.5"
                      strokeLinecap="round" strokeLinejoin="round"
                    />
                    {/* Endpoint dot on last value */}
                    {salesValues.length > 0 && (() => {
                      const max = Math.max(...salesValues, 1);
                      const stepX = salesValues.length > 1 ? chartWidth / (salesValues.length - 1) : 0;
                      const lastIdx = salesValues.length - 1;
                      const cx = Math.round(lastIdx * stepX);
                      const cy = Math.round(20 + (chartHeight - 20 - 30) - (salesValues[lastIdx] / max) * (chartHeight - 20 - 30));
                      return <circle cx={cx} cy={cy} r="5" fill="var(--sa-orange)" />;
                    })()}
                    {salesValues.length > 1 && (() => {
                      const max = Math.max(...salesValues, 1);
                      const stepX = salesValues.length > 1 ? chartWidth / (salesValues.length - 1) : 0;
                      const prevIdx = salesValues.length - 2;
                      const cx = Math.round(prevIdx * stepX);
                      const cy = Math.round(20 + (chartHeight - 20 - 30) - (salesValues[prevIdx] / max) * (chartHeight - 20 - 30));
                      return <circle cx={cx} cy={cy} r="3.5" fill="var(--sa-orange)" />;
                    })()}
                    <defs>
                      <linearGradient id="saAreaFill" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stopColor="var(--sa-orange)" stopOpacity=".3" />
                        <stop offset="100%" stopColor="var(--sa-orange)" stopOpacity="0" />
                      </linearGradient>
                    </defs>
                  </svg>
                  <div className="sa-chart-months">
                    {sales.months.map((m) => (
                      <span key={m.key}>{m.label}</span>
                    ))}
                  </div>
                </>
              ) : (
                <div style={{ textAlign: "center", padding: "40px 0", color: "var(--sa-text-3)" }}>
                  No sales data available
                </div>
              )}
              <div className="sa-chart-stat-row">
                <div>
                  <div className="sa-stat-label">This Month</div>
                  <div className="sa-stat-value" style={{ color: "var(--sa-orange)" }}>
                    {centsToDollars(thisMonthSales, primarySalesCurrency)}
                  </div>
                </div>
                <div>
                  <div className="sa-stat-label">Last Month</div>
                  <div className="sa-stat-value" style={{ color: "var(--sa-text-2)" }}>
                    {centsToDollars(lastMonthSales, primarySalesCurrency)}
                  </div>
                </div>
                <div>
                  <div className="sa-stat-label">YTD</div>
                  <div className="sa-stat-value" style={{ color: "var(--sa-text)" }}>
                    {centsToDollars(ytdCents, primarySalesCurrency)}
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Tenant Status */}
          <div className="sa-panel">
            <div className="sa-ph">
              <div>
                <div className="sa-ph-t">Tenant Status</div>
                <div className="sa-ph-s">
                  {kpis ? `${fmtNum(kpis.tenants.total)} total businesses` : "Loading..."}
                </div>
              </div>
            </div>
            <div className="sa-pb">
              {kpis ? (
                <div className="sa-stacked-wrap">
                  <div className="sa-stacked-bar">
                    {tenantStatuses.map((s) => (
                      s.count > 0 && (
                        <div
                          key={s.key}
                          className="sa-stacked-seg"
                          style={{ flex: s.count, background: s.color }}
                        />
                      )
                    ))}
                    {tenantTotal === 0 && (
                      <div className="sa-stacked-seg" style={{ flex: 1, background: "var(--sa-border-2)" }} />
                    )}
                  </div>
                  <div className="sa-stacked-legend">
                    {tenantStatuses.map((s) => (
                      <div className="sa-leg" key={s.key}>
                        <div className="sa-leg-dot" style={{ background: s.color }} />
                        {s.label} <b>{fmtNum(s.count)}</b>
                      </div>
                    ))}
                  </div>
                </div>
              ) : (
                <div style={{ textAlign: "center", padding: "40px 0", color: "var(--sa-text-3)" }}>
                  No tenant data available
                </div>
              )}
              {kpis && (
                <div style={{ borderTop: "1px solid var(--sa-border)", paddingTop: 16 }}>
                  <div className="sa-mrow">
                    <div className="sa-ml">Trial &rarr; Paid Conv.</div>
                    <div className="sa-mv gr">
                      {kpis.tenants.by_status.trial + kpis.subscriptions.by_status.active > 0
                        ? `${Math.round(
                            (kpis.subscriptions.by_status.active /
                              (kpis.tenants.by_status.trial + kpis.subscriptions.by_status.active)) *
                              100
                          )}%`
                        : "N/A"}
                    </div>
                  </div>
                  <div className="sa-mrow">
                    <div className="sa-ml">Past Due Tenants</div>
                    <div className={`sa-mv${kpis.tenants.by_status.past_due > 0 ? " am" : ""}`}>
                      {fmtNum(kpis.tenants.by_status.past_due)}
                    </div>
                  </div>
                  <div className="sa-mrow">
                    <div className="sa-ml">Suspended Tenants</div>
                    <div className={`sa-mv${kpis.tenants.by_status.suspended > 0 ? " am" : ""}`}>
                      {fmtNum(kpis.tenants.by_status.suspended)}
                    </div>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>

        {/* ── BOTTOM 3-COL ── */}
        <div className="sa-g3">
          {/* Latest Registrations */}
          <div className="sa-panel">
            <div className="sa-ph">
              <div className="sa-ph-t">Latest Registrations</div>
              <a
                href="/superadmin/businesses"
                className="sa-link"
                onClick={(e) => {
                  e.preventDefault();
                  router.push("/superadmin/businesses");
                }}
              >
                See all &rarr;
              </a>
            </div>
            <div className="sa-pb" style={{ padding: "0 20px" }}>
              {recentTenants.length > 0 ? (
                <table className="sa-mt">
                  <thead>
                    <tr>
                      <th>Business</th>
                      <th>Status</th>
                      <th>Plan</th>
                    </tr>
                  </thead>
                  <tbody>
                    {recentTenants.map((t) => (
                      <tr key={t.id}>
                        <td>
                          <div className="sa-tdb">{t.name}</div>
                          <div className="sa-tds">{fmtDate(t.created_at)}</div>
                        </td>
                        <td>
                          <span className={`sa-badge ${statusBadgeClass(t.status)}`}>
                            {statusLabel(t.status)}
                          </span>
                        </td>
                        <td>{t.billing_snapshot?.plan_name ?? "None"}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              ) : (
                <div style={{ textAlign: "center", padding: "30px 0", color: "var(--sa-text-3)" }}>
                  No businesses registered yet
                </div>
              )}
            </div>
          </div>

          {/* Revenue Breakdown */}
          <div className="sa-panel">
            <div className="sa-ph">
              <div className="sa-ph-t">Revenue Breakdown</div>
            </div>
            <div className="sa-pb">
              {revenueRows.map((m) => (
                <div className="sa-mrow" key={m.label}>
                  <div className="sa-ml">{m.label}</div>
                  <div className={`sa-mv${m.className ? ` ${m.className}` : ""}`}>{m.value}</div>
                </div>
              ))}
            </div>
          </div>

          {/* Subscription Status (replaces Platform Health) */}
          <div className="sa-panel">
            <div className="sa-ph">
              <div className="sa-ph-t">Subscription Status</div>
              {kpis && (
                <div className="sa-health-indicator">
                  <div className="sa-health-dot" />
                  {fmtNum(kpis.subscriptions.active_total)} active
                </div>
              )}
            </div>
            <div className="sa-pb">
              {subscriptionStatuses.length > 0 ? (
                subscriptionStatuses.map((m) => (
                  <div className="sa-mrow" key={m.label}>
                    <div className="sa-ml">{m.label}</div>
                    <div className={`sa-mv${m.className ? ` ${m.className}` : ""}`}>{m.value}</div>
                  </div>
                ))
              ) : (
                <div style={{ textAlign: "center", padding: "30px 0", color: "var(--sa-text-3)" }}>
                  No subscription data available
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
