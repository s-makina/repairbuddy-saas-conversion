"use client";

import { SATopbar, SAButton } from "../SATopbar";
import { Plus, Search, Pencil, Trash2, Loader2, AlertCircle, X } from "lucide-react";
import { useState, useEffect, useCallback } from "react";
import {
  listEntitlementDefinitions,
  createEntitlementDefinition,
  updateEntitlementDefinition,
  deleteEntitlementDefinition,
} from "@/lib/superadmin";
import type { EntitlementDefinition } from "@/lib/types";
import { notify } from "@/lib/notify";

export function SAEntitlementsContent() {
  const [definitions, setDefinitions] = useState<EntitlementDefinition[]>([]);
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
    type: "boolean",
    description: "",
    isPremium: false,
  });
  const [formError, setFormError] = useState<string | null>(null);

  const fetchDefinitions = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await listEntitlementDefinitions();
      setDefinitions(data.definitions);
    } catch (e) {
      setError(e instanceof Error ? e.message : "Failed to load entitlements");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { fetchDefinitions(); }, [fetchDefinitions]);

  function resetForm() {
    setEditingId(null);
    setForm({ name: "", code: "", type: "boolean", description: "", isPremium: false });
    setFormError(null);
  }

  function startEdit(def: EntitlementDefinition) {
    setEditingId(def.id);
    setForm({
      name: def.name,
      code: def.code,
      type: def.value_type,
      description: def.description ?? "",
      isPremium: def.is_premium ?? false,
    });
    setFormError(null);
  }

  async function handleSubmit() {
    if (saving) return;
    const name = form.name.trim();
    const code = form.code.trim();
    if (!name) { setFormError("Name is required"); return; }

    setSaving(true);
    setFormError(null);
    try {
      if (editingId) {
        await updateEntitlementDefinition({
          id: editingId,
          code: code || name.toLowerCase().replace(/\s+/g, "_"),
          name,
          valueType: form.type,
          description: form.description.trim() || undefined,
          isPremium: form.isPremium,
        });
        notify.success(`Entitlement "${name}" updated`);
      } else {
        await createEntitlementDefinition({
          code: code || undefined,
          name,
          valueType: form.type,
          description: form.description.trim() || undefined,
          isPremium: form.isPremium,
        });
        notify.success(`Entitlement "${name}" created`);
      }
      resetForm();
      await fetchDefinitions();
    } catch (e) {
      const msg = e instanceof Error ? e.message : "Failed to save entitlement";
      setFormError(msg);
      notify.error(msg);
    } finally {
      setSaving(false);
    }
  }

  async function handleDelete(def: EntitlementDefinition) {
    if (deleting) return;
    if (!confirm(`Delete entitlement "${def.name}"? This cannot be undone.`)) return;
    setDeleting(def.id);
    try {
      await deleteEntitlementDefinition({ id: def.id });
      notify.success(`Entitlement "${def.name}" deleted`);
      if (editingId === def.id) resetForm();
      await fetchDefinitions();
    } catch (e) {
      notify.error(e instanceof Error ? e.message : "Failed to delete entitlement");
    } finally {
      setDeleting(null);
    }
  }

  const handleChange = (
    e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>
  ) => {
    setForm({ ...form, [e.target.name]: e.target.value });
  };

  const filtered = definitions.filter((d) => {
    if (!searchQuery.trim()) return true;
    const q = searchQuery.toLowerCase();
    return d.name.toLowerCase().includes(q) || d.code.toLowerCase().includes(q);
  });

  return (
    <>
      <SATopbar
        breadcrumb="Billing & Subscriptions"
        title="Entitlements"
        actions={
          <SAButton variant="primary" icon={<Plus />} onClick={resetForm}>
            Add Entitlement
          </SAButton>
        }
      />

      <div className="sa-content">
        <div className="sa-split">
          {/* Left — table */}
          <div className="sa-panel">
            <div className="sa-ph">
              <div>
                <div className="sa-ph-t">All Entitlements</div>
                <div className="sa-ph-s">
                  {definitions.length} entitlement{definitions.length !== 1 ? "s" : ""} configured
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
                <SAButton variant="outline" onClick={fetchDefinitions} style={{ marginTop: 8 }}>Retry</SAButton>
              </div>
            ) : (
              <table className="sa-dt">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Type</th>
                    <th>Premium</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {filtered.length === 0 ? (
                    <tr><td colSpan={5} style={{ textAlign: "center", color: "var(--sa-text-muted)", padding: 24 }}>No entitlements found</td></tr>
                  ) : filtered.map((d) => (
                    <tr key={d.id} style={deleting === d.id ? { opacity: 0.5 } : undefined}>
                      <td className="sa-td-name">{d.name}</td>
                      <td><span className="sa-td-code">{d.code}</span></td>
                      <td><span className="sa-badge">{d.value_type}</span></td>
                      <td>
                        <span className={`sa-badge ${d.is_premium ? "sa-badge-g" : ""}`}>
                          {d.is_premium ? "Yes" : "No"}
                        </span>
                      </td>
                      <td>
                        <div className="sa-td-actions">
                          <button className="sa-act-btn" title="Edit" onClick={() => startEdit(d)}>
                            <Pencil />
                          </button>
                          <button className="sa-act-btn del" title="Delete" onClick={() => handleDelete(d)} disabled={deleting === d.id}>
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
              <div className="sa-fp-title">{editingId ? "Edit Entitlement" : "Add Entitlement"}</div>
              <div className="sa-fp-desc">
                {editingId ? "Update entitlement details" : "Define a new feature or limit for plans"}
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
                <label className="sa-label">Name<span className="sa-req">*</span></label>
                <input
                  className="sa-input"
                  name="name"
                  value={form.name}
                  onChange={handleChange}
                  placeholder="e.g., Max Users"
                />
              </div>
              <div className="sa-form-group">
                <label className="sa-label">Code</label>
                <input
                  className="sa-input"
                  name="code"
                  value={form.code}
                  onChange={handleChange}
                  placeholder="e.g., max_users (auto-generated if empty)"
                />
                <div className="sa-form-hint">Unique identifier, use snake_case</div>
              </div>
              <div className="sa-form-row">
                <div className="sa-form-group">
                  <label className="sa-label">Value Type</label>
                  <select className="sa-select" name="type" value={form.type} onChange={handleChange}>
                    <option value="boolean">Boolean</option>
                    <option value="integer">Integer</option>
                    <option value="string">String</option>
                    <option value="json">JSON</option>
                  </select>
                </div>
              </div>
              <div className="sa-form-group">
                <label className="sa-label">Description</label>
                <textarea
                  className="sa-textarea"
                  name="description"
                  value={form.description}
                  onChange={handleChange}
                  placeholder="Brief description of what this entitlement controls..."
                  rows={3}
                />
              </div>
              <div className="sa-toggle-wrap compact">
                <button
                  type="button"
                  className={`sa-toggle${form.isPremium ? " on" : ""}`}
                  onClick={() => setForm({ ...form, isPremium: !form.isPremium })}
                />
                <div className="sa-toggle-info">
                  <div className="sa-toggle-label">Premium Feature</div>
                </div>
              </div>
            </div>
            <div className="sa-fp-footer">
              <SAButton variant="ghost" onClick={resetForm}>
                {editingId ? "Cancel" : "Reset"}
              </SAButton>
              <SAButton variant="primary" icon={editingId ? <Pencil /> : <Plus />} onClick={handleSubmit} disabled={saving}>
                {saving ? "Saving..." : editingId ? "Update Entitlement" : "Add Entitlement"}
              </SAButton>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
