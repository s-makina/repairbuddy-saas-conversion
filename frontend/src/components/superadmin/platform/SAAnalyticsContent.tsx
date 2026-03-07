'use client';

import React, { useState, useEffect, useCallback } from 'react';
import { Loader2, RefreshCw, AlertCircle } from 'lucide-react';
import { SATopbar, SAButton } from '../SATopbar';
import { getAnalytics } from '@/lib/superadmin';
import type { AdminAnalyticsData } from '@/lib/types';

/* ── Helpers ── */

function centsToDollars(cents: number, currency?: string): string {
  const dollars = cents / 100;
  const symbol = currencySymbol(currency);
  return `${symbol}${dollars.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function currencySymbol(code?: string): string {
  if (!code) return '$';
  const map: Record<string, string> = { USD: '$', GBP: '\u00a3', EUR: '\u20ac', CAD: 'CA$', AUD: 'A$' };
  return map[code.toUpperCase()] ?? `${code.toUpperCase()} `;
}

function fmtNum(n: number): string {
  return n.toLocaleString();
}

function shortMonth(ym: string): string {
  const [y, m] = ym.split('-');
  const d = new Date(Number(y), Number(m) - 1, 1);
  return d.toLocaleDateString(undefined, { month: 'short' });
}

function shortMonthYear(ym: string): string {
  const [y, m] = ym.split('-');
  const d = new Date(Number(y), Number(m) - 1, 1);
  return d.toLocaleDateString(undefined, { month: 'short', year: '2-digit' });
}

function buildChartPaths(
  values: number[],
  width: number,
  height: number,
  paddingTop: number,
  paddingBottom: number
): { linePath: string; areaPath: string } {
  if (values.length === 0) return { linePath: '', areaPath: '' };
  const max = Math.max(...values, 1);
  const usableHeight = height - paddingTop - paddingBottom;
  const stepX = values.length > 1 ? width / (values.length - 1) : 0;
  const points = values.map((v, i) => ({
    x: Math.round(i * stepX),
    y: Math.round(paddingTop + usableHeight - (v / max) * usableHeight),
  }));
  const linePath = points.map((p, i) => `${i === 0 ? 'M' : 'L'}${p.x},${p.y}`).join(' ');
  const areaPath = `${linePath} L${points[points.length - 1].x},${height} L${points[0].x},${height}Z`;
  return { linePath, areaPath };
}

/* ── Component ── */

export default function SAAnalyticsContent() {
  const [data, setData] = useState<AdminAnalyticsData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [months, setMonths] = useState(12);
  const [refreshing, setRefreshing] = useState(false);

  const fetchData = useCallback(async (signal: AbortSignal, monthCount: number) => {
    setError(null);
    try {
      const result = await getAnalytics({ months: monthCount });
      if (!signal.aborted) {
        setData(result);
      }
    } catch (err) {
      if (!signal.aborted) {
        setError(err instanceof Error ? err.message : 'Failed to load analytics data.');
      }
    }
  }, []);

  useEffect(() => {
    const controller = new AbortController();
    setLoading(true);
    fetchData(controller.signal, months).finally(() => {
      if (!controller.signal.aborted) setLoading(false);
    });
    return () => controller.abort();
  }, [fetchData, months]);

  const handleRefresh = async () => {
    setRefreshing(true);
    const controller = new AbortController();
    await fetchData(controller.signal, months);
    setRefreshing(false);
  };

  const handleMonthsChange = (newMonths: number) => {
    setMonths(newMonths);
  };

  /* ── Derived values ── */

  // Tenant trend chart
  const tenantCounts = data?.tenant_trend.map((t) => t.count) ?? [];
  const tenantLabels = data?.tenant_trend ?? [];
  const totalTenantsRegistered = tenantCounts.reduce((s, c) => s + c, 0);
  const thisMonthTenants = tenantCounts.length > 0 ? tenantCounts[tenantCounts.length - 1] : 0;
  const lastMonthTenants = tenantCounts.length > 1 ? tenantCounts[tenantCounts.length - 2] : 0;
  const tenantGrowthPct = lastMonthTenants > 0
    ? Math.round(((thisMonthTenants - lastMonthTenants) / lastMonthTenants) * 100)
    : thisMonthTenants > 0 ? 100 : 0;

  // User trend
  const userCounts = data?.user_trend.map((t) => t.count) ?? [];
  const totalUsersRegistered = userCounts.reduce((s, c) => s + c, 0);
  const thisMonthUsers = userCounts.length > 0 ? userCounts[userCounts.length - 1] : 0;
  const lastMonthUsers = userCounts.length > 1 ? userCounts[userCounts.length - 2] : 0;
  const userGrowthPct = lastMonthUsers > 0
    ? Math.round(((thisMonthUsers - lastMonthUsers) / lastMonthUsers) * 100)
    : thisMonthUsers > 0 ? 100 : 0;

  // MRR trend
  const mrrCents = data?.mrr_trend.map((t) => t.cents) ?? [];
  const latestMrr = mrrCents.length > 0 ? mrrCents[mrrCents.length - 1] : 0;
  const prevMrr = mrrCents.length > 1 ? mrrCents[mrrCents.length - 2] : 0;
  const mrrGrowthPct = prevMrr > 0
    ? Math.round(((latestMrr - prevMrr) / prevMrr) * 100)
    : latestMrr > 0 ? 100 : 0;

  // Revenue by plan for the sidebar
  const revenueByPlan = data?.revenue_by_plan ?? [];

  // Subscription snapshot
  const subSnap = data?.subscriptions_snapshot ?? { trial: 0, active: 0, past_due: 0, canceled: 0 };
  const subTotal = subSnap.trial + subSnap.active + subSnap.past_due + subSnap.canceled;

  // Tenant snapshot
  const tSnap = data?.tenants_snapshot ?? { trial: 0, active: 0, past_due: 0, suspended: 0, closed: 0 };
  const tTotal = tSnap.trial + tSnap.active + tSnap.past_due + tSnap.suspended + tSnap.closed;

  // Trial to paid conversion
  const conversionPct = (tSnap.trial + subSnap.active) > 0
    ? Math.round((subSnap.active / (tSnap.trial + subSnap.active)) * 100)
    : 0;

  // ARPU (average revenue per user with active sub)
  const totalRevAllPlans = revenueByPlan.reduce((s, p) => s + p.total_cents, 0);
  const arpu = subSnap.active > 0 ? totalRevAllPlans / subSnap.active : 0;

  // Chart paths
  const chartWidth = 696;
  const chartHeight = 210;
  const { linePath: tenantLinePath, areaPath: tenantAreaPath } = buildChartPaths(
    tenantCounts, chartWidth, chartHeight, 20, 30
  );

  /* ── KPI cards ── */
  const kpiCards = data ? [
    {
      color: 'var(--sa-orange)', bgColor: 'var(--sa-orange-bg)',
      icon: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z',
      val: fmtNum(totalTenantsRegistered),
      lbl: 'Tenant Signups',
      tag: tenantGrowthPct >= 0
        ? `+${tenantGrowthPct}% vs last month`
        : `${tenantGrowthPct}% vs last month`,
      tagClass: tenantGrowthPct >= 0 ? 'tag-up' : 'tag-down',
    },
    {
      color: 'var(--sa-blue)', bgColor: 'var(--sa-blue-bg)',
      icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
      val: fmtNum(totalUsersRegistered),
      lbl: 'User Signups',
      tag: userGrowthPct >= 0
        ? `+${userGrowthPct}% vs last month`
        : `${userGrowthPct}% vs last month`,
      tagClass: userGrowthPct >= 0 ? 'tag-up' : 'tag-down',
    },
    {
      color: 'var(--sa-green)', bgColor: 'var(--sa-green-bg)',
      icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
      val: centsToDollars(latestMrr, 'USD'),
      lbl: 'Current MRR',
      tag: mrrGrowthPct >= 0
        ? `+${mrrGrowthPct}% vs last month`
        : `${mrrGrowthPct}% vs last month`,
      tagClass: mrrGrowthPct >= 0 ? 'tag-up' : 'tag-down',
    },
    {
      color: 'var(--sa-purple)', bgColor: 'var(--sa-purple-bg)',
      icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
      val: fmtNum(subSnap.active),
      lbl: 'Active Subscriptions',
      tag: subTotal > 0
        ? `${Math.round((subSnap.active / subTotal) * 100)}% of total`
        : '0%',
      tagClass: 'tag-info',
    },
  ] : [];

  /* ── Loading state ── */
  if (loading) {
    return (
      <>
        <SATopbar
          breadcrumb={<>Admin &rsaquo; Platform &rsaquo; <b>Analytics</b></>}
          title="Platform Analytics"
          actions={null}
        />
        <div className="sa-content" style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', minHeight: 400 }}>
          <div style={{ textAlign: 'center' }}>
            <Loader2 className="sa-spinner" style={{ width: 36, height: 36, animation: 'spin 1s linear infinite', color: 'var(--sa-orange)' }} />
            <div style={{ marginTop: 12, color: 'var(--sa-text-2)' }}>Loading analytics...</div>
          </div>
        </div>
        <style>{`@keyframes spin { to { transform: rotate(360deg); } }`}</style>
      </>
    );
  }

  /* ── Error state ── */
  if (error && !data) {
    return (
      <>
        <SATopbar
          breadcrumb={<>Admin &rsaquo; Platform &rsaquo; <b>Analytics</b></>}
          title="Platform Analytics"
          actions={null}
        />
        <div className="sa-content" style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', minHeight: 400 }}>
          <div style={{ textAlign: 'center', maxWidth: 420 }}>
            <AlertCircle style={{ width: 40, height: 40, color: 'var(--sa-red)', margin: '0 auto 12px' }} />
            <div style={{ fontSize: 18, fontWeight: 600, color: 'var(--sa-red)', marginBottom: 8 }}>
              Failed to load analytics
            </div>
            <div style={{ color: 'var(--sa-text-2)', marginBottom: 16 }}>{error}</div>
            <SAButton variant="primary" onClick={handleRefresh}>Retry</SAButton>
          </div>
        </div>
      </>
    );
  }

  /* ── Main render ── */
  return (
    <>
      <SATopbar
        breadcrumb={<>Admin &rsaquo; Platform &rsaquo; <b>Analytics</b></>}
        title="Platform Analytics"
        actions={
          <SAButton
            variant="ghost"
            icon={<RefreshCw size={14} className={refreshing ? 'sa-spin' : ''} />}
            onClick={handleRefresh}
            disabled={refreshing}
          >
            {refreshing ? 'Refreshing...' : 'Refresh'}
          </SAButton>
        }
      />
      <div className="sa-content">
        {/* Period selector */}
        <div style={{ display: 'flex', justifyContent: 'flex-end', marginBottom: 16 }}>
          <div className="sa-tab-row">
            {[
              { label: '6 mo', value: 6 },
              { label: '12 mo', value: 12 },
              { label: '24 mo', value: 24 },
            ].map((opt) => (
              <button
                key={opt.value}
                className={`sa-tab-btn${months === opt.value ? ' active' : ''}`}
                onClick={() => handleMonthsChange(opt.value)}
              >
                {opt.label}
              </button>
            ))}
          </div>
        </div>

        {/* KPI Row */}
        <div className="sa-kpi-row">
          {kpiCards.map((k) => (
            <div className="sa-kc" key={k.lbl}>
              <div className="sa-kc-stripe" style={{ background: k.color }} />
              <div className="sa-kc-icon" style={{ background: k.bgColor, color: k.color }}>
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d={k.icon} />
                </svg>
              </div>
              <div>
                <div className="sa-kc-val">{k.val}</div>
                <div className="sa-kc-lbl">{k.lbl}</div>
                <div className={`sa-kc-tag ${k.tagClass}`}>
                  {k.tag}
                </div>
              </div>
            </div>
          ))}
        </div>

        {/* Chart + Top Plans */}
        <div className="sa-g2">
          {/* Tenant Signups Trend */}
          <div className="sa-panel">
            <div className="sa-ph">
              <div>
                <div className="sa-ph-t">Signups Trend</div>
                <div className="sa-ph-s">New tenant registrations &mdash; last {months} months</div>
              </div>
            </div>
            <div className="sa-pb">
              {tenantCounts.length > 0 ? (
                <>
                  <svg className="sa-chart-svg" viewBox="0 0 700 210" preserveAspectRatio="none">
                    <line x1="0" y1="52" x2="700" y2="52" stroke="var(--sa-border)" strokeWidth="1" />
                    <line x1="0" y1="105" x2="700" y2="105" stroke="var(--sa-border)" strokeWidth="1" />
                    <line x1="0" y1="158" x2="700" y2="158" stroke="var(--sa-border)" strokeWidth="1" />
                    <path d={tenantAreaPath} fill="url(#aFill)" opacity=".6" />
                    <path d={tenantLinePath} fill="none" stroke="var(--sa-orange)" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" />
                    {tenantCounts.length > 0 && (() => {
                      const max = Math.max(...tenantCounts, 1);
                      const stepX = tenantCounts.length > 1 ? chartWidth / (tenantCounts.length - 1) : 0;
                      const lastIdx = tenantCounts.length - 1;
                      const cx = Math.round(lastIdx * stepX);
                      const cy = Math.round(20 + (chartHeight - 50) - (tenantCounts[lastIdx] / max) * (chartHeight - 50));
                      return <circle cx={cx} cy={cy} r="5" fill="var(--sa-orange)" />;
                    })()}
                    <defs>
                      <linearGradient id="aFill" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stopColor="var(--sa-orange)" stopOpacity=".3" />
                        <stop offset="100%" stopColor="var(--sa-orange)" stopOpacity="0" />
                      </linearGradient>
                    </defs>
                  </svg>
                  <div className="sa-chart-months">
                    {tenantLabels.map((t, i) => {
                      // Show every label for <= 12 months, every other for more
                      if (months > 12 && i % 2 !== 0 && i !== tenantLabels.length - 1) return null;
                      return <span key={t.month}>{i === 0 || i === tenantLabels.length - 1 ? shortMonthYear(t.month) : shortMonth(t.month)}</span>;
                    })}
                  </div>
                </>
              ) : (
                <div style={{ textAlign: 'center', padding: '40px 0', color: 'var(--sa-text-3)' }}>
                  No signup data available
                </div>
              )}
              <div className="sa-stat-row">
                <div>
                  <div className="sa-stat-label">This Month</div>
                  <div className="sa-stat-value" style={{ color: 'var(--sa-orange)' }}>{fmtNum(thisMonthTenants)}</div>
                </div>
                <div>
                  <div className="sa-stat-label">Last Month</div>
                  <div className="sa-stat-value" style={{ color: 'var(--sa-text-2)' }}>{fmtNum(lastMonthTenants)}</div>
                </div>
                <div>
                  <div className="sa-stat-label">Period Total</div>
                  <div className="sa-stat-value" style={{ color: 'var(--sa-text)' }}>{fmtNum(totalTenantsRegistered)}</div>
                </div>
              </div>
            </div>
          </div>

          {/* Revenue by Plan */}
          <div className="sa-panel">
            <div className="sa-ph">
              <div>
                <div className="sa-ph-t">Top Plans by Revenue</div>
                <div className="sa-ph-s">Paid invoices in last {months} months</div>
              </div>
            </div>
            <div className="sa-pb">
              {revenueByPlan.length > 0 ? (
                <>
                  {revenueByPlan.map((p) => (
                    <div className="sa-mrow" key={`${p.plan_name}-${p.currency}`}>
                      <div className="sa-ml">
                        {p.plan_name}
                        <span style={{ fontSize: 11, color: 'var(--sa-text-3)', marginLeft: 4 }}>
                          ({p.currency})
                        </span>
                      </div>
                      <div className="sa-mv gr">{centsToDollars(p.total_cents, p.currency)}</div>
                    </div>
                  ))}
                  <div style={{ borderTop: '1px solid var(--sa-border)', paddingTop: 12, marginTop: 8 }}>
                    <div className="sa-mrow">
                      <div className="sa-ml">Trial &rarr; Paid Conv.</div>
                      <div className="sa-mv gr">{conversionPct}%</div>
                    </div>
                    <div className="sa-mrow">
                      <div className="sa-ml">ARPU</div>
                      <div className="sa-mv">{centsToDollars(Math.round(arpu), 'USD')}</div>
                    </div>
                  </div>
                </>
              ) : (
                <div style={{ textAlign: 'center', padding: '40px 0', color: 'var(--sa-text-3)' }}>
                  No revenue data yet
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Bottom 3-col grid */}
        <div className="sa-g3">
          {/* Tenant Status Breakdown */}
          <div className="sa-panel">
            <div className="sa-ph"><div className="sa-ph-t">Tenant Status</div></div>
            <div className="sa-pb">
              {tTotal > 0 ? (
                <div className="sa-stacked-wrap">
                  <div className="sa-stacked-bar">
                    {[
                      { key: 'active', color: 'var(--sa-green)', count: tSnap.active },
                      { key: 'trial', color: 'var(--sa-blue)', count: tSnap.trial },
                      { key: 'past_due', color: 'var(--sa-amber)', count: tSnap.past_due },
                      { key: 'suspended', color: 'var(--sa-red)', count: tSnap.suspended },
                      { key: 'closed', color: 'var(--sa-border-2)', count: tSnap.closed },
                    ].map((s) =>
                      s.count > 0 ? (
                        <div key={s.key} className="sa-stacked-seg" style={{ flex: s.count, background: s.color }} />
                      ) : null
                    )}
                  </div>
                  <div className="sa-stacked-legend">
                    {[
                      { color: 'var(--sa-green)', label: 'Active', count: tSnap.active },
                      { color: 'var(--sa-blue)', label: 'Trial', count: tSnap.trial },
                      { color: 'var(--sa-amber)', label: 'Past Due', count: tSnap.past_due },
                      { color: 'var(--sa-red)', label: 'Suspended', count: tSnap.suspended },
                      { color: 'var(--sa-border-2)', label: 'Closed', count: tSnap.closed },
                    ].map((l) => (
                      <div className="sa-leg" key={l.label}>
                        <div className="sa-leg-dot" style={{ background: l.color }} />
                        {l.label} <b>{fmtNum(l.count)}</b>
                      </div>
                    ))}
                  </div>
                </div>
              ) : (
                <div style={{ textAlign: 'center', padding: '30px 0', color: 'var(--sa-text-3)' }}>
                  No tenant data available
                </div>
              )}
            </div>
          </div>

          {/* Subscription Status */}
          <div className="sa-panel">
            <div className="sa-ph"><div className="sa-ph-t">Subscription Status</div></div>
            <div className="sa-pb">
              {subTotal > 0 ? (
                <div className="sa-stacked-wrap">
                  <div className="sa-stacked-bar">
                    {[
                      { key: 'active', color: 'var(--sa-green)', count: subSnap.active },
                      { key: 'trial', color: 'var(--sa-blue)', count: subSnap.trial },
                      { key: 'past_due', color: 'var(--sa-amber)', count: subSnap.past_due },
                      { key: 'canceled', color: 'var(--sa-border-2)', count: subSnap.canceled },
                    ].map((s) =>
                      s.count > 0 ? (
                        <div key={s.key} className="sa-stacked-seg" style={{ flex: s.count, background: s.color }} />
                      ) : null
                    )}
                  </div>
                  <div className="sa-stacked-legend">
                    {[
                      { color: 'var(--sa-green)', label: 'Active', count: subSnap.active },
                      { color: 'var(--sa-blue)', label: 'Trial', count: subSnap.trial },
                      { color: 'var(--sa-amber)', label: 'Past Due', count: subSnap.past_due },
                      { color: 'var(--sa-border-2)', label: 'Canceled', count: subSnap.canceled },
                    ].map((l) => (
                      <div className="sa-leg" key={l.label}>
                        <div className="sa-leg-dot" style={{ background: l.color }} />
                        {l.label} <b>{fmtNum(l.count)}</b>
                      </div>
                    ))}
                  </div>
                </div>
              ) : (
                <div style={{ textAlign: 'center', padding: '30px 0', color: 'var(--sa-text-3)' }}>
                  No subscription data available
                </div>
              )}
            </div>
          </div>

          {/* Platform Totals */}
          <div className="sa-panel">
            <div className="sa-ph"><div className="sa-ph-t">Platform Totals</div></div>
            <div className="sa-pb">
              <div className="sa-mrow">
                <div className="sa-ml">Total Tenants</div>
                <div className="sa-mv gr">{fmtNum(data?.totals.tenants ?? 0)}</div>
              </div>
              <div className="sa-mrow">
                <div className="sa-ml">Total Users</div>
                <div className="sa-mv">{fmtNum(data?.totals.users ?? 0)}</div>
              </div>
              <div className="sa-mrow">
                <div className="sa-ml">Active Subs</div>
                <div className="sa-mv gr">{fmtNum(subSnap.active)}</div>
              </div>
              <div className="sa-mrow">
                <div className="sa-ml">Past Due Subs</div>
                <div className={`sa-mv${subSnap.past_due > 0 ? ' am' : ''}`}>{fmtNum(subSnap.past_due)}</div>
              </div>
              <div className="sa-mrow">
                <div className="sa-ml">Canceled Subs</div>
                <div className={`sa-mv${subSnap.canceled > 0 ? ' am' : ''}`}>{fmtNum(subSnap.canceled)}</div>
              </div>
              <div className="sa-mrow">
                <div className="sa-ml">Current MRR</div>
                <div className="sa-mv gr">{centsToDollars(latestMrr, 'USD')}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <style>{`@keyframes spin { to { transform: rotate(360deg); } } .sa-spin { animation: spin 1s linear infinite; }`}</style>
    </>
  );
}
