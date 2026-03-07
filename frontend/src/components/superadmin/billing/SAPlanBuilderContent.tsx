"use client";

import { SATopbar, SAButton } from "../SATopbar";
import { ArrowLeft, ArrowRight, Check, Save } from "lucide-react";
import { useState } from "react";

/* ── stepper data ── */
const steps = ["Plan Details", "Pricing", "Entitlements", "Review & Launch"];

export function SAPlanBuilderContent() {
  const [activeStep, setActiveStep] = useState(0);
  const [formData, setFormData] = useState({
    name: "Professional Plus",
    slug: "professional-plus",
    sortOrder: "5",
    description:
      "Enhanced professional plan with additional features for growing businesses.",
    planType: "standard",
    trialDays: "14",
    isActive: true,
    isFeatured: false,
    isHidden: false,
  });

  const handleChange = (
    e: React.ChangeEvent<
      HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement
    >
  ) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const toggleField = (field: keyof typeof formData) => {
    setFormData({ ...formData, [field]: !formData[field] });
  };

  return (
    <>
      {/* Topbar */}
      <SATopbar
        breadcrumb="Billing & Subscriptions / Plans"
        title="Plan Builder"
        actions={
          <>
            <SAButton variant="ghost" icon={<ArrowLeft />}>
              Back to Plans
            </SAButton>
            <SAButton variant="primary" icon={<Save />}>
              Save Draft
            </SAButton>
          </>
        }
      />

      {/* Content */}
      <div className="sa-content">
        {/* Stepper */}
        <div
          className="sa-panel"
          style={{ padding: "20px 24px", marginBottom: 20 }}
        >
          <div className="sa-stepper">
            {steps.map((label, i) => (
              <div
                className={`sa-step${i === activeStep ? " active" : ""}${
                  i < activeStep ? " done" : ""
                }`}
                key={label}
                onClick={() => setActiveStep(i)}
                style={{ cursor: "pointer" }}
              >
                <div className="sa-step-num">
                  {i < activeStep ? (
                    <Check style={{ width: 14, height: 14 }} />
                  ) : (
                    i + 1
                  )}
                </div>
                <span className="sa-step-label">{label}</span>
              </div>
            ))}
          </div>
        </div>

        {/* Form panel */}
        <div className="sa-form-panel" style={{ position: "static" }}>
          <div className="sa-fp-header">
            <div className="sa-fp-title">Plan Details</div>
            <div className="sa-fp-desc">
              Basic information about the subscription plan
            </div>
          </div>
          <div className="sa-fp-body">
            {/* Plan Name */}
            <div className="sa-form-group">
              <label className="sa-label">
                Plan Name<span className="sa-req">*</span>
              </label>
              <input
                className="sa-input"
                name="name"
                value={formData.name}
                onChange={handleChange}
              />
              <div className="sa-form-hint">
                Display name shown to customers
              </div>
            </div>

            {/* Slug + Sort Order */}
            <div className="sa-form-row">
              <div className="sa-form-group">
                <label className="sa-label">
                  URL Slug<span className="sa-req">*</span>
                </label>
                <input
                  className="sa-input"
                  name="slug"
                  value={formData.slug}
                  onChange={handleChange}
                />
              </div>
              <div className="sa-form-group">
                <label className="sa-label">Sort Order</label>
                <input
                  className="sa-input"
                  name="sortOrder"
                  type="number"
                  value={formData.sortOrder}
                  onChange={handleChange}
                />
              </div>
            </div>

            {/* Description */}
            <div className="sa-form-group">
              <label className="sa-label">Description</label>
              <textarea
                className="sa-textarea"
                name="description"
                value={formData.description}
                onChange={handleChange}
                rows={3}
              />
            </div>

            {/* Plan Type + Trial */}
            <div className="sa-form-row">
              <div className="sa-form-group">
                <label className="sa-label">Plan Type</label>
                <select
                  className="sa-select"
                  name="planType"
                  value={formData.planType}
                  onChange={handleChange}
                >
                  <option value="standard">Standard</option>
                  <option value="addon">Add-on</option>
                  <option value="legacy">Legacy</option>
                </select>
              </div>
              <div className="sa-form-group">
                <label className="sa-label">Trial Period (Days)</label>
                <input
                  className="sa-input"
                  name="trialDays"
                  type="number"
                  value={formData.trialDays}
                  onChange={handleChange}
                />
                <div className="sa-form-hint">Set 0 for no trial</div>
              </div>
            </div>

            {/* Toggles */}
            <div style={{ marginTop: 20 }}>
              <div className="sa-toggle-wrap">
                <button
                  type="button"
                  className={`sa-toggle${formData.isActive ? " on" : ""}`}
                  onClick={() => toggleField("isActive")}
                />
                <div className="sa-toggle-info">
                  <div className="sa-toggle-label">Active</div>
                  <div className="sa-toggle-desc">
                    Plan is visible and available for purchase
                  </div>
                </div>
              </div>
              <div className="sa-toggle-wrap">
                <button
                  type="button"
                  className={`sa-toggle${formData.isFeatured ? " on" : ""}`}
                  onClick={() => toggleField("isFeatured")}
                />
                <div className="sa-toggle-info">
                  <div className="sa-toggle-label">Featured</div>
                  <div className="sa-toggle-desc">
                    Highlight this plan on the pricing page
                  </div>
                </div>
              </div>
              <div className="sa-toggle-wrap">
                <button
                  type="button"
                  className={`sa-toggle${formData.isHidden ? " on" : ""}`}
                  onClick={() => toggleField("isHidden")}
                />
                <div className="sa-toggle-info">
                  <div className="sa-toggle-label">Hidden</div>
                  <div className="sa-toggle-desc">
                    Plan exists but is not shown in plan listings
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div className="sa-fp-footer">
            <SAButton variant="ghost">Cancel</SAButton>
            <SAButton variant="primary" icon={<ArrowRight />}>
              Next: Pricing
            </SAButton>
          </div>
        </div>
      </div>
    </>
  );
}
