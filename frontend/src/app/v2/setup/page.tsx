"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import React, { useState } from "react";
import { useAuth } from "@/lib/auth";

const WrenchIcon = () => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
    <path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z" />
  </svg>
);

const TIME_OPTIONS = [
  "6:00 AM", "7:00 AM", "8:00 AM", "9:00 AM", "10:00 AM", "11:00 AM",
  "12:00 PM", "1:00 PM", "2:00 PM", "3:00 PM", "4:00 PM", "5:00 PM",
  "6:00 PM", "7:00 PM", "8:00 PM", "9:00 PM",
];

const DAYS = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];

interface DayHours {
  open: boolean;
  from: string;
  to: string;
}

interface Service {
  id: number;
  name: string;
  price: string;
  duration: string;
}

const DEFAULT_HOURS: DayHours[] = [
  { open: true,  from: "9:00 AM", to: "6:00 PM" },
  { open: true,  from: "9:00 AM", to: "6:00 PM" },
  { open: true,  from: "9:00 AM", to: "6:00 PM" },
  { open: true,  from: "9:00 AM", to: "6:00 PM" },
  { open: true,  from: "9:00 AM", to: "5:00 PM" },
  { open: true,  from: "10:00 AM", to: "3:00 PM" },
  { open: false, from: "9:00 AM", to: "5:00 PM" },
];

const DEFAULT_SERVICES: Service[] = [
  { id: 1, name: "Screen Replacement", price: "$89.00", duration: "45 min" },
  { id: 2, name: "Battery Replacement", price: "$49.00", duration: "30 min" },
  { id: 3, name: "Diagnostic Check",    price: "$29.00", duration: "20 min" },
  { id: 4, name: "Water Damage Repair", price: "$129.00", duration: "2 hrs" },
];

// ─── Step 1: Business Info ───────────────────────────────────────────────────

interface BusinessInfo {
  businessName: string;
  industry: string;
  phone: string;
  address: string;
  country: string;
  timezone: string;
}

function StepBusinessInfo({
  info,
  onChange,
}: {
  info: BusinessInfo;
  onChange: (info: BusinessInfo) => void;
}) {
  function set(field: keyof BusinessInfo, value: string) {
    onChange({ ...info, [field]: value });
  }

  return (
    <>
      <div className="wc-header">
        <h2>Tell us about your business</h2>
        <p>This information helps us set up your workspace and customise your experience.</p>
      </div>
      <div className="wc-body">
        <div className="form-grid">
          <div className="form-group full">
            <label className="form-label">Business Name</label>
            <div className="input-wrap">
              <svg className="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
              <input
                type="text"
                className="form-input"
                placeholder="e.g. QuickFix Electronics"
                value={info.businessName}
                onChange={(e) => set("businessName", e.target.value)}
              />
            </div>
          </div>

          <div className="form-group">
            <label className="form-label">Industry</label>
            <div className="input-wrap">
              <svg className="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                  d="M21 13.255A23.193 23.193 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
              <select
                className="form-select"
                value={info.industry}
                onChange={(e) => set("industry", e.target.value)}
              >
                <option>Electronics Repair</option>
                <option>Phone &amp; Tablet Repair</option>
                <option>Computer Repair</option>
                <option>Auto Repair</option>
                <option>Appliance Repair</option>
                <option>Watch &amp; Jewelry Repair</option>
                <option>General Repairs</option>
                <option>Other</option>
              </select>
            </div>
          </div>

          <div className="form-group">
            <label className="form-label">Phone Number</label>
            <div className="input-wrap">
              <svg className="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                  d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
              </svg>
              <input
                type="tel"
                className="form-input"
                placeholder="+1 (555) 000-0000"
                value={info.phone}
                onChange={(e) => set("phone", e.target.value)}
              />
            </div>
          </div>

          <div className="form-group full">
            <label className="form-label">Business Address</label>
            <div className="input-wrap">
              <svg className="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <input
                type="text"
                className="form-input"
                placeholder="123 Main St, City, State, ZIP"
                value={info.address}
                onChange={(e) => set("address", e.target.value)}
              />
            </div>
          </div>

          <div className="form-group">
            <label className="form-label">Country</label>
            <div className="input-wrap">
              <svg className="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                  d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <select
                className="form-select"
                value={info.country}
                onChange={(e) => set("country", e.target.value)}
              >
                <option>United States</option>
                <option>Canada</option>
                <option>United Kingdom</option>
                <option>Australia</option>
                <option>Other</option>
              </select>
            </div>
          </div>

          <div className="form-group">
            <label className="form-label">Timezone</label>
            <div className="input-wrap">
              <svg className="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <select
                className="form-select"
                value={info.timezone}
                onChange={(e) => set("timezone", e.target.value)}
              >
                <option>Pacific Time (PT)</option>
                <option>Mountain Time (MT)</option>
                <option>Central Time (CT)</option>
                <option>Eastern Time (ET)</option>
                <option>GMT</option>
                <option>CET</option>
              </select>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}

// ─── Step 2: Working Hours ───────────────────────────────────────────────────

function StepWorkingHours({
  hours,
  onChange,
}: {
  hours: DayHours[];
  onChange: (hours: DayHours[]) => void;
}) {
  function toggleDay(i: number) {
    const next = hours.map((h, idx) => (idx === i ? { ...h, open: !h.open } : h));
    onChange(next);
  }
  function setFrom(i: number, from: string) {
    const next = hours.map((h, idx) => (idx === i ? { ...h, from } : h));
    onChange(next);
  }
  function setTo(i: number, to: string) {
    const next = hours.map((h, idx) => (idx === i ? { ...h, to } : h));
    onChange(next);
  }

  return (
    <>
      <div className="wc-header">
        <h2>Set your working hours</h2>
        <p>Define when your shop is open. Customers will only be able to book during these hours.</p>
      </div>
      <div className="wc-body">
        <div className="hours-table">
          {DAYS.map((day, i) => (
            <div className="hour-row" key={day}>
              <button
                className={`hour-toggle${hours[i].open ? " on" : ""}`}
                onClick={() => toggleDay(i)}
                aria-pressed={hours[i].open}
                aria-label={`Toggle ${day}`}
              />
              <div className="hour-day">{day}</div>
              <div className="hour-times">
                {hours[i].open ? (
                  <>
                    <select
                      className="hour-select"
                      value={hours[i].from}
                      onChange={(e) => setFrom(i, e.target.value)}
                    >
                      {TIME_OPTIONS.map((t) => <option key={t}>{t}</option>)}
                    </select>
                    <span style={{ fontSize: 12, color: "var(--text-3)" }}>to</span>
                    <select
                      className="hour-select"
                      value={hours[i].to}
                      onChange={(e) => setTo(i, e.target.value)}
                    >
                      {TIME_OPTIONS.map((t) => <option key={t}>{t}</option>)}
                    </select>
                  </>
                ) : (
                  <span className="hour-closed">Closed</span>
                )}
              </div>
            </div>
          ))}
        </div>
      </div>
    </>
  );
}

// ─── Step 3: Services ────────────────────────────────────────────────────────

let nextServiceId = 10;

function StepServices({
  services,
  onChange,
}: {
  services: Service[];
  onChange: (services: Service[]) => void;
}) {
  function updateService(id: number, field: keyof Service, value: string) {
    onChange(services.map((s) => (s.id === id ? { ...s, [field]: value } : s)));
  }
  function removeService(id: number) {
    onChange(services.filter((s) => s.id !== id));
  }
  function addService() {
    onChange([...services, { id: nextServiceId++, name: "", price: "", duration: "" }]);
  }

  return (
    <>
      <div className="wc-header">
        <h2>Add your services</h2>
        <p>List the repair services you offer. You can always add more later.</p>
      </div>
      <div className="wc-body">
        <div className="services-header">
          <div>Service Name</div>
          <div>Price</div>
          <div>Duration</div>
          <div style={{ width: 32 }} />
        </div>
        <div className="service-list">
          {services.map((svc) => (
            <div className="service-item" key={svc.id}>
              <input
                type="text"
                className="service-input"
                placeholder="Service name"
                value={svc.name}
                onChange={(e) => updateService(svc.id, "name", e.target.value)}
              />
              <input
                type="text"
                className="service-input"
                placeholder="$0.00"
                value={svc.price}
                onChange={(e) => updateService(svc.id, "price", e.target.value)}
              />
              <input
                type="text"
                className="service-input"
                placeholder="e.g. 1hr"
                value={svc.duration}
                onChange={(e) => updateService(svc.id, "duration", e.target.value)}
              />
              <button
                className="remove-btn"
                onClick={() => removeService(svc.id)}
                aria-label="Remove service"
              >
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                    d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          ))}
        </div>
        <button className="add-service-btn" onClick={addService}>
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" />
          </svg>
          Add Another Service
        </button>
      </div>
    </>
  );
}

// ─── Step 4: Review & Launch ─────────────────────────────────────────────────

function StepReview({
  info,
  hours,
  services,
  onGoToStep,
  onLaunch,
  launching,
}: {
  info: BusinessInfo;
  hours: DayHours[];
  services: Service[];
  onGoToStep: (n: number) => void;
  onLaunch: () => void;
  launching: boolean;
}) {
  // Build a concise hours summary
  const openDays = DAYS.map((d, i) => ({ day: d, ...hours[i] })).filter((d) => d.open);
  const mondayFriday = openDays
    .filter((d) => ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"].includes(d.day))
    .every((d) => d.from === openDays[0]?.from && d.to === openDays[0]?.to);

  const hourRows: { label: string; value: string; closed?: boolean }[] = [];
  if (mondayFriday && openDays.some((d) => d.day === "Monday")) {
    hourRows.push({
      label: "Mon – Fri",
      value: `${openDays[0].from} – ${openDays[0].to}`,
    });
  } else {
    openDays
      .filter((d) => ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"].includes(d.day))
      .forEach((d) => hourRows.push({ label: d.day, value: `${d.from} – ${d.to}` }));
  }
  if (hours[5].open) {
    hourRows.push({ label: "Saturday", value: `${hours[5].from} – ${hours[5].to}` });
  } else {
    hourRows.push({ label: "Saturday", value: "Closed", closed: true });
  }
  if (hours[6].open) {
    hourRows.push({ label: "Sunday", value: `${hours[6].from} – ${hours[6].to}` });
  } else {
    hourRows.push({ label: "Sunday", value: "Closed", closed: true });
  }

  return (
    <>
      <div className="wc-header">
        <h2>Review &amp; launch</h2>
        <p>Everything looks good? Hit launch to start using 99SmartX!</p>
      </div>
      <div className="wc-body">
        <div className="review-sections">
          {/* Business Info */}
          <div className="review-block">
            <div className="review-block-title">
              <span>📋 Business Information</span>
              <a href="#" onClick={(e) => { e.preventDefault(); onGoToStep(1); }}>Edit</a>
            </div>
            {[
              { label: "Business Name", value: info.businessName || "—" },
              { label: "Industry",       value: info.industry },
              { label: "Phone",          value: info.phone || "—" },
              { label: "Address",        value: info.address || "—" },
              { label: "Timezone",       value: info.timezone },
            ].map((r) => (
              <div className="review-row" key={r.label}>
                <span className="review-label">{r.label}</span>
                <span className="review-value">{r.value}</span>
              </div>
            ))}
          </div>

          {/* Working Hours */}
          <div className="review-block">
            <div className="review-block-title">
              <span>🕐 Working Hours</span>
              <a href="#" onClick={(e) => { e.preventDefault(); onGoToStep(2); }}>Edit</a>
            </div>
            {hourRows.map((r) => (
              <div className="review-row" key={r.label}>
                <span className="review-label">{r.label}</span>
                <span className="review-value" style={r.closed ? { color: "var(--red)" } : undefined}>
                  {r.value}
                </span>
              </div>
            ))}
          </div>

          {/* Services */}
          <div className="review-block">
            <div className="review-block-title">
              <span>🔧 Services ({services.length})</span>
              <a href="#" onClick={(e) => { e.preventDefault(); onGoToStep(3); }}>Edit</a>
            </div>
            {services.map((s) => (
              <div className="review-row" key={s.id}>
                <span className="review-label">{s.name || "Unnamed service"}</span>
                <span className="review-value">
                  {[s.price, s.duration].filter(Boolean).join(" · ") || "—"}
                </span>
              </div>
            ))}
            {services.length === 0 && (
              <div className="review-row">
                <span className="review-label" style={{ fontStyle: "italic" }}>No services added</span>
              </div>
            )}
          </div>

          {/* Plan */}
          <div className="review-block" style={{ background: "var(--orange-bg)", borderColor: "rgba(232,89,12,.15)" }}>
            <div className="review-block-title">
              <span>⭐ Your Plan</span>
            </div>
            <div className="review-row">
              <span className="review-label">Plan</span>
              <span className="review-value" style={{ color: "var(--orange)", fontWeight: 700 }}>Professional</span>
            </div>
            <div className="review-row">
              <span className="review-label">Billing</span>
              <span className="review-value">$79.00/month</span>
            </div>
            <div className="review-row">
              <span className="review-label">Trial</span>
              <span className="review-value" style={{ color: "var(--green)", fontWeight: 700 }}>14 days free</span>
            </div>
          </div>

          {/* Launch */}
          <div className="launch-section">
            <p>🎉 Your workspace is ready. Launch it and start managing your repair shop like a pro with 99SmartX!</p>
            <button className="btn btn-success" onClick={onLaunch} disabled={launching}>
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style={{ width: 18, height: 18 }}>
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                  d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
              {launching ? "Launching…" : "Launch Your Business"}
            </button>
          </div>
        </div>
      </div>
    </>
  );
}

// ─── Main Component ───────────────────────────────────────────────────────────

const STEP_LABELS = ["Business Info", "Working Hours", "Services", "Review & Launch"];
const TOTAL_STEPS = 4;

export default function BusinessSetupPage() {
  const { user } = useAuth();
  const router = useRouter();

  const [step, setStep] = useState(1);
  const [launching, setLaunching] = useState(false);

  const [info, setInfo] = useState<BusinessInfo>({
    businessName: "",
    industry: "Electronics Repair",
    phone: "",
    address: "",
    country: "United States",
    timezone: "Eastern Time (ET)",
  });
  const [hours, setHours] = useState<DayHours[]>(DEFAULT_HOURS);
  const [services, setServices] = useState<Service[]>(DEFAULT_SERVICES);

  function goToStep(n: number) {
    if (n >= 1 && n <= TOTAL_STEPS) setStep(n);
  }

  async function handleLaunch() {
    setLaunching(true);
    // In production, POST setup data to the API here.
    await new Promise((r) => setTimeout(r, 800));
    router.replace("/");
  }

  // Derive initials for avatar
  const displayName = user?.name ?? "User";
  const initials = displayName
    .split(" ")
    .map((w: string) => w[0])
    .slice(0, 2)
    .join("")
    .toUpperCase();

  return (
    <div style={{ minHeight: "100vh", display: "flex", flexDirection: "column" }}>
      {/* TOPBAR */}
      <div className="topbar">
        <Link href="/v2" className="nav-brand">
          <div className="logo-mark"><WrenchIcon /></div>
          <span className="brand-name">99SmartX</span>
        </Link>
        <div className="tb-right">
          <Link href="/" className="skip-link">Skip for now →</Link>
          <div className="tb-user">
            <div className="tb-avatar">{initials}</div>
            {displayName}
          </div>
        </div>
      </div>

      {/* MAIN */}
      <div className="setup-main">
        {/* PROGRESS */}
        <div className="progress-section">
          <div className="progress-steps">
            {STEP_LABELS.map((label, idx) => {
              const n = idx + 1;
              const isActive = n === step;
              const isDone = n < step;
              return (
                <div
                  key={n}
                  className={`p-step${isActive ? " active" : ""}${isDone ? " done" : ""}`}
                  onClick={() => isDone && goToStep(n)}
                >
                  <div className="p-num">
                    {isDone ? (
                      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style={{ width: 16, height: 16 }}>
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="3" d="M5 13l4 4L19 7" />
                      </svg>
                    ) : n}
                  </div>
                  <span className="p-label">{label}</span>
                </div>
              );
            })}
          </div>
        </div>

        {/* WIZARD CARD */}
        <div className="wizard-card">
          {/* STEP PANELS */}
          <div className={`step-panel${step === 1 ? " active" : ""}`}>
            <StepBusinessInfo info={info} onChange={setInfo} />
          </div>
          <div className={`step-panel${step === 2 ? " active" : ""}`}>
            <StepWorkingHours hours={hours} onChange={setHours} />
          </div>
          <div className={`step-panel${step === 3 ? " active" : ""}`}>
            <StepServices services={services} onChange={setServices} />
          </div>
          <div className={`step-panel${step === 4 ? " active" : ""}`}>
            <StepReview
              info={info}
              hours={hours}
              services={services}
              onGoToStep={goToStep}
              onLaunch={handleLaunch}
              launching={launching}
            />
          </div>

          {/* FOOTER NAV */}
          <div className="wc-footer">
            <button
              className="btn btn-ghost"
              onClick={() => goToStep(step - 1)}
              style={{ visibility: step === 1 ? "hidden" : "visible" }}
            >
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 19l-7-7 7-7" />
              </svg>
              Previous
            </button>
            <div style={{ fontSize: 12, color: "var(--text-3)" }}>
              Step {step} of {TOTAL_STEPS}
            </div>
            {step < TOTAL_STEPS ? (
              <button className="btn btn-primary" onClick={() => goToStep(step + 1)}>
                Next Step
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7" />
                </svg>
              </button>
            ) : (
              <div style={{ width: 120 }} />
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
