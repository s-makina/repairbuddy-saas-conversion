'use client';

import { Download, Bell } from 'lucide-react';
import { SATopbar, SAButton, SAIconButton } from '../SATopbar';

export default function SAAnalyticsContent() {
  return (
    <>
      <SATopbar
        breadcrumb={<>Admin › Platform › <b>Analytics</b></>}
        title="Platform Analytics"
        actions={
          <>
            <SAIconButton hasNotification><Bell size={18} /></SAIconButton>
            <SAButton variant="ghost" icon={<Download size={14} />}>Export Report</SAButton>
          </>
        }
      />
      <div className="sa-content">
      {/* KPI Row */}
      <div className="sa-kpi-row">
        <div className="sa-kc">
          <div className="sa-kc-stripe" style={{ background: 'var(--sa-orange)' }} />
          <div className="sa-kc-icon" style={{ background: 'var(--sa-orange-bg)', color: 'var(--sa-orange)' }}>
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
          </div>
          <div>
            <div className="sa-kc-val">248K</div>
            <div className="sa-kc-lbl">Page Views</div>
            <div className="sa-kc-tag tag-up">▲ +18% this month</div>
          </div>
        </div>
        <div className="sa-kc">
          <div className="sa-kc-stripe" style={{ background: 'var(--sa-blue)' }} />
          <div className="sa-kc-icon" style={{ background: 'var(--sa-blue-bg)', color: 'var(--sa-blue)' }}>
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          </div>
          <div>
            <div className="sa-kc-val">4m 32s</div>
            <div className="sa-kc-lbl">Avg. Session</div>
            <div className="sa-kc-tag tag-up">▲ +8% vs last month</div>
          </div>
        </div>
        <div className="sa-kc">
          <div className="sa-kc-stripe" style={{ background: 'var(--sa-green)' }} />
          <div className="sa-kc-icon" style={{ background: 'var(--sa-green-bg)', color: 'var(--sa-green)' }}>
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
          </div>
          <div>
            <div className="sa-kc-val">32%</div>
            <div className="sa-kc-lbl">Bounce Rate</div>
            <div className="sa-kc-tag tag-info">-3% improvement</div>
          </div>
        </div>
        <div className="sa-kc">
          <div className="sa-kc-stripe" style={{ background: 'var(--sa-purple)' }} />
          <div className="sa-kc-icon" style={{ background: 'var(--sa-purple-bg)', color: 'var(--sa-purple)' }}>
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
          </div>
          <div>
            <div className="sa-kc-val">1.2M</div>
            <div className="sa-kc-lbl">API Calls</div>
            <div className="sa-kc-tag tag-up">▲ +24% this month</div>
          </div>
        </div>
      </div>

      {/* Chart + Top Plans — 2-col grid */}
      <div className="sa-g2">
        <div className="sa-panel">
          <div className="sa-ph">
            <div>
              <div className="sa-ph-t">Signups Trend</div>
              <div className="sa-ph-s">New tenant registrations — last 12 months</div>
            </div>
            <div className="sa-tab-row">
              <button className="sa-tab-btn">Quarterly</button>
              <button className="sa-tab-btn active">Monthly</button>
            </div>
          </div>
          <div className="sa-pb">
            <svg className="sa-chart-svg" viewBox="0 0 700 210" preserveAspectRatio="none">
              <line x1="0" y1="52" x2="700" y2="52" stroke="var(--sa-border)" strokeWidth="1" />
              <line x1="0" y1="105" x2="700" y2="105" stroke="var(--sa-border)" strokeWidth="1" />
              <line x1="0" y1="158" x2="700" y2="158" stroke="var(--sa-border)" strokeWidth="1" />
              <path d="M0,180 L58,165 L116,170 L174,155 L232,140 L290,130 L348,110 L406,120 L464,90 L522,95 L580,65 L638,45 L696,30 L696,210 L0,210Z" fill="url(#aFill)" opacity=".6" />
              <path d="M0,180 L58,165 L116,170 L174,155 L232,140 L290,130 L348,110 L406,120 L464,90 L522,95 L580,65 L638,45 L696,30" fill="none" stroke="var(--sa-orange)" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" />
              <circle cx="696" cy="30" r="5" fill="var(--sa-orange)" />
              <circle cx="638" cy="45" r="3.5" fill="var(--sa-orange)" />
              <defs>
                <linearGradient id="aFill" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stopColor="var(--sa-orange)" stopOpacity=".3" />
                  <stop offset="100%" stopColor="var(--sa-orange)" stopOpacity="0" />
                </linearGradient>
              </defs>
            </svg>
            <div className="sa-chart-months">
              {["Mar '25","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec","Jan","Feb '26"].map(m => <span key={m}>{m}</span>)}
            </div>
          </div>
        </div>

        <div className="sa-panel">
          <div className="sa-ph">
            <div>
              <div className="sa-ph-t">Top Plans by Revenue</div>
              <div className="sa-ph-s">Monthly recurring revenue</div>
            </div>
          </div>
          <div className="sa-pb">
            {[
              { lbl: 'Enterprise', val: '$22,410', green: true },
              { lbl: 'Professional', val: '$15,820', green: true },
              { lbl: 'Basic', val: '$4,620', green: false },
              { lbl: 'Trial → Paid Conv.', val: '73%', green: true },
              { lbl: 'ARPU', val: '$46.95', green: false },
              { lbl: 'LTV (est.)', val: '$657', green: true },
            ].map(r => (
              <div className="sa-mrow" key={r.lbl}>
                <div className="sa-ml">{r.lbl}</div>
                <div className={`sa-mv${r.green ? ' gr' : ''}`}>{r.val}</div>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Bottom 3-col grid */}
      <div className="sa-g3">
        {/* Signups by Source */}
        <div className="sa-panel">
          <div className="sa-ph"><div className="sa-ph-t">Signups by Source</div></div>
          <div className="sa-pb">
            <div className="sa-stacked-wrap">
              <div className="sa-stacked-bar">
                <div className="sa-stacked-seg" style={{ flex: 42, background: 'var(--sa-orange)' }} />
                <div className="sa-stacked-seg" style={{ flex: 28, background: 'var(--sa-blue)' }} />
                <div className="sa-stacked-seg" style={{ flex: 18, background: 'var(--sa-green)' }} />
                <div className="sa-stacked-seg" style={{ flex: 12, background: 'var(--sa-border-2)' }} />
              </div>
              <div className="sa-stacked-legend">
                {[
                  { color: 'var(--sa-orange)', label: 'Organic', pct: '42%' },
                  { color: 'var(--sa-blue)', label: 'Referral', pct: '28%' },
                  { color: 'var(--sa-green)', label: 'Paid Ads', pct: '18%' },
                  { color: 'var(--sa-border-2)', label: 'Other', pct: '12%' },
                ].map(l => (
                  <div className="sa-leg" key={l.label}>
                    <div className="sa-leg-dot" style={{ background: l.color }} />
                    {l.label} <b>{l.pct}</b>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>

        {/* Geographic Distribution */}
        <div className="sa-panel">
          <div className="sa-ph"><div className="sa-ph-t">Geographic Distribution</div></div>
          <div className="sa-pb">
            {[
              { flag: '🇺🇸', country: 'United States', pct: '42%' },
              { flag: '🇬🇧', country: 'United Kingdom', pct: '18%' },
              { flag: '🇨🇦', country: 'Canada', pct: '12%' },
              { flag: '🇦🇺', country: 'Australia', pct: '9%' },
              { flag: '🌍', country: 'Other', pct: '19%' },
            ].map(g => (
              <div className="sa-mrow" key={g.country}>
                <div className="sa-ml">{g.flag} {g.country}</div>
                <div className="sa-mv">{g.pct}</div>
              </div>
            ))}
          </div>
        </div>

        {/* Feature Adoption */}
        <div className="sa-panel">
          <div className="sa-ph"><div className="sa-ph-t">Feature Adoption</div></div>
          <div className="sa-pb">
            {[
              { lbl: 'Jobs', pct: 92, color: 'var(--sa-green)' },
              { lbl: 'Invoicing', pct: 78, color: 'var(--sa-blue)' },
              { lbl: 'Calendar', pct: 65, color: 'var(--sa-orange)' },
              { lbl: 'Reports', pct: 41, color: 'var(--sa-purple)' },
              { lbl: 'Booking', pct: 33, color: 'var(--sa-amber)' },
            ].map(f => (
              <div className="sa-prog-row" key={f.lbl}>
                <div className="sa-prog-lbl">{f.lbl}</div>
                <div className="sa-prog-bar"><div className="sa-prog-fill" style={{ width: `${f.pct}%`, background: f.color }} /></div>
                <div className="sa-prog-val">{f.pct}%</div>
              </div>
            ))}
          </div>
        </div>
      </div>
      </div>
    </>
  );
}
