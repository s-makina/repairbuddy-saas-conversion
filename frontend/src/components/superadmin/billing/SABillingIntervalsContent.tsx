"use client";

import { SATopbar, SAButton } from "../SATopbar";
import { Plus, Download, Search, Pencil, Trash2 } from "lucide-react";
import { useState } from "react";

/* ── static data ── */
interface Interval {
  name: string;
  unit: string;
  duration: number;
  status: "active" | "inactive";
  plans: number;
}

const intervals: Interval[] = [
  { name: "Monthly", unit: "Month", duration: 1, status: "active", plans: 4 },
  {
    name: "Quarterly",
    unit: "Month",
    duration: 3,
    status: "active",
    plans: 3,
  },
  {
    name: "Semi-Annually",
    unit: "Month",
    duration: 6,
    status: "active",
    plans: 2,
  },
  { name: "Yearly", unit: "Year", duration: 1, status: "active", plans: 4 },
];

export function SABillingIntervalsContent() {
  const [form, setForm] = useState({
    name: "",
    unit: "month",
    duration: "1",
    isActive: true,
  });

  const handleChange = (
    e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>
  ) => {
    setForm({ ...form, [e.target.name]: e.target.value });
  };

  return (
    <>
      {/* Topbar */}
      <SATopbar
        breadcrumb="Billing & Subscriptions"
        title="Billing Intervals"
        actions={
          <>
            <SAButton variant="ghost" icon={<Download />}>
              Export
            </SAButton>
            <SAButton variant="primary" icon={<Plus />}>
              Add Interval
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
                <div className="sa-ph-t">All Intervals</div>
                <div className="sa-ph-s">
                  {intervals.length} intervals configured
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
                  <th>Period Unit</th>
                  <th>Duration</th>
                  <th>Plans</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {intervals.map((iv) => (
                  <tr key={iv.name}>
                    <td className="sa-td-name">{iv.name}</td>
                    <td>
                      <span className="sa-td-code">{iv.unit}</span>
                    </td>
                    <td>{iv.duration}</td>
                    <td>
                      <span className="sa-badge sa-badge-b">
                        {iv.plans} plans
                      </span>
                    </td>
                    <td>
                      <span
                        className={`sa-badge ${
                          iv.status === "active" ? "sa-badge-g" : ""
                        }`}
                      >
                        {iv.status === "active" ? "Active" : "Inactive"}
                      </span>
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
              <div className="sa-fp-title">Add Interval</div>
              <div className="sa-fp-desc">
                Configure a new billing interval
              </div>
            </div>
            <div className="sa-fp-body">
              <div className="sa-form-group">
                <label className="sa-label">
                  Interval Name<span className="sa-req">*</span>
                </label>
                <input
                  className="sa-input"
                  name="name"
                  value={form.name}
                  onChange={handleChange}
                  placeholder="e.g., Quarterly"
                />
              </div>
              <div className="sa-form-row">
                <div className="sa-form-group">
                  <label className="sa-label">
                    Period Unit<span className="sa-req">*</span>
                  </label>
                  <select
                    className="sa-select"
                    name="unit"
                    value={form.unit}
                    onChange={handleChange}
                  >
                    <option value="day">Day</option>
                    <option value="week">Week</option>
                    <option value="month">Month</option>
                    <option value="year">Year</option>
                  </select>
                </div>
                <div className="sa-form-group">
                  <label className="sa-label">
                    Duration<span className="sa-req">*</span>
                  </label>
                  <input
                    className="sa-input"
                    name="duration"
                    type="number"
                    value={form.duration}
                    onChange={handleChange}
                  />
                  <div className="sa-form-hint">
                    Number of period units per billing cycle
                  </div>
                </div>
              </div>
              <div className="sa-toggle-wrap compact">
                <button
                  type="button"
                  className={`sa-toggle${form.isActive ? " on" : ""}`}
                  onClick={() =>
                    setForm({ ...form, isActive: !form.isActive })
                  }
                />
                <div className="sa-toggle-info">
                  <div className="sa-toggle-label">Active</div>
                </div>
              </div>
            </div>
            <div className="sa-fp-footer">
              <SAButton variant="ghost">Reset</SAButton>
              <SAButton variant="primary" icon={<Plus />}>
                Add Interval
              </SAButton>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
