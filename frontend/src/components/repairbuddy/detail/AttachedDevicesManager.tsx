"use client";

import React from "react";
import { Alert } from "@/components/ui/Alert";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";

export type AttachedDeviceExtraField = {
  key: string;
  label: string;
  type: string;
  value_text: string;
};

export type AttachedDeviceRow = {
  id: number;
  customer_device_id: number;
  label: string;
  serial: string | null;
  notes: string | null;
  extra_fields?: AttachedDeviceExtraField[] | null;
};

export type CustomerDeviceRow = {
  id: number;
  label: string;
  serial: string | null;
};

export function AttachedDevicesManager({
  devicesError,
  attachError,
  devicesLoading,
  customerDevices,
  attachedDevices,
  attachId,
  setAttachId,
  attachBusy,
  onAttach,
  onDetach,
}: {
  devicesError: string | null;
  attachError: string | null;
  devicesLoading: boolean;
  customerDevices: CustomerDeviceRow[];
  attachedDevices: AttachedDeviceRow[];
  attachId: string;
  setAttachId: (next: string) => void;
  attachBusy: boolean;
  onAttach: () => void;
  onDetach: (attachedDeviceId: number) => void;
}) {
  return (
    <div className="space-y-4">
      {devicesError ? (
        <Alert variant="danger" title="Could not load devices">
          {devicesError}
        </Alert>
      ) : null}

      {attachError ? (
        <Alert variant="danger" title="Could not update devices">
          {attachError}
        </Alert>
      ) : null}

      {devicesLoading ? <div className="text-sm text-zinc-500">Loading devices...</div> : null}

      <Card className="shadow-none">
        <CardContent className="pt-5">
          <div className="text-sm font-semibold text-[var(--rb-text)]">Attach customer device</div>
          <div className="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center">
            <select
              value={attachId}
              onChange={(e) => setAttachId(e.target.value)}
              disabled={attachBusy || customerDevices.length === 0}
              className="h-10 w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 text-sm"
            >
              <option value="">Select a customer device...</option>
              {customerDevices.map((d) => (
                <option key={d.id} value={String(d.id)}>
                  {d.label}
                  {d.serial ? ` (Serial: ${d.serial})` : ""}
                </option>
              ))}
            </select>
            <Button onClick={onAttach} disabled={attachBusy || attachId.trim().length === 0}>
              {attachBusy ? "Saving..." : "Attach"}
            </Button>
          </div>
          {customerDevices.length === 0 ? <div className="mt-2 text-xs text-zinc-500">No customer devices available.</div> : null}
        </CardContent>
      </Card>

      <Card className="shadow-none">
        <CardContent className="pt-5">
          <div className="text-sm font-semibold text-[var(--rb-text)]">Attached devices</div>
          <div className="mt-4 space-y-3">
            {attachedDevices.length === 0 ? <div className="text-sm text-zinc-600">No devices attached.</div> : null}
            {attachedDevices.map((d) => (
              <div key={d.id} className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-3">
                <div className="flex flex-wrap items-start justify-between gap-3">
                  <div className="min-w-0">
                    <div className="truncate text-sm font-semibold text-[var(--rb-text)]">{d.label}</div>
                    <div className="mt-1 text-xs text-zinc-500">Serial: {d.serial ?? "—"}</div>
                    {d.notes ? <div className="mt-2 whitespace-pre-wrap text-sm text-zinc-700">{d.notes}</div> : null}
                    {Array.isArray(d.extra_fields) && d.extra_fields.length > 0 ? (
                      <div className="mt-3">
                        <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Extra fields</div>
                        <div className="mt-2 grid gap-2 sm:grid-cols-2">
                          {d.extra_fields.map((f) => (
                            <div key={f.key} className="min-w-0">
                              <div className="truncate text-xs text-zinc-500">{f.label}</div>
                              <div className="truncate text-sm text-zinc-700">{f.value_text}</div>
                            </div>
                          ))}
                        </div>
                      </div>
                    ) : null}
                  </div>
                  <div className="flex items-center gap-2">
                    <Badge variant="default">{d.customer_device_id}</Badge>
                    <Button variant="outline" size="sm" onClick={() => onDetach(d.id)} disabled={attachBusy}>
                      Remove
                    </Button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
