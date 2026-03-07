"use client";

import { SATopbar, SAButton } from "../SATopbar";
import { Plus, Search, Pencil, Trash2, Loader2, AlertCircle, X } from "lucide-react";
import { useState, useEffect, useCallback } from "react";
import {
  listBillingIntervals,
  createBillingInterval,
  updateBillingInterval,
  setBillingIntervalActive,
  deleteBillingInterval,
} from "@/lib/superadmin";
import type { BillingInterval } from "@/lib/types";
import { notify } from "@/lib/notify";

export function SABillingIntervalsContent() {
  const [intervals, setIntervals] = useState<BillingInterval[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [searchQuery, setSearchQuery] = useState("");
  const [saving, setSaving] = useState(false);
  const [deleting, setDeleting] = useState<number | null>(null);

  // Form state
  const [editingId, setEditingId] = useState<number | null>(null);
  const [form, setForm] = useState({ name: "", months: "1", isActive: true });
  const [formError, setFormError] = useState<string | null>(null);

  const fetchIntervals = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await listBillingIntervals({ includeInactive: true });
      setIntervals(data.billing_intervals);
    } catch (e) {
      setError(e instanceof Error ? e.message : "Failed to load intervals");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { fetchIntervals(); }, [fetchIntervals]);

  function resetForm() {
    setEditingId(null);
    setForm({ name: "", months: "1", isActive: true });
    setFormError(null);
  }

  function startEdit(iv: BillingInterval) {
    setEditingId(iv.id);
    setForm({ name: iv.name, months: String(iv.months), isActive: iv.is_active });
    setFormError(null);
  }

  async function handleSubmit() {
    if (saving) return;
    const name = form.name.trim();
    if (!name) { setFormError("Name is required"); return; }
    const months = parseInt(form.months, 10);
    if (!months || months < 1) { setFormError("Months must be at least 1"); return; }

    setSaving(true);
    setFormError(null);
    try {
      if (editingId) {
        const existing = intervals.find((i) => i.id === editingId);
        await updateBillingInterval({
          intervalId: editingId,
          code: existing?.code ?? name.toLowerCase().replace(/\s+/g, "_"),
          name,
          months,
          isActive: form.isActive,
        });
        notify.success(`Interval "${name}" updated`);
      } else {
        await createBillingInterval({ name, months, isActive: form.isActive });
        notify.success(`Interval "${name}" created`);
      }
      resetForm();
      await fetchIntervals();
    } catch (e) {
      const msg = e instanceof Error ? e.message : "Failed to save interval";
      setFormError(msg);
      notify.error(msg);
    } finally {
      setSaving(false);
    }
  }

  async function handleToggleActive(iv: BillingInterval) {
    try {
      await setBillingIntervalActive({ intervalId: iv.id, isActive: !iv.is_active });
      notify.success(`Interval "${iv.name}" ${iv.is_active ? "deactivated" : "activated"}`);
      await fetchIntervals();
    } catch (e) {
      notify.error(e instanceof Error ? e.message : "Failed to toggle status");
    }
  }

  async function handleDelete(iv: BillingInterval) {
    if (deleting) return;
    if (!confirm(`Delete interval "${iv.name}"? This cannot be undone.`)) return;
    setDeleting(iv.id);
    try {
      await deleteBillingInterval({ intervalId: iv.id });
      notify.success(`Interval "${iv.name}" deleted`);
      if (editingId === iv.id) resetForm();
      await fetchIntervals();
    } catch (e) {
      notify.error(e instanceof Error ? e.message : "Failed to delete interval");
    } finally {
      setDeleting(null);
    }
  }

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    setForm({ ...form, [e.target.name]: e.target.value });
  };

  const filtered = intervals.filter((iv) => {
    if (!searchQuery.trim()) return true;
    const q = searchQuery.toLowerCase();
    return iv.name.toLowerCase().includes(q) || iv.code.toLowerCase().includes(q);
  });

  return (
    <>
      <SATopbar
        breadcrumb="Billing & Subscriptions"
        title="Billing Intervals"
        actions={
          <SAButton variant="primary" icon={<Plus />} onClick={resetForm}>
            Add Interval
          </SAButton>
        }
      />

      <div className="sa-content">
        <div className="sa-split">
          {/* Left — table */}
          <div className="sa-panel">
            <div className="sa-ph">
              <div>
                <div className="sa-ph-t">All Intervals</div>
                <div className="sa-ph-s">
                  {intervals.length} interval{intervals.length !== 1 ? "s" : ""} configured
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
                <SAButton variant="outline" onClick={fetchIntervals} style={{ marginTop: 8 }}>Retry</SAButton>
              </div>
            ) : (
              <table className="sa-dt">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Months</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {filtered.length === 0 ? (
                    <tr><td colSpan={5} style={{ textAlign: "center", color: "var(--sa-text-muted)", padding: 24 }}>No intervals found</td></tr>
                  ) : filtered.map((iv) => (
                    <tr key={iv.id} style={deleting === iv.id ? { opacity: 0.5 } : undefined}>
                      <td className="sa-td-name">{iv.name}</td>
                      <td><span className="sa-td-code">{iv.code}</span></td>
                      <td>{iv.months}</td>
                      <td>
                        <span
                          className={`sa-badge ${iv.is_active ? "sa-badge-g" : ""}`}
                          style={{ cursor: "pointer" }}
                          onClick={() => handleToggleActive(iv)}
                          title="Click to toggle"
                        >
                          {iv.is_active ? "Active" : "Inactive"}
                        </span>
                      </td>
                      <td>
                        <div className="sa-td-actions">
                          <button className="sa-act-btn" title="Edit" onClick={() => startEdit(iv)}>
                            <Pencil />
                          </button>
                          <button className="sa-act-btn del" title="Delete" onClick={() => handleDelete(iv)} disabled={deleting === iv.id}>
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
              <div className="sa-fp-title">{editingId ? "Edit Interval" : "Add Interval"}</div>
              <div className="sa-fp-desc">
                {editingId ? "Update interval details" : "Configure a new billing interval"}
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
                <label className="sa-label">Interval Name<span className="sa-req">*</span></label>
                <input
                  className="sa-input"
                  name="name"
                  value={form.name}
                  onChange={handleChange}
                  placeholder="e.g., Quarterly"
                />
              </div>
              <div className="sa-form-group">
                <label className="sa-label">Duration (months)<span className="sa-req">*</span></label>
                <input
                  className="sa-input"
                  name="months"
                  type="number"
                  min="1"
                  value={form.months}
                  onChange={handleChange}
                />
                <div className="sa-form-hint">Number of months per billing cycle</div>
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
                {saving ? "Saving..." : editingId ? "Update Interval" : "Add Interval"}
              </SAButton>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
