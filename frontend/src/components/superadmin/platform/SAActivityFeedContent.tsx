'use client';

import React, { useState, useEffect, useCallback, useRef } from 'react';
import {
  Loader2,
  RefreshCw,
  AlertCircle,
  ChevronLeft,
  ChevronRight,
  Search,
  X,
  Zap,
  UserPlus,
  CreditCard,
  AlertTriangle,
  TrendingUp,
  Clock,
} from 'lucide-react';
import { SATopbar, SAButton } from '../SATopbar';
import {
  listActivityFeed,
  listAdminBusinesses,
} from '@/lib/superadmin';
import type {
  ListActivityFeedParams,
  ActivityFeedKpis,
  ActivityFeedTabCounts,
  ActivityFeedRecentSignup,
} from '@/lib/superadmin';
import type { PlatformAuditLog } from '@/lib/types';

/* ── Helpers ── */

function timeAgo(iso?: string): string {
  if (!iso) return '--';
  const now = Date.now();
  const then = new Date(iso).getTime();
  const diffMs = now - then;
  const mins = Math.floor(diffMs / 60000);
  if (mins < 1) return 'Just now';
  if (mins < 60) return `${mins} min ago`;
  const hours = Math.floor(mins / 60);
  if (hours < 24) return `${hours} hr${hours > 1 ? 's' : ''} ago`;
  const days = Math.floor(hours / 24);
  if (days === 1) {
    return `Yesterday, ${new Date(iso).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' })}`;
  }
  return new Date(iso).toLocaleDateString(undefined, {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

function dayLabel(iso: string): string {
  const d = new Date(iso);
  const today = new Date();
  const yesterday = new Date();
  yesterday.setDate(yesterday.getDate() - 1);

  if (d.toDateString() === today.toDateString()) {
    return `Today \u2014 ${d.toLocaleDateString(undefined, { month: 'long', day: 'numeric', year: 'numeric' })}`;
  }
  if (d.toDateString() === yesterday.toDateString()) {
    return `Yesterday \u2014 ${d.toLocaleDateString(undefined, { month: 'long', day: 'numeric', year: 'numeric' })}`;
  }
  return d.toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
}

function dayKey(iso: string): string {
  return new Date(iso).toDateString();
}

type FeedEventMeta = {
  iconColor: string;
  iconBg: string;
  icon: React.ReactNode;
  title: React.ReactNode;
  desc: string;
  badges: Array<{ label: string; cls: string }>;
};

function getEventMeta(log: PlatformAuditLog): FeedEventMeta {
  const action = log.action || '';
  const tenantName = log.tenant?.name ?? null;
  const actorName = log.actor?.name ?? 'System';
  const reason = log.reason || '';
  const meta = (log.metadata ?? {}) as Record<string, unknown>;

  // Signup / tenant created
  if (action.startsWith('tenant.created') || action.startsWith('auth.register')) {
    return {
      iconColor: 'var(--sa-green)',
      iconBg: 'var(--sa-green-bg)',
      icon: <UserPlus size={16} />,
      title: <>New business registered: {tenantName ? <a href={`/superadmin/businesses`}>{tenantName}</a> : 'Unknown'}</>,
      desc: reason || `${actorName} registered a new business.`,
      badges: [
        { label: 'Signup', cls: 'sa-b-green' },
        ...(meta.plan_name ? [{ label: String(meta.plan_name), cls: 'sa-b-blue' }] : []),
      ],
    };
  }

  // Plan change / upgrade / downgrade
  if (action.startsWith('tenant.plan') || action.startsWith('billing.subscription')) {
    const isUpgrade = action.includes('upgrade') || action.includes('assign');
    return {
      iconColor: 'var(--sa-blue)',
      iconBg: 'var(--sa-blue-bg)',
      icon: <TrendingUp size={16} />,
      title: <>{tenantName ? <a href={`/superadmin/businesses`}>{tenantName}</a> : 'Business'} {isUpgrade ? 'upgraded plan' : 'changed plan'}</>,
      desc: reason || `Plan change processed${actorName !== 'System' ? ` by ${actorName}` : ''}.`,
      badges: [
        { label: isUpgrade ? 'Plan Upgrade' : 'Plan Change', cls: 'sa-b-blue' },
        ...(meta.plan_name ? [{ label: String(meta.plan_name), cls: 'sa-b-purple' }] : []),
      ],
    };
  }

  // Payment / billing event
  if (action.startsWith('billing.payment') || action.startsWith('billing.invoice')) {
    const isFailed = action.includes('failed');
    return {
      iconColor: isFailed ? 'var(--sa-red)' : 'var(--sa-green)',
      iconBg: isFailed ? 'var(--sa-red-bg)' : 'var(--sa-green-bg)',
      icon: isFailed ? <AlertTriangle size={16} /> : <CreditCard size={16} />,
      title: <>{isFailed ? 'Payment failed' : 'Payment received'}{tenantName ? <> for <a href={`/superadmin/businesses`}>{tenantName}</a></> : ''}</>,
      desc: reason || (isFailed ? 'Payment processing failed.' : 'Payment processed successfully.'),
      badges: [
        { label: isFailed ? 'Alert' : 'Payment', cls: isFailed ? 'sa-b-red' : 'sa-b-green' },
        ...(isFailed ? [{ label: 'Payment Failed', cls: 'sa-b-amber' }] : []),
        ...(meta.amount ? [{ label: String(meta.amount), cls: 'sa-b-gray' }] : []),
      ],
    };
  }

  // Suspension
  if (action.startsWith('tenant.suspended')) {
    return {
      iconColor: 'var(--sa-red)',
      iconBg: 'var(--sa-red-bg)',
      icon: <AlertTriangle size={16} />,
      title: <>{tenantName ? <a href={`/superadmin/businesses`}>{tenantName}</a> : 'Business'} suspended</>,
      desc: reason || `Business suspended by ${actorName}.`,
      badges: [{ label: 'Suspended', cls: 'sa-b-red' }],
    };
  }

  // Closure / cancellation
  if (action.startsWith('tenant.closed')) {
    return {
      iconColor: 'var(--sa-red)',
      iconBg: 'var(--sa-red-bg)',
      icon: <X size={16} />,
      title: <>{tenantName ? <a href={`/superadmin/businesses`}>{tenantName}</a> : 'Business'} closed</>,
      desc: reason || `Business account closed.`,
      badges: [{ label: 'Cancellation', cls: 'sa-b-red' }],
    };
  }

  // Unsuspend
  if (action.startsWith('tenant.unsuspend')) {
    return {
      iconColor: 'var(--sa-green)',
      iconBg: 'var(--sa-green-bg)',
      icon: <Zap size={16} />,
      title: <>{tenantName ? <a href={`/superadmin/businesses`}>{tenantName}</a> : 'Business'} reactivated</>,
      desc: reason || `Business reactivated by ${actorName}.`,
      badges: [{ label: 'Reactivated', cls: 'sa-b-green' }],
    };
  }

  // Auth events
  if (action.startsWith('auth.login')) {
    return {
      iconColor: 'var(--sa-green)',
      iconBg: 'var(--sa-green-bg)',
      icon: <UserPlus size={16} />,
      title: <>{actorName} logged in</>,
      desc: reason || `Login from ${log.ip || 'unknown IP'}.`,
      badges: [{ label: 'Login', cls: 'sa-b-green' }],
    };
  }

  if (action.startsWith('auth.failed') || action.startsWith('auth.lockout')) {
    return {
      iconColor: 'var(--sa-red)',
      iconBg: 'var(--sa-red-bg)',
      icon: <AlertTriangle size={16} />,
      title: <>Failed login attempt{log.ip ? ` from ${log.ip}` : ''}</>,
      desc: reason || 'Authentication failure detected.',
      badges: [{ label: 'Alert', cls: 'sa-b-red' }, { label: 'Auth Failed', cls: 'sa-b-amber' }],
    };
  }

  // Impersonation
  if (action.startsWith('impersonation.')) {
    return {
      iconColor: 'var(--sa-purple)',
      iconBg: 'var(--sa-purple-bg)',
      icon: <UserPlus size={16} />,
      title: <>{actorName} {action.includes('start') ? 'started' : 'ended'} impersonation{tenantName ? <> for <a href={`/superadmin/businesses`}>{tenantName}</a></> : ''}</>,
      desc: reason || `Impersonation session ${action.includes('start') ? 'started' : 'ended'}.`,
      badges: [{ label: 'Impersonation', cls: 'sa-b-purple' }],
    };
  }

  // Settings / platform changes
  if (action.startsWith('platform.') || action.startsWith('currency.')) {
    return {
      iconColor: 'var(--sa-orange)',
      iconBg: 'var(--sa-orange-bg)',
      icon: <Zap size={16} />,
      title: <>Platform settings updated{actorName !== 'System' ? ` by ${actorName}` : ''}</>,
      desc: reason || actionToLabel(action),
      badges: [{ label: 'Settings', cls: 'sa-b-orange' }],
    };
  }

  // Default / generic
  return {
    iconColor: 'var(--sa-text-2)',
    iconBg: 'var(--sa-surface-2)',
    icon: <Zap size={16} />,
    title: <>{actionToLabel(action)}</>,
    desc: reason || `Performed by ${actorName}.`,
    badges: [{ label: actionCategoryLabel(action), cls: 'sa-b-gray' }],
  };
}

function actionToLabel(action: string): string {
  return action
    .replace(/\./g, ' ')
    .split(' ')
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
    .join(' ');
}

function actionCategoryLabel(action: string): string {
  const prefix = action.split('.')[0];
  return prefix.charAt(0).toUpperCase() + prefix.slice(1);
}

function getInitials(name: string): string {
  return name
    .split(' ')
    .map((w) => w[0])
    .join('')
    .toUpperCase()
    .slice(0, 2);
}

const AVATAR_COLORS = [
  { bg: 'var(--sa-green-bg)', color: 'var(--sa-green)' },
  { bg: 'var(--sa-blue-bg)', color: 'var(--sa-blue)' },
  { bg: 'var(--sa-purple-bg)', color: 'var(--sa-purple)' },
  { bg: 'var(--sa-orange-bg)', color: 'var(--sa-orange)' },
  { bg: 'var(--sa-amber-bg)', color: 'var(--sa-amber)' },
];

const TYPE_FILTER_OPTIONS = [
  { value: '', label: 'All Types' },
  { value: 'tenant.created', label: 'Signups' },
  { value: 'tenant.plan', label: 'Plan Changes' },
  { value: 'tenant.closed', label: 'Cancellations' },
  { value: 'billing.', label: 'Payments' },
  { value: 'platform.', label: 'Settings' },
  { value: 'auth.failed', label: 'Alerts' },
];

const RANGE_OPTIONS = [
  { value: 'today', label: 'Today' },
  { value: '7d', label: 'Last 7 days' },
  { value: '30d', label: 'Last 30 days' },
  { value: '90d', label: 'Last 90 days' },
  { value: 'all', label: 'All time' },
];

type TabKey = 'all' | 'signups' | 'billing' | 'alerts';

const TABS: Array<{ key: TabKey; label: string }> = [
  { key: 'all', label: 'All Activity' },
  { key: 'signups', label: 'Signups' },
  { key: 'billing', label: 'Billing' },
  { key: 'alerts', label: 'Alerts' },
];

/* ── Component ── */

export default function SAActivityFeedContent() {
  const [logs, setLogs] = useState<PlatformAuditLog[]>([]);
  const [meta, setMeta] = useState<{ total: number; per_page: number; current_page: number; last_page: number }>({
    total: 0, per_page: 20, current_page: 1, last_page: 1,
  });
  const [kpis, setKpis] = useState<ActivityFeedKpis>({
    events_today: 0, signups_today: 0, plan_changes_today: 0, alerts_today: 0,
  });
  const [tabCounts, setTabCounts] = useState<ActivityFeedTabCounts>({
    all: 0, signups: 0, billing: 0, alerts: 0,
  });
  const [recentSignups, setRecentSignups] = useState<ActivityFeedRecentSignup[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [refreshing, setRefreshing] = useState(false);

  // Filters
  const [searchQuery, setSearchQuery] = useState('');
  const [debouncedQuery, setDebouncedQuery] = useState('');
  const [typeFilter, setTypeFilter] = useState('');
  const [rangeFilter, setRangeFilter] = useState<'today' | '7d' | '30d' | '90d' | 'all'>('today');
  const [activeTab, setActiveTab] = useState<TabKey>('all');
  const [page, setPage] = useState(1);

  // Business list for filter dropdown
  const [businesses, setBusinesses] = useState<Array<{ id: number; name: string }>>([]);

  const [tenantFilter, setTenantFilter] = useState<number | undefined>(undefined);

  // Debounce search
  const debounceTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  useEffect(() => {
    if (debounceTimerRef.current) clearTimeout(debounceTimerRef.current);
    debounceTimerRef.current = setTimeout(() => {
      setDebouncedQuery(searchQuery);
      setPage(1);
    }, 400);
    return () => {
      if (debounceTimerRef.current) clearTimeout(debounceTimerRef.current);
    };
  }, [searchQuery]);

  // Fetch businesses for the filter dropdown
  useEffect(() => {
    listAdminBusinesses({ per_page: 200, sort: 'name', dir: 'asc' })
      .then((res) => {
        setBusinesses(res.data.map((t) => ({ id: t.id, name: t.name })));
      })
      .catch(() => {});
  }, []);

  const fetchFeed = useCallback(async (signal: AbortSignal) => {
    setError(null);
    try {
      const params: ListActivityFeedParams = {
        page,
        per_page: 20,
        range: rangeFilter,
        tab: activeTab,
      };
      if (debouncedQuery) params.q = debouncedQuery;
      if (typeFilter) params.type = typeFilter;
      if (tenantFilter) params.tenant_id = tenantFilter;

      const result = await listActivityFeed(params);
      if (!signal.aborted) {
        setLogs(result.data);
        setMeta(result.meta);
        setKpis(result.kpis);
        setTabCounts(result.tab_counts);
        setRecentSignups(result.recent_signups);
      }
    } catch (err) {
      if (!signal.aborted) {
        setError(err instanceof Error ? err.message : 'Failed to load activity feed.');
      }
    }
  }, [page, debouncedQuery, typeFilter, rangeFilter, activeTab, tenantFilter]);

  useEffect(() => {
    const controller = new AbortController();
    setLoading(true);
    fetchFeed(controller.signal).finally(() => {
      if (!controller.signal.aborted) setLoading(false);
    });
    return () => controller.abort();
  }, [fetchFeed]);

  const handleRefresh = async () => {
    setRefreshing(true);
    const controller = new AbortController();
    await fetchFeed(controller.signal);
    setRefreshing(false);
  };

  const handleTabChange = (tab: TabKey) => {
    setActiveTab(tab);
    setPage(1);
  };

  const clearSearch = () => {
    setSearchQuery('');
    setDebouncedQuery('');
    setPage(1);
  };

  // Group logs by day
  const dayGroups: Array<{ key: string; label: string; items: PlatformAuditLog[] }> = [];
  for (const log of logs) {
    const key = dayKey(log.created_at ?? '');
    const existing = dayGroups.find((g) => g.key === key);
    if (existing) {
      existing.items.push(log);
    } else {
      dayGroups.push({
        key,
        label: dayLabel(log.created_at ?? ''),
        items: [log],
      });
    }
  }

  /* ── Loading state ── */
  if (loading && logs.length === 0) {
    return (
      <>
        <SATopbar
          breadcrumb={<>Overview &rsaquo; <b>Activity Feed</b></>}
          title="Activity Feed"
          actions={null}
        />
        <div className="sa-content" style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'center', minHeight: 400 }}>
          <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center' }}>
            <Loader2 className="sa-spin" style={{ width: 36, height: 36, color: 'var(--sa-orange)' }} />
            <div style={{ marginTop: 12, color: 'var(--sa-text-2)' }}>Loading activity feed...</div>
          </div>
        </div>
      </>
    );
  }

  /* ── Error state ── */
  if (error && logs.length === 0) {
    return (
      <>
        <SATopbar
          breadcrumb={<>Overview &rsaquo; <b>Activity Feed</b></>}
          title="Activity Feed"
          actions={null}
        />
        <div className="sa-content" style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', minHeight: 400 }}>
          <div style={{ textAlign: 'center', maxWidth: 420 }}>
            <AlertCircle style={{ width: 40, height: 40, color: 'var(--sa-red)', margin: '0 auto 12px' }} />
            <div style={{ fontSize: 18, fontWeight: 600, color: 'var(--sa-red)', marginBottom: 8 }}>
              Failed to load activity feed
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
        breadcrumb={<>Overview &rsaquo; <b>Activity Feed</b></>}
        title="Activity Feed"
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
        {/* KPI Cards */}
        <div className="sa-kpi-row">
          <div className="sa-kc">
            <div className="sa-kc-stripe" style={{ background: 'var(--sa-orange)' }} />
            <div className="sa-kc-icon" style={{ background: 'var(--sa-orange-bg)', color: 'var(--sa-orange)' }}>
              <Zap size={20} />
            </div>
            <div>
              <div className="sa-kc-val">{kpis.events_today.toLocaleString()}</div>
              <div className="sa-kc-lbl">Events Today</div>
            </div>
          </div>
          <div className="sa-kc">
            <div className="sa-kc-stripe" style={{ background: 'var(--sa-green)' }} />
            <div className="sa-kc-icon" style={{ background: 'var(--sa-green-bg)', color: 'var(--sa-green)' }}>
              <UserPlus size={20} />
            </div>
            <div>
              <div className="sa-kc-val">{kpis.signups_today}</div>
              <div className="sa-kc-lbl">New Signups</div>
            </div>
          </div>
          <div className="sa-kc">
            <div className="sa-kc-stripe" style={{ background: 'var(--sa-blue)' }} />
            <div className="sa-kc-icon" style={{ background: 'var(--sa-blue-bg)', color: 'var(--sa-blue)' }}>
              <CreditCard size={20} />
            </div>
            <div>
              <div className="sa-kc-val">{kpis.plan_changes_today}</div>
              <div className="sa-kc-lbl">Plan Changes</div>
            </div>
          </div>
          <div className="sa-kc">
            <div className="sa-kc-stripe" style={{ background: 'var(--sa-red)' }} />
            <div className="sa-kc-icon" style={{ background: 'var(--sa-red-bg)', color: 'var(--sa-red)' }}>
              <AlertTriangle size={20} />
            </div>
            <div>
              <div className="sa-kc-val">{kpis.alerts_today}</div>
              <div className="sa-kc-lbl">Alerts</div>
            </div>
          </div>
        </div>

        {/* Filter Bar */}
        <div className="sa-filter-bar">
          <div className="sa-search-wrap">
            <Search size={16} style={{ color: 'var(--sa-text-3)' }} />
            <input
              type="text"
              placeholder="Search events..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
            />
            {searchQuery && (
              <button
                onClick={clearSearch}
                style={{ background: 'none', border: 'none', cursor: 'pointer', padding: 4, color: 'var(--sa-text-3)' }}
                aria-label="Clear search"
              >
                <X size={14} />
              </button>
            )}
          </div>
          <select
            value={typeFilter}
            onChange={(e) => { setTypeFilter(e.target.value); setPage(1); }}
          >
            {TYPE_FILTER_OPTIONS.map((opt) => (
              <option key={opt.value} value={opt.value}>{opt.label}</option>
            ))}
          </select>
          <select
            value={tenantFilter ?? ''}
            onChange={(e) => { setTenantFilter(e.target.value ? Number(e.target.value) : undefined); setPage(1); }}
          >
            <option value="">All Businesses</option>
            {businesses.map((b) => (
              <option key={b.id} value={b.id}>{b.name}</option>
            ))}
          </select>
          <select
            value={rangeFilter}
            onChange={(e) => { setRangeFilter(e.target.value as typeof rangeFilter); setPage(1); }}
          >
            {RANGE_OPTIONS.map((opt) => (
              <option key={opt.value} value={opt.value}>{opt.label}</option>
            ))}
          </select>
        </div>

        {/* Error banner */}
        {error && logs.length > 0 && (
          <div style={{
            padding: '10px 16px',
            background: 'var(--sa-red-bg)',
            color: 'var(--sa-red)',
            borderRadius: 'var(--sa-r)',
            display: 'flex',
            alignItems: 'center',
            gap: 8,
          }}>
            <AlertCircle size={16} />
            <span>{error}</span>
            <button onClick={handleRefresh} style={{ marginLeft: 'auto', background: 'none', border: 'none', color: 'var(--sa-red)', cursor: 'pointer', textDecoration: 'underline' }}>Retry</button>
          </div>
        )}

        {/* Feed Layout */}
        <div className="sa-feed-layout">
          {/* Main feed panel */}
          <div className="sa-panel">
            {/* Tabs */}
            <div className="sa-tabs">
              {TABS.map((tab) => (
                <button
                  key={tab.key}
                  className={`sa-tab${activeTab === tab.key ? ' active' : ''}`}
                  onClick={() => handleTabChange(tab.key)}
                >
                  {tab.label}
                  <span className="sa-tab-count">{tabCounts[tab.key].toLocaleString()}</span>
                </button>
              ))}
            </div>

            {/* Feed list */}
            {logs.length > 0 ? (
              <div className="sa-feed-list">
                {dayGroups.map((group) => (
                  <div className="sa-day-group" key={group.key}>
                    <div className="sa-day-label">{group.label}</div>
                    {group.items.map((log) => {
                      const evtMeta = getEventMeta(log);
                      return (
                        <div className="sa-feed-item" key={log.id}>
                          <div className="sa-fi-icon" style={{ background: evtMeta.iconBg, color: evtMeta.iconColor }}>
                            {evtMeta.icon}
                          </div>
                          <div className="sa-fi-body">
                            <div className="sa-fi-head">
                              <span className="sa-fi-title">{evtMeta.title}</span>
                              <span className="sa-fi-time">{timeAgo(log.created_at)}</span>
                            </div>
                            <div className="sa-fi-desc">{evtMeta.desc}</div>
                            <div className="sa-fi-meta">
                              {evtMeta.badges.map((badge, i) => (
                                <span key={i} className={`sa-badge ${badge.cls}`}>{badge.label}</span>
                              ))}
                            </div>
                          </div>
                        </div>
                      );
                    })}
                  </div>
                ))}
              </div>
            ) : (
              <div style={{ textAlign: 'center', padding: '60px 0', color: 'var(--sa-text-3)' }}>
                {debouncedQuery || typeFilter || tenantFilter
                  ? 'No events match your filters.'
                  : 'No activity events recorded yet.'
                }
              </div>
            )}

            {/* Pagination */}
            {meta.last_page > 1 && (
              <div style={{
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
                padding: '14px 20px',
                borderTop: '1px solid var(--sa-border)',
                fontSize: 12,
                color: 'var(--sa-text-3)',
              }}>
                <span>
                  Showing {(meta.current_page - 1) * meta.per_page + 1}&ndash;{Math.min(meta.current_page * meta.per_page, meta.total)} of {meta.total.toLocaleString()} events
                  {loading && <span style={{ marginLeft: 8 }}>(updating...)</span>}
                </span>
                <div style={{ display: 'flex', gap: 4 }}>
                  <button
                    className="sa-pg-btn"
                    disabled={page <= 1}
                    onClick={() => setPage((p) => Math.max(1, p - 1))}
                    aria-label="Previous page"
                  >
                    <ChevronLeft size={16} />
                  </button>
                  {Array.from({ length: Math.min(meta.last_page, 7) }, (_, i) => {
                    let pageNum: number;
                    if (meta.last_page <= 7) {
                      pageNum = i + 1;
                    } else if (page <= 4) {
                      pageNum = i + 1;
                    } else if (page >= meta.last_page - 3) {
                      pageNum = meta.last_page - 6 + i;
                    } else {
                      pageNum = page - 3 + i;
                    }
                    return (
                      <button
                        key={pageNum}
                        className={`sa-pg-btn${pageNum === page ? ' active' : ''}`}
                        onClick={() => setPage(pageNum)}
                      >
                        {pageNum}
                      </button>
                    );
                  })}
                  <button
                    className="sa-pg-btn"
                    disabled={page >= meta.last_page}
                    onClick={() => setPage((p) => Math.min(meta.last_page, p + 1))}
                    aria-label="Next page"
                  >
                    <ChevronRight size={16} />
                  </button>
                </div>
              </div>
            )}
          </div>

          {/* Right sidebar widgets */}
          <div>
            {/* Live Status Widget */}
            <div className="sa-widget">
              <div className="sa-wh">
                <div className="sa-live-dot" />
                Live Status
              </div>
              <div className="sa-wb">
                <div className="sa-stat-row">
                  <span className="sa-stat-lbl">Active Users (now)</span>
                  <span className="sa-stat-val" style={{ color: 'var(--sa-green)' }}>{kpis.active_users?.toLocaleString() ?? '—'}</span>
                </div>
                <div className="sa-stat-row">
                  <span className="sa-stat-lbl">Open Repairs</span>
                  <span className="sa-stat-val">{kpis.open_repairs?.toLocaleString() ?? '—'}</span>
                </div>
                <div className="sa-stat-row">
                  <span className="sa-stat-lbl">API Requests/min</span>
                  <span className="sa-stat-val">{kpis.api_requests_min?.toLocaleString() ?? '—'}</span>
                </div>
                <div className="sa-stat-row">
                  <span className="sa-stat-lbl">Server Load</span>
                  <span className="sa-stat-val" style={{ color: 'var(--sa-green)' }}>{kpis.server_load ?? '—'}{kpis.server_load ? '%' : ''}</span>
                </div>
              </div>
            </div>

            {/* Today's Summary Widget */}
            <div className="sa-widget">
              <div className="sa-wh">
                <TrendingUp size={15} />
                Today&rsquo;s Summary
              </div>
              <div className="sa-wb">
                <div className="sa-stat-row">
                  <span className="sa-stat-lbl">New Signups</span>
                  <span className="sa-stat-val" style={{ color: 'var(--sa-green)' }}>{kpis.signups_today}</span>
                </div>
                <div className="sa-stat-row">
                  <span className="sa-stat-lbl">Trial Conversions</span>
                  <span className="sa-stat-val" style={{ color: 'var(--sa-green)' }}>{kpis.trial_conversions_today ?? 0}</span>
                </div>
                <div className="sa-stat-row">
                  <span className="sa-stat-lbl">Cancellations</span>
                  <span className="sa-stat-val" style={{ color: (kpis.cancellations_today ?? 0) > 0 ? 'var(--sa-red)' : undefined }}>{kpis.cancellations_today ?? 0}</span>
                </div>
                <div className="sa-stat-row">
                  <span className="sa-stat-lbl">Revenue Today</span>
                  <span className="sa-stat-val" style={{ color: 'var(--sa-green)' }}>${kpis.revenue_today?.toLocaleString() ?? '0'}</span>
                </div>
                <div className="sa-stat-row">
                  <span className="sa-stat-lbl">Failed Payments</span>
                  <span className="sa-stat-val" style={{ color: (kpis.failed_payments_today ?? 0) > 0 ? 'var(--sa-red)' : undefined }}>{kpis.failed_payments_today ?? 0}</span>
                </div>
              </div>
            </div>

            {/* Recent Signups Widget */}
            <div className="sa-widget">
              <div className="sa-wh">
                <Clock size={15} />
                Recent Signups
              </div>
              <div className="sa-wb" style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
                {recentSignups.length > 0 ? (
                  recentSignups.map((signup, i) => {
                    const colorPair = AVATAR_COLORS[i % AVATAR_COLORS.length];
                    return (
                      <div className="sa-signup-item" key={signup.tenant_id}>
                        <div
                          className="sa-signup-avatar"
                          style={{ background: colorPair.bg, color: colorPair.color }}
                        >
                          {getInitials(signup.tenant_name)}
                        </div>
                        <div>
                          <div style={{ fontSize: '12.5px', fontWeight: 600 }}>{signup.tenant_name}</div>
                          <div style={{ fontSize: '11px', color: 'var(--sa-text-3)' }}>
                            {timeAgo(signup.created_at)}{signup.plan_name ? ` \u00B7 ${signup.plan_name}` : ''}
                          </div>
                        </div>
                      </div>
                    );
                  })
                ) : (
                  <div style={{ fontSize: '12.5px', color: 'var(--sa-text-3)' }}>No recent signups</div>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
      </>
  );
}
