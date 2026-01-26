"use client";

import React from "react";
import { Input } from "@/components/ui/Input";
import { SectionShell } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/SectionShell";
import type { RepairBuddySettingsDraft } from "@/app/app/[tenant]/settings/_components/repairbuddy/types";

export function StylingLabelsSection({
  draft,
  updateStylingLabels,
}: {
  draft: RepairBuddySettingsDraft;
  updateStylingLabels: (patch: Partial<RepairBuddySettingsDraft["stylingLabels"]>) => void;
}) {
  const s = draft.stylingLabels;

  return (
    <SectionShell title="Styling & Labels" description="Customer-facing label text and primary/secondary colors.">
      <div className="grid gap-4 sm:grid-cols-2">
        <div className="sm:col-span-2">
          <div className="text-sm font-semibold text-[var(--rb-text)]">Labels</div>
          <div className="mt-2 grid gap-3 sm:grid-cols-2">
            <Input value={s.labels.delivery} onChange={(e) => updateStylingLabels({ labels: { ...s.labels, delivery: e.target.value } })} placeholder="Delivery" />
            <Input value={s.labels.pickup} onChange={(e) => updateStylingLabels({ labels: { ...s.labels, pickup: e.target.value } })} placeholder="Pickup" />
            <Input value={s.labels.nextService} onChange={(e) => updateStylingLabels({ labels: { ...s.labels, nextService: e.target.value } })} placeholder="Next service" />
            <Input value={s.labels.caseNumber} onChange={(e) => updateStylingLabels({ labels: { ...s.labels, caseNumber: e.target.value } })} placeholder="Case number" />
          </div>
        </div>

        <div className="sm:col-span-2">
          <div className="text-sm font-semibold text-[var(--rb-text)]">Colors</div>
          <div className="mt-2 grid gap-3 sm:grid-cols-2">
            <div>
              <label className="text-xs font-medium text-zinc-600">Primary</label>
              <div className="mt-1 flex items-center gap-3">
                <input
                  type="color"
                  value={s.colors.primary}
                  onChange={(e) => updateStylingLabels({ colors: { ...s.colors, primary: e.target.value } })}
                  className="h-10 w-14 rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white shadow-sm"
                />
                <Input value={s.colors.primary} onChange={(e) => updateStylingLabels({ colors: { ...s.colors, primary: e.target.value } })} />
              </div>
            </div>
            <div>
              <label className="text-xs font-medium text-zinc-600">Secondary</label>
              <div className="mt-1 flex items-center gap-3">
                <input
                  type="color"
                  value={s.colors.secondary}
                  onChange={(e) => updateStylingLabels({ colors: { ...s.colors, secondary: e.target.value } })}
                  className="h-10 w-14 rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white shadow-sm"
                />
                <Input value={s.colors.secondary} onChange={(e) => updateStylingLabels({ colors: { ...s.colors, secondary: e.target.value } })} />
              </div>
            </div>
          </div>
        </div>
      </div>
    </SectionShell>
  );
}
