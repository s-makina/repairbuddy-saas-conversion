'use client';

import { useState, useEffect, useCallback, useRef } from 'react';
import { Download, Bell, Loader2, ChevronLeft, ChevronRight, AlertCircle, RefreshCw, Square } from 'lucide-react';
import { SATopbar, SAButton, SAIconButton } from '../SATopbar';
import { listImpersonationSessions, stopImpersonation } from '@/lib/superadmin';
import { ApiError } from '@/lib/api';
import type { ImpersonationSession } from '@/lib/types';

// ── Helpers ──────────────────────────────────────────────────────────────────

function formatDuration(startedAt: string, endedAt?: string | null): { text: string; isLong: boolean } {
  const start = new Date(startedAt).getTime();
  const end = endedAt ? new Date(endedAt).getTime() : Date.now();
  const diffMs = Math.max(0, end - start);
  const totalSec = Math.floor(diffMs / 1000);
  const hrs = Math.floor(totalSec / 3600);
  const mins = Math.floor((totalSec % 3600) / 60);
  const secs = totalSec % 60;
  const text = `${String(hrs).padStart(2, '0')}:${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
  return { text, isLong: hrs >= 1 };
}

function getSessionStatus(session: ImpersonationSession): { label: string; className: string } {
  if (session.ended_at) return { label: 'Completed', className: 'sa-b-blue' };
  if (session.expires_at && new Date(session.expires_at) <= new Date()) return { label: 'Expired', className: 'sa-b-red' };
  return { label: 'Active', className: 'sa-b-green' };
}

function formatDate(dateStr: string): string {
  const d = new Date(dateStr);
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
    + ' ' + d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });
}

function getFirstDayOfMonth(): string {
  const now = new Date();
  const y = now.getFullYear();
  const m = String(now.getMonth() + 1).padStart(2, '0');
  return `${y}-${m}-01`;
}

function isSessionActive(session: ImpersonationSession): boolean {
  if (session.ended_at) return false;
  if (session.expires_at && new Date(session.expires_at) <= new Date()) return false;
  return true;
}

// ── Component ────────────────────────────────────────────────────────────────

export default function SAImpersonationLogContent() {
  // Summary stats
  const [totalSessions, setTotalSessions] = useState<number | null>(null);
  const [activeNow, setActiveNow] = useState<number | null>(null);
  const [thisMonth, setThisMonth] = useState<number | null>(null);

  // Table data
  const [sessions, setSessions] = useState<ImpersonationSession[]>([]);
  const [meta, setMeta] = useState<{ total: number; per_page: number; current_page: number; last_page: number } | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Filters
  const [search, setSearch] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [fromDate, setFromDate] = useState('');
  const [toDate, setToDate] = useState('');
  const [page, setPage] = useState(1);
  const perPage = 15;

  // Stop session state
  const [stoppingId, setStoppingId] = useState<number | null>(null);

  // Refs for cleanup
  const abortRef = useRef<AbortController | null>(null);
  const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  // ── Debounced search ────────────────────────────────────────────────────

  useEffect(() => {
    if (debounceRef.current) clearTimeout(debounceRef.current);
    debounceRef.current = setTimeout(() => {
      setDebouncedSearch(search);
      setPage(1);
    }, 300);
    return () => {
      if (debounceRef.current) clearTimeout(debounceRef.current);
    };
  }, [search]);

  // ── Load summary stats ─────────────────────────────────────────────────

  const loadStats = useCallback(async () => {
    try {
      const [totalRes, activeRes, monthRes] = await Promise.all([
        listImpersonationSessions({ per_page: 1 }),
        listImpersonationSessions({ status: 'active', per_page: 1 }),
        listImpersonationSessions({ from: getFirstDayOfMonth(), per_page: 1 }),
      ]);
      setTotalSessions(totalRes.meta.total);
      setActiveNow(activeRes.meta.total);
      setThisMonth(monthRes.meta.total);
    } catch {
      // Stats are non-critical; silently fail
    }
  }, []);

  useEffect(() => {
    loadStats();
  }, [loadStats]);

  // ── Load table data ────────────────────────────────────────────────────

  const loadSessions = useCallback(async () => {
    if (abortRef.current) abortRef.current.abort();
    const controller = new AbortController();
    abortRef.current = controller;

    setLoading(true);
    setError(null);

    try {
      const res = await listImpersonationSessions({
        q: debouncedSearch || undefined,
        from: fromDate || undefined,
        to: toDate || undefined,
        page,
        per_page: perPage,
        sort: 'started_at',
        dir: 'desc',
      });

      if (controller.signal.aborted) return;

      setSessions(res.data);
      setMeta(res.meta);
    } catch (err) {
      if (controller.signal.aborted) return;
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError('Failed to load impersonation sessions.');
      }
    } finally {
      if (!controller.signal.aborted) {
        setLoading(false);
      }
    }
  }, [debouncedSearch, fromDate, toDate, page]);

  useEffect(() => {
    loadSessions();
    return () => {
      if (abortRef.current) abortRef.current.abort();
    };
  }, [loadSessions]);

  // ── Stop session handler ───────────────────────────────────────────────

  const handleStop = async (sessionId: number) => {
    const confirmed = window.confirm('Are you sure you want to stop this impersonation session?');
    if (!confirmed) return;

    setStoppingId(sessionId);
    try {
      await stopImpersonation({ sessionId });
      // Refresh list and stats
      await Promise.all([loadSessions(), loadStats()]);
    } catch (err) {
      if (err instanceof ApiError) {
        alert(`Failed to stop session: ${err.message}`);
      } else {
        alert('Failed to stop session. Please try again.');
      }
    } finally {
      setStoppingId(null);
    }
  };

  // ── Filter change handlers ─────────────────────────────────────────────

  const handleFromChange = (val: string) => {
    setFromDate(val);
    setPage(1);
  };

  const handleToChange = (val: string) => {
    setToDate(val);
    setPage(1);
  };

  // ── Pagination helpers ─────────────────────────────────────────────────

  const lastPage = meta?.last_page ?? 1;
  const currentPage = meta?.current_page ?? 1;

  function getPageNumbers(): (number | '...')[] {
    const pages: (number | '...')[] = [];
    if (lastPage <= 7) {
      for (let i = 1; i <= lastPage; i++) pages.push(i);
    } else {
      pages.push(1);
      if (currentPage > 3) pages.push('...');
      const start = Math.max(2, currentPage - 1);
      const end = Math.min(lastPage - 1, currentPage + 1);
      for (let i = start; i <= end; i++) pages.push(i);
      if (currentPage < lastPage - 2) pages.push('...');
      pages.push(lastPage);
    }
    return pages;
  }

  // ── Render ─────────────────────────────────────────────────────────────

  const dateInputStyle: React.CSSProperties = {
    padding: '9px 12px',
    border: '1px solid var(--sa-border)',
    borderRadius: 'var(--sa-r-sm)',
    fontSize: 13,
    fontFamily: 'inherit',
    background: 'var(--sa-surface)',
    color: 'var(--sa-text-2)',
    outline: 'none',
  };

  return (
    <>
      <SATopbar
        breadcrumb={<>Admin &rsaquo; Business Management &rsaquo; <b>Impersonation Log</b></>}
        title="Impersonation Log"
        actions={
          <>
            <SAIconButton hasNotification><Bell size={18} /></SAIconButton>
            <SAButton variant="ghost" icon={<Download size={14} />} disabled>Export</SAButton>
          </>
        }
      />
      <div className="sa-content">
        {/* Summary Row */}
        <div className="sa-summary-row">
          <div className="sa-scard">
            <div className="sa-scard-icon" style={{ background: 'var(--sa-purple-bg)', color: 'var(--sa-purple)' }}>
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
            </div>
            <div>
              <div className="sa-sc-val">{totalSessions !== null ? totalSessions.toLocaleString() : '...'}</div>
              <div className="sa-sc-lbl">Total Sessions</div>
            </div>
          </div>
          <div className="sa-scard">
            <div className="sa-scard-icon" style={{ background: 'var(--sa-green-bg)', color: 'var(--sa-green)' }}>
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
            </div>
            <div>
              <div className="sa-sc-val">{activeNow !== null ? activeNow.toLocaleString() : '...'}</div>
              <div className="sa-sc-lbl">Active Now</div>
            </div>
          </div>
          <div className="sa-scard">
            <div className="sa-scard-icon" style={{ background: 'var(--sa-blue-bg)', color: 'var(--sa-blue)' }}>
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            </div>
            <div>
              <div className="sa-sc-val">{thisMonth !== null ? thisMonth.toLocaleString() : '...'}</div>
              <div className="sa-sc-lbl">This Month</div>
            </div>
          </div>
        </div>

        {/* Filter Bar */}
        <div className="sa-filter-bar">
          <div className="sa-search-wrap">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            <input
              type="text"
              placeholder="Search by admin or target user..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
            />
          </div>
          <input
            type="date"
            value={fromDate}
            onChange={(e) => handleFromChange(e.target.value)}
            style={dateInputStyle}
          />
          <input
            type="date"
            value={toDate}
            onChange={(e) => handleToChange(e.target.value)}
            style={dateInputStyle}
          />
          <select disabled>
            <option>All Admins</option>
          </select>
        </div>

        {/* Table Panel */}
        <div className="sa-panel">
          <div className="sa-ph">
            <div>
              <div className="sa-ph-t">Session History</div>
              <div className="sa-ph-s">
                {meta ? `${meta.total.toLocaleString()} impersonation session${meta.total !== 1 ? 's' : ''} recorded` : 'Loading...'}
              </div>
            </div>
            <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
              <SAButton variant="ghost" icon={<RefreshCw size={14} />} onClick={() => { loadSessions(); loadStats(); }}>
                Refresh
              </SAButton>
              <SAButton variant="ghost" icon={<Download size={14} />} disabled>Export</SAButton>
            </div>
          </div>

          {/* Error state */}
          {error && (
            <div style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '24px 20px', color: 'var(--sa-red)' }}>
              <AlertCircle size={20} />
              <div style={{ flex: 1 }}>
                <div style={{ fontWeight: 600, marginBottom: 4 }}>Failed to load sessions</div>
                <div style={{ fontSize: 13, opacity: 0.85 }}>{error}</div>
              </div>
              <SAButton variant="outline" icon={<RefreshCw size={14} />} onClick={loadSessions}>
                Retry
              </SAButton>
            </div>
          )}

          {/* Loading state */}
          {loading && !error && (
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 10, padding: '48px 20px', color: 'var(--sa-text-3)' }}>
              <Loader2 size={20} style={{ animation: 'spin 1s linear infinite' }} />
              <span style={{ fontSize: 14 }}>Loading sessions...</span>
            </div>
          )}

          {/* Empty state */}
          {!loading && !error && sessions.length === 0 && (
            <div style={{ textAlign: 'center', padding: '48px 20px', color: 'var(--sa-text-3)' }}>
              <AlertCircle size={32} style={{ marginBottom: 12, opacity: 0.5 }} />
              <div style={{ fontSize: 15, fontWeight: 600, marginBottom: 4, color: 'var(--sa-text-2)' }}>No sessions found</div>
              <div style={{ fontSize: 13 }}>
                {debouncedSearch || fromDate || toDate
                  ? 'Try adjusting your search or filters.'
                  : 'No impersonation sessions have been recorded yet.'}
              </div>
            </div>
          )}

          {/* Data table */}
          {!loading && !error && sessions.length > 0 && (
            <>
              <table className="sa-dt">
                <thead>
                  <tr>
                    <th>Admin User</th>
                    <th>Target User</th>
                    <th>Business</th>
                    <th>Started</th>
                    <th>Duration</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {sessions.map((s) => {
                    const dur = formatDuration(s.started_at, s.ended_at);
                    const status = getSessionStatus(s);
                    const active = isSessionActive(s);
                    const isStopping = stoppingId === s.id;
                    return (
                      <tr key={s.id}>
                        <td className="sa-td-name">{s.actor?.name || 'Unknown'}</td>
                        <td>
                          <div className="sa-td-name">{s.target_user?.name || 'Unknown'}</div>
                          <div className="sa-td-sub">{s.target_user?.email || ''}</div>
                        </td>
                        <td>{s.tenant?.name || '\u2014'}</td>
                        <td>{formatDate(s.started_at)}</td>
                        <td><span className={`sa-dur${dur.isLong ? ' long' : ''}`}>{dur.text}</span></td>
                        <td><span className="sa-reason">{s.reason || '\u2014'}</span></td>
                        <td><span className={`sa-badge ${status.className}`}>{status.label}</span></td>
                        <td className="sa-td-actions">
                          {active && (
                            <button
                              className="sa-act-btn"
                              onClick={() => handleStop(s.id)}
                              disabled={isStopping}
                              title="Stop impersonation session"
                            >
                              {isStopping
                                ? <Loader2 size={14} style={{ animation: 'spin 1s linear infinite' }} />
                                : <Square size={14} />}
                              {isStopping ? 'Stopping...' : 'Stop'}
                            </button>
                          )}
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>

              {/* Pagination */}
              {meta && meta.last_page > 1 && (
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '12px 20px', borderTop: '1px solid var(--sa-border)', fontSize: 13 }}>
                  <div style={{ color: 'var(--sa-text-3)' }}>
                    Page {currentPage} of {lastPage} ({meta.total.toLocaleString()} total)
                  </div>
                  <div style={{ display: 'flex', alignItems: 'center', gap: 4 }}>
                    <button
                      onClick={() => setPage((p) => Math.max(1, p - 1))}
                      disabled={currentPage <= 1}
                      style={{
                        display: 'inline-flex', alignItems: 'center', justifyContent: 'center',
                        width: 32, height: 32, border: '1px solid var(--sa-border)',
                        borderRadius: 'var(--sa-r-sm)', background: 'var(--sa-surface)',
                        cursor: currentPage <= 1 ? 'not-allowed' : 'pointer',
                        opacity: currentPage <= 1 ? 0.4 : 1, color: 'var(--sa-text-2)',
                      }}
                    >
                      <ChevronLeft size={16} />
                    </button>
                    {getPageNumbers().map((p, idx) =>
                      p === '...' ? (
                        <span key={`dots-${idx}`} style={{ padding: '0 6px', color: 'var(--sa-text-3)' }}>...</span>
                      ) : (
                        <button
                          key={p}
                          onClick={() => setPage(p)}
                          style={{
                            display: 'inline-flex', alignItems: 'center', justifyContent: 'center',
                            minWidth: 32, height: 32, padding: '0 8px',
                            border: p === currentPage ? '1px solid var(--sa-orange)' : '1px solid var(--sa-border)',
                            borderRadius: 'var(--sa-r-sm)',
                            background: p === currentPage ? 'var(--sa-orange-bg)' : 'var(--sa-surface)',
                            color: p === currentPage ? 'var(--sa-orange)' : 'var(--sa-text-2)',
                            fontWeight: p === currentPage ? 600 : 400,
                            cursor: 'pointer', fontSize: 13, fontFamily: 'inherit',
                          }}
                        >
                          {p}
                        </button>
                      )
                    )}
                    <button
                      onClick={() => setPage((p) => Math.min(lastPage, p + 1))}
                      disabled={currentPage >= lastPage}
                      style={{
                        display: 'inline-flex', alignItems: 'center', justifyContent: 'center',
                        width: 32, height: 32, border: '1px solid var(--sa-border)',
                        borderRadius: 'var(--sa-r-sm)', background: 'var(--sa-surface)',
                        cursor: currentPage >= lastPage ? 'not-allowed' : 'pointer',
                        opacity: currentPage >= lastPage ? 0.4 : 1, color: 'var(--sa-text-2)',
                      }}
                    >
                      <ChevronRight size={16} />
                    </button>
                  </div>
                </div>
              )}
            </>
          )}
        </div>
      </div>

      {/* Spin animation for Loader2 */}
      <style>{`
        @keyframes spin {
          from { transform: rotate(0deg); }
          to { transform: rotate(360deg); }
        }
      `}</style>
    </>
  );
}
