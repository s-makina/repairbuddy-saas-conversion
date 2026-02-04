"use client";

import React, { useMemo, useState } from "react";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { Input } from "@/components/ui/Input";
import { Modal } from "@/components/ui/Modal";
import { Select } from "@/components/ui/Select";
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
  const [fieldModalOpen, setFieldModalOpen] = useState(false);
  const [editingFieldId, setEditingFieldId] = useState<string | null>(null);
  const [fieldError, setFieldError] = useState<string | null>(null);
  const d = draft.devicesBrands;

  const [query, setQuery] = useState("");
  const [pageIndex, setPageIndex] = useState(0);
  const [pageSize, setPageSize] = useState(10);

  const fields = d.additionalDeviceFields;

  const filtered = React.useMemo(() => {
    const needle = query.trim().toLowerCase();
    if (!needle) return fields;
    return fields.filter((f) => `${f.id} ${f.label}`.toLowerCase().includes(needle));
  }, [fields, query]);

  const totalRows = filtered.length;
  const pageRows = React.useMemo(() => {
    const start = pageIndex * pageSize;
    const end = start + pageSize;
    return filtered.slice(start, end);
  }, [filtered, pageIndex, pageSize]);

  const editingField = useMemo(() => {
    if (!editingFieldId) return null;
    return fields.find((f) => f.id === editingFieldId) ?? null;
  }, [editingFieldId, fields]);

  const [fieldDraft, setFieldDraft] = useState<RepairBuddyAdditionalDeviceField>(() => ({
    id: "",
    label: "",
    type: "text",
    displayInBookingForm: true,
    displayInInvoice: true,
    displayForCustomer: true,
  }));

  function openAddField() {
    setFieldError(null);
    setEditingFieldId(null);
    setFieldDraft({
      id: "",
      label: "",
      type: "text",
      displayInBookingForm: true,
      displayInInvoice: true,
      displayForCustomer: true,
    });
    setFieldModalOpen(true);
  }

  function openEditField(field: RepairBuddyAdditionalDeviceField) {
    setFieldError(null);
    setEditingFieldId(field.id);
    setFieldDraft({ ...field });
    setFieldModalOpen(true);
  }

  function closeFieldModal() {
    setFieldModalOpen(false);
    setEditingFieldId(null);
    setFieldError(null);
  }

  function genId() {
    const c = globalThis as unknown as { crypto?: { randomUUID?: () => string } };
    if (c.crypto?.randomUUID) return `device_field_${c.crypto.randomUUID()}`;
    return `device_field_${Date.now()}_${Math.random().toString(16).slice(2)}`;
  }

  function saveField() {
    const nextLabel = fieldDraft.label.trim();

    if (!nextLabel) {
      setFieldError("Label is required.");
      return;
    }

    const next: RepairBuddyAdditionalDeviceField = {
      id: editingFieldId ?? genId(),
      label: nextLabel,
      type: "text",
      displayInBookingForm: Boolean(fieldDraft.displayInBookingForm),
      displayInInvoice: Boolean(fieldDraft.displayInInvoice),
      displayForCustomer: Boolean(fieldDraft.displayForCustomer),
    };

    const nextFields = editingFieldId ? fields.map((f) => (f.id === editingFieldId ? next : f)) : [...fields, next];
    updateDevicesBrands({ additionalDeviceFields: nextFields });
    closeFieldModal();
  }

  function deleteField(field: RepairBuddyAdditionalDeviceField) {
    if (!globalThis.confirm(`Delete field "${field.label}"?`)) return;
    updateDevicesBrands({ additionalDeviceFields: fields.filter((f) => f.id !== field.id) });
  }

  const columns = React.useMemo<Array<DataTableColumn<RepairBuddyAdditionalDeviceField>>>(
    () => [
      { id: "label", header: "Label", cell: (f) => <div className="text-sm text-zinc-700">{f.label}</div>, className: "min-w-[200px]" },
      { id: "type", header: "Type", cell: () => <div className="text-sm text-zinc-600">Text</div>, className: "whitespace-nowrap" },
      {
        id: "booking",
        header: "In booking form?",
        cell: (f) => <div className="text-sm text-zinc-700">{f.displayInBookingForm ? "Display" : "Hide"}</div>,
        className: "whitespace-nowrap",
      },
      {
        id: "invoice",
        header: "In invoice?",
        cell: (f) => <div className="text-sm text-zinc-700">{f.displayInInvoice ? "Display" : "Hide"}</div>,
        className: "whitespace-nowrap",
      },
      {
        id: "customer",
        header: "In customer output?",
        cell: (f) => <div className="text-sm text-zinc-700">{f.displayForCustomer ? "Display" : "Hide"}</div>,
        className: "whitespace-nowrap",
      },
      {
        id: "actions",
        header: "Actions",
        cell: (f) => (
          <div className="flex items-center gap-2">
            <Button size="sm" variant="outline" disabled={isMock} onClick={() => openEditField(f)}>
              Edit
            </Button>
            <Button size="sm" variant="outline" disabled={isMock} onClick={() => deleteField(f)}>
              Delete
            </Button>
          </div>
        ),
        className: "whitespace-nowrap",
      },
    ],
    [isMock, fields],
  );

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
        {/* <label className="sm:col-span-2 flex items-center gap-2 text-sm">
          <input
            type="checkbox"
            checked={d.useWooProductsAsDevices}
            onChange={(e) => updateDevicesBrands({ useWooProductsAsDevices: e.target.checked })}
          />
          Use Woo products as devices
        </label> */}
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
          <div className="mt-1 text-xs text-zinc-500">Custom fields shown on the device form.</div>
        </div>
        <Button variant="outline" disabled={isMock} onClick={openAddField}>
          Add field
        </Button>
      </div>

      <Card className="shadow-none">
        <CardContent className="pt-5">
          <DataTable
            title="Additional device fields"
            data={pageRows}
            columns={columns}
            getRowId={(row) => row.id}
            emptyMessage="No additional fields yet."
            search={{ placeholder: "Search fields..." }}
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
        open={fieldModalOpen}
        onClose={closeFieldModal}
        title={editingField ? "Edit additional device field" : "Add additional device field"}
        footer={
          <div className="flex items-center justify-end gap-2">
            <Button variant="outline" onClick={closeFieldModal}>
              Close
            </Button>
            <Button disabled={isMock} onClick={saveField}>
              Save
            </Button>
          </div>
        }
      >
        <div className="space-y-3">
          {fieldError ? <div className="text-sm text-red-600">{fieldError}</div> : null}
          <div className="space-y-1">
            <label className="text-sm font-medium">Label</label>
            <Input value={fieldDraft.label} onChange={(e) => setFieldDraft((p) => ({ ...p, label: e.target.value }))} placeholder="e.g. Password" />
          </div>
          <div className="space-y-1">
            <label className="text-sm font-medium">Type</label>
            <Select value={fieldDraft.type} onChange={() => {}} disabled>
              <option value="text">Text</option>
            </Select>
          </div>
          <div className="grid gap-3 sm:grid-cols-3">
            <div className="space-y-1">
              <label className="text-sm font-medium">In booking form?</label>
              <Select
                value={fieldDraft.displayInBookingForm ? "yes" : "no"}
                onChange={(e) => setFieldDraft((p) => ({ ...p, displayInBookingForm: e.target.value === "yes" }))}
              >
                <option value="yes">Display</option>
                <option value="no">Hide</option>
              </Select>
            </div>
            <div className="space-y-1">
              <label className="text-sm font-medium">In invoice?</label>
              <Select value={fieldDraft.displayInInvoice ? "yes" : "no"} onChange={(e) => setFieldDraft((p) => ({ ...p, displayInInvoice: e.target.value === "yes" }))}>
                <option value="yes">Display</option>
                <option value="no">Hide</option>
              </Select>
            </div>
            <div className="space-y-1">
              <label className="text-sm font-medium">In customer output?</label>
              <Select
                value={fieldDraft.displayForCustomer ? "yes" : "no"}
                onChange={(e) => setFieldDraft((p) => ({ ...p, displayForCustomer: e.target.value === "yes" }))}
              >
                <option value="yes">Display</option>
                <option value="no">Hide</option>
              </Select>
            </div>
          </div>
        </div>
      </Modal>
    </SectionShell>
  );
}
