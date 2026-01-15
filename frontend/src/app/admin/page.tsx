"use client";

import { apiFetch } from "@/lib/api";
import type { Tenant } from "@/lib/types";
import React, { useEffect, useState } from "react";

export default function AdminDashboardPage() {
  const [loading, setLoading] = useState(true);
  const [tenants, setTenants] = useState<Tenant[]>([]);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        const res = await apiFetch<{ tenants: Tenant[] }>("/api/admin/tenants");
        if (!alive) return;
        setTenants(res.tenants);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load tenants.");
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
    <div className="space-y-6">
      <div>
        <h1 className="text-xl font-semibold">Tenants</h1>
        <p className="mt-1 text-sm text-zinc-500">List of tenants (Milestone 1 placeholder).</p>
      </div>

      {loading ? <div className="text-sm text-zinc-500">Loading tenants...</div> : null}
      {error ? <div className="text-sm text-red-600">{error}</div> : null}

      {!loading && !error ? (
        <div className="rounded-lg border bg-white">
          <div className="grid grid-cols-12 gap-2 border-b px-4 py-2 text-xs font-semibold text-zinc-600">
            <div className="col-span-2">ID</div>
            <div className="col-span-4">Name</div>
            <div className="col-span-3">Slug</div>
            <div className="col-span-3">Status</div>
          </div>
          {tenants.length === 0 ? (
            <div className="px-4 py-4 text-sm text-zinc-500">No tenants found.</div>
          ) : (
            tenants.map((t) => (
              <div key={t.id} className="grid grid-cols-12 gap-2 px-4 py-2 text-sm">
                <div className="col-span-2">{t.id}</div>
                <div className="col-span-4">{t.name}</div>
                <div className="col-span-3">{t.slug}</div>
                <div className="col-span-3">{t.status}</div>
              </div>
            ))
          )}
        </div>
      ) : null}
    </div>
  );
}
