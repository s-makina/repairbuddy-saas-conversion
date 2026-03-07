'use client';

import { useState } from 'react';
import { Check } from 'lucide-react';
import { SATopbar, SAButton, SAIconButton } from '../SATopbar';
import { Bell } from 'lucide-react';

const tabs = ['General', 'Email', 'Security', 'Maintenance'];

export default function SASettingsContent() {
  const [activeTab, setActiveTab] = useState('General');
  const [toggles, setToggles] = useState({
    maintenance: false,
    registrations: true,
    emailNotifications: true,
    twoFactor: true,
    betaFeatures: false,
  });

  type ToggleKey = keyof typeof toggles;
  const toggle = (key: ToggleKey) => setToggles(prev => ({ ...prev, [key]: !prev[key] }));

  return (
    <>
      <SATopbar
        breadcrumb={<>Admin › Platform › <b>Settings</b></>}
        title="Platform Settings"
        actions={<SAIconButton hasNotification><Bell size={18} /></SAIconButton>}
      />
      <div className="sa-content">
      {/* Tab Row */}
      <div className="sa-tab-row">
        {tabs.map(t => (
          <button
            key={t}
            className={`sa-tab-btn${activeTab === t ? ' active' : ''}`}
            onClick={() => setActiveTab(t)}
          >{t}</button>
        ))}
      </div>

      {/* Split Layout */}
      <div className="sa-split">
        {/* Main Form */}
        <div>
          <div className="sa-panel">
            <div className="sa-ph">
              <div>
                <div className="sa-ph-t">General Settings</div>
                <div className="sa-ph-s">Configure core platform settings</div>
              </div>
            </div>
            <div className="sa-pb">
              {/* Platform Identity */}
              <div className="sa-form-section">
                <div className="sa-fs-title">Platform Identity</div>
                <div className="sa-fs-desc">Configure how your platform appears to tenants and their customers.</div>
                <div className="sa-form-group">
                  <label className="sa-label">Platform Name <span className="sa-req">*</span></label>
                  <input className="sa-input" type="text" defaultValue="99SmartX" />
                </div>
                <div className="sa-form-row">
                  <div className="sa-form-group">
                    <label className="sa-label">Support Email <span className="sa-req">*</span></label>
                    <input className="sa-input" type="email" defaultValue="support@99smartx.com" />
                  </div>
                  <div className="sa-form-group">
                    <label className="sa-label">Admin Email</label>
                    <input className="sa-input" type="email" defaultValue="admin@99smartx.com" />
                  </div>
                </div>
                <div className="sa-form-group">
                  <label className="sa-label">Platform URL</label>
                  <input className="sa-input" type="text" defaultValue="https://app.99smartx.com" />
                  <div className="sa-form-hint">Base URL for all tenant sub-domains.</div>
                </div>
              </div>

              {/* Regional Defaults */}
              <div className="sa-form-section">
                <div className="sa-fs-title">Regional Defaults</div>
                <div className="sa-fs-desc">These defaults apply to new tenants upon registration.</div>
                <div className="sa-form-row">
                  <div className="sa-form-group">
                    <label className="sa-label">Default Timezone</label>
                    <select className="sa-select">
                      <option>UTC</option>
                      <option>America/New_York (EST)</option>
                      <option>Europe/London (GMT)</option>
                      <option>Asia/Tokyo (JST)</option>
                    </select>
                  </div>
                  <div className="sa-form-group">
                    <label className="sa-label">Default Currency</label>
                    <select className="sa-select">
                      <option>USD — US Dollar</option>
                      <option>GBP — British Pound</option>
                      <option>EUR — Euro</option>
                    </select>
                  </div>
                </div>
                <div className="sa-form-row">
                  <div className="sa-form-group">
                    <label className="sa-label">Date Format</label>
                    <select className="sa-select">
                      <option>MM/DD/YYYY</option>
                      <option>DD/MM/YYYY</option>
                      <option>YYYY-MM-DD</option>
                    </select>
                  </div>
                  <div className="sa-form-group">
                    <label className="sa-label">Time Format</label>
                    <select className="sa-select">
                      <option>12-hour (AM/PM)</option>
                      <option>24-hour</option>
                    </select>
                  </div>
                </div>
              </div>

              {/* Tenant Limits */}
              <div className="sa-form-section">
                <div className="sa-fs-title">Tenant Limits</div>
                <div className="sa-fs-desc">Configure default limits for new tenant accounts.</div>
                <div className="sa-form-row">
                  <div className="sa-form-group">
                    <label className="sa-label">Trial Duration (days)</label>
                    <input className="sa-input" type="number" defaultValue={14} />
                    <div className="sa-form-hint">Number of days before trial expires.</div>
                  </div>
                  <div className="sa-form-group">
                    <label className="sa-label">Max Users per Tenant</label>
                    <input className="sa-input" type="number" defaultValue={50} />
                    <div className="sa-form-hint">Override per-plan limits if needed.</div>
                  </div>
                </div>
                <div className="sa-form-row">
                  <div className="sa-form-group">
                    <label className="sa-label">Max Storage (GB)</label>
                    <input className="sa-input" type="number" defaultValue={10} />
                  </div>
                  <div className="sa-form-group">
                    <label className="sa-label">Max Branches</label>
                    <input className="sa-input" type="number" defaultValue={5} />
                  </div>
                </div>
              </div>

              {/* Feature Toggles */}
              <div className="sa-form-section">
                <div className="sa-fs-title">Feature Toggles</div>
                <div className="sa-fs-desc">Enable or disable platform-wide features.</div>
                {([
                  { key: 'maintenance' as ToggleKey, label: 'Maintenance Mode', desc: 'Show a maintenance page to all users. Admins can still access the platform.' },
                  { key: 'registrations' as ToggleKey, label: 'New Registrations', desc: 'Allow new businesses to sign up on the platform.' },
                  { key: 'emailNotifications' as ToggleKey, label: 'Email Notifications', desc: 'Send transactional emails (welcome, billing, alerts) to tenants.' },
                  { key: 'twoFactor' as ToggleKey, label: 'Two-Factor Authentication', desc: 'Require 2FA for all admin and owner accounts.' },
                  { key: 'betaFeatures' as ToggleKey, label: 'Beta Features', desc: 'Show experimental features flagged as beta to all tenants.' },
                ] as { key: ToggleKey; label: string; desc: string }[]).map(t => (
                  <div className="sa-toggle-wrap" key={t.key}>
                    <button
                      className={`sa-toggle${toggles[t.key] ? ' on' : ''}`}
                      onClick={() => toggle(t.key)}
                      aria-pressed={toggles[t.key]}
                      aria-label={t.label}
                    />
                    <div className="sa-toggle-info">
                      <div className="sa-toggle-label">{t.label}</div>
                      <div className="sa-toggle-desc">{t.desc}</div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
            <div className="sa-fp-footer">
              <SAButton variant="ghost">Reset to Defaults</SAButton>
              <SAButton variant="primary" icon={<Check size={14} />}>Save Changes</SAButton>
            </div>
          </div>
        </div>

        {/* Platform Info Sidebar */}
        <div className="sa-info-panel">
          <div className="sa-ph">
            <div className="sa-ph-t">Platform Info</div>
          </div>
          <div className="sa-pb">
            {[
              { lbl: 'Version', val: '2.4.1', cls: '' },
              { lbl: 'Last Deploy', val: 'Mar 6, 2026', cls: '' },
              { lbl: 'Environment', val: 'Production', cls: ' gr' },
              { lbl: 'PHP Version', val: '8.2.14', cls: '' },
              { lbl: 'Laravel', val: '11.x', cls: '' },
              { lbl: 'Database', val: 'MySQL 8.0', cls: '' },
              { lbl: 'Cache Driver', val: 'Redis', cls: '' },
              { lbl: 'Queue Driver', val: 'Redis', cls: '' },
              { lbl: 'Storage Used', val: '68%', cls: ' am' },
              { lbl: 'Uptime', val: '99.97%', cls: ' gr' },
            ].map(r => (
              <div className="sa-mrow" key={r.lbl}>
                <div className="sa-ml">{r.lbl}</div>
                <div className={`sa-mv${r.cls}`}>{r.val}</div>
              </div>
            ))}
          </div>
        </div>
      </div>
      </div>
    </>
  );
}
