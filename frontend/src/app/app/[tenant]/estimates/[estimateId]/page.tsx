"use client";

import React from "react";
import Link from "next/link";
import { useParams, useRouter } from "next/navigation";
import { Alert } from "@/components/ui/Alert";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { DetailPageShell } from "@/components/shells/DetailPageShell";
import { mockApi } from "@/mock/mockApi";
import type { Client, Estimate, Job } from "@/mock/types";
import { formatMoney } from "@/lib/money";

function estimateBadgeVariant(status: Estimate["status"]): "default" | "success" | "warning" | "danger" {
  if (status === "approved") return "success";
  if (status === "rejected") return "danger";
  return "warning";
}

export default function TenantEstimateDetailPage() {
  const router = useRouter();
  const params = useParams() as { tenant?: string; business?: string; estimateId?: string };
  const tenantSlug = params.business ?? params.tenant;
  const estimateId = params.estimateId;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);

  const [estimate, setEstimate] = React.useState<Estimate | null>(null);
  const [job, setJob] = React.useState<Job | null>(null);
  const [client, setClient] = React.useState<Client | null>(null);

  const [busy, setBusy] = React.useState(false);
  const [actionError, setActionError] = React.useState<string | null>(null);
  const [refreshKey, setRefreshKey] = React.useState(0);

  React.useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);

        if (!estimateId) {
          setError("Estimate ID is missing.");
          setEstimate(null);
          setJob(null);
          setClient(null);
          return;
        }

        const e = await mockApi.getEstimate(estimateId as any);
        if (!alive) return;

        if (!e) {
          setError("Estimate not found.");
          setEstimate(null);
          setJob(null);
          setClient(null);
          return;
        }

        setEstimate(e);

        const [j, c] = await Promise.all([mockApi.getJob(e.job_id as any), mockApi.getClient(e.client_id as any)]);
        if (!alive) return;
        setJob(j);
        setClient(c);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load estimate.");
        setEstimate(null);
        setJob(null);
        setClient(null);
      } finally {
        if (!alive) return;
        setLoading(false);
      }
    }

    void load();

    return () => {
      alive = false;
    };
  }, [estimateId, refreshKey]);

  const totalCents = React.useMemo(() => (estimate ? mockApi.computeEstimateTotalCents(estimate) : 0), [estimate]);
  const currency = estimate?.lines[0]?.unit_price.currency ?? "USD";

  async function setStatus(status: Estimate["status"]) {
    if (!estimate) return;
    setBusy(true);
    setActionError(null);
    try {
      await mockApi.setEstimateStatus({ estimateId: estimate.id, status });
      setRefreshKey((x) => x + 1);
    } catch (e) {
      setActionError(e instanceof Error ? e.message : "Failed to update estimate.");
    } finally {
      setBusy(false);
    }
  }

  return (
    <div className="space-y-6">
      {error ? (
        <Alert variant="danger" title="Could not load estimate">
          {error}
        </Alert>
      ) : null}

      {loading ? <div className="text-sm text-zinc-500">Loading estimate...</div> : null}

      {estimate ? (
        <DetailPageShell
          breadcrumb={
            <span>
              <Link href={typeof tenantSlug === "string" ? `/app/${tenantSlug}/estimates` : "/app"} className="hover:text-[var(--rb-text)]">
                Estimates
              </Link>
              <span className="px-2">/</span>
              <span>{estimate.id}</span>
            </span>
          }
          backHref={typeof tenantSlug === "string" ? `/app/${tenantSlug}/estimates` : "/app"}
          title={estimate.id}
          description={job?.case_number ? `Case ${job.case_number}` : estimate.job_id}
          actions={
            <div className="flex flex-wrap items-center gap-2">
              <Badge variant={estimateBadgeVariant(estimate.status)}>{estimate.status}</Badge>
              <Button
                variant="outline"
                size="sm"
                disabled={busy || estimate.status !== "pending"}
                onClick={() => void setStatus("rejected")}
              >
                Reject
              </Button>
              <Button disabled={busy || estimate.status !== "pending"} size="sm" onClick={() => void setStatus("approved")}>
                Approve
              </Button>
            </div>
          }
          tabs={{
            overview: (
              <Card className="shadow-none">
                <CardContent className="pt-5">
                  {actionError ? (
                    <Alert variant="danger" title="Could not update estimate">
                      {actionError}
                    </Alert>
                  ) : null}

                  <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                      <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Client</div>
                      <div className="mt-1 text-sm text-zinc-700">{client?.name ?? estimate.client_id}</div>
                    </div>
                    <div>
                      <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Total</div>
                      <div className="mt-1 text-sm font-semibold text-[var(--rb-text)]">{formatMoney({ amountCents: totalCents, currency })}</div>
                    </div>
                    <div>
                      <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Created</div>
                      <div className="mt-1 text-sm text-zinc-700">{new Date(estimate.created_at).toLocaleString()}</div>
                    </div>
                    <div>
                      <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Updated</div>
                      <div className="mt-1 text-sm text-zinc-700">{new Date(estimate.updated_at).toLocaleString()}</div>
                    </div>
                    {job ? (
                      <div className="sm:col-span-2">
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => {
                            if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
                            router.push(`/app/${tenantSlug}/jobs/${job.id}`);
                          }}
                        >
                          View job
                        </Button>
                      </div>
                    ) : null}
                  </div>
                </CardContent>
              </Card>
            ),
            timeline: (
              <Card className="shadow-none">
                <CardContent className="pt-5">
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Line items</div>
                  <div className="mt-4 space-y-2">
                    {estimate.lines.map((line) => (
                      <div key={line.id} className="flex items-start justify-between gap-3 rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-3">
                        <div className="min-w-0">
                          <div className="truncate text-sm font-semibold text-[var(--rb-text)]">{line.label}</div>
                          <div className="mt-1 text-xs text-zinc-500">Qty: {line.qty}</div>
                        </div>
                        <div className="text-sm font-semibold text-[var(--rb-text)] whitespace-nowrap">
                          {formatMoney({ amountCents: line.qty * line.unit_price.amount_cents, currency: line.unit_price.currency })}
                        </div>
                      </div>
                    ))}
                    <div className="flex items-center justify-between border-t border-[var(--rb-border)] pt-3">
                      <div className="text-sm text-zinc-700">Total</div>
                      <div className="text-sm font-semibold text-[var(--rb-text)]">{formatMoney({ amountCents: totalCents, currency })}</div>
                    </div>
                  </div>
                </CardContent>
              </Card>
            ),
            messages: (
              <Card className="shadow-none">
                <CardContent className="pt-5">
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Messages</div>
                  <div className="mt-2 text-sm text-zinc-600">Estimate-specific messages are shown on the Job detail timeline in later phases.</div>
                </CardContent>
              </Card>
            ),
            financial: (
              <Card className="shadow-none">
                <CardContent className="pt-5">
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Financial</div>
                  <div className="mt-2 text-sm text-zinc-600">Payments and invoices are implemented in later phases.</div>
                </CardContent>
              </Card>
            ),
            print: (
              <Card className="shadow-none">
                <CardContent className="pt-5">
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Print</div>
                  <div className="mt-2 text-sm text-zinc-600">Printable estimate views are implemented in later phases.</div>
                </CardContent>
              </Card>
            ),
          }}
        />
      ) : null}
    </div>
  );
}
