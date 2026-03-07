'use client';

import { useRouter } from 'next/navigation';
import { Eye, Pencil, UserRound, Download, Plus } from 'lucide-react';
import { SATopbar, SAButton, SAIconButton } from '../SATopbar';
import { Bell } from 'lucide-react';

const businesses = [
  { name: 'QuickFix Electronics', domain: 'quickfix.99smartx.com', owner: 'James Carter', plan: 'Professional', status: 'Active', statusClass: 'b-green', users: 12, mrr: '$89/mo', created: 'Mar 12, 2025' },
  { name: 'Metro Auto Care', domain: 'metro-auto.99smartx.com', owner: 'Sarah Mitchell', plan: 'Basic', status: 'Trial', statusClass: 'b-blue', users: 3, mrr: '—', created: 'Feb 28, 2026' },
  { name: 'Pioneer Appliance Repair', domain: 'pioneer.99smartx.com', owner: 'David Chen', plan: 'Enterprise', status: 'Suspended', statusClass: 'b-red', users: 28, mrr: '$249/mo', created: 'Jan 15, 2025' },
  { name: 'TechStar Services', domain: 'techstar.99smartx.com', owner: 'Emily Rodriguez', plan: 'Professional', status: 'Past Due', statusClass: 'b-amber', users: 8, mrr: '$89/mo', created: 'Dec 22, 2024' },
  { name: 'Gadget Guru Repairs', domain: 'gadgetguru.99smartx.com', owner: 'Michael Brown', plan: 'Enterprise', status: 'Active', statusClass: 'b-green', users: 45, mrr: '$249/mo', created: 'Nov 5, 2024' },
  { name: 'FixIt Pro Workshop', domain: 'fixitpro.99smartx.com', owner: 'Lisa Wang', plan: 'Professional', status: 'Active', statusClass: 'b-green', users: 6, mrr: '$89/mo', created: 'Oct 18, 2024' },
  { name: 'Apex Mobile Repairs', domain: 'apex.99smartx.com', owner: 'Robert Taylor', plan: 'Basic', status: 'Active', statusClass: 'b-green', users: 2, mrr: '$29/mo', created: 'Sep 3, 2024' },
];

export default function SAAllBusinessesContent() {
  const router = useRouter();
  return (
    <>
      <SATopbar
        breadcrumb={<>Admin › Business Management › <b>All Businesses</b></>}
        title="All Businesses"
        actions={
          <>
            <SAIconButton hasNotification><Bell size={18} /></SAIconButton>
            <SAButton variant="ghost" icon={<Download size={14} />}>Export</SAButton>
            <SAButton variant="primary" icon={<Plus size={14} />} onClick={() => router.push('/superadmin/businesses/new')}>New Tenant</SAButton>
          </>
        }
      />
      <div className="sa-content">
      {/* Summary Row */}
      <div className="sa-summary-row">
        <div className="sa-scard">
          <div className="sa-scard-icon" style={{ background: 'var(--sa-orange-bg)', color: 'var(--sa-orange)' }}>
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
          </div>
          <div><div className="sa-sc-val">1,248</div><div className="sa-sc-lbl">Total Businesses</div></div>
        </div>
        <div className="sa-scard">
          <div className="sa-scard-icon" style={{ background: 'var(--sa-green-bg)', color: 'var(--sa-green)' }}>
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          </div>
          <div><div className="sa-sc-val">912</div><div className="sa-sc-lbl">Active Tenants</div></div>
        </div>
        <div className="sa-scard">
          <div className="sa-scard-icon" style={{ background: 'var(--sa-blue-bg)', color: 'var(--sa-blue)' }}>
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          </div>
          <div><div className="sa-sc-val">128</div><div className="sa-sc-lbl">On Trial</div></div>
        </div>
      </div>

      {/* Filter Bar */}
      <div className="sa-filter-bar">
        <div className="sa-search-wrap">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
          <input type="text" placeholder="Search businesses…" />
        </div>
        <select><option>All Statuses</option><option>Active</option><option>Trial</option><option>Past Due</option><option>Suspended</option><option>Closed</option></select>
        <select><option>All Plans</option><option>Basic</option><option>Professional</option><option>Enterprise</option></select>
        <select><option>Newest First</option><option>Oldest First</option><option>A → Z</option><option>MRR ↓</option></select>
      </div>

      {/* Table */}
      <div className="sa-panel">
        <div className="sa-ph">
          <div>
            <div className="sa-ph-t">Tenant Directory</div>
            <div className="sa-ph-s">1,248 businesses registered</div>
          </div>
          <div style={{ display: 'flex', gap: 8 }}>
            <SAButton variant="ghost" icon={<Download size={14} />}>Export</SAButton>
            <SAButton variant="primary" icon={<Plus size={14} />} onClick={() => router.push('/superadmin/businesses/new')}>New Tenant</SAButton>
          </div>
        </div>
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
            {businesses.map((b) => (
              <tr key={b.name}>
                <td>
                  <div className="sa-td-name">{b.name}</div>
                  <div className="sa-td-sub">{b.domain}</div>
                </td>
                <td>{b.owner}</td>
                <td>{b.plan}</td>
                <td><span className={`sa-badge sa-${b.statusClass}`}>{b.status}</span></td>
                <td>{b.users}</td>
                <td>{b.mrr}</td>
                <td>{b.created}</td>
                <td>
                  <div className="sa-td-actions">
                    <button className="sa-act-btn" title="View"><Eye size={13} /></button>
                    <button className="sa-act-btn" title="Edit"><Pencil size={13} /></button>
                    <button className="sa-act-btn" title="Impersonate"><UserRound size={13} /></button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      </div>
    </>
  );
}
