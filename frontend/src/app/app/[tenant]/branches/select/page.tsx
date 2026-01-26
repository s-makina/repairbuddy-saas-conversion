"use client";

import React, { useEffect, useMemo, useState } from "react";
import { useParams, useRouter, useSearchParams } from "next/navigation";
import { apiFetch, ApiError } from "@/lib/api";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { PageHeader } from "@/components/ui/PageHeader";

type Branch = {
  id: number;
  tenant_id?: number;
  name: string;
  code: string;
  is_active: boolean;
};

export default function SelectBranchPage() {
  const router = useRouter();
  const params = useParams() as { tenant?: string; business?: string };
  const tenant = params.business ?? params.tenant;
  const searchParams = useSearchParams();

  const next = useMemo(() => {
    const n = searchParams.get("next");
    return n && n.startsWith("/") ? n : null;
  }, [searchParams]);

  const [loading, setLoading] = useState(true);
  const [branches, setBranches] = useState<Branch[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [selectingId, setSelectingId] = useState<number | null>(null);

  useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setError(null);
        setLoading(true);
        const res = await apiFetch<{ branches: Branch[] }>(`/api/${tenant}/app/branches`);
        if (!alive) return;
        setBranches(Array.isArray(res.branches) ? res.branches : []);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load branches.");
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

  async function onSelect(branchId: number) {
    setSelectingId(branchId);
    setError(null);

    try {
      await apiFetch(`/api/${tenant}/app/branches/active`, {
        method: "POST",
        body: { branch_id: branchId },
      });

      router.replace(next ?? `/app/${tenant}`);
    } catch (e) {
      if (e instanceof ApiError) {
        setError(e.message);
      } else {
        setError("Failed to select branch.");
      }
    } finally {
      setSelectingId(null);
    }
  }

  const activeBranches = useMemo(() => branches.filter((b) => b.is_active), [branches]);

  return (
    <div className="space-y-6">
      <PageHeader title="Choose branch" description="Select the shop you want to work in." />

      {loading ? <div className="text-sm text-zinc-500">Loading branches...</div> : null}
      {error ? <div className="text-sm text-red-600">{error}</div> : null}

      {!loading && !error && activeBranches.length === 0 ? (
        <Card className="shadow-none">
          <CardContent className="pt-5">
            <div className="text-sm font-semibold text-[var(--rb-text)]">No branches available</div>
            <div className="mt-2 text-sm text-zinc-600">Please contact your administrator.</div>
          </CardContent>
        </Card>
      ) : null}

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {activeBranches.map((b) => {
          const busy = selectingId === b.id;
          return (
            <Card key={b.id} className="shadow-none">
              <CardContent className="pt-5">
                <div className="text-xs font-semibold text-zinc-500 uppercase tracking-wider">{b.code}</div>
                <div className="mt-2 text-sm font-semibold text-[var(--rb-text)]">{b.name}</div>
                <div className="mt-4">
                  <Button
                    variant="primary"
                    disabled={busy}
                    onClick={() => {
                      void onSelect(b.id);
                    }}
                  >
                    {busy ? "Selecting..." : "Select"}
                  </Button>
                </div>
              </CardContent>
            </Card>
          );
        })}
      </div>
    </div>
  );
}
