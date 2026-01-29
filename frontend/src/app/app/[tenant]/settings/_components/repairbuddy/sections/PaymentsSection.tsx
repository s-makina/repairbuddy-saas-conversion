"use client";

import React, { useState } from "react";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { Input } from "@/components/ui/Input";
import { Modal } from "@/components/ui/Modal";
import { SectionShell } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/SectionShell";
import type { RepairBuddySettingsDraft } from "@/app/app/[tenant]/settings/_components/repairbuddy/types";

export function PaymentsSection({
  draft,
  updatePayments,
  isMock,
}: {
  draft: RepairBuddySettingsDraft;
  updatePayments: (patch: Partial<RepairBuddySettingsDraft["payments"]>) => void;
  isMock: boolean;
}) {
  const [addOpen, setAddOpen] = useState(false);
  const p = draft.payments;

  const [query, setQuery] = useState("");
  const [pageIndex, setPageIndex] = useState(0);
  const [pageSize, setPageSize] = useState(10);

  const filtered = React.useMemo(() => {
    const needle = query.trim().toLowerCase();
    if (!needle) return p.statuses;
    return p.statuses.filter((s) => `${s.id} ${s.name} ${s.slug} ${s.status}`.toLowerCase().includes(needle));
  }, [p.statuses, query]);

  const totalRows = filtered.length;
  const pageRows = React.useMemo(() => {
    const start = pageIndex * pageSize;
    const end = start + pageSize;
    return filtered.slice(start, end);
  }, [filtered, pageIndex, pageSize]);

  type PaymentStatusRow = RepairBuddySettingsDraft["payments"]["statuses"][number];

  const columns = React.useMemo<Array<DataTableColumn<PaymentStatusRow>>>(
    () => [
      { id: "id", header: "ID", cell: (s) => <div className="text-sm text-zinc-600">{s.id}</div>, className: "whitespace-nowrap" },
      { id: "name", header: "Name", cell: (s) => <div className="text-sm text-zinc-700">{s.name}</div>, className: "min-w-[200px]" },
      { id: "slug", header: "Slug", cell: (s) => <div className="text-sm text-zinc-600">{s.slug}</div>, className: "min-w-[200px]" },
      { id: "status", header: "Status", cell: (s) => <div className="text-sm text-zinc-700">{s.status}</div>, className: "whitespace-nowrap" },
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
    <SectionShell title="Payments" description="Payment statuses and allowed payment methods.">
      <div className="flex items-center justify-between gap-3">
        <div className="text-sm text-zinc-600">Payment statuses table (UI-only).</div>
        <div className="inline-flex" onClick={() => setAddOpen(true)}>
          <Button variant="outline" disabled={isMock} className="pointer-events-none">
            Add status
          </Button>
        </div>
      </div>

      <Card className="shadow-none">
        <CardContent className="pt-5">
          <DataTable
            title="Payment statuses"
            data={pageRows}
            columns={columns}
            getRowId={(row) => row.id}
            emptyMessage="No payment statuses."
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

      <div>
        <div className="text-sm font-semibold text-[var(--rb-text)]">Payment methods</div>
        <div className="mt-2 grid gap-2 sm:grid-cols-2">
          <label className="flex items-center gap-2 text-sm">
            <input
              type="checkbox"
              checked={p.paymentMethods.cash}
              onChange={(e) => updatePayments({ paymentMethods: { ...p.paymentMethods, cash: e.target.checked } })}
            />
            Cash
          </label>
          <label className="flex items-center gap-2 text-sm">
            <input
              type="checkbox"
              checked={p.paymentMethods.card}
              onChange={(e) => updatePayments({ paymentMethods: { ...p.paymentMethods, card: e.target.checked } })}
            />
            Card
          </label>
          <label className="flex items-center gap-2 text-sm">
            <input
              type="checkbox"
              checked={p.paymentMethods.bankTransfer}
              onChange={(e) => updatePayments({ paymentMethods: { ...p.paymentMethods, bankTransfer: e.target.checked } })}
            />
            Bank transfer
          </label>
          <label className="flex items-center gap-2 text-sm">
            <input
              type="checkbox"
              checked={p.paymentMethods.paypal}
              onChange={(e) => updatePayments({ paymentMethods: { ...p.paymentMethods, paypal: e.target.checked } })}
            />
            PayPal
          </label>
          <label className="flex items-center gap-2 text-sm">
            <input
              type="checkbox"
              checked={p.paymentMethods.other}
              onChange={(e) => updatePayments({ paymentMethods: { ...p.paymentMethods, other: e.target.checked } })}
            />
            Other
          </label>
        </div>
      </div>

      <Modal
        open={addOpen}
        onClose={() => setAddOpen(false)}
        title="Add payment status"
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
            <Input value="" onChange={() => {}} placeholder="e.g. Partially paid" />
          </div>
          <div className="space-y-1">
            <label className="text-sm font-medium">Slug</label>
            <Input value="" onChange={() => {}} placeholder="partially_paid" />
          </div>
        </div>
      </Modal>
    </SectionShell>
  );
}
