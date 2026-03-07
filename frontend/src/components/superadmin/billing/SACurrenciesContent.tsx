"use client";

import { SATopbar, SAButton } from "../SATopbar";
import { Plus, Download, Search, Pencil, Trash2 } from "lucide-react";
import { useState } from "react";

/* ── static data ── */
interface Currency {
  name: string;
  code: string;
  symbol: string;
  decimals: number;
  status: "active" | "inactive";
}

const currencies: Currency[] = [
  { name: "US Dollar", code: "USD", symbol: "$", decimals: 2, status: "active" },
  {
    name: "British Pound",
    code: "GBP",
    symbol: "£",
    decimals: 2,
    status: "active",
  },
  { name: "Euro", code: "EUR", symbol: "€", decimals: 2, status: "active" },
];

export function SACurrenciesContent() {
  const [form, setForm] = useState({
    name: "",
    code: "",
    symbol: "",
    decimals: "2",
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
        title="Currencies"
        actions={
          <>
            <SAButton variant="ghost" icon={<Download />}>
              Export
            </SAButton>
            <SAButton variant="primary" icon={<Plus />}>
              Add Currency
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
                <div className="sa-ph-t">All Currencies</div>
                <div className="sa-ph-s">
                  {currencies.length} currencies configured
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
                  <th>Currency</th>
                  <th>Code</th>
                  <th>Symbol</th>
                  <th>Decimals</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {currencies.map((c) => (
                  <tr key={c.code}>
                    <td className="sa-td-name">{c.name}</td>
                    <td>
                      <span className="sa-td-code">{c.code}</span>
                    </td>
                    <td style={{ fontSize: 16, fontWeight: 600 }}>
                      {c.symbol}
                    </td>
                    <td>{c.decimals}</td>
                    <td>
                      <span
                        className={`sa-badge ${
                          c.status === "active" ? "sa-badge-g" : ""
                        }`}
                      >
                        {c.status === "active" ? "Active" : "Inactive"}
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
              <div className="sa-fp-title">Add Currency</div>
              <div className="sa-fp-desc">
                Configure a new currency for billing
              </div>
            </div>
            <div className="sa-fp-body">
              <div className="sa-form-group">
                <label className="sa-label">
                  Currency Name<span className="sa-req">*</span>
                </label>
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
                  <label className="sa-label">
                    ISO Code<span className="sa-req">*</span>
                  </label>
                  <input
                    className="sa-input"
                    name="code"
                    value={form.code}
                    onChange={handleChange}
                    placeholder="e.g., USD"
                  />
                  <div className="sa-form-hint">3-letter ISO 4217 code</div>
                </div>
                <div className="sa-form-group">
                  <label className="sa-label">
                    Symbol<span className="sa-req">*</span>
                  </label>
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
                <label className="sa-label">Decimal Places</label>
                <input
                  className="sa-input"
                  name="decimals"
                  type="number"
                  value={form.decimals}
                  onChange={handleChange}
                />
                <div className="sa-form-hint">
                  Number of decimal places for this currency
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
                Add Currency
              </SAButton>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
