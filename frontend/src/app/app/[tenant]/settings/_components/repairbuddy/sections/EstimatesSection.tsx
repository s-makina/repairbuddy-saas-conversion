"use client";

import React from "react";
import { Input } from "@/components/ui/Input";
import { SectionShell } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/SectionShell";
import type { RepairBuddySettingsDraft } from "@/app/app/[tenant]/settings/_components/repairbuddy/types";

export function EstimatesSection({
  draft,
  updateEstimates,
}: {
  draft: RepairBuddySettingsDraft;
  updateEstimates: (patch: Partial<RepairBuddySettingsDraft["estimates"]>) => void;
}) {
  const e = draft.estimates;

  return (
    <SectionShell title="Estimates" description="Email templates and estimate workflow toggles.">
      <div className="grid gap-4 sm:grid-cols-2">
        <label className="sm:col-span-2 flex items-center gap-2 text-sm">
          <input type="checkbox" checked={e.disableEstimates} onChange={(ev) => updateEstimates({ disableEstimates: ev.target.checked })} />
          Disable estimates
        </label>
        <label className="sm:col-span-2 flex items-center gap-2 text-sm">
          <input
            type="checkbox"
            checked={e.bookingQuoteSendToJobs}
            onChange={(ev) => updateEstimates({ bookingQuoteSendToJobs: ev.target.checked })}
          />
          Booking & quote forms: send to jobs
        </label>

        <div className="sm:col-span-2 space-y-1">
          <label className="text-sm font-medium">Email subject to customer</label>
          <Input value={e.customerEmailSubject} onChange={(ev) => updateEstimates({ customerEmailSubject: ev.target.value })} />
        </div>
        <div className="sm:col-span-2 space-y-1">
          <label className="text-sm font-medium">Email body to customer</label>
          <textarea
            className="min-h-[140px] w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
            value={e.customerEmailBody}
            onChange={(ev) => updateEstimates({ customerEmailBody: ev.target.value })}
          />
        </div>

        <div className="sm:col-span-2 space-y-1">
          <label className="text-sm font-medium">Approve/reject email subject to admin</label>
          <Input value={e.adminApproveRejectEmailSubject} onChange={(ev) => updateEstimates({ adminApproveRejectEmailSubject: ev.target.value })} />
        </div>
        <div className="sm:col-span-2 space-y-1">
          <label className="text-sm font-medium">Approve/reject email body to admin</label>
          <textarea
            className="min-h-[140px] w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
            value={e.adminApproveRejectEmailBody}
            onChange={(ev) => updateEstimates({ adminApproveRejectEmailBody: ev.target.value })}
          />
        </div>
      </div>
    </SectionShell>
  );
}
