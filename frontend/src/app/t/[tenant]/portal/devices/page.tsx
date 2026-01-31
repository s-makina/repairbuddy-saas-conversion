"use client";

import React from "react";
import { useParams } from "next/navigation";
import { PortalShell } from "@/components/shells/PortalShell";
import { Alert } from "@/components/ui/Alert";
import { Badge } from "@/components/ui/Badge";
import { Card, CardContent } from "@/components/ui/Card";
import { apiFetch, ApiError } from "@/lib/api";

type PortalSession = {
  jobId: string;
  caseNumber: string;
};

type ApiJobDevice = {
  id: number;
  customer_device_id: number;
  label: string;
  serial: string | null;
  notes: string | null;
  extra_fields?: Array<{ key: string; label: string; type: string; value_text: string }> | null;
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

export default function PortalDevicesPage() {
  const params = useParams() as { tenant?: string };
  const tenantSlug = typeof params.tenant === "string" ? params.tenant : "";

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [session, setSession] = React.useState<PortalSession | null>(null);

  const [jobIdOk, setJobIdOk] = React.useState(false);
  const [jobDevices, setJobDevices] = React.useState<ApiJobDevice[]>([]);

  React.useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);

        if (!tenantSlug) {
          setSession(null);
          setJobIdOk(false);
          setJobDevices([]);
          return;
        }

        const s = loadPortalSession(tenantSlug);
        setSession(s);

        if (!s) {
          setJobIdOk(false);
          return;
        }

        const devicesRes = await apiFetch<{ devices: ApiJobDevice[] }>(`/api/t/${tenantSlug}/portal/job-devices?caseNumber=${encodeURIComponent(s.caseNumber)}`);

        if (!alive) return;

        setJobIdOk(true);
        setJobDevices(Array.isArray(devicesRes?.devices) ? devicesRes.devices : []);
      } catch (e) {
        if (!alive) return;
        if (e instanceof ApiError && e.status === 404) {
          setError("We couldn’t load your ticket.");
        } else {
          setError(e instanceof Error ? e.message : "Failed to load devices.");
        }
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

  const myDevices = React.useMemo(() => {
    return jobDevices.map((d) => ({
      id: d.id,
      label: d.label,
      serial: d.serial ?? "—",
      notes: d.notes ?? "",
      extraFields: Array.isArray(d.extra_fields) ? d.extra_fields : [],
    }));
  }, [jobDevices]);

  return (
    <PortalShell tenantSlug={tenantSlug} title="My Devices" subtitle="Devices linked to your tickets.">
      <div className="space-y-4">
        {loading ? <div className="text-sm text-zinc-500">Loading devices...</div> : null}

        {error ? (
          <Alert variant="danger" title="Could not load devices">
            {error}
          </Alert>
        ) : null}

        {!loading && !error && !session ? (
          <Alert variant="warning" title="Portal locked">
            Enter your case number to view devices.
          </Alert>
        ) : null}

        {!loading && !error && session && !jobIdOk ? (
          <Alert variant="warning" title="Ticket not found">
            We couldn’t load your ticket.
          </Alert>
        ) : null}

        {!loading && !error && session && jobIdOk && myDevices.length === 0 ? (
          <Alert variant="info" title="No devices linked">
            This ticket doesn’t have any devices linked yet.
          </Alert>
        ) : null}

        {myDevices.length > 0 ? (
          <div className="space-y-3">
            {myDevices.map((d) => (
              <Card key={d.id} className="shadow-none">
                <CardContent className="pt-5">
                  <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="min-w-0">
                      <div className="truncate text-sm font-semibold text-[var(--rb-text)]">{d.label}</div>
                      <div className="mt-1 text-xs text-zinc-500">Serial: {d.serial}</div>
                    </div>
                    <Badge variant="default">{d.id}</Badge>
                  </div>
                  {d.notes ? <div className="mt-2 text-sm text-zinc-700">{d.notes}</div> : null}
                  {d.extraFields.length > 0 ? (
                    <div className="mt-3">
                      <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Extra details</div>
                      <div className="mt-2 grid gap-2 sm:grid-cols-2">
                        {d.extraFields.map((f) => (
                          <div key={f.key} className="min-w-0">
                            <div className="truncate text-xs text-zinc-500">{f.label}</div>
                            <div className="truncate text-sm text-zinc-700">{f.value_text}</div>
                          </div>
                        ))}
                      </div>
                    </div>
                  ) : null}
                </CardContent>
              </Card>
            ))}
          </div>
        ) : null}
      </div>
    </PortalShell>
  );
}
