"use client";

import React from "react";
import { Input } from "@/components/ui/Input";
import { SectionShell } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/SectionShell";
import type { RepairBuddySettingsDraft } from "@/app/app/[tenant]/settings/_components/repairbuddy/types";

export function GeneralSection({
  draft,
  updateGeneral,
}: {
  draft: RepairBuddySettingsDraft;
  updateGeneral: (patch: Partial<RepairBuddySettingsDraft["general"]>) => void;
}) {
  const g = draft.general;

  return (
    <SectionShell title="General" description="Business identity, case numbering and customer communication defaults.">
      <div className="grid gap-4 sm:grid-cols-2">
        <div className="space-y-1">
          <label className="text-sm font-medium">Business name</label>
          <Input value={g.businessName} disabled />
        </div>
        <div className="space-y-1">
          <label className="text-sm font-medium">Business phone</label>
          <Input value={g.businessPhone} disabled placeholder="+1 555 123 4567" />
        </div>
        <div className="space-y-1">
          <label className="text-sm font-medium">Email</label>
          <Input value={g.email} disabled type="email" placeholder="support@company.com" />
        </div>
        <div className="sm:col-span-2 space-y-1">
          <label className="text-sm font-medium">Business address</label>
          <Input value={g.businessAddress} disabled placeholder="Street, City, Region" />
        </div>
        <div className="sm:col-span-2 space-y-1">
          <label className="text-sm font-medium">Logo URL</label>
          <Input value={g.logoUrl} disabled placeholder="https://..." />
        </div>

        <div className="space-y-1">
          <label className="text-sm font-medium">Case number prefix</label>
          <Input value={g.caseNumberPrefix} onChange={(e) => updateGeneral({ caseNumberPrefix: e.target.value })} placeholder="RB" />
        </div>
        <div className="space-y-1">
          <label className="text-sm font-medium">Case number length</label>
          <Input
            value={String(g.caseNumberLength)}
            onChange={(e) => updateGeneral({ caseNumberLength: Number(e.target.value || 0) })}
            type="number"
            min={1}
          />
        </div>

        <label className="sm:col-span-2 flex items-center gap-2 text-sm">
          <input type="checkbox" checked={g.emailCustomer} onChange={(e) => updateGeneral({ emailCustomer: e.target.checked })} />
          Email customer
        </label>
        <label className="sm:col-span-2 flex items-center gap-2 text-sm">
          <input type="checkbox" checked={g.attachPdf} onChange={(e) => updateGeneral({ attachPdf: e.target.checked })} />
          Attach PDF
        </label>
        <label className="sm:col-span-2 flex items-center gap-2 text-sm">
          <input
            type="checkbox"
            checked={g.nextServiceDateEnabled}
            onChange={(e) => updateGeneral({ nextServiceDateEnabled: e.target.checked })}
          />
          Next service date toggle
        </label>

        <div className="sm:col-span-2 space-y-1">
          <label className="text-sm font-medium">GDPR acceptance text</label>
          <textarea
            className="min-h-[90px] w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
            value={g.gdprAcceptanceText}
            onChange={(e) => updateGeneral({ gdprAcceptanceText: e.target.value })}
          />
        </div>
        <div className="space-y-1">
          <label className="text-sm font-medium">GDPR link label</label>
          <Input value={g.gdprLinkLabel} onChange={(e) => updateGeneral({ gdprLinkLabel: e.target.value })} />
        </div>
        <div className="space-y-1">
          <label className="text-sm font-medium">GDPR link URL</label>
          <Input value={g.gdprLinkUrl} onChange={(e) => updateGeneral({ gdprLinkUrl: e.target.value })} placeholder="https://..." />
        </div>

        <div className="space-y-1">
          <label className="text-sm font-medium">Default country</label>
          <Input value={g.defaultCountry} disabled placeholder="US" />
        </div>

        <label className="sm:col-span-2 flex items-center gap-2 text-sm">
          <input
            type="checkbox"
            checked={g.disablePartsUseWooProducts}
            onChange={(e) => updateGeneral({ disablePartsUseWooProducts: e.target.checked })}
          />
          Disable parts and use Woo products
        </label>
        <label className="sm:col-span-2 flex items-center gap-2 text-sm">
          <input
            type="checkbox"
            checked={g.disableStatusCheckBySerial}
            onChange={(e) => updateGeneral({ disableStatusCheckBySerial: e.target.checked })}
          />
          Disable status check by serial
        </label>
      </div>
    </SectionShell>
  );
}
