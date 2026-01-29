"use client";

import React from "react";
import { useParams, useRouter } from "next/navigation";
import Link from "next/link";
import { Button } from "@/components/ui/Button";
import { Badge } from "@/components/ui/Badge";
import { Card, CardContent } from "@/components/ui/Card";
import { Alert } from "@/components/ui/Alert";
import { DetailPageShell } from "@/components/shells/DetailPageShell";
import { apiFetch, ApiError } from "@/lib/api";

type JobStatusKey = string;

type ApiJob = {
  id: number;
  case_number: string;
  title: string;
  status: JobStatusKey;
  updated_at: string;
  customer_id: number | null;
  timeline: Array<{ id: string; title: string; type: string; message?: string | null; created_at: string }>;
  messages: Array<{ id: string; author: string; body: string; created_at: string }>;
};

type ApiJobDevice = {
  id: number;
  job_id: number;
  customer_device_id: number;
  label: string;
  serial: string | null;
  notes: string | null;
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

export default function TenantJobDetailPage() {
  const router = useRouter();
  const params = useParams() as { tenant?: string; business?: string; jobId?: string };
  const tenantSlug = params.business ?? params.tenant;
  const jobId = params.jobId;

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
          breadcrumb={
            <span>
              <Link href={typeof tenantSlug === "string" ? `/app/${tenantSlug}/jobs` : "/app"} className="hover:text-[var(--rb-text)]">
                Jobs
              </Link>
              <span className="px-2">/</span>
              <span>{job.case_number}</span>
            </span>
          }
          backHref={typeof tenantSlug === "string" ? `/app/${tenantSlug}/jobs` : "/app"}
          title={job.case_number}
          description={job.title}
          actions={
            <div className="flex items-center gap-2">
              <Badge variant={statusBadgeVariant(job.status)}>{job.status.replace(/_/g, " ")}</Badge>
              <Button disabled variant="outline" size="sm">
                Change status
              </Button>
            </div>
          }
          tabs={{
            overview: (
              <Card className="shadow-none">
                <CardContent className="pt-5">
                  <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                      <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Job ID</div>
                      <div className="mt-1 text-sm text-zinc-700">{job.id}</div>
                    </div>
                    <div>
                      <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Updated</div>
                      <div className="mt-1 text-sm text-zinc-700">{new Date(job.updated_at).toLocaleString()}</div>
                    </div>
                    <div className="sm:col-span-2">
                      <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Notes</div>
                      <div className="mt-1 text-sm text-zinc-700">This is a mock detail page (Phase 3 shell validation).</div>
                    </div>
                  </div>
                </CardContent>
              </Card>
            ),
            devices: (
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
                            {d.label}{d.serial ? ` (Serial: ${d.serial})` : ""}
                          </option>
                        ))}
                      </select>
                      <Button onClick={() => void attachDevice()} disabled={attachBusy || attachId.trim().length === 0}>
                        {attachBusy ? "Saving..." : "Attach"}
                      </Button>
                    </div>
                    {customerDevices.length === 0 ? <div className="mt-2 text-xs text-zinc-500">No customer devices available for this job.</div> : null}
                  </CardContent>
                </Card>

                <Card className="shadow-none">
                  <CardContent className="pt-5">
                    <div className="text-sm font-semibold text-[var(--rb-text)]">Attached devices</div>
                    <div className="mt-4 space-y-3">
                      {jobDevices.length === 0 ? <div className="text-sm text-zinc-600">No devices attached.</div> : null}
                      {jobDevices.map((d) => (
                        <div key={d.id} className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-3">
                          <div className="flex flex-wrap items-start justify-between gap-3">
                            <div className="min-w-0">
                              <div className="truncate text-sm font-semibold text-[var(--rb-text)]">{d.label}</div>
                              <div className="mt-1 text-xs text-zinc-500">Serial: {d.serial ?? "—"}</div>
                              {d.notes ? <div className="mt-2 whitespace-pre-wrap text-sm text-zinc-700">{d.notes}</div> : null}
                            </div>
                            <div className="flex items-center gap-2">
                              <Badge variant="default">{d.customer_device_id}</Badge>
                              <Button variant="outline" size="sm" onClick={() => void detachDevice(d.id)} disabled={attachBusy}>
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
