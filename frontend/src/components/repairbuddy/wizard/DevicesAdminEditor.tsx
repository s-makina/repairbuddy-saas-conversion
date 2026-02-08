"use client";

import React from "react";
import AsyncSelect from "react-select/async";
import { Trash2 } from "lucide-react";
import { Button } from "@/components/ui/Button";
import { Input } from "@/components/ui/Input";

export type WizardDeviceOption = {
  value: number;
  label: string;
};

export type WizardDeviceDraft = {
  device_id: number | null;
  option: WizardDeviceOption | null;
  serial: string;
  pin: string;
  notes: string;
  extra_fields: Array<{ key: string; label: string; value_text: string }>;
};

export type WizardAdditionalDeviceField = {
  key: string;
  label: string;
};

export function DevicesAdminEditor({
  value,
  onChange,
  deviceOptions,
  loadDeviceOptions,
  disabled,
  idPrefix,
  showPin,
  serialLabel,
  pinLabel,
  notesLabel,
  addButtonLabel,
  createEmptyRow,
  additionalFields,
}: {
  value: WizardDeviceDraft[];
  onChange: (next: WizardDeviceDraft[]) => void;
  deviceOptions: WizardDeviceOption[];
  loadDeviceOptions: (inputValue: string) => Promise<WizardDeviceOption[]>;
  disabled: boolean;
  idPrefix: string;
  showPin: boolean;
  serialLabel: string;
  pinLabel: string;
  notesLabel: string;
  addButtonLabel: string;
  createEmptyRow: () => WizardDeviceDraft;
  additionalFields?: WizardAdditionalDeviceField[];
}) {
  return (
    <div className="space-y-4">
      <div className="rounded-[var(--rb-radius-sm)] border border-dashed border-zinc-300 bg-white p-3">
        <div className="grid grid-cols-1 gap-4">
          {value.map((d, idx) => (
            <div key={idx} className="rounded-[var(--rb-radius-md)] border border-zinc-200 bg-zinc-50 p-3">
              <div className="mb-3 flex items-center justify-between gap-3">
                <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Device {idx + 1}</div>
                <span
                  role="button"
                  tabIndex={disabled ? -1 : 0}
                  aria-label="Remove device"
                  title="Remove device"
                  onClick={() => {
                    if (disabled) return;
                    onChange(value.filter((_, i) => i !== idx));
                  }}
                  onKeyDown={(e) => {
                    if (disabled) return;
                    if (e.key === "Enter" || e.key === " ") {
                      e.preventDefault();
                      onChange(value.filter((_, i) => i !== idx));
                    }
                  }}
                  className={
                    "inline-flex h-9 w-9 items-center justify-center rounded-[var(--rb-radius-sm)] border border-zinc-300 bg-white text-zinc-700 " +
                    (disabled ? "pointer-events-none opacity-50" : "cursor-pointer hover:bg-zinc-50")
                  }
                >
                  <Trash2 className="h-4 w-4" aria-hidden="true" />
                </span>
              </div>

              <div className="grid grid-cols-1 gap-3 md:grid-cols-3 md:items-end">
                <div>
                  <div className="mb-1 text-xs text-zinc-600">Device</div>
                  <AsyncSelect
                    inputId={`${idPrefix}_device_${idx}`}
                    instanceId={`${idPrefix}_device_${idx}`}
                    cacheOptions
                    defaultOptions={deviceOptions}
                    loadOptions={loadDeviceOptions}
                    isClearable
                    isSearchable
                    value={d.option}
                    onChange={(opt) => {
                      const next = (opt as WizardDeviceOption | null) ?? null;
                      onChange(
                        value.map((x, i) =>
                          i === idx
                            ? {
                                ...x,
                                option: next,
                                device_id: typeof next?.value === "number" ? next.value : null,
                              }
                            : x,
                        ),
                      );
                    }}
                    isDisabled={disabled}
                    placeholder="Search..."
                    classNamePrefix="rb-select"
                    styles={{
                      control: (base) => ({
                        ...base,
                        borderRadius: "var(--rb-radius-sm)",
                        borderColor: "#d4d4d8",
                        minHeight: 40,
                        boxShadow: "none",
                      }),
                      menu: (base) => ({
                        ...base,
                        zIndex: 50,
                      }),
                    }}
                  />
                </div>

                <div>
                  <div className="mb-1 text-xs text-zinc-600">{serialLabel}</div>
                  <Input
                    value={d.serial}
                    onChange={(e) => {
                      const v = e.target.value;
                      onChange(value.map((x, i) => (i === idx ? { ...x, serial: v } : x)));
                    }}
                    disabled={disabled}
                  />
                </div>

                <div>
                  <div className="mb-1 text-xs text-zinc-600">{showPin ? pinLabel : notesLabel}</div>
                  <Input
                    value={showPin ? d.pin : d.notes}
                    onChange={(e) => {
                      const v = e.target.value;
                      onChange(value.map((x, i) => (i === idx ? (showPin ? { ...x, pin: v } : { ...x, notes: v }) : x)));
                    }}
                    disabled={disabled}
                  />
                </div>

                {showPin ? (
                  <div>
                    <div className="mb-1 text-xs text-zinc-600">{notesLabel}</div>
                    <Input
                      value={d.notes}
                      onChange={(e) => {
                        const v = e.target.value;
                        onChange(value.map((x, i) => (i === idx ? { ...x, notes: v } : x)));
                      }}
                      disabled={disabled}
                    />
                  </div>
                ) : null}

                {Array.isArray(additionalFields) && additionalFields.length > 0 ? (
                  <>
                    {additionalFields.map((f) => {
                      const existing = Array.isArray(d.extra_fields)
                        ? d.extra_fields.find((x) => x && typeof x === "object" && x.key === f.key)
                        : undefined;
                      const valueText = typeof existing?.value_text === "string" ? existing.value_text : "";

                      return (
                        <div key={f.key}>
                          <div className="mb-1 text-xs text-zinc-600">{f.label}</div>
                          <Input
                            value={valueText}
                            onChange={(e) => {
                              const v = e.target.value;
                              onChange(
                                value.map((x, i) => {
                                  if (i !== idx) return x;

                                  const prev = Array.isArray(x.extra_fields) ? x.extra_fields : [];
                                  const next = prev.some((row) => row.key === f.key)
                                    ? prev.map((row) => (row.key === f.key ? { ...row, value_text: v } : row))
                                    : [...prev, { key: f.key, label: f.label, value_text: v }];

                                  return {
                                    ...x,
                                    extra_fields: next,
                                  };
                                }),
                              );
                            }}
                            disabled={disabled}
                          />
                        </div>
                      );
                    })}
                  </>
                ) : null}
              </div>
            </div>
          ))}
        </div>
      </div>

      <div>
        <Button
          type="button"
          variant="outline"
          size="sm"
          disabled={disabled}
          onClick={() => {
            onChange([...value, createEmptyRow()]);
          }}
        >
          {addButtonLabel}
        </Button>
      </div>
    </div>
  );
}
