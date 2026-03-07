"use client";

import { SATopbar, SAButton } from "../SATopbar";
import { ArrowLeft, ArrowRight, Check, Save, Plus, Trash2 } from "lucide-react";
import { useState } from "react";

/* ── Step labels ── */
const STEPS = ["Details", "Pricing", "Entitlements", "Review"];

/* ── Entitlement data ── */
type Entitlement = { id: string; name: string; type: string; category: string };
const ALL_ENTITLEMENTS: Entitlement[] = [
  { id: "jobs",      name: "Job Management",     type: "Boolean · Core",     category: "Core Features" },
  { id: "invoicing", name: "Invoicing",           type: "Boolean · Core",     category: "Core Features" },
  { id: "customers", name: "Customer Management", type: "Boolean · Core",     category: "Core Features" },
  { id: "inventory", name: "Inventory Tracking",  type: "Boolean · Core",     category: "Core Features" },
  { id: "booking",   name: "Online Booking",      type: "Boolean · Advanced", category: "Advanced Features" },
  { id: "workflows", name: "Automated Workflows", type: "Boolean · Advanced", category: "Advanced Features" },
  { id: "reports",   name: "Reports & Analytics", type: "Boolean · Advanced", category: "Advanced Features" },
  { id: "api",       name: "API Access",          type: "Boolean · Advanced", category: "Advanced Features" },
  { id: "users",     name: "Max Users: 25",       type: "Numeric · Limit",    category: "Limits" },
  { id: "storage",   name: "Storage: 10 GB",      type: "Numeric · Limit",    category: "Limits" },
  { id: "branches",  name: "Max Branches: 3",     type: "Numeric · Limit",    category: "Limits" },
  { id: "domain",    name: "Custom Domain",       type: "Boolean · Premium",  category: "Limits" },
];

type PriceTier = { currency: string; interval: string; amount: string };

/* ── stepper data ── */
export function SAPlanBuilderContent() {
  const [step, setStep] = useState(0);

  /* Step 1 — Details */
  const [details, setDetails] = useState({
    name: "Professional",
    slug: "professional",
    sortOrder: "3",
    description: "Enhanced professional plan with additional features for growing businesses.",
    planType: "Recurring",
    trialDays: "14",
    isActive: true,
    isFeatured: true,
    isHidden: false,
  });

  /* Step 2 — Pricing */
  const [priceTiers, setPriceTiers] = useState<PriceTier[]>([
    { currency: "USD ($)", interval: "Monthly", amount: "79.00" },
    { currency: "USD ($)", interval: "Yearly",  amount: "790.00" },
    { currency: "GBP (£)", interval: "Monthly", amount: "65.00" },
  ]);

  const addTier = () =>
    setPriceTiers((t) => [...t, { currency: "USD ($)", interval: "Monthly", amount: "" }]);
  const removeTier = (i: number) =>
    setPriceTiers((t) => t.filter((_, idx) => idx !== i));
  const updateTier = (i: number, field: keyof PriceTier, val: string) =>
    setPriceTiers((t) => t.map((r, idx) => (idx === i ? { ...r, [field]: val } : r)));

  /* Step 3 — Entitlements */
  const [selected, setSelected] = useState<Set<string>>(
    new Set(["jobs", "invoicing", "customers", "booking", "reports", "users", "storage"])
  );
  const toggleEnt = (id: string) =>
    setSelected((s) => {
      const n = new Set(s);
      n.has(id) ? n.delete(id) : n.add(id);
      return n;
    });

  const handleDetailChange = (
    e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>
  ) => setDetails({ ...details, [e.target.name]: e.target.value });

  const toggleDetail = (key: "isActive" | "isFeatured" | "isHidden") =>
    setDetails({ ...details, [key]: !details[key] });

  const categories = [...new Set(ALL_ENTITLEMENTS.map((e) => e.category))];
  const selectedEnts = ALL_ENTITLEMENTS.filter((e) => selected.has(e.id));

  /* ── Stepper ── */
  const Stepper = () => (
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
  );

  /* ── Step 1: Details ── */
  const StepDetails = () => (
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
            <label className="sa-label">Slug <span className="sa-req">*</span></label>
            <input className="sa-input" name="slug" value={details.slug} onChange={handleDetailChange} placeholder="e.g. professional" />
            <div className="sa-form-hint">URL-safe identifier. Auto-generated from name.</div>
          </div>
          <div className="sa-form-group">
            <label className="sa-label">Sort Order</label>
            <input className="sa-input" name="sortOrder" type="number" value={details.sortOrder} onChange={handleDetailChange} />
            <div className="sa-form-hint">Controls display order on pricing page.</div>
          </div>
        </div>
        <div className="sa-form-group">
          <label className="sa-label">Description</label>
          <textarea className="sa-textarea" name="description" value={details.description} onChange={handleDetailChange} rows={3} placeholder="Describe what this plan includes…" />
        </div>
        <div className="sa-form-row">
          <div className="sa-form-group">
            <label className="sa-label">Plan Type</label>
            <select className="sa-select" name="planType" value={details.planType} onChange={handleDetailChange}>
              <option>Recurring</option>
              <option>One-Time</option>
              <option>Usage-Based</option>
            </select>
          </div>
          <div className="sa-form-group">
            <label className="sa-label">Trial Period (days)</label>
            <input className="sa-input" name="trialDays" type="number" value={details.trialDays} onChange={handleDetailChange} />
            <div className="sa-form-hint">Set to 0 for no trial.</div>
          </div>
        </div>
        <div style={{ marginTop: 24, paddingTop: 20, borderTop: "1px solid var(--sa-border)" }}>
          <div style={{ fontSize: 13, fontWeight: 700, color: "var(--sa-text)", marginBottom: 12 }}>Plan Options</div>
          {([
            { key: "isActive" as const,   label: "Active",   desc: "Make this plan visible and available for purchase." },
            { key: "isFeatured" as const, label: "Featured", desc: 'Highlight this plan on the pricing page with a "Most Popular" badge.' },
            { key: "isHidden" as const,   label: "Hidden",   desc: "Only accessible via direct link. Not shown on public pricing page." },
          ] as const).map(({ key, label, desc }) => (
            <div className="sa-toggle-wrap" key={key}>
              <button type="button" className={`sa-toggle${details[key] ? " on" : ""}`} onClick={() => toggleDetail(key)} />
              <div className="sa-toggle-info">
                <div className="sa-toggle-label">{label}</div>
                <div className="sa-toggle-desc">{desc}</div>
              </div>
            </div>
          ))}
        </div>
      </div>
      <div className="sa-fp-footer">
        <SAButton variant="ghost">Cancel</SAButton>
        <div className="sa-fp-footer-end">
          <SAButton variant="ghost" icon={<Save size={14} />}>Save Draft</SAButton>
          <SAButton variant="primary" icon={<ArrowRight size={14} />} onClick={() => setStep(1)}>Next: Pricing</SAButton>
        </div>
      </div>
    </div>
  );

  /* ── Step 2: Pricing ── */
  const StepPricing = () => (
    <div className="sa-form-panel" style={{ position: "static" }}>
      <div className="sa-fp-header">
        <div className="sa-fp-title">Plan Pricing</div>
        <div className="sa-fp-desc">Define pricing tiers for different currencies and billing intervals.</div>
      </div>
      <div className="sa-fp-body">
        <div style={{ marginBottom: 16, display: "flex", alignItems: "center", justifyContent: "space-between" }}>
          <div style={{ fontSize: 13, fontWeight: 700, color: "var(--sa-text)" }}>Price Tiers</div>
          <SAButton variant="ghost" icon={<Plus size={13} />} onClick={addTier}>Add Price</SAButton>
        </div>
        {priceTiers.map((tier, i) => (
          <div key={i} className="sa-price-row">
            <div className="sa-form-group" style={{ margin: 0 }}>
              {i === 0 && <label className="sa-label">Currency</label>}
              <select className="sa-select" value={tier.currency} onChange={(e) => updateTier(i, "currency", e.target.value)}>
                <option>USD ($)</option><option>GBP (£)</option><option>EUR (€)</option>
                <option>AUD ($)</option><option>CAD ($)</option>
              </select>
            </div>
            <div className="sa-form-group" style={{ margin: 0 }}>
              {i === 0 && <label className="sa-label">Interval</label>}
              <select className="sa-select" value={tier.interval} onChange={(e) => updateTier(i, "interval", e.target.value)}>
                <option>Monthly</option><option>Yearly</option><option>Quarterly</option>
              </select>
            </div>
            <div className="sa-form-group" style={{ margin: 0 }}>
              {i === 0 && <label className="sa-label">Amount</label>}
              <input className="sa-input" type="number" value={tier.amount} placeholder="0.00" onChange={(e) => updateTier(i, "amount", e.target.value)} />
            </div>
            <button className="sa-pr-del" style={{ marginTop: i === 0 ? 22 : 0 }} title="Remove" onClick={() => removeTier(i)}>
              <Trash2 size={14} />
            </button>
          </div>
        ))}
        <div className="sa-price-info">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          You can add multiple price tiers for different currencies and billing intervals. At least one price tier is required.
        </div>
      </div>
      <div className="sa-fp-footer">
        <SAButton variant="ghost" icon={<ArrowLeft size={14} />} onClick={() => setStep(0)}>Previous</SAButton>
        <div className="sa-fp-footer-end">
          <SAButton variant="ghost" icon={<Save size={14} />}>Save Draft</SAButton>
          <SAButton variant="primary" icon={<ArrowRight size={14} />} onClick={() => setStep(2)}>Next: Entitlements</SAButton>
        </div>
      </div>
    </div>
  );

  /* ── Step 3: Entitlements ── */
  const StepEntitlements = () => (
    <div className="sa-form-panel" style={{ position: "static" }}>
      <div className="sa-fp-header">
        <div className="sa-fp-title">Plan Entitlements</div>
        <div className="sa-fp-desc">Select which features and limits are included in this plan.</div>
      </div>
      <div className="sa-fp-body">
        {categories.map((cat) => (
          <div key={cat}>
            <div className="sa-cat-label">{cat}</div>
            <div className="sa-ent-grid">
              {ALL_ENTITLEMENTS.filter((e) => e.category === cat).map((ent) => (
                <div
                  key={ent.id}
                  className={`sa-ent-item${selected.has(ent.id) ? " selected" : ""}`}
                  onClick={() => toggleEnt(ent.id)}
                >
                  <div className="sa-ent-check">
                    {selected.has(ent.id) && (
                      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 13l4 4L19 7" />
                      </svg>
                    )}
                  </div>
                  <div>
                    <div className="sa-ent-name">{ent.name}</div>
                    <div className="sa-ent-type">{ent.type}</div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        ))}
      </div>
      <div className="sa-fp-footer">
        <SAButton variant="ghost" icon={<ArrowLeft size={14} />} onClick={() => setStep(1)}>Previous</SAButton>
        <div className="sa-fp-footer-end">
          <SAButton variant="ghost" icon={<Save size={14} />}>Save Draft</SAButton>
          <SAButton variant="primary" icon={<ArrowRight size={14} />} onClick={() => setStep(3)}>Next: Review</SAButton>
        </div>
      </div>
    </div>
  );

  /* ── Step 4: Review ── */
  const StepReview = () => (
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
          {([
            { label: "Plan Name",    value: details.name,                      mono: false },
            { label: "Slug",         value: details.slug,                      mono: true  },
            { label: "Type",         value: details.planType,                  mono: false },
            { label: "Trial Period", value: `${details.trialDays} days`,       mono: false },
          ] as const).map(({ label, value, mono }) => (
            <div className="sa-rv-row" key={label}>
              <div className="sa-rv-label">{label}</div>
              <div className="sa-rv-value" style={mono ? { fontFamily: "monospace", fontSize: 12 } : {}}>{value}</div>
            </div>
          ))}
          <div className="sa-rv-row">
            <div className="sa-rv-label">Status</div>
            <span className={`sa-rv-tag ${details.isActive ? "tag-green" : "tag-blue"}`}>{details.isActive ? "Active" : "Inactive"}</span>
          </div>
          {details.isFeatured && (
            <div className="sa-rv-row">
              <div className="sa-rv-label">Featured</div>
              <span className="sa-rv-tag tag-orange">Yes — Most Popular</span>
            </div>
          )}
        </div>
        <div className="sa-rv-section">
          <div className="sa-rv-title">
            Pricing
            <button onClick={() => setStep(1)}>Edit</button>
          </div>
          <div className="sa-price-summary">
            {priceTiers.map((tier, i) => (
              <div className="sa-ps-card" key={i}>
                <div className="sa-ps-cur">{tier.currency}</div>
                <div className="sa-ps-amt">
                  {tier.currency.includes("£") ? "£" : tier.currency.includes("€") ? "€" : "$"}{tier.amount || "0.00"}
                </div>
                <div className="sa-ps-int">/ {tier.interval.toLowerCase()}</div>
              </div>
            ))}
          </div>
        </div>
        <div className="sa-rv-section">
          <div className="sa-rv-title">
            Entitlements ({selected.size} selected)
            <button onClick={() => setStep(2)}>Edit</button>
          </div>
          <div className="sa-ent-pills">
            {selectedEnts.map((e) => (
              <div className="sa-ent-pill" key={e.id}>✓ {e.name}</div>
            ))}
          </div>
        </div>
      </div>
      <div className="sa-fp-footer">
        <SAButton variant="ghost" icon={<ArrowLeft size={14} />} onClick={() => setStep(2)}>Previous</SAButton>
        <div className="sa-fp-footer-end">
          <SAButton variant="ghost" icon={<Save size={14} />}>Save as Draft</SAButton>
          <SAButton
            variant="primary"
            icon={<Check size={14} />}
            style={{ background: "var(--sa-green)", boxShadow: "0 2px 8px rgba(43,138,62,.25)" }}
          >
            Publish Plan
          </SAButton>
        </div>
      </div>
    </div>
  );

  return (
    <>
      <SATopbar
        breadcrumb={<>Admin › Billing & Subscriptions › <b>Plan Builder</b></>}
        title="Create New Plan"
        actions={
          <SAButton variant="ghost" icon={<ArrowLeft size={14} />}>
            Back to Plans
          </SAButton>
        }
      />
      <div className="sa-content" style={{ maxWidth: 860 }}>
        <Stepper />
        {step === 0 && <StepDetails />}
        {step === 1 && <StepPricing />}
        {step === 2 && <StepEntitlements />}
        {step === 3 && <StepReview />}
      </div>
    </>
  );
}
