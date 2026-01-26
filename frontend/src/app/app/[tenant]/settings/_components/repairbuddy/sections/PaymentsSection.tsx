"use client";

import React, { useState } from "react";
import { Button } from "@/components/ui/Button";
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

      <div className="overflow-x-auto rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white">
        <table className="w-full text-left text-sm">
          <thead className="border-b border-[var(--rb-border)] bg-[var(--rb-surface-muted)]">
            <tr>
              <th className="px-3 py-2 font-medium">ID</th>
              <th className="px-3 py-2 font-medium">Name</th>
              <th className="px-3 py-2 font-medium">Slug</th>
              <th className="px-3 py-2 font-medium">Status</th>
              <th className="px-3 py-2 font-medium">Actions</th>
            </tr>
          </thead>
          <tbody>
            {p.statuses.map((s) => (
              <tr key={s.id} className="border-b border-[color:color-mix(in_srgb,var(--rb-border),transparent_30%)]">
                <td className="px-3 py-2 text-zinc-600">{s.id}</td>
                <td className="px-3 py-2">{s.name}</td>
                <td className="px-3 py-2 text-zinc-600">{s.slug}</td>
                <td className="px-3 py-2">{s.status}</td>
                <td className="px-3 py-2">
                  <Button size="sm" variant="outline" disabled={isMock}>
                    Edit
                  </Button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

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
