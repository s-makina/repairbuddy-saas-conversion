"use client";

import React, { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { RequireAuth } from "@/components/RequireAuth";
import { useDashboardHeader } from "@/components/DashboardShell";
import { Alert } from "@/components/ui/Alert";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { DataTable } from "@/components/ui/DataTable";
import { Modal } from "@/components/ui/Modal";
import { Input } from "@/components/ui/Input";
import { createBillingPlan, getBillingCatalog } from "@/lib/billing";
import { useAuth } from "@/lib/auth";
import type { BillingPlan } from "@/lib/types";

export default function AdminBillingPlansPage() {
  const auth = useAuth();
  const dashboardHeader = useDashboardHeader();

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [plans, setPlans] = useState<BillingPlan[]>([]);
  const [reloadNonce, setReloadNonce] = useState(0);

  const [createOpen, setCreateOpen] = useState(false);
  const [createBusy, setCreateBusy] = useState(false);
  const [createError, setCreateError] = useState<string | null>(null);
  const [name, setName] = useState("");
  const [code, setCode] = useState("");
  const [description, setDescription] = useState("");
  const [isActive, setIsActive] = useState(true);

  const canWrite = auth.can("admin.billing.write");

  function resetCreateForm() {
    setCreateError(null);
    setName("");
    setCode("");
    setDescription("");
    setIsActive(true);
  }

  useEffect(() => {
    dashboardHeader.setHeader({
      breadcrumb: "Admin / Billing",
      title: "Billing plans",
      subtitle: "View billing catalog (plans, versions, prices)",
      actions: (
        <div className="flex items-center gap-2">
          <Link href="/admin/billing/builder">
            <Button variant="outline" size="sm" disabled={loading}>
              Builder (mock)
            </Button>
          </Link>
          {canWrite ? (
            <Button
              variant="secondary"
              size="sm"
              onClick={() => {
                resetCreateForm();
                setCreateOpen(true);
              }}
              disabled={loading}
            >
              New plan
            </Button>
          ) : null}
          <Button
            variant="outline"
            size="sm"
            onClick={() => setReloadNonce((v) => v + 1)}
            disabled={loading}
          >
            Refresh
          </Button>
        </div>
      ),
    });

    return () => dashboardHeader.setHeader(null);
  }, [canWrite, dashboardHeader, loading]);

  async function onCreate() {
    const nextName = name.trim();
    if (!nextName) {
      setCreateError("Name is required.");
      return;
    }

    setCreateBusy(true);
    setCreateError(null);

    try {
      await createBillingPlan({
        name: nextName,
        code,
        description,
        isActive,
      });

      setCreateOpen(false);
      setReloadNonce((v) => v + 1);
    } catch (e) {
      setCreateError(e instanceof Error ? e.message : "Failed to create billing plan.");
    } finally {
      setCreateBusy(false);
    }
  }

  useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);

        const res = await getBillingCatalog({ includeInactive: true });
        if (!alive) return;

        setPlans(Array.isArray(res.billing_plans) ? res.billing_plans : []);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load billing catalog.");
        setPlans([]);
      } finally {
        if (!alive) return;
        setLoading(false);
      }
    }

    void load();

    return () => {
      alive = false;
    };
  }, [reloadNonce]);

  const rows = useMemo(() => plans, [plans]);

  const activeVersionLabel = (p: BillingPlan) => {
    const versions = Array.isArray(p.versions) ? p.versions : [];
    const active = versions.find((v) => v.status === "active");
    const latest = versions.slice().sort((a, b) => (b.version ?? 0) - (a.version ?? 0))[0];
    const v = active ?? latest;
    return v ? `v${v.version} (${v.status})` : "—";
  };

  const currenciesLabel = (p: BillingPlan) => {
    const versions = Array.isArray(p.versions) ? p.versions : [];
    const allPrices = versions.flatMap((v) => (Array.isArray(v.prices) ? v.prices : []));
    const currencies = Array.from(new Set(allPrices.map((x) => x.currency).filter(Boolean)))
      .map((c) => String(c).toUpperCase())
      .sort();
    return currencies.length > 0 ? currencies.join(", ") : "—";
  };

  return (
    <RequireAuth requiredPermission="admin.billing.read">
      <div className="space-y-6">
        {error ? (
          <Alert variant="danger" title="Could not load billing plans">
            {error}
          </Alert>
        ) : null}

        <Card>
          <CardContent className="pt-5">
            <DataTable
              title="Plans"
              data={rows}
              loading={loading}
              emptyMessage="No billing plans found."
              getRowId={(p) => p.id}
              search={{
                placeholder: "Search by name or code…",
                getSearchText: (p) => `${p.name} ${p.code}`,
              }}
              columns={[
                {
                  id: "name",
                  header: "Plan",
                  cell: (p) => (
                    <div className="min-w-0">
                      <div className="truncate text-sm font-medium text-zinc-800">{p.name}</div>
                      <div className="truncate text-xs text-zinc-500">{p.code}</div>
                    </div>
                  ),
                  className: "min-w-[220px]",
                },
                {
                  id: "status",
                  header: "Status",
                  cell: (p) => (
                    <Badge variant={p.is_active ? "success" : "default"}>{p.is_active ? "active" : "inactive"}</Badge>
                  ),
                  className: "whitespace-nowrap",
                },
                {
                  id: "current_version",
                  header: "Current version",
                  cell: (p) => <div className="text-sm text-zinc-700">{activeVersionLabel(p)}</div>,
                  className: "whitespace-nowrap",
                },
                {
                  id: "currencies",
                  header: "Currencies",
                  cell: (p) => <div className="text-sm text-zinc-700">{currenciesLabel(p)}</div>,
                },
                {
                  id: "actions",
                  header: "",
                  cell: (p) => (
                    <div className="flex items-center justify-end">
                      <Link href={`/admin/billing/plans/${p.id}`}>
                        <Button variant="outline" size="sm">
                          View
                        </Button>
                      </Link>
                    </div>
                  ),
                  className: "whitespace-nowrap",
                },
              ]}
            />
          </CardContent>
        </Card>

        <div className="text-sm text-zinc-500">
          Entitlements definitions are available under{" "}
          <Link className="underline" href="/admin/billing/entitlements">
            Billing / Entitlements
          </Link>
          .
        </div>

        <Modal
          open={createOpen}
          onClose={() => {
            if (!createBusy) setCreateOpen(false);
          }}
          title="Create billing plan"
          footer={
            <div className="flex items-center justify-end gap-2">
              <Button
                variant="outline"
                onClick={() => {
                  if (!createBusy) setCreateOpen(false);
                }}
                disabled={createBusy}
              >
                Cancel
              </Button>
              <Button variant="primary" onClick={() => void onCreate()} disabled={createBusy}>
                {createBusy ? "Creating…" : "Create"}
              </Button>
            </div>
          }
        >
          <div className="space-y-4">
            {createError ? (
              <Alert variant="danger" title="Cannot create plan">
                {createError}
              </Alert>
            ) : null}

            <div className="space-y-1">
              <label className="text-sm font-medium">Name</label>
              <Input value={name} onChange={(e) => setName(e.target.value)} placeholder="e.g. Starter" />
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium">Code (optional)</label>
              <Input value={code} onChange={(e) => setCode(e.target.value)} placeholder="e.g. starter" />
              <div className="text-xs text-zinc-500">If empty, it will be generated from the name.</div>
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium">Description (optional)</label>
              <textarea
                className="min-h-[90px] w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={description}
                onChange={(e) => setDescription(e.target.value)}
                placeholder="Short description shown to admins…"
              />
            </div>

            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" checked={isActive} onChange={(e) => setIsActive(e.target.checked)} />
              Active
            </label>

            <div className="text-xs text-zinc-500">A draft version v1 will be created automatically.</div>
          </div>
        </Modal>
      </div>
    </RequireAuth>
  );
}
