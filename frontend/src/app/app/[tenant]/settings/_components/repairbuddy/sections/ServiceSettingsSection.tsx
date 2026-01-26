"use client";

import React from "react";
import { Select } from "@/components/ui/Select";
import { SectionShell } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/SectionShell";
import type { RepairBuddySettingsDraft } from "@/app/app/[tenant]/settings/_components/repairbuddy/types";

export function ServiceSettingsSection({
  draft,
  updateServiceSettings,
}: {
  draft: RepairBuddySettingsDraft;
  updateServiceSettings: (patch: Partial<RepairBuddySettingsDraft["serviceSettings"]>) => void;
}) {
  const s = draft.serviceSettings;

  return (
    <SectionShell title="Service Settings" description="Settings for the public service page and booking behavior.">
      <div className="grid gap-4 sm:grid-cols-2">
        <div className="sm:col-span-2 space-y-1">
          <label className="text-sm font-medium">Sidebar description</label>
          <textarea
            className="min-h-[120px] w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
            value={s.sidebarDescription}
            onChange={(e) => updateServiceSettings({ sidebarDescription: e.target.value })}
          />
        </div>

        <label className="sm:col-span-2 flex items-center gap-2 text-sm">
          <input
            type="checkbox"
            checked={s.disableBookingOnServicePage}
            onChange={(e) => updateServiceSettings({ disableBookingOnServicePage: e.target.checked })}
          />
          Disable booking on service page
        </label>

        <div className="space-y-1">
          <label className="text-sm font-medium">Booking form type</label>
          <Select
            value={s.bookingFormType}
            onChange={(e) => updateServiceSettings({ bookingFormType: e.target.value as RepairBuddySettingsDraft["serviceSettings"]["bookingFormType"] })}
          >
            <option value="simple">Simple</option>
            <option value="detailed">Detailed</option>
          </Select>
        </div>
      </div>
    </SectionShell>
  );
}
