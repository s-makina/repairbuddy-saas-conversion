"use client";

import { apiFetch } from "@/lib/api";
import type { Tenant, User } from "@/lib/types";
import { useParams } from "next/navigation";
import React, { useEffect, useState } from "react";

type DashboardPayload = {
  tenant: Tenant;
  user: User;
};

export default function TenantDashboardPage() {
  const params = useParams<{ tenant: string }>();
  const tenant = params.tenant;

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
      <div>
        <h1 className="text-xl font-semibold">Dashboard</h1>
        <p className="mt-1 text-sm text-zinc-500">Milestone 1 placeholder widgets.</p>
      </div>

      {loading ? <div className="text-sm text-zinc-500">Loading dashboard...</div> : null}
      {error ? <div className="text-sm text-red-600">{error}</div> : null}

      {data ? (
        <div className="grid gap-4 md:grid-cols-2">
          <div className="rounded-lg border bg-white p-4">
            <div className="text-xs font-semibold text-zinc-500">Tenant</div>
            <div className="mt-1 text-sm">{data.tenant.name}</div>
            <div className="mt-1 text-xs text-zinc-500">Slug: {data.tenant.slug}</div>
          </div>
          <div className="rounded-lg border bg-white p-4">
            <div className="text-xs font-semibold text-zinc-500">Signed in as</div>
            <div className="mt-1 text-sm">{data.user.name}</div>
            <div className="mt-1 text-xs text-zinc-500">{data.user.email}</div>
          </div>
        </div>
      ) : null}
    </div>
  );
}
