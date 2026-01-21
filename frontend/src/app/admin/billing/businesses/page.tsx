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
import { apiFetch } from "@/lib/api";
import type { Tenant } from "@/lib/types";

export default function AdminBillingTenantsHubPage() {
  const dashboardHeader = useDashboardHeader();

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [tenants, setTenants] = useState<Tenant[]>([]);

  const [query, setQuery] = useState<string>("");
  const [status, setStatus] = useState<string>("all");

  const [pageIndex, setPageIndex] = useState<number>(0);
  const [pageSize, setPageSize] = useState<number>(10);
  const [totalRows, setTotalRows] = useState<number>(0);

  const [sort, setSort] = useState<{ id: string; dir: "asc" | "desc" } | null>({ id: "id", dir: "desc" });

  const [reloadNonce, setReloadNonce] = useState(0);

  useEffect(() => {
    dashboardHeader.setHeader({
      breadcrumb: "Admin / Billing",
      title: "Business billing",
      subtitle: "Manage subscriptions and invoices per business",
      actions: (
        <div className="flex items-center gap-2">
          <Link href="/admin/billing/plans">
            <Button variant="outline" size="sm">
              Catalog
            </Button>
          </Link>
          <Button variant="outline" size="sm" onClick={() => setReloadNonce((v) => v + 1)} disabled={loading}>
            Refresh
          </Button>
        </div>
      ),
    });

    return () => dashboardHeader.setHeader(null);
  }, [dashboardHeader, loading]);

  useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);

        const qs = new URLSearchParams();
        if (query.trim().length > 0) qs.set("q", query.trim());
        if (status && status !== "all") qs.set("status", status);
        if (sort?.id && sort?.dir) {
          qs.set("sort", sort.id);
          qs.set("dir", sort.dir);
        }
        qs.set("page", String(pageIndex + 1));
        qs.set("per_page", String(pageSize));

        const res = await apiFetch<{
          tenants: Tenant[];
          meta?: { current_page: number; per_page: number; total: number; last_page: number };
        }>(`/api/admin/businesses?${qs.toString()}`);

        if (!alive) return;

        setTenants(Array.isArray(res.tenants) ? res.tenants : []);
        setTotalRows(typeof res.meta?.total === "number" ? res.meta.total : 0);
        setPageSize(typeof res.meta?.per_page === "number" ? res.meta.per_page : pageSize);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load businesses.");
        setTenants([]);
        setTotalRows(0);
      } finally {
        if (!alive) return;
        setLoading(false);
      }
    }

    void load();

    return () => {
      alive = false;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [query, status, pageIndex, pageSize, sort?.id, sort?.dir, reloadNonce]);

  const rows = useMemo(() => tenants, [tenants]);

  const statusVariant = (s: Tenant["status"]) => {
    if (s === "active") return "success" as const;
    if (s === "trial") return "info" as const;
    if (s === "past_due") return "warning" as const;
    if (s === "suspended") return "danger" as const;
    return "default" as const;
  };

  return (
    <RequireAuth requiredPermission="admin.billing.read">
      <RequireAuth requiredPermission="admin.tenants.read">
        <div className="space-y-6">
          {error ? (
            <Alert variant="danger" title="Could not load businesses">
              {error}
            </Alert>
          ) : null}

          <Card>
            <CardContent className="pt-5">
              <DataTable
                title="Businesses"
                data={rows}
                loading={loading}
                emptyMessage="No businesses found."
                getRowId={(t) => t.id}
                server={{
                  query,
                  onQueryChange: (v) => {
                    setQuery(v);
                    setPageIndex(0);
                  },
                  pageIndex,
                  onPageIndexChange: setPageIndex,
                  pageSize,
                  onPageSizeChange: setPageSize,
                  totalRows,
                  sort,
                  onSortChange: setSort,
                }}
                filters={[
                  {
                    id: "status",
                    label: "Status",
                    value: status,
                    options: [
                      { label: "All statuses", value: "all" },
                      { label: "Trial", value: "trial" },
                      { label: "Active", value: "active" },
                      { label: "Past due", value: "past_due" },
                      { label: "Suspended", value: "suspended" },
                      { label: "Closed", value: "closed" },
                    ],
                    onChange: (v) => {
                      setStatus(String(v));
                      setPageIndex(0);
                    },
                  },
                ]}
                columns={[
                  {
                    id: "tenant",
                    header: "Business",
                    sortId: "name",
                    cell: (t) => (
                      <div className="min-w-0">
                        <div className="truncate text-sm font-medium text-zinc-800">{t.name}</div>
                        <div className="truncate text-xs text-zinc-500">{t.slug}</div>
                      </div>
                    ),
                    className: "min-w-[240px]",
                  },
                  {
                    id: "status",
                    header: "Status",
                    sortId: "status",
                    cell: (t) => <Badge variant={statusVariant(t.status)}>{t.status}</Badge>,
                    className: "whitespace-nowrap",
                  },
                  {
                    id: "country",
                    header: "Billing country",
                    sortId: "id",
                    cell: (t) => <div className="text-sm text-zinc-700">{t.billing_country ?? "—"}</div>,
                    className: "whitespace-nowrap",
                  },
                  {
                    id: "currency",
                    header: "Currency",
                    sortId: "id",
                    cell: (t) => <div className="text-sm text-zinc-700">{t.currency ?? "—"}</div>,
                    className: "whitespace-nowrap",
                  },
                  {
                    id: "actions",
                    header: "",
                    cell: (t) => (
                      <div className="flex items-center justify-end gap-2">
                        <Link href={`/admin/businesses/${t.id}/billing`}>
                          <Button variant="outline" size="sm">
                            Billing
                          </Button>
                        </Link>
                        <Link href={`/admin/businesses/${t.id}/billing/invoices`}>
                          <Button variant="outline" size="sm">
                            Invoices
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
        </div>
      </RequireAuth>
    </RequireAuth>
  );
}
