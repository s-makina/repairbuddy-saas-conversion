"use client";

import React from "react";
import { useParams } from "next/navigation";
import { PortalShell } from "@/components/shells/PortalShell";
import { Alert } from "@/components/ui/Alert";
import { Badge } from "@/components/ui/Badge";
import { Card, CardContent } from "@/components/ui/Card";
import { mockApi } from "@/mock/mockApi";
import type { CustomerDevice, Device, DeviceBrand, DeviceType, Job, JobId } from "@/mock/types";

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

export default function PortalDevicesPage() {
  const params = useParams() as { tenant?: string };
  const tenantSlug = typeof params.tenant === "string" ? params.tenant : "";

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [session, setSession] = React.useState<PortalSession | null>(null);

  const [job, setJob] = React.useState<Job | null>(null);
  const [customerDevices, setCustomerDevices] = React.useState<CustomerDevice[]>([]);
  const [devices, setDevices] = React.useState<Device[]>([]);
  const [brands, setBrands] = React.useState<DeviceBrand[]>([]);
  const [types, setTypes] = React.useState<DeviceType[]>([]);

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

        const [j, cd, d, b, t] = await Promise.all([
          mockApi.getJob(s.jobId as JobId),
          mockApi.listCustomerDevices(),
          mockApi.listDevices(),
          mockApi.listDeviceBrands(),
          mockApi.listDeviceTypes(),
        ]);

        if (!alive) return;

        setJob(j);
        setCustomerDevices(Array.isArray(cd) ? cd : []);
        setDevices(Array.isArray(d) ? d : []);
        setBrands(Array.isArray(b) ? b : []);
        setTypes(Array.isArray(t) ? t : []);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load devices.");
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

  const deviceById = React.useMemo(() => new Map(devices.map((d) => [d.id, d])), [devices]);
  const brandById = React.useMemo(() => new Map(brands.map((b) => [b.id, b])), [brands]);
  const typeById = React.useMemo(() => new Map(types.map((t) => [t.id, t])), [types]);

  const myDevices = React.useMemo(() => {
    if (!job) return [];
    const ids = new Set(job.customer_device_ids);
    return customerDevices
      .filter((cd) => ids.has(cd.id))
      .map((cd) => {
        const dev = deviceById.get(cd.device_id) ?? null;
        const brand = dev ? brandById.get(dev.brand_id) ?? null : null;
        const type = dev ? typeById.get(dev.type_id) ?? null : null;
        return {
          id: cd.id,
          label: dev ? `${brand?.name ?? ""} ${dev.model}`.trim() : cd.device_id,
          type: type?.name ?? "—",
          serial: cd.serial_number ?? "—",
          notes: cd.notes ?? "",
        };
      });
  }, [brandById, customerDevices, deviceById, job, typeById]);

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

        {!loading && !error && session && !job ? (
          <Alert variant="warning" title="Ticket not found">
            We couldn’t load your ticket.
          </Alert>
        ) : null}

        {!loading && !error && session && job && myDevices.length === 0 ? (
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
                      <div className="mt-1 text-xs text-zinc-500">Type: {d.type} · Serial: {d.serial}</div>
                    </div>
                    <Badge variant="default">{d.id}</Badge>
                  </div>
                  {d.notes ? <div className="mt-2 text-sm text-zinc-700">{d.notes}</div> : null}
                </CardContent>
              </Card>
            ))}
          </div>
        ) : null}
      </div>
    </PortalShell>
  );
}
