"use client";

import React from "react";
import { useParams } from "next/navigation";
import Link from "next/link";
import { Button } from "@/components/ui/Button";
import { Badge } from "@/components/ui/Badge";
import { Card, CardContent } from "@/components/ui/Card";
import { Alert } from "@/components/ui/Alert";
import { DetailPageShell } from "@/components/shells/DetailPageShell";
import { apiFetch } from "@/lib/api";

type JobStatusKey = string;

type ApiJob = {
  id: number;
  case_number: string;
  title: string;
  status: JobStatusKey;
  updated_at: string;
  timeline: Array<{ id: string; title: string; type: string; created_at: string }>;
  messages: Array<{ id: string; author: string; body: string; created_at: string }>;
};

function statusBadgeVariant(status: JobStatusKey): "default" | "info" | "success" | "warning" | "danger" {
  if (status === "delivered" || status === "completed") return "success";
  if (status === "ready") return "warning";
  if (status === "cancelled") return "danger";
  if (status === "in_process") return "info";
  return "default";
}

export default function TenantJobDetailPage() {
  const params = useParams() as { tenant?: string; business?: string; jobId?: string };
  const tenantSlug = params.business ?? params.tenant;
  const jobId = params.jobId;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [job, setJob] = React.useState<ApiJob | null>(null);

  const [messageBody, setMessageBody] = React.useState<string>("");
  const [messageBusy, setMessageBusy] = React.useState(false);
  const [messageError, setMessageError] = React.useState<string | null>(null);

  const [refreshKey, setRefreshKey] = React.useState(0);

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
  }, [jobId, refreshKey]);

  async function postMessage() {
    setMessageError("Messaging will be enabled in EPIC 3.");
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
            timeline: (
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
                              <div className="mt-1 text-xs text-zinc-500">{new Date(ev.created_at).toLocaleString()}</div>
                            </div>
                            <Badge variant="default">{ev.type}</Badge>
                          </div>
                        </div>
                      ))}
                  </div>
                </CardContent>
              </Card>
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
