"use client";

import React from "react";
import Link from "next/link";
import { useParams } from "next/navigation";
import { PortalShell } from "@/components/shells/PortalShell";
import { Alert } from "@/components/ui/Alert";
import { Badge } from "@/components/ui/Badge";
import { Card, CardContent } from "@/components/ui/Card";
import { mockApi } from "@/mock/mockApi";
import type { Appointment } from "@/mock/types";

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

export default function PortalBookingPage() {
  const params = useParams() as { tenant?: string };
  const tenantSlug = typeof params.tenant === "string" ? params.tenant : "";

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [session, setSession] = React.useState<PortalSession | null>(null);
  const [appointments, setAppointments] = React.useState<Appointment[]>([]);

  React.useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);

        if (!tenantSlug) {
          setSession(null);
          setAppointments([]);
          return;
        }

        const s = loadPortalSession(tenantSlug);
        setSession(s);

        if (!s) {
          setAppointments([]);
          return;
        }

        const appts = await mockApi.listAppointments();
        if (!alive) return;

        const sorted = (Array.isArray(appts) ? appts : []).slice().sort((a, b) => (a.created_at < b.created_at ? 1 : -1));
        setAppointments(sorted.slice(0, 10));
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load bookings.");
        setAppointments([]);
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
    <PortalShell tenantSlug={tenantSlug} title="Booking" subtitle="Your appointment requests (mock).">
      <div className="space-y-4">
        {loading ? <div className="text-sm text-zinc-500">Loading bookings...</div> : null}

        {error ? (
          <Alert variant="danger" title="Could not load bookings">
            {error}
          </Alert>
        ) : null}

        {!loading && !error && !session ? (
          <Alert variant="warning" title="Portal locked">
            Enter your case number to view booking history.
          </Alert>
        ) : null}

        {session ? (
          <Card className="shadow-none">
            <CardContent className="pt-5">
              <div className="text-sm text-zinc-600">
                Need a new appointment?{" "}
                <Link href={`/t/${tenantSlug}/book`} className="font-semibold text-[var(--rb-blue)] hover:underline">
                  Book now
                </Link>
                .
              </div>
            </CardContent>
          </Card>
        ) : null}

        {!loading && !error && session && appointments.length === 0 ? (
          <Alert variant="info" title="No bookings yet">
            You haven’t requested any appointments yet.
          </Alert>
        ) : null}

        {appointments.length > 0 ? (
          <div className="space-y-3">
            {appointments.map((a) => (
              <Card key={a.id} className="shadow-none">
                <CardContent className="pt-5">
                  <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="min-w-0">
                      <div className="truncate text-sm font-semibold text-[var(--rb-text)]">{a.client_name}</div>
                      <div className="mt-1 text-sm text-zinc-600">{new Date(a.scheduled_at).toLocaleString()}</div>
                      <div className="mt-2 text-xs text-zinc-500">Requested: {new Date(a.created_at).toLocaleString()}</div>
                    </div>
                    <Badge variant={a.status === "requested" ? "warning" : a.status === "confirmed" ? "success" : "danger"}>{a.status}</Badge>
                  </div>
                  {a.notes ? <div className="mt-2 text-sm text-zinc-700">{a.notes}</div> : null}
                </CardContent>
              </Card>
            ))}
          </div>
        ) : null}
      </div>
    </PortalShell>
  );
}
