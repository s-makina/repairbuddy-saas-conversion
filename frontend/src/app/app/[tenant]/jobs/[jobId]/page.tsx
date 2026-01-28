"use client";

import React from "react";
import { useParams } from "next/navigation";
import Link from "next/link";
import { Button } from "@/components/ui/Button";
import { Badge } from "@/components/ui/Badge";
import { Card, CardContent } from "@/components/ui/Card";
import { Alert } from "@/components/ui/Alert";
import { DetailPageShell } from "@/components/shells/DetailPageShell";
import { mockApi, computeEstimateTotalCents } from "@/mock/mockApi";
import type { Estimate, Job, JobStatusKey } from "@/mock/types";
import { formatMoney } from "@/lib/money";

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
  const [job, setJob] = React.useState<Job | null>(null);
  const [estimate, setEstimate] = React.useState<Estimate | null>(null);

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
          setEstimate(null);
          return;
        }

        const j = await mockApi.getJob(jobId as any);
        if (!alive) return;

        if (!j) {
          setError("Job not found.");
          setJob(null);
          setEstimate(null);
          return;
        }

        setJob(j);

        if (j.estimate_id) {
          const e = await mockApi.getEstimate(j.estimate_id as any);
          if (!alive) return;
          setEstimate(e);
        } else {
          setEstimate(null);
        }
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load job.");
        setJob(null);
        setEstimate(null);
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

  const estimateTotal = React.useMemo(() => {
    if (!estimate) return null;
    return computeEstimateTotalCents(estimate);
  }, [estimate]);

  async function postMessage() {
    if (!job) return;
    setMessageBusy(true);
    setMessageError(null);
    try {
      await mockApi.postJobMessage({
        jobId: job.id,
        author: "staff",
        body: messageBody,
      });
      setMessageBody("");
      setRefreshKey((x) => x + 1);
    } catch (e) {
      setMessageError(e instanceof Error ? e.message : "Failed to post message.");
    } finally {
      setMessageBusy(false);
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
              <div className="space-y-4">
                <Card className="shadow-none">
                  <CardContent className="pt-5">
                    <div className="text-sm font-semibold text-[var(--rb-text)]">Estimate</div>
                    {estimate ? (
                      <div className="mt-3 space-y-2">
                        <div className="flex items-center justify-between gap-3">
                          <div className="text-sm text-zinc-700">Status</div>
                          <Badge variant={estimate.status === "approved" ? "success" : estimate.status === "rejected" ? "danger" : "warning"}>
                            {estimate.status}
                          </Badge>
                        </div>
                        <div className="flex items-center justify-between gap-3">
                          <div className="text-sm text-zinc-700">Total</div>
                          <div className="text-sm font-semibold text-[var(--rb-text)]">
                            {formatMoney({ amountCents: estimateTotal ?? 0, currency: estimate.lines[0]?.unit_price.currency ?? "USD" })}
                          </div>
                        </div>
                      </div>
                    ) : (
                      <div className="mt-2 text-sm text-zinc-600">No estimate linked.</div>
                    )}
                  </CardContent>
                </Card>

                <Card className="shadow-none">
                  <CardContent className="pt-5">
                    <div className="text-sm font-semibold text-[var(--rb-text)]">Payments</div>
                    <div className="mt-2 text-sm text-zinc-600">Payments list will be wired in Phase 4/5.</div>
                  </CardContent>
                </Card>
              </div>
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
