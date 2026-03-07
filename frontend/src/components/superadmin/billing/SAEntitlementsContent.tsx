"use client";

import { SATopbar, SAButton } from "../SATopbar";
import { Plus, Download, Search, Pencil, Trash2 } from "lucide-react";
import { useState } from "react";

/* ── static data ── */
interface Entitlement {
  name: string;
  code: string;
  type: string;
  default: string;
  plans: number;
}

const entitlements: Entitlement[] = [
  {
    name: "Max Employees",
    code: "max_employees",
    type: "Integer",
    default: "5",
    plans: 4,
  },
  {
    name: "Inventory Module",
    code: "feature_inventory",
    type: "Boolean",
    default: "true",
    plans: 3,
  },
  {
    name: "POS Feature",
    code: "feature_pos",
    type: "Boolean",
    default: "false",
    plans: 2,
  },
  {
    name: "Storage Limit (GB)",
    code: "storage_limit_gb",
    type: "Integer",
    default: "10",
    plans: 4,
  },
  {
    name: "White Label",
    code: "feature_whitelabel",
    type: "Boolean",
    default: "false",
    plans: 1,
  },
  {
    name: "API Access",
    code: "feature_api",
    type: "Boolean",
    default: "false",
    plans: 2,
  },
  {
    name: "Appointments Module",
    code: "feature_appointments",
    type: "Boolean",
    default: "true",
    plans: 3,
  },
  {
    name: "Custom Config",
    code: "custom_config",
    type: "JSON",
    default: "{}",
    plans: 1,
  },
];

export function SAEntitlementsContent() {
  const [form, setForm] = useState({
    name: "",
    code: "",
    type: "boolean",
    default: "",
    description: "",
  });

  const handleChange = (
    e: React.ChangeEvent<
      HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement
    >
  ) => {
    setForm({ ...form, [e.target.name]: e.target.value });
  };

  return (
    <>
      {/* Topbar */}
      <SATopbar
        breadcrumb="Billing & Subscriptions"
        title="Entitlements"
        actions={
          <>
            <SAButton variant="ghost" icon={<Download />}>
              Export
            </SAButton>
            <SAButton variant="primary" icon={<Plus />}>
              Add Entitlement
            </SAButton>
          </>
        }
      />

      {/* Content */}
      <div className="sa-content">
        <div className="sa-split">
          {/* Left — table */}
          <div className="sa-panel">
            <div className="sa-ph">
              <div>
                <div className="sa-ph-t">All Entitlements</div>
                <div className="sa-ph-s">
                  {entitlements.length} entitlements configured
                </div>
              </div>
              <div className="sa-ph-search">
                <Search />
                <input placeholder="Search..." />
              </div>
            </div>
            <table className="sa-dt">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Code</th>
                  <th>Type</th>
                  <th>Default</th>
                  <th>Plans</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {entitlements.map((e) => (
                  <tr key={e.code}>
                    <td className="sa-td-name">{e.name}</td>
                    <td>
                      <span className="sa-td-code">{e.code}</span>
                    </td>
                    <td>
                      <span className="sa-badge">{e.type}</span>
                    </td>
                    <td>{e.default}</td>
                    <td>
                      <span className="sa-badge sa-badge-b">{e.plans} plans</span>
                    </td>
                    <td>
                      <div className="sa-td-actions">
                        <button className="sa-act-btn" title="Edit">
                          <Pencil />
                        </button>
                        <button className="sa-act-btn del" title="Delete">
                          <Trash2 />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {/* Right — add form */}
          <div className="sa-form-panel">
            <div className="sa-fp-header">
              <div className="sa-fp-title">Add Entitlement</div>
              <div className="sa-fp-desc">
                Define a new feature or limit for plans
              </div>
            </div>
            <div className="sa-fp-body">
              <div className="sa-form-group">
                <label className="sa-label">
                  Name<span className="sa-req">*</span>
                </label>
                <input
                  className="sa-input"
                  name="name"
                  value={form.name}
                  onChange={handleChange}
                  placeholder="e.g., Max Users"
                />
              </div>
              <div className="sa-form-group">
                <label className="sa-label">
                  Code<span className="sa-req">*</span>
                </label>
                <input
                  className="sa-input"
                  name="code"
                  value={form.code}
                  onChange={handleChange}
                  placeholder="e.g., max_users"
                />
                <div className="sa-form-hint">
                  Unique identifier, use snake_case
                </div>
              </div>
              <div className="sa-form-row">
                <div className="sa-form-group">
                  <label className="sa-label">Type</label>
                  <select
                    className="sa-select"
                    name="type"
                    value={form.type}
                    onChange={handleChange}
                  >
                    <option value="boolean">Boolean</option>
                    <option value="integer">Integer</option>
                    <option value="json">JSON</option>
                  </select>
                </div>
                <div className="sa-form-group">
                  <label className="sa-label">Default Value</label>
                  <input
                    className="sa-input"
                    name="default"
                    value={form.default}
                    onChange={handleChange}
                    placeholder="e.g., true"
                  />
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
            </div>
            <div className="sa-fp-footer">
              <SAButton variant="ghost">Reset</SAButton>
              <SAButton variant="primary" icon={<Plus />}>
                Add Entitlement
              </SAButton>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
