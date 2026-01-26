"use client";

import React, { useMemo } from "react";
import { Input } from "@/components/ui/Input";
import { Select } from "@/components/ui/Select";
import { SectionShell } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/SectionShell";
import type { RepairBuddySettingsDraft, RepairBuddySignatureWorkflowSettings } from "@/app/app/[tenant]/settings/_components/repairbuddy/types";

export function SignatureWorkflowSection({
  draft,
  updateSignatureWorkflow,
}: {
  draft: RepairBuddySettingsDraft;
  updateSignatureWorkflow: (patch: Partial<RepairBuddySettingsDraft["signatureWorkflow"]>) => void;
}) {
  const s = draft.signatureWorkflow;
  const statuses = useMemo(() => draft.jobStatuses.statuses, [draft.jobStatuses.statuses]);

  function updatePickup(patch: Partial<RepairBuddySignatureWorkflowSettings["pickup"]>) {
    updateSignatureWorkflow({ pickup: { ...s.pickup, ...patch } });
  }

  function updateDelivery(patch: Partial<RepairBuddySignatureWorkflowSettings["delivery"]>) {
    updateSignatureWorkflow({ delivery: { ...s.delivery, ...patch } });
  }

  return (
    <SectionShell title="Signature Workflow" description="Pickup and delivery signature automation.">
      <div className="grid gap-6">
        <SignatureBlock
          title="Pickup signature"
          value={s.pickup}
          statuses={statuses}
          onChange={updatePickup}
        />
        <SignatureBlock
          title="Delivery signature"
          value={s.delivery}
          statuses={statuses}
          onChange={updateDelivery}
        />
      </div>
    </SectionShell>
  );
}

function SignatureBlock({
  title,
  value,
  statuses,
  onChange,
}: {
  title: string;
  value: RepairBuddySignatureWorkflowSettings["pickup"];
  statuses: RepairBuddySettingsDraft["jobStatuses"]["statuses"];
  onChange: (patch: Partial<RepairBuddySignatureWorkflowSettings["pickup"]>) => void;
}) {
  return (
    <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-4">
      <div className="text-sm font-semibold text-[var(--rb-text)]">{title}</div>

      <div className="mt-3 grid gap-4 sm:grid-cols-2">
        <label className="sm:col-span-2 flex items-center gap-2 text-sm">
          <input type="checkbox" checked={value.enabled} onChange={(e) => onChange({ enabled: e.target.checked })} />
          Enable
        </label>

        <div className="space-y-1">
          <label className="text-sm font-medium">Trigger status</label>
          <Select value={value.triggerStatusId} onChange={(e) => onChange({ triggerStatusId: e.target.value })}>
            {statuses.map((s) => (
              <option key={s.id} value={s.id}>
                {s.name}
              </option>
            ))}
          </Select>
        </div>

        <div className="space-y-1">
          <label className="text-sm font-medium">Status after submission</label>
          <Select value={value.statusAfterSubmissionId} onChange={(e) => onChange({ statusAfterSubmissionId: e.target.value })}>
            {statuses.map((s) => (
              <option key={s.id} value={s.id}>
                {s.name}
              </option>
            ))}
          </Select>
        </div>

        <div className="sm:col-span-2 space-y-1">
          <label className="text-sm font-medium">Email subject</label>
          <Input value={value.templates.emailSubject} onChange={(e) => onChange({ templates: { ...value.templates, emailSubject: e.target.value } })} />
        </div>

        <div className="sm:col-span-2 space-y-1">
          <label className="text-sm font-medium">Email template</label>
          <textarea
            className="min-h-[140px] w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
            value={value.templates.emailTemplate}
            onChange={(e) => onChange({ templates: { ...value.templates, emailTemplate: e.target.value } })}
          />
        </div>

        <div className="sm:col-span-2 space-y-1">
          <label className="text-sm font-medium">SMS text</label>
          <textarea
            className="min-h-[90px] w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
            value={value.templates.smsText}
            onChange={(e) => onChange({ templates: { ...value.templates, smsText: e.target.value } })}
          />
        </div>
      </div>
    </div>
  );
}
