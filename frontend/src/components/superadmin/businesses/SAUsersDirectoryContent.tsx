'use client';

import { useState, useEffect, useCallback, useRef } from 'react';
import {
  Eye,
  Ban,
  UserRound,
  Download,
  Bell,
  Loader2,
  ChevronLeft,
  ChevronRight,
  AlertCircle,
  RefreshCw,
} from 'lucide-react';
import { SATopbar, SAButton, SAIconButton } from '../SATopbar';
import { listAdminUsers, startImpersonation } from '@/lib/superadmin';
import { ApiError } from '@/lib/api';
import type { AdminUser, PaginatedResponse } from '@/lib/types';

// ─── Helpers ────────────────────────────────────────────────────────────────

function getInitials(name: string): string {
  const parts = name.trim().split(/\s+/);
  if (parts.length >= 2)
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  return name.slice(0, 2).toUpperCase();
}

function getAvatarGradient(name: string): string {
  const gradients = [
    'linear-gradient(135deg,#e8590c,#f76707)',
    'linear-gradient(135deg,#1971c2,#339af0)',
    'linear-gradient(135deg,#2b8a3e,#51cf66)',
    'linear-gradient(135deg,#7048e8,#9775fa)',
    'linear-gradient(135deg,#e67700,#fcc419)',
    'linear-gradient(135deg,#e03131,#ff6b6b)',
  ];
  let hash = 0;
  for (let i = 0; i < name.length; i++)
    hash = name.charCodeAt(i) + ((hash << 5) - hash);
  return gradients[Math.abs(hash) % gradients.length];
}

function roleBadgeClass(role: string | null | undefined): string {
  switch (role?.toLowerCase()) {
    case 'owner':
      return 'sa-b-purple';
    case 'technician':
      return 'sa-b-blue';
    case 'member':
      return 'sa-b-green';
    default:
      return 'sa-b-amber';
  }
}

function statusBadgeClass(status: string | null | undefined): string {
  switch (status?.toLowerCase()) {
    case 'active':
      return 'sa-b-green';
    case 'suspended':
      return 'sa-b-red';
    case 'inactive':
      return 'sa-b-amber';
    case 'pending':
      return 'sa-b-blue';
    default:
      return 'sa-b-amber';
  }
}

function capitalize(str: string | null | undefined): string {
  if (!str) return '—';
  return str.charAt(0).toUpperCase() + str.slice(1);
}

function formatDate(dateStr: string | null | undefined): string {
  if (!dateStr) return '—';
  try {
    return new Intl.DateTimeFormat('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
    }).format(new Date(dateStr));
  } catch {
    return '—';
  }
}

const PER_PAGE = 15;

// ─── Component ──────────────────────────────────────────────────────────────

export default function SAUsersDirectoryContent() {
  // ── State ───────────────────────────────────────────────────────────────
  const [users, setUsers] = useState<AdminUser[]>([]);
  const [meta, setMeta] = useState<PaginatedResponse<AdminUser>['meta'] | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const [searchInput, setSearchInput] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [roleFilter, setRoleFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [page, setPage] = useState(1);

  const [impersonatingId, setImpersonatingId] = useState<number | null>(null);

  // Track the latest fetch so we can ignore stale responses
  const fetchIdRef = useRef(0);
  const debounceTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  // ── Debounced search ────────────────────────────────────────────────────
  useEffect(() => {
    if (debounceTimerRef.current) clearTimeout(debounceTimerRef.current);
    debounceTimerRef.current = setTimeout(() => {
      setDebouncedSearch(searchInput);
      setPage(1);
    }, 300);

    return () => {
      if (debounceTimerRef.current) clearTimeout(debounceTimerRef.current);
    };
  }, [searchInput]);

  // ── Fetch users ─────────────────────────────────────────────────────────
  const fetchUsers = useCallback(async () => {
    const id = ++fetchIdRef.current;
    setLoading(true);
    setError(null);

    try {
      const res = await listAdminUsers({
        q: debouncedSearch || undefined,
        role: roleFilter || undefined,
        status: statusFilter || undefined,
        page,
        per_page: PER_PAGE,
      });

      // Only apply if this is still the latest request
      if (id !== fetchIdRef.current) return;

      setUsers(res.data);
      setMeta(res.meta);
    } catch (err) {
      if (id !== fetchIdRef.current) return;

      if (err instanceof ApiError) {
        setError(err.message);
      } else if (err instanceof Error) {
        setError(err.message);
      } else {
        setError('An unexpected error occurred.');
      }
    } finally {
      if (id === fetchIdRef.current) setLoading(false);
    }
  }, [debouncedSearch, roleFilter, statusFilter, page]);

  useEffect(() => {
    fetchUsers();
  }, [fetchUsers]);

  // ── Filter change helpers (reset to page 1) ────────────────────────────
  const handleRoleChange = (value: string) => {
    setRoleFilter(value);
    setPage(1);
  };

  const handleStatusChange = (value: string) => {
    setStatusFilter(value);
    setPage(1);
  };

  // ── Impersonation ──────────────────────────────────────────────────────
  const handleImpersonate = async (user: AdminUser) => {
    if (!user.tenant_id) return;
    if (impersonatingId === user.id) return;

    const reason = window.prompt(
      `Enter a reason for impersonating ${user.name}:`
    );
    if (!reason) return;

    setImpersonatingId(user.id);
    try {
      await startImpersonation({
        tenantId: user.tenant_id,
        targetUserId: user.id,
        reason,
        referenceId: `users-dir-${Date.now()}`,
      });
      // Reload after impersonation starts — the app-level impersonation
      // handler will pick up the session.
      window.location.reload();
    } catch (err) {
      const msg =
        err instanceof ApiError
          ? err.message
          : 'Failed to start impersonation.';
      alert(msg);
    } finally {
      setImpersonatingId(null);
    }
  };

  // ── Pagination helpers ─────────────────────────────────────────────────
  const lastPage = meta?.last_page ?? 1;
  const currentPage = meta?.current_page ?? page;

  function pageNumbers(): (number | '...')[] {
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

  // ── Unique business count from loaded data ────────────────────────────
  const filteredCountLabel =
    meta
      ? `Showing ${users.length} of ${meta.total} users`
      : '';

  // ── Render ─────────────────────────────────────────────────────────────
  return (
    <>
      <SATopbar
        breadcrumb={
          <>
            Admin &rsaquo; Business Management &rsaquo;{' '}
            <b>Users Directory</b>
          </>
        }
        title="Users Directory"
        actions={
          <>
            <SAIconButton hasNotification>
              <Bell size={18} />
            </SAIconButton>
            <SAButton variant="ghost" icon={<Download size={14} />}>
              Export CSV
            </SAButton>
          </>
        }
      />

      <div className="sa-content">
        {/* Summary Card */}
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
                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                />
              </svg>
            </div>
            <div>
              <div className="sa-sc-val">
                {loading && !meta
                  ? '...'
                  : meta
                    ? meta.total.toLocaleString()
                    : '—'}
              </div>
              <div className="sa-sc-lbl">Total Users</div>
            </div>
          </div>
        </div>

        {/* Filter Bar */}
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
              placeholder="Search users by name or email..."
              value={searchInput}
              onChange={(e) => setSearchInput(e.target.value)}
            />
          </div>
          <select
            value={roleFilter}
            onChange={(e) => handleRoleChange(e.target.value)}
          >
            <option value="">All Roles</option>
            <option value="owner">Owner</option>
            <option value="member">Member</option>
            <option value="technician">Technician</option>
            <option value="admin">Admin</option>
          </select>
          <select
            value={statusFilter}
            onChange={(e) => handleStatusChange(e.target.value)}
          >
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="suspended">Suspended</option>
            <option value="pending">Pending</option>
          </select>
        </div>

        {/* Table Panel */}
        <div className="sa-panel">
          <div className="sa-ph">
            <div>
              <div className="sa-ph-t">All Platform Users</div>
              <div className="sa-ph-s">{filteredCountLabel}</div>
            </div>
            <SAButton
              variant="ghost"
              icon={<RefreshCw size={14} />}
              onClick={fetchUsers}
              disabled={loading}
            >
              Refresh
            </SAButton>
          </div>

          {/* Loading State */}
          {loading && (
            <div
              style={{
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                gap: 8,
                padding: '48px 0',
                color: 'var(--sa-text-3)',
              }}
            >
              <Loader2 size={20} className="sa-spin" />
              <span>Loading users...</span>
            </div>
          )}

          {/* Error State */}
          {!loading && error && (
            <div
              style={{
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                justifyContent: 'center',
                gap: 12,
                padding: '48px 0',
                color: 'var(--sa-red)',
              }}
            >
              <AlertCircle size={32} />
              <span style={{ fontWeight: 500 }}>{error}</span>
              <SAButton variant="outline" onClick={fetchUsers}>
                Retry
              </SAButton>
            </div>
          )}

          {/* Empty State */}
          {!loading && !error && users.length === 0 && (
            <div
              style={{
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                justifyContent: 'center',
                gap: 8,
                padding: '48px 0',
                color: 'var(--sa-text-3)',
              }}
            >
              <UserRound size={32} />
              <span style={{ fontWeight: 500 }}>No users found</span>
              <span style={{ fontSize: 13 }}>
                Try adjusting your search or filter criteria.
              </span>
            </div>
          )}

          {/* Data Table */}
          {!loading && !error && users.length > 0 && (
            <>
              <table className="sa-dt">
                <thead>
                  <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Business</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last Active</th>
                    <th style={{ width: 120 }}>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {users.map((u) => (
                    <tr key={u.id}>
                      <td>
                        <div className="sa-user-cell">
                          <div
                            className="sa-u-avatar"
                            style={{
                              background: getAvatarGradient(u.name),
                            }}
                          >
                            {getInitials(u.name)}
                          </div>
                          <div>
                            <div className="sa-td-name">{u.name}</div>
                            <div className="sa-td-sub">
                              {capitalize(u.role)}
                            </div>
                          </div>
                        </div>
                      </td>
                      <td>{u.email}</td>
                      <td>{u.tenant?.name ?? '—'}</td>
                      <td>
                        <span
                          className={`sa-badge ${roleBadgeClass(u.role)}`}
                        >
                          {capitalize(u.role)}
                        </span>
                      </td>
                      <td>
                        <span
                          className={`sa-badge ${statusBadgeClass(u.status)}`}
                        >
                          {capitalize(u.status)}
                        </span>
                      </td>
                      <td>{formatDate(u.created_at)}</td>
                      <td>
                        <div className="sa-td-actions">
                          <button
                            className="sa-act-btn"
                            title="View"
                          >
                            <Eye size={13} />
                          </button>
                          <button
                            className="sa-act-btn warn"
                            title="Suspend"
                          >
                            <Ban size={13} />
                          </button>
                          {u.tenant_id && (
                            <button
                              className="sa-act-btn"
                              title="Impersonate"
                              disabled={impersonatingId === u.id}
                              onClick={() => handleImpersonate(u)}
                            >
                              {impersonatingId === u.id ? (
                                <Loader2 size={13} className="sa-spin" />
                              ) : (
                                <UserRound size={13} />
                              )}
                            </button>
                          )}
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>

              {/* Pagination */}
              {lastPage > 1 && (
                <div
                  style={{
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    gap: 4,
                    padding: '16px 0',
                    borderTop: '1px solid var(--sa-border)',
                  }}
                >
                  <button
                    className="sa-act-btn"
                    disabled={currentPage <= 1}
                    onClick={() => setPage((p) => Math.max(1, p - 1))}
                    title="Previous page"
                    style={{ marginRight: 4 }}
                  >
                    <ChevronLeft size={14} />
                  </button>

                  {pageNumbers().map((p, idx) =>
                    p === '...' ? (
                      <span
                        key={`dots-${idx}`}
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
                          minWidth: 28,
                          fontWeight: p === currentPage ? 700 : 400,
                          background:
                            p === currentPage
                              ? 'var(--sa-orange-bg)'
                              : undefined,
                          color:
                            p === currentPage
                              ? 'var(--sa-orange)'
                              : undefined,
                          borderRadius: 'var(--sa-r-sm)',
                        }}
                      >
                        {p}
                      </button>
                    )
                  )}

                  <button
                    className="sa-act-btn"
                    disabled={currentPage >= lastPage}
                    onClick={() =>
                      setPage((p) => Math.min(lastPage, p + 1))
                    }
                    title="Next page"
                    style={{ marginLeft: 4 }}
                  >
                    <ChevronRight size={14} />
                  </button>
                </div>
              )}
            </>
          )}
        </div>
      </div>

      {/* Spinner animation */}
      <style>{`
        @keyframes sa-spin-kf { to { transform: rotate(360deg); } }
        .sa-spin { animation: sa-spin-kf 0.8s linear infinite; }
      `}</style>
    </>
  );
}
