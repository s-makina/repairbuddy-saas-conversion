"use client";

import { SATopbar, SAButton } from "../SATopbar";
import { ArrowLeft, ArrowRight, Check, Save, Plus, Trash2, Loader2, AlertCircle } from "lucide-react";
import { useState, useEffect, useCallback } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import {
  getBillingCatalog,
  getAdminBillingPlan,
  listBillingIntervals,
  listCurrencies,
  listEntitlementDefinitions,
  createBillingPlan,
  updateBillingPlan,
  createDraftBillingPlanVersionFromActive,
  createBillingPrice,
  updateBillingPrice,
  deleteBillingPrice,
  syncBillingPlanVersionEntitlements,
  activateBillingPlanVersion,
  validateBillingPlanVersionDraft,
} from "@/lib/superadmin";
import type { BillingPlan, BillingPlanVersion, BillingInterval, EntitlementDefinition, BillingPrice, PlatformCurrency } from "@/lib/types";
import { notify } from "@/lib/notify";

const STEPS = ["Details", "Pricing", "Entitlements", "Review"];

type PriceTier = {
  id?: number; // existing price id
  currency: string;
  billingIntervalId: number | null;
  amount: string;
  trialDays: string;
  isDefault: boolean;
  _deleted?: boolean;
};

export function SAPlanBuilderContent() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const editPlanId = searchParams.get("planId") ? parseInt(searchParams.get("planId")!, 10) : null;
  const isEditMode = !!editPlanId;

  const [step, setStep] = useState(0);
  const [initialLoading, setInitialLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);
  const [publishing, setPublishing] = useState(false);

  // Reference data
  const [allIntervals, setAllIntervals] = useState<BillingInterval[]>([]);
  const [allCurrencies, setAllCurrencies] = useState<PlatformCurrency[]>([]);
  const [allEntitlements, setAllEntitlements] = useState<EntitlementDefinition[]>([]);

  // Plan being edited
  const [plan, setPlan] = useState<BillingPlan | null>(null);
  const [draftVersion, setDraftVersion] = useState<BillingPlanVersion | null>(null);

  // Step 1 — Details
  const [details, setDetails] = useState({
    name: "",
    code: "",
    description: "",
    isActive: true,
  });

  // Step 2 — Pricing
  const [priceTiers, setPriceTiers] = useState<PriceTier[]>([]);

  // Step 3 — Entitlements
  const [selectedEntitlements, setSelectedEntitlements] = useState<Map<number, unknown>>(new Map());

  // Load reference data + plan for editing
  const loadData = useCallback(async () => {
    setInitialLoading(true);
    setLoadError(null);
    try {
      const [intervalsRes, currenciesRes, entitlementsRes] = await Promise.all([
        listBillingIntervals({ includeInactive: false }),
        listCurrencies(),
        listEntitlementDefinitions(),
      ]);
      setAllIntervals(intervalsRes.billing_intervals);
      setAllCurrencies(currenciesRes.currencies.filter((c) => c.is_active));
      setAllEntitlements(entitlementsRes.definitions);

      if (editPlanId) {
        const planRes = await getAdminBillingPlan(editPlanId);
        const loadedPlan = planRes.plan;
        setPlan(loadedPlan);

        // Populate details
        setDetails({
          name: loadedPlan.name,
          code: loadedPlan.code,
          description: loadedPlan.description ?? "",
          isActive: loadedPlan.is_active,
        });

        // Find draft or active version
        const versions = loadedPlan.versions ?? [];
        let dv = versions.find((v) => v.status === "draft");
        const activeVersion = versions.find((v) => v.status === "active");

        if (!dv && activeVersion) {
          // Create a draft from the active version for editing
          const draftRes = await createDraftBillingPlanVersionFromActive({
            planId: editPlanId,
            reason: "Editing plan from builder",
          });
          dv = draftRes.version;
        }

        if (dv) {
          setDraftVersion(dv);

          // Populate price tiers
          const prices = dv.prices ?? [];
          if (prices.length > 0) {
            setPriceTiers(
              prices.map((p) => ({
                id: p.id,
                currency: p.currency,
                billingIntervalId: p.billing_interval_id ?? null,
                amount: (p.amount_cents / 100).toFixed(2),
                trialDays: String(p.trial_days ?? 0),
                isDefault: p.is_default,
              }))
            );
          }

          // Populate entitlements
          const ents = dv.entitlements ?? [];
          const map = new Map<number, unknown>();
          ents.forEach((e) => {
            map.set(e.entitlement_definition_id, e.value_json);
          });
          setSelectedEntitlements(map);
        }
      }
    } catch (e) {
      setLoadError(e instanceof Error ? e.message : "Failed to load data");
    } finally {
      setInitialLoading(false);
    }
  }, [editPlanId]);

  useEffect(() => { loadData(); }, [loadData]);

  // ── Helpers ──
  const handleDetailChange = (
    e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>
  ) => setDetails({ ...details, [e.target.name]: e.target.value });

  const addTier = () =>
    setPriceTiers((t) => [
      ...t,
      {
        currency: allCurrencies[0]?.code ?? "USD",
        billingIntervalId: allIntervals[0]?.id ?? null,
        amount: "",
        trialDays: "0",
        isDefault: true,
      },
    ]);

  // When marking a tier as default, un-default any other tier with the same currency+interval pair.
  const toggleDefault = (idx: number) => {
    setPriceTiers((tiers) => {
      const tier = tiers[idx];
      return tiers.map((t, i) => {
        if (i === idx) return { ...t, isDefault: true };
        if (t.currency === tier.currency && t.billingIntervalId === tier.billingIntervalId) {
          return { ...t, isDefault: false };
        }
        return t;
      });
    });
  };

  const removeTier = (i: number) => {
    setPriceTiers((t) => {
      const tier = t[i];
      if (tier.id) {
        // Mark existing prices for deletion
        return t.map((r, idx) => (idx === i ? { ...r, _deleted: true } : r));
      }
      return t.filter((_, idx) => idx !== i);
    });
  };

  const updateTier = (i: number, field: keyof PriceTier, val: string | number | boolean | null) =>
    setPriceTiers((t) => t.map((r, idx) => (idx === i ? { ...r, [field]: val } : r)));

  const toggleEntitlement = (defId: number) => {
    setSelectedEntitlements((prev) => {
      const next = new Map(prev);
      if (next.has(defId)) {
        next.delete(defId);
      } else {
        // Default value based on type
        const def = allEntitlements.find((d) => d.id === defId);
        next.set(defId, def?.value_type === "boolean" ? true : def?.value_type === "integer" ? 1 : true);
      }
      return next;
    });
  };

  const setEntitlementValue = (defId: number, value: unknown) => {
    setSelectedEntitlements((prev) => {
      const next = new Map(prev);
      next.set(defId, value);
      return next;
    });
  };

  // ── Save Draft (plan + details only) ──
  async function saveDraft() {
    if (saving) return;
    const name = details.name.trim();
    if (!name) { notify.error("Plan name is required"); return; }

    setSaving(true);
    try {
      if (plan) {
        // Update existing plan
        const res = await updateBillingPlan({
          planId: plan.id,
          name,
          code: details.code.trim() || plan.code,
          description: details.description.trim() || undefined,
          isActive: details.isActive,
        });
        setPlan(res.plan);
        notify.success("Plan details saved");
      } else {
        // Create new plan
        const res = await createBillingPlan({
          name,
          code: details.code.trim() || undefined,
          description: details.description.trim() || undefined,
          isActive: details.isActive,
        });
        setPlan(res.plan);
        // The plan should come with a draft version
        const versions = res.plan.versions ?? [];
        const dv = versions.find((v) => v.status === "draft");
        if (dv) setDraftVersion(dv);
        notify.success("Plan created");
      }
    } catch (e) {
      notify.error(e instanceof Error ? e.message : "Failed to save plan");
    } finally {
      setSaving(false);
    }
  }

  // ── Save Prices ──
  async function savePrices() {
    if (!draftVersion) {
      notify.error("No draft version available. Save plan details first.");
      return;
    }
    setSaving(true);
    try {
      const visibleTiers = priceTiers.filter((t) => !t._deleted);
      const deletedTiers = priceTiers.filter((t) => t._deleted && t.id);

      // Delete removed prices
      for (const tier of deletedTiers) {
        await deleteBillingPrice({ priceId: tier.id!, reason: "Removed in plan builder" });
      }

      // Create or update prices
      for (const tier of visibleTiers) {
        const amountCents = Math.round(parseFloat(tier.amount || "0") * 100);
        const trialDays = parseInt(tier.trialDays || "0", 10) || 0;

        if (tier.id) {
          await updateBillingPrice({
            priceId: tier.id,
            amountCents,
            trialDays,
            isDefault: tier.isDefault,
          });
        } else {
          const res = await createBillingPrice({
            versionId: draftVersion.id,
            currency: tier.currency,
            billingIntervalId: tier.billingIntervalId,
            amountCents,
            trialDays,
            isDefault: tier.isDefault,
          });
          // Update the tier with the returned ID
          tier.id = res.price.id;
        }
      }

      // Remove deleted from state
      setPriceTiers((t) => t.filter((tier) => !tier._deleted));
      notify.success("Pricing saved");
    } catch (e) {
      notify.error(e instanceof Error ? e.message : "Failed to save prices");
    } finally {
      setSaving(false);
    }
  }

  // ── Save Entitlements ──
  async function saveEntitlements() {
    if (!draftVersion) {
      notify.error("No draft version available. Save plan details first.");
      return;
    }
    setSaving(true);
    try {
      const entitlements = Array.from(selectedEntitlements.entries()).map(([defId, val]) => ({
        entitlement_definition_id: defId,
        value_json: val,
      }));
      await syncBillingPlanVersionEntitlements({
        versionId: draftVersion.id,
        entitlements,
      });
      notify.success("Entitlements saved");
    } catch (e) {
      notify.error(e instanceof Error ? e.message : "Failed to save entitlements");
    } finally {
      setSaving(false);
    }
  }

  // ── Publish (activate version) ──
  async function handlePublish() {
    if (!draftVersion || !plan) {
      notify.error("No draft version to publish");
      return;
    }
    if (!confirm("Activate this plan version? Once activated it becomes immutable.")) return;

    setPublishing(true);
    try {
      // Validate first
      const validationRes = await validateBillingPlanVersionDraft({ versionId: draftVersion.id });
      if ("message" in validationRes && validationRes.message) {
        const errors = validationRes.errors?.join(", ") ?? "";
        notify.error(`Validation failed: ${validationRes.message}${errors ? ` (${errors})` : ""}`);
        setPublishing(false);
        return;
      }

      await activateBillingPlanVersion({
        versionId: draftVersion.id,
        confirm: "ACTIVATE",
        reason: "Activated from plan builder",
      });

      notify.success(`Plan "${plan.name}" version activated!`);
      router.push("/superadmin/billing/plans");
    } catch (e) {
      notify.error(e instanceof Error ? e.message : "Failed to activate plan");
    } finally {
      setPublishing(false);
    }
  }

  // ── Step navigation with auto-save ──
  async function goNext() {
    if (step === 0) {
      await saveDraft();
      // Ensure we have a draft version after creating plan
      if (!draftVersion && plan) {
        try {
          const planRes = await getAdminBillingPlan(plan.id);
          const versions = planRes.plan.versions ?? [];
          const dv = versions.find((v) => v.status === "draft");
          if (dv) setDraftVersion(dv);
        } catch { /* ignore */ }
      }
    }
    if (step === 1) await savePrices();
    if (step === 2) await saveEntitlements();
    setStep((s) => Math.min(s + 1, 3));
  }

  // ── Loading / Error states ──
  if (initialLoading) {
    return (
      <>
        <SATopbar breadcrumb={<>Billing & Subscriptions › <b>Plan Builder</b></>} title={isEditMode ? "Edit Plan" : "Create New Plan"} />
        <div className="sa-content" style={{ maxWidth: 860, textAlign: "center", padding: 60 }}>
          <Loader2 className="sa-spin" style={{ width: 32, height: 32, color: "var(--sa-orange)" }} />
          <div style={{ marginTop: 12, color: "var(--sa-text-muted)", fontSize: 13 }}>Loading...</div>
        </div>
      </>
    );
  }

  if (loadError) {
    return (
      <>
        <SATopbar breadcrumb={<>Billing & Subscriptions › <b>Plan Builder</b></>} title={isEditMode ? "Edit Plan" : "Create New Plan"} />
        <div className="sa-content" style={{ maxWidth: 860, textAlign: "center", padding: 60, color: "#dc2626" }}>
          <AlertCircle style={{ width: 28, height: 28, margin: "0 auto 8px" }} />
          <div>{loadError}</div>
          <SAButton variant="outline" onClick={loadData} style={{ marginTop: 12 }}>Retry</SAButton>
        </div>
      </>
    );
  }

  const visibleTiers = priceTiers.filter((t) => !t._deleted);
  const entitlementCategories = [...new Set(allEntitlements.map((e) => e.is_premium ? "Premium Features" : "Standard Features"))];
  const selectedEnts = allEntitlements.filter((e) => selectedEntitlements.has(e.id));









  return (
    <>
      <SATopbar
        breadcrumb={<>Billing & Subscriptions › <b>Plan Builder</b></>}
        title={isEditMode ? `Edit Plan: ${plan?.name ?? ""}` : "Create New Plan"}
        actions={
          <SAButton variant="ghost" icon={<ArrowLeft size={14} />} onClick={() => router.push("/superadmin/billing/plans")}>
            Back to Plans
          </SAButton>
        }
      />
      <div className="sa-content" style={{ maxWidth: 860 }}>

        {/* ── Stepper ── */}
        <div className="sa-panel" style={{ padding: "20px 24px" }}>
          <div className="sa-stepper">
            {STEPS.map((label, i) => (
              <div
                key={label}
                className={`sa-step${i === step ? " active" : ""}${i < step ? " done" : ""}`}
                onClick={() => i < step && setStep(i)}
                style={{ cursor: i < step ? "pointer" : "default" }}
              >
                <div className="sa-step-num">
                  {i < step ? <Check style={{ width: 14, height: 14 }} /> : i + 1}
                </div>
                <span className="sa-step-label">{label}</span>
              </div>
            ))}
          </div>
        </div>

        {/* ── Step 1: Details ── */}
        {step === 0 && (
          <div className="sa-form-panel" style={{ position: "static" }}>
            <div className="sa-fp-header">
              <div className="sa-fp-title">Plan Details</div>
              <div className="sa-fp-desc">Define the basic information for this billing plan.</div>
            </div>
            <div className="sa-fp-body">
              <div className="sa-form-group">
                <label className="sa-label">Plan Name <span className="sa-req">*</span></label>
                <input className="sa-input" name="name" value={details.name} onChange={handleDetailChange} placeholder="e.g. Professional" />
                <div className="sa-form-hint">The display name shown to tenants.</div>
              </div>
              <div className="sa-form-row">
                <div className="sa-form-group">
                  <label className="sa-label">Code</label>
                  <input
                    className="sa-input"
                    name="code"
                    value={details.code}
                    onChange={handleDetailChange}
                    placeholder="e.g. professional (auto-generated)"
                    disabled={isEditMode}
                    style={isEditMode ? { background: "#f3f4f6", cursor: "not-allowed" } : undefined}
                  />
                  <div className="sa-form-hint">Unique identifier.{isEditMode ? " Cannot be changed." : " Auto-generated from name if left empty."}</div>
                </div>
              </div>
              <div className="sa-form-group">
                <label className="sa-label">Description</label>
                <textarea className="sa-textarea" name="description" value={details.description} onChange={handleDetailChange} rows={3} placeholder="Describe what this plan includes…" />
              </div>
              <div style={{ marginTop: 24, paddingTop: 20, borderTop: "1px solid var(--sa-border)" }}>
                <div style={{ fontSize: 13, fontWeight: 700, color: "var(--sa-text)", marginBottom: 12 }}>Plan Options</div>
                <div className="sa-toggle-wrap">
                  <button type="button" className={`sa-toggle${details.isActive ? " on" : ""}`} onClick={() => setDetails({ ...details, isActive: !details.isActive })} />
                  <div className="sa-toggle-info">
                    <div className="sa-toggle-label">Active</div>
                    <div className="sa-toggle-desc">Make this plan visible and available for purchase.</div>
                  </div>
                </div>
              </div>
            </div>
            <div className="sa-fp-footer">
              <SAButton variant="ghost" onClick={() => router.push("/superadmin/billing/plans")}>Cancel</SAButton>
              <div className="sa-fp-footer-end">
                <SAButton variant="ghost" icon={<Save size={14} />} onClick={saveDraft} disabled={saving}>
                  {saving ? "Saving..." : "Save Draft"}
                </SAButton>
                <SAButton variant="primary" icon={<ArrowRight size={14} />} onClick={goNext} disabled={saving}>
                  Next: Pricing
                </SAButton>
              </div>
            </div>
          </div>
        )}

        {/* ── Step 2: Pricing ── */}
        {step === 1 && (
          <div className="sa-form-panel" style={{ position: "static" }}>
            <div className="sa-fp-header">
              <div className="sa-fp-title">Plan Pricing</div>
              <div className="sa-fp-desc">Define pricing tiers for different currencies and billing intervals.</div>
            </div>
            <div className="sa-fp-body">
              {!draftVersion && (
                <div style={{ padding: "12px 16px", background: "#fef3c7", borderRadius: 6, color: "#92400e", fontSize: 13, marginBottom: 16 }}>
                  Save plan details first to configure pricing.
                </div>
              )}
              <div style={{ marginBottom: 16, display: "flex", alignItems: "center", justifyContent: "space-between" }}>
                <div style={{ fontSize: 13, fontWeight: 700, color: "var(--sa-text)" }}>Price Tiers</div>
                <SAButton variant="ghost" icon={<Plus size={13} />} onClick={addTier} disabled={!draftVersion}>Add Price</SAButton>
              </div>
              {visibleTiers.map((tier, visualIdx) => {
                const realIdx = priceTiers.findIndex((t) => t === tier);
                return (
                  <div key={visualIdx} className="sa-price-row">
                    <div className="sa-form-group" style={{ margin: 0 }}>
                      {visualIdx === 0 && <label className="sa-label">Currency</label>}
                      <select
                        className="sa-select"
                        value={tier.currency}
                        onChange={(e) => updateTier(realIdx, "currency", e.target.value)}
                        disabled={!!tier.id}
                      >
                        {allCurrencies.map((c) => (
                          <option key={c.code} value={c.code}>{c.code} ({c.symbol})</option>
                        ))}
                        {!allCurrencies.some((c) => c.code === tier.currency) && (
                          <option value={tier.currency}>{tier.currency}</option>
                        )}
                      </select>
                    </div>
                    <div className="sa-form-group" style={{ margin: 0 }}>
                      {visualIdx === 0 && <label className="sa-label">Interval</label>}
                      <select
                        className="sa-select"
                        value={tier.billingIntervalId ?? ""}
                        onChange={(e) => updateTier(realIdx, "billingIntervalId", e.target.value ? parseInt(e.target.value, 10) : null)}
                        disabled={!!tier.id}
                      >
                        <option value="">Select interval</option>
                        {allIntervals.map((iv) => (
                          <option key={iv.id} value={iv.id}>{iv.name} ({iv.months}mo)</option>
                        ))}
                      </select>
                    </div>
                    <div className="sa-form-group" style={{ margin: 0 }}>
                      {visualIdx === 0 && <label className="sa-label">Amount</label>}
                      <input
                        className="sa-input"
                        type="number"
                        step="0.01"
                        min="0"
                        value={tier.amount}
                        placeholder="0.00"
                        onChange={(e) => updateTier(realIdx, "amount", e.target.value)}
                      />
                    </div>
                    <div className="sa-form-group" style={{ margin: 0 }}>
                      {visualIdx === 0 && <label className="sa-label">Trial (days)</label>}
                      <input
                        className="sa-input"
                        type="number"
                        min="0"
                        value={tier.trialDays}
                        placeholder="0"
                        onChange={(e) => updateTier(realIdx, "trialDays", e.target.value)}
                      />
                    </div>
                    <div className="sa-form-group" style={{ margin: 0 }}>
                      {visualIdx === 0 && <label className="sa-label" style={{ whiteSpace: "nowrap" }}>Default</label>}
                      <div style={{ height: 36, display: "flex", alignItems: "center", justifyContent: "center" }}>
                        <input
                          type="checkbox"
                          checked={tier.isDefault}
                          onChange={() => toggleDefault(realIdx)}
                          title="Mark as default price for this currency + interval"
                          style={{ width: 16, height: 16, cursor: "pointer", accentColor: "var(--sa-orange)" }}
                        />
                      </div>
                    </div>
                    <button
                      className="sa-pr-del"
                      style={{ marginTop: visualIdx === 0 ? 22 : 0 }}
                      title="Remove"
                      onClick={() => removeTier(realIdx)}
                    >
                      <Trash2 size={14} />
                    </button>
                  </div>
                );
              })}
              {visibleTiers.length === 0 && (
                <div style={{ padding: 20, textAlign: "center", color: "var(--sa-text-muted)", fontSize: 13 }}>
                  No price tiers configured. Click &ldquo;Add Price&rdquo; to add one.
                </div>
              )}
              <div className="sa-price-info">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Add multiple price tiers for different currencies and billing intervals. At least one price tier is required.
              </div>
            </div>
            <div className="sa-fp-footer">
              <SAButton variant="ghost" icon={<ArrowLeft size={14} />} onClick={() => setStep(0)}>Previous</SAButton>
              <div className="sa-fp-footer-end">
                <SAButton variant="ghost" icon={<Save size={14} />} onClick={savePrices} disabled={saving || !draftVersion}>
                  {saving ? "Saving..." : "Save Prices"}
                </SAButton>
                <SAButton variant="primary" icon={<ArrowRight size={14} />} onClick={goNext} disabled={saving}>
                  Next: Entitlements
                </SAButton>
              </div>
            </div>
          </div>
        )}

        {/* ── Step 3: Entitlements ── */}
        {step === 2 && (
          <div className="sa-form-panel" style={{ position: "static" }}>
            <div className="sa-fp-header">
              <div className="sa-fp-title">Plan Entitlements</div>
              <div className="sa-fp-desc">Select which features and limits are included in this plan.</div>
            </div>
            <div className="sa-fp-body">
              {!draftVersion && (
                <div style={{ padding: "12px 16px", background: "#fef3c7", borderRadius: 6, color: "#92400e", fontSize: 13, marginBottom: 16 }}>
                  Save plan details first to configure entitlements.
                </div>
              )}
              {allEntitlements.length === 0 ? (
                <div style={{ padding: 24, textAlign: "center", color: "var(--sa-text-muted)", fontSize: 13 }}>
                  No entitlement definitions found. Create some in the Entitlements page first.
                </div>
              ) : (
                entitlementCategories.map((cat) => (
                  <div key={cat}>
                    <div className="sa-cat-label">{cat}</div>
                    <div className="sa-ent-grid">
                      {allEntitlements
                        .filter((e) => (e.is_premium ? "Premium Features" : "Standard Features") === cat)
                        .map((ent) => {
                          const isSelected = selectedEntitlements.has(ent.id);
                          const value = selectedEntitlements.get(ent.id);
                          return (
                            <div
                              key={ent.id}
                              className={`sa-ent-item${isSelected ? " selected" : ""}`}
                              onClick={() => toggleEntitlement(ent.id)}
                            >
                              <div className="sa-ent-check">
                                {isSelected && (
                                  <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 13l4 4L19 7" />
                                  </svg>
                                )}
                              </div>
                              <div style={{ flex: 1 }}>
                                <div className="sa-ent-name">{ent.name}</div>
                                <div className="sa-ent-type">{ent.value_type} · {ent.code}</div>
                                {isSelected && ent.value_type === "integer" && (
                                  <input
                                    className="sa-input"
                                    type="number"
                                    min="0"
                                    value={typeof value === "number" ? value : ""}
                                    onClick={(e) => e.stopPropagation()}
                                    onChange={(e) => {
                                      e.stopPropagation();
                                      setEntitlementValue(ent.id, parseInt(e.target.value, 10) || 0);
                                    }}
                                    style={{ marginTop: 6, padding: "4px 8px", fontSize: 12, maxWidth: 120 }}
                                    placeholder="Value"
                                  />
                                )}
                              </div>
                            </div>
                          );
                        })}
                    </div>
                  </div>
                ))
              )}
            </div>
            <div className="sa-fp-footer">
              <SAButton variant="ghost" icon={<ArrowLeft size={14} />} onClick={() => setStep(1)}>Previous</SAButton>
              <div className="sa-fp-footer-end">
                <SAButton variant="ghost" icon={<Save size={14} />} onClick={saveEntitlements} disabled={saving || !draftVersion}>
                  {saving ? "Saving..." : "Save Entitlements"}
                </SAButton>
                <SAButton variant="primary" icon={<ArrowRight size={14} />} onClick={goNext} disabled={saving}>
                  Next: Review
                </SAButton>
              </div>
            </div>
          </div>
        )}

        {/* ── Step 4: Review ── */}
        {step === 3 && (
          <div className="sa-form-panel" style={{ position: "static" }}>
            <div className="sa-success-banner" style={{ margin: "0 0 4px" }}>
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
              All steps completed! Review your plan details below and publish when ready.
            </div>
            <div className="sa-fp-header">
              <div>
                <div className="sa-fp-title">Plan Summary</div>
                <div className="sa-fp-desc">Review all details before publishing.</div>
              </div>
            </div>
            <div className="sa-fp-body">
              <div className="sa-rv-section">
                <div className="sa-rv-title">
                  Plan Details
                  <button onClick={() => setStep(0)}>Edit</button>
                </div>
                <div className="sa-rv-row">
                  <div className="sa-rv-label">Plan Name</div>
                  <div className="sa-rv-value">{details.name || "—"}</div>
                </div>
                <div className="sa-rv-row">
                  <div className="sa-rv-label">Code</div>
                  <div className="sa-rv-value" style={{ fontFamily: "monospace", fontSize: 12 }}>
                    {details.code || plan?.code || "(auto-generated)"}
                  </div>
                </div>
                {details.description && (
                  <div className="sa-rv-row">
                    <div className="sa-rv-label">Description</div>
                    <div className="sa-rv-value">{details.description}</div>
                  </div>
                )}
                <div className="sa-rv-row">
                  <div className="sa-rv-label">Status</div>
                  <span className={`sa-rv-tag ${details.isActive ? "tag-green" : "tag-blue"}`}>
                    {details.isActive ? "Active" : "Inactive"}
                  </span>
                </div>
              </div>
              <div className="sa-rv-section">
                <div className="sa-rv-title">
                  Pricing ({visibleTiers.length} tier{visibleTiers.length !== 1 ? "s" : ""})
                  <button onClick={() => setStep(1)}>Edit</button>
                </div>
                {visibleTiers.length > 0 ? (
                  <div className="sa-price-summary">
                    {visibleTiers.map((tier, i) => {
                      const cur = allCurrencies.find((c) => c.code === tier.currency);
                      const iv = allIntervals.find((interval) => interval.id === tier.billingIntervalId);
                      return (
                        <div className="sa-ps-card" key={i}>
                          <div className="sa-ps-cur">{tier.currency}</div>
                          <div className="sa-ps-amt">
                            {cur?.symbol ?? "$"}{tier.amount || "0.00"}
                          </div>
                          <div className="sa-ps-int">/ {iv?.name ?? "period"}</div>
                          {parseInt(tier.trialDays) > 0 && (
                            <div style={{ fontSize: 11, color: "var(--sa-text-muted)" }}>
                              {tier.trialDays}-day trial
                            </div>
                          )}
                        </div>
                      );
                    })}
                  </div>
                ) : (
                  <div style={{ color: "var(--sa-text-muted)", fontSize: 13, fontStyle: "italic" }}>
                    No pricing configured
                  </div>
                )}
              </div>
              <div className="sa-rv-section">
                <div className="sa-rv-title">
                  Entitlements ({selectedEntitlements.size} selected)
                  <button onClick={() => setStep(2)}>Edit</button>
                </div>
                {selectedEnts.length > 0 ? (
                  <div className="sa-ent-pills">
                    {selectedEnts.map((e) => {
                      const val = selectedEntitlements.get(e.id);
                      const suffix = typeof val === "number" ? `: ${val}` : "";
                      return (
                        <div className="sa-ent-pill" key={e.id}>✓ {e.name}{suffix}</div>
                      );
                    })}
                  </div>
                ) : (
                  <div style={{ color: "var(--sa-text-muted)", fontSize: 13, fontStyle: "italic" }}>
                    No entitlements selected
                  </div>
                )}
              </div>
            </div>
            <div className="sa-fp-footer">
              <SAButton variant="ghost" icon={<ArrowLeft size={14} />} onClick={() => setStep(2)}>Previous</SAButton>
              <div className="sa-fp-footer-end">
                <SAButton variant="ghost" onClick={() => router.push("/superadmin/billing/plans")}>
                  Back to Plans
                </SAButton>
                <SAButton
                  variant="primary"
                  icon={publishing ? <Loader2 size={14} className="sa-spin" /> : <Check size={14} />}
                  style={{ background: "var(--sa-green)", boxShadow: "0 2px 8px rgba(43,138,62,.25)" }}
                  onClick={handlePublish}
                  disabled={publishing || !draftVersion}
                >
                  {publishing ? "Publishing..." : "Publish Plan"}
                </SAButton>
              </div>
            </div>
          </div>
        )}

      </div>
    </>
  );
}
