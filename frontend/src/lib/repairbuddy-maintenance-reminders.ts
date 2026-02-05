import { apiFetch } from "@/lib/api";

export type MaintenanceReminderRule = {
  id: number;
  name: string;
  description: string | null;
  interval_days: number;
  device_type_id: number | null;
  device_brand_id: number | null;
  device_type_name?: string | null;
  device_brand_name?: string | null;
  email_enabled: boolean;
  sms_enabled: boolean;
  reminder_enabled: boolean;
  email_body: string | null;
  sms_body: string | null;
  last_executed_at: string | null;
  created_at: string;
  updated_at: string;
};

export async function listMaintenanceReminders(business: string, args?: { q?: string }): Promise<{ reminders: MaintenanceReminderRule[] }> {
  const qs = new URLSearchParams();
  if (args?.q) qs.set("q", args.q);
  const suffix = qs.toString() ? `?${qs.toString()}` : "";
  return apiFetch<{ reminders: MaintenanceReminderRule[] }>(`/api/${business}/app/repairbuddy/maintenance-reminders${suffix}`);
}

export type MaintenanceReminderUpsertInput = {
  name: string;
  description?: string | null;
  interval_days: number;
  device_type_id?: number | null;
  device_brand_id?: number | null;
  email_enabled?: boolean;
  sms_enabled?: boolean;
  reminder_enabled?: boolean;
  email_body?: string | null;
  sms_body?: string | null;
};

export async function createMaintenanceReminder(business: string, input: MaintenanceReminderUpsertInput): Promise<{ reminder: MaintenanceReminderRule }> {
  return apiFetch(`/api/${business}/app/repairbuddy/maintenance-reminders`, {
    method: "POST",
    body: input,
  });
}

export async function updateMaintenanceReminder(
  business: string,
  id: number,
  input: Partial<MaintenanceReminderUpsertInput>,
): Promise<{ reminder: MaintenanceReminderRule }> {
  return apiFetch(`/api/${business}/app/repairbuddy/maintenance-reminders/${id}`, {
    method: "PATCH",
    body: input,
  });
}

export async function deleteMaintenanceReminder(business: string, id: number): Promise<{ ok: boolean }> {
  return apiFetch(`/api/${business}/app/repairbuddy/maintenance-reminders/${id}`, {
    method: "DELETE",
  });
}

export async function testMaintenanceReminder(
  business: string,
  id: number,
  input: { email?: string | null; phone?: string | null },
): Promise<{ ok: boolean; unsubscribe_url: string; preview: { email: string; sms: string }; status: { email: string; sms: string } }> {
  return apiFetch(`/api/${business}/app/repairbuddy/maintenance-reminders/${id}/test`, {
    method: "POST",
    body: input,
  });
}
