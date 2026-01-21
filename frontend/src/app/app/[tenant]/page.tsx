"use client";

import { apiFetch } from "@/lib/api";
import type { Tenant, User } from "@/lib/types";
import { Card, CardContent } from "@/components/ui/Card";
import { PageHeader } from "@/components/ui/PageHeader";
import { useParams } from "next/navigation";
import React, { useEffect, useState } from "react";

type DashboardPayload = {
  tenant: Tenant;
  user: User;
  metrics: {
    notes_count: number;
  };
};

export default function TenantDashboardPage() {
  const params = useParams() as { tenant?: string; business?: string };
  const tenant = params.business ?? params.tenant;

  const [loading, setLoading] = useState(true);
  const [data, setData] = useState<DashboardPayload | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        const res = await apiFetch<DashboardPayload>(`/api/${tenant}/app/dashboard`);
        if (!alive) return;
        setData(res);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load dashboard.");
      } finally {
        if (!alive) return;
        setLoading(false);
      }
    }

    if (typeof tenant === "string" && tenant.length > 0) {
      void load();
    } else {
      setLoading(false);
      setError("Tenant is missing.");
    }

    return () => {
      alive = false;
    };
  }, [tenant]);

  return (
    <div className="space-y-6">
      <PageHeader title="Dashboard" description="Milestone 1 placeholder widgets." />

      {loading ? <div className="text-sm text-zinc-500">Loading dashboard...</div> : null}
      {error ? <div className="text-sm text-red-600">{error}</div> : null}

      {data ? (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <Card className="shadow-none">
            <CardContent className="pt-5">
              <div className="text-xs font-semibold text-zinc-500 uppercase tracking-wider">
                Active Notes
              </div>
              <div className="mt-2 text-2xl font-bold text-[var(--rb-text)]">
                {data.metrics.notes_count}
              </div>
            </CardContent>
          </Card>
          <Card className="shadow-none">
            <CardContent className="pt-5">
              <div className="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Tenant</div>
              <div className="mt-2 text-sm font-medium text-[var(--rb-text)]">{data.tenant.name}</div>
              <div className="mt-1 text-xs text-zinc-500">Slug: {data.tenant.slug}</div>
            </CardContent>
          </Card>
          <Card className="shadow-none">
            <CardContent className="pt-5">
              <div className="text-xs font-semibold text-zinc-500 uppercase tracking-wider">
                Signed in as
              </div>
              <div className="mt-2 text-sm font-medium text-[var(--rb-text)]">{data.user.name}</div>
              <div className="mt-1 text-xs text-zinc-500">{data.user.email}</div>
            </CardContent>
          </Card>
        </div>
      ) : null}
    </div>
  );
}
