'use client';

import React, { useState, useEffect, useCallback } from 'react';
import { Check, Loader2, AlertCircle, RefreshCw } from 'lucide-react';
import { SATopbar, SAButton } from '../SATopbar';
import { getPlatformSettings, updatePlatformSettings } from '@/lib/superadmin';
import type { PlatformSettings, PlatformCurrency } from '@/lib/types';
import { ApiError } from '@/lib/api';

/* ── Component ── */

export default function SASettingsContent() {
  const [settings, setSettings] = useState<PlatformSettings | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);
  const [saveSuccess, setSaveSuccess] = useState(false);
  const [saveError, setSaveError] = useState<string | null>(null);

  // Editable currency state (derived from loaded settings)
  const [currencies, setCurrencies] = useState<PlatformCurrency[]>([]);
  const [newCurrencyCode, setNewCurrencyCode] = useState('');
  const [newCurrencyName, setNewCurrencyName] = useState('');
  const [newCurrencySymbol, setNewCurrencySymbol] = useState('');
  const [currencyError, setCurrencyError] = useState<string | null>(null);

  const fetchSettings = useCallback(async (signal: AbortSignal) => {
    setError(null);
    try {
      const result = await getPlatformSettings();
      if (!signal.aborted) {
        setSettings(result);
        setCurrencies(result.currencies ?? []);
      }
    } catch (err) {
      if (!signal.aborted) {
        setError(err instanceof Error ? err.message : 'Failed to load platform settings.');
      }
    }
  }, []);

  useEffect(() => {
    const controller = new AbortController();
    setLoading(true);
    fetchSettings(controller.signal).finally(() => {
      if (!controller.signal.aborted) setLoading(false);
    });
    return () => controller.abort();
  }, [fetchSettings]);

  /* ── Currency management ── */
  const handleToggleCurrency = (code: string) => {
    setCurrencies((prev) =>
      prev.map((c) =>
        c.code === code ? { ...c, is_active: !c.is_active } : c
      )
    );
  };

  const handleRemoveCurrency = (code: string) => {
    setCurrencies((prev) => prev.filter((c) => c.code !== code));
  };

  const handleAddCurrency = () => {
    setCurrencyError(null);

    const code = newCurrencyCode.trim().toUpperCase();
    const name = newCurrencyName.trim();
    const symbol = newCurrencySymbol.trim();

    if (!code || !/^[A-Z]{3}$/.test(code)) {
      setCurrencyError('Currency code must be exactly 3 uppercase letters (e.g., USD).');
      return;
    }
    if (!name) {
      setCurrencyError('Currency name is required.');
      return;
    }
    if (currencies.some((c) => c.code === code)) {
      setCurrencyError(`Currency ${code} already exists.`);
      return;
    }

    setCurrencies((prev) => [
      ...prev,
      {
        id: 0, // will be assigned by backend
        code,
        symbol: symbol || null,
        name,
        is_active: true,
        sort_order: prev.length,
      },
    ]);
    setNewCurrencyCode('');
    setNewCurrencyName('');
    setNewCurrencySymbol('');
  };

  /* ── Save handler ── */
  const handleSave = async () => {
    setSaving(true);
    setSaveError(null);
    setSaveSuccess(false);

    try {
      const payload = {
        currencies: currencies.map((c, i) => ({
          code: c.code,
          symbol: c.symbol ?? undefined,
          name: c.name,
          is_active: c.is_active,
        })),
      };

      const result = await updatePlatformSettings(payload as Parameters<typeof updatePlatformSettings>[0]);
      setSettings(result);
      setCurrencies(result.currencies ?? []);
      setSaveSuccess(true);
      setTimeout(() => setSaveSuccess(false), 3000);
    } catch (err) {
      if (err instanceof ApiError) {
        const body = err.data as Record<string, unknown> | null;
        const validationErrors = body?.errors;
        if (validationErrors && typeof validationErrors === 'object') {
          const messages = Object.values(validationErrors as Record<string, string[]>).flat().join(', ');
          setSaveError(messages);
        } else {
          const msg = typeof body?.message === 'string' ? body.message : err.message;
          setSaveError(msg);
        }
      } else {
        setSaveError(err instanceof Error ? err.message : 'Failed to save settings.');
      }
    } finally {
      setSaving(false);
    }
  };

  const handleRefresh = async () => {
    setLoading(true);
    const controller = new AbortController();
    await fetchSettings(controller.signal);
    setLoading(false);
  };

  /* ── Loading state ── */
  if (loading) {
    return (
      <>
        <SATopbar
          breadcrumb={<>Admin &rsaquo; Platform &rsaquo; <b>Settings</b></>}
          title="Platform Settings"
          actions={null}
        />
        <div className="sa-content" style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', minHeight: 400 }}>
          <div style={{ textAlign: 'center' }}>
            <Loader2 className="sa-spinner" style={{ width: 36, height: 36, animation: 'spin 1s linear infinite', color: 'var(--sa-orange)' }} />
            <div style={{ marginTop: 12, color: 'var(--sa-text-2)' }}>Loading settings...</div>
          </div>
        </div>
        <style>{`@keyframes spin { to { transform: rotate(360deg); } }`}</style>
      </>
    );
  }

  /* ── Error state ── */
  if (error && !settings) {
    return (
      <>
        <SATopbar
          breadcrumb={<>Admin &rsaquo; Platform &rsaquo; <b>Settings</b></>}
          title="Platform Settings"
          actions={null}
        />
        <div className="sa-content" style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', minHeight: 400 }}>
          <div style={{ textAlign: 'center', maxWidth: 420 }}>
            <AlertCircle style={{ width: 40, height: 40, color: 'var(--sa-red)', margin: '0 auto 12px' }} />
            <div style={{ fontSize: 18, fontWeight: 600, color: 'var(--sa-red)', marginBottom: 8 }}>
              Failed to load settings
            </div>
            <div style={{ color: 'var(--sa-text-2)', marginBottom: 16 }}>{error}</div>
            <SAButton variant="primary" onClick={handleRefresh}>Retry</SAButton>
          </div>
        </div>
      </>
    );
  }

  if (!settings) return null;

  const activeCurrencies = currencies.filter((c) => c.is_active);
  const isDirty =
    JSON.stringify(currencies.map((c) => ({ code: c.code, name: c.name, symbol: c.symbol, is_active: c.is_active }))) !==
    JSON.stringify((settings.currencies ?? []).map((c) => ({ code: c.code, name: c.name, symbol: c.symbol, is_active: c.is_active })));

  return (
    <>
      <SATopbar
        breadcrumb={<>Admin &rsaquo; Platform &rsaquo; <b>Settings</b></>}
        title="Platform Settings"
        actions={null}
      />
      <div className="sa-content">
        {/* Split Layout */}
        <div className="sa-split">
          {/* Main Form */}
          <div>
            {/* Platform Identity (read-only from server config) */}
            <div className="sa-panel">
              <div className="sa-ph">
                <div>
                  <div className="sa-ph-t">Platform Identity</div>
                  <div className="sa-ph-s">Core platform configuration (managed via server environment)</div>
                </div>
              </div>
              <div className="sa-pb">
                <div className="sa-form-section">
                  <div className="sa-form-group">
                    <label className="sa-label">Platform Name</label>
                    <input className="sa-input" type="text" value={settings.app.name} readOnly disabled />
                    <div className="sa-form-hint">Set via APP_NAME in .env</div>
                  </div>
                  <div className="sa-form-row">
                    <div className="sa-form-group">
                      <label className="sa-label">Platform URL</label>
                      <input className="sa-input" type="text" value={settings.app.url} readOnly disabled />
                      <div className="sa-form-hint">Set via APP_URL in .env</div>
                    </div>
                    <div className="sa-form-group">
                      <label className="sa-label">Environment</label>
                      <input className="sa-input" type="text" value={settings.app.env} readOnly disabled />
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {/* Mail Settings (read-only from server config) */}
            <div className="sa-panel" style={{ marginTop: 16 }}>
              <div className="sa-ph">
                <div>
                  <div className="sa-ph-t">Email Configuration</div>
                  <div className="sa-ph-s">Mail settings from server configuration</div>
                </div>
              </div>
              <div className="sa-pb">
                <div className="sa-form-section">
                  <div className="sa-form-row">
                    <div className="sa-form-group">
                      <label className="sa-label">Mail Driver</label>
                      <input className="sa-input" type="text" value={settings.mail.default || '--'} readOnly disabled />
                    </div>
                    <div className="sa-form-group">
                      <label className="sa-label">From Name</label>
                      <input className="sa-input" type="text" value={settings.mail.from_name || '--'} readOnly disabled />
                    </div>
                  </div>
                  <div className="sa-form-group">
                    <label className="sa-label">From Address</label>
                    <input className="sa-input" type="email" value={settings.mail.from_address || '--'} readOnly disabled />
                    <div className="sa-form-hint">Set via MAIL_FROM_ADDRESS in .env</div>
                  </div>
                </div>
              </div>
            </div>

            {/* Currencies (editable) */}
            <div className="sa-panel" style={{ marginTop: 16 }}>
              <div className="sa-ph">
                <div>
                  <div className="sa-ph-t">Platform Currencies</div>
                  <div className="sa-ph-s">
                    Manage which currencies are available for billing.
                    {activeCurrencies.length > 0 && (
                      <span style={{ marginLeft: 8 }}>
                        <b>{activeCurrencies.length}</b> active
                      </span>
                    )}
                  </div>
                </div>
              </div>
              <div className="sa-pb">
                {/* Existing currencies list */}
                {currencies.length > 0 ? (
                  <table className="sa-dt" style={{ marginBottom: 16 }}>
                    <thead>
                      <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Symbol</th>
                        <th>Active</th>
                        <th style={{ width: 60 }}></th>
                      </tr>
                    </thead>
                    <tbody>
                      {currencies.map((c) => (
                        <tr key={c.code}>
                          <td>
                            <span style={{ fontFamily: 'monospace', fontWeight: 600 }}>{c.code}</span>
                          </td>
                          <td>{c.name}</td>
                          <td>{c.symbol || '--'}</td>
                          <td>
                            <button
                              className={`sa-toggle${c.is_active ? ' on' : ''}`}
                              onClick={() => handleToggleCurrency(c.code)}
                              aria-pressed={c.is_active}
                              aria-label={`Toggle ${c.code}`}
                            />
                          </td>
                          <td>
                            <button
                              onClick={() => handleRemoveCurrency(c.code)}
                              style={{
                                background: 'none',
                                border: 'none',
                                color: 'var(--sa-red)',
                                cursor: 'pointer',
                                fontSize: 12,
                                padding: '4px 8px',
                              }}
                            >
                              Remove
                            </button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                ) : (
                  <div style={{ textAlign: 'center', padding: '20px 0', color: 'var(--sa-text-3)', marginBottom: 16 }}>
                    No currencies configured. Add one below.
                  </div>
                )}

                {/* Add currency form */}
                <div style={{ borderTop: '1px solid var(--sa-border)', paddingTop: 16 }}>
                  <div className="sa-fs-title" style={{ marginBottom: 8, fontSize: 13 }}>Add Currency</div>
                  {currencyError && (
                    <div style={{ padding: '8px 12px', background: 'var(--sa-red-bg)', color: 'var(--sa-red)', borderRadius: 'var(--sa-r)', marginBottom: 12, fontSize: 13 }}>
                      {currencyError}
                    </div>
                  )}
                  <div className="sa-form-row">
                    <div className="sa-form-group">
                      <label className="sa-label">Code <span className="sa-req">*</span></label>
                      <input
                        className="sa-input"
                        type="text"
                        placeholder="USD"
                        maxLength={3}
                        value={newCurrencyCode}
                        onChange={(e) => {
                          setNewCurrencyCode(e.target.value.toUpperCase());
                          setCurrencyError(null);
                        }}
                      />
                    </div>
                    <div className="sa-form-group">
                      <label className="sa-label">Name <span className="sa-req">*</span></label>
                      <input
                        className="sa-input"
                        type="text"
                        placeholder="US Dollar"
                        value={newCurrencyName}
                        onChange={(e) => {
                          setNewCurrencyName(e.target.value);
                          setCurrencyError(null);
                        }}
                      />
                    </div>
                    <div className="sa-form-group">
                      <label className="sa-label">Symbol</label>
                      <input
                        className="sa-input"
                        type="text"
                        placeholder="$"
                        maxLength={10}
                        value={newCurrencySymbol}
                        onChange={(e) => setNewCurrencySymbol(e.target.value)}
                      />
                    </div>
                  </div>
                  <SAButton
                    variant="ghost"
                    onClick={handleAddCurrency}
                    disabled={!newCurrencyCode || !newCurrencyName}
                  >
                    Add Currency
                  </SAButton>
                </div>
              </div>

              {/* Save footer */}
              <div className="sa-fp-footer">
                {saveError && (
                  <div style={{ flex: 1, color: 'var(--sa-red)', fontSize: 13, marginRight: 12 }}>
                    {saveError}
                  </div>
                )}
                {saveSuccess && (
                  <div style={{ flex: 1, color: 'var(--sa-green)', fontSize: 13, marginRight: 12, display: 'flex', alignItems: 'center', gap: 4 }}>
                    <Check size={14} /> Settings saved successfully
                  </div>
                )}
                <SAButton
                  variant="ghost"
                  onClick={() => {
                    setCurrencies(settings.currencies ?? []);
                    setSaveError(null);
                  }}
                  disabled={!isDirty || saving}
                >
                  Reset
                </SAButton>
                <SAButton
                  variant="primary"
                  icon={saving ? <Loader2 size={14} style={{ animation: 'spin 1s linear infinite' }} /> : <Check size={14} />}
                  onClick={handleSave}
                  disabled={!isDirty || saving}
                >
                  {saving ? 'Saving...' : 'Save Changes'}
                </SAButton>
              </div>
            </div>
          </div>

          {/* Platform Info Sidebar */}
          <div className="sa-info-panel">
            <div className="sa-ph">
              <div className="sa-ph-t">Platform Info</div>
            </div>
            <div className="sa-pb">
              <div className="sa-mrow">
                <div className="sa-ml">Application</div>
                <div className="sa-mv">{settings.app.name}</div>
              </div>
              <div className="sa-mrow">
                <div className="sa-ml">Environment</div>
                <div className={`sa-mv${settings.app.env === 'production' ? ' gr' : settings.app.env === 'local' ? ' am' : ''}`}>
                  {settings.app.env.charAt(0).toUpperCase() + settings.app.env.slice(1)}
                </div>
              </div>
              <div className="sa-mrow">
                <div className="sa-ml">Debug Mode</div>
                <div className={`sa-mv${settings.app.debug ? ' am' : ' gr'}`}>
                  {settings.app.debug ? 'ON' : 'OFF'}
                </div>
              </div>
              <div className="sa-mrow">
                <div className="sa-ml">Base URL</div>
                <div className="sa-mv" style={{ fontSize: 12 }}>{settings.app.url}</div>
              </div>
              <div className="sa-mrow">
                <div className="sa-ml">Tenant Resolution</div>
                <div className="sa-mv">{settings.tenancy.resolution}</div>
              </div>
              <div className="sa-mrow">
                <div className="sa-ml">Route Param</div>
                <div className="sa-mv" style={{ fontFamily: 'monospace' }}>{settings.tenancy.route_param}</div>
              </div>
              <div className="sa-mrow">
                <div className="sa-ml">Seller Country</div>
                <div className="sa-mv">{settings.billing.seller_country || '--'}</div>
              </div>
              <div className="sa-mrow">
                <div className="sa-ml">Mail Driver</div>
                <div className="sa-mv">{settings.mail.default}</div>
              </div>
              <div className="sa-mrow">
                <div className="sa-ml">Active Currencies</div>
                <div className="sa-mv gr">
                  {activeCurrencies.length > 0
                    ? activeCurrencies.map((c) => c.code).join(', ')
                    : 'None'
                  }
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <style>{`@keyframes spin { to { transform: rotate(360deg); } }`}</style>
    </>
  );
}
