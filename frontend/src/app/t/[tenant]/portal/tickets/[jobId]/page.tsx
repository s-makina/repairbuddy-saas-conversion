"use client";

import React from "react";
import Link from "next/link";
import { useParams } from "next/navigation";
import { PortalShell } from "@/components/shells/PortalShell";
import { Alert } from "@/components/ui/Alert";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { mockApi } from "@/mock/mockApi";
import type { Estimate, EstimateId, Job, JobId, JobStatusKey } from "@/mock/types";
import { formatMoney } from "@/lib/money";

type PortalSession = {
  jobId: string;
  caseNumber: string;
};

function portalSessionKey(tenantSlug: string) {
  return `rb.portal.session:v1:${tenantSlug}`;
}

function loadPortalSession(tenantSlug: string): PortalSession | null {
  if (typeof window === "undefined") return null;
  try {
    const raw = window.localStorage.getItem(portalSessionKey(tenantSlug));
    if (!raw) return null;
    const parsed = JSON.parse(raw) as { job_id?: unknown; case_number?: unknown };
    const jobId = typeof parsed.job_id === "string" ? parsed.job_id : "";
    const caseNumber = typeof parsed.case_number === "string" ? parsed.case_number : "";
    if (!jobId) return null;
    return { jobId, caseNumber };
  } catch {
    return null;
  }
}

function statusBadgeVariant(status: JobStatusKey): "default" | "info" | "success" | "warning" | "danger" {
  if (status === "delivered" || status === "completed") return "success";
  if (status === "ready") return "warning";
  if (status === "cancelled") return "danger";
  if (status === "in_process") return "info";
  return "default";
}

export default function PortalTicketDetailPage() {
  const params = useParams() as { tenant?: string; jobId?: string };
  const tenantSlug = typeof params.tenant === "string" ? params.tenant : "";
  const jobId = typeof params.jobId === "string" ? params.jobId : "";

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [session, setSession] = React.useState<PortalSession | null>(null);

  const [job, setJob] = React.useState<Job | null>(null);
  const [estimate, setEstimate] = React.useState<Estimate | null>(null);
  const [statusLabels, setStatusLabels] = React.useState<Record<string, string>>({});

  const [messageBody, setMessageBody] = React.useState("");
  const [messageBusy, setMessageBusy] = React.useState(false);
  const [messageError, setMessageError] = React.useState<string | null>(null);

  const [estimateBusy, setEstimateBusy] = React.useState(false);
  const [estimateError, setEstimateError] = React.useState<string | null>(null);

  const [refreshKey, setRefreshKey] = React.useState(0);

  React.useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);

        if (!tenantSlug) {
          setSession(null);
          setJob(null);
          setEstimate(null);
          return;
        }

        const s = loadPortalSession(tenantSlug);
        setSession(s);

        if (!s) {
          setJob(null);
          setEstimate(null);
          return;
        }

        if (!jobId) {
          setError("Job ID is missing.");
          setJob(null);
          setEstimate(null);
          return;
        }

        if (s.jobId !== jobId) {
          setError("This ticket does not match your current case session.");
          setJob(null);
          setEstimate(null);
          return;
        }

        const [statuses, found] = await Promise.all([mockApi.getStatuses(), mockApi.getJob(jobId as JobId)]);
        if (!alive) return;

        const nextLabels: Record<string, string> = {};
        for (const st of Array.isArray(statuses) ? statuses : []) {
          nextLabels[st.key] = st.label;
        }
        setStatusLabels(nextLabels);

        if (!found) {
          setError("Ticket not found.");
          setJob(null);
          setEstimate(null);
          return;
        }

        setJob(found);

        if (found.estimate_id) {
          const e = await mockApi.getEstimate(found.estimate_id as EstimateId);
          if (!alive) return;
          setEstimate(e);
        } else {
          setEstimate(null);
        }
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load ticket.");
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
  }, [jobId, refreshKey, tenantSlug]);

  const estimateTotalCents = React.useMemo(() => {
    if (!estimate) return 0;
    return mockApi.computeEstimateTotalCents(estimate);
  }, [estimate]);

  async function postMessage() {
    if (!job) return;
    const body = messageBody.trim();
    if (!body) return;

    setMessageBusy(true);
    setMessageError(null);
    try {
      await mockApi.postJobMessage({
        jobId: job.id,
        author: "customer",
        body,
      });
      setMessageBody("");
      setRefreshKey((x) => x + 1);
    } catch (e) {
      setMessageError(e instanceof Error ? e.message : "Failed to post message.");
    } finally {
      setMessageBusy(false);
    }
  }

  async function setEstimateStatus(status: "approved" | "rejected") {
    if (!estimate) return;
    setEstimateBusy(true);
    setEstimateError(null);
    try {
      await mockApi.setEstimateStatus({ estimateId: estimate.id, status });
      setRefreshKey((x) => x + 1);
    } catch (e) {
      setEstimateError(e instanceof Error ? e.message : "Failed to update estimate.");
    } finally {
      setEstimateBusy(false);
    }
  }

  return (
    <PortalShell tenantSlug={tenantSlug} title="Ticket" subtitle={session?.caseNumber ? `Case ${session.caseNumber}` : ""}>
      <div className="space-y-4">
        {loading ? <div className="text-sm text-zinc-500">Loading ticket...</div> : null}

        {error ? (
          <Alert variant="danger" title="Could not load ticket">
            {error}
          </Alert>
        ) : null}

        {!loading && !error && !session ? (
          <Alert variant="warning" title="Portal locked">
            Enter your case number to view this ticket.
          </Alert>
        ) : null}

        {job ? (
          <div className="space-y-4">
            <Card className="shadow-none">
              <CardContent className="pt-5">
                <div className="flex flex-wrap items-start justify-between gap-3">
                  <div className="min-w-0">
                    <div className="text-sm font-semibold text-[var(--rb-text)]">{job.case_number}</div>
                    <div className="mt-1 text-sm text-zinc-600">{job.title}</div>
                    <div className="mt-2 text-xs text-zinc-500">Last updated: {new Date(job.updated_at).toLocaleString()}</div>
                  </div>
                  <Badge variant={statusBadgeVariant(job.status)}>{statusLabels[job.status] ?? job.status.replace(/_/g, " ")}</Badge>
                </div>

                <div className="mt-4 flex flex-wrap items-center gap-2">
                  <Button asChild variant="outline" size="sm">
                    <Link href={`/t/${tenantSlug}/portal/tickets`}>Back to tickets</Link>
                  </Button>
                  <Button asChild variant="outline" size="sm">
                    <Link href={`/t/${tenantSlug}/status`}>Public status</Link>
                  </Button>
                </div>
              </CardContent>
            </Card>

            <Card className="shadow-none">
              <CardContent className="pt-5">
                <div className="text-sm font-semibold text-[var(--rb-text)]">Timeline</div>
                <div className="mt-4 space-y-3">
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

            <Card className="shadow-none">
              <CardContent className="pt-5">
                <div className="text-sm font-semibold text-[var(--rb-text)]">Messages</div>

                {messageError ? (
                  <div className="mt-3">
                    <Alert variant="danger" title="Could not post message">
                      {messageError}
                    </Alert>
                  </div>
                ) : null}

                <div className="mt-4">
                  <textarea
                    value={messageBody}
                    onChange={(e) => setMessageBody(e.target.value)}
                    placeholder="Write a message to the shop..."
                    className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                    rows={4}
                    disabled={messageBusy}
                  />
                  <div className="mt-3 flex items-center justify-end">
                    <Button onClick={() => void postMessage()} disabled={messageBusy || messageBody.trim().length === 0}>
                      {messageBusy ? "Sending..." : "Send"}
                    </Button>
                  </div>
                </div>

                <div className="mt-5 space-y-3">
                  {job.messages.length === 0 ? <div className="text-sm text-zinc-600">No messages yet.</div> : null}
                  {job.messages
                    .slice()
                    .sort((a, b) => (a.created_at < b.created_at ? 1 : -1))
                    .map((m) => (
                      <div key={m.id} className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-3">
                        <div className="flex items-start justify-between gap-3">
                          <div className="min-w-0">
                            <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">{m.author}</div>
                            <div className="mt-1 whitespace-pre-wrap text-sm text-zinc-700">{m.body}</div>
                            <div className="mt-2 text-xs text-zinc-500">{new Date(m.created_at).toLocaleString()}</div>
                          </div>
                          <Badge variant={m.author === "staff" ? "info" : "default"}>{m.author}</Badge>
                        </div>
                      </div>
                    ))}
                </div>
              </CardContent>
            </Card>

            <Card className="shadow-none">
              <CardContent className="pt-5">
                <div className="flex flex-wrap items-start justify-between gap-3">
                  <div>
                    <div className="text-sm font-semibold text-[var(--rb-text)]">Estimate</div>
                    <div className="mt-1 text-sm text-zinc-600">Approve or reject your estimate.</div>
                  </div>
                  {estimate ? (
                    <Badge variant={estimate.status === "approved" ? "success" : estimate.status === "rejected" ? "danger" : "warning"}>{estimate.status}</Badge>
                  ) : (
                    <Badge variant="default">none</Badge>
                  )}
                </div>

                {estimateError ? (
                  <div className="mt-3">
                    <Alert variant="danger" title="Could not update estimate">
                      {estimateError}
                    </Alert>
                  </div>
                ) : null}

                {estimate ? (
                  <div className="mt-4 space-y-3">
                    <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-3">
                      <div className="text-sm font-semibold text-[var(--rb-text)]">Line items</div>
                      <div className="mt-3 space-y-2">
                        {estimate.lines.map((line) => (
                          <div key={line.id} className="flex items-start justify-between gap-3 text-sm">
                            <div className="min-w-0">
                              <div className="truncate text-zinc-700">{line.label}</div>
                              <div className="text-xs text-zinc-500">Qty: {line.qty}</div>
                            </div>
                            <div className="whitespace-nowrap font-semibold text-[var(--rb-text)]">
                              {formatMoney({ amountCents: line.qty * line.unit_price.amount_cents, currency: line.unit_price.currency })}
                            </div>
                          </div>
                        ))}
                      </div>
                      <div className="mt-4 flex items-center justify-between border-t border-[var(--rb-border)] pt-3">
                        <div className="text-sm text-zinc-700">Total</div>
                        <div className="text-sm font-semibold text-[var(--rb-text)]">
                          {formatMoney({ amountCents: estimateTotalCents, currency: estimate.lines[0]?.unit_price.currency ?? "USD" })}
                        </div>
                      </div>
                    </div>

                    <div className="flex flex-wrap items-center justify-end gap-2">
                      <Button variant="outline" disabled={estimateBusy || estimate.status !== "pending"} onClick={() => void setEstimateStatus("rejected")}>
                        Reject
                      </Button>
                      <Button disabled={estimateBusy || estimate.status !== "pending"} onClick={() => void setEstimateStatus("approved")}>
                        Approve
                      </Button>
                    </div>

                    <div className="text-xs text-zinc-500">Approving/rejecting is saved in your browser (mock persistence).</div>
                  </div>
                ) : (
                  <div className="mt-3 text-sm text-zinc-600">No estimate linked to this ticket.</div>
                )}
              </CardContent>
            </Card>
          </div>
        ) : null}
      </div>
    </PortalShell>
  );
}
