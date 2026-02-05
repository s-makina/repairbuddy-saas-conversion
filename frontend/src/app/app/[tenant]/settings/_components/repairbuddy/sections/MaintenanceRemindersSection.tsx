"use client";

import React, { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { Button } from "@/components/ui/Button";
import { Badge } from "@/components/ui/Badge";
import { Card, CardContent } from "@/components/ui/Card";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { Input } from "@/components/ui/Input";
import { Modal } from "@/components/ui/Modal";
import { Select } from "@/components/ui/Select";
import { SectionShell } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/SectionShell";
import type { RepairBuddySettingsDraft } from "@/app/app/[tenant]/settings/_components/repairbuddy/types";
import { ApiError, apiFetch } from "@/lib/api";
import {
  createMaintenanceReminder,
  deleteMaintenanceReminder,
  listMaintenanceReminders,
  testMaintenanceReminder,
  updateMaintenanceReminder,
  type MaintenanceReminderRule,
  type MaintenanceReminderUpsertInput,
} from "@/lib/repairbuddy-maintenance-reminders";

type DeviceType = { id: number; name: string; is_active?: boolean };
type DeviceBrand = { id: number; name: string; is_active?: boolean };

function yesNo(v: boolean) {
  return v ? "Yes" : "No";
}

function enabledVariant(v: boolean): "success" | "warning" {
  return v ? "success" : "warning";
}

export function MaintenanceRemindersSection({
  tenantSlug,
  draft,
  isMock,
}: {
  tenantSlug: string;
  draft: RepairBuddySettingsDraft;
  isMock: boolean;
}) {
  void draft;
  void isMock;

  const [addOpen, setAddOpen] = useState(false);
  const [testOpen, setTestOpen] = useState(false);
  const [editing, setEditing] = useState<MaintenanceReminderRule | null>(null);
  const [testTarget, setTestTarget] = useState<MaintenanceReminderRule | null>(null);
  const [query, setQuery] = useState("");
  const [pageIndex, setPageIndex] = useState(0);
  const [pageSize, setPageSize] = useState(10);

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [reminders, setReminders] = useState<MaintenanceReminderRule[]>([]);

  const [deviceTypes, setDeviceTypes] = useState<DeviceType[]>([]);
  const [deviceBrands, setDeviceBrands] = useState<DeviceBrand[]>([]);

  const refresh = React.useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await listMaintenanceReminders(String(tenantSlug), { q: query.trim() || undefined });
      setReminders(Array.isArray(res.reminders) ? res.reminders : []);
    } catch (e) {
      setError(e instanceof Error ? e.message : "Failed to load reminders.");
    } finally {
      setLoading(false);
    }
  }, [query, tenantSlug]);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  useEffect(() => {
    let alive = true;

    async function loadTargets() {
      try {
        const [typesRes, brandsRes] = await Promise.all([
          apiFetch<{ device_types: DeviceType[] }>(`/api/${String(tenantSlug)}/app/repairbuddy/device-types?limit=200`),
          apiFetch<{ device_brands: DeviceBrand[] }>(`/api/${String(tenantSlug)}/app/repairbuddy/device-brands?limit=200`),
        ]);
        if (!alive) return;
        setDeviceTypes(Array.isArray(typesRes.device_types) ? typesRes.device_types : []);
        setDeviceBrands(Array.isArray(brandsRes.device_brands) ? brandsRes.device_brands : []);
      } catch {
        if (!alive) return;
        setDeviceTypes([]);
        setDeviceBrands([]);
      }
    }

    void loadTargets();
    return () => {
      alive = false;
    };
  }, [tenantSlug]);

  const totalRows = reminders.length;
  const pageRows = useMemo(() => {
    const start = pageIndex * pageSize;
    const end = start + pageSize;
    return reminders.slice(start, end);
  }, [pageIndex, pageSize, reminders]);

  const columns = useMemo<Array<DataTableColumn<MaintenanceReminderRule>>>(
    () => [
      {
        id: "name",
        header: "Name",
        cell: (r) => (
          <div className="min-w-0">
            <div className="truncate font-medium text-[var(--rb-text)]">{r.name}</div>
            {r.description ? <div className="truncate text-xs text-zinc-600">{r.description}</div> : null}
          </div>
        ),
        className: "min-w-[240px]",
      },
      {
        id: "interval",
        header: "Interval",
        cell: (r) => <div className="text-sm text-zinc-700">{r.interval_days} days</div>,
        className: "whitespace-nowrap",
      },
      {
        id: "type",
        header: "Device Type",
        cell: (r) => <div className="text-sm text-zinc-700">{r.device_type_name ?? "All"}</div>,
        className: "min-w-[160px]",
      },
      {
        id: "brand",
        header: "Brand",
        cell: (r) => <div className="text-sm text-zinc-700">{r.device_brand_name ?? "All"}</div>,
        className: "min-w-[160px]",
      },
      {
        id: "email",
        header: "Email",
        cell: (r) => <Badge variant={enabledVariant(r.email_enabled)}>{yesNo(r.email_enabled)}</Badge>,
        className: "whitespace-nowrap",
      },
      {
        id: "sms",
        header: "SMS",
        cell: (r) => <Badge variant={enabledVariant(r.sms_enabled)}>{yesNo(r.sms_enabled)}</Badge>,
        className: "whitespace-nowrap",
      },
      {
        id: "enabled",
        header: "Reminder",
        cell: (r) => <Badge variant={enabledVariant(r.reminder_enabled)}>{r.reminder_enabled ? "Enabled" : "Disabled"}</Badge>,
        className: "whitespace-nowrap",
      },
      {
        id: "last_run",
        header: "Last run",
        cell: (r) => <div className="text-sm text-zinc-700">{r.last_executed_at ? new Date(r.last_executed_at).toLocaleString() : "—"}</div>,
        className: "whitespace-nowrap",
      },
      {
        id: "actions",
        header: "Actions",
        cell: (r) => (
          <div className="flex items-center gap-2">
            <Button
              size="sm"
              variant="outline"
              onClick={() => {
                setEditing(r);
                setAddOpen(true);
              }}
            >
              Edit
            </Button>
            <Button
              size="sm"
              variant="outline"
              onClick={() => {
                setTestTarget(r);
                setTestOpen(true);
              }}
            >
              Send test
            </Button>
            <Button asChild size="sm" variant="outline">
              <Link href={`/app/${String(tenantSlug)}/reminder-logs?reminder_id=${r.id}`}>Logs</Link>
            </Button>
          </div>
        ),
        className: "whitespace-nowrap",
      },
    ],
    [tenantSlug],
  );

  async function onDelete(reminder: MaintenanceReminderRule) {
    if (!globalThis.confirm(`Delete reminder "${reminder.name}"?`)) return;
    setError(null);
    try {
      await deleteMaintenanceReminder(String(tenantSlug), reminder.id);
      await refresh();
    } catch (e) {
      setError(e instanceof Error ? e.message : "Delete failed.");
    }
  }

  return (
    <SectionShell title="Maintenance Reminders" description="Automated service follow-ups based on delivery date.">
      <div className="flex flex-wrap items-center justify-end gap-2">
        <Button
          variant="outline"
          onClick={() => {
            setEditing(null);
            setAddOpen(true);
          }}
        >
          Add reminder
        </Button>
      </div>

      <Card className="shadow-none">
        <CardContent className="pt-5">
          <DataTable
            title="Reminders"
            data={pageRows}
            columns={columns}
            getRowId={(row) => row.id}
            loading={loading}
            emptyMessage="No reminders configured."
            search={{ placeholder: "Search reminders..." }}
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

      {error ? <div className="text-sm text-red-600">{error}</div> : null}

      <Modal
        open={addOpen}
        onClose={() => {
          setAddOpen(false);
          setEditing(null);
        }}
        title={editing ? `Edit reminder · ${editing.name}` : "Add reminder"}
        footer={
          <div className="flex items-center justify-between gap-2">
            <div>
              {editing ? (
                <Button variant="outline" onClick={() => void onDelete(editing)}>
                  Delete
                </Button>
              ) : null}
            </div>
            <div className="flex items-center justify-end gap-2">
              <Button
                variant="outline"
                onClick={() => {
                  setAddOpen(false);
                  setEditing(null);
                }}
              >
              Close
            </Button>
              <Button form="maintenanceReminderForm" type="submit">
                Save
              </Button>
            </div>
          </div>
        }
      >
        <ReminderForm
          tenantSlug={tenantSlug}
          deviceTypes={deviceTypes}
          deviceBrands={deviceBrands}
          initial={editing}
          onSaved={async () => {
            setAddOpen(false);
            setEditing(null);
            await refresh();
          }}
          onError={(msg) => setError(msg)}
        />
      </Modal>

      <Modal
        open={testOpen}
        onClose={() => {
          setTestOpen(false);
          setTestTarget(null);
        }}
        title={testTarget ? `Send test · ${testTarget.name}` : "Send test"}
        footer={
          <div className="flex items-center justify-end gap-2">
            <Button
              variant="outline"
              onClick={() => {
                setTestOpen(false);
                setTestTarget(null);
              }}
            >
              Close
            </Button>
          </div>
        }
      >
        <TestReminderForm
          tenantSlug={tenantSlug}
          reminder={testTarget}
          onError={(msg) => setError(msg)}
        />
      </Modal>
    </SectionShell>
  );
}

function ReminderForm({
  tenantSlug,
  deviceTypes,
  deviceBrands,
  initial,
  onSaved,
  onError,
}: {
  tenantSlug: string;
  deviceTypes: DeviceType[];
  deviceBrands: DeviceBrand[];
  initial: MaintenanceReminderRule | null;
  onSaved: () => void | Promise<void>;
  onError: (msg: string) => void;
}) {
  const [busy, setBusy] = useState(false);
  const [formError, setFormError] = useState<string | null>(null);

  const [name, setName] = useState(initial?.name ?? "");
  const [description, setDescription] = useState(initial?.description ?? "");
  const [intervalDays, setIntervalDays] = useState<number>(initial?.interval_days ?? 180);
  const [deviceTypeId, setDeviceTypeId] = useState<number | 0>(initial?.device_type_id ?? 0);
  const [deviceBrandId, setDeviceBrandId] = useState<number | 0>(initial?.device_brand_id ?? 0);
  const [reminderEnabled, setReminderEnabled] = useState<boolean>(initial?.reminder_enabled ?? true);
  const [emailEnabled, setEmailEnabled] = useState<boolean>(initial?.email_enabled ?? false);
  const [smsEnabled, setSmsEnabled] = useState<boolean>(initial?.sms_enabled ?? false);
  const [emailBody, setEmailBody] = useState<string>(initial?.email_body ?? "");
  const [smsBody, setSmsBody] = useState<string>(initial?.sms_body ?? "");

  useEffect(() => {
    setName(initial?.name ?? "");
    setDescription(initial?.description ?? "");
    setIntervalDays(initial?.interval_days ?? 180);
    setDeviceTypeId(initial?.device_type_id ?? 0);
    setDeviceBrandId(initial?.device_brand_id ?? 0);
    setReminderEnabled(initial?.reminder_enabled ?? true);
    setEmailEnabled(initial?.email_enabled ?? false);
    setSmsEnabled(initial?.sms_enabled ?? false);
    setEmailBody(initial?.email_body ?? "");
    setSmsBody(initial?.sms_body ?? "");
  }, [initial]);

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    setFormError(null);

    const nextName = name.trim();
    if (!nextName) {
      setFormError("Name is required.");
      return;
    }
    if (!Number.isFinite(intervalDays) || intervalDays <= 0) {
      setFormError("Interval must be at least 1 day.");
      return;
    }
    if (emailEnabled && !emailBody.trim()) {
      setFormError("Email body is required when email is enabled.");
      return;
    }
    if (smsEnabled && !smsBody.trim()) {
      setFormError("SMS body is required when SMS is enabled.");
      return;
    }

    const payload: MaintenanceReminderUpsertInput = {
      name: nextName,
      description: description.trim() ? description.trim() : null,
      interval_days: intervalDays,
      device_type_id: deviceTypeId ? deviceTypeId : null,
      device_brand_id: deviceBrandId ? deviceBrandId : null,
      reminder_enabled: reminderEnabled,
      email_enabled: emailEnabled,
      sms_enabled: smsEnabled,
      email_body: emailBody.trim() ? emailBody : null,
      sms_body: smsBody.trim() ? smsBody : null,
    };

    setBusy(true);
    try {
      if (initial) {
        await updateMaintenanceReminder(String(tenantSlug), initial.id, payload);
      } else {
        await createMaintenanceReminder(String(tenantSlug), payload);
      }
      await onSaved();
    } catch (err) {
      const msg = err instanceof ApiError ? err.message : err instanceof Error ? err.message : "Save failed.";
      setFormError(msg);
      onError(msg);
    } finally {
      setBusy(false);
    }
  }

  return (
    <form id="maintenanceReminderForm" onSubmit={onSubmit} className="space-y-4">
      {formError ? <div className="text-sm text-red-600">{formError}</div> : null}

      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div className="space-y-1">
          <label className="text-sm font-medium">Name</label>
          <Input value={name} onChange={(e) => setName(e.target.value)} placeholder="e.g. 6-month service" disabled={busy} />
        </div>

        <div className="space-y-1">
          <label className="text-sm font-medium">Interval (days)</label>
          <Input
            type="number"
            min={1}
            max={3650}
            value={String(intervalDays)}
            onChange={(e) => setIntervalDays(Number(e.target.value || 0))}
            disabled={busy}
          />
        </div>
      </div>

      <div className="space-y-1">
        <label className="text-sm font-medium">Description</label>
        <Input value={description} onChange={(e) => setDescription(e.target.value)} placeholder="Optional" disabled={busy} />
      </div>

      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div className="space-y-1">
          <label className="text-sm font-medium">Device Type</label>
          <Select value={String(deviceTypeId)} onChange={(e) => setDeviceTypeId(Number(e.target.value))} disabled={busy}>
            <option value="0">All</option>
            {deviceTypes
              .filter((t) => t && typeof t.id === "number")
              .map((t) => (
                <option key={t.id} value={String(t.id)}>
                  {t.name}
                </option>
              ))}
          </Select>
        </div>
        <div className="space-y-1">
          <label className="text-sm font-medium">Brand</label>
          <Select value={String(deviceBrandId)} onChange={(e) => setDeviceBrandId(Number(e.target.value))} disabled={busy}>
            <option value="0">All</option>
            {deviceBrands
              .filter((b) => b && typeof b.id === "number")
              .map((b) => (
                <option key={b.id} value={String(b.id)}>
                  {b.name}
                </option>
              ))}
          </Select>
        </div>
      </div>

      <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <label className="flex items-center gap-2 text-sm">
          <input type="checkbox" checked={reminderEnabled} onChange={(e) => setReminderEnabled(e.target.checked)} disabled={busy} />
          <span>Reminder enabled</span>
        </label>
        <label className="flex items-center gap-2 text-sm">
          <input type="checkbox" checked={emailEnabled} onChange={(e) => setEmailEnabled(e.target.checked)} disabled={busy} />
          <span>Email enabled</span>
        </label>
        <label className="flex items-center gap-2 text-sm">
          <input type="checkbox" checked={smsEnabled} onChange={(e) => setSmsEnabled(e.target.checked)} disabled={busy} />
          <span>SMS enabled</span>
        </label>
      </div>

      <div className="space-y-1">
        <div className="text-xs text-zinc-500">Template variables: {{customer_name}} {{device_name}} {{unsubscribe_device}}</div>
      </div>

      <div className="space-y-1">
        <label className="text-sm font-medium">Email body</label>
        <textarea
          className="w-full rounded-[var(--rb-radius-sm)] border border-zinc-300 bg-white px-3 py-2 text-sm text-[var(--rb-text)] outline-none transition focus-visible:ring-2 focus-visible:ring-[color:color-mix(in_srgb,var(--rb-orange),white_65%)]"
          rows={6}
          value={emailBody}
          onChange={(e) => setEmailBody(e.target.value)}
          placeholder="Write your email message..."
          disabled={busy}
        />
      </div>

      <div className="space-y-1">
        <label className="text-sm font-medium">SMS body</label>
        <textarea
          className="w-full rounded-[var(--rb-radius-sm)] border border-zinc-300 bg-white px-3 py-2 text-sm text-[var(--rb-text)] outline-none transition focus-visible:ring-2 focus-visible:ring-[color:color-mix(in_srgb,var(--rb-orange),white_65%)]"
          rows={4}
          value={smsBody}
          onChange={(e) => setSmsBody(e.target.value)}
          placeholder="Write your SMS message..."
          disabled={busy}
        />
      </div>
    </form>
  );
}

function TestReminderForm({
  tenantSlug,
  reminder,
  onError,
}: {
  tenantSlug: string;
  reminder: MaintenanceReminderRule | null;
  onError: (msg: string) => void;
}) {
  const [email, setEmail] = useState("");
  const [phone, setPhone] = useState("");
  const [busy, setBusy] = useState(false);
  const [localError, setLocalError] = useState<string | null>(null);
  const [preview, setPreview] = useState<{ email: string; sms: string } | null>(null);
  const [status, setStatus] = useState<{ email: string; sms: string } | null>(null);

  useEffect(() => {
    setLocalError(null);
    setPreview(null);
    setStatus(null);
  }, [reminder]);

  async function onSend() {
    if (!reminder) return;
    setLocalError(null);
    setPreview(null);
    setStatus(null);

    const nextEmail = email.trim();
    const nextPhone = phone.trim();

    if (!nextEmail && !nextPhone) {
      setLocalError("Email or phone is required.");
      return;
    }

    setBusy(true);
    try {
      const res = await testMaintenanceReminder(String(tenantSlug), reminder.id, {
        email: nextEmail || null,
        phone: nextPhone || null,
      });
      setPreview(res.preview);
      setStatus(res.status);
    } catch (e) {
      const msg = e instanceof ApiError ? e.message : e instanceof Error ? e.message : "Test failed.";
      setLocalError(msg);
      onError(msg);
    } finally {
      setBusy(false);
    }
  }

  return (
    <div className="space-y-3">
      {localError ? <div className="text-sm text-red-600">{localError}</div> : null}

      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div className="space-y-1">
          <label className="text-sm font-medium">Email</label>
          <Input value={email} onChange={(e) => setEmail(e.target.value)} placeholder="name@company.com" disabled={busy} />
        </div>
        <div className="space-y-1">
          <label className="text-sm font-medium">Phone</label>
          <Input value={phone} onChange={(e) => setPhone(e.target.value)} placeholder="+1 555 000 0000" disabled={busy} />
        </div>
      </div>

      <div className="flex items-center justify-end">
        <Button onClick={() => void onSend()} disabled={busy || !reminder}>
          {busy ? "Sending..." : "Send test"}
        </Button>
      </div>

      {status ? (
        <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
          <div className="text-xs text-zinc-600">Email status: {status.email}</div>
          <div className="text-xs text-zinc-600">SMS status: {status.sms}</div>
        </div>
      ) : null}

      {preview ? (
        <div className="space-y-3">
          <div className="rounded border border-zinc-200 bg-white p-3">
            <div className="text-xs font-semibold text-zinc-700">Email preview</div>
            <pre className="mt-2 whitespace-pre-wrap text-xs text-zinc-700">{preview.email || "(empty)"}</pre>
          </div>
          <div className="rounded border border-zinc-200 bg-white p-3">
            <div className="text-xs font-semibold text-zinc-700">SMS preview</div>
            <pre className="mt-2 whitespace-pre-wrap text-xs text-zinc-700">{preview.sms || "(empty)"}</pre>
          </div>
        </div>
      ) : null}
    </div>
  );
}
