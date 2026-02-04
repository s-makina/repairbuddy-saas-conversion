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
import { apiFetch, ApiError } from "@/lib/api";

type ApiLookupJob = {
  id: number;
  case_number: string;
  title: string;
  status: string;
  status_label?: string | null;
  updated_at: string;
};

type ApiLookupEstimate = {
  id: number;
  case_number: string;
  title: string;
  status: string;
  status_label?: string | null;
  updated_at: string;
};

function portalSessionKey(tenantSlug: string) {
  return `rb.portal.session:v1:${tenantSlug}`;
}

function statusBadgeVariant(status: string): "default" | "info" | "success" | "warning" | "danger" {
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
  const [job, setJob] = React.useState<ApiLookupJob | null>(null);
  const [estimate, setEstimate] = React.useState<ApiLookupEstimate | null>(null);

  const [messageBody, setMessageBody] = React.useState("");
  const [messageBusy, setMessageBusy] = React.useState(false);
  const [messageError, setMessageError] = React.useState<string | null>(null);
  const [messageSuccess, setMessageSuccess] = React.useState<string | null>(null);

  const [refreshKey, setRefreshKey] = React.useState(0);
  const lastCaseNumberRef = React.useRef<string>("");

  const [estimateActionBusy, setEstimateActionBusy] = React.useState(false);
  const [estimateActionError, setEstimateActionError] = React.useState<string | null>(null);
  const [estimateActionSuccess, setEstimateActionSuccess] = React.useState<string | null>(null);

  const normalizedTenant = typeof tenantSlug === "string" ? tenantSlug : "";

  async function loadByCaseNumber(input: string) {
    const normalized = input.trim();
    if (!normalized) {
      setError("Case number is required.");
      setJob(null);
      return;
    }

    lastCaseNumberRef.current = normalized;

    setLoading(true);
    setError(null);
    try {
      if (!normalizedTenant) {
        throw new Error("Tenant slug is required in the URL.");
      }

      const res = await apiFetch<{ entity_type?: string; job?: ApiLookupJob; estimate?: ApiLookupEstimate }>(`/api/t/${normalizedTenant}/status/lookup`, {
        method: "POST",
        body: { caseNumber: normalized },
        token: null,
        impersonationSessionId: null,
      });

      const entityType = typeof res?.entity_type === "string" ? res.entity_type : "job";
      const foundJob = res?.job ?? null;
      const foundEstimate = res?.estimate ?? null;

      setJob(entityType === "job" ? foundJob : null);
      setEstimate(entityType === "estimate" ? foundEstimate : null);

      const caseForSession = foundJob?.case_number ?? foundEstimate?.case_number ?? normalized;
      const idForSession = foundJob?.id ?? foundEstimate?.id ?? null;

      if (!idForSession) {
        throw new Error("No case found for that case number.");
      }

      setMessageBody("");
      setMessageError(null);
      setMessageSuccess(null);

      try {
        if (typeof window !== "undefined") {
          window.localStorage.setItem(
            portalSessionKey(normalizedTenant),
            JSON.stringify({
              job_id: String(idForSession),
              case_number: caseForSession,
            }),
          );
        }
      } catch {
        // ignore
      }
    } catch (e) {
      if (e instanceof ApiError && e.status === 404) {
        setError("No case found for that case number.");
      } else {
        setError(e instanceof Error ? e.message : "Failed to load status.");
      }
      setJob(null);
      setEstimate(null);
    } finally {
      setLoading(false);
    }
  }

  async function postMessage() {
    setMessageError(null);
    setMessageSuccess(null);

    const body = messageBody.trim();
    if (!body) {
      setMessageError("Message is required.");
      return;
    }

    if (!job) {
      setMessageError("Load a case first.");
      return;
    }

    if (!normalizedTenant) {
      setMessageError("Tenant slug is required in the URL.");
      return;
    }

    setMessageBusy(true);
    try {
      await apiFetch<{ message: string; event_id: number }>(`/api/t/${normalizedTenant}/status/${encodeURIComponent(job.case_number)}/message`, {
        method: "POST",
        body: { message: body },
        token: null,
        impersonationSessionId: null,
      });

      setMessageBody("");
      setMessageSuccess("Message sent. We'll get back to you soon.");
    } catch (e) {
      if (e instanceof ApiError) {
        setMessageError(e.message);
      } else {
        setMessageError(e instanceof Error ? e.message : "Failed to send message.");
      }
    } finally {
      setMessageBusy(false);
    }
  }

  React.useEffect(() => {
    if (!lastCaseNumberRef.current) return;
    void loadByCaseNumber(lastCaseNumberRef.current);
  }, [refreshKey]);

  React.useEffect(() => {
    if (typeof window === "undefined") return;
    if (!normalizedTenant) return;

    const qs = new URLSearchParams(window.location.search);
    const qsCaseRaw = qs.get("caseNumber");
    const qsActionRaw = qs.get("estimateAction");
    const qsTokenRaw = qs.get("token");

    const qsCase = typeof qsCaseRaw === "string" ? qsCaseRaw : "";
    const qsAction = typeof qsActionRaw === "string" ? qsActionRaw : "";
    const qsToken = typeof qsTokenRaw === "string" ? qsTokenRaw : "";

    if (qsCase !== "" && caseNumber.trim() === "") {
      setCaseNumber(qsCase);
    }

    if (qsCase === "" || qsAction === "" || qsToken === "") return;
    if (estimateActionBusy) return;

    const action = qsAction === "approve" || qsAction === "reject" ? qsAction : null;
    if (!action) return;

    let cancelled = false;

    async function run() {
      setEstimateActionError(null);
      setEstimateActionSuccess(null);
      setEstimateActionBusy(true);

      try {
        await loadByCaseNumber(qsCase);

        await apiFetch<{ message: string; status: string }>(
          `/api/t/${normalizedTenant}/estimates/${encodeURIComponent(qsCase)}/${action}?token=${encodeURIComponent(qsToken)}`,
          {
            method: "GET",
            token: null,
            impersonationSessionId: null,
          },
        );

        if (cancelled) return;
        setEstimateActionSuccess(action === "approve" ? "Estimate approved." : "Estimate rejected.");

        try {
          const nextUrl = `/t/${normalizedTenant}/status?caseNumber=${encodeURIComponent(qsCase)}`;
          window.history.replaceState({}, "", nextUrl);
        } catch {
          // ignore
        }

        setRefreshKey((x) => x + 1);
      } catch (e) {
        if (cancelled) return;
        if (e instanceof ApiError) {
          setEstimateActionError(e.message);
        } else {
          setEstimateActionError(e instanceof Error ? e.message : "Failed to process estimate action.");
        }
      } finally {
        if (cancelled) return;
        setEstimateActionBusy(false);
      }
    }

    void run();

    return () => {
      cancelled = true;
    };
  }, [caseNumber, estimateActionBusy, normalizedTenant]);

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

              {estimateActionBusy ? (
                <Alert variant="info" title="Processing estimate">
                  Please wait...
                </Alert>
              ) : null}

              {estimateActionSuccess ? (
                <Alert variant="success" title="Estimate updated">
                  {estimateActionSuccess}
                </Alert>
              ) : null}

              {estimateActionError ? (
                <Alert variant="danger" title="Could not update estimate">
                  {estimateActionError}
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
                      <Badge variant={statusBadgeVariant(job.status)}>{job.status_label ?? job.status.replace(/_/g, " ")}</Badge>
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

                  <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-4">
                    <div className="text-sm font-semibold text-[var(--rb-text)]">Send a message to the shop</div>
                    <div className="mt-1 text-xs text-zinc-500">Reference: {job.case_number}</div>

                    {messageSuccess ? (
                      <div className="mt-3">
                        <Alert variant="success" title="Message sent">
                          {messageSuccess}
                        </Alert>
                      </div>
                    ) : null}

                    {messageError ? (
                      <div className="mt-3">
                        <Alert variant="danger" title="Could not send message">
                          {messageError}
                        </Alert>
                      </div>
                    ) : null}

                    <textarea
                      value={messageBody}
                      onChange={(e) => setMessageBody(e.target.value)}
                      placeholder="Write your message..."
                      className="mt-3 w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                      rows={4}
                      disabled={messageBusy}
                    />

                    <div className="mt-3 flex items-center justify-end">
                      <Button onClick={() => void postMessage()} disabled={messageBusy || messageBody.trim().length === 0}>
                        {messageBusy ? "Sending..." : "Send message"}
                      </Button>
                    </div>
                  </div>
                </div>
              ) : null}

              {estimate ? (
                <div className="space-y-4">
                  <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-4">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                      <div className="min-w-0">
                        <div className="text-sm font-semibold text-[var(--rb-text)]">Case {estimate.case_number}</div>
                        <div className="mt-1 text-sm text-zinc-600">{estimate.title}</div>
                        <div className="mt-2 text-xs text-zinc-500">Last updated: {new Date(estimate.updated_at).toLocaleString()}</div>
                      </div>
                      <Badge variant="warning">{estimate.status_label ?? estimate.status.replace(/_/g, " ")}</Badge>
                    </div>

                    <div className="mt-4 flex flex-wrap items-center gap-2">
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => {
                          if (!normalizedTenant) return;
                          router.push(`/t/${normalizedTenant}/portal/estimates`);
                        }}
                      >
                        View estimate
                      </Button>
                      <Button variant="outline" size="sm" onClick={() => setRefreshKey((x) => x + 1)}>
                        Refresh
                      </Button>
                    </div>
                  </div>

                  <Alert variant="info" title="Estimate case">
                    This case currently has an estimate (quote). If you have questions, you can approve/reject via the links in your email or from the portal.
                  </Alert>
                </div>
              ) : null}
            </div>
          </CardContent>
        </Card>
      </div>
    </PublicPageShell>
  );
}
