'use client';

import { useState, useEffect, useCallback } from 'react';
import { useRouter } from 'next/navigation';
import { Plus, Bell, Loader2, AlertCircle } from 'lucide-react';
import { SATopbar, SAButton, SAIconButton } from '../SATopbar';
import { createBusiness, listCurrencies, getBillingCatalog } from '@/lib/superadmin';
import { ApiError } from '@/lib/api';
import type { PlatformCurrency, BillingPlan } from '@/lib/types';

// ─────────────────────────────────────────────────────────────────────────────
// Types
// ─────────────────────────────────────────────────────────────────────────────

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
  currency: string;
  trialDays: number;
  // Options
  startOnTrial: boolean;
  sendWelcomeEmail: boolean;
  skipEmailVerification: boolean;
}

type FieldErrors = Partial<Record<keyof TenantForm | '_global', string>>;

// Hardcoded fallback currencies when API fails
const FALLBACK_CURRENCIES: PlatformCurrency[] = [
  { id: 0, code: 'USD', symbol: '$', name: 'US Dollar', is_active: true, sort_order: 0 },
  { id: 0, code: 'GBP', symbol: '\u00a3', name: 'British Pound', is_active: true, sort_order: 1 },
  { id: 0, code: 'EUR', symbol: '\u20ac', name: 'Euro', is_active: true, sort_order: 2 },
];

// Map API validation field names (snake_case) to form field names
const API_FIELD_MAP: Record<string, keyof TenantForm> = {
  name: 'businessName',
  slug: 'subdomain',
  owner_name: 'firstName', // closest match — displayed on first name
  owner_email: 'email',
  owner_password: 'password',
  owner_phone: 'phone',
  contact_email: 'email',
  contact_phone: 'phone',
  billing_country: 'country',
  currency: 'currency',
  status: 'startOnTrial',
};

// ─────────────────────────────────────────────────────────────────────────────
// Component
// ─────────────────────────────────────────────────────────────────────────────

export default function SANewTenantContent() {
  const router = useRouter();

  // ── Form state ──
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
    billingPlan: '',
    currency: 'USD',
    trialDays: 14,
    startOnTrial: true,
    sendWelcomeEmail: true,
    skipEmailVerification: false,
  });

  // ── Reference data from API ──
  const [currencies, setCurrencies] = useState<PlatformCurrency[]>([]);
  const [billingPlans, setBillingPlans] = useState<BillingPlan[]>([]);
  const [initialLoading, setInitialLoading] = useState(true);

  // ── Submission state ──
  const [submitting, setSubmitting] = useState(false);
  const [fieldErrors, setFieldErrors] = useState<FieldErrors>({});

  // ── Generic setter ──
  function set<K extends keyof TenantForm>(key: K, value: TenantForm[K]) {
    setForm(prev => ({ ...prev, [key]: value }));
    // Clear field error when user edits the field
    if (fieldErrors[key]) {
      setFieldErrors(prev => {
        const next = { ...prev };
        delete next[key];
        return next;
      });
    }
  }

  // ── Auto-derive subdomain from business name ──
  function handleBusinessNameChange(value: string) {
    set('businessName', value);
    const slug = value
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-|-$/g, '');
    setForm(prev => ({ ...prev, businessName: value, subdomain: slug }));
    // Clear errors on both fields
    setFieldErrors(prev => {
      const next = { ...prev };
      delete next.businessName;
      delete next.subdomain;
      return next;
    });
  }

  // ── Load reference data on mount ──
  const loadReferenceData = useCallback(async () => {
    setInitialLoading(true);

    // Load currencies and billing catalog in parallel
    const [currencyResult, catalogResult] = await Promise.allSettled([
      listCurrencies(),
      getBillingCatalog(),
    ]);

    // Handle currencies
    if (currencyResult.status === 'fulfilled') {
      const activeCurrencies = currencyResult.value.currencies.filter(c => c.is_active);
      if (activeCurrencies.length > 0) {
        setCurrencies(activeCurrencies);
        // Default to the first active currency if current selection not in list
        const codes = activeCurrencies.map(c => c.code);
        setForm(prev => ({
          ...prev,
          currency: codes.includes(prev.currency) ? prev.currency : activeCurrencies[0].code,
        }));
      } else {
        setCurrencies(FALLBACK_CURRENCIES);
      }
    } else {
      // API failed — use fallback
      setCurrencies(FALLBACK_CURRENCIES);
    }

    // Handle billing plans
    if (catalogResult.status === 'fulfilled') {
      const activePlans = catalogResult.value.billing_plans.filter(p => p.is_active);
      setBillingPlans(activePlans);
      if (activePlans.length > 0) {
        setForm(prev => ({
          ...prev,
          billingPlan: prev.billingPlan || String(activePlans[0].id),
        }));
      }
    } else {
      setBillingPlans([]);
    }

    setInitialLoading(false);
  }, []);

  useEffect(() => {
    loadReferenceData();
  }, [loadReferenceData]);

  // ── Client-side validation ──
  function validate(): FieldErrors {
    const errs: FieldErrors = {};

    if (!form.businessName.trim()) {
      errs.businessName = 'Business name is required.';
    }
    if (!form.subdomain.trim()) {
      errs.subdomain = 'Subdomain is required.';
    } else if (!/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/.test(form.subdomain)) {
      errs.subdomain = 'Subdomain must start and end with a letter or number, and contain only lowercase letters, numbers, and hyphens.';
    }
    if (!form.firstName.trim()) {
      errs.firstName = 'First name is required.';
    }
    if (!form.lastName.trim()) {
      errs.lastName = 'Last name is required.';
    }
    if (!form.email.trim()) {
      errs.email = 'Email address is required.';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
      errs.email = 'Please enter a valid email address.';
    }
    if (!form.password.trim()) {
      errs.password = 'Password is required.';
    } else if (form.password.length < 8) {
      errs.password = 'Password must be at least 8 characters.';
    }

    return errs;
  }

  // ── Extract field errors from API 422 response ──
  function extractApiFieldErrors(data: unknown): FieldErrors {
    const errs: FieldErrors = {};
    if (data && typeof data === 'object' && 'errors' in (data as Record<string, unknown>)) {
      const apiErrors = (data as { errors: Record<string, string[]> }).errors;
      for (const [apiField, messages] of Object.entries(apiErrors)) {
        const formField = API_FIELD_MAP[apiField];
        if (formField) {
          // Only set if not already set (first error wins)
          if (!errs[formField]) {
            errs[formField] = Array.isArray(messages) ? messages[0] : String(messages);
          }
        } else {
          // Unknown field — append to global error
          const msg = Array.isArray(messages) ? messages[0] : String(messages);
          errs._global = errs._global ? `${errs._global} ${msg}` : msg;
        }
      }
    }
    return errs;
  }

  // ── Submit handler ──
  async function handleCreate() {
    // Clear previous errors
    setFieldErrors({});

    // Client-side validation
    const clientErrors = validate();
    if (Object.keys(clientErrors).length > 0) {
      setFieldErrors(clientErrors);
      return;
    }

    setSubmitting(true);

    try {
      await createBusiness({
        name: form.businessName.trim(),
        slug: form.subdomain.trim(),
        contactEmail: form.email.trim(),
        contactPhone: form.phone.trim() || undefined,
        currency: form.currency,
        billingCountry: form.country || undefined,
        status: form.startOnTrial ? 'trial' : 'active',
        ownerName: `${form.firstName.trim()} ${form.lastName.trim()}`,
        ownerEmail: form.email.trim(),
        ownerPassword: form.password,
        ownerPhone: form.phone.trim() || undefined,
        skipEmailVerification: form.skipEmailVerification || undefined,
        mustChangePassword: true,
      });

      // Navigate to businesses list with success indicator
      router.push('/superadmin/businesses?created=1');
    } catch (err) {
      if (err instanceof ApiError) {
        if (err.status === 422) {
          // Validation errors — map to form fields
          const apiFieldErrs = extractApiFieldErrors(err.data);
          if (Object.keys(apiFieldErrs).length > 0) {
            setFieldErrors(apiFieldErrs);
          } else {
            // 422 but no parsable field errors
            setFieldErrors({ _global: err.message || 'Validation failed. Please check your inputs.' });
          }
        } else {
          // Non-validation API error
          setFieldErrors({ _global: err.message || 'An unexpected error occurred. Please try again.' });
        }
      } else {
        setFieldErrors({ _global: 'A network error occurred. Please check your connection and try again.' });
      }
    } finally {
      setSubmitting(false);
    }
  }

  // ── Cancel handler ──
  function handleCancel() {
    router.push('/superadmin/businesses');
  }

  // ── Inline error helper ──
  function fieldError(field: keyof TenantForm) {
    const msg = fieldErrors[field];
    if (!msg) return null;
    return (
      <div className="sa-form-hint" style={{ color: '#ef4444', display: 'flex', alignItems: 'center', gap: 4, marginTop: 4 }}>
        <AlertCircle size={13} style={{ flexShrink: 0 }} />
        {msg}
      </div>
    );
  }

  // ── Loading state while fetching reference data ──
  if (initialLoading) {
    return (
      <>
        <SATopbar
          breadcrumb={
            <>
              Admin &rsaquo; Business Management &rsaquo;{' '}
              <a href="/superadmin/businesses" style={{ color: 'inherit', textDecoration: 'none' }}>
                All Businesses
              </a>{' '}
              &rsaquo; <b>New Tenant</b>
            </>
          }
          title="New Tenant"
          actions={
            <SAIconButton hasNotification>
              <Bell size={18} />
            </SAIconButton>
          }
        />
        <div className="sa-content" style={{ maxWidth: 860 }}>
          <div className="sa-form-panel" style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', padding: '80px 0' }}>
            <Loader2 size={28} style={{ animation: 'spin 1s linear infinite', color: '#6366f1' }} />
            <span style={{ marginLeft: 12, color: '#64748b', fontSize: 15 }}>Loading form data&hellip;</span>
          </div>
        </div>
      </>
    );
  }

  return (
    <>
      <SATopbar
        breadcrumb={
          <>
            Admin &rsaquo; Business Management &rsaquo;{' '}
            <a href="/superadmin/businesses" style={{ color: 'inherit', textDecoration: 'none' }}>
              All Businesses
            </a>{' '}
            &rsaquo; <b>New Tenant</b>
          </>
        }
        title="New Tenant"
        actions={
          <SAIconButton hasNotification>
            <Bell size={18} />
          </SAIconButton>
        }
      />

      <div className="sa-content" style={{ maxWidth: 860 }}>
        <div className="sa-form-panel">

        <div className="sa-fp-body">

          {/* ── Global error banner ── */}
          {fieldErrors._global && (
            <div
              style={{
                background: '#fef2f2',
                border: '1px solid #fecaca',
                borderRadius: 8,
                padding: '12px 16px',
                marginBottom: 24,
                display: 'flex',
                alignItems: 'flex-start',
                gap: 10,
                color: '#dc2626',
                fontSize: 14,
                lineHeight: 1.5,
              }}
            >
              <AlertCircle size={18} style={{ flexShrink: 0, marginTop: 1 }} />
              <span>{fieldErrors._global}</span>
            </div>
          )}

          {/* ── Section 1: Business Information ── */}
          <div className="sa-form-section">
            <div className="sa-fs-title">Business Information</div>
            <div className="sa-fs-desc">Enter the details for the new tenant business.</div>

            <div className="sa-form-group">
              <label className="sa-label">
                Business Name <span className="sa-req">*</span>
              </label>
              <input
                className="sa-input"
                type="text"
                placeholder="e.g. QuickFix Electronics"
                value={form.businessName}
                onChange={e => handleBusinessNameChange(e.target.value)}
                disabled={submitting}
                style={fieldErrors.businessName ? { borderColor: '#ef4444' } : undefined}
              />
              {fieldError('businessName')}
            </div>

            <div className="sa-form-group">
              <label className="sa-label">
                Subdomain <span className="sa-req">*</span>
              </label>
              <div className="sa-subdomain-preview">
                <input
                  type="text"
                  className="sa-sd-input"
                  placeholder="quickfix"
                  value={form.subdomain}
                  onChange={e =>
                    set(
                      'subdomain',
                      e.target.value
                        .toLowerCase()
                        .replace(/[^a-z0-9-]/g, '')
                    )
                  }
                  disabled={submitting}
                  style={fieldErrors.subdomain ? { borderColor: '#ef4444' } : undefined}
                />
                <span className="sa-sd-suffix">.99smartx.com</span>
              </div>
              {fieldErrors.subdomain ? (
                fieldError('subdomain')
              ) : (
                <div className="sa-form-hint">
                  This will be the tenant&apos;s unique URL on the platform.
                </div>
              )}
            </div>

            <div className="sa-form-row">
              <div className="sa-form-group">
                <label className="sa-label">Industry</label>
                <select
                  className="sa-select"
                  value={form.industry}
                  onChange={e => set('industry', e.target.value)}
                  disabled={submitting}
                >
                  <option value="">Select industry&hellip;</option>
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
                <label className="sa-label">Country</label>
                <select
                  className="sa-select"
                  value={form.country}
                  onChange={e => set('country', e.target.value)}
                  disabled={submitting}
                  style={fieldErrors.country ? { borderColor: '#ef4444' } : undefined}
                >
                  <option value="">Select country&hellip;</option>
                  <option value="US">United States</option>
                  <option value="GB">United Kingdom</option>
                  <option value="CA">Canada</option>
                  <option value="AU">Australia</option>
                  <option value="DE">Germany</option>
                </select>
                {fieldError('country')}
              </div>
            </div>
          </div>

          {/* ── Section 2: Owner Account ── */}
          <div className="sa-form-section">
            <div className="sa-fs-title">Owner Account</div>
            <div className="sa-fs-desc">
              This person will have full admin access to the tenant&apos;s workspace.
            </div>

            <div className="sa-form-row">
              <div className="sa-form-group">
                <label className="sa-label">
                  First Name <span className="sa-req">*</span>
                </label>
                <input
                  className="sa-input"
                  type="text"
                  placeholder="First name"
                  value={form.firstName}
                  onChange={e => set('firstName', e.target.value)}
                  disabled={submitting}
                  style={fieldErrors.firstName ? { borderColor: '#ef4444' } : undefined}
                />
                {fieldError('firstName')}
              </div>
              <div className="sa-form-group">
                <label className="sa-label">
                  Last Name <span className="sa-req">*</span>
                </label>
                <input
                  className="sa-input"
                  type="text"
                  placeholder="Last name"
                  value={form.lastName}
                  onChange={e => set('lastName', e.target.value)}
                  disabled={submitting}
                  style={fieldErrors.lastName ? { borderColor: '#ef4444' } : undefined}
                />
                {fieldError('lastName')}
              </div>
            </div>

            <div className="sa-form-group">
              <label className="sa-label">
                Email Address <span className="sa-req">*</span>
              </label>
              <input
                className="sa-input"
                type="email"
                placeholder="owner@example.com"
                value={form.email}
                onChange={e => set('email', e.target.value)}
                disabled={submitting}
                style={fieldErrors.email ? { borderColor: '#ef4444' } : undefined}
              />
              {fieldErrors.email ? (
                fieldError('email')
              ) : (
                <div className="sa-form-hint">
                  A welcome email with login credentials will be sent here.
                </div>
              )}
            </div>

            <div className="sa-form-row">
              <div className="sa-form-group">
                <label className="sa-label">
                  Temporary Password <span className="sa-req">*</span>
                </label>
                <input
                  className="sa-input"
                  type="text"
                  value={form.password}
                  onChange={e => set('password', e.target.value)}
                  disabled={submitting}
                  style={fieldErrors.password ? { borderColor: '#ef4444' } : undefined}
                />
                {fieldErrors.password ? (
                  fieldError('password')
                ) : (
                  <div className="sa-form-hint">
                    Owner will be required to change this on first login.
                  </div>
                )}
              </div>
              <div className="sa-form-group">
                <label className="sa-label">Phone</label>
                <input
                  className="sa-input"
                  type="text"
                  placeholder="+1 (555) 000-0000"
                  value={form.phone}
                  onChange={e => set('phone', e.target.value)}
                  disabled={submitting}
                  style={fieldErrors.phone ? { borderColor: '#ef4444' } : undefined}
                />
                {fieldError('phone')}
              </div>
            </div>
          </div>

          {/* ── Section 3: Plan & Billing ── */}
          <div className="sa-form-section">
            <div className="sa-fs-title">Plan &amp; Billing</div>
            <div className="sa-fs-desc">Configure the subscription plan and billing cycle.</div>

            <div className="sa-form-row">
              <div className="sa-form-group">
                <label className="sa-label">Billing Plan</label>
                {billingPlans.length > 0 ? (
                  <select
                    className="sa-select"
                    value={form.billingPlan}
                    onChange={e => set('billingPlan', e.target.value)}
                    disabled={submitting}
                  >
                    {billingPlans.map(plan => (
                      <option key={plan.id} value={String(plan.id)}>
                        {plan.name}
                      </option>
                    ))}
                  </select>
                ) : (
                  <div
                    style={{
                      padding: '10px 14px',
                      background: 'var(--sa-surface-2)',
                      border: '1px solid var(--sa-border)',
                      borderRadius: 'var(--sa-r-sm)',
                      color: 'var(--sa-text-3)',
                      fontSize: 13.5,
                    }}
                  >
                    No plans configured
                  </div>
                )}
              </div>
              <div className="sa-form-group">
                <label className="sa-label">Currency</label>
                <select
                  className="sa-select"
                  value={form.currency}
                  onChange={e => set('currency', e.target.value)}
                  disabled={submitting}
                  style={fieldErrors.currency ? { borderColor: '#ef4444' } : undefined}
                >
                  {currencies.map(c => (
                    <option key={c.code} value={c.code}>
                      {c.code} &mdash; {c.name}
                    </option>
                  ))}
                </select>
                {fieldError('currency')}
              </div>
            </div>

            <div className="sa-form-group" style={{ maxWidth: '50%' }}>
              <label className="sa-label">Trial Duration (days)</label>
              <input
                className="sa-input"
                type="number"
                min={0}
                max={365}
                value={form.trialDays}
                onChange={e => set('trialDays', Number(e.target.value))}
                disabled={submitting}
              />
              <div className="sa-form-hint">
                Number of free trial days before billing begins.
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
                disabled={submitting}
              />
              <div className="sa-toggle-info">
                <div className="sa-toggle-label">Start on Trial</div>
                <div className="sa-toggle-desc">
                  Begin with a free trial period. Billing starts after trial expires.
                </div>
              </div>
            </div>

            <div className="sa-toggle-wrap">
              <button
                className={`sa-toggle${form.sendWelcomeEmail ? ' on' : ''}`}
                onClick={() => set('sendWelcomeEmail', !form.sendWelcomeEmail)}
                aria-pressed={form.sendWelcomeEmail}
                type="button"
                disabled={submitting}
              />
              <div className="sa-toggle-info">
                <div className="sa-toggle-label">Send Welcome Email</div>
                <div className="sa-toggle-desc">
                  Send login credentials and onboarding info to the owner.
                </div>
              </div>
            </div>

            <div className="sa-toggle-wrap">
              <button
                className={`sa-toggle${form.skipEmailVerification ? ' on' : ''}`}
                onClick={() => set('skipEmailVerification', !form.skipEmailVerification)}
                aria-pressed={form.skipEmailVerification}
                type="button"
                disabled={submitting}
              />
              <div className="sa-toggle-info">
                <div className="sa-toggle-label">Skip Email Verification</div>
                <div className="sa-toggle-desc">
                  Mark the owner&apos;s email as verified immediately.
                </div>
              </div>
            </div>
          </div>

        </div>{/* /sa-fp-body */}

        {/* ── Footer ── */}
        <div className="sa-fp-footer">
            <SAButton variant="ghost" onClick={handleCancel} disabled={submitting}>
              Cancel
            </SAButton>
            <div className="sa-fp-footer-end">
              <SAButton
                variant="primary"
                icon={
                  submitting ? (
                    <Loader2 size={14} style={{ animation: 'spin 1s linear infinite' }} />
                  ) : (
                    <Plus size={14} />
                  )
                }
                onClick={handleCreate}
                disabled={submitting}
              >
                {submitting ? 'Creating\u2026' : 'Create Tenant'}
              </SAButton>
            </div>
          </div>

        </div>
      </div>
    </>
  );
}
