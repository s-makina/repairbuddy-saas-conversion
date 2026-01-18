"use client";

import { apiFetch } from "@/lib/api";
import type { Tenant } from "@/lib/types";
import { Badge } from "@/components/ui/Badge";
import { Card, CardContent } from "@/components/ui/Card";
import { DataTable } from "@/components/ui/DataTable";
import { PageHeader } from "@/components/ui/PageHeader";
import React, { useEffect, useMemo, useState } from "react";

export default function AdminDashboardPage() {
  const [loading, setLoading] = useState(true);
  const [tenants, setTenants] = useState<Tenant[]>([]);
  const [error, setError] = useState<string | null>(null);

  const [q, setQ] = useState<string>("");
  const [statusFilter, setStatusFilter] = useState<string>("all");
  const [pageIndex, setPageIndex] = useState<number>(0);
  const [pageSize, setPageSize] = useState<number>(10);
  const [totalTenants, setTotalTenants] = useState<number>(0);
  const [sort, setSort] = useState<{ id: string; dir: "asc" | "desc" } | null>(null);

  const statusOptions = useMemo(() => {
    return [
      { label: "All statuses", value: "all" },
      { label: "Active", value: "active" },
      { label: "Inactive", value: "inactive" },
    ];
  }, []);

  useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setError(null);
        setLoading(true);

        const qs = new URLSearchParams();
        if (q.trim().length > 0) qs.set("q", q.trim());
        if (statusFilter && statusFilter !== "all") qs.set("status", statusFilter);
        if (sort?.id && sort?.dir) {
          qs.set("sort", sort.id);
          qs.set("dir", sort.dir);
        }
        qs.set("page", String(pageIndex + 1));
        qs.set("per_page", String(pageSize));

        const res = await apiFetch<{
          tenants: Tenant[];
          meta?: { current_page: number; per_page: number; total: number; last_page: number };
        }>(`/api/admin/tenants?${qs.toString()}`);

        if (!alive) return;
        setTenants(Array.isArray(res.tenants) ? res.tenants : []);
        setTotalTenants(typeof res.meta?.total === "number" ? res.meta.total : 0);
        setPageSize(typeof res.meta?.per_page === "number" ? res.meta.per_page : pageSize);
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
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [q, statusFilter, pageIndex, pageSize, sort?.id, sort?.dir]);

  return (
    <div className="space-y-6">
      <PageHeader title="Tenants" description="Manage tenants (admin)." />

      {loading ? <div className="text-sm text-zinc-500">Loading tenants...</div> : null}
      {error ? <div className="text-sm text-red-600">{error}</div> : null}

      <Card className="shadow-none">
        <CardContent className="pt-5">
          <DataTable
            title="Tenants"
            data={tenants}
            loading={loading}
            emptyMessage="No tenants found."
            getRowId={(t) => t.id}
            search={{
              placeholder: "Search name, slug, or email...",
            }}
            server={{
              query: q,
              onQueryChange: (value) => {
                setQ(value);
                setPageIndex(0);
              },
              pageIndex,
              onPageIndexChange: setPageIndex,
              pageSize,
              onPageSizeChange: (value) => {
                setPageSize(value);
                setPageIndex(0);
              },
              totalRows: totalTenants,
              sort,
              onSortChange: (next) => {
                setSort(next);
                setPageIndex(0);
              },
            }}
            exportConfig={{
              url: "/api/admin/tenants/export",
              formats: ["csv", "xlsx", "pdf"],
              filename: ({ format }) => `tenants_export.${format}`,
            }}
            columnVisibilityKey="rb:datatable:admin:tenants"
            filters={[
              {
                id: "status",
                label: "Status",
                value: statusFilter,
                options: statusOptions,
                onChange: (value) => {
                  setStatusFilter(String(value));
                  setPageIndex(0);
                },
              },
            ]}
            columns={[
              {
                id: "id",
                header: "ID",
                sortId: "id",
                cell: (t) => <div className="text-sm text-zinc-700">{t.id}</div>,
                className: "whitespace-nowrap",
              },
              {
                id: "name",
                header: "Name",
                sortId: "name",
                cell: (t) => (
                  <div className="min-w-0">
                    <div className="truncate font-semibold text-[var(--rb-text)]">{t.name}</div>
                    {t.contact_email ? <div className="truncate text-xs text-zinc-600">{t.contact_email}</div> : null}
                  </div>
                ),
                className: "max-w-[420px]",
              },
              {
                id: "slug",
                header: "Slug",
                sortId: "slug",
                cell: (t) => <div className="text-sm text-zinc-700">{t.slug}</div>,
                className: "whitespace-nowrap",
              },
              {
                id: "status",
                header: "Status",
                sortId: "status",
                cell: (t) => <Badge>{t.status}</Badge>,
                className: "whitespace-nowrap",
              },
              {
                id: "contact_email",
                header: "Contact Email",
                sortId: "contact_email",
                hiddenByDefault: true,
                cell: (t) => <div className="text-sm text-zinc-700">{t.contact_email ?? ""}</div>,
                className: "whitespace-nowrap",
              },
              {
                id: "created_at",
                header: "Created",
                sortId: "created_at",
                hiddenByDefault: true,
                cell: (t) => <div className="text-sm text-zinc-700">{t.created_at ?? ""}</div>,
                className: "whitespace-nowrap",
              },
            ]}
          />
        </CardContent>
      </Card>
    </div>
  );
}
