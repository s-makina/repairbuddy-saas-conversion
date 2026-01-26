"use client";

import React, { useState } from "react";
import { Button } from "@/components/ui/Button";
import { Input } from "@/components/ui/Input";
import { Modal } from "@/components/ui/Modal";
import { SectionShell } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/SectionShell";
import type { RepairBuddyAdditionalDeviceField, RepairBuddySettingsDraft } from "@/app/app/[tenant]/settings/_components/repairbuddy/types";

export function DevicesBrandsSection({
  draft,
  updateDevicesBrands,
  isMock,
}: {
  draft: RepairBuddySettingsDraft;
  updateDevicesBrands: (patch: Partial<RepairBuddySettingsDraft["devicesBrands"]>) => void;
  isMock: boolean;
}) {
  const [addFieldOpen, setAddFieldOpen] = useState(false);
  const d = draft.devicesBrands;

  const fields = d.additionalDeviceFields;

  return (
    <SectionShell title="Devices & Brands" description="Device fields, labels and pickup/delivery/rental toggles.">
      <div className="grid gap-4 sm:grid-cols-2">
        <label className="sm:col-span-2 flex items-center gap-2 text-sm">
          <input type="checkbox" checked={d.enablePinCodeField} onChange={(e) => updateDevicesBrands({ enablePinCodeField: e.target.checked })} />
          Enable pin code field
        </label>
        <label className="sm:col-span-2 flex items-center gap-2 text-sm">
          <input
            type="checkbox"
            checked={d.showPinCodeInDocuments}
            onChange={(e) => updateDevicesBrands({ showPinCodeInDocuments: e.target.checked })}
          />
          Show pin code in invoices/emails/status check
        </label>
        <label className="sm:col-span-2 flex items-center gap-2 text-sm">
          <input
            type="checkbox"
            checked={d.useWooProductsAsDevices}
            onChange={(e) => updateDevicesBrands({ useWooProductsAsDevices: e.target.checked })}
          />
          Use Woo products as devices
        </label>
      </div>

      <div>
        <div className="text-sm font-semibold text-[var(--rb-text)]">Labels</div>
        <div className="mt-2 grid gap-3 sm:grid-cols-2">
          <Input value={d.labels.note} onChange={(e) => updateDevicesBrands({ labels: { ...d.labels, note: e.target.value } })} placeholder="Note" />
          <Input value={d.labels.pin} onChange={(e) => updateDevicesBrands({ labels: { ...d.labels, pin: e.target.value } })} placeholder="PIN" />
          <Input value={d.labels.device} onChange={(e) => updateDevicesBrands({ labels: { ...d.labels, device: e.target.value } })} placeholder="Device" />
          <Input value={d.labels.deviceBrand} onChange={(e) => updateDevicesBrands({ labels: { ...d.labels, deviceBrand: e.target.value } })} placeholder="Brand" />
          <Input value={d.labels.deviceType} onChange={(e) => updateDevicesBrands({ labels: { ...d.labels, deviceType: e.target.value } })} placeholder="Type" />
          <Input value={d.labels.imei} onChange={(e) => updateDevicesBrands({ labels: { ...d.labels, imei: e.target.value } })} placeholder="IMEI" />
        </div>
      </div>

      <div className="flex items-center justify-between gap-3">
        <div>
          <div className="text-sm font-semibold text-[var(--rb-text)]">Additional device fields</div>
          <div className="mt-1 text-xs text-zinc-500">Repeater UI (mock). Add/edit is disabled.</div>
        </div>
        <div className="inline-flex" onClick={() => setAddFieldOpen(true)}>
          <Button variant="outline" disabled={isMock} className="pointer-events-none">
            Add field
          </Button>
        </div>
      </div>

      <div className="overflow-x-auto rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white">
        <table className="w-full text-left text-sm">
          <thead className="border-b border-[var(--rb-border)] bg-[var(--rb-surface-muted)]">
            <tr>
              <th className="px-3 py-2 font-medium">Label</th>
              <th className="px-3 py-2 font-medium">Key</th>
              <th className="px-3 py-2 font-medium">Required</th>
              <th className="px-3 py-2 font-medium">Actions</th>
            </tr>
          </thead>
          <tbody>
            {fields.length === 0 ? (
              <tr>
                <td className="px-3 py-4 text-zinc-500" colSpan={4}>
                  No additional fields yet.
                </td>
              </tr>
            ) : (
              fields.map((f) => (
                <tr key={f.id} className="border-b border-[color:color-mix(in_srgb,var(--rb-border),transparent_30%)]">
                  <td className="px-3 py-2">{f.label}</td>
                  <td className="px-3 py-2 text-zinc-600">{f.key}</td>
                  <td className="px-3 py-2">{f.required ? "Yes" : "No"}</td>
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

      <div>
        <div className="text-sm font-semibold text-[var(--rb-text)]">Pickup / Delivery</div>
        <div className="mt-2 grid gap-3 sm:grid-cols-2">
          <label className="sm:col-span-2 flex items-center gap-2 text-sm">
            <input
              type="checkbox"
              checked={d.pickupDeliveryEnabled}
              onChange={(e) => updateDevicesBrands({ pickupDeliveryEnabled: e.target.checked })}
            />
            Pickup/delivery enabled
          </label>
          <Input value={d.pickupCharge} onChange={(e) => updateDevicesBrands({ pickupCharge: e.target.value })} placeholder="Pickup charge" />
          <Input value={d.deliveryCharge} onChange={(e) => updateDevicesBrands({ deliveryCharge: e.target.value })} placeholder="Delivery charge" />
        </div>
      </div>

      <div>
        <div className="text-sm font-semibold text-[var(--rb-text)]">Rental</div>
        <div className="mt-2 grid gap-3 sm:grid-cols-2">
          <label className="sm:col-span-2 flex items-center gap-2 text-sm">
            <input type="checkbox" checked={d.rentalEnabled} onChange={(e) => updateDevicesBrands({ rentalEnabled: e.target.checked })} />
            Rental enabled
          </label>
          <Input value={d.rentalPerDay} onChange={(e) => updateDevicesBrands({ rentalPerDay: e.target.value })} placeholder="Per-day" />
          <Input value={d.rentalPerWeek} onChange={(e) => updateDevicesBrands({ rentalPerWeek: e.target.value })} placeholder="Per-week" />
        </div>
      </div>

      <Modal
        open={addFieldOpen}
        onClose={() => setAddFieldOpen(false)}
        title="Add additional device field"
        footer={
          <div className="flex items-center justify-end gap-2">
            <Button variant="outline" onClick={() => setAddFieldOpen(false)}>
              Close
            </Button>
            <Button disabled={isMock}>Save</Button>
          </div>
        }
      >
        <AddFieldForm />
      </Modal>
    </SectionShell>
  );
}

function AddFieldForm() {
  const [draft, setDraft] = useState<RepairBuddyAdditionalDeviceField>(() => ({
    id: "",
    label: "",
    key: "",
    required: false,
  }));

  return (
    <div className="space-y-3">
      <div className="space-y-1">
        <label className="text-sm font-medium">Label</label>
        <Input value={draft.label} onChange={(e) => setDraft((p) => ({ ...p, label: e.target.value }))} placeholder="e.g. Password" />
      </div>
      <div className="space-y-1">
        <label className="text-sm font-medium">Key</label>
        <Input value={draft.key} onChange={(e) => setDraft((p) => ({ ...p, key: e.target.value }))} placeholder="password" />
      </div>
      <label className="flex items-center gap-2 text-sm">
        <input type="checkbox" checked={draft.required} onChange={(e) => setDraft((p) => ({ ...p, required: e.target.checked }))} />
        Required
      </label>
    </div>
  );
}
