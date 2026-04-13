'use client';

import { useState, useEffect, useCallback } from 'react';
import { useRouter } from 'next/navigation';
import { Save, Bell, Loader2, AlertCircle, ArrowLeft } from 'lucide-react';
import { SATopbar, SAButton, SAIconButton } from '../SATopbar';
import { getAdminBusiness, updateBusiness, listCurrencies, getBillingCatalog } from '@/lib/superadmin';
import { ApiError } from '@/lib/api';
import type { PlatformCurrency, BillingPlan, Tenant } from '@/lib/types';

// ─────────────────────────────────────────────────────────────────────────────
// Types
// ─────────────────────────────────────────────────────────────────────────────

interface EditTenantForm {
  // Business Information
  name: string;
  contactEmail: string;
  contactPhone: string;
  // Address
  addressLine1: string;
  addressLine2: string;
  addressCity: string;
  addressState: string;
  addressPostalCode: string;
  // Regional Settings
  country: string;
  currency: string;
  timezone: string;
  language: string;
  // Branding
  brandColor: string;
  // Tax & Billing
  billingVatNumber: string;
  // Audit
  reason: string;
}

type FieldErrors = Partial<Record<keyof EditTenantForm | '_global', string>>;

const FALLBACK_CURRENCIES: PlatformCurrency[] = [
  { id: 0, code: 'USD', symbol: '$', name: 'US Dollar', is_active: true, sort_order: 0 },
  { id: 0, code: 'GBP', symbol: '£', name: 'British Pound', is_active: true, sort_order: 1 },
  { id: 0, code: 'EUR', symbol: '€', name: 'Euro', is_active: true, sort_order: 2 },
];

const API_FIELD_MAP: Record<string, keyof EditTenantForm> = {
  name: 'name',
  contact_email: 'contactEmail',
  contact_phone: 'contactPhone',
  billing_country: 'country',
  currency: 'currency',
  timezone: 'timezone',
  language: 'language',
  brand_color: 'brandColor',
  billing_vat_number: 'billingVatNumber',
  'billing_address.line1': 'addressLine1',
  'billing_address.line2': 'addressLine2',
  'billing_address.city': 'addressCity',
  'billing_address.state': 'addressState',
  'billing_address.postal_code': 'addressPostalCode',
};

// ─────────────────────────────────────────────────────────────────────────────
// Component
// ─────────────────────────────────────────────────────────────────────────────

export default function SAEditBusinessContent({ businessId }: { businessId: number }) {
  const router = useRouter();

  // ── State ──
  const [form, setForm] = useState<EditTenantForm>({
    name: '',
    contactEmail: '',
    contactPhone: '',
    addressLine1: '',
    addressLine2: '',
    addressCity: '',
    addressState: '',
    addressPostalCode: '',
    country: '',
    currency: 'USD',
    timezone: 'UTC',
    language: 'en',
    brandColor: '#2563eb',
    billingVatNumber: '',
    reason: '',
  });

  const [currencies, setCurrencies] = useState<PlatformCurrency[]>([]);
  const [initialLoading, setInitialLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [fieldErrors, setFieldErrors] = useState<FieldErrors>({});
  const [tenant, setTenant] = useState<Tenant | null>(null);

  // ── Generic setter ──
  function set<K extends keyof EditTenantForm>(key: K, value: EditTenantForm[K]) {
    setForm(prev => ({ ...prev, [key]: value }));
    if (fieldErrors[key]) {
      setFieldErrors(prev => {
        const next = { ...prev };
        delete next[key];
        return next;
      });
    }
  }

  // ── Load data ──
  const loadData = useCallback(async () => {
    setInitialLoading(true);
    try {
      const [businessRes, currencyResult] = await Promise.allSettled([
        getAdminBusiness(businessId),
        listCurrencies(),
      ]);

      if (businessRes.status === 'fulfilled') {
        const t = businessRes.value.tenant;
        setTenant(t);
        const addr = t.billing_address_json as Record<string, string> | null;
        setForm({
          name: t.name,
          contactEmail: t.contact_email || '',
          contactPhone: t.contact_phone || '',
          addressLine1: addr?.line1 || '',
          addressLine2: addr?.line2 || '',
          addressCity: addr?.city || '',
          addressState: addr?.state || '',
          addressPostalCode: addr?.postal_code || '',
          country: t.billing_country || '',
          currency: t.currency || 'USD',
          timezone: t.timezone || 'UTC',
          language: t.language || 'en',
          brandColor: t.brand_color || '#2563eb',
          billingVatNumber: t.billing_vat_number || '',
          reason: '',
        });
      }

      if (currencyResult.status === 'fulfilled') {
        const activeCurrencies = currencyResult.value.currencies.filter(c => c.is_active);
        setCurrencies(activeCurrencies.length > 0 ? activeCurrencies : FALLBACK_CURRENCIES);
      } else {
        setCurrencies(FALLBACK_CURRENCIES);
      }
    } catch (err) {
      console.error('Failed to load edit data', err);
    } finally {
      setInitialLoading(false);
    }
  }, [businessId]);

  useEffect(() => {
    loadData();
  }, [loadData]);

  // ── Validation ──
  function validate(): FieldErrors {
    const errs: FieldErrors = {};
    if (!form.name.trim()) errs.name = 'Business name is required.';
    if (form.contactEmail && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.contactEmail)) {
      errs.contactEmail = 'Please enter a valid email address.';
    }
    if (form.brandColor && !/^#[0-9a-fA-F]{6}$/.test(form.brandColor)) {
      errs.brandColor = 'Brand color must be a valid 6-digit hex code (e.g., #2563eb).';
    }
    return errs;
  }

  // ── API Error Extraction ──
  function extractApiFieldErrors(data: unknown): FieldErrors {
    const errs: FieldErrors = {};
    if (data && typeof data === 'object' && 'errors' in (data as Record<string, unknown>)) {
      const apiErrors = (data as { errors: Record<string, string[]> }).errors;
      for (const [apiField, messages] of Object.entries(apiErrors)) {
        const formField = API_FIELD_MAP[apiField];
        if (formField) {
          if (!errs[formField]) errs[formField] = Array.isArray(messages) ? messages[0] : String(messages);
        } else {
          const msg = Array.isArray(messages) ? messages[0] : String(messages);
          errs._global = errs._global ? `${errs._global} ${msg}` : msg;
        }
      }
    }
    return errs;
  }

  // ── Handlers ──
  async function handleSave() {
    setFieldErrors({});
    const clientErrors = validate();
    if (Object.keys(clientErrors).length > 0) {
      setFieldErrors(clientErrors);
      return;
    }

    setSubmitting(true);
    try {
      await updateBusiness({
        tenantId: businessId,
        name: form.name.trim(),
        contactEmail: form.contactEmail.trim() || undefined,
        contactPhone: form.contactPhone.trim() || undefined,
        currency: form.currency,
        billingCountry: form.country || undefined,
        billingVatNumber: form.billingVatNumber.trim() || undefined,
        timezone: form.timezone || undefined,
        language: form.language || undefined,
        brandColor: form.brandColor || undefined,
        addressLine1: form.addressLine1.trim() || undefined,
        addressLine2: form.addressLine2.trim() || undefined,
        addressCity: form.addressCity.trim() || undefined,
        addressState: form.addressState.trim() || undefined,
        addressPostalCode: form.addressPostalCode.trim() || undefined,
        reason: form.reason.trim() || undefined,
      });
      router.push(`/superadmin/businesses/${businessId}?updated=1`);
    } catch (err) {
      if (err instanceof ApiError) {
        if (err.status === 422) {
          const apiFieldErrs = extractApiFieldErrors(err.data);
          setFieldErrors(Object.keys(apiFieldErrs).length > 0 ? apiFieldErrs : { _global: err.message });
        } else {
          setFieldErrors({ _global: err.message });
        }
      } else {
        setFieldErrors({ _global: 'An unexpected error occurred.' });
      }
    } finally {
      setSubmitting(false);
    }
  }

  // ── Render Helpers ──
  function fieldError(field: keyof EditTenantForm) {
    const msg = fieldErrors[field];
    if (!msg) return null;
    return (
      <div className="sa-form-hint" style={{ color: '#ef4444', display: 'flex', alignItems: 'center', gap: 4, marginTop: 4 }}>
        <AlertCircle size={13} style={{ flexShrink: 0 }} />
        {msg}
      </div>
    );
  }

  if (initialLoading) {
    return (
      <div className="sa-content" style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', minHeight: 400 }}>
        <Loader2 size={24} className="sa-spin" style={{ color: 'var(--sa-orange)' }} />
      </div>
    );
  }

  if (!tenant) {
    return (
      <div className="sa-content">
        <div className="sa-panel" style={{ textAlign: 'center', padding: 48 }}>
          <AlertCircle size={40} style={{ color: 'var(--sa-red)', marginBottom: 16 }} />
          <div style={{ fontWeight: 600 }}>Business not found</div>
          <SAButton variant="ghost" onClick={() => router.push('/superadmin/businesses')} style={{ marginTop: 16 }}>
             Back to List
          </SAButton>
        </div>
      </div>
    );
  }

  return (
    <>
      <SATopbar
        breadcrumb={
          <>
            Admin &rsaquo; Business Management &rsaquo;{' '}
            <a href="/superadmin/businesses" style={{ color: 'inherit', textDecoration: 'none' }}>All Businesses</a>{' '}
            &rsaquo; <a href={`/superadmin/businesses/${businessId}`} style={{ color: 'inherit', textDecoration: 'none' }}>{tenant.name}</a>{' '}
            &rsaquo; <b>Edit</b>
          </>
        }
        title="Edit Business"
        actions={<SAIconButton hasNotification><Bell size={18} /></SAIconButton>}
      />

      <div className="sa-content" style={{ maxWidth: 860 }}>
        <div className="sa-form-panel">
          <div className="sa-fp-body">
            {fieldErrors._global && (
              <div style={{ background: '#fef2f2', border: '1px solid #fecaca', borderRadius: 8, padding: '12px 16px', marginBottom: 24, display: 'flex', alignItems: 'flex-start', gap: 10, color: '#dc2626', fontSize: 14 }}>
                <AlertCircle size={18} style={{ flexShrink: 0, marginTop: 1 }} />
                <span>{fieldErrors._global}</span>
              </div>
            )}

            <div className="sa-form-section">
              <div className="sa-fs-title">Business Information</div>
              <div className="sa-form-group">
                <label className="sa-label">Business Name <span className="sa-req">*</span></label>
                <input
                  className="sa-input"
                  type="text"
                  value={form.name}
                  onChange={e => set('name', e.target.value)}
                  disabled={submitting}
                  style={fieldErrors.name ? { borderColor: '#ef4444' } : undefined}
                />
                {fieldError('name')}
              </div>

              <div className="sa-form-row">
                <div className="sa-form-group">
                  <label className="sa-label">Contact Email</label>
                  <input
                    className="sa-input"
                    type="email"
                    value={form.contactEmail}
                    onChange={e => set('contactEmail', e.target.value)}
                    disabled={submitting}
                  />
                  {fieldError('contactEmail')}
                </div>
                <div className="sa-form-group">
                  <label className="sa-label">Contact Phone</label>
                  <input
                    className="sa-input"
                    type="text"
                    value={form.contactPhone}
                    onChange={e => set('contactPhone', e.target.value)}
                    disabled={submitting}
                  />
                </div>
              </div>
            </div>

            <div className="sa-form-section">
              <div className="sa-fs-title">Billing Address</div>
              <div className="sa-form-group">
                <label className="sa-label">Address Line 1</label>
                <input
                  className="sa-input"
                  type="text"
                  placeholder="Street address"
                  value={form.addressLine1}
                  onChange={e => set('addressLine1', e.target.value)}
                  disabled={submitting}
                />
              </div>
              <div className="sa-form-group">
                <label className="sa-label">Address Line 2</label>
                <input
                  className="sa-input"
                  type="text"
                  placeholder="Apt, suite, building, etc."
                  value={form.addressLine2}
                  onChange={e => set('addressLine2', e.target.value)}
                  disabled={submitting}
                />
              </div>
              <div className="sa-form-row">
                <div className="sa-form-group">
                  <label className="sa-label">City</label>
                  <input
                    className="sa-input"
                    type="text"
                    value={form.addressCity}
                    onChange={e => set('addressCity', e.target.value)}
                    disabled={submitting}
                  />
                </div>
                <div className="sa-form-group">
                  <label className="sa-label">State / Province</label>
                  <input
                    className="sa-input"
                    type="text"
                    value={form.addressState}
                    onChange={e => set('addressState', e.target.value)}
                    disabled={submitting}
                  />
                </div>
              </div>
              <div className="sa-form-group" style={{ maxWidth: '50%' }}>
                <label className="sa-label">Postal Code</label>
                <input
                  className="sa-input"
                  type="text"
                  value={form.addressPostalCode}
                  onChange={e => set('addressPostalCode', e.target.value)}
                  disabled={submitting}
                />
              </div>
            </div>

            <div className="sa-form-section">
              <div className="sa-fs-title">Regional Settings</div>
              <div className="sa-form-row">
                <div className="sa-form-group">
                  <label className="sa-label">Country</label>
                  <select className="sa-select" value={form.country} onChange={e => set('country', e.target.value)} disabled={submitting}>
                    <option value="">Select country...</option>
                    <option value="US">United States</option>
                    <option value="GB">United Kingdom</option>
                    <option value="CA">Canada</option>
                    <option value="AU">Australia</option>
                    <option value="DE">Germany</option>
                    <option value="FR">France</option>
                    <option value="ES">Spain</option>
                    <option value="IT">Italy</option>
                    <option value="NL">Netherlands</option>
                    <option value="MW">Malawi</option>
                    <option value="ZA">South Africa</option>
                    <option value="NG">Nigeria</option>
                    <option value="KE">Kenya</option>
                    <option value="EG">Egypt</option>
                    <option value="AE">United Arab Emirates</option>
                    <option value="SA">Saudi Arabia</option>
                    <option value="IN">India</option>
                    <option value="SG">Singapore</option>
                    <option value="JP">Japan</option>
                  </select>
                </div>
                <div className="sa-form-group">
                  <label className="sa-label">Currency</label>
                  <select className="sa-select" value={form.currency} onChange={e => set('currency', e.target.value)} disabled={submitting}>
                    {currencies.map(c => (
                      <option key={c.code} value={c.code}>{c.code} — {c.name}</option>
                    ))}
                  </select>
                </div>
              </div>

              <div className="sa-form-row">
                <div className="sa-form-group">
                  <label className="sa-label">Timezone</label>
                  <select className="sa-select" value={form.timezone} onChange={e => set('timezone', e.target.value)} disabled={submitting}>
                    <option value="UTC">UTC</option>
                    <option value="Europe/London">Europe/London</option>
                    <option value="Europe/Berlin">Europe/Berlin</option>
                    <option value="Europe/Paris">Europe/Paris</option>
                    <option value="Africa/Cairo">Africa/Cairo</option>
                    <option value="Africa/Blantyre">Africa/Blantyre</option>
                    <option value="Africa/Johannesburg">Africa/Johannesburg</option>
                    <option value="Asia/Dubai">Asia/Dubai</option>
                    <option value="Asia/Riyadh">Asia/Riyadh</option>
                    <option value="Asia/Kolkata">Asia/Kolkata</option>
                    <option value="Asia/Singapore">Asia/Singapore</option>
                    <option value="Asia/Tokyo">Asia/Tokyo</option>
                    <option value="Australia/Sydney">Australia/Sydney</option>
                    <option value="America/New_York">America/New_York</option>
                    <option value="America/Chicago">America/Chicago</option>
                    <option value="America/Denver">America/Denver</option>
                    <option value="America/Los_Angeles">America/Los_Angeles</option>
                  </select>
                </div>
                <div className="sa-form-group">
                  <label className="sa-label">Language</label>
                  <select className="sa-select" value={form.language} onChange={e => set('language', e.target.value)} disabled={submitting}>
                    <option value="en">English</option>
                    <option value="fr">French</option>
                    <option value="es">Spanish</option>
                  </select>
                </div>
              </div>
            </div>

            <div className="sa-form-section">
              <div className="sa-fs-title">Branding</div>
              <div className="sa-form-group" style={{ maxWidth: '50%' }}>
                <label className="sa-label">Brand Color</label>
                <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                  <input
                    type="color"
                    value={form.brandColor}
                    onChange={e => set('brandColor', e.target.value)}
                    disabled={submitting}
                    style={{ width: 40, height: 32, border: '1px solid var(--sa-border)', borderRadius: 4, cursor: 'pointer' }}
                  />
                  <input
                    className="sa-input"
                    type="text"
                    value={form.brandColor}
                    onChange={e => set('brandColor', e.target.value)}
                    disabled={submitting}
                    style={{ fontFamily: 'monospace', flex: 1 }}
                  />
                </div>
                {fieldError('brandColor')}
              </div>
            </div>

            <div className="sa-form-section">
              <div className="sa-fs-title">Tax & Billing</div>
              <div className="sa-form-group" style={{ maxWidth: '50%' }}>
                <label className="sa-label">VAT / Tax ID Number</label>
                <input
                  className="sa-input"
                  type="text"
                  placeholder="e.g. GB123456789"
                  value={form.billingVatNumber}
                  onChange={e => set('billingVatNumber', e.target.value)}
                  disabled={submitting}
                />
              </div>
            </div>

            <div className="sa-form-section" style={{ borderBottom: 'none' }}>
              <div className="sa-fs-title">Audit Log</div>
              <div className="sa-form-group">
                <label className="sa-label">Update Reason</label>
                <textarea
                  className="sa-textarea"
                  placeholder="Why are you making this change?"
                  value={form.reason}
                  onChange={e => set('reason', e.target.value)}
                  disabled={submitting}
                  rows={2}
                />
              </div>
            </div>
          </div>

          <div className="sa-fp-footer">
            <SAButton variant="ghost" onClick={() => router.back()} disabled={submitting}>
              Cancel
            </SAButton>
            <div className="sa-fp-footer-end">
              <SAButton
                variant="primary"
                icon={submitting ? <Loader2 size={14} className="sa-spin" /> : <Save size={14} />}
                onClick={handleSave}
                disabled={submitting}
              >
                {submitting ? 'Saving...' : 'Save Changes'}
              </SAButton>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
