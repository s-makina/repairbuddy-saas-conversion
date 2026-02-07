"use client";

import React, { useMemo, useState } from "react";
import { Badge } from "@/components/ui/Badge";
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

  const openEditField = React.useCallback((field: RepairBuddyAdditionalDeviceField) => {
    setFieldError(null);
    setEditingFieldId(field.id);
    setFieldDraft({ ...field });
    setFieldModalOpen(true);
  }, []);

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

  const deleteField = React.useCallback(
    (field: RepairBuddyAdditionalDeviceField) => {
      if (!globalThis.confirm(`Delete field "${field.label}"?`)) return;
      updateDevicesBrands({ additionalDeviceFields: fields.filter((f) => f.id !== field.id) });
    },
    [fields, updateDevicesBrands],
  );

  const columns = React.useMemo<Array<DataTableColumn<RepairBuddyAdditionalDeviceField>>>(
    () => [
      {
        id: "label",
        header: "Label",
        cell: (f) => (
          <div className="min-w-[200px]">
            <div className="text-sm font-medium text-[var(--rb-text)]">{f.label}</div>
            <div className="mt-0.5 text-xs text-zinc-500">ID: {f.id}</div>
          </div>
        ),
      },
      { id: "type", header: "Type", cell: () => <div className="text-sm text-zinc-600">Text</div>, className: "whitespace-nowrap" },
      {
        id: "booking",
        header: "In booking form?",
        cell: (f) => <Badge variant={f.displayInBookingForm ? "success" : "default"}>{f.displayInBookingForm ? "Display" : "Hide"}</Badge>,
        className: "whitespace-nowrap",
      },
      {
        id: "invoice",
        header: "In invoice?",
        cell: (f) => <Badge variant={f.displayInInvoice ? "success" : "default"}>{f.displayInInvoice ? "Display" : "Hide"}</Badge>,
        className: "whitespace-nowrap",
      },
      {
        id: "customer",
        header: "In customer output?",
        cell: (f) => <Badge variant={f.displayForCustomer ? "success" : "default"}>{f.displayForCustomer ? "Display" : "Hide"}</Badge>,
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
    [deleteField, isMock, openEditField],
  );

  return (
    <SectionShell title="Devices & Brands" description="Device fields, labels and pickup/delivery/rental toggles.">
      <div className="grid gap-4 sm:grid-cols-2">
        <div className="sm:col-span-2">
          <div className="text-sm font-semibold text-[var(--rb-text)]">Device form</div>
          <div className="mt-2 grid gap-3">
            <label className="flex items-start gap-2 text-sm">
              <input
                type="checkbox"
                checked={d.enablePinCodeField}
                onChange={(e) => updateDevicesBrands({ enablePinCodeField: e.target.checked })}
              />
              <span>
                <span className="block font-medium text-[var(--rb-text)]">Enable pin code field</span>
                <span className="mt-0.5 block text-xs text-zinc-500">Adds a PIN input on the device form.</span>
              </span>
            </label>
            <label className="flex items-start gap-2 text-sm">
              <input
                type="checkbox"
                checked={d.showPinCodeInDocuments}
                onChange={(e) => updateDevicesBrands({ showPinCodeInDocuments: e.target.checked })}
                disabled={!d.enablePinCodeField}
              />
              <span>
                <span className="block font-medium text-[var(--rb-text)]">Show pin code in documents</span>
                <span className="mt-0.5 block text-xs text-zinc-500">Visible in invoices, emails, and status check (if enabled).</span>
              </span>
            </label>
          </div>
        </div>
      </div>

      <div>
        <div className="text-sm font-semibold text-[var(--rb-text)]">Labels</div>
        <div className="mt-1 text-xs text-zinc-500">Customize how fields are named across the app.</div>
        <div className="mt-2 grid gap-3 sm:grid-cols-2">
          <div className="space-y-1">
            <label className="text-sm font-medium">Note label</label>
            <Input value={d.labels.note} onChange={(e) => updateDevicesBrands({ labels: { ...d.labels, note: e.target.value } })} placeholder="Note" />
          </div>
          <div className="space-y-1">
            <label className="text-sm font-medium">PIN label</label>
            <Input value={d.labels.pin} onChange={(e) => updateDevicesBrands({ labels: { ...d.labels, pin: e.target.value } })} placeholder="PIN" />
          </div>
          <div className="space-y-1">
            <label className="text-sm font-medium">Device label</label>
            <Input value={d.labels.device} onChange={(e) => updateDevicesBrands({ labels: { ...d.labels, device: e.target.value } })} placeholder="Device" />
          </div>
          <div className="space-y-1">
            <label className="text-sm font-medium">Brand label</label>
            <Input value={d.labels.deviceBrand} onChange={(e) => updateDevicesBrands({ labels: { ...d.labels, deviceBrand: e.target.value } })} placeholder="Brand" />
          </div>
          <div className="space-y-1">
            <label className="text-sm font-medium">Type label</label>
            <Input value={d.labels.deviceType} onChange={(e) => updateDevicesBrands({ labels: { ...d.labels, deviceType: e.target.value } })} placeholder="Type" />
          </div>
          <div className="space-y-1">
            <label className="text-sm font-medium">IMEI label</label>
            <Input value={d.labels.imei} onChange={(e) => updateDevicesBrands({ labels: { ...d.labels, imei: e.target.value } })} placeholder="IMEI" />
          </div>
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
            onRowClick={isMock ? undefined : (row) => openEditField(row)}
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

          {fields.length === 0 ? (
            <div className="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-[var(--rb-surface-muted)] px-3 py-2">
              <div className="text-sm text-zinc-700">Add your first field to capture extra device info (e.g. Password, Pattern, Account email).</div>
              <Button variant="outline" size="sm" disabled={isMock} onClick={openAddField}>
                Add field
              </Button>
            </div>
          ) : null}
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
          <div className="space-y-1">
            <label className="text-sm font-medium">Pickup charge</label>
            <Input
              value={d.pickupCharge}
              onChange={(e) => updateDevicesBrands({ pickupCharge: e.target.value })}
              placeholder="Pickup charge"
              disabled={!d.pickupDeliveryEnabled}
            />
          </div>
          <div className="space-y-1">
            <label className="text-sm font-medium">Delivery charge</label>
            <Input
              value={d.deliveryCharge}
              onChange={(e) => updateDevicesBrands({ deliveryCharge: e.target.value })}
              placeholder="Delivery charge"
              disabled={!d.pickupDeliveryEnabled}
            />
          </div>
        </div>
      </div>

      <div>
        <div className="text-sm font-semibold text-[var(--rb-text)]">Rental</div>
        <div className="mt-2 grid gap-3 sm:grid-cols-2">
          <label className="sm:col-span-2 flex items-center gap-2 text-sm">
            <input type="checkbox" checked={d.rentalEnabled} onChange={(e) => updateDevicesBrands({ rentalEnabled: e.target.checked })} />
            Rental enabled
          </label>
          <div className="space-y-1">
            <label className="text-sm font-medium">Per day</label>
            <Input
              value={d.rentalPerDay}
              onChange={(e) => updateDevicesBrands({ rentalPerDay: e.target.value })}
              placeholder="Per-day"
              disabled={!d.rentalEnabled}
            />
          </div>
          <div className="space-y-1">
            <label className="text-sm font-medium">Per week</label>
            <Input
              value={d.rentalPerWeek}
              onChange={(e) => updateDevicesBrands({ rentalPerWeek: e.target.value })}
              placeholder="Per-week"
              disabled={!d.rentalEnabled}
            />
          </div>
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
            <Input
              value={fieldDraft.label}
              onChange={(e) => {
                setFieldError(null);
                setFieldDraft((p) => ({ ...p, label: e.target.value }));
              }}
              placeholder="e.g. Password"
              autoFocus
              onKeyDown={(e) => {
                if (e.key === "Enter") {
                  e.preventDefault();
                  saveField();
                }
              }}
            />
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
