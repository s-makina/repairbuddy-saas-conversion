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
} from 'lucide-react';
import { SATopbar, SAButton } from '../SATopbar';
import { listAuditLogs } from '@/lib/superadmin';
import type { ListAuditLogsParams } from '@/lib/superadmin';
import type { PlatformAuditLog } from '@/lib/types';

/* ── Helpers ── */

function fmtTimestamp(iso?: string): string {
  if (!iso) return '--';
  const d = new Date(iso);
  return d.toLocaleString(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  });
}

function fmtTime(iso?: string): string {
  if (!iso) return '--';
  const d = new Date(iso);
  return d.toLocaleTimeString(undefined, {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  });
}

function fmtDate(iso?: string): string {
  if (!iso) return '--';
  const d = new Date(iso);
  return d.toLocaleDateString(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
}

type ActionCategory = {
  prefix: string;
  label: string;
  color: string;
  bgColor: string;
  iconPath: string;
};

const ACTION_CATEGORIES: ActionCategory[] = [
  { prefix: 'auth.', label: 'Authentication', color: 'var(--sa-green)', bgColor: 'var(--sa-green-bg)', iconPath: 'M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1' },
  { prefix: 'tenant.', label: 'Tenant', color: 'var(--sa-blue)', bgColor: 'var(--sa-blue-bg)', iconPath: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
  { prefix: 'impersonation.', label: 'Impersonation', color: 'var(--sa-purple)', bgColor: 'var(--sa-purple-bg)', iconPath: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
  { prefix: 'billing.', label: 'Billing', color: 'var(--sa-green)', bgColor: 'var(--sa-green-bg)', iconPath: 'M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v10a2 2 0 002 2z' },
  { prefix: 'platform.', label: 'Platform', color: 'var(--sa-amber)', bgColor: 'var(--sa-amber-bg)', iconPath: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35' },
  { prefix: 'currency.', label: 'Currency', color: 'var(--sa-amber)', bgColor: 'var(--sa-amber-bg)', iconPath: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
];

function getActionMeta(action: string): ActionCategory {
  for (const cat of ACTION_CATEGORIES) {
    if (action.startsWith(cat.prefix)) return cat;
  }
  return { prefix: '', label: 'System', color: 'var(--sa-text-2)', bgColor: 'var(--sa-bg)', iconPath: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2' };
}

function actionLabel(action: string): string {
  return action
    .replace(/\./g, ' ')
    .split(' ')
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
    .join(' ');
}

const ACTION_FILTER_OPTIONS = [
  { value: '', label: 'All Actions' },
  { value: 'auth.', label: 'Authentication' },
  { value: 'tenant.', label: 'Tenant' },
  { value: 'impersonation.', label: 'Impersonation' },
  { value: 'billing.', label: 'Billing' },
  { value: 'platform.', label: 'Platform' },
  { value: 'currency.', label: 'Currency' },
];

const DATE_RANGE_OPTIONS = [
  { value: '1', label: 'Last 24 Hours' },
  { value: '7', label: 'Last 7 Days' },
  { value: '30', label: 'Last 30 Days' },
  { value: '90', label: 'Last 90 Days' },
  { value: '', label: 'All Time' },
];

/* ── Component ── */

export default function SAAuditLogsContent() {
  const [logs, setLogs] = useState<PlatformAuditLog[]>([]);
  const [meta, setMeta] = useState<{ total: number; per_page: number; current_page: number; last_page: number }>({
    total: 0, per_page: 50, current_page: 1, last_page: 1,
  });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [refreshing, setRefreshing] = useState(false);

  // Filters
  const [searchQuery, setSearchQuery] = useState('');
  const [debouncedQuery, setDebouncedQuery] = useState('');
  const [actionFilter, setActionFilter] = useState('');
  const [dateRange, setDateRange] = useState('30');
  const [page, setPage] = useState(1);

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

  const fetchLogs = useCallback(async (signal: AbortSignal) => {
    setError(null);
    try {
      const params: ListAuditLogsParams = {
        page,
        per_page: 50,
        sort: 'created_at',
        dir: 'desc',
      };
      if (debouncedQuery) params.q = debouncedQuery;
      if (actionFilter) params.action = actionFilter;

      if (dateRange) {
        const days = parseInt(dateRange, 10);
        if (!isNaN(days) && days > 0) {
          const from = new Date();
          from.setDate(from.getDate() - days);
          params.from = from.toISOString().split('T')[0];
        }
      }

      const result = await listAuditLogs(params);
      if (!signal.aborted) {
        setLogs(result.data);
        setMeta(result.meta);
      }
    } catch (err) {
      if (!signal.aborted) {
        setError(err instanceof Error ? err.message : 'Failed to load audit logs.');
      }
    }
  }, [page, debouncedQuery, actionFilter, dateRange]);

  useEffect(() => {
    const controller = new AbortController();
    setLoading(true);
    fetchLogs(controller.signal).finally(() => {
      if (!controller.signal.aborted) setLoading(false);
    });
    return () => controller.abort();
  }, [fetchLogs]);

  const handleRefresh = async () => {
    setRefreshing(true);
    const controller = new AbortController();
    await fetchLogs(controller.signal);
    setRefreshing(false);
  };

  const handleActionFilterChange = (value: string) => {
    setActionFilter(value);
    setPage(1);
  };

  const handleDateRangeChange = (value: string) => {
    setDateRange(value);
    setPage(1);
  };

  const clearSearch = () => {
    setSearchQuery('');
    setDebouncedQuery('');
    setPage(1);
  };

  // Count unique actions for summary
  const actionCounts: Record<string, number> = {};
  for (const log of logs) {
    const cat = getActionMeta(log.action);
    const key = cat.label;
    actionCounts[key] = (actionCounts[key] ?? 0) + 1;
  }

  /* ── Loading state (initial) ── */
  if (loading && logs.length === 0) {
    return (
      <>
        <SATopbar
          breadcrumb={<>Admin &rsaquo; Platform &rsaquo; <b>Audit Logs</b></>}
          title="Audit Logs"
          actions={null}
        />
        <div className="sa-content" style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', minHeight: 400 }}>
          <div style={{ textAlign: 'center' }}>
            <Loader2 className="sa-spinner" style={{ width: 36, height: 36, animation: 'spin 1s linear infinite', color: 'var(--sa-orange)' }} />
            <div style={{ marginTop: 12, color: 'var(--sa-text-2)' }}>Loading audit logs...</div>
          </div>
        </div>
        <style>{`@keyframes spin { to { transform: rotate(360deg); } }`}</style>
      </>
    );
  }

  /* ── Error state (no data) ── */
  if (error && logs.length === 0) {
    return (
      <>
        <SATopbar
          breadcrumb={<>Admin &rsaquo; Platform &rsaquo; <b>Audit Logs</b></>}
          title="Audit Logs"
          actions={null}
        />
        <div className="sa-content" style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', minHeight: 400 }}>
          <div style={{ textAlign: 'center', maxWidth: 420 }}>
            <AlertCircle style={{ width: 40, height: 40, color: 'var(--sa-red)', margin: '0 auto 12px' }} />
            <div style={{ fontSize: 18, fontWeight: 600, color: 'var(--sa-red)', marginBottom: 8 }}>
              Failed to load audit logs
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
        breadcrumb={<>Admin &rsaquo; Platform &rsaquo; <b>Audit Logs</b></>}
        title="Audit Logs"
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
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
            </div>
            <div>
              <div className="sa-kc-val">{meta.total.toLocaleString()}</div>
              <div className="sa-kc-lbl">Total Events</div>
            </div>
          </div>
          <div className="sa-kc">
            <div className="sa-kc-stripe" style={{ background: 'var(--sa-blue)' }} />
            <div className="sa-kc-icon" style={{ background: 'var(--sa-blue-bg)', color: 'var(--sa-blue)' }}>
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <div className="sa-kc-val">{logs.length}</div>
              <div className="sa-kc-lbl">On This Page</div>
            </div>
          </div>
          <div className="sa-kc">
            <div className="sa-kc-stripe" style={{ background: 'var(--sa-green)' }} />
            <div className="sa-kc-icon" style={{ background: 'var(--sa-green-bg)', color: 'var(--sa-green)' }}>
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12" />
              </svg>
            </div>
            <div>
              <div className="sa-kc-val">{meta.last_page}</div>
              <div className="sa-kc-lbl">Total Pages</div>
            </div>
          </div>
          <div className="sa-kc">
            <div className="sa-kc-stripe" style={{ background: 'var(--sa-purple)' }} />
            <div className="sa-kc-icon" style={{ background: 'var(--sa-purple-bg)', color: 'var(--sa-purple)' }}>
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
              </svg>
            </div>
            <div>
              <div className="sa-kc-val">{Object.keys(actionCounts).length}</div>
              <div className="sa-kc-lbl">Action Types</div>
            </div>
          </div>
        </div>

        {/* Filter Bar */}
        <div className="sa-filter-bar">
          <div className="sa-search-wrap">
            <Search size={16} style={{ color: 'var(--sa-text-3)' }} />
            <input
              type="text"
              placeholder="Search events, users, IPs..."
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
            value={actionFilter}
            onChange={(e) => handleActionFilterChange(e.target.value)}
          >
            {ACTION_FILTER_OPTIONS.map((opt) => (
              <option key={opt.value} value={opt.value}>{opt.label}</option>
            ))}
          </select>
          <select
            value={dateRange}
            onChange={(e) => handleDateRangeChange(e.target.value)}
          >
            {DATE_RANGE_OPTIONS.map((opt) => (
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
            marginBottom: 16,
            display: 'flex',
            alignItems: 'center',
            gap: 8,
          }}>
            <AlertCircle size={16} />
            <span>{error}</span>
            <button onClick={handleRefresh} style={{ marginLeft: 'auto', background: 'none', border: 'none', color: 'var(--sa-red)', cursor: 'pointer', textDecoration: 'underline' }}>Retry</button>
          </div>
        )}

        {/* Table */}
        <div className="sa-panel">
          <div className="sa-ph">
            <div>
              <div className="sa-ph-t">Event Log</div>
              <div className="sa-ph-s">
                {meta.total > 0
                  ? `Showing ${(meta.current_page - 1) * meta.per_page + 1}–${Math.min(meta.current_page * meta.per_page, meta.total)} of ${meta.total.toLocaleString()} events`
                  : 'No events found'
                }
                {loading && <span style={{ marginLeft: 8, color: 'var(--sa-text-3)' }}>(updating...)</span>}
              </div>
            </div>
          </div>

          {logs.length > 0 ? (
            <table className="sa-dt">
              <thead>
                <tr>
                  <th>Timestamp</th>
                  <th>Action</th>
                  <th>Actor</th>
                  <th>IP Address</th>
                  <th>Tenant</th>
                  <th>Details</th>
                </tr>
              </thead>
              <tbody>
                {logs.map((log) => {
                  const cat = getActionMeta(log.action);
                  return (
                    <tr key={log.id}>
                      <td>
                        <div className="sa-td-name">{fmtDate(log.created_at)}</div>
                        <div style={{ fontSize: 11, color: 'var(--sa-text-3)' }}>{fmtTime(log.created_at)}</div>
                      </td>
                      <td>
                        <div className="sa-evt">
                          <div className="sa-evt-icon" style={{ background: cat.bgColor, color: cat.color }}>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d={cat.iconPath} />
                            </svg>
                          </div>
                          <div>
                            <div className="sa-td-name">{actionLabel(log.action)}</div>
                            <div style={{ fontSize: 11, color: 'var(--sa-text-3)' }}>{cat.label}</div>
                          </div>
                        </div>
                      </td>
                      <td>
                        {log.actor ? (
                          <div>
                            <div className="sa-td-name">{log.actor.name}</div>
                            <div style={{ fontSize: 11, color: 'var(--sa-text-3)' }}>{log.actor.email}</div>
                          </div>
                        ) : (
                          <span style={{ color: 'var(--sa-text-3)' }}>System</span>
                        )}
                      </td>
                      <td>
                        <span className="sa-ip">{log.ip ?? '--'}</span>
                      </td>
                      <td>
                        {log.tenant_id ? (
                          <span style={{ fontSize: 12 }}>
                            {(log as PlatformAuditLog & { tenant?: { name: string } }).tenant?.name ?? `#${log.tenant_id}`}
                          </span>
                        ) : (
                          <span style={{ color: 'var(--sa-text-3)' }}>--</span>
                        )}
                      </td>
                      <td>
                        <span style={{ fontSize: 12, color: 'var(--sa-text-2)', maxWidth: 250, display: 'inline-block', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                          {log.reason || '--'}
                        </span>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          ) : (
            <div style={{ textAlign: 'center', padding: '60px 0', color: 'var(--sa-text-3)' }}>
              {debouncedQuery || actionFilter
                ? 'No events match your filters.'
                : 'No audit log events recorded yet.'
              }
            </div>
          )}

          {/* Pagination */}
          {meta.last_page > 1 && (
            <div style={{
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'space-between',
              padding: '12px 20px',
              borderTop: '1px solid var(--sa-border)',
            }}>
              <div style={{ fontSize: 13, color: 'var(--sa-text-2)' }}>
                Page {meta.current_page} of {meta.last_page}
              </div>
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
      </div>
      <style>{`@keyframes spin { to { transform: rotate(360deg); } } .sa-spin { animation: spin 1s linear infinite; }`}</style>
    </>
  );
}
