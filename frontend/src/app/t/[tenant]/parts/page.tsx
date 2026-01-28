"use client";

import React from "react";
import Link from "next/link";
import { useParams } from "next/navigation";
import { PublicPageShell } from "@/components/PublicPageShell";
import { Card, CardContent } from "@/components/ui/Card";
import { Alert } from "@/components/ui/Alert";
import { Badge } from "@/components/ui/Badge";
import { mockApi } from "@/mock/mockApi";
import type { Part } from "@/mock/types";
import { formatMoney } from "@/lib/money";

export default function PublicPartsPage() {
  const params = useParams() as { tenant?: string };
  const tenantSlug = typeof params.tenant === "string" ? params.tenant : "";

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [parts, setParts] = React.useState<Part[]>([]);

  React.useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);
        const res = await mockApi.listParts();
        if (!alive) return;
        setParts(Array.isArray(res) ? res : []);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load parts.");
        setParts([]);
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
            <Link href={`/t/${tenantSlug}/services`} className="text-sm font-semibold text-[var(--rb-blue)] hover:underline">
              Services
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
                <div className="text-xl font-semibold text-[var(--rb-text)]">Parts</div>
                <div className="mt-1 text-sm text-zinc-600">Common replacement parts and accessories.</div>
              </div>

              {!tenantSlug ? (
                <Alert variant="warning" title="Missing tenant">
                  Tenant slug is required in the URL.
                </Alert>
              ) : null}

              {error ? (
                <Alert variant="danger" title="Could not load parts">
                  {error}
                </Alert>
              ) : null}

              {loading ? <div className="text-sm text-zinc-500">Loading parts...</div> : null}

              <div className="grid gap-4 sm:grid-cols-2">
                {parts.map((p) => (
                  <div key={p.id} className="rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)] bg-white p-4">
                    <div className="flex items-start justify-between gap-3">
                      <div className="min-w-0">
                        <div className="truncate text-sm font-semibold text-[var(--rb-text)]">{p.name}</div>
                        {p.sku ? <div className="mt-1 text-xs text-zinc-500">SKU: {p.sku}</div> : null}
                      </div>
                      <Badge variant="info">{p.id}</Badge>
                    </div>

                    <div className="mt-4 flex items-center justify-between gap-3">
                      <div className="text-sm text-zinc-600">Price</div>
                      <div className="text-sm font-semibold text-[var(--rb-text)]">
                        {formatMoney({ amountCents: p.price?.amount_cents, currency: p.price?.currency })}
                      </div>
                    </div>

                    <div className="mt-2 flex items-center justify-between gap-3">
                      <div className="text-sm text-zinc-600">Stock</div>
                      <div className="text-sm text-zinc-700">{typeof p.stock === "number" ? p.stock : "—"}</div>
                    </div>
                  </div>
                ))}
              </div>

              {!loading && !error && parts.length === 0 ? <div className="text-sm text-zinc-600">No parts available.</div> : null}
            </div>
          </CardContent>
        </Card>
      </div>
    </PublicPageShell>
  );
}
