"use client";

import React from "react";
import { Input } from "@/components/ui/Input";
import { SectionShell } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/SectionShell";
import type { RepairBuddySettingsDraft } from "@/app/app/[tenant]/settings/_components/repairbuddy/types";

export function BookingSection({
  draft,
  updateBooking,
}: {
  draft: RepairBuddySettingsDraft;
  updateBooking: (patch: Partial<RepairBuddySettingsDraft["booking"]>) => void;
}) {
  const b = draft.booking;

  return (
    <SectionShell title="Booking" description="Booking/quote templates and form defaults.">
      <div className="space-y-6">
        <div>
          <div className="text-sm font-semibold text-[var(--rb-text)]">Email templates</div>
          <div className="mt-2 grid gap-4 sm:grid-cols-2">
            <div className="sm:col-span-2 space-y-1">
              <label className="text-sm font-medium">Customer email subject</label>
              <Input value={b.customerEmailSubject} onChange={(e) => updateBooking({ customerEmailSubject: e.target.value })} />
            </div>
            <div className="sm:col-span-2 space-y-1">
              <label className="text-sm font-medium">Customer email body</label>
              <textarea
                className="min-h-[140px] w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={b.customerEmailBody}
                onChange={(e) => updateBooking({ customerEmailBody: e.target.value })}
              />
            </div>

            <div className="sm:col-span-2 space-y-1">
              <label className="text-sm font-medium">Admin email subject</label>
              <Input value={b.adminEmailSubject} onChange={(e) => updateBooking({ adminEmailSubject: e.target.value })} />
            </div>
            <div className="sm:col-span-2 space-y-1">
              <label className="text-sm font-medium">Admin email body</label>
              <textarea
                className="min-h-[140px] w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={b.adminEmailBody}
                onChange={(e) => updateBooking({ adminEmailBody: e.target.value })}
              />
            </div>
          </div>
        </div>

        <div>
          <div className="text-sm font-semibold text-[var(--rb-text)]">Booking workflow</div>
          <div className="mt-2 grid gap-2">
            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" checked={b.sendBookingQuoteToJobs} onChange={(e) => updateBooking({ sendBookingQuoteToJobs: e.target.checked })} />
              Send booking/quote to jobs
            </label>
            <label className="flex items-center gap-2 text-sm">
              <input
                type="checkbox"
                checked={b.turnOffOtherDeviceBrand}
                onChange={(e) => updateBooking({ turnOffOtherDeviceBrand: e.target.checked })}
              />
              Turn off “other device brand”
            </label>
            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" checked={b.turnOffOtherService} onChange={(e) => updateBooking({ turnOffOtherService: e.target.checked })} />
              Turn off “other service”
            </label>
            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" checked={b.turnOffServicePrice} onChange={(e) => updateBooking({ turnOffServicePrice: e.target.checked })} />
              Turn off service price
            </label>
            <label className="flex items-center gap-2 text-sm">
              <input
                type="checkbox"
                checked={b.turnOffIdImeiInBooking}
                onChange={(e) => updateBooking({ turnOffIdImeiInBooking: e.target.checked })}
              />
              Turn off ID/IMEI in booking
            </label>
          </div>
        </div>

        <div>
          <div className="text-sm font-semibold text-[var(--rb-text)]">Defaults</div>
          <div className="mt-2 grid gap-3 sm:grid-cols-2">
            <Input value={b.defaultType} onChange={(e) => updateBooking({ defaultType: e.target.value })} placeholder="Default type" />
            <Input value={b.defaultBrand} onChange={(e) => updateBooking({ defaultBrand: e.target.value })} placeholder="Default brand" />
            <Input value={b.defaultDevice} onChange={(e) => updateBooking({ defaultDevice: e.target.value })} placeholder="Default device" />
          </div>
        </div>
      </div>
    </SectionShell>
  );
}
