"use client";

import React from "react";
import Link from "next/link";
import { useParams, useRouter } from "next/navigation";
import { Alert } from "@/components/ui/Alert";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { DetailPageShell } from "@/components/shells/DetailPageShell";
import { apiFetch, ApiError } from "@/lib/api";
import { useAuth } from "@/lib/auth";
import { formatMoney } from "@/lib/money";
import { notify } from "@/lib/notify";

type ApiEstimateDetail = {
  id: number;
  case_number: string;
  title: string;
  status: string;
  converted_job_id?: number | null;
  customer: null | {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    company: string | null;
  };
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
  created_at: string;
  updated_at: string;
};

function estimateBadgeVariant(status: string): "default" | "success" | "warning" | "danger" {
  if (status === "approved") return "success";
  if (status === "rejected") return "danger";
  return "warning";
}

export default function TenantEstimateDetailPage() {
  const router = useRouter();
  const auth = useAuth();
  const params = useParams() as { tenant?: string; business?: string; estimateId?: string };
  const tenantSlug = params.business ?? params.tenant;
  const estimateId = params.estimateId;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);

  const [estimate, setEstimate] = React.useState<ApiEstimateDetail | null>(null);

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
          return;
        }

        if (typeof tenantSlug !== "string" || tenantSlug.trim().length === 0) {
          setError("Tenant is missing.");
          setEstimate(null);
          return;
        }

        const res = await apiFetch<{ estimate: ApiEstimateDetail }>(`/api/${tenantSlug}/app/repairbuddy/estimates/${estimateId}`, {
          method: "GET",
        });
        if (!alive) return;

        const e = res?.estimate ?? null;
        if (!e) {
          setError("Estimate not found.");
          setEstimate(null);
          return;
        }

        setEstimate(e);
      } catch (e) {
        if (!alive) return;
        if (e instanceof ApiError && e.status === 428 && typeof tenantSlug === "string" && tenantSlug.trim().length > 0) {
          const next = `/app/${tenantSlug}/estimates/${estimateId}`;
          router.replace(`/app/${tenantSlug}/branches/select?next=${encodeURIComponent(next)}`);
          return;
        }
        if (e instanceof ApiError) {
          setError(e.message);
        } else {
          setError(e instanceof Error ? e.message : "Failed to load estimate.");
        }
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
  }, [estimateId, refreshKey, router, tenantSlug]);

  const totalCents = estimate?.totals?.total_cents ?? 0;
  const currency = estimate?.totals?.currency ?? "USD";
  const convertedJobId = estimate?.converted_job_id ?? null;
  const isConverted = typeof convertedJobId === "number" && convertedJobId > 0;

  async function setStatus(status: string) {
    if (!estimate) return;
    setBusy(true);
    setActionError(null);
    try {
      if (typeof tenantSlug !== "string" || tenantSlug.trim().length === 0) throw new Error("Tenant is missing.");

      await apiFetch<{ estimate: ApiEstimateDetail }>(`/api/${tenantSlug}/app/repairbuddy/estimates/${estimate.id}`, {
        method: "PATCH",
        body: { status },
      });
      setRefreshKey((x) => x + 1);
    } catch (e) {
      if (e instanceof ApiError) {
        setActionError(e.message);
      } else {
        setActionError(e instanceof Error ? e.message : "Failed to update estimate.");
      }
    } finally {
      setBusy(false);
    }
  }

  async function sendEstimate() {
    if (!estimate) return;
    if (busy) return;
    if (!auth.can("estimates.manage")) return;

    setBusy(true);
    setActionError(null);
    try {
      if (typeof tenantSlug !== "string" || tenantSlug.trim().length === 0) throw new Error("Tenant is missing.");

      await apiFetch<{ message: string }>(`/api/${tenantSlug}/app/repairbuddy/estimates/${estimate.id}/send`, {
        method: "POST",
      });

      notify.success("Estimate sent.");
      setRefreshKey((x) => x + 1);
    } catch (e) {
      if (e instanceof ApiError) {
        setActionError(e.message);
      } else {
        setActionError(e instanceof Error ? e.message : "Failed to send estimate.");
      }
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
          title={estimate.case_number}
          description={estimate.title}
          actions={
            <div className="flex flex-wrap items-center gap-2">
              <Badge variant={estimateBadgeVariant(estimate.status)}>{estimate.status}</Badge>
              {isConverted && typeof tenantSlug === "string" ? (
                <Link href={`/app/${tenantSlug}/jobs/${convertedJobId}`} className="text-sm text-[var(--rb-text)] underline">
                  View Job
                </Link>
              ) : null}
              <Button
                variant="outline"
                size="sm"
                disabled={
                  busy ||
                  !auth.can("estimates.manage") ||
                  !estimate.customer ||
                  !estimate.customer.email ||
                  estimate.customer.email.trim().length === 0
                }
                onClick={() => void sendEstimate()}
              >
                Send
              </Button>
              <Button
                variant="outline"
                size="sm"
                disabled={busy || estimate.status !== "pending" || isConverted}
                onClick={() => void setStatus("rejected")}
              >
                Reject
              </Button>
              <Button disabled={busy || estimate.status !== "pending" || isConverted} size="sm" onClick={() => void setStatus("approved")}>
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
                      <div className="mt-1 text-sm text-zinc-700">{estimate.customer?.name ?? "—"}</div>
                      <div className="mt-1 text-xs text-zinc-500">{estimate.customer?.email ?? ""}</div>
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
                  </div>
                </CardContent>
              </Card>
            ),
            timeline: (
              <Card className="shadow-none">
                <CardContent className="pt-5">
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Line items</div>
                  <div className="mt-4 space-y-2">
                    {(estimate.items ?? []).map((line) => (
                      <div key={line.id} className="flex items-start justify-between gap-3 rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-3">
                        <div className="min-w-0">
                          <div className="truncate text-sm font-semibold text-[var(--rb-text)]">{line.name}</div>
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
