"use client";

import React from "react";
import Link from "next/link";
import { useParams, useRouter } from "next/navigation";
import { Alert } from "@/components/ui/Alert";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/Card";
import { DetailPageShell } from "@/components/shells/DetailPageShell";
import { CardSkeleton, Skeleton } from "@/components/ui/Skeleton";
import { apiFetch, ApiError } from "@/lib/api";

type ApiClient = {
  id: number;
  name: string;
  email: string | null;
  phone: string | null;
  company: string | null;
  tax_id: string | null;
  address_line1: string | null;
  address_line2: string | null;
  address_city: string | null;
  address_state: string | null;
  address_postal_code: string | null;
  address_country: string | null;
  created_at: string;
  jobs_count: number;
};

type ApiClientJob = {
  id: number;
  case_number: string;
  title: string;
  status: string;
  updated_at: string;
};

export default function TenantClientDetailPage() {
  const router = useRouter();
  const params = useParams() as { tenant?: string; business?: string; clientId?: string };
  const tenantSlug = params.business ?? params.tenant;
  const clientId = params.clientId;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);

  const [client, setClient] = React.useState<ApiClient | null>(null);
  const [jobs, setJobs] = React.useState<ApiClientJob[]>([]);

  React.useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);

        if (!clientId) {
          setError("Client ID is missing.");
          setClient(null);
          return;
        }

        if (typeof tenantSlug !== "string" || tenantSlug.length === 0) {
          throw new Error("Business is missing.");
        }

        const [clientRes, jobsRes] = await Promise.all([
          apiFetch<{ client: ApiClient }>(`/api/${tenantSlug}/app/clients/${clientId}`),
          apiFetch<{ jobs: ApiClientJob[] }>(`/api/${tenantSlug}/app/clients/${clientId}/jobs`),
        ]);

        if (!alive) return;

        setClient(clientRes.client ?? null);
        setJobs(Array.isArray(jobsRes.jobs) ? jobsRes.jobs : []);
      } catch (e) {
        if (!alive) return;

        if (e instanceof ApiError && e.status === 428 && typeof tenantSlug === "string" && tenantSlug.length > 0) {
          const next = `/app/${tenantSlug}/clients/${clientId}`;
          router.replace(`/app/${tenantSlug}/branches/select?next=${encodeURIComponent(next)}`);
          return;
        }

        setError(e instanceof Error ? e.message : "Failed to load client.");
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
  }, [clientId, router, tenantSlug]);

  const clientJobs = React.useMemo(() => {
    return jobs.slice().sort((a, b) => (a.updated_at < b.updated_at ? 1 : -1));
  }, [jobs]);

  function getJobBadgeVariant(status: string): "default" | "info" | "success" | "warning" | "danger" {
    const s = (status ?? "").toLowerCase();
    if (s.includes("done") || s.includes("complete") || s.includes("closed") || s.includes("paid")) return "success";
    if (s.includes("cancel") || s.includes("failed") || s.includes("declin")) return "danger";
    if (s.includes("hold") || s.includes("await") || s.includes("pending") || s.includes("quote")) return "warning";
    if (s.includes("progress") || s.includes("work") || s.includes("repair") || s.includes("open")) return "info";
    return "default";
  }

  const addressSummary = React.useMemo(() => {
    if (!client) return "—";
    const parts = [
      client.address_line1,
      client.address_line2,
      [client.address_city, client.address_state].filter(Boolean).join(", "),
      client.address_postal_code,
      client.address_country,
    ]
      .map((p) => (typeof p === "string" ? p.trim() : ""))
      .filter(Boolean);
    return parts.length > 0 ? parts.join("\n") : "—";
  }, [client]);

  return (
    <div className="space-y-6">
      {error ? (
        <Alert variant="danger" title="Could not load customer">
          {error}
        </Alert>
      ) : null}

      {loading ? (
        <div className="space-y-6">
          <div className="space-y-2">
            <Skeleton className="h-4 w-48 rounded-[var(--rb-radius-sm)]" />
            <Skeleton className="h-9 w-72 rounded-[var(--rb-radius-sm)]" />
          </div>
          <div className="grid gap-4 lg:grid-cols-3">
            <CardSkeleton lines={5} className="shadow-none" />
            <CardSkeleton lines={5} className="shadow-none" />
            <CardSkeleton lines={5} className="shadow-none" />
          </div>
          <CardSkeleton lines={7} className="shadow-none" />
        </div>
      ) : null}

      {client && !loading ? (
        <DetailPageShell
          // breadcrumb={
          //   <span>
          //     <Link href={typeof tenantSlug === "string" ? `/app/${tenantSlug}/clients` : "/app"} className="hover:text-[var(--rb-text)]">
          //       Customers
          //     </Link>
          //     <span className="px-2">/</span>
          //     <span>{client.name}</span>
          //   </span>
          // }
          // backHref={typeof tenantSlug === "string" ? `/app/${tenantSlug}/clients` : "/app"}
          title={client.name}
          description={client.email ?? client.phone ?? "Customer details"}
          actions={
            <div className="flex items-center gap-2">
              <Button
                variant="outline"
                size="sm"
                onClick={() => {
                  router.back();
                }}
              >
                Back
              </Button>
              <Button
                variant="primary"
                size="sm"
                onClick={() => {
                  if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
                  router.push(`/app/${tenantSlug}/clients/${client.id}/edit`);
                }}
              >
                Edit
              </Button>
            </div>
          }
          tabs={{
            overview: (
              <div className="space-y-4">
                <div className="flex flex-wrap items-center gap-2">
                  <Badge variant="info">Customer</Badge>
                  <Badge variant="default">ID: {client.id}</Badge>
                  <Badge variant={client.jobs_count > 0 ? "info" : "default"}>Jobs: {client.jobs_count ?? 0}</Badge>
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                  <Card className="shadow-none">
                    <CardHeader>
                      <CardTitle>Contact</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                      <div>
                        <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Email</div>
                        {client.email ? (
                          <a className="mt-1 block text-sm text-[var(--rb-blue)] hover:underline" href={`mailto:${client.email}`}>
                            {client.email}
                          </a>
                        ) : (
                          <div className="mt-1 text-sm text-zinc-700">—</div>
                        )}
                      </div>
                      <div>
                        <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Phone</div>
                        {client.phone ? (
                          <a className="mt-1 block text-sm text-[var(--rb-blue)] hover:underline" href={`tel:${client.phone}`}>
                            {client.phone}
                          </a>
                        ) : (
                          <div className="mt-1 text-sm text-zinc-700">—</div>
                        )}
                      </div>
                    </CardContent>
                  </Card>

                  <Card className="shadow-none">
                    <CardHeader>
                      <CardTitle>Company</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                      <div>
                        <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Company name</div>
                        <div className="mt-1 text-sm text-zinc-700">{client.company ?? "—"}</div>
                      </div>
                      <div>
                        <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Tax ID</div>
                        <div className="mt-1 text-sm text-zinc-700">{client.tax_id ?? "—"}</div>
                      </div>
                    </CardContent>
                  </Card>

                  <Card className="shadow-none">
                    <CardHeader>
                      <CardTitle>Meta</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                      <div>
                        <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Created</div>
                        <div className="mt-1 text-sm text-zinc-700">{new Date(client.created_at).toLocaleString()}</div>
                      </div>
                      <div>
                        <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Last update</div>
                        <div className="mt-1 text-sm text-zinc-700">{clientJobs[0]?.updated_at ? new Date(clientJobs[0].updated_at).toLocaleString() : "—"}</div>
                      </div>
                    </CardContent>
                  </Card>
                </div>

                <Card className="shadow-none">
                  <CardHeader>
                    <CardTitle>Address</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="whitespace-pre-wrap text-sm text-zinc-700">{addressSummary}</div>
                  </CardContent>
                </Card>
              </div>
            ),
            timeline: (
              <Card className="shadow-none">
                <CardHeader>
                  <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <CardTitle>Jobs</CardTitle>
                    <div className="text-sm text-zinc-600">{clientJobs.length} total</div>
                  </div>
                </CardHeader>
                <CardContent>
                  {clientJobs.length === 0 ? <div className="text-sm text-zinc-600">No jobs for this customer.</div> : null}

                  <div className="mt-2 divide-y divide-[var(--rb-border)] rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white">
                    {clientJobs.map((j) => (
                      <div key={j.id} className="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div className="min-w-0">
                          <div className="flex flex-wrap items-center gap-2">
                            <div className="truncate text-sm font-semibold text-[var(--rb-text)]">{j.case_number}</div>
                            {j.status ? <Badge variant={getJobBadgeVariant(j.status)}>{j.status}</Badge> : null}
                          </div>
                          <div className="mt-1 truncate text-sm text-zinc-600">{j.title}</div>
                          <div className="mt-2 text-xs text-zinc-500">Updated: {new Date(j.updated_at).toLocaleString()}</div>
                        </div>
                        <div className="flex items-center justify-end gap-2">
                          <Button
                            variant="outline"
                            size="sm"
                            onClick={() => {
                              if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
                              router.push(`/app/${tenantSlug}/jobs/${j.id}`);
                            }}
                          >
                            View
                          </Button>
                        </div>
                      </div>
                    ))}
                  </div>
                </CardContent>
              </Card>
            ),
            messages: (
              <Card className="shadow-none">
                <CardHeader>
                  <CardTitle>Devices</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="mt-4 space-y-3">
                    <div className="text-sm text-zinc-600">Customer devices will appear here after EPIC 5 (devices + customer devices) is enabled.</div>
                  </div>
                </CardContent>
              </Card>
            ),
            financial: (
              <Card className="shadow-none">
                <CardHeader>
                  <CardTitle>Financial</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="mt-2 text-sm text-zinc-600">Customer-level financials are wired in later phases.</div>
                </CardContent>
              </Card>
            ),
            print: (
              <Card className="shadow-none">
                <CardHeader>
                  <CardTitle>Print</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="mt-2 text-sm text-zinc-600">Printable customer summaries are implemented in later phases.</div>
                </CardContent>
              </Card>
            ),
          }}
        />
      ) : null}
    </div>
  );
}
