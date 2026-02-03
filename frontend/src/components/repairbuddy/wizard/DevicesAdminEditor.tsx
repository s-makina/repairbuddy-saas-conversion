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
}) {
  return (
    <div className="space-y-4">
      <div className="rounded-[var(--rb-radius-sm)] border border-dashed border-zinc-300 bg-white p-3">
        <div className="grid grid-cols-1 gap-3">
          {value.map((d, idx) => (
            <div
              key={idx}
              className={
                showPin
                  ? "grid grid-cols-1 gap-3 md:grid-cols-[1fr_1fr_1fr_1fr_auto] md:items-end"
                  : "grid grid-cols-1 gap-3 md:grid-cols-[1fr_1fr_1fr_auto] md:items-end"
              }
            >
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

              {showPin ? (
                <div>
                  <div className="mb-1 text-xs text-zinc-600">{pinLabel}</div>
                  <Input
                    value={d.pin}
                    onChange={(e) => {
                      const v = e.target.value;
                      onChange(value.map((x, i) => (i === idx ? { ...x, pin: v } : x)));
                    }}
                    disabled={disabled}
                  />
                </div>
              ) : null}

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

              <div className="flex md:justify-end">
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
                    "inline-flex h-10 w-10 items-center justify-center rounded-[var(--rb-radius-sm)] border border-zinc-300 bg-white text-zinc-700 " +
                    (disabled ? "pointer-events-none opacity-50" : "cursor-pointer hover:bg-zinc-50")
                  }
                >
                  <Trash2 className="h-4 w-4" aria-hidden="true" />
                </span>
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
