'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { Plus, Bell } from 'lucide-react';
import { SATopbar, SAButton, SAIconButton } from '../SATopbar';

interface TenantForm {
  // Business Information
  businessName: string;
  subdomain: string;
  industry: string;
  country: string;
  // Owner Account
  firstName: string;
  lastName: string;
  email: string;
  password: string;
  phone: string;
  // Plan & Billing
  billingPlan: string;
  billingInterval: string;
  currency: string;
  trialDays: number;
  // Options
  startOnTrial: boolean;
  sendWelcomeEmail: boolean;
  skipEmailVerification: boolean;
}

export default function SANewTenantContent() {
  const router = useRouter();

  const [form, setForm] = useState<TenantForm>({
    businessName: '',
    subdomain: '',
    industry: '',
    country: '',
    firstName: '',
    lastName: '',
    email: '',
    password: 'Temp@2026!',
    phone: '',
    billingPlan: 'professional',
    billingInterval: 'monthly',
    currency: 'usd',
    trialDays: 14,
    startOnTrial: true,
    sendWelcomeEmail: true,
    skipEmailVerification: false,
  });

  function set<K extends keyof TenantForm>(key: K, value: TenantForm[K]) {
    setForm(prev => ({ ...prev, [key]: value }));
  }

  function handleBusinessNameChange(value: string) {
    set('businessName', value);
    // Auto-derive subdomain from business name
    const slug = value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    set('subdomain', slug);
  }

  function handleCancel() {
    router.push('/superadmin/businesses');
  }

  function handleCreate() {
    // TODO: submit to API
    router.push('/superadmin/businesses');
  }

  return (
    <>
      <SATopbar
        breadcrumb={<>Admin › Business Management › <a href="/superadmin/businesses" style={{ color: 'inherit', textDecoration: 'none' }}>All Businesses</a> › <b>New Tenant</b></>}
        title="New Tenant"
        actions={
          <SAIconButton hasNotification><Bell size={18} /></SAIconButton>
        }
      />

      <div className="sa-content" style={{ maxWidth: 860 }}>
        <div className="sa-form-panel">

          {/* ── Section 1: Business Information ── */}
          <div className="sa-form-section">
            <div className="sa-fs-title">Business Information</div>
            <div className="sa-fs-desc">Enter the details for the new tenant business.</div>

            <div className="sa-form-group">
              <label>Business Name <span className="sa-req">*</span></label>
              <input
                type="text"
                placeholder="e.g. QuickFix Electronics"
                value={form.businessName}
                onChange={e => handleBusinessNameChange(e.target.value)}
              />
            </div>

            <div className="sa-form-group">
              <label>Subdomain <span className="sa-req">*</span></label>
              <div className="sa-subdomain-preview">
                <input
                  type="text"
                  className="sa-sd-input"
                  placeholder="quickfix"
                  value={form.subdomain}
                  onChange={e => set('subdomain', e.target.value.toLowerCase().replace(/[^a-z0-9-]/g, ''))}
                />
                <span className="sa-sd-suffix">.repairbuddy.com</span>
              </div>
              <div className="sa-form-hint">This will be the tenant's unique URL on the platform.</div>
            </div>

            <div className="sa-form-row">
              <div className="sa-form-group">
                <label>Industry</label>
                <select value={form.industry} onChange={e => set('industry', e.target.value)}>
                  <option value="">Select industry…</option>
                  <option value="electronics">Electronics Repair</option>
                  <option value="auto">Auto Repair</option>
                  <option value="appliance">Appliance Repair</option>
                  <option value="computer">Computer Repair</option>
                  <option value="phone">Phone Repair</option>
                  <option value="general">General Repair</option>
                  <option value="other">Other</option>
                </select>
              </div>
              <div className="sa-form-group">
                <label>Country</label>
                <select value={form.country} onChange={e => set('country', e.target.value)}>
                  <option value="">Select country…</option>
                  <option value="us">United States</option>
                  <option value="gb">United Kingdom</option>
                  <option value="ca">Canada</option>
                  <option value="au">Australia</option>
                  <option value="de">Germany</option>
                </select>
              </div>
            </div>
          </div>

          {/* ── Section 2: Owner Account ── */}
          <div className="sa-form-section">
            <div className="sa-fs-title">Owner Account</div>
            <div className="sa-fs-desc">This person will have full admin access to the tenant's workspace.</div>

            <div className="sa-form-row">
              <div className="sa-form-group">
                <label>First Name <span className="sa-req">*</span></label>
                <input
                  type="text"
                  placeholder="First name"
                  value={form.firstName}
                  onChange={e => set('firstName', e.target.value)}
                />
              </div>
              <div className="sa-form-group">
                <label>Last Name <span className="sa-req">*</span></label>
                <input
                  type="text"
                  placeholder="Last name"
                  value={form.lastName}
                  onChange={e => set('lastName', e.target.value)}
                />
              </div>
            </div>

            <div className="sa-form-group">
              <label>Email Address <span className="sa-req">*</span></label>
              <input
                type="email"
                placeholder="owner@example.com"
                value={form.email}
                onChange={e => set('email', e.target.value)}
              />
              <div className="sa-form-hint">A welcome email with login credentials will be sent here.</div>
            </div>

            <div className="sa-form-row">
              <div className="sa-form-group">
                <label>Temporary Password <span className="sa-req">*</span></label>
                <input
                  type="text"
                  value={form.password}
                  onChange={e => set('password', e.target.value)}
                />
                <div className="sa-form-hint">Owner will be required to change this on first login.</div>
              </div>
              <div className="sa-form-group">
                <label>Phone</label>
                <input
                  type="text"
                  placeholder="+1 (555) 000-0000"
                  value={form.phone}
                  onChange={e => set('phone', e.target.value)}
                />
              </div>
            </div>
          </div>

          {/* ── Section 3: Plan & Billing ── */}
          <div className="sa-form-section">
            <div className="sa-fs-title">Plan &amp; Billing</div>
            <div className="sa-fs-desc">Configure the subscription plan and billing cycle.</div>

            <div className="sa-form-row">
              <div className="sa-form-group">
                <label>Billing Plan <span className="sa-req">*</span></label>
                <select value={form.billingPlan} onChange={e => set('billingPlan', e.target.value)}>
                  <option value="professional">Professional — $79/mo</option>
                  <option value="basic">Basic — $29/mo</option>
                  <option value="enterprise">Enterprise — $249/mo</option>
                </select>
              </div>
              <div className="sa-form-group">
                <label>Billing Interval</label>
                <select value={form.billingInterval} onChange={e => set('billingInterval', e.target.value)}>
                  <option value="monthly">Monthly</option>
                  <option value="yearly">Yearly</option>
                  <option value="quarterly">Quarterly</option>
                </select>
              </div>
            </div>

            <div className="sa-form-row">
              <div className="sa-form-group">
                <label>Currency</label>
                <select value={form.currency} onChange={e => set('currency', e.target.value)}>
                  <option value="usd">USD — US Dollar</option>
                  <option value="gbp">GBP — British Pound</option>
                  <option value="eur">EUR — Euro</option>
                </select>
              </div>
              <div className="sa-form-group">
                <label>Trial Duration (days)</label>
                <input
                  type="number"
                  min={0}
                  max={365}
                  value={form.trialDays}
                  onChange={e => set('trialDays', Number(e.target.value))}
                />
              </div>
            </div>
          </div>

          {/* ── Section 4: Options ── */}
          <div className="sa-form-section">
            <div className="sa-fs-title">Options</div>
            <div className="sa-fs-desc">Additional settings for the new tenant account.</div>

            <div className="sa-toggle-wrap">
              <button
                className={`sa-toggle${form.startOnTrial ? ' on' : ''}`}
                onClick={() => set('startOnTrial', !form.startOnTrial)}
                aria-pressed={form.startOnTrial}
                type="button"
              />
              <div className="sa-toggle-info">
                <div className="sa-toggle-label">Start on Trial</div>
                <div className="sa-toggle-desc">Begin with a free trial period. Billing starts after trial expires.</div>
              </div>
            </div>

            <div className="sa-toggle-wrap">
              <button
                className={`sa-toggle${form.sendWelcomeEmail ? ' on' : ''}`}
                onClick={() => set('sendWelcomeEmail', !form.sendWelcomeEmail)}
                aria-pressed={form.sendWelcomeEmail}
                type="button"
              />
              <div className="sa-toggle-info">
                <div className="sa-toggle-label">Send Welcome Email</div>
                <div className="sa-toggle-desc">Send login credentials and onboarding info to the owner.</div>
              </div>
            </div>

            <div className="sa-toggle-wrap">
              <button
                className={`sa-toggle${form.skipEmailVerification ? ' on' : ''}`}
                onClick={() => set('skipEmailVerification', !form.skipEmailVerification)}
                aria-pressed={form.skipEmailVerification}
                type="button"
              />
              <div className="sa-toggle-info">
                <div className="sa-toggle-label">Skip Email Verification</div>
                <div className="sa-toggle-desc">Mark the owner's email as verified immediately.</div>
              </div>
            </div>
          </div>

          {/* ── Footer ── */}
          <div className="sa-fp-footer">
            <SAButton variant="ghost" onClick={handleCancel}>Cancel</SAButton>
            <div className="sa-fp-footer-end">
              <SAButton variant="ghost">Save as Draft</SAButton>
              <SAButton variant="primary" icon={<Plus size={14} />} onClick={handleCreate}>Create Tenant</SAButton>
            </div>
          </div>

        </div>
      </div>
    </>
  );
}
