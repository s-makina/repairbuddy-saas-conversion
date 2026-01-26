"use client";

import React from "react";
import { Input } from "@/components/ui/Input";
import { Select } from "@/components/ui/Select";
import { SectionShell } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/SectionShell";
import type { RepairBuddySettingsDraft } from "@/app/app/[tenant]/settings/_components/repairbuddy/types";

export function CurrencySection({
  draft,
  updateCurrency,
}: {
  draft: RepairBuddySettingsDraft;
  updateCurrency: (patch: Partial<RepairBuddySettingsDraft["currency"]>) => void;
}) {
  const c = draft.currency;

  return (
    <SectionShell title="Currency" description="Formatting and currency display preferences.">
      <div className="grid gap-4 sm:grid-cols-2">
        <div className="space-y-1">
          <label className="text-sm font-medium">Currency</label>
          <Input value={c.currency} onChange={(e) => updateCurrency({ currency: e.target.value.toUpperCase().slice(0, 3) })} placeholder="USD" />
        </div>
        <div className="space-y-1">
          <label className="text-sm font-medium">Currency position</label>
          <Select
            value={c.currencyPosition}
            onChange={(e) => updateCurrency({ currencyPosition: e.target.value as RepairBuddySettingsDraft["currency"]["currencyPosition"] })}
          >
            <option value="left">Left</option>
            <option value="right">Right</option>
            <option value="left_space">Left (space)</option>
            <option value="right_space">Right (space)</option>
          </Select>
        </div>
        <div className="space-y-1">
          <label className="text-sm font-medium">Thousand separator</label>
          <Input value={c.thousandSeparator} onChange={(e) => updateCurrency({ thousandSeparator: e.target.value })} placeholder="," />
        </div>
        <div className="space-y-1">
          <label className="text-sm font-medium">Decimal separator</label>
          <Input value={c.decimalSeparator} onChange={(e) => updateCurrency({ decimalSeparator: e.target.value })} placeholder="." />
        </div>
        <div className="space-y-1">
          <label className="text-sm font-medium">Number of decimals</label>
          <Input
            value={String(c.numberOfDecimals)}
            onChange={(e) => updateCurrency({ numberOfDecimals: Number(e.target.value || 0) })}
            type="number"
            min={0}
            max={6}
          />
        </div>
      </div>
    </SectionShell>
  );
}
