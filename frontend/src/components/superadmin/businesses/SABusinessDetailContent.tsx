'use client';

import { useState, useEffect, useCallback } from 'react';
import { useRouter } from 'next/navigation';
import {
  ArrowLeft,
  Pencil,
  Ban,
  RotateCcw,
  UserCircle,
  Loader2,
  AlertCircle,
  RefreshCw,
} from 'lucide-react';
import { SATopbar, SAButton, SAIconButton } from '../SATopbar';
import {
  getAdminBusiness,
  getAdminBusinessEntitlements,
  getAdminBusinessAudit,
  suspendBusiness,
  unsuspendBusiness,
  startImpersonation,
  listAdminUsers,
} from '@/lib/superadmin';
import type { Tenant, TenantStatus, User } from '@/lib/types';
import { ApiError } from '@/lib/api';

// Status badge configuration
const STATUS_BADGE: Record<TenantStatus, { label: string; cls: string }> = {
  active: { label: 'Active', cls: 'sa-b-green' },
  trial: { label: 'Trial', cls: 'sa-b-blue' },
  past_due: { label: 'Past Due', cls: 'sa-b-amber' },
  suspended: { label: 'Suspended', cls: 'sa-b-red' },
  closed: { label: 'Closed', cls: 'sa-b-gray' },
};

// Role badge configuration
const ROLE_BADGE: Record<string, { label: string; cls: string }> = {
  owner: { label: 'Owner', cls: 'sa-b-orange' },
  admin: { label: 'Admin', cls: 'sa-b-blue' },
  technician: { label: 'Technician', cls: 'sa-b-purple' },
  member: { label: 'Member', cls: 'sa-b-gray' },
};

// Tab configuration
type TabKey = 'overview' | 'users' | 'billing' | 'activity';
const TABS: { key: TabKey; label: string }[] = [
  { key: 'overview', label: 'Overview' },
  { key: 'users', label: 'Users' },
  { key: 'billing', label: 'Billing' },
  { key: 'activity', label: 'Activity' },
];

// Helper functions
function formatDate(dateStr: string | undefined | null): string {
  if (!dateStr) return '---';
  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  }).format(new Date(dateStr));
}

function formatMrr(tenant: Tenant): string {
  const cents = tenant.billing_snapshot?.mrr_cents;
  const currency = tenant.billing_snapshot?.subscription_currency?.toUpperCase() || 'USD';
  if (cents == null || cents === 0) return '---';
  return (
    new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency,
      minimumFractionDigits: 0,
    }).format(cents / 100) + '/mo'
  );
}

function getInitials(name: string): string {
  return name
    .split(' ')
    .map((n) => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2);
}

function getAvatarGradient(name: string): string {
  const gradients = [
    'linear-gradient(135deg, #1971c2, #4dabf7)',
    'linear-gradient(135deg, #2b8a3e, #51cf66)',
    'linear-gradient(135deg, #e8590c, #f76707)',
    'linear-gradient(135deg, #7048e8, #9775fa)',
    'linear-gradient(135deg, #e03131, #ff6b6b)',
    'linear-gradient(135deg, #0ca678, #38d9a9)',
  ];
  let hash = 0;
  for (let i = 0; i < name.length; i++) {
    hash = name.charCodeAt(i) + ((hash << 5) - hash);
  }
  return gradients[Math.abs(hash) % gradients.length];
}

// Props type
interface SABusinessDetailContentProps {
  businessId: number;
}

export default function SABusinessDetailContent({ businessId }: SABusinessDetailContentProps) {
  const router = useRouter();

  // State
  const [tenant, setTenant] = useState<Tenant | null>(null);
  const [entitlements, setEntitlements] = useState<Record<string, unknown> | null>(null);
  const [auditLog, setAuditLog] = useState<unknown[] | null>(null);
  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [activeTab, setActiveTab] = useState<TabKey>('overview');
  const [actionLoading, setActionLoading] = useState(false);
  const [confirmAction, setConfirmAction] = useState<'suspend' | 'unsuspend' | 'impersonate' | null>(null);
  const [confirmReason, setConfirmReason] = useState('');
  const [confirmError, setConfirmError] = useState<string | null>(null);

  // Fetch tenant data
  const fetchData = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const [tenantRes, entitlementsRes, auditRes, usersRes] = await Promise.all([
        getAdminBusiness(businessId),
        getAdminBusinessEntitlements(businessId).catch(() => null),
        getAdminBusinessAudit(businessId, { per_page: 10 }).catch(() => null),
        listAdminUsers({ tenant_id: businessId, per_page: 100 }).catch(() => null),
      ]);
      setTenant(tenantRes.tenant);
      setEntitlements(entitlementsRes?.entitlements ?? null);
      setAuditLog(Array.isArray(auditRes) ? auditRes : (auditRes as any)?.data ?? []);
      setUsers(usersRes?.data ?? []);
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError('Failed to load business details');
      }
    } finally {
      setLoading(false);
    }
  }, [businessId]);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  // Handle suspend/unsuspend
  const handleStatusAction = async () => {
    if (!tenant || !confirmAction) return;

    if ((confirmAction === 'suspend') && !confirmReason.trim()) {
      setConfirmError('A reason is required for suspension.');
      return;
    }

    setActionLoading(true);
    setConfirmError(null);

    try {
      if (confirmAction === 'suspend') {
        await suspendBusiness({ tenantId: tenant.id, reason: confirmReason.trim() });
      } else if (confirmAction === 'unsuspend') {
        await unsuspendBusiness({ tenantId: tenant.id, reason: confirmReason.trim() || undefined });
      }
      setConfirmAction(null);
      setConfirmReason('');
      fetchData();
    } catch (err) {
      setConfirmError(err instanceof ApiError ? err.message : 'Action failed');
    } finally {
      setActionLoading(false);
    }
  };

  // Handle impersonate
  const handleImpersonate = async () => {
    if (!tenant?.owner) {
      setConfirmError('This tenant has no owner to impersonate.');
      return;
    }

    setActionLoading(true);
    setConfirmError(null);

    try {
      await startImpersonation({
        tenantId: tenant.id,
        targetUserId: tenant.owner.id,
        reason: confirmReason.trim() || 'Admin impersonation',
        referenceId: `imp-${tenant.id}-${Date.now()}`,
      });
      // Reset state before redirect
      setConfirmAction(null);
      setConfirmReason('');
      setActionLoading(false);
      // Redirect to app after impersonation
      router.push(`/app/${tenant.slug}`);
    } catch (err) {
      setConfirmError(err instanceof ApiError ? err.message : 'Impersonation failed');
      setActionLoading(false);
    }
  };

  // Loading state
  if (loading) {
    return (
      <>
        <SATopbar
          breadcrumb={<></>}
          title="Loading..."
          actions={<></>}
        />
        <div className="sa-content" style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', minHeight: 400 }}>
          <Loader2 size={24} className="sa-spin" style={{ color: 'var(--sa-orange)' }} />
        </div>
      </>
    );
  }

  // Error state
  if (error || !tenant) {
    return (
      <>
        <SATopbar
          breadcrumb={<></>}
          title="Business Not Found"
          actions={
            <SAButton variant="ghost" onClick={() => router.push('/superadmin/businesses')}>
              <ArrowLeft size={14} /> Back to List
            </SAButton>
          }
        />
        <div className="sa-content">
          <div className="sa-panel" style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', padding: 48, gap: 16 }}>
            <AlertCircle size={40} style={{ color: 'var(--sa-red)' }} />
            <div style={{ fontWeight: 600, fontSize: 16 }}>{error || 'Business not found'}</div>
            <SAButton variant="outline" onClick={fetchData}>
              <RefreshCw size={14} /> Retry
            </SAButton>
          </div>
        </div>
      </>
    );
  }

  const statusBadge = STATUS_BADGE[tenant.status] ?? { label: tenant.status, cls: 'sa-b-gray' };

  // Calculate usage percentages (guard against division by zero)
  const userLimit = (entitlements?.max_users as number | undefined) ?? 25;
  const userCount = tenant.user_count ?? 0;
  const userPercent = userLimit > 0 ? Math.min(100, (userCount / userLimit) * 100) : 0;

  const storageLimit = (entitlements?.max_storage_gb as number | undefined) ?? 10;
  const storageUsedRaw = entitlements?.storage_used_gb;
  const storageUsed = typeof storageUsedRaw === 'number' ? storageUsedRaw : 0;
  const storagePercent = storageLimit > 0 ? Math.min(100, (storageUsed / storageLimit) * 100) : 0;

  const branchLimit = (entitlements?.max_branches as number | undefined) ?? 3;
  const branchCount = (entitlements?.branch_count as number | undefined) ?? 0;
  const branchPercent = branchLimit > 0 ? Math.min(100, (branchCount / branchLimit) * 100) : 0;

  return (
    <>
      <SATopbar
        breadcrumb={
          <>
            Admin &rsaquo; Business Management &rsaquo;{' '}
            <b>{tenant.name}</b>
          </>
        }
        title="Business Detail"
        actions={
          <>
            <SAButton variant="ghost" onClick={() => router.push('/superadmin/businesses')}>
              <ArrowLeft size={14} /> Back
            </SAButton>
            <SAButton variant="ghost" onClick={() => router.push(`/superadmin/businesses/${tenant.id}/edit`)}>
              <Pencil size={14} /> Edit
            </SAButton>
            {tenant.status === 'suspended' ? (
              <SAButton variant="outline" onClick={() => { setConfirmAction('unsuspend'); setConfirmReason(''); setConfirmError(null); }}>
                <RotateCcw size={14} /> Unsuspend
              </SAButton>
            ) : tenant.status !== 'closed' ? (
              <SAButton variant="danger" onClick={() => { setConfirmAction('suspend'); setConfirmReason(''); setConfirmError(null); }}>
                <Ban size={14} /> Suspend
              </SAButton>
            ) : null}
          </>
        }
      />

      <div className="sa-content">
        {/* Business Header */}
        <div className="sa-biz-header">
          <div className="sa-biz-logo" style={{ background: getAvatarGradient(tenant.name) }}>
            {getInitials(tenant.name)}
          </div>
          <div className="sa-biz-info">
            <div className="sa-biz-name">
              {tenant.name}{' '}
              <span className={`sa-badge ${statusBadge.cls}`} style={{ marginLeft: 8, verticalAlign: 'middle' }}>
                {statusBadge.label}
              </span>
            </div>
            <div className="sa-biz-sub">
              {tenant.slug}.repairbuddy.com
              {tenant.billing_country ? ` · ${tenant.billing_country}` : ''}
              {' · Joined '}{formatDate(tenant.created_at)}
            </div>
          </div>
          {tenant.owner && (
            <SAButton variant="ghost" onClick={() => { setConfirmAction('impersonate'); setConfirmReason(''); setConfirmError(null); }}>
              <UserCircle size={14} /> Impersonate Owner
            </SAButton>
          )}
        </div>

        {/* Tab Navigation */}
        <div className="sa-tab-row">
          {TABS.map((tab) => (
            <button
              key={tab.key}
              className={`sa-tab-btn ${activeTab === tab.key ? 'active' : ''}`}
              onClick={() => setActiveTab(tab.key)}
            >
              {tab.label}
              {tab.key === 'users' && tenant.user_count ? ` (${tenant.user_count})` : ''}
            </button>
          ))}
        </div>

        {/* Overview Tab */}
        {activeTab === 'overview' && (
          <>
            <div className="sa-g2" style={{ gridTemplateColumns: '1fr 1fr' }}>
              {/* Account Details Panel */}
              <div className="sa-panel">
                <div className="sa-ph">
                  <div className="sa-ph-t">Account Details</div>
                </div>
                <div className="sa-pb">
                  <div className="sa-mrow">
                    <div className="sa-ml">Owner</div>
                    <div className="sa-mv">{tenant.owner?.name ?? '---'}</div>
                  </div>
                  <div className="sa-mrow">
                    <div className="sa-ml">Email</div>
                    <div className="sa-mv">{tenant.owner?.email ?? tenant.contact_email ?? '---'}</div>
                  </div>
                  <div className="sa-mrow">
                    <div className="sa-ml">Plan</div>
                    <div className="sa-mv">
                      <span className="sa-badge sa-b-blue">
                        {tenant.billing_snapshot?.plan_name ?? tenant.plan?.name ?? 'No Plan'}
                      </span>
                    </div>
                  </div>
                  <div className="sa-mrow">
                    <div className="sa-ml">MRR</div>
                    <div className="sa-mv gr">{formatMrr(tenant)}</div>
                  </div>
                  <div className="sa-mrow">
                    <div className="sa-ml">Billing Interval</div>
                    <div className="sa-mv">
                      {tenant.billing_snapshot?.price_interval
                        ? tenant.billing_snapshot.price_interval.charAt(0).toUpperCase() +
                          tenant.billing_snapshot.price_interval.slice(1) +
                          'ly'
                        : '---'}
                    </div>
                  </div>
                  <div className="sa-mrow">
                    <div className="sa-ml">Next Invoice</div>
                    <div className="sa-mv">
                      {formatDate(tenant.billing_snapshot?.subscription_current_period_end)}
                    </div>
                  </div>
                </div>
              </div>

              {/* Usage & Limits Panel */}
              <div className="sa-panel">
                <div className="sa-ph">
                  <div className="sa-ph-t">Usage & Limits</div>
                </div>
                <div className="sa-pb">
                  <div className="sa-mrow">
                    <div className="sa-ml">Users</div>
                    <div className="sa-mv">{userCount} / {userLimit}</div>
                  </div>
                  <div className="sa-prog-bar">
                    <div
                      className="sa-prog-fill"
                      style={{ width: `${userPercent}%`, background: userPercent > 80 ? 'var(--sa-red)' : 'var(--sa-blue)' }}
                    />
                  </div>

                  <div className="sa-mrow" style={{ marginTop: 12 }}>
                    <div className="sa-ml">Storage</div>
                    <div className={`sa-mv ${storagePercent > 70 ? 'am' : ''}`}>
                      {storageUsed.toFixed(1)} GB / {storageLimit} GB
                    </div>
                  </div>
                  <div className="sa-prog-bar">
                    <div
                      className="sa-prog-fill"
                      style={{ width: `${storagePercent}%`, background: storagePercent > 70 ? 'var(--sa-amber)' : 'var(--sa-green)' }}
                    />
                  </div>

                  <div className="sa-mrow" style={{ marginTop: 12 }}>
                    <div className="sa-ml">Branches</div>
                    <div className="sa-mv">{branchCount} / {branchLimit}</div>
                  </div>
                  <div className="sa-prog-bar">
                    <div
                      className="sa-prog-fill"
                      style={{ width: `${branchPercent}%`, background: 'var(--sa-green)' }}
                    />
                  </div>

                  <div className="sa-mrow" style={{ marginTop: 12 }}>
                    <div className="sa-ml">Jobs This Month</div>
                    <div className="sa-mv">{(entitlements?.jobs_this_month as number) ?? 0}</div>
                  </div>
                  <div className="sa-mrow">
                    <div className="sa-ml">Invoices Issued</div>
                    <div className="sa-mv">{(entitlements?.invoices_count as number) ?? 0}</div>
                  </div>
                </div>
              </div>
            </div>

            {/* Team Members Table */}
            <div className="sa-panel">
              <div className="sa-ph">
                <div>
                  <div className="sa-ph-t">Team Members</div>
                  <div className="sa-ph-s">{tenant.user_count ?? 0} users in this organization</div>
                </div>
              </div>
              {users.length > 0 ? (
                <table className="sa-dt">
                  <thead>
                    <tr>
                      <th>User</th>
                      <th>Role</th>
                      <th>Last Active</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    {users.map((user) => {
                      const roleBadge = ROLE_BADGE[user.role ?? 'member'] ?? { label: user.role ?? 'Member', cls: 'sa-b-gray' };
                      return (
                        <tr key={user.id}>
                          <td>
                            <div className="sa-user-cell">
                              <div className="sa-uc-av" style={{ background: getAvatarGradient(user.name) }}>
                                {getInitials(user.name)}
                              </div>
                              <div>
                                <div style={{ fontWeight: 600 }}>{user.name}</div>
                                <div style={{ fontSize: 11, color: 'var(--sa-text-3)' }}>{user.email}</div>
                              </div>
                            </div>
                          </td>
                          <td>
                            <span className={`sa-badge ${roleBadge.cls}`}>{roleBadge.label}</span>
                          </td>
                          <td>{formatDate(user.updated_at)}</td>
                          <td>
                            <span className={`sa-badge ${user.status === 'active' ? 'sa-b-green' : 'sa-b-gray'}`}>
                              {user.status === 'active' ? 'Online' : 'Offline'}
                            </span>
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              ) : (
                <div style={{ padding: 20, textAlign: 'center', color: 'var(--sa-text-3)' }}>
                  User list not available. Switch to Users tab for details.
                </div>
              )}
            </div>

            {/* Recent Activity */}
            <div className="sa-panel">
              <div className="sa-ph">
                <div className="sa-ph-t">Recent Activity</div>
              </div>
              <div className="sa-pb">
                {Array.isArray(auditLog) && auditLog.length > 0 ? (
                  auditLog.slice(0, 5).map((entry: any, idx: number) => {
                    const colors = ['var(--sa-green)', 'var(--sa-blue)', 'var(--sa-orange)', 'var(--sa-purple)'];
                    return (
                      <div key={entry.id ?? idx} className="sa-act-row">
                        <div className="sa-act-dot" style={{ background: colors[idx % colors.length] }} />
                        <div className="sa-act-txt">
                          <b>{entry.actor?.name ?? 'System'}</b> {entry.action?.toLowerCase()?.replace(/_/g, ' ') ?? 'performed action'}
                          {entry.tenant?.name ? ` on ${entry.tenant.name}` : ''}
                          <div className="sa-act-time">{formatDate(entry.created_at)}</div>
                        </div>
                      </div>
                    );
                  })
                ) : (
                  <div style={{ textAlign: 'center', color: 'var(--sa-text-3)' }}>No recent activity</div>
                )}
              </div>
            </div>
          </>
        )}

        {/* Users Tab */}
        {activeTab === 'users' && (
          <div className="sa-panel">
            <div className="sa-ph">
              <div>
                <div className="sa-ph-t">Users Directory</div>
                <div className="sa-ph-s">Manage users for this business</div>
              </div>
            </div>
            <div className="sa-pb" style={{ textAlign: 'center', color: 'var(--sa-text-3)', padding: 40 }}>
              User management coming soon. Use the tenant app to manage users directly.
            </div>
          </div>
        )}

        {/* Billing Tab */}
        {activeTab === 'billing' && (
          <div className="sa-panel">
            <div className="sa-ph">
              <div>
                <div className="sa-ph-t">Billing & Subscription</div>
                <div className="sa-ph-s">Manage subscription and invoices</div>
              </div>
            </div>
            <div className="sa-pb" style={{ textAlign: 'center', color: 'var(--sa-text-3)', padding: 40 }}>
              Billing management coming soon.
            </div>
          </div>
        )}

        {/* Activity Tab */}
        {activeTab === 'activity' && (
          <div className="sa-panel">
            <div className="sa-ph">
              <div className="sa-ph-t">Activity Log</div>
            </div>
            <div className="sa-pb">
              {Array.isArray(auditLog) && auditLog.length > 0 ? (
                auditLog.map((entry: any, idx: number) => {
                  const colors = ['var(--sa-green)', 'var(--sa-blue)', 'var(--sa-orange)', 'var(--sa-purple)', 'var(--sa-red)'];
                  return (
                    <div key={entry.id ?? idx} className="sa-act-row">
                      <div className="sa-act-dot" style={{ background: colors[idx % colors.length] }} />
                      <div className="sa-act-txt">
                        <b>{entry.actor?.name ?? 'System'}</b> {entry.action?.toLowerCase()?.replace(/_/g, ' ') ?? 'performed action'}
                        {entry.reason ? ` - ${entry.reason}` : ''}
                        <div className="sa-act-time">{formatDate(entry.created_at)}</div>
                      </div>
                    </div>
                  );
                })
              ) : (
                <div style={{ textAlign: 'center', color: 'var(--sa-text-3)' }}>No activity recorded</div>
              )}
            </div>
          </div>
        )}
      </div>

      {/* Confirmation Dialog */}
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
          onClick={() => { if (!actionLoading) { setConfirmAction(null); setConfirmReason(''); setConfirmError(null); } }}
        >
          <div
            style={{
              background: '#fff',
              borderRadius: 'var(--sa-r)',
              boxShadow: 'var(--sa-sh-md)',
              padding: 24,
              width: 440,
              maxWidth: '90vw',
            }}
            onClick={(e) => e.stopPropagation()}
          >
            <div style={{ fontWeight: 700, fontSize: 16, marginBottom: 16 }}>
              {confirmAction === 'suspend' && `Suspend "${tenant.name}"`}
              {confirmAction === 'unsuspend' && `Unsuspend "${tenant.name}"`}
              {confirmAction === 'impersonate' && `Impersonate ${tenant.owner?.name ?? 'Owner'}`}
            </div>

            <div style={{ marginBottom: 16, fontSize: 14, color: 'var(--sa-text-2)' }}>
              {confirmAction === 'suspend' && 'This will suspend the business. Users will not be able to access the app.'}
              {confirmAction === 'unsuspend' && 'This will restore access to the business.'}
              {confirmAction === 'impersonate' && `You will be logged in as ${tenant.owner?.name ?? 'the owner'}. All actions will be logged.`}
            </div>

            {(confirmAction === 'suspend' || confirmAction === 'unsuspend') && (
              <div className="sa-form-group">
                <label className="sa-label">
                  Reason {confirmAction === 'suspend' && <span className="sa-req">*</span>}
                </label>
                <textarea
                  className="sa-textarea"
                  value={confirmReason}
                  onChange={(e) => setConfirmReason(e.target.value)}
                  placeholder="Enter reason..."
                  rows={3}
                />
              </div>
            )}

            {confirmError && (
              <div style={{ color: 'var(--sa-red)', fontSize: 13, marginBottom: 12 }}>{confirmError}</div>
            )}

            <div style={{ display: 'flex', gap: 8, justifyContent: 'flex-end' }}>
              <SAButton variant="ghost" onClick={() => { setConfirmAction(null); setConfirmReason(''); setConfirmError(null); }} disabled={actionLoading}>
                Cancel
              </SAButton>
              <SAButton
                variant={confirmAction === 'suspend' ? 'primary' : 'outline'}
                onClick={confirmAction === 'impersonate' ? handleImpersonate : handleStatusAction}
                disabled={actionLoading}
              >
                {actionLoading ? 'Processing...' : confirmAction === 'suspend' ? 'Suspend' : confirmAction === 'unsuspend' ? 'Unsuspend' : 'Start Impersonation'}
              </SAButton>
            </div>
          </div>
        </div>
      )}
    </>
  );
}
