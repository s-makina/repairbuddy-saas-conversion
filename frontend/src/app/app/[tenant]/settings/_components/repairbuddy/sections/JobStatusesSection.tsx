"use client";

import React, { useCallback, useMemo, useState } from "react";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { Input } from "@/components/ui/Input";
import { Modal } from "@/components/ui/Modal";
import { Select } from "@/components/ui/Select";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
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

  const [query, setQuery] = useState("");
  const [pageIndex, setPageIndex] = useState(0);
  const [pageSize, setPageSize] = useState(10);

  const statuses = draft.jobStatuses.statuses;
  const statusOptions = useMemo(() => statuses.map((s) => ({ id: s.id, name: s.name })), [statuses]);

  const toggleActive = useCallback(
    (id: string) => {
      setDraft((prev) => ({
        ...prev,
        jobStatuses: {
          ...prev.jobStatuses,
          statuses: prev.jobStatuses.statuses.map((s) =>
            s.id === id ? { ...s, status: s.status === "active" ? "inactive" : "active" } : s,
          ),
        },
      }));
    },
    [setDraft],
  );

  const filtered = useMemo(() => {
    const needle = query.trim().toLowerCase();
    if (!needle) return statuses;
    return statuses.filter((s) => {
      const hay = `${s.id} ${s.name} ${s.slug} ${s.invoiceLabel} ${s.manageWooStock ? "woo" : ""} ${s.status}`.toLowerCase();
      return hay.includes(needle);
    });
  }, [query, statuses]);

  const totalRows = filtered.length;
  const pageRows = useMemo(() => {
    const start = pageIndex * pageSize;
    const end = start + pageSize;
    return filtered.slice(start, end);
  }, [filtered, pageIndex, pageSize]);

  const columns = useMemo<Array<DataTableColumn<RepairBuddySettingsDraft["jobStatuses"]["statuses"][number]>>>(
    () => [
      { id: "id", header: "ID", cell: (s) => <div className="text-sm text-zinc-600">{s.id}</div>, className: "whitespace-nowrap" },
      { id: "name", header: "Name", cell: (s) => <div className="text-sm text-zinc-700">{s.name}</div>, className: "min-w-[180px]" },
      { id: "slug", header: "Slug", cell: (s) => <div className="text-sm text-zinc-600">{s.slug}</div>, className: "min-w-[180px]" },
      { id: "invoiceLabel", header: "Invoice label", cell: (s) => <div className="text-sm text-zinc-700">{s.invoiceLabel}</div>, className: "min-w-[200px]" },
      { id: "woo", header: "Woo stock", cell: (s) => <div className="text-sm text-zinc-700">{s.manageWooStock ? "Yes" : "No"}</div>, className: "whitespace-nowrap" },
      { id: "status", header: "Status", cell: (s) => <div className="text-sm text-zinc-700">{s.status}</div>, className: "whitespace-nowrap" },
      {
        id: "actions",
        header: "Actions",
        cell: (s) => (
          <div className="flex items-center gap-2">
            <Button variant="outline" size="sm" disabled={isMock}>
              Edit
            </Button>
            <Button variant="outline" size="sm" disabled={isMock} onClick={() => toggleActive(s.id)}>
              Toggle
            </Button>
          </div>
        ),
        className: "whitespace-nowrap",
      },
    ],
    [isMock, toggleActive],
  );

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

      <Card className="shadow-none">
        <CardContent className="pt-5">
          <DataTable
            title="Statuses"
            data={pageRows}
            columns={columns}
            getRowId={(row) => row.id}
            emptyMessage="No statuses."
            search={{ placeholder: "Search statuses..." }}
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
