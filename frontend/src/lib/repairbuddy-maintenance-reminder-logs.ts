import { apiFetch } from "@/lib/api";

export type MaintenanceReminderLogRow = {
  id: number;
  created_at: string;
  reminder: { id: number; name: string } | null;
  job: { id: number; case_number: string; title: string } | null;
  customer: { id: number; name: string } | null;
  channel: "email" | "sms" | string;
  to_address: string | null;
  status: "sent" | "failed" | "skipped" | string;
  error_message: string | null;
};

export type MaintenanceReminderLogsResponse = {
  logs: MaintenanceReminderLogRow[];
  meta: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
};

export async function listMaintenanceReminderLogs(
  business: string,
  args: {
    q?: string;
    reminder_id?: number | null;
    job_id?: number | null;
    date_from?: string | null;
    date_to?: string | null;
    page?: number;
    per_page?: number;
  } = {},
): Promise<MaintenanceReminderLogsResponse> {
  const qs = new URLSearchParams();
  if (args.q) qs.set("q", args.q);
  if (args.reminder_id) qs.set("reminder_id", String(args.reminder_id));
  if (args.job_id) qs.set("job_id", String(args.job_id));
  if (args.date_from) qs.set("date_from", args.date_from);
  if (args.date_to) qs.set("date_to", args.date_to);
  if (args.page) qs.set("page", String(args.page));
  if (args.per_page) qs.set("per_page", String(args.per_page));

  const suffix = qs.toString() ? `?${qs.toString()}` : "";

  return apiFetch<MaintenanceReminderLogsResponse>(`/api/${business}/app/repairbuddy/maintenance-reminder-logs${suffix}`);
}
