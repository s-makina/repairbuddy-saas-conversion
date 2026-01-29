"use client";

import React, { useState } from "react";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
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
  const [query, setQuery] = useState("");
  const [pageIndex, setPageIndex] = useState(0);
  const [pageSize, setPageSize] = useState(10);
  const reminders = draft.maintenanceReminders.reminders;

  const filtered = React.useMemo(() => {
    const needle = query.trim().toLowerCase();
    if (!needle) return reminders;
    return reminders.filter((r) => `${r.id} ${r.name} ${r.intervalDays} ${r.status}`.toLowerCase().includes(needle));
  }, [query, reminders]);

  const totalRows = filtered.length;
  const pageRows = React.useMemo(() => {
    const start = pageIndex * pageSize;
    const end = start + pageSize;
    return filtered.slice(start, end);
  }, [filtered, pageIndex, pageSize]);

  const columns = React.useMemo<Array<DataTableColumn<RepairBuddyMaintenanceReminder>>>(
    () => [
      { id: "name", header: "Name", cell: (r) => <div className="text-sm text-zinc-700">{r.name}</div>, className: "min-w-[220px]" },
      { id: "interval", header: "Interval (days)", cell: (r) => <div className="text-sm text-zinc-700">{r.intervalDays}</div>, className: "whitespace-nowrap" },
      { id: "status", header: "Status", cell: (r) => <div className="text-sm text-zinc-700">{r.status}</div>, className: "whitespace-nowrap" },
      {
        id: "actions",
        header: "Actions",
        cell: () => (
          <Button size="sm" variant="outline" disabled={isMock}>
            Edit
          </Button>
        ),
        className: "whitespace-nowrap",
      },
    ],
    [isMock],
  );

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

      <Card className="shadow-none">
        <CardContent className="pt-5">
          <DataTable
            title="Reminders"
            data={pageRows}
            columns={columns}
            getRowId={(row) => row.id}
            emptyMessage="No reminders configured."
            search={{ placeholder: "Search reminders..." }}
            server={{
              query,
              onQueryChange: (value) => {
                setQuery(value);
                setPageIndex(0);
              },
              pageIndex,
              onPageIndexChange: setPageIndex,
              pageSize,
              onPageSizeChange: (value) => {
                setPageSize(value);
                setPageIndex(0);
              },
              totalRows,
            }}
          />
        </CardContent>
      </Card>

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
