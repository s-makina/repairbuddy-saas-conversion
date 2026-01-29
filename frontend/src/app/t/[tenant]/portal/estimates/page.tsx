"use client";

import React from "react";
import Link from "next/link";
import { useParams } from "next/navigation";
import { PortalShell } from "@/components/shells/PortalShell";
import { Alert } from "@/components/ui/Alert";
import { Badge } from "@/components/ui/Badge";
import { Card, CardContent } from "@/components/ui/Card";
import { mockApi } from "@/mock/mockApi";
import type { Estimate, EstimateId, Job, JobId } from "@/mock/types";
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

function estimateBadgeVariant(status: Estimate["status"]): "default" | "success" | "warning" | "danger" {
  if (status === "approved") return "success";
  if (status === "rejected") return "danger";
  return "warning";
}

export default function PortalEstimatesPage() {
  const params = useParams() as { tenant?: string };
  const tenantSlug = typeof params.tenant === "string" ? params.tenant : "";

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [session, setSession] = React.useState<PortalSession | null>(null);

  const [job, setJob] = React.useState<Job | null>(null);
  const [estimate, setEstimate] = React.useState<Estimate | null>(null);

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

        const foundJob = await mockApi.getJob(s.jobId as JobId);
        if (!alive) return;
        setJob(foundJob);

        if (!foundJob?.estimate_id) {
          setEstimate(null);
          return;
        }

        const foundEstimate = await mockApi.getEstimate(foundJob.estimate_id as EstimateId);
        if (!alive) return;
        setEstimate(foundEstimate);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load estimates.");
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
  }, [tenantSlug]);

  const totalCents = React.useMemo(() => (estimate ? mockApi.computeEstimateTotalCents(estimate) : 0), [estimate]);
  const currency = estimate?.lines[0]?.unit_price.currency ?? "USD";

  return (
    <PortalShell tenantSlug={tenantSlug} title="Estimates" subtitle="Approvals and quote breakdowns.">
      <div className="space-y-4">
        {loading ? <div className="text-sm text-zinc-500">Loading estimates...</div> : null}

        {error ? (
          <Alert variant="danger" title="Could not load estimates">
            {error}
          </Alert>
        ) : null}

        {!loading && !error && !session ? (
          <Alert variant="warning" title="Portal locked">
            Enter your case number to view estimates.
          </Alert>
        ) : null}

        {!loading && !error && session && !job ? (
          <Alert variant="warning" title="Ticket not found">
            We couldn’t load your ticket.
          </Alert>
        ) : null}

        {!loading && !error && session && job && !estimate ? (
          <Card className="shadow-none">
            <CardContent className="pt-5">
              <div className="text-sm font-semibold text-[var(--rb-text)]">No estimate available</div>
              <div className="mt-1 text-sm text-zinc-600">This ticket does not have an estimate attached yet.</div>
              <div className="mt-4">
                <Link href={`/t/${tenantSlug}/status`} className="text-sm font-semibold text-[var(--rb-blue)] hover:underline">
                  View public status
                </Link>
              </div>
            </CardContent>
          </Card>
        ) : null}

        {estimate ? (
          <Card className="shadow-none">
            <CardContent className="pt-5">
              <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Estimate</div>
                  <div className="mt-1 text-sm text-zinc-600">For case {job?.case_number ?? "—"}</div>
                </div>
                <Badge variant={estimateBadgeVariant(estimate.status)}>{estimate.status}</Badge>
              </div>

              <div className="mt-4 space-y-2">
                {estimate.lines.map((line) => (
                  <div key={line.id} className="flex items-start justify-between gap-3 rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-3">
                    <div className="min-w-0">
                      <div className="truncate text-sm font-semibold text-[var(--rb-text)]">{line.label}</div>
                      <div className="mt-1 text-xs text-zinc-500">Qty: {line.qty}</div>
                    </div>
                    <div className="whitespace-nowrap text-sm font-semibold text-[var(--rb-text)]">
                      {formatMoney({ amountCents: line.qty * line.unit_price.amount_cents, currency: line.unit_price.currency })}
                    </div>
                  </div>
                ))}

                <div className="flex items-center justify-between border-t border-[var(--rb-border)] pt-3">
                  <div className="text-sm text-zinc-700">Total</div>
                  <div className="text-sm font-semibold text-[var(--rb-text)]">{formatMoney({ amountCents: totalCents, currency })}</div>
                </div>
              </div>

              <div className="mt-4">
                <Link href={`/t/${tenantSlug}/status`} className="text-sm font-semibold text-[var(--rb-blue)] hover:underline">
                  Approve/reject on status page
                </Link>
              </div>
            </CardContent>
          </Card>
        ) : null}
      </div>
    </PortalShell>
  );
}
