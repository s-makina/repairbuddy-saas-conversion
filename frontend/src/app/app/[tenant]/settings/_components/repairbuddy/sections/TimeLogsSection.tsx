"use client";

import React, { useMemo } from "react";
import { Select } from "@/components/ui/Select";
import { SectionShell } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/SectionShell";
import type { RepairBuddySettingsDraft } from "@/app/app/[tenant]/settings/_components/repairbuddy/types";

export function TimeLogsSection({
  draft,
  updateTimeLogs,
}: {
  draft: RepairBuddySettingsDraft;
  updateTimeLogs: (patch: Partial<RepairBuddySettingsDraft["timeLogs"]>) => void;
}) {
  const t = draft.timeLogs;
  const statusOptions = useMemo(() => draft.jobStatuses.statuses, [draft.jobStatuses.statuses]);
  const taxOptions = useMemo(() => draft.taxes.taxes.filter((x) => x.status === "active"), [draft.taxes.taxes]);

  function toggleStatus(id: string) {
    const set = new Set(t.enableTimeLogForStatusIds);
    if (set.has(id)) set.delete(id);
    else set.add(id);
    updateTimeLogs({ enableTimeLogForStatusIds: Array.from(set) });
  }

  return (
    <SectionShell title="Time Logs" description="Time tracking and hourly tax defaults.">
      <div className="space-y-4">
        <label className="flex items-center gap-2 text-sm">
          <input type="checkbox" checked={t.disableTimeLog} onChange={(e) => updateTimeLogs({ disableTimeLog: e.target.checked })} />
          Disable time log
        </label>

        <div className="grid gap-4 sm:grid-cols-2">
          <div className="space-y-1">
            <label className="text-sm font-medium">Default tax for hours</label>
            <Select value={t.defaultTaxIdForHours} onChange={(e) => updateTimeLogs({ defaultTaxIdForHours: e.target.value })}>
              {taxOptions.map((x) => (
                <option key={x.id} value={x.id}>
                  {x.name} ({x.ratePercent}%)
                </option>
              ))}
            </Select>
          </div>
        </div>

        <div>
          <div className="text-sm font-semibold text-[var(--rb-text)]">Enable time log for statuses</div>
          <div className="mt-2 grid gap-2 sm:grid-cols-2">
            {statusOptions.map((s) => (
              <label key={s.id} className="flex items-center gap-2 text-sm">
                <input type="checkbox" checked={t.enableTimeLogForStatusIds.includes(s.id)} onChange={() => toggleStatus(s.id)} />
                {s.name}
              </label>
            ))}
          </div>
        </div>

        <div className="space-y-1">
          <label className="text-sm font-medium">Activities</label>
          <textarea
            className="min-h-[140px] w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
            value={t.activities}
            onChange={(e) => updateTimeLogs({ activities: e.target.value })}
            placeholder="List of activities (one per line)"
          />
        </div>
      </div>
    </SectionShell>
  );
}
