'use client';

import { Download, Bell } from 'lucide-react';
import { SATopbar, SAButton, SAIconButton } from '../SATopbar';

const sessions = [
  { admin: 'Super Admin', targetName: 'James Carter', targetEmail: 'james@quickfix.com', business: 'QuickFix Electronics', started: 'Mar 7, 2026 07:12', duration: '00:14:22', isLong: false, reason: 'Debugging billing issue', status: 'Active', statusClass: 'b-green' },
  { admin: 'Super Admin', targetName: 'Emily Rodriguez', targetEmail: 'emily@techstar.com', business: 'TechStar Services', started: 'Mar 7, 2026 06:45', duration: '00:08:11', isLong: false, reason: 'Support ticket #4821', status: 'Active', statusClass: 'b-green' },
  { admin: 'John Admin', targetName: 'Sarah Mitchell', targetEmail: 'sarah@metroauto.com', business: 'Metro Auto Care', started: 'Mar 7, 2026 05:30', duration: '00:22:47', isLong: false, reason: 'Onboarding assistance', status: 'Active', statusClass: 'b-green' },
  { admin: 'Super Admin', targetName: 'Michael Brown', targetEmail: 'michael@gadgetguru.com', business: 'Gadget Guru Repairs', started: 'Mar 6, 2026 14:20', duration: '01:45:33', isLong: true, reason: 'Data migration verification', status: 'Completed', statusClass: 'b-blue' },
  { admin: 'John Admin', targetName: 'David Chen', targetEmail: 'david@pioneer.com', business: 'Pioneer Appliance', started: 'Mar 5, 2026 10:05', duration: '00:03:14', isLong: false, reason: 'Account suspension review', status: 'Terminated', statusClass: 'b-red' },
  { admin: 'Super Admin', targetName: 'Lisa Wang', targetEmail: 'lisa@fixitpro.com', business: 'FixIt Pro Workshop', started: 'Mar 4, 2026 16:42', duration: '00:31:08', isLong: false, reason: 'Feature demo for upgrade', status: 'Completed', statusClass: 'b-blue' },
];

export default function SAImpersonationLogContent() {
  return (
    <>
      <SATopbar
        breadcrumb={<>Admin › Business Management › <b>Impersonation Log</b></>}
        title="Impersonation Log"
        actions={
          <>
            <SAIconButton hasNotification><Bell size={18} /></SAIconButton>
            <SAButton variant="ghost" icon={<Download size={14} />}>Export</SAButton>
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
          <div><div className="sa-sc-val">847</div><div className="sa-sc-lbl">Total Sessions</div></div>
        </div>
        <div className="sa-scard">
          <div className="sa-scard-icon" style={{ background: 'var(--sa-green-bg)', color: 'var(--sa-green)' }}>
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
          </div>
          <div><div className="sa-sc-val">3</div><div className="sa-sc-lbl">Active Now</div></div>
        </div>
        <div className="sa-scard">
          <div className="sa-scard-icon" style={{ background: 'var(--sa-blue-bg)', color: 'var(--sa-blue)' }}>
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
          </div>
          <div><div className="sa-sc-val">42</div><div className="sa-sc-lbl">This Month</div></div>
        </div>
      </div>

      {/* Filter Bar */}
      <div className="sa-filter-bar">
        <div className="sa-search-wrap">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
          <input type="text" placeholder="Search by admin or target user…" />
        </div>
        <input type="date" defaultValue="2026-02-01" style={{ padding: '9px 12px', border: '1px solid var(--sa-border)', borderRadius: 'var(--sa-r-sm)', fontSize: 13, fontFamily: 'inherit', background: 'var(--sa-surface)', color: 'var(--sa-text-2)', outline: 'none' }} />
        <input type="date" defaultValue="2026-03-07" style={{ padding: '9px 12px', border: '1px solid var(--sa-border)', borderRadius: 'var(--sa-r-sm)', fontSize: 13, fontFamily: 'inherit', background: 'var(--sa-surface)', color: 'var(--sa-text-2)', outline: 'none' }} />
        <select><option>All Admins</option><option>Super Admin</option><option>John Admin</option></select>
      </div>

      {/* Table */}
      <div className="sa-panel">
        <div className="sa-ph">
          <div>
            <div className="sa-ph-t">Session History</div>
            <div className="sa-ph-s">847 impersonation sessions recorded</div>
          </div>
          <SAButton variant="ghost" icon={<Download size={14} />}>Export</SAButton>
        </div>
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
            </tr>
          </thead>
          <tbody>
            {sessions.map((s, i) => (
              <tr key={i}>
                <td className="sa-td-name">{s.admin}</td>
                <td>
                  <div className="sa-td-name">{s.targetName}</div>
                  <div className="sa-td-sub">{s.targetEmail}</div>
                </td>
                <td>{s.business}</td>
                <td>{s.started}</td>
                <td><span className={`sa-dur${s.isLong ? ' long' : ''}`}>{s.duration}</span></td>
                <td><span className="sa-reason">{s.reason}</span></td>
                <td><span className={`sa-badge sa-${s.statusClass}`}>{s.status}</span></td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      </div>
    </>
  );
}
