"use client";

import React from "react";
import { Select } from "@/components/ui/Select";
import { SectionShell } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/SectionShell";
import type { RepairBuddySettingsDraft } from "@/app/app/[tenant]/settings/_components/repairbuddy/types";

export function PagesSetupSection({
  draft,
  updatePagesSetup,
}: {
  draft: RepairBuddySettingsDraft;
  updatePagesSetup: (patch: Partial<RepairBuddySettingsDraft["pagesSetup"]>) => void;
}) {
  const p = draft.pagesSetup;

  const pages = [
    { id: "", name: "Select page (mock)" },
    { id: "dashboard", name: "Dashboard" },
    { id: "status", name: "Status check" },
    { id: "feedback", name: "Feedback" },
    { id: "booking", name: "Booking" },
    { id: "services", name: "Services" },
    { id: "parts", name: "Parts" },
  ];

  return (
    <SectionShell title="Pages Setup" description="Connect RepairBuddy features to pages in your site (mock selects).">
      <div className="grid gap-4 sm:grid-cols-2">
        <div className="space-y-1">
          <label className="text-sm font-medium">Dashboard page</label>
          <Select value={p.dashboardPage} onChange={(e) => updatePagesSetup({ dashboardPage: e.target.value })}>
            {pages.map((x) => (
              <option key={x.id || "_"} value={x.id}>
                {x.name}
              </option>
            ))}
          </Select>
        </div>
        <div className="space-y-1">
          <label className="text-sm font-medium">Status check page</label>
          <Select value={p.statusCheckPage} onChange={(e) => updatePagesSetup({ statusCheckPage: e.target.value })}>
            {pages.map((x) => (
              <option key={x.id || "_"} value={x.id}>
                {x.name}
              </option>
            ))}
          </Select>
        </div>
        <div className="space-y-1">
          <label className="text-sm font-medium">Feedback page</label>
          <Select value={p.feedbackPage} onChange={(e) => updatePagesSetup({ feedbackPage: e.target.value })}>
            {pages.map((x) => (
              <option key={x.id || "_"} value={x.id}>
                {x.name}
              </option>
            ))}
          </Select>
        </div>
        <div className="space-y-1">
          <label className="text-sm font-medium">Booking page</label>
          <Select value={p.bookingPage} onChange={(e) => updatePagesSetup({ bookingPage: e.target.value })}>
            {pages.map((x) => (
              <option key={x.id || "_"} value={x.id}>
                {x.name}
              </option>
            ))}
          </Select>
        </div>
        <div className="space-y-1">
          <label className="text-sm font-medium">Services page</label>
          <Select value={p.servicesPage} onChange={(e) => updatePagesSetup({ servicesPage: e.target.value })}>
            {pages.map((x) => (
              <option key={x.id || "_"} value={x.id}>
                {x.name}
              </option>
            ))}
          </Select>
        </div>
        <div className="space-y-1">
          <label className="text-sm font-medium">Parts page</label>
          <Select value={p.partsPage} onChange={(e) => updatePagesSetup({ partsPage: e.target.value })}>
            {pages.map((x) => (
              <option key={x.id || "_"} value={x.id}>
                {x.name}
              </option>
            ))}
          </Select>
        </div>

        <div className="space-y-1 sm:col-span-2">
          <label className="text-sm font-medium">Redirect after login</label>
          <Select value={p.redirectAfterLogin} onChange={(e) => updatePagesSetup({ redirectAfterLogin: e.target.value })}>
            <option value="">Default</option>
            <option value="dashboard">Dashboard</option>
            <option value="status">Status check</option>
          </Select>
        </div>

        <label className="sm:col-span-2 flex items-center gap-2 text-sm">
          <input type="checkbox" checked={p.enableRegistration} onChange={(e) => updatePagesSetup({ enableRegistration: e.target.checked })} />
          Enable registration
        </label>
      </div>
    </SectionShell>
  );
}
