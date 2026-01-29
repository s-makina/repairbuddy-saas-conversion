"use client";

import React from "react";
import Link from "next/link";
import { useParams } from "next/navigation";
import { PublicPageShell } from "@/components/PublicPageShell";
import { Card, CardContent } from "@/components/ui/Card";
import { Alert } from "@/components/ui/Alert";
import { Badge } from "@/components/ui/Badge";
import { apiFetch, ApiError } from "@/lib/api";
import { formatMoney } from "@/lib/money";

type ApiService = {
  id: number;
  name: string;
  description: string | null;
  base_price: { currency: string; amount_cents: number } | null;
};

export default function PublicServicesPage() {
  const params = useParams() as { tenant?: string };
  const tenantSlug = typeof params.tenant === "string" ? params.tenant : "";

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [services, setServices] = React.useState<ApiService[]>([]);

  React.useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);
        if (!tenantSlug) {
          setServices([]);
          return;
        }

        const res = await apiFetch<{ services: ApiService[] }>(`/api/t/${tenantSlug}/services`, {
          token: null,
          impersonationSessionId: null,
        });
        if (!alive) return;
        setServices(Array.isArray(res?.services) ? res.services : []);
      } catch (e) {
        if (!alive) return;

        if (e instanceof ApiError) {
          setError(e.message);
        } else {
          setError(e instanceof Error ? e.message : "Failed to load services.");
        }
        setServices([]);
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
    <PublicPageShell
      badge={
        <span className="inline-flex items-center gap-2">
          <span>RepairBuddy</span>
          {tenantSlug ? <Badge variant="info">{tenantSlug}</Badge> : null}
        </span>
      }
      actions={
        tenantSlug ? (
          <div className="flex items-center gap-3">
            <Link href={`/t/${tenantSlug}/status`} className="text-sm font-semibold text-[var(--rb-blue)] hover:underline">
              Status check
            </Link>
            <Link href={`/t/${tenantSlug}/quote`} className="text-sm font-semibold text-[var(--rb-blue)] hover:underline">
              Quote
            </Link>
            <Link href={`/t/${tenantSlug}/book`} className="text-sm font-semibold text-[var(--rb-blue)] hover:underline">
              Book
            </Link>
          </div>
        ) : null
      }
      centerContent
    >
      <div className="mx-auto w-full max-w-4xl px-4">
        <Card className="shadow-none">
          <CardContent className="pt-6">
            <div className="space-y-6">
              <div>
                <div className="text-xl font-semibold text-[var(--rb-text)]">Services</div>
                <div className="mt-1 text-sm text-zinc-600">Common services offered by this shop.</div>
              </div>

              {!tenantSlug ? (
                <Alert variant="warning" title="Missing tenant">
                  Tenant slug is required in the URL.
                </Alert>
              ) : null}

              {error ? (
                <Alert variant="danger" title="Could not load services">
                  {error}
                </Alert>
              ) : null}

              {loading ? <div className="text-sm text-zinc-500">Loading services...</div> : null}

              <div className="grid gap-4 sm:grid-cols-2">
                {services.map((s) => (
                  <div key={s.id} className="rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)] bg-white p-4">
                    <div className="flex items-start justify-between gap-3">
                      <div className="min-w-0">
                        <div className="truncate text-sm font-semibold text-[var(--rb-text)]">{s.name}</div>
                        {s.description ? <div className="mt-1 text-sm text-zinc-600">{s.description}</div> : null}
                      </div>
                      <Badge variant="info">{s.id}</Badge>
                    </div>
                    <div className="mt-4 flex items-center justify-between">
                      <div className="text-sm text-zinc-600">Starting at</div>
                      <div className="text-sm font-semibold text-[var(--rb-text)]">
                        {formatMoney({ amountCents: s.base_price?.amount_cents, currency: s.base_price?.currency })}
                      </div>
                    </div>
                  </div>
                ))}
              </div>

              {!loading && !error && services.length === 0 ? <div className="text-sm text-zinc-600">No services available.</div> : null}
            </div>
          </CardContent>
        </Card>
      </div>
    </PublicPageShell>
  );
}
