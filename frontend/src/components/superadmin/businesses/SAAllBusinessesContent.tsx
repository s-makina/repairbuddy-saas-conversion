'use client';

import { useState, useEffect, useCallback } from 'react';
import { useRouter } from 'next/navigation';
import {
  Eye,
  Pencil,
  UserRound,
  Download,
  Plus,
  Bell,
  Loader2,
  ChevronLeft,
  ChevronRight,
  AlertCircle,
  RefreshCw,
  X,
} from 'lucide-react';
import { SATopbar, SAButton, SAIconButton } from '../SATopbar';
import {
  listAdminBusinesses,
  getAdminBusinessStats,
  exportBusinesses,
  suspendBusiness,
  unsuspendBusiness,
  closeBusiness,
  startImpersonation,
} from '@/lib/superadmin';
import type { ListBusinessesParams, AdminBusinessesPayload } from '@/lib/superadmin';
import type { Tenant, TenantStatus } from '@/lib/types';
import { ApiError } from '@/lib/api';

// ─── Helpers ────────────────────────────────────────────────────────────────

const STATUS_BADGE: Record<TenantStatus, { label: string; cls: string }> = {
  active:    { label: 'Active',    cls: 'sa-b-green' },
  trial:     { label: 'Trial',     cls: 'sa-b-blue' },
  past_due:  { label: 'Past Due',  cls: 'sa-b-amber' },
  suspended: { label: 'Suspended', cls: 'sa-b-red' },
  closed:    { label: 'Closed',    cls: 'sa-b-gray' },
};

function formatMrr(tenant: Tenant): string {
  const cents = tenant.billing_snapshot?.mrr_cents;
  const currency =
    tenant.billing_snapshot?.subscription_currency?.toUpperCase() || 'USD';
  if (cents == null || cents === 0) return '\u2014';
  return (
    new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency,
      minimumFractionDigits: 0,
    }).format(cents / 100) + '/mo'
  );
}

function formatDate(dateStr: string | undefined | null): string {
  if (!dateStr) return '\u2014';
  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  }).format(new Date(dateStr));
}

function planName(tenant: Tenant): string {
  return (
    tenant.billing_snapshot?.plan_name || tenant.plan?.name || '\u2014'
  );
}

type SortOption = {
  label: string;
  sort: string;
  dir: 'asc' | 'desc';
};

const SORT_OPTIONS: SortOption[] = [
  { label: 'Newest First', sort: 'created_at', dir: 'desc' },
  { label: 'Oldest First', sort: 'created_at', dir: 'asc' },
  { label: 'A \u2192 Z',  sort: 'name',       dir: 'asc' },
  { label: 'MRR \u2193',  sort: 'mrr',        dir: 'desc' },
];

const STATUS_FILTER_OPTIONS: { label: string; value: string }[] = [
  { label: 'All Statuses', value: '' },
  { label: 'Active',       value: 'active' },
  { label: 'Trial',        value: 'trial' },
  { label: 'Past Due',     value: 'past_due' },
  { label: 'Suspended',    value: 'suspended' },
  { label: 'Closed',       value: 'closed' },
];

type ConfirmAction = {
  type: 'suspend' | 'unsuspend' | 'close' | 'impersonate';
  tenant: Tenant;
};

const PER_PAGE = 15;

// ─── Component ──────────────────────────────────────────────────────────────

export default function SAAllBusinessesContent() {
  const router = useRouter();

  // ── Data state ──
  const [businesses, setBusinesses] = useState<Tenant[]>([]);
  const [meta, setMeta] = useState<AdminBusinessesPayload['meta'] | null>(null);
  const [stats, setStats] = useState<{
    total: number;
    by_status: Record<string, number>;
  } | null>(null);

  // ── Filter / search state ──
  const [search, setSearch] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [planFilter, setPlanFilter] = useState('');
  const [sortIndex, setSortIndex] = useState(0);
  const [page, setPage] = useState(1);

  // ── UI state ──
  const [loading, setLoading] = useState(true);
  const [statsLoading, setStatsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [statsError, setStatsError] = useState<string | null>(null);
  const [exporting, setExporting] = useState(false);

  // ── Confirmation dialog state ──
  const [confirmAction, setConfirmAction] = useState<ConfirmAction | null>(null);
  const [confirmReason, setConfirmReason] = useState('');
  const [confirmBusy, setConfirmBusy] = useState(false);
  const [confirmError, setConfirmError] = useState<string | null>(null);

  // ── Debounced search ──
  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedSearch(search);
      setPage(1);
    }, 300);
    return () => clearTimeout(timer);
  }, [search]);

  // ── Build params ──
  const buildParams = useCallback((): ListBusinessesParams => {
    const sortOpt = SORT_OPTIONS[sortIndex];
    const params: ListBusinessesParams = {
      page,
      per_page: PER_PAGE,
      sort: sortOpt.sort,
      dir: sortOpt.dir,
    };
    if (debouncedSearch) params.q = debouncedSearch;
    if (statusFilter) params.status = statusFilter;
    return params;
  }, [debouncedSearch, statusFilter, sortIndex, page]);

  // ── Fetch businesses ──
  const fetchBusinesses = useCallback(
    async (signal?: AbortSignal) => {
      setLoading(true);
      setError(null);
      try {
        const params = buildParams();
        const result = await listAdminBusinesses(params);
        if (signal?.aborted) return;
        setBusinesses(result.data);
        setMeta(result.meta);
      } catch (err) {
        if (signal?.aborted) return;
        if (err instanceof ApiError) {
          setError(err.message);
        } else if (err instanceof Error && err.name !== 'AbortError') {
          setError(err.message || 'Failed to load businesses');
        }
      } finally {
        if (!signal?.aborted) setLoading(false);
      }
    },
    [buildParams]
  );

  // ── Fetch stats ──
  const fetchStats = useCallback(
    async (signal?: AbortSignal) => {
      setStatsLoading(true);
      setStatsError(null);
      try {
        const params: { q?: string; status?: string } = {};
        if (debouncedSearch) params.q = debouncedSearch;
        if (statusFilter) params.status = statusFilter;
        const result = await getAdminBusinessStats(params);
        if (signal?.aborted) return;
        setStats(result);
      } catch (err) {
        if (signal?.aborted) return;
        if (err instanceof ApiError) {
          setStatsError(err.message);
        } else if (err instanceof Error && err.name !== 'AbortError') {
          setStatsError(err.message || 'Failed to load stats');
        }
      } finally {
        if (!signal?.aborted) setStatsLoading(false);
      }
    },
    [debouncedSearch, statusFilter]
  );

  // ── Effect: load businesses ──
  useEffect(() => {
    const controller = new AbortController();
    fetchBusinesses(controller.signal);
    return () => controller.abort();
  }, [fetchBusinesses]);

  // ── Effect: load stats ──
  useEffect(() => {
    const controller = new AbortController();
    fetchStats(controller.signal);
    return () => controller.abort();
  }, [fetchStats]);

  // ── Retry handler ──
  const handleRetry = useCallback(() => {
    fetchBusinesses();
    fetchStats();
  }, [fetchBusinesses, fetchStats]);

  // ── Export ──
  const handleExport = useCallback(async () => {
    setExporting(true);
    try {
      const sortOpt = SORT_OPTIONS[sortIndex];
      await exportBusinesses({
        q: debouncedSearch || undefined,
        status: statusFilter || undefined,
        sort: sortOpt.sort,
        dir: sortOpt.dir,
      });
    } catch (err) {
      const msg =
        err instanceof ApiError
          ? err.message
          : 'Export failed. Please try again.';
      alert(msg);
    } finally {
      setExporting(false);
    }
  }, [debouncedSearch, statusFilter, sortIndex]);

  // ── Confirmation dialog handlers ──
  const openConfirm = (type: ConfirmAction['type'], tenant: Tenant) => {
    setConfirmAction({ type, tenant });
    setConfirmReason('');
    setConfirmError(null);
    setConfirmBusy(false);
  };

  const closeConfirm = () => {
    if (confirmBusy) return;
    setConfirmAction(null);
    setConfirmReason('');
    setConfirmError(null);
  };

  const executeConfirmAction = async () => {
    if (!confirmAction) return;
    const { type, tenant } = confirmAction;

    if ((type === 'suspend' || type === 'close') && !confirmReason.trim()) {
      setConfirmError('A reason is required.');
      return;
    }

    setConfirmBusy(true);
    setConfirmError(null);

    try {
      switch (type) {
        case 'suspend':
          await suspendBusiness({
            tenantId: tenant.id,
            reason: confirmReason.trim(),
          });
          break;
        case 'unsuspend':
          await unsuspendBusiness({
            tenantId: tenant.id,
            reason: confirmReason.trim() || undefined,
          });
          break;
        case 'close':
          await closeBusiness({
            tenantId: tenant.id,
            reason: confirmReason.trim(),
          });
          break;
        case 'impersonate':
          if (!tenant.owner) {
            setConfirmError('This tenant has no owner to impersonate.');
            setConfirmBusy(false);
            return;
          }
          await startImpersonation({
            tenantId: tenant.id,
            targetUserId: tenant.owner.id,
            reason: confirmReason.trim() || 'Admin impersonation',
            referenceId: `imp-${tenant.id}-${Date.now()}`,
          });
          break;
      }

      closeConfirm();
      // Refresh the list after the action
      fetchBusinesses();
      fetchStats();
    } catch (err) {
      const msg =
        err instanceof ApiError ? err.message : 'Action failed. Please try again.';
      setConfirmError(msg);
    } finally {
      setConfirmBusy(false);
    }
  };

  // ── Pagination helpers ──
  const lastPage = meta?.last_page ?? 1;
  const totalItems = meta?.total ?? 0;

  function pageNumbers(): (number | '...')[] {
    const pages: (number | '...')[] = [];
    if (lastPage <= 7) {
      for (let i = 1; i <= lastPage; i++) pages.push(i);
    } else {
      pages.push(1);
      if (page > 3) pages.push('...');
      const start = Math.max(2, page - 1);
      const end = Math.min(lastPage - 1, page + 1);
      for (let i = start; i <= end; i++) pages.push(i);
      if (page < lastPage - 2) pages.push('...');
      pages.push(lastPage);
    }
    return pages;
  }

  // ── Confirm dialog labels ──
  function confirmTitle(): string {
    if (!confirmAction) return '';
    switch (confirmAction.type) {
      case 'suspend':
        return `Suspend "${confirmAction.tenant.name}"`;
      case 'unsuspend':
        return `Unsuspend "${confirmAction.tenant.name}"`;
      case 'close':
        return `Close "${confirmAction.tenant.name}"`;
      case 'impersonate':
        return `Impersonate "${confirmAction.tenant.name}" owner`;
    }
  }

  function confirmButtonLabel(): string {
    if (!confirmAction) return '';
    if (confirmBusy) return 'Processing...';
    switch (confirmAction.type) {
      case 'suspend':
        return 'Suspend Business';
      case 'unsuspend':
        return 'Unsuspend Business';
      case 'close':
        return 'Close Business';
      case 'impersonate':
        return 'Start Impersonation';
    }
  }

  const reasonRequired =
    confirmAction?.type === 'suspend' || confirmAction?.type === 'close';

  // ── Render ────────────────────────────────────────────────────────────────

  return (
    <>
      <SATopbar
        breadcrumb={
          <>
            Admin &rsaquo; Business Management &rsaquo;{' '}
            <b>All Businesses</b>
          </>
        }
        title="All Businesses"
        actions={
          <>
            <SAIconButton hasNotification>
              <Bell size={18} />
            </SAIconButton>
            <SAButton
              variant="ghost"
              icon={
                exporting ? (
                  <Loader2 size={14} className="sa-spin" />
                ) : (
                  <Download size={14} />
                )
              }
              onClick={handleExport}
              disabled={exporting}
            >
              {exporting ? 'Exporting...' : 'Export'}
            </SAButton>
            <SAButton
              variant="primary"
              icon={<Plus size={14} />}
              onClick={() => router.push('/superadmin/businesses/new')}
            >
              New Tenant
            </SAButton>
          </>
        }
      />

      <div className="sa-content">
        {/* ── Summary Cards ── */}
        <div className="sa-summary-row">
          <div className="sa-scard">
            <div
              className="sa-scard-icon"
              style={{
                background: 'var(--sa-orange-bg)',
                color: 'var(--sa-orange)',
              }}
            >
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                />
              </svg>
            </div>
            <div>
              {statsLoading ? (
                <div className="sa-sc-val sa-skeleton">&nbsp;&nbsp;&nbsp;&nbsp;</div>
              ) : statsError ? (
                <div className="sa-sc-val">&mdash;</div>
              ) : (
                <div className="sa-sc-val">
                  {stats?.total?.toLocaleString() ?? 0}
                </div>
              )}
              <div className="sa-sc-lbl">Total Businesses</div>
            </div>
          </div>

          <div className="sa-scard">
            <div
              className="sa-scard-icon"
              style={{
                background: 'var(--sa-green-bg)',
                color: 'var(--sa-green)',
              }}
            >
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
            </div>
            <div>
              {statsLoading ? (
                <div className="sa-sc-val sa-skeleton">&nbsp;&nbsp;&nbsp;&nbsp;</div>
              ) : statsError ? (
                <div className="sa-sc-val">&mdash;</div>
              ) : (
                <div className="sa-sc-val">
                  {(stats?.by_status?.active ?? 0).toLocaleString()}
                </div>
              )}
              <div className="sa-sc-lbl">Active Tenants</div>
            </div>
          </div>

          <div className="sa-scard">
            <div
              className="sa-scard-icon"
              style={{
                background: 'var(--sa-blue-bg)',
                color: 'var(--sa-blue)',
              }}
            >
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
            </div>
            <div>
              {statsLoading ? (
                <div className="sa-sc-val sa-skeleton">&nbsp;&nbsp;&nbsp;&nbsp;</div>
              ) : statsError ? (
                <div className="sa-sc-val">&mdash;</div>
              ) : (
                <div className="sa-sc-val">
                  {(stats?.by_status?.trial ?? 0).toLocaleString()}
                </div>
              )}
              <div className="sa-sc-lbl">On Trial</div>
            </div>
          </div>
        </div>

        {/* ── Filter Bar ── */}
        <div className="sa-filter-bar">
          <div className="sa-search-wrap">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
              />
            </svg>
            <input
              type="text"
              placeholder="Search businesses\u2026"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
            />
          </div>
          <select
            value={statusFilter}
            onChange={(e) => {
              setStatusFilter(e.target.value);
              setPage(1);
            }}
          >
            {STATUS_FILTER_OPTIONS.map((o) => (
              <option key={o.value} value={o.value}>
                {o.label}
              </option>
            ))}
          </select>
          <select
            value={planFilter}
            onChange={(e) => {
              setPlanFilter(e.target.value);
              setPage(1);
            }}
          >
            <option value="">All Plans</option>
          </select>
          <select
            value={sortIndex}
            onChange={(e) => {
              setSortIndex(Number(e.target.value));
              setPage(1);
            }}
          >
            {SORT_OPTIONS.map((o, i) => (
              <option key={i} value={i}>
                {o.label}
              </option>
            ))}
          </select>
        </div>

        {/* ── Error State ── */}
        {error && !loading && (
          <div
            className="sa-panel"
            style={{
              display: 'flex',
              flexDirection: 'column',
              alignItems: 'center',
              justifyContent: 'center',
              padding: '48px 24px',
              textAlign: 'center',
              gap: 16,
            }}
          >
            <AlertCircle size={40} style={{ color: 'var(--sa-red)' }} />
            <div>
              <div
                style={{
                  fontWeight: 600,
                  fontSize: 16,
                  marginBottom: 4,
                }}
              >
                Failed to load businesses
              </div>
              <div
                style={{
                  color: 'var(--sa-text-2)',
                  fontSize: 14,
                  marginBottom: 16,
                }}
              >
                {error}
              </div>
            </div>
            <SAButton
              variant="outline"
              icon={<RefreshCw size={14} />}
              onClick={handleRetry}
            >
              Retry
            </SAButton>
          </div>
        )}

        {/* ── Main Table Panel ── */}
        {!error && (
          <div className="sa-panel">
            <div className="sa-ph">
              <div>
                <div className="sa-ph-t">Tenant Directory</div>
                <div className="sa-ph-s">
                  {loading
                    ? 'Loading...'
                    : `${totalItems.toLocaleString()} business${totalItems !== 1 ? 'es' : ''} registered`}
                </div>
              </div>
              <div style={{ display: 'flex', gap: 8 }}>
                <SAButton
                  variant="ghost"
                  icon={
                    exporting ? (
                      <Loader2 size={14} className="sa-spin" />
                    ) : (
                      <Download size={14} />
                    )
                  }
                  onClick={handleExport}
                  disabled={exporting}
                >
                  Export
                </SAButton>
                <SAButton
                  variant="primary"
                  icon={<Plus size={14} />}
                  onClick={() => router.push('/superadmin/businesses/new')}
                >
                  New Tenant
                </SAButton>
              </div>
            </div>

            {/* Loading indicator */}
            {loading && (
              <div
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  padding: '64px 24px',
                  gap: 8,
                  color: 'var(--sa-text-2)',
                }}
              >
                <Loader2 size={20} className="sa-spin" />
                <span>Loading businesses...</span>
              </div>
            )}

            {/* Empty state */}
            {!loading && businesses.length === 0 && (
              <div
                style={{
                  display: 'flex',
                  flexDirection: 'column',
                  alignItems: 'center',
                  justifyContent: 'center',
                  padding: '64px 24px',
                  textAlign: 'center',
                  color: 'var(--sa-text-2)',
                  gap: 8,
                }}
              >
                <svg
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                  style={{ width: 40, height: 40, opacity: 0.4 }}
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={1.5}
                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                  />
                </svg>
                <div style={{ fontWeight: 600, fontSize: 15 }}>
                  No businesses found
                </div>
                <div style={{ fontSize: 13 }}>
                  {debouncedSearch || statusFilter
                    ? 'Try adjusting your search or filters.'
                    : 'Create your first tenant to get started.'}
                </div>
                {!debouncedSearch && !statusFilter && (
                  <SAButton
                    variant="primary"
                    icon={<Plus size={14} />}
                    onClick={() => router.push('/superadmin/businesses/new')}
                    style={{ marginTop: 12 }}
                  >
                    New Tenant
                  </SAButton>
                )}
              </div>
            )}

            {/* Data table */}
            {!loading && businesses.length > 0 && (
              <>
                <table className="sa-dt">
                  <thead>
                    <tr>
                      <th>Business</th>
                      <th>Owner</th>
                      <th>Plan</th>
                      <th>Status</th>
                      <th>Users</th>
                      <th>MRR</th>
                      <th>Created</th>
                      <th style={{ width: 100 }}>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {businesses.map((t) => {
                      const badge = STATUS_BADGE[t.status] ?? {
                        label: t.status,
                        cls: 'sa-b-gray',
                      };
                      return (
                        <tr key={t.id}>
                          <td>
                            <div className="sa-td-name">{t.name}</div>
                            <div className="sa-td-sub">
                              {t.slug}.99smartx.com
                            </div>
                          </td>
                          <td>{t.owner?.name ?? '\u2014'}</td>
                          <td>{planName(t)}</td>
                          <td>
                            <span className={`sa-badge ${badge.cls}`}>
                              {badge.label}
                            </span>
                          </td>
                          <td>{t.user_count ?? 0}</td>
                          <td>{formatMrr(t)}</td>
                          <td>{formatDate(t.created_at)}</td>
                          <td>
                            <div className="sa-td-actions">
                              <button
                                className="sa-act-btn"
                                title="View"
                                onClick={() =>
                                  router.push(
                                    `/superadmin/businesses/${t.id}`
                                  )
                                }
                              >
                                <Eye size={13} />
                              </button>
                              <button
                                className="sa-act-btn"
                                title="Edit"
                                onClick={() =>
                                  router.push(
                                    `/superadmin/businesses/${t.id}/edit`
                                  )
                                }
                              >
                                <Pencil size={13} />
                              </button>
                              <button
                                className="sa-act-btn"
                                title="Impersonate"
                                onClick={() =>
                                  openConfirm('impersonate', t)
                                }
                              >
                                <UserRound size={13} />
                              </button>
                            </div>
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>

                {/* ── Pagination ── */}
                {lastPage > 1 && (
                  <div className="sa-pb" style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                    <div style={{ fontSize: 13, color: 'var(--sa-text-2)' }}>
                      Page {page} of {lastPage} ({totalItems.toLocaleString()} total)
                    </div>
                    <div
                      className="sa-pagination"
                      style={{ display: 'flex', alignItems: 'center', gap: 4 }}
                    >
                      <button
                        className="sa-act-btn"
                        disabled={page <= 1}
                        onClick={() => setPage((p) => Math.max(1, p - 1))}
                        title="Previous page"
                        style={{ opacity: page <= 1 ? 0.4 : 1 }}
                      >
                        <ChevronLeft size={14} />
                      </button>
                      {pageNumbers().map((p, i) =>
                        p === '...' ? (
                          <span
                            key={`dots-${i}`}
                            style={{
                              padding: '4px 6px',
                              fontSize: 13,
                              color: 'var(--sa-text-3)',
                            }}
                          >
                            ...
                          </span>
                        ) : (
                          <button
                            key={p}
                            className="sa-act-btn"
                            onClick={() => setPage(p)}
                            style={{
                              fontWeight: p === page ? 700 : 400,
                              background:
                                p === page
                                  ? 'var(--sa-orange-bg)'
                                  : undefined,
                              color:
                                p === page
                                  ? 'var(--sa-orange)'
                                  : undefined,
                              minWidth: 28,
                              textAlign: 'center',
                            }}
                          >
                            {p}
                          </button>
                        )
                      )}
                      <button
                        className="sa-act-btn"
                        disabled={page >= lastPage}
                        onClick={() =>
                          setPage((p) => Math.min(lastPage, p + 1))
                        }
                        title="Next page"
                        style={{ opacity: page >= lastPage ? 0.4 : 1 }}
                      >
                        <ChevronRight size={14} />
                      </button>
                    </div>
                  </div>
                )}
              </>
            )}
          </div>
        )}
      </div>

      {/* ── Confirmation Dialog ── */}
      {confirmAction && (
        <div
          style={{
            position: 'fixed',
            inset: 0,
            zIndex: 9999,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            background: 'rgba(0,0,0,0.4)',
          }}
          onClick={closeConfirm}
        >
          <div
            style={{
              background: '#fff',
              borderRadius: 'var(--sa-r)',
              boxShadow: 'var(--sa-sh-md)',
              padding: '24px',
              width: 440,
              maxWidth: '90vw',
            }}
            onClick={(e) => e.stopPropagation()}
          >
            <div
              style={{
                display: 'flex',
                justifyContent: 'space-between',
                alignItems: 'center',
                marginBottom: 16,
              }}
            >
              <div style={{ fontWeight: 700, fontSize: 16 }}>
                {confirmTitle()}
              </div>
              <button
                className="sa-act-btn"
                onClick={closeConfirm}
                disabled={confirmBusy}
              >
                <X size={16} />
              </button>
            </div>

            <div style={{ marginBottom: 12, fontSize: 14, color: 'var(--sa-text-2)' }}>
              {confirmAction.type === 'impersonate'
                ? `You will be logged in as the owner (${confirmAction.tenant.owner?.name ?? 'N/A'}) of this tenant. All actions will be logged.`
                : confirmAction.type === 'suspend'
                  ? 'This will immediately prevent all users of this business from accessing the platform.'
                  : confirmAction.type === 'unsuspend'
                    ? 'This will restore access for all users of this business.'
                    : 'This will permanently close this business. This action cannot be easily undone.'}
            </div>

            <div style={{ marginBottom: 16 }}>
              <label
                style={{
                  display: 'block',
                  fontSize: 13,
                  fontWeight: 600,
                  marginBottom: 6,
                  color: 'var(--sa-text)',
                }}
              >
                Reason{reasonRequired ? ' *' : ' (optional)'}
              </label>
              <textarea
                value={confirmReason}
                onChange={(e) => setConfirmReason(e.target.value)}
                placeholder={
                  confirmAction.type === 'impersonate'
                    ? 'e.g. Investigating support ticket #1234'
                    : 'Enter a reason...'
                }
                rows={3}
                style={{
                  width: '100%',
                  borderRadius: 'var(--sa-r-sm)',
                  border: '1px solid var(--sa-border)',
                  padding: '8px 12px',
                  fontSize: 14,
                  fontFamily: 'inherit',
                  resize: 'vertical',
                }}
              />
            </div>

            {confirmError && (
              <div
                style={{
                  marginBottom: 12,
                  padding: '8px 12px',
                  background: 'var(--sa-red-bg)',
                  color: 'var(--sa-red)',
                  borderRadius: 'var(--sa-r-sm)',
                  fontSize: 13,
                }}
              >
                {confirmError}
              </div>
            )}

            <div
              style={{ display: 'flex', justifyContent: 'flex-end', gap: 8 }}
            >
              <SAButton
                variant="ghost"
                onClick={closeConfirm}
                disabled={confirmBusy}
              >
                Cancel
              </SAButton>
              <SAButton
                variant={
                  confirmAction.type === 'close' ||
                  confirmAction.type === 'suspend'
                    ? 'primary'
                    : 'primary'
                }
                onClick={executeConfirmAction}
                disabled={confirmBusy}
                icon={
                  confirmBusy ? (
                    <Loader2 size={14} className="sa-spin" />
                  ) : undefined
                }
                style={
                  confirmAction.type === 'close'
                    ? { background: 'var(--sa-red)', borderColor: 'var(--sa-red)' }
                    : confirmAction.type === 'suspend'
                      ? { background: 'var(--sa-amber)', borderColor: 'var(--sa-amber)' }
                      : undefined
                }
              >
                {confirmButtonLabel()}
              </SAButton>
            </div>
          </div>
        </div>
      )}

      {/* ── Spinner keyframes ── */}
      <style>{`
        @keyframes sa-spin-kf {
          to { transform: rotate(360deg); }
        }
        .sa-spin {
          animation: sa-spin-kf 0.8s linear infinite;
        }
        .sa-skeleton {
          background: linear-gradient(90deg, var(--sa-surface-2) 25%, var(--sa-border) 50%, var(--sa-surface-2) 75%);
          background-size: 200% 100%;
          animation: sa-skeleton-kf 1.5s ease-in-out infinite;
          border-radius: 4px;
          color: transparent !important;
          min-width: 48px;
          display: inline-block;
        }
        @keyframes sa-skeleton-kf {
          0% { background-position: 200% 0; }
          100% { background-position: -200% 0; }
        }
      `}</style>
    </>
  );
}
