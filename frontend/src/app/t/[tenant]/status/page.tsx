"use client";

import React from "react";
import Link from "next/link";
import { useParams, useRouter } from "next/navigation";
import { PublicPageShell } from "@/components/PublicPageShell";
import { Card, CardContent } from "@/components/ui/Card";
import { Alert } from "@/components/ui/Alert";
import { Button } from "@/components/ui/Button";
import { Input } from "@/components/ui/Input";
import { Badge } from "@/components/ui/Badge";
import { mockApi } from "@/mock/mockApi";
import type { Estimate, Job, JobStatusKey } from "@/mock/types";
import { formatMoney } from "@/lib/money";

function statusBadgeVariant(status: JobStatusKey): "default" | "info" | "success" | "warning" | "danger" {
  if (status === "delivered" || status === "completed") return "success";
  if (status === "ready") return "warning";
  if (status === "cancelled") return "danger";
  if (status === "in_process") return "info";
  return "default";
}

export default function PublicStatusPage() {
  const router = useRouter();
  const params = useParams() as { tenant?: string };
  const tenantSlug = params.tenant;

  const [caseNumber, setCaseNumber] = React.useState("");
  const [loading, setLoading] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);
  const [job, setJob] = React.useState<Job | null>(null);
  const [estimate, setEstimate] = React.useState<Estimate | null>(null);
  const [statusLabels, setStatusLabels] = React.useState<Record<string, string>>({});

  const [messageBody, setMessageBody] = React.useState("");
  const [messageBusy, setMessageBusy] = React.useState(false);
  const [messageError, setMessageError] = React.useState<string | null>(null);

  const [estimateBusy, setEstimateBusy] = React.useState(false);
  const [estimateError, setEstimateError] = React.useState<string | null>(null);

  const [refreshKey, setRefreshKey] = React.useState(0);
  const lastCaseNumberRef = React.useRef<string>("");

  const normalizedTenant = typeof tenantSlug === "string" ? tenantSlug : "";

  const estimateTotalCents = React.useMemo(() => {
    if (!estimate) return 0;
    return mockApi.computeEstimateTotalCents(estimate);
  }, [estimate]);

  async function loadByCaseNumber(input: string) {
    const normalized = input.trim();
    if (!normalized) {
      setError("Case number is required.");
      setJob(null);
      setEstimate(null);
      return;
    }

    lastCaseNumberRef.current = normalized;

    setLoading(true);
    setError(null);
    try {
      const [statuses, found] = await Promise.all([mockApi.getStatuses(), mockApi.getJobByCaseNumber(normalized)]);
      const nextLabels: Record<string, string> = {};
      for (const s of Array.isArray(statuses) ? statuses : []) {
        nextLabels[s.key] = s.label;
      }
      setStatusLabels(nextLabels);

      if (!found) {
        setError("No job found for that case number.");
        setJob(null);
        setEstimate(null);
        return;
      }

      setJob(found);

      if (found.estimate_id) {
        const est = await mockApi.getEstimate(found.estimate_id as any);
        setEstimate(est);
      } else {
        setEstimate(null);
      }
    } catch (e) {
      setError(e instanceof Error ? e.message : "Failed to load status.");
      setJob(null);
      setEstimate(null);
    } finally {
      setLoading(false);
    }
  }

  React.useEffect(() => {
    if (!lastCaseNumberRef.current) return;
    void loadByCaseNumber(lastCaseNumberRef.current);
  }, [refreshKey]);

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
    <PublicPageShell
      badge="RepairBuddy"
      actions={
        normalizedTenant ? (
          <Link href={`/t/${normalizedTenant}/portal`} className="text-sm font-semibold text-[var(--rb-blue)] hover:underline">
            Customer portal
          </Link>
        ) : null
      }
      centerContent
    >
      <div className="mx-auto w-full max-w-3xl px-4">
        <Card className="shadow-none">
          <CardContent className="pt-6">
            <div className="space-y-4">
              <div>
                <div className="text-xl font-semibold text-[var(--rb-text)]">Status Check</div>
                <div className="mt-1 text-sm text-zinc-600">Enter your case number to see the latest updates.</div>
              </div>

              <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                <Input
                  value={caseNumber}
                  onChange={(e) => setCaseNumber(e.target.value)}
                  placeholder="e.g. RB-1001"
                  disabled={loading}
                  onKeyDown={(e) => {
                    if (e.key === "Enter") {
                      e.preventDefault();
                      void loadByCaseNumber(caseNumber);
                    }
                  }}
                />
                <Button onClick={() => void loadByCaseNumber(caseNumber)} disabled={loading || !normalizedTenant}>
                  {loading ? "Checking..." : "Check"}
                </Button>
              </div>

              {normalizedTenant ? null : (
                <Alert variant="warning" title="Missing tenant">
                  Tenant slug is required in the URL.
                </Alert>
              )}

              {error ? (
                <Alert variant="danger" title="Could not load status">
                  {error}
                </Alert>
              ) : null}

              {job ? (
                <div className="space-y-4">
                  <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-4">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                      <div className="min-w-0">
                        <div className="text-sm font-semibold text-[var(--rb-text)]">Case {job.case_number}</div>
                        <div className="mt-1 text-sm text-zinc-600">{job.title}</div>
                        <div className="mt-2 text-xs text-zinc-500">Last updated: {new Date(job.updated_at).toLocaleString()}</div>
                      </div>
                      <Badge variant={statusBadgeVariant(job.status)}>{statusLabels[job.status] ?? job.status.replace(/_/g, " ")}</Badge>
                    </div>

                    <div className="mt-4 flex flex-wrap items-center gap-2">
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => {
                          if (!normalizedTenant) return;
                          router.push(`/t/${normalizedTenant}/portal`);
                        }}
                      >
                        Go to portal
                      </Button>
                      <Button variant="outline" size="sm" onClick={() => setRefreshKey((x) => x + 1)}>
                        Refresh
                      </Button>
                    </div>
                  </div>

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
                            <Button
                              variant="outline"
                              disabled={estimateBusy || estimate.status !== "pending"}
                              onClick={() => void setEstimateStatus("rejected")}
                            >
                              Reject
                            </Button>
                            <Button disabled={estimateBusy || estimate.status !== "pending"} onClick={() => void setEstimateStatus("approved")}>
                              Approve
                            </Button>
                          </div>

                          <div className="text-xs text-zinc-500">
                            Approving/rejecting is saved in your browser (mock persistence).
                          </div>
                        </div>
                      ) : (
                        <div className="mt-3 text-sm text-zinc-600">No estimate linked to this case.</div>
                      )}
                    </CardContent>
                  </Card>
                </div>
              ) : null}
            </div>
          </CardContent>
        </Card>
      </div>
    </PublicPageShell>
  );
}
