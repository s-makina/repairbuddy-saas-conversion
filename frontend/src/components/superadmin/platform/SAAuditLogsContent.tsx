'use client';

import { Download, Bell } from 'lucide-react';
import { SATopbar, SAButton, SAIconButton } from '../SATopbar';

interface KpiCard {
  stripe: string;
  iconBg: string;
  iconColor: string;
  iconPath: string;
  val: string;
  lbl: string;
}

const kpiCards: KpiCard[] = [
  { stripe: 'var(--sa-orange)', iconBg: 'var(--sa-orange-bg)', iconColor: 'var(--sa-orange)', iconPath: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', val: '124,890', lbl: 'Total Events' },
  { stripe: 'var(--sa-blue)', iconBg: 'var(--sa-blue-bg)', iconColor: 'var(--sa-blue)', iconPath: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', val: '892', lbl: 'Today' },
  { stripe: 'var(--sa-amber)', iconBg: 'var(--sa-amber-bg)', iconColor: 'var(--sa-amber)', iconPath: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z', val: '23', lbl: 'Warnings' },
  { stripe: 'var(--sa-red)', iconBg: 'var(--sa-red-bg)', iconColor: 'var(--sa-red)', iconPath: 'M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', val: '4', lbl: 'Critical' },
];

interface LogEvent {
  time: string;
  evtBg: string;
  evtColor: string;
  evtIconPath: string;
  event: string;
  user: string;
  ip: string;
  severity: string;
  sevClass: string;
  details: string;
}

const events: LogEvent[] = [
  { time: '07:24:18', evtBg: 'var(--sa-green-bg)', evtColor: 'var(--sa-green)', evtIconPath: 'M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1', event: 'User Login', user: 'James Carter', ip: '192.168.1.42', severity: 'Success', sevClass: 'b-green', details: 'Login via SSO — QuickFix Electronics' },
  { time: '07:18:05', evtBg: 'var(--sa-blue-bg)', evtColor: 'var(--sa-blue)', evtIconPath: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35', event: 'Config Change', user: 'Super Admin', ip: '10.0.0.1', severity: 'Info', sevClass: 'b-blue', details: 'Updated SMTP settings' },
  { time: '07:12:33', evtBg: 'var(--sa-purple-bg)', evtColor: 'var(--sa-purple)', evtIconPath: 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z', event: 'Permission Update', user: 'Super Admin', ip: '10.0.0.1', severity: 'Info', sevClass: 'b-blue', details: 'Granted admin role to Emily R. — TechStar' },
  { time: '06:58:41', evtBg: 'var(--sa-red-bg)', evtColor: 'var(--sa-red)', evtIconPath: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z', event: 'Failed Auth', user: 'unknown@hack.com', ip: '203.0.113.42', severity: 'Critical', sevClass: 'b-red', details: '5 failed login attempts — IP blocked' },
  { time: '06:45:19', evtBg: 'var(--sa-amber-bg)', evtColor: 'var(--sa-amber)', evtIconPath: 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4', event: 'Data Export', user: 'John Admin', ip: '10.0.0.2', severity: 'Warning', sevClass: 'b-amber', details: 'Exported 1,248 tenant records to CSV' },
  { time: '06:30:07', evtBg: 'var(--sa-green-bg)', evtColor: 'var(--sa-green)', evtIconPath: 'M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v10a2 2 0 002 2z', event: 'Billing Event', user: 'System', ip: '—', severity: 'Success', sevClass: 'b-green', details: 'Monthly billing run completed — 912 invoices' },
  { time: '06:15:52', evtBg: 'var(--sa-red-bg)', evtColor: 'var(--sa-red)', evtIconPath: 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636', event: 'Account Suspended', user: 'Super Admin', ip: '10.0.0.1', severity: 'Critical', sevClass: 'b-red', details: 'Suspended Pioneer Appliance — payment failure' },
  { time: '05:48:30', evtBg: 'var(--sa-blue-bg)', evtColor: 'var(--sa-blue)', evtIconPath: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', event: 'Impersonation Start', user: 'Super Admin', ip: '10.0.0.1', severity: 'Warning', sevClass: 'b-amber', details: 'Started impersonating James Carter — QuickFix' },
];

export default function SAAuditLogsContent() {
  return (
    <>
      <SATopbar
        breadcrumb={<>Admin › Platform › <b>Audit Logs</b></>}
        title="Audit Logs"
        actions={
          <>
            <SAIconButton hasNotification><Bell size={18} /></SAIconButton>
            <SAButton variant="ghost" icon={<Download size={14} />}>Export</SAButton>
          </>
        }
      />
      <div className="sa-content">
      {/* KPI Cards */}
      <div className="sa-kpi-row">
        {kpiCards.map((k) => (
          <div className="sa-kc" key={k.lbl}>
            <div className="sa-kc-stripe" style={{ background: k.stripe }} />
            <div className="sa-kc-icon" style={{ background: k.iconBg, color: k.iconColor }}>
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d={k.iconPath} /></svg>
            </div>
            <div>
              <div className="sa-kc-val">{k.val}</div>
              <div className="sa-kc-lbl">{k.lbl}</div>
            </div>
          </div>
        ))}
      </div>

      {/* Filter Bar */}
      <div className="sa-filter-bar">
        <div className="sa-search-wrap">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
          <input type="text" placeholder="Search events…" />
        </div>
        <select><option>All Event Types</option><option>Authentication</option><option>Configuration</option><option>Data Access</option><option>Permission</option><option>Billing</option></select>
        <select><option>All Severities</option><option>Info</option><option>Warning</option><option>Critical</option><option>Success</option></select>
        <select><option>Last 24 Hours</option><option>Last 7 Days</option><option>Last 30 Days</option><option>Custom Range</option></select>
      </div>

      {/* Table */}
      <div className="sa-panel">
        <div className="sa-ph">
          <div>
            <div className="sa-ph-t">Event Log</div>
            <div className="sa-ph-s">Showing 892 events from today</div>
          </div>
          <SAButton variant="ghost" icon={<Download size={14} />}>Export</SAButton>
        </div>
        <table className="sa-dt">
          <thead>
            <tr>
              <th>Timestamp</th>
              <th>Event</th>
              <th>User</th>
              <th>IP Address</th>
              <th>Severity</th>
              <th>Details</th>
            </tr>
          </thead>
          <tbody>
            {events.map((e, i) => (
              <tr key={i}>
                <td><span className="sa-ts">{e.time}</span></td>
                <td>
                  <div className="sa-evt">
                    <div className="sa-evt-icon" style={{ background: e.evtBg, color: e.evtColor }}>
                      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d={e.evtIconPath} /></svg>
                    </div>
                    <div className="sa-td-name">{e.event}</div>
                  </div>
                </td>
                <td>{e.user}</td>
                <td><span className="sa-ip">{e.ip}</span></td>
                <td><span className={`sa-badge sa-${e.sevClass}`}>{e.severity}</span></td>
                <td>{e.details}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      </div>
    </>
  );
}
