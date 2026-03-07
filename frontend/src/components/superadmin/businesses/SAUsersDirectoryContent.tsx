'use client';

import { Eye, Ban, UserRound, Download, Bell } from 'lucide-react';
import { SATopbar, SAButton, SAIconButton } from '../SATopbar';

const users = [
  { initials: 'JC', avatarStyle: 'linear-gradient(135deg,#e8590c,#f76707)', name: 'James Carter', role: 'Owner', email: 'james@quickfix.com', business: 'QuickFix Electronics', badgeRole: 'Owner', roleClass: 'b-purple', status: 'Active', statusClass: 'b-green', lastActive: '2 min ago' },
  { initials: 'SM', avatarStyle: 'linear-gradient(135deg,#1971c2,#339af0)', name: 'Sarah Mitchell', role: 'Owner', email: 'sarah@metroauto.com', business: 'Metro Auto Care', badgeRole: 'Owner', roleClass: 'b-purple', status: 'Active', statusClass: 'b-green', lastActive: '15 min ago' },
  { initials: 'MK', avatarStyle: 'linear-gradient(135deg,#2b8a3e,#51cf66)', name: 'Mike Kowalski', role: 'Technician', email: 'mike.k@quickfix.com', business: 'QuickFix Electronics', badgeRole: 'Technician', roleClass: 'b-blue', status: 'Active', statusClass: 'b-green', lastActive: '1 hr ago' },
  { initials: 'DC', avatarStyle: 'linear-gradient(135deg,#7048e8,#9775fa)', name: 'David Chen', role: 'Owner', email: 'david@pioneer.com', business: 'Pioneer Appliance', badgeRole: 'Owner', roleClass: 'b-purple', status: 'Suspended', statusClass: 'b-red', lastActive: '3 days ago' },
  { initials: 'ER', avatarStyle: 'linear-gradient(135deg,#e67700,#fcc419)', name: 'Emily Rodriguez', role: 'Admin', email: 'emily@techstar.com', business: 'TechStar Services', badgeRole: 'Admin', roleClass: 'b-amber', status: 'Active', statusClass: 'b-green', lastActive: '30 min ago' },
  { initials: 'RT', avatarStyle: 'linear-gradient(135deg,#e03131,#ff6b6b)', name: 'Robert Taylor', role: 'Manager', email: 'robert@gadgetguru.com', business: 'Gadget Guru Repairs', badgeRole: 'Manager', roleClass: 'b-green', status: 'Active', statusClass: 'b-green', lastActive: '4 hrs ago' },
];

export default function SAUsersDirectoryContent() {
  return (
    <>
      <SATopbar
        breadcrumb={<>Admin › Business Management › <b>Users Directory</b></>}
        title="Users Directory"
        actions={
          <>
            <SAIconButton hasNotification><Bell size={18} /></SAIconButton>
            <SAButton variant="ghost" icon={<Download size={14} />}>Export CSV</SAButton>
          </>
        }
      />
      <div className="sa-content">
      {/* Summary Row */}
      <div className="sa-summary-row">
        <div className="sa-scard">
          <div className="sa-scard-icon" style={{ background: 'var(--sa-orange-bg)', color: 'var(--sa-orange)' }}>
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
          </div>
          <div><div className="sa-sc-val">8,421</div><div className="sa-sc-lbl">Total Users</div></div>
        </div>
        <div className="sa-scard">
          <div className="sa-scard-icon" style={{ background: 'var(--sa-green-bg)', color: 'var(--sa-green)' }}>
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
          </div>
          <div><div className="sa-sc-val">1,204</div><div className="sa-sc-lbl">Active Today</div></div>
        </div>
        <div className="sa-scard">
          <div className="sa-scard-icon" style={{ background: 'var(--sa-blue-bg)', color: 'var(--sa-blue)' }}>
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
          </div>
          <div><div className="sa-sc-val">342</div><div className="sa-sc-lbl">New This Month</div></div>
        </div>
      </div>

      {/* Filter Bar */}
      <div className="sa-filter-bar">
        <div className="sa-search-wrap">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
          <input type="text" placeholder="Search users by name or email…" />
        </div>
        <select><option>All Roles</option><option>Owner</option><option>Admin</option><option>Manager</option><option>Technician</option></select>
        <select><option>All Statuses</option><option>Active</option><option>Inactive</option><option>Suspended</option></select>
      </div>

      {/* Table */}
      <div className="sa-panel">
        <div className="sa-ph">
          <div>
            <div className="sa-ph-t">All Platform Users</div>
            <div className="sa-ph-s">8,421 users across 1,248 businesses</div>
          </div>
          <SAButton variant="ghost" icon={<Download size={14} />}>Export CSV</SAButton>
        </div>
        <table className="sa-dt">
          <thead>
            <tr>
              <th>User</th>
              <th>Email</th>
              <th>Business</th>
              <th>Role</th>
              <th>Status</th>
              <th>Last Active</th>
              <th style={{ width: 100 }}>Actions</th>
            </tr>
          </thead>
          <tbody>
            {users.map((u) => (
              <tr key={u.email}>
                <td>
                  <div className="sa-user-cell">
                    <div className="sa-u-avatar" style={{ background: u.avatarStyle }}>{u.initials}</div>
                    <div>
                      <div className="sa-td-name">{u.name}</div>
                      <div className="sa-td-sub">{u.role}</div>
                    </div>
                  </div>
                </td>
                <td>{u.email}</td>
                <td>{u.business}</td>
                <td><span className={`sa-badge sa-${u.roleClass}`}>{u.badgeRole}</span></td>
                <td><span className={`sa-badge sa-${u.statusClass}`}>{u.status}</span></td>
                <td>{u.lastActive}</td>
                <td>
                  <div className="sa-td-actions">
                    <button className="sa-act-btn" title="View"><Eye size={13} /></button>
                    <button className="sa-act-btn warn" title="Suspend"><Ban size={13} /></button>
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
