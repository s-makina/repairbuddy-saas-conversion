"use client";

import React from "react";
import { Input } from "@/components/ui/Input";
import { Select } from "@/components/ui/Select";
import { SectionShell } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/SectionShell";
import type { RepairBuddySettingsDraft, EstimateSignatureWorkflowSettings } from "@/app/app/[tenant]/settings/_components/repairbuddy/types";

// Estimate statuses for signature workflow
const ESTIMATE_STATUSES = [
  { id: "", name: "— Select Status —" },
  { id: "pending", name: "Pending" },
  { id: "approved", name: "Approved" },
  { id: "rejected", name: "Rejected" },
  { id: "converted", name: "Converted" },
];

export function EstimateSignatureWorkflowSection({
  draft,
  updateEstimateSignatureWorkflow,
}: {
  draft: RepairBuddySettingsDraft;
  updateEstimateSignatureWorkflow: (patch: Partial<RepairBuddySettingsDraft["estimateSignatureWorkflow"]>) => void;
}) {
  const s = draft.estimateSignatureWorkflow;

  function updateApproval(patch: Partial<EstimateSignatureWorkflowSettings["approval"]>) {
    updateEstimateSignatureWorkflow({ approval: { ...s.approval, ...patch } });
  }

  function updatePickup(patch: Partial<EstimateSignatureWorkflowSettings["pickup"]>) {
    updateEstimateSignatureWorkflow({ pickup: { ...s.pickup, ...patch } });
  }

  function updateDelivery(patch: Partial<EstimateSignatureWorkflowSettings["delivery"]>) {
    updateEstimateSignatureWorkflow({ delivery: { ...s.delivery, ...patch } });
  }

  return (
    <SectionShell title="Estimate Signature Workflow" description="Approval, pickup, and delivery signature automation for estimates.">
      <div className="grid gap-6">
        <SignatureBlock
          title="Approval signature"
          description="Customer signs to approve the estimate"
          value={s.approval}
          statuses={ESTIMATE_STATUSES}
          onChange={updateApproval}
          defaultOpen={true}
        />
        <SignatureBlock
          title="Pickup signature"
          description="Customer signs when picking up their device"
          value={s.pickup}
          statuses={ESTIMATE_STATUSES}
          onChange={updatePickup}
          defaultOpen={false}
        />
        <SignatureBlock
          title="Delivery signature"
          description="Customer signs when receiving their device"
          value={s.delivery}
          statuses={ESTIMATE_STATUSES}
          onChange={updateDelivery}
          defaultOpen={false}
        />
      </div>
    </SectionShell>
  );
}

function SignatureBlock({
  title,
  description,
  value,
  statuses,
  onChange,
  defaultOpen = true,
}: {
  title: string;
  description?: string;
  value: EstimateSignatureWorkflowSettings["approval"];
  statuses: { id: string; name: string }[];
  onChange: (patch: Partial<EstimateSignatureWorkflowSettings["approval"]>) => void;
  defaultOpen?: boolean;
}) {
  const [isOpen, setIsOpen] = React.useState(defaultOpen);

  return (
    <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white overflow-hidden">
      <button
        type="button"
        className="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50 transition-colors"
        onClick={() => setIsOpen(!isOpen)}
      >
        <div>
          <div className="text-sm font-semibold text-[var(--rb-text)]">{title}</div>
          {description && (
            <div className="text-xs text-[var(--rb-text-2)] mt-0.5">{description}</div>
          )}
        </div>
        <svg
          className={`w-5 h-5 text-[var(--rb-text-3)] transition-transform ${isOpen ? "rotate-180" : ""}`}
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="m19.5 8.25-7.5 7.5-7.5-7.5" />
        </svg>
      </button>

      {isOpen && (
        <div className="p-4 pt-0 border-t border-[var(--rb-border)]">
          <div className="mt-3 grid gap-4 sm:grid-cols-2">
            <label className="sm:col-span-2 flex items-center gap-2 text-sm">
              <input type="checkbox" checked={value.enabled} onChange={(e) => onChange({ enabled: e.target.checked })} />
              Enable
            </label>

            <div className="space-y-1">
              <label className="text-sm font-medium">Trigger status</label>
              <Select value={value.triggerStatus} onChange={(e) => onChange({ triggerStatus: e.target.value })}>
                {statuses.map((s) => (
                  <option key={s.id} value={s.id}>
                    {s.name}
                  </option>
                ))}
              </Select>
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium">Status after submission</label>
              <Select value={value.statusAfterSubmission} onChange={(e) => onChange({ statusAfterSubmission: e.target.value })}>
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
              <p className="text-xs text-[var(--rb-text-3)]">
                Keywords: {"{{approval_signature_url}}"}, {"{{estimate_id}}"}, {"{{case_number}}"}, {"{{customer_full_name}}"}, {"{{estimate_total}}"}, {"{{estimate_items}}"}
              </p>
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
      )}
    </div>
  );
}
