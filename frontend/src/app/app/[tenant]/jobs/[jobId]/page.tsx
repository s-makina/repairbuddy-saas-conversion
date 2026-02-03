"use client";

import React from "react";
import { useParams, useRouter } from "next/navigation";
import Link from "next/link";
import { CalendarClock, Laptop, ListChecks, MessageSquare, RefreshCw, User } from "lucide-react";
import { Button } from "@/components/ui/Button";
import { Badge } from "@/components/ui/Badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/Card";
import { Alert } from "@/components/ui/Alert";
import { DetailPageShell } from "@/components/shells/DetailPageShell";
import { useDetailTab } from "@/components/repairbuddy/detail/detailTabs";
import { AttachedDevicesManager } from "@/components/repairbuddy/detail/AttachedDevicesManager";
import { apiFetch, ApiError } from "@/lib/api";
import { formatMoney } from "@/lib/money";

type JobStatusKey = string;

type ApiJob = {
  id: number;
  case_number: string;
  title: string;
  status: JobStatusKey;
  updated_at: string;
  customer_id: number | null;
  payment_status?: string | null;
  priority?: string | null;
  pickup_date?: string | null;
  delivery_date?: string | null;
  next_service_date?: string | null;
  totals?: { currency: string; subtotal_cents: number; tax_cents: number; total_cents: number } | null;
  customer?: { id: number; name: string; email: string | null; phone: string | null; company: string | null } | null;
  assigned_technicians?: Array<{ id: number; name: string; email: string }>;
  timeline: Array<{ id: string; title: string; type: string; message?: string | null; created_at: string }>;
  messages: Array<{ id: string; author: string; body: string; created_at: string }>;
};

type ApiJobDeviceExtraField = {
  key: string;
  label: string;
  type: string;
  value_text: string;
};

type ApiJobDevice = {
  id: number;
  job_id: number;
  customer_device_id: number;
  label: string;
  serial: string | null;
  notes: string | null;
  extra_fields?: ApiJobDeviceExtraField[] | null;
  created_at: string;
};

type ApiCustomerDevice = {
  id: number;
  customer_id: number;
  device_id: number | null;
  label: string;
  serial: string | null;
  notes: string | null;
};

function statusBadgeVariant(status: JobStatusKey): "default" | "info" | "success" | "warning" | "danger" {
  if (status === "delivered" || status === "completed") return "success";
  if (status === "ready") return "warning";
  if (status === "cancelled") return "danger";
  if (status === "in_process") return "info";
  return "default";
}

function paymentBadgeVariant(status: string | null | undefined): "default" | "info" | "success" | "warning" | "danger" {
  if (!status) return "default";
  if (status === "paid") return "success";
  if (status === "partial") return "warning";
  if (status === "unpaid" || status === "due") return "danger";
  return "default";
}

function priorityBadgeVariant(priority: string | null | undefined): "default" | "info" | "success" | "warning" | "danger" {
  const p = (priority ?? "").toLowerCase();
  if (p.includes("urgent") || p.includes("critical")) return "danger";
  if (p.includes("high")) return "warning";
  if (p.includes("low")) return "info";
  return "default";
}

function formatSlugLabel(value: string | null | undefined): string {
  const v = (value ?? "").trim();
  if (!v) return "—";
  return v.replace(/_/g, " ");
}

function formatDateTime(value: string | null | undefined): string {
  if (!value) return "—";
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return "—";
  return d.toLocaleString();
}

function formatShortDate(value: string | null | undefined): string {
  if (!value) return "—";
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return "—";
  return d.toLocaleDateString();
}

export default function TenantJobDetailPage() {
  const router = useRouter();
  const params = useParams() as { tenant?: string; business?: string; jobId?: string };
  const tenantSlug = params.business ?? params.tenant;
  const jobId = params.jobId;

  const defaultTab = useDetailTab("overview");

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [job, setJob] = React.useState<ApiJob | null>(null);

  const [messageBody, setMessageBody] = React.useState<string>("");
  const [messageBusy, setMessageBusy] = React.useState(false);
  const [messageError, setMessageError] = React.useState<string | null>(null);

  const [noteBody, setNoteBody] = React.useState<string>("");
  const [noteBusy, setNoteBusy] = React.useState(false);
  const [noteError, setNoteError] = React.useState<string | null>(null);

  const [refreshKey, setRefreshKey] = React.useState(0);

  const [devicesLoading, setDevicesLoading] = React.useState(false);
  const [devicesError, setDevicesError] = React.useState<string | null>(null);
  const [jobDevices, setJobDevices] = React.useState<ApiJobDevice[]>([]);
  const [customerDevices, setCustomerDevices] = React.useState<ApiCustomerDevice[]>([]);

  const [attachId, setAttachId] = React.useState<string>("");
  const [attachBusy, setAttachBusy] = React.useState(false);
  const [attachError, setAttachError] = React.useState<string | null>(null);

  React.useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);

        if (!jobId) {
          setError("Job ID is missing.");
          setJob(null);
          return;
        }

        if (typeof tenantSlug !== "string" || tenantSlug.length === 0) {
          throw new Error("Business is missing.");
        }

        const res = await apiFetch<{ job: ApiJob }>(`/api/${tenantSlug}/app/repairbuddy/jobs/${jobId}`);
        if (!alive) return;

        setJob(res.job ?? null);
      } catch (e) {
        if (!alive) return;
        if (e instanceof ApiError && e.status === 428 && typeof tenantSlug === "string" && tenantSlug.length > 0) {
          const next = `/app/${tenantSlug}/jobs/${jobId}`;
          router.replace(`/app/${tenantSlug}/branches/select?next=${encodeURIComponent(next)}`);
          return;
        }

        setError(e instanceof Error ? e.message : "Failed to load job.");
        setJob(null);
      } finally {
        if (!alive) return;
        setLoading(false);
      }
    }

    void load();

    return () => {
      alive = false;
    };
  }, [jobId, refreshKey, router, tenantSlug]);

  React.useEffect(() => {
    let alive = true;

    async function loadDevices() {
      try {
        setDevicesLoading(true);
        setDevicesError(null);

        if (!jobId) {
          setJobDevices([]);
          setCustomerDevices([]);
          return;
        }

        if (typeof tenantSlug !== "string" || tenantSlug.length === 0) {
          setJobDevices([]);
          setCustomerDevices([]);
          return;
        }

        if (!job) {
          setJobDevices([]);
          setCustomerDevices([]);
          return;
        }

        const jobDevicesRes = await apiFetch<{ job_devices: ApiJobDevice[] }>(`/api/${tenantSlug}/app/repairbuddy/jobs/${jobId}/devices`);
        if (!alive) return;
        setJobDevices(Array.isArray(jobDevicesRes?.job_devices) ? jobDevicesRes.job_devices : []);

        if (typeof job.customer_id !== "number") {
          setCustomerDevices([]);
          return;
        }

        const customerDevicesRes = await apiFetch<{ customer_devices: ApiCustomerDevice[] }>(
          `/api/${tenantSlug}/app/repairbuddy/customer-devices?customer_id=${encodeURIComponent(String(job.customer_id))}`,
        );
        if (!alive) return;
        setCustomerDevices(Array.isArray(customerDevicesRes?.customer_devices) ? customerDevicesRes.customer_devices : []);
      } catch (e) {
        if (!alive) return;

        if (e instanceof ApiError && e.status === 428 && typeof tenantSlug === "string" && tenantSlug.length > 0) {
          const next = `/app/${tenantSlug}/jobs/${jobId}`;
          router.replace(`/app/${tenantSlug}/branches/select?next=${encodeURIComponent(next)}`);
          return;
        }

        setDevicesError(e instanceof Error ? e.message : "Failed to load devices.");
        setJobDevices([]);
        setCustomerDevices([]);
      } finally {
        if (!alive) return;
        setDevicesLoading(false);
      }
    }

    void loadDevices();

    return () => {
      alive = false;
    };
  }, [job, jobId, refreshKey, router, tenantSlug]);

  async function postMessage() {
    setMessageError("Messaging will be enabled in EPIC 3.");
  }

  async function attachDevice() {
    setAttachError(null);

    if (!attachId || attachId.trim().length === 0) {
      setAttachError("Select a customer device.");
      return;
    }

    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) {
      setAttachError("Business is missing.");
      return;
    }

    if (!jobId) {
      setAttachError("Job ID is missing.");
      return;
    }

    setAttachBusy(true);
    try {
      await apiFetch<{ job_device: unknown }>(`/api/${tenantSlug}/app/repairbuddy/jobs/${jobId}/devices`, {
        method: "POST",
        body: { customer_device_id: Number(attachId) },
      });
      setAttachId("");
      setRefreshKey((x) => x + 1);
    } catch (e) {
      if (e instanceof ApiError) {
        setAttachError(e.message);
      } else {
        setAttachError(e instanceof Error ? e.message : "Failed to attach device.");
      }
    } finally {
      setAttachBusy(false);
    }
  }

  async function detachDevice(jobDeviceId: number) {
    setAttachError(null);

    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) {
      setAttachError("Business is missing.");
      return;
    }

    if (!jobId) {
      setAttachError("Job ID is missing.");
      return;
    }

    setAttachBusy(true);
    try {
      await apiFetch<{ message: string }>(`/api/${tenantSlug}/app/repairbuddy/jobs/${jobId}/devices/${jobDeviceId}`, {
        method: "DELETE",
      });
      setRefreshKey((x) => x + 1);
    } catch (e) {
      if (e instanceof ApiError) {
        setAttachError(e.message);
      } else {
        setAttachError(e instanceof Error ? e.message : "Failed to detach device.");
      }
    } finally {
      setAttachBusy(false);
    }
  }

  async function postInternalNote() {
    setNoteError(null);

    if (!noteBody.trim()) {
      setNoteError("Note is required.");
      return;
    }

    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) {
      setNoteError("Business is missing.");
      return;
    }

    if (!jobId) {
      setNoteError("Job ID is missing.");
      return;
    }

    setNoteBusy(true);
    try {
      await apiFetch<{ event: unknown }>(`/api/${tenantSlug}/app/repairbuddy/jobs/${jobId}/events`, {
        method: "POST",
        body: { message: noteBody.trim() },
      });

      setNoteBody("");
      setRefreshKey((x) => x + 1);
    } catch (e) {
      if (e instanceof ApiError) {
        setNoteError(e.message);
      } else {
        setNoteError("Failed to post note.");
      }
    } finally {
      setNoteBusy(false);
    }
  }

  return (
    <div className="space-y-6">
      {error ? (
        <Alert variant="danger" title="Could not load job">
          {error}
        </Alert>
      ) : null}

      {loading ? <div className="text-sm text-zinc-500">Loading job...</div> : null}

      {job ? (
        <DetailPageShell
          key={`${job.id}:${defaultTab}`}
          // breadcrumb={
          //   <span>
          //     <Link href={typeof tenantSlug === "string" ? `/app/${tenantSlug}/jobs` : "/app"} className="hover:text-[var(--rb-text)]">
          //       Jobs
          //     </Link>
          //     <span className="px-2">/</span>
          //     <span>{job.case_number}</span>
          //   </span>
          // }
          // backHref={typeof tenantSlug === "string" ? `/app/${tenantSlug}/jobs` : "/app"}
          title={job.case_number}
          description={job.title}
          actions={
            <div className="flex items-center gap-2">
              <Badge variant={statusBadgeVariant(job.status)}>{formatSlugLabel(job.status)}</Badge>
              {job.payment_status ? <Badge variant={paymentBadgeVariant(job.payment_status)}>{formatSlugLabel(job.payment_status)}</Badge> : null}
              {job.priority ? <Badge variant={priorityBadgeVariant(job.priority)}>{formatSlugLabel(job.priority)}</Badge> : null}
              <Button
                variant="outline"
                size="sm"
                onClick={() => {
                  setRefreshKey((x) => x + 1);
                }}
              >
                <RefreshCw className="mr-2 h-4 w-4" />
                Refresh
              </Button>
              <Button disabled variant="outline" size="sm">
                Change status
              </Button>
            </div>
          }
          defaultTab={defaultTab}
          tabs={{
            overview: (
              <JobOverview
                tenantSlug={typeof tenantSlug === "string" ? tenantSlug : null}
                job={job}
                jobDevices={jobDevices}
                devicesLoading={devicesLoading}
              />
            ),
            devices: (
              <AttachedDevicesManager
                devicesError={devicesError}
                attachError={attachError}
                devicesLoading={devicesLoading}
                customerDevices={customerDevices}
                attachedDevices={jobDevices}
                attachId={attachId}
                setAttachId={setAttachId}
                attachBusy={attachBusy}
                onAttach={() => void attachDevice()}
                onDetach={(id) => void detachDevice(id)}
              />
            ),
            timeline: (
              <div className="space-y-4">
                {noteError ? (
                  <Alert variant="danger" title="Could not post note">
                    {noteError}
                  </Alert>
                ) : null}

                <Card className="shadow-none">
                  <CardContent className="pt-5">
                    <div className="text-sm font-semibold text-[var(--rb-text)]">Add internal note</div>
                    <textarea
                      value={noteBody}
                      onChange={(e) => setNoteBody(e.target.value)}
                      placeholder="Write a note for internal staff..."
                      className="mt-3 w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                      rows={3}
                      disabled={noteBusy}
                    />
                    <div className="mt-3 flex items-center justify-end">
                      <Button onClick={() => void postInternalNote()} disabled={noteBusy || noteBody.trim().length === 0}>
                        {noteBusy ? "Posting..." : "Add note"}
                      </Button>
                    </div>
                  </CardContent>
                </Card>

                <Card className="shadow-none">
                  <CardContent className="pt-5">
                    <div className="space-y-3">
                      {job.timeline.length === 0 ? <div className="text-sm text-zinc-600">No timeline events.</div> : null}
                      {job.timeline
                        .slice()
                        .sort((a, b) => (a.created_at < b.created_at ? 1 : -1))
                        .map((ev) => (
                          <div key={ev.id} className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-3">
                            <div className="flex items-start justify-between gap-3">
                              <div className="min-w-0">
                                <div className="truncate text-sm font-semibold text-[var(--rb-text)]">{ev.title}</div>
                                {ev.message ? <div className="mt-2 whitespace-pre-wrap text-sm text-zinc-700">{ev.message}</div> : null}
                                <div className="mt-2 text-xs text-zinc-500">{new Date(ev.created_at).toLocaleString()}</div>
                              </div>
                              <Badge variant="default">{ev.type}</Badge>
                            </div>
                          </div>
                        ))}
                    </div>
                  </CardContent>
                </Card>
              </div>
            ),
            messages: (
              <div className="space-y-4">
                {messageError ? (
                  <Alert variant="danger" title="Could not post message">
                    {messageError}
                  </Alert>
                ) : null}

                <Card className="shadow-none">
                  <CardContent className="pt-5">
                    <div className="text-sm font-semibold text-[var(--rb-text)]">Post a message</div>
                    <textarea
                      value={messageBody}
                      onChange={(e) => setMessageBody(e.target.value)}
                      placeholder="Write an update for the customer..."
                      className="mt-3 w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                      rows={4}
                      disabled={messageBusy}
                    />
                    <div className="mt-3 flex items-center justify-end">
                      <Button onClick={() => void postMessage()} disabled={messageBusy || messageBody.trim().length === 0}>
                        {messageBusy ? "Posting..." : "Post"}
                      </Button>
                    </div>
                  </CardContent>
                </Card>

                <Card className="shadow-none">
                  <CardContent className="pt-5">
                    <div className="text-sm font-semibold text-[var(--rb-text)]">Conversation</div>
                    <div className="mt-4 space-y-3">
                      {job.messages.length === 0 ? <div className="text-sm text-zinc-600">No messages yet.</div> : null}
                      {job.messages
                        .slice()
                        .sort((a, b) => (a.created_at < b.created_at ? 1 : -1))
                        .map((m) => (
                          <div key={m.id} className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-3">
                            <div className="flex items-start justify-between gap-3">
                              <div className="min-w-0">
                                <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">{m.author}</div>
                                <div className="mt-1 text-sm text-zinc-700 whitespace-pre-wrap">{m.body}</div>
                                <div className="mt-2 text-xs text-zinc-500">{new Date(m.created_at).toLocaleString()}</div>
                              </div>
                              <Badge variant={m.author === "staff" ? "info" : "default"}>{m.author}</Badge>
                            </div>
                          </div>
                        ))}
                    </div>
                  </CardContent>
                </Card>
              </div>
            ),
            financial: (
              <Card className="shadow-none">
                <CardContent className="pt-5">
                  <div className="text-sm text-zinc-600">Financials will be enabled in EPIC 7–9.</div>
                </CardContent>
              </Card>
            ),
            print: (
              <Card className="shadow-none">
                <CardContent className="pt-5">
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Print</div>
                  <div className="mt-2 text-sm text-zinc-600">Print-friendly views are implemented in later phases.</div>
                </CardContent>
              </Card>
            ),
          }}
        />
      ) : null}
    </div>
  );
}

function JobOverview({
  tenantSlug,
  job,
  jobDevices,
  devicesLoading,
}: {
  tenantSlug: string | null;
  job: ApiJob;
  jobDevices: ApiJobDevice[];
  devicesLoading: boolean;
}) {
  const timeline = Array.isArray(job.timeline) ? job.timeline : [];
  const messages = Array.isArray(job.messages) ? job.messages : [];

  const customerId = typeof job.customer?.id === "number" ? job.customer.id : job.customer_id;
  const customerHref = tenantSlug && typeof customerId === "number" ? `/app/${tenantSlug}/clients/${customerId}` : null;
  const devicesHref = tenantSlug ? `/app/${tenantSlug}/jobs/${job.id}?tab=devices` : null;
  const timelineHref = tenantSlug ? `/app/${tenantSlug}/jobs/${job.id}?tab=timeline` : null;
  const messagesHref = tenantSlug ? `/app/${tenantSlug}/jobs/${job.id}?tab=messages` : null;

  const latestTimeline = React.useMemo(() => {
    return timeline
      .slice()
      .sort((a, b) => (a.created_at < b.created_at ? 1 : -1))
      .at(0);
  }, [timeline]);

  const latestMessage = React.useMemo(() => {
    return messages
      .slice()
      .sort((a, b) => (a.created_at < b.created_at ? 1 : -1))
      .at(0);
  }, [messages]);

  const assigned = React.useMemo(() => {
    const list = Array.isArray(job.assigned_technicians) ? job.assigned_technicians : [];
    if (list.length === 0) return "—";
    return list.map((t) => t.name).filter(Boolean).join(", ") || "—";
  }, [job.assigned_technicians]);

  const totalLabel = formatMoney({
    amountCents: job.totals?.total_cents,
    currency: job.totals?.currency,
    fallback: "—",
  });

  return (
    <div className="space-y-4">
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Card className="shadow-none">
          <CardContent className="pt-5">
            <div className="flex items-start gap-3">
              <div className="mt-0.5 flex h-9 w-9 items-center justify-center rounded-[var(--rb-radius-md)] bg-[var(--rb-surface-muted)] text-zinc-700">
                <User className="h-4 w-4" />
              </div>
              <div className="min-w-0 flex-1">
                <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Customer</div>
                <div className="mt-1 truncate text-sm font-semibold text-[var(--rb-text)]">
                  {job.customer?.name ?? (typeof customerId === "number" ? `Customer #${customerId}` : "—")}
                </div>
                <div className="mt-1 truncate text-xs text-zinc-500">{job.customer?.email ?? job.customer?.phone ?? ""}</div>
              </div>
              {customerHref ? (
                <Button asChild variant="outline" size="sm">
                  <Link href={customerHref}>View</Link>
                </Button>
              ) : null}
            </div>
          </CardContent>
        </Card>

        <Card className="shadow-none">
          <CardContent className="pt-5">
            <div className="flex items-start gap-3">
              <div className="mt-0.5 flex h-9 w-9 items-center justify-center rounded-[var(--rb-radius-md)] bg-[var(--rb-surface-muted)] text-zinc-700">
                <Laptop className="h-4 w-4" />
              </div>
              <div className="min-w-0 flex-1">
                <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Devices</div>
                <div className="mt-1 text-sm font-semibold text-[var(--rb-text)]">{devicesLoading ? "Loading…" : `${jobDevices.length} attached`}</div>
                <div className="mt-1 truncate text-xs text-zinc-500">{jobDevices[0]?.label ?? ""}</div>
              </div>
              {devicesHref ? (
                <Button asChild variant="outline" size="sm">
                  <Link href={devicesHref}>Manage</Link>
                </Button>
              ) : null}
            </div>
          </CardContent>
        </Card>

        <Card className="shadow-none">
          <CardContent className="pt-5">
            <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Payment</div>
            <div className="mt-1 flex flex-wrap items-center gap-2">
              <div className="text-sm font-semibold text-[var(--rb-text)]">{totalLabel}</div>
              {job.payment_status ? <Badge variant={paymentBadgeVariant(job.payment_status)}>{formatSlugLabel(job.payment_status)}</Badge> : <Badge variant="default">—</Badge>}
            </div>
            <div className="mt-2 text-xs text-zinc-500">Subtotal {formatMoney({ amountCents: job.totals?.subtotal_cents, currency: job.totals?.currency, fallback: "—" })}</div>
          </CardContent>
        </Card>

        <Card className="shadow-none">
          <CardContent className="pt-5">
            <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Last update</div>
            <div className="mt-1 text-sm font-semibold text-[var(--rb-text)]">{formatDateTime(job.updated_at)}</div>
            <div className="mt-2 text-xs text-zinc-500">Job ID {job.id}</div>
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-4 lg:grid-cols-3">
        <div className="space-y-4 lg:col-span-2">
          <Card className="shadow-none">
            <CardHeader>
              <CardTitle>Job summary</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="grid gap-4 sm:grid-cols-2">
                <div className="min-w-0">
                  <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Case</div>
                  <div className="mt-1 truncate text-sm font-semibold text-[var(--rb-text)]">{job.case_number}</div>
                </div>
                <div>
                  <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Status</div>
                  <div className="mt-1 flex flex-wrap items-center gap-2">
                    <Badge variant={statusBadgeVariant(job.status)}>{formatSlugLabel(job.status)}</Badge>
                    {job.priority ? <Badge variant={priorityBadgeVariant(job.priority)}>{formatSlugLabel(job.priority)}</Badge> : null}
                  </div>
                </div>

                <div className="sm:col-span-2">
                  <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Issue / title</div>
                  <div className="mt-1 text-sm text-zinc-700">{job.title || "—"}</div>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="shadow-none">
            <CardHeader>
              <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <CardTitle>Recent activity</CardTitle>
                <div className="flex items-center gap-2">
                  {timelineHref ? (
                    <Button asChild variant="outline" size="sm">
                      <Link href={timelineHref}>Timeline</Link>
                    </Button>
                  ) : null}
                  {messagesHref ? (
                    <Button asChild variant="outline" size="sm">
                      <Link href={messagesHref}>Messages</Link>
                    </Button>
                  ) : null}
                </div>
              </div>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-start gap-3">
                <div className="mt-0.5 flex h-9 w-9 items-center justify-center rounded-[var(--rb-radius-md)] bg-[var(--rb-surface-muted)] text-zinc-700">
                  <ListChecks className="h-4 w-4" />
                </div>
                <div className="min-w-0 flex-1">
                  <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Latest timeline event</div>
                  <div className="mt-1 truncate text-sm font-semibold text-[var(--rb-text)]">{latestTimeline?.title ?? "—"}</div>
                  <div className="mt-1 text-xs text-zinc-500">{latestTimeline?.created_at ? formatDateTime(latestTimeline.created_at) : ""}</div>
                </div>
                {latestTimeline?.type ? <Badge variant="default">{latestTimeline.type}</Badge> : null}
              </div>

              <div className="flex items-start gap-3">
                <div className="mt-0.5 flex h-9 w-9 items-center justify-center rounded-[var(--rb-radius-md)] bg-[var(--rb-surface-muted)] text-zinc-700">
                  <MessageSquare className="h-4 w-4" />
                </div>
                <div className="min-w-0 flex-1">
                  <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Latest message</div>
                  <div className="mt-1 truncate text-sm font-semibold text-[var(--rb-text)]">
                    {latestMessage ? `${latestMessage.author}` : "—"}
                  </div>
                  <div className="mt-1 truncate text-xs text-zinc-500">{latestMessage?.body ?? ""}</div>
                </div>
                {latestMessage?.created_at ? <div className="text-xs text-zinc-500">{formatShortDate(latestMessage.created_at)}</div> : null}
              </div>
            </CardContent>
          </Card>
        </div>

        <div className="space-y-4">
          <Card className="shadow-none">
            <CardHeader>
              <CardTitle>Dates</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              <div className="flex items-start gap-3">
                <div className="mt-0.5 flex h-9 w-9 items-center justify-center rounded-[var(--rb-radius-md)] bg-[var(--rb-surface-muted)] text-zinc-700">
                  <CalendarClock className="h-4 w-4" />
                </div>
                <div className="min-w-0 flex-1">
                  <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Pickup</div>
                  <div className="mt-1 text-sm text-zinc-700">{formatShortDate(job.pickup_date)}</div>
                </div>
              </div>
              <div className="flex items-start gap-3">
                <div className="mt-0.5 flex h-9 w-9 items-center justify-center rounded-[var(--rb-radius-md)] bg-[var(--rb-surface-muted)] text-zinc-700">
                  <CalendarClock className="h-4 w-4" />
                </div>
                <div className="min-w-0 flex-1">
                  <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Delivery</div>
                  <div className="mt-1 text-sm text-zinc-700">{formatShortDate(job.delivery_date)}</div>
                </div>
              </div>
              <div className="flex items-start gap-3">
                <div className="mt-0.5 flex h-9 w-9 items-center justify-center rounded-[var(--rb-radius-md)] bg-[var(--rb-surface-muted)] text-zinc-700">
                  <CalendarClock className="h-4 w-4" />
                </div>
                <div className="min-w-0 flex-1">
                  <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Next service</div>
                  <div className="mt-1 text-sm text-zinc-700">{formatShortDate(job.next_service_date)}</div>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="shadow-none">
            <CardHeader>
              <CardTitle>Assigned</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Technicians</div>
              <div className="mt-1 text-sm text-zinc-700">{assigned}</div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
