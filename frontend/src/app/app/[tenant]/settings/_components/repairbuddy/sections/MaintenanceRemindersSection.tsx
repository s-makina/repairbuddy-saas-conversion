"use client";

import React, { useState } from "react";
import { Button } from "@/components/ui/Button";
import { Input } from "@/components/ui/Input";
import { Modal } from "@/components/ui/Modal";
import { SectionShell } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/SectionShell";
import type { RepairBuddyMaintenanceReminder, RepairBuddySettingsDraft } from "@/app/app/[tenant]/settings/_components/repairbuddy/types";

export function MaintenanceRemindersSection({
  draft,
  isMock,
}: {
  draft: RepairBuddySettingsDraft;
  isMock: boolean;
}) {
  const [addOpen, setAddOpen] = useState(false);
  const [testOpen, setTestOpen] = useState(false);
  const reminders = draft.maintenanceReminders.reminders;

  return (
    <SectionShell title="Maintenance Reminders" description="Reminder rules and test UI (all actions disabled).">
      <div className="flex flex-wrap items-center justify-end gap-2">
        <div className="inline-flex" onClick={() => setTestOpen(true)}>
          <Button variant="outline" disabled={isMock} className="pointer-events-none">
            Test reminder
          </Button>
        </div>
        <div className="inline-flex" onClick={() => setAddOpen(true)}>
          <Button variant="outline" disabled={isMock} className="pointer-events-none">
            Add reminder
          </Button>
        </div>
      </div>

      <div className="overflow-x-auto rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white">
        <table className="w-full text-left text-sm">
          <thead className="border-b border-[var(--rb-border)] bg-[var(--rb-surface-muted)]">
            <tr>
              <th className="px-3 py-2 font-medium">Name</th>
              <th className="px-3 py-2 font-medium">Interval (days)</th>
              <th className="px-3 py-2 font-medium">Status</th>
              <th className="px-3 py-2 font-medium">Actions</th>
            </tr>
          </thead>
          <tbody>
            {reminders.length === 0 ? (
              <tr>
                <td className="px-3 py-4 text-zinc-500" colSpan={4}>
                  No reminders configured.
                </td>
              </tr>
            ) : (
              reminders.map((r) => (
                <tr key={r.id} className="border-b border-[color:color-mix(in_srgb,var(--rb-border),transparent_30%)]">
                  <td className="px-3 py-2">{r.name}</td>
                  <td className="px-3 py-2">{r.intervalDays}</td>
                  <td className="px-3 py-2">{r.status}</td>
                  <td className="px-3 py-2">
                    <Button size="sm" variant="outline" disabled={isMock}>
                      Edit
                    </Button>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      <Modal
        open={addOpen}
        onClose={() => setAddOpen(false)}
        title="Add reminder"
        footer={
          <div className="flex items-center justify-end gap-2">
            <Button variant="outline" onClick={() => setAddOpen(false)}>
              Close
            </Button>
            <Button disabled={isMock}>Save</Button>
          </div>
        }
      >
        <ReminderForm />
      </Modal>

      <Modal
        open={testOpen}
        onClose={() => setTestOpen(false)}
        title="Test reminder"
        footer={
          <div className="flex items-center justify-end gap-2">
            <Button variant="outline" onClick={() => setTestOpen(false)}>
              Close
            </Button>
            <Button disabled={isMock}>Send test</Button>
          </div>
        }
      >
        <div className="space-y-3">
          <div className="space-y-1">
            <label className="text-sm font-medium">Phone / Email</label>
            <Input value="" onChange={() => {}} placeholder="(mock)" />
          </div>
          <div className="text-xs text-zinc-500">Sending is disabled in this phase.</div>
        </div>
      </Modal>
    </SectionShell>
  );
}

function ReminderForm() {
  const [draft, setDraft] = useState<RepairBuddyMaintenanceReminder>(() => ({
    id: "",
    name: "",
    intervalDays: 180,
    status: "active",
  }));

  return (
    <div className="space-y-3">
      <div className="space-y-1">
        <label className="text-sm font-medium">Name</label>
        <Input value={draft.name} onChange={(e) => setDraft((p) => ({ ...p, name: e.target.value }))} placeholder="e.g. 6-month service" />
      </div>
      <div className="space-y-1">
        <label className="text-sm font-medium">Interval (days)</label>
        <Input
          type="number"
          min={0}
          value={String(draft.intervalDays)}
          onChange={(e) => setDraft((p) => ({ ...p, intervalDays: Number(e.target.value || 0) }))}
        />
      </div>
    </div>
  );
}
