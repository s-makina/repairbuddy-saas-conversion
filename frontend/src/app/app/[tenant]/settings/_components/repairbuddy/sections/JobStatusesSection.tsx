"use client";

import React, { useMemo, useState } from "react";
import { Button } from "@/components/ui/Button";
import { Input } from "@/components/ui/Input";
import { Modal } from "@/components/ui/Modal";
import { Select } from "@/components/ui/Select";
import { SectionShell } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/SectionShell";
import type { RepairBuddyJobStatus, RepairBuddySettingsDraft } from "@/app/app/[tenant]/settings/_components/repairbuddy/types";

export function JobStatusesSection({
  draft,
  updateJobStatuses,
  setDraft,
  isMock,
}: {
  draft: RepairBuddySettingsDraft;
  updateJobStatuses: (patch: Partial<RepairBuddySettingsDraft["jobStatuses"]>) => void;
  setDraft: React.Dispatch<React.SetStateAction<RepairBuddySettingsDraft>>;
  isMock: boolean;
}) {
  const [addOpen, setAddOpen] = useState(false);

  const statuses = draft.jobStatuses.statuses;
  const statusOptions = useMemo(() => statuses.map((s) => ({ id: s.id, name: s.name })), [statuses]);

  function toggleActive(id: string) {
    setDraft((prev) => ({
      ...prev,
      jobStatuses: {
        ...prev.jobStatuses,
        statuses: prev.jobStatuses.statuses.map((s) => (s.id === id ? { ...s, status: s.status === "active" ? "inactive" : "active" } : s)),
      },
    }));
  }

  return (
    <SectionShell title="Job Statuses" description="Manage job lifecycle statuses. (UI-only; actions disabled)">
      <div className="flex items-center justify-between gap-3">
        <div className="text-sm text-zinc-600">Statuses used in jobs, notifications and reports.</div>
        <div className="inline-flex" onClick={() => setAddOpen(true)}>
          <Button variant="outline" disabled={isMock} className="pointer-events-none">
            Add new status
          </Button>
        </div>
      </div>

      <div className="overflow-x-auto rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white">
        <table className="w-full text-left text-sm">
          <thead className="border-b border-[var(--rb-border)] bg-[var(--rb-surface-muted)]">
            <tr>
              <th className="px-3 py-2 font-medium">ID</th>
              <th className="px-3 py-2 font-medium">Name</th>
              <th className="px-3 py-2 font-medium">Slug</th>
              <th className="px-3 py-2 font-medium">Invoice label</th>
              <th className="px-3 py-2 font-medium">Woo stock</th>
              <th className="px-3 py-2 font-medium">Status</th>
              <th className="px-3 py-2 font-medium">Actions</th>
            </tr>
          </thead>
          <tbody>
            {statuses.map((s) => (
              <tr key={s.id} className="border-b border-[color:color-mix(in_srgb,var(--rb-border),transparent_30%)]">
                <td className="px-3 py-2 text-zinc-600">{s.id}</td>
                <td className="px-3 py-2">{s.name}</td>
                <td className="px-3 py-2 text-zinc-600">{s.slug}</td>
                <td className="px-3 py-2">{s.invoiceLabel}</td>
                <td className="px-3 py-2">{s.manageWooStock ? "Yes" : "No"}</td>
                <td className="px-3 py-2">{s.status}</td>
                <td className="px-3 py-2">
                  <div className="flex items-center gap-2">
                    <Button variant="outline" size="sm" disabled={isMock}>
                      Edit
                    </Button>
                    <Button variant="outline" size="sm" disabled={isMock} onClick={() => toggleActive(s.id)}>
                      Toggle
                    </Button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <div className="grid gap-4 sm:grid-cols-2">
        <div className="space-y-1">
          <label className="text-sm font-medium">Status considered completed</label>
          <Select value={draft.jobStatuses.completedStatusId} onChange={(e) => updateJobStatuses({ completedStatusId: e.target.value })}>
            {statusOptions.map((s) => (
              <option key={s.id} value={s.id}>
                {s.name}
              </option>
            ))}
          </Select>
        </div>
        <div className="space-y-1">
          <label className="text-sm font-medium">Status considered cancelled</label>
          <Select value={draft.jobStatuses.cancelledStatusId} onChange={(e) => updateJobStatuses({ cancelledStatusId: e.target.value })}>
            {statusOptions.map((s) => (
              <option key={s.id} value={s.id}>
                {s.name}
              </option>
            ))}
          </Select>
        </div>
      </div>

      <Modal
        open={addOpen}
        onClose={() => setAddOpen(false)}
        title="Add status"
        footer={
          <div className="flex items-center justify-end gap-2">
            <Button variant="outline" onClick={() => setAddOpen(false)}>
              Close
            </Button>
            <Button disabled={isMock}>Save</Button>
          </div>
        }
      >
        <div className="space-y-3">
          <div className="space-y-1">
            <label className="text-sm font-medium">Name</label>
            <Input value="" onChange={() => {}} placeholder="e.g. Diagnosing" />
          </div>
          <div className="space-y-1">
            <label className="text-sm font-medium">Slug</label>
            <Input value="" onChange={() => {}} placeholder="diagnosing" />
          </div>
          <div className="space-y-1">
            <label className="text-sm font-medium">Description</label>
            <Input value="" onChange={() => {}} placeholder="Optional" />
          </div>
        </div>
      </Modal>
    </SectionShell>
  );
}

export function createJobStatusDraft(): RepairBuddyJobStatus {
  return {
    id: "",
    name: "",
    slug: "",
    description: "",
    invoiceLabel: "",
    manageWooStock: false,
    status: "active",
  };
}
