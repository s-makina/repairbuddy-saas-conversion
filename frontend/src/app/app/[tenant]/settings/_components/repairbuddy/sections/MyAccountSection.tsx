"use client";

import React from "react";
import { Select } from "@/components/ui/Select";
import { SectionShell } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/SectionShell";
import type { RepairBuddySettingsDraft } from "@/app/app/[tenant]/settings/_components/repairbuddy/types";

export function MyAccountSection({
  draft,
  updateMyAccount,
}: {
  draft: RepairBuddySettingsDraft;
  updateMyAccount: (patch: Partial<RepairBuddySettingsDraft["myAccount"]>) => void;
}) {
  const a = draft.myAccount;

  return (
    <SectionShell title="My Account" description="Customer portal toggles and booking form type.">
      <div className="grid gap-4 sm:grid-cols-2">
        <label className="sm:col-span-2 flex items-center gap-2 text-sm">
          <input type="checkbox" checked={a.disableBooking} onChange={(e) => updateMyAccount({ disableBooking: e.target.checked })} />
          Disable booking
        </label>
        <label className="sm:col-span-2 flex items-center gap-2 text-sm">
          <input type="checkbox" checked={a.disableEstimates} onChange={(e) => updateMyAccount({ disableEstimates: e.target.checked })} />
          Disable estimates
        </label>
        <label className="sm:col-span-2 flex items-center gap-2 text-sm">
          <input type="checkbox" checked={a.disableReviews} onChange={(e) => updateMyAccount({ disableReviews: e.target.checked })} />
          Disable reviews
        </label>

        <div className="space-y-1">
          <label className="text-sm font-medium">Booking form type</label>
          <Select value={a.bookingFormType} onChange={(e) => updateMyAccount({ bookingFormType: e.target.value as RepairBuddySettingsDraft["myAccount"]["bookingFormType"] })}>
            <option value="simple">Simple</option>
            <option value="detailed">Detailed</option>
          </Select>
        </div>
      </div>
    </SectionShell>
  );
}
