"use client";

import React, { useMemo } from "react";
import { Input } from "@/components/ui/Input";
import { Select } from "@/components/ui/Select";
import { SectionShell } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/SectionShell";
import type { RepairBuddySettingsDraft, RepairBuddySmsGateway } from "@/app/app/[tenant]/settings/_components/repairbuddy/types";

export function SmsSection({
  draft,
  updateSms,
}: {
  draft: RepairBuddySettingsDraft;
  updateSms: (patch: Partial<RepairBuddySettingsDraft["sms"]>) => void;
}) {
  const s = draft.sms;
  const statusOptions = useMemo(() => draft.jobStatuses.statuses, [draft.jobStatuses.statuses]);

  function toggleStatus(id: string) {
    const set = new Set(s.sendWhenStatusChangedToIds);
    if (set.has(id)) set.delete(id);
    else set.add(id);
    updateSms({ sendWhenStatusChangedToIds: Array.from(set) });
  }

  return (
    <SectionShell title="SMS" description="SMS gateway setup and when messages are sent (UI-only).">
      <div className="grid gap-4 sm:grid-cols-2">
        <label className="sm:col-span-2 flex items-center gap-2 text-sm">
          <input
            type="checkbox"
            checked={s.activateSmsForSelectiveStatuses}
            onChange={(e) => updateSms({ activateSmsForSelectiveStatuses: e.target.checked })}
          />
          Activate SMS for selective statuses
        </label>

        <div className="space-y-1">
          <label className="text-sm font-medium">Gateway selection</label>
          <Select value={s.gateway} onChange={(e) => updateSms({ gateway: e.target.value as RepairBuddySmsGateway })}>
            <option value="twilio">Twilio</option>
            <option value="nexmo">Nexmo</option>
            <option value="custom">Custom</option>
          </Select>
        </div>

        <div className="space-y-1">
          <label className="text-sm font-medium">From number</label>
          <Input value={s.gatewayFromNumber} onChange={(e) => updateSms({ gatewayFromNumber: e.target.value })} placeholder="+15551234567" />
        </div>

        <div className="space-y-1">
          <label className="text-sm font-medium">Account SID</label>
          <Input value={s.gatewayAccountSid} onChange={(e) => updateSms({ gatewayAccountSid: e.target.value })} placeholder="(mock)" />
        </div>
        <div className="space-y-1">
          <label className="text-sm font-medium">Auth token</label>
          <Input value={s.gatewayAuthToken} onChange={(e) => updateSms({ gatewayAuthToken: e.target.value })} placeholder="(mock)" />
        </div>

        <div className="sm:col-span-2">
          <div className="text-sm font-semibold text-[var(--rb-text)]">Send when status changed to</div>
          <div className="mt-2 grid gap-2 sm:grid-cols-2">
            {statusOptions.map((st) => (
              <label key={st.id} className="flex items-center gap-2 text-sm">
                <input type="checkbox" checked={s.sendWhenStatusChangedToIds.includes(st.id)} onChange={() => toggleStatus(st.id)} />
                {st.name}
              </label>
            ))}
          </div>
        </div>

        <div className="sm:col-span-2">
          <div className="text-sm font-semibold text-[var(--rb-text)]">Test SMS</div>
          <div className="mt-2 grid gap-3 sm:grid-cols-2">
            <Input value={s.testNumber} onChange={(e) => updateSms({ testNumber: e.target.value })} placeholder="Test number" />
            <Input value={s.testMessage} onChange={(e) => updateSms({ testMessage: e.target.value })} placeholder="Test message" />
          </div>
          <div className="mt-2 text-xs text-zinc-500">Sending is disabled in this phase.</div>
        </div>
      </div>
    </SectionShell>
  );
}
