"use client";

import React from "react";
import { Input } from "@/components/ui/Input";
import { Select } from "@/components/ui/Select";
import { SectionShell } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/SectionShell";
import type { RepairBuddySettingsDraft } from "@/app/app/[tenant]/settings/_components/repairbuddy/types";

export function InvoicesReportsSection({
  draft,
  updateInvoicesReports,
}: {
  draft: RepairBuddySettingsDraft;
  updateInvoicesReports: (patch: Partial<RepairBuddySettingsDraft["invoicesReports"]>) => void;
}) {
  const s = draft.invoicesReports;

  return (
    <SectionShell title="Invoices & Reports" description="Document printing, footer content and invoice display preferences.">
      <div className="grid gap-4 sm:grid-cols-2">
        <label className="sm:col-span-2 flex items-center gap-2 text-sm">
          <input type="checkbox" checked={s.addQrCodeToInvoice} onChange={(e) => updateInvoicesReports({ addQrCodeToInvoice: e.target.checked })} />
          Add QR code to invoice
        </label>

        <div className="sm:col-span-2 space-y-1">
          <label className="text-sm font-medium">Invoice footer message</label>
          <Input value={s.invoiceFooterMessage} onChange={(e) => updateInvoicesReports({ invoiceFooterMessage: e.target.value })} />
        </div>

        <div className="space-y-1">
          <label className="text-sm font-medium">Invoice print type</label>
          <Select value={s.invoicePrintType} onChange={(e) => updateInvoicesReports({ invoicePrintType: e.target.value as RepairBuddySettingsDraft["invoicesReports"]["invoicePrintType"] })}>
            <option value="standard">Standard</option>
            <option value="thermal">Thermal</option>
          </Select>
        </div>

        <div className="space-y-1">
          <label className="text-sm font-medium">Repair order type</label>
          <Select value={s.repairOrderType} onChange={(e) => updateInvoicesReports({ repairOrderType: e.target.value as RepairBuddySettingsDraft["invoicesReports"]["repairOrderType"] })}>
            <option value="standard">Standard</option>
            <option value="detailed">Detailed</option>
          </Select>
        </div>

        <div className="space-y-1">
          <label className="text-sm font-medium">Repair order print size</label>
          <Select
            value={s.repairOrderPrintSize}
            onChange={(e) => updateInvoicesReports({ repairOrderPrintSize: e.target.value as RepairBuddySettingsDraft["invoicesReports"]["repairOrderPrintSize"] })}
          >
            <option value="a4">A4</option>
            <option value="letter">Letter</option>
          </Select>
        </div>

        <div className="sm:col-span-2 grid gap-2">
          <label className="flex items-center gap-2 text-sm">
            <input type="checkbox" checked={s.displayPickupDate} onChange={(e) => updateInvoicesReports({ displayPickupDate: e.target.checked })} />
            Display pickup date
          </label>
          <label className="flex items-center gap-2 text-sm">
            <input type="checkbox" checked={s.displayDeliveryDate} onChange={(e) => updateInvoicesReports({ displayDeliveryDate: e.target.checked })} />
            Display delivery date
          </label>
          <label className="flex items-center gap-2 text-sm">
            <input
              type="checkbox"
              checked={s.displayNextServiceDate}
              onChange={(e) => updateInvoicesReports({ displayNextServiceDate: e.target.checked })}
            />
            Display next service date
          </label>
        </div>

        <div className="sm:col-span-2 space-y-1">
          <label className="text-sm font-medium">Invoice disclaimer / terms</label>
          <textarea
            className="min-h-[120px] w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
            value={s.invoiceDisclaimerTerms}
            onChange={(e) => updateInvoicesReports({ invoiceDisclaimerTerms: e.target.value })}
          />
        </div>

        <div className="sm:col-span-2 space-y-1">
          <label className="text-sm font-medium">Terms URL</label>
          <Input value={s.termsUrl} onChange={(e) => updateInvoicesReports({ termsUrl: e.target.value })} placeholder="https://..." />
        </div>

        <label className="sm:col-span-2 flex items-center gap-2 text-sm">
          <input
            type="checkbox"
            checked={s.displayBusinessAddressDetails}
            onChange={(e) => updateInvoicesReports({ displayBusinessAddressDetails: e.target.checked })}
          />
          Display business address details
        </label>
        <label className="sm:col-span-2 flex items-center gap-2 text-sm">
          <input
            type="checkbox"
            checked={s.displayCustomerEmailAddressDetails}
            onChange={(e) => updateInvoicesReports({ displayCustomerEmailAddressDetails: e.target.checked })}
          />
          Display customer email/address details
        </label>

        <div className="sm:col-span-2 space-y-1">
          <label className="text-sm font-medium">Repair order footer message</label>
          <Input value={s.repairOrderFooterMessage} onChange={(e) => updateInvoicesReports({ repairOrderFooterMessage: e.target.value })} />
        </div>
      </div>
    </SectionShell>
  );
}
