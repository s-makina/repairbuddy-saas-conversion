"use client";

import React from "react";
import Link from "next/link";
import { useParams } from "next/navigation";
import { Card, CardContent } from "@/components/ui/Card";
import { Badge } from "@/components/ui/Badge";
import { PortalShell } from "@/components/shells/PortalShell";
import { mockApi } from "@/mock/mockApi";

export default function PortalHomePage() {
  const params = useParams() as { tenant?: string };
  const tenantSlug = params.tenant;

  const [loading, setLoading] = React.useState(true);
  const [jobsCount, setJobsCount] = React.useState(0);
  const [pendingEstimates, setPendingEstimates] = React.useState(0);

  React.useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        const bundle = await mockApi.getBundle();
        if (!alive) return;

        setJobsCount(Array.isArray(bundle.jobs) ? bundle.jobs.length : 0);
        setPendingEstimates(Array.isArray(bundle.estimates) ? bundle.estimates.filter((e) => e.status === "pending").length : 0);
      } finally {
        if (!alive) return;
        setLoading(false);
      }
    }

    void load();

    return () => {
      alive = false;
    };
  }, []);

  return (
    <PortalShell
      tenantSlug={typeof tenantSlug === "string" ? tenantSlug : ""}
      title="Customer Portal"
      subtitle="View tickets, approvals, and updates."
      actions={
        <Link
          href={typeof tenantSlug === "string" ? `/t/${tenantSlug}/status` : "/"}
          className="inline-flex h-9 items-center justify-center rounded-[var(--rb-radius-sm)] bg-[var(--rb-blue)] px-3 text-sm font-medium text-white"
        >
          Status check
        </Link>
      }
    >
      <div className="space-y-6">
        {loading ? <div className="text-sm text-zinc-500">Loading portal...</div> : null}

        <div className="grid gap-4 sm:grid-cols-2">
          <Card className="shadow-none">
            <CardContent className="pt-5">
              <div className="flex items-start justify-between gap-3">
                <div>
                  <div className="text-sm font-semibold text-[var(--rb-text)]">My tickets</div>
                  <div className="mt-1 text-sm text-zinc-600">All jobs associated with your case numbers.</div>
                </div>
                <Badge variant="info">{jobsCount}</Badge>
              </div>
              <div className="mt-4">
                <Link href={typeof tenantSlug === "string" ? `/t/${tenantSlug}/portal/tickets` : "/"} className="text-sm text-[var(--rb-blue)] hover:underline">
                  View tickets
                </Link>
              </div>
            </CardContent>
          </Card>

          <Card className="shadow-none">
            <CardContent className="pt-5">
              <div className="flex items-start justify-between gap-3">
                <div>
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Pending approvals</div>
                  <div className="mt-1 text-sm text-zinc-600">Estimates waiting for your decision.</div>
                </div>
                <Badge variant={pendingEstimates > 0 ? "warning" : "success"}>{pendingEstimates}</Badge>
              </div>
              <div className="mt-4">
                <Link href={typeof tenantSlug === "string" ? `/t/${tenantSlug}/portal/estimates` : "/"} className="text-sm text-[var(--rb-blue)] hover:underline">
                  View estimates
                </Link>
              </div>
            </CardContent>
          </Card>
        </div>

        <Card className="shadow-none">
          <CardContent className="pt-5">
            <div className="text-sm font-semibold text-[var(--rb-text)]">Quick links</div>
            <div className="mt-3 grid gap-2 sm:grid-cols-2">
              <Link href={typeof tenantSlug === "string" ? `/t/${tenantSlug}/book` : "/"} className="rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm text-zinc-700 hover:bg-[var(--rb-surface-muted)]">
                Book an appointment
              </Link>
              <Link href={typeof tenantSlug === "string" ? `/t/${tenantSlug}/quote` : "/"} className="rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm text-zinc-700 hover:bg-[var(--rb-surface-muted)]">
                Request a quote
              </Link>
              <Link href={typeof tenantSlug === "string" ? `/t/${tenantSlug}/services` : "/"} className="rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm text-zinc-700 hover:bg-[var(--rb-surface-muted)]">
                Services
              </Link>
              <Link href={typeof tenantSlug === "string" ? `/t/${tenantSlug}/parts` : "/"} className="rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm text-zinc-700 hover:bg-[var(--rb-surface-muted)]">
                Parts
              </Link>
            </div>
          </CardContent>
        </Card>
      </div>
    </PortalShell>
  );
}
