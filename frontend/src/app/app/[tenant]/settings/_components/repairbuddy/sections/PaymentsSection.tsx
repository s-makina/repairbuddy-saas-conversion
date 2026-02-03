"use client";

import React from "react";
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
  void isMock;
  const p = draft.payments;

  return (
    <SectionShell title="Payments" description="Allowed payment methods.">
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
          <label className="flex items-center gap-2 text-sm text-zinc-400">
            <input type="checkbox" checked={p.paymentMethods.card} disabled />
            Card
          </label>
          <label className="flex items-center gap-2 text-sm text-zinc-400">
            <input type="checkbox" checked={p.paymentMethods.bankTransfer} disabled />
            Bank transfer
          </label>
          <label className="flex items-center gap-2 text-sm text-zinc-400">
            <input type="checkbox" checked={p.paymentMethods.paypal} disabled />
            PayPal
          </label>
          <label className="flex items-center gap-2 text-sm text-zinc-400">
            <input type="checkbox" checked={p.paymentMethods.other} disabled />
            Other
          </label>
        </div>
      </div>
    </SectionShell>
  );
}
