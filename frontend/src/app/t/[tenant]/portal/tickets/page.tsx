"use client";

import React from "react";
import Link from "next/link";
import { useParams } from "next/navigation";
import { PortalShell } from "@/components/shells/PortalShell";
import { Alert } from "@/components/ui/Alert";
import { Badge } from "@/components/ui/Badge";
import { Card, CardContent } from "@/components/ui/Card";
import { mockApi } from "@/mock/mockApi";
import type { Job, JobId, JobStatusKey } from "@/mock/types";

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

export default function PortalTicketsPage() {
  const params = useParams() as { tenant?: string };
  const tenantSlug = typeof params.tenant === "string" ? params.tenant : "";

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [session, setSession] = React.useState<PortalSession | null>(null);
  const [job, setJob] = React.useState<Job | null>(null);
  const [statusLabels, setStatusLabels] = React.useState<Record<string, string>>({});

  React.useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);

        if (!tenantSlug) {
          setSession(null);
          setJob(null);
          return;
        }

        const s = loadPortalSession(tenantSlug);
        setSession(s);

        if (!s) {
          setJob(null);
          return;
        }

        const [statuses, found] = await Promise.all([mockApi.getStatuses(), mockApi.getJob(s.jobId as JobId)]);
        if (!alive) return;

        const nextLabels: Record<string, string> = {};
        for (const st of Array.isArray(statuses) ? statuses : []) {
          nextLabels[st.key] = st.label;
        }
        setStatusLabels(nextLabels);

        setJob(found);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load tickets.");
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
  }, [tenantSlug]);

  return (
    <PortalShell tenantSlug={tenantSlug} title="Tickets / Jobs" subtitle="Your repair tickets and updates.">
      <div className="space-y-4">
        {loading ? <div className="text-sm text-zinc-500">Loading tickets...</div> : null}

        {error ? (
          <Alert variant="danger" title="Could not load tickets">
            {error}
          </Alert>
        ) : null}

        {!loading && !error && !session ? (
          <Alert variant="warning" title="Portal locked">
            Enter your case number to view tickets.
          </Alert>
        ) : null}

        {!loading && !error && session && !job ? (
          <Alert variant="warning" title="No ticket found">
            We couldn’t find a ticket for your current session.
          </Alert>
        ) : null}

        {job ? (
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

              <div className="mt-4">
                <Link href={`/t/${tenantSlug}/portal/tickets/${job.id}`} className="text-sm font-semibold text-[var(--rb-blue)] hover:underline">
                  View ticket
                </Link>
              </div>
            </CardContent>
          </Card>
        ) : null}
      </div>
    </PortalShell>
  );
}
