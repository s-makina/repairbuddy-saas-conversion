"use client";

import { SATopbar, SAButton } from "../SATopbar";
import { Plus, Search, Pencil, Trash2, Loader2, AlertCircle, X } from "lucide-react";
import { useState, useEffect, useCallback } from "react";
import {
  listCurrencies,
  createCurrency,
  updateCurrency,
  deleteCurrency,
  setCurrencyActive,
} from "@/lib/superadmin";
import type { PlatformCurrency } from "@/lib/types";
import { notify } from "@/lib/notify";

export function SACurrenciesContent() {
  const [currencies, setCurrencies] = useState<PlatformCurrency[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [searchQuery, setSearchQuery] = useState("");
  const [saving, setSaving] = useState(false);
  const [deleting, setDeleting] = useState<number | null>(null);

  // Form state
  const [editingId, setEditingId] = useState<number | null>(null);
  const [form, setForm] = useState({
    name: "",
    code: "",
    symbol: "",
    isActive: true,
    sortOrder: "0",
  });
  const [formError, setFormError] = useState<string | null>(null);

  const fetchCurrencies = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await listCurrencies();
      setCurrencies(data.currencies);
    } catch (e) {
      setError(e instanceof Error ? e.message : "Failed to load currencies");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { fetchCurrencies(); }, [fetchCurrencies]);

  function resetForm() {
    setEditingId(null);
    setForm({ name: "", code: "", symbol: "", isActive: true, sortOrder: "0" });
    setFormError(null);
  }

  function startEdit(cur: PlatformCurrency) {
    setEditingId(cur.id);
    setForm({
      name: cur.name,
      code: cur.code,
      symbol: cur.symbol ?? "",
      isActive: cur.is_active,
      sortOrder: String(cur.sort_order ?? 0),
    });
    setFormError(null);
  }

  async function handleSubmit() {
    if (saving) return;
    const name = form.name.trim();
    const code = form.code.trim().toUpperCase();
    const symbol = form.symbol.trim();
    if (!name) { setFormError("Name is required"); return; }
    if (!code) { setFormError("Code is required"); return; }
    if (!symbol) { setFormError("Symbol is required"); return; }

    setSaving(true);
    setFormError(null);
    try {
      if (editingId) {
        await updateCurrency({
          id: editingId,
          name,
          symbol,
          isActive: form.isActive,
          sortOrder: parseInt(form.sortOrder, 10) || 0,
        });
        notify.success(`Currency "${name}" updated`);
      } else {
        await createCurrency({
          code,
          name,
          symbol,
          isActive: form.isActive,
          sortOrder: parseInt(form.sortOrder, 10) || 0,
        });
        notify.success(`Currency "${name}" created`);
      }
      resetForm();
      await fetchCurrencies();
    } catch (e) {
      const msg = e instanceof Error ? e.message : "Failed to save currency";
      setFormError(msg);
      notify.error(msg);
    } finally {
      setSaving(false);
    }
  }

  async function handleToggleActive(cur: PlatformCurrency) {
    try {
      await setCurrencyActive({ id: cur.id, isActive: !cur.is_active });
      notify.success(`Currency "${cur.name}" ${cur.is_active ? "deactivated" : "activated"}`);
      await fetchCurrencies();
    } catch (e) {
      notify.error(e instanceof Error ? e.message : "Failed to toggle status");
    }
  }

  async function handleDelete(cur: PlatformCurrency) {
    if (deleting) return;
    if (!confirm(`Delete currency "${cur.name}" (${cur.code})? This cannot be undone.`)) return;
    setDeleting(cur.id);
    try {
      await deleteCurrency(cur.id);
      notify.success(`Currency "${cur.name}" deleted`);
      if (editingId === cur.id) resetForm();
      await fetchCurrencies();
    } catch (e) {
      notify.error(e instanceof Error ? e.message : "Failed to delete currency");
    } finally {
      setDeleting(null);
    }
  }

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    setForm({ ...form, [e.target.name]: e.target.value });
  };

  const filtered = currencies.filter((c) => {
    if (!searchQuery.trim()) return true;
    const q = searchQuery.toLowerCase();
    return c.name.toLowerCase().includes(q) || c.code.toLowerCase().includes(q);
  });

  return (
    <>
      <SATopbar
        breadcrumb="Billing & Subscriptions"
        title="Currencies"
        actions={
          <SAButton variant="primary" icon={<Plus />} onClick={resetForm}>
            Add Currency
          </SAButton>
        }
      />

      <div className="sa-content">
        <div className="sa-split">
          {/* Left — table */}
          <div className="sa-panel">
            <div className="sa-ph">
              <div>
                <div className="sa-ph-t">All Currencies</div>
                <div className="sa-ph-s">
                  {currencies.length} currenc{currencies.length !== 1 ? "ies" : "y"} configured
                </div>
              </div>
              <div className="sa-ph-search">
                <Search />
                <input
                  placeholder="Search..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                />
              </div>
            </div>

            {loading ? (
              <div style={{ textAlign: "center", padding: 40 }}>
                <Loader2 className="sa-spin" style={{ width: 24, height: 24, color: "var(--sa-orange)" }} />
                <div style={{ marginTop: 8, color: "var(--sa-text-muted)", fontSize: 13 }}>Loading...</div>
              </div>
            ) : error ? (
              <div style={{ padding: 32, textAlign: "center", color: "#dc2626" }}>
                <AlertCircle style={{ width: 24, height: 24, margin: "0 auto 8px" }} />
                <div>{error}</div>
                <SAButton variant="outline" onClick={fetchCurrencies} style={{ marginTop: 8 }}>Retry</SAButton>
              </div>
            ) : (
              <table className="sa-dt">
                <thead>
                  <tr>
                    <th>Currency</th>
                    <th>Code</th>
                    <th>Symbol</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {filtered.length === 0 ? (
                    <tr><td colSpan={5} style={{ textAlign: "center", color: "var(--sa-text-muted)", padding: 24 }}>No currencies found</td></tr>
                  ) : filtered.map((c) => (
                    <tr key={c.id} style={deleting === c.id ? { opacity: 0.5 } : undefined}>
                      <td className="sa-td-name">{c.name}</td>
                      <td><span className="sa-td-code">{c.code}</span></td>
                      <td style={{ fontSize: 16, fontWeight: 600 }}>{c.symbol}</td>
                      <td>
                        <span
                          className={`sa-badge ${c.is_active ? "sa-badge-g" : ""}`}
                          style={{ cursor: "pointer" }}
                          onClick={() => handleToggleActive(c)}
                          title="Click to toggle"
                        >
                          {c.is_active ? "Active" : "Inactive"}
                        </span>
                      </td>
                      <td>
                        <div className="sa-td-actions">
                          <button className="sa-act-btn" title="Edit" onClick={() => startEdit(c)}>
                            <Pencil />
                          </button>
                          <button className="sa-act-btn del" title="Delete" onClick={() => handleDelete(c)} disabled={deleting === c.id}>
                            <Trash2 />
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </div>

          {/* Right — add/edit form */}
          <div className="sa-form-panel">
            <div className="sa-fp-header">
              <div className="sa-fp-title">{editingId ? "Edit Currency" : "Add Currency"}</div>
              <div className="sa-fp-desc">
                {editingId ? "Update currency details" : "Configure a new currency for billing"}
              </div>
              {editingId && (
                <button
                  onClick={resetForm}
                  style={{ position: "absolute", top: 16, right: 16, background: "none", border: "none", cursor: "pointer", color: "var(--sa-text-muted)" }}
                  title="Cancel editing"
                >
                  <X size={16} />
                </button>
              )}
            </div>
            <div className="sa-fp-body">
              {formError && (
                <div style={{ padding: "8px 12px", background: "#fef2f2", borderRadius: 6, color: "#dc2626", fontSize: 13, marginBottom: 16 }}>
                  {formError}
                </div>
              )}
              <div className="sa-form-group">
                <label className="sa-label">Currency Name<span className="sa-req">*</span></label>
                <input
                  className="sa-input"
                  name="name"
                  value={form.name}
                  onChange={handleChange}
                  placeholder="e.g., US Dollar"
                />
              </div>
              <div className="sa-form-row">
                <div className="sa-form-group">
                  <label className="sa-label">ISO Code<span className="sa-req">*</span></label>
                  <input
                    className="sa-input"
                    name="code"
                    value={form.code}
                    onChange={handleChange}
                    placeholder="e.g., USD"
                    maxLength={3}
                    disabled={!!editingId}
                    style={editingId ? { background: "#f3f4f6", cursor: "not-allowed" } : undefined}
                  />
                  <div className="sa-form-hint">3-letter ISO 4217 code{editingId ? " (cannot be changed)" : ""}</div>
                </div>
                <div className="sa-form-group">
                  <label className="sa-label">Symbol<span className="sa-req">*</span></label>
                  <input
                    className="sa-input"
                    name="symbol"
                    value={form.symbol}
                    onChange={handleChange}
                    placeholder="e.g., $"
                  />
                </div>
              </div>
              <div className="sa-form-group">
                <label className="sa-label">Sort Order</label>
                <input
                  className="sa-input"
                  name="sortOrder"
                  type="number"
                  value={form.sortOrder}
                  onChange={handleChange}
                />
                <div className="sa-form-hint">Lower numbers appear first</div>
              </div>
              <div className="sa-toggle-wrap compact">
                <button
                  type="button"
                  className={`sa-toggle${form.isActive ? " on" : ""}`}
                  onClick={() => setForm({ ...form, isActive: !form.isActive })}
                />
                <div className="sa-toggle-info">
                  <div className="sa-toggle-label">Active</div>
                </div>
              </div>
            </div>
            <div className="sa-fp-footer">
              <SAButton variant="ghost" onClick={resetForm}>
                {editingId ? "Cancel" : "Reset"}
              </SAButton>
              <SAButton variant="primary" icon={editingId ? <Pencil /> : <Plus />} onClick={handleSubmit} disabled={saving}>
                {saving ? "Saving..." : editingId ? "Update Currency" : "Add Currency"}
              </SAButton>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
