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
import type { Client, ClientId, CustomerDevice, Device, DeviceBrand, DeviceType, Job } from "@/mock/types";

export default function TenantClientDetailPage() {
  const router = useRouter();
  const params = useParams() as { tenant?: string; business?: string; clientId?: string };
  const tenantSlug = params.business ?? params.tenant;
  const clientId = params.clientId;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);

  const [client, setClient] = React.useState<Client | null>(null);
  const [jobs, setJobs] = React.useState<Job[]>([]);
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

        if (!clientId) {
          setError("Client ID is missing.");
          setClient(null);
          return;
        }

        const [c, j, cd, d, b, t] = await Promise.all([
          mockApi.getClient(clientId as ClientId),
          mockApi.listJobs(),
          mockApi.listCustomerDevices(),
          mockApi.listDevices(),
          mockApi.listDeviceBrands(),
          mockApi.listDeviceTypes(),
        ]);

        if (!alive) return;

        if (!c) {
          setError("Client not found.");
          setClient(null);
          return;
        }

        setClient(c);
        setJobs(Array.isArray(j) ? j : []);
        setCustomerDevices(Array.isArray(cd) ? cd : []);
        setDevices(Array.isArray(d) ? d : []);
        setBrands(Array.isArray(b) ? b : []);
        setTypes(Array.isArray(t) ? t : []);
      } catch (e) {
        if (!alive) return;
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
  }, [clientId]);

  const clientJobs = React.useMemo(() => {
    if (!client) return [];
    return jobs.filter((j) => j.client_id === client.id).slice().sort((a, b) => (a.updated_at < b.updated_at ? 1 : -1));
  }, [client, jobs]);

  const deviceById = React.useMemo(() => new Map(devices.map((d) => [d.id, d])), [devices]);
  const brandById = React.useMemo(() => new Map(brands.map((b) => [b.id, b])), [brands]);
  const typeById = React.useMemo(() => new Map(types.map((t) => [t.id, t])), [types]);

  const myDevices = React.useMemo(() => {
    if (!client) return [];
    return customerDevices
      .filter((cd) => cd.client_id === client.id)
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
  }, [brandById, client, customerDevices, deviceById, typeById]);

  return (
    <div className="space-y-6">
      {error ? (
        <Alert variant="danger" title="Could not load client">
          {error}
        </Alert>
      ) : null}

      {loading ? <div className="text-sm text-zinc-500">Loading client...</div> : null}

      {client ? (
        <DetailPageShell
          breadcrumb={
            <span>
              <Link href={typeof tenantSlug === "string" ? `/app/${tenantSlug}/clients` : "/app"} className="hover:text-[var(--rb-text)]">
                Clients
              </Link>
              <span className="px-2">/</span>
              <span>{client.name}</span>
            </span>
          }
          backHref={typeof tenantSlug === "string" ? `/app/${tenantSlug}/clients` : "/app"}
          title={client.name}
          description={client.email ?? client.phone ?? client.id}
          actions={
            <div className="flex items-center gap-2">
              <Badge variant="info">{client.id}</Badge>
              <Button disabled variant="outline" size="sm">
                Edit
              </Button>
            </div>
          }
          tabs={{
            overview: (
              <Card className="shadow-none">
                <CardContent className="pt-5">
                  <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                      <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Email</div>
                      <div className="mt-1 text-sm text-zinc-700">{client.email ?? "—"}</div>
                    </div>
                    <div>
                      <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Phone</div>
                      <div className="mt-1 text-sm text-zinc-700">{client.phone ?? "—"}</div>
                    </div>
                    <div className="sm:col-span-2">
                      <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Created</div>
                      <div className="mt-1 text-sm text-zinc-700">{new Date(client.created_at).toLocaleString()}</div>
                    </div>
                  </div>
                </CardContent>
              </Card>
            ),
            timeline: (
              <Card className="shadow-none">
                <CardContent className="pt-5">
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Jobs</div>
                  <div className="mt-4 space-y-3">
                    {clientJobs.length === 0 ? <div className="text-sm text-zinc-600">No jobs for this client.</div> : null}
                    {clientJobs.map((j) => (
                      <div key={j.id} className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-3">
                        <div className="flex items-start justify-between gap-3">
                          <div className="min-w-0">
                            <div className="truncate text-sm font-semibold text-[var(--rb-text)]">{j.case_number}</div>
                            <div className="mt-1 text-sm text-zinc-600">{j.title}</div>
                            <div className="mt-2 text-xs text-zinc-500">Updated: {new Date(j.updated_at).toLocaleString()}</div>
                          </div>
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
                <CardContent className="pt-5">
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Devices</div>
                  <div className="mt-4 space-y-3">
                    {myDevices.length === 0 ? <div className="text-sm text-zinc-600">No devices linked.</div> : null}
                    {myDevices.map((d) => (
                      <div key={d.id} className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-3">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                          <div className="min-w-0">
                            <div className="truncate text-sm font-semibold text-[var(--rb-text)]">{d.label}</div>
                            <div className="mt-1 text-xs text-zinc-500">Type: {d.type} · Serial: {d.serial}</div>
                          </div>
                          <Badge variant="default">{d.id}</Badge>
                        </div>
                        {d.notes ? <div className="mt-2 text-sm text-zinc-700">{d.notes}</div> : null}
                      </div>
                    ))}
                  </div>
                </CardContent>
              </Card>
            ),
            financial: (
              <Card className="shadow-none">
                <CardContent className="pt-5">
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Financial</div>
                  <div className="mt-2 text-sm text-zinc-600">Client-level financials are wired in later phases.</div>
                </CardContent>
              </Card>
            ),
            print: (
              <Card className="shadow-none">
                <CardContent className="pt-5">
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Print</div>
                  <div className="mt-2 text-sm text-zinc-600">Printable client summaries are implemented in later phases.</div>
                </CardContent>
              </Card>
            ),
          }}
        />
      ) : null}
    </div>
  );
}
