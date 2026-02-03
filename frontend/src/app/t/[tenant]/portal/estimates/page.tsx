"use client";

import React from "react";
import Link from "next/link";
import { useParams } from "next/navigation";
import { PortalShell } from "@/components/shells/PortalShell";
import { Alert } from "@/components/ui/Alert";
import { Badge } from "@/components/ui/Badge";
import { Card, CardContent } from "@/components/ui/Card";
import { apiFetch, ApiError } from "@/lib/api";
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

type ApiPortalJob = {
  id: number;
  case_number: string;
};

type ApiPortalEstimate = {
  id: number;
  case_number: string;
  title: string;
  status: string;
  items: Array<{
    id: number;
    name: string;
    qty: number;
    unit_price: { currency: string; amount_cents: number };
  }>;
  totals: {
    currency: string;
    subtotal_cents: number;
    tax_cents: number;
    total_cents: number;
  };
};

function estimateBadgeVariant(status: string): "default" | "success" | "warning" | "danger" {
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

  const [job, setJob] = React.useState<ApiPortalJob | null>(null);
  const [estimate, setEstimate] = React.useState<ApiPortalEstimate | null>(null);

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

        const jobRes = await apiFetch<{ job: ApiPortalJob }>(`/api/t/${tenantSlug}/status/lookup`, {
          method: "POST",
          body: { caseNumber: s.caseNumber },
          token: null,
          impersonationSessionId: null,
        });

        const foundJob = jobRes?.job ?? null;
        if (!alive) return;
        setJob(foundJob);

        if (!foundJob) {
          setEstimate(null);
          return;
        }

        const listRes = await apiFetch<{ estimates: Array<{ id: number }> }>(`/api/t/${tenantSlug}/portal/estimates?caseNumber=${encodeURIComponent(s.caseNumber)}`, {
          method: "GET",
          token: null,
          impersonationSessionId: null,
        });

        const estimateId = listRes?.estimates?.[0]?.id ?? null;
        if (!estimateId) {
          setEstimate(null);
          return;
        }

        const detailRes = await apiFetch<{ estimate: ApiPortalEstimate }>(
          `/api/t/${tenantSlug}/portal/estimates/${estimateId}?caseNumber=${encodeURIComponent(s.caseNumber)}`,
          {
            method: "GET",
            token: null,
            impersonationSessionId: null,
          },
        );

        if (!alive) return;
        setEstimate(detailRes?.estimate ?? null);
      } catch (e) {
        if (!alive) return;
        if (e instanceof ApiError) {
          setError(e.message);
        } else {
          setError(e instanceof Error ? e.message : "Failed to load estimates.");
        }
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

  const totalCents = estimate?.totals?.total_cents ?? 0;
  const currency = estimate?.totals?.currency ?? "USD";

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
                {(estimate.items ?? []).map((line) => (
                  <div key={line.id} className="flex items-start justify-between gap-3 rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-3">
                    <div className="min-w-0">
                      <div className="truncate text-sm font-semibold text-[var(--rb-text)]">{line.name}</div>
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
