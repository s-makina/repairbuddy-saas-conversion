"use client";

import React from "react";
import { useParams, useRouter } from "next/navigation";
import { useAuth } from "@/lib/auth";
import { Button } from "@/components/ui/Button";
import { Badge } from "@/components/ui/Badge";
import { Card, CardContent } from "@/components/ui/Card";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { ListPageShell } from "@/components/shells/ListPageShell";
import { apiFetch, ApiError } from "@/lib/api";
import { formatMoney } from "@/lib/money";

type ApiEstimate = {
  id: number;
  case_number: string;
  title: string;
  status: string;
  customer: null | {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    company: string | null;
  };
  totals: {
    currency: string;
    subtotal_cents: number;
    tax_cents: number;
    total_cents: number;
  };
  updated_at: string;
};

function estimateBadgeVariant(status: string): "default" | "success" | "warning" | "danger" {
  if (status === "approved") return "success";
  if (status === "rejected") return "danger";
  return "warning";
}

export default function TenantEstimatesPage() {
  const auth = useAuth();
  const router = useRouter();
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [estimates, setEstimates] = React.useState<ApiEstimate[]>([]);
  const [q, setQ] = React.useState<string>("");

  const [pageIndex, setPageIndex] = React.useState(0);
  const [pageSize, setPageSize] = React.useState(10);

  React.useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);
        if (typeof tenantSlug !== "string" || tenantSlug.trim().length === 0) {
          setEstimates([]);
          return;
        }

        const res = await apiFetch<{ estimates: ApiEstimate[] }>(
          `/api/${tenantSlug}/app/repairbuddy/estimates?limit=200&q=${encodeURIComponent(q.trim())}`,
          {
            method: "GET",
          },
        );

        if (!alive) return;
        setEstimates(Array.isArray(res?.estimates) ? res.estimates : []);
      } catch (e) {
        if (!alive) return;
        if (e instanceof ApiError) {
          setError(e.message);
        } else {
          setError(e instanceof Error ? e.message : "Failed to load estimates.");
        }
      } finally {
        if (!alive) return;
        setLoading(false);
      }
    }

    void load();

    return () => {
      alive = false;
    };
  }, [q, tenantSlug]);

  const rows = React.useMemo(() => {
    const needle = q.trim().toLowerCase();
    const mapped = estimates.map((e) => {
      const totalCents = e?.totals?.total_cents ?? 0;
      const currency = e?.totals?.currency ?? "USD";
      return {
        estimate: e,
        totalCents,
        currency,
      };
    });

    if (!needle) return mapped;

    return mapped.filter((r) => {
      const hay = `${r.estimate.case_number} ${r.estimate.title ?? ""} ${r.estimate.customer?.name ?? ""} ${r.estimate.customer?.email ?? ""} ${r.estimate.status}`.toLowerCase();
      return hay.includes(needle);
    });
  }, [estimates, q]);

  const totalRows = rows.length;
  const pageRows = React.useMemo(() => {
    const start = pageIndex * pageSize;
    const end = start + pageSize;
    return rows.slice(start, end);
  }, [pageIndex, pageSize, rows]);

  const columns = React.useMemo<
    Array<
      DataTableColumn<{
        estimate: ApiEstimate;
        totalCents: number;
        currency: string;
      }>
    >
  >(
    () => [
      {
        id: "id",
        header: "Estimate",
        cell: (row) => (
          <div className="min-w-0">
            <div className="truncate font-semibold text-[var(--rb-text)]">{row.estimate.case_number}</div>
            <div className="truncate text-xs text-zinc-600">{row.estimate.title}</div>
          </div>
        ),
        className: "min-w-[220px]",
      },
      {
        id: "client",
        header: "Client",
        cell: (row) => (
          <div className="min-w-0">
            <div className="truncate text-sm text-zinc-700">{row.estimate.customer?.name ?? "—"}</div>
            <div className="truncate text-xs text-zinc-500">{row.estimate.customer?.email ?? ""}</div>
          </div>
        ),
        className: "min-w-[220px]",
      },
      {
        id: "status",
        header: "Status",
        cell: (row) => <Badge variant={estimateBadgeVariant(row.estimate.status)}>{row.estimate.status}</Badge>,
        className: "whitespace-nowrap",
      },
      {
        id: "total",
        header: "Total",
        cell: (row) => <div className="text-sm font-semibold text-[var(--rb-text)]">{formatMoney({ amountCents: row.totalCents, currency: row.currency })}</div>,
        className: "whitespace-nowrap",
      },
      {
        id: "updated",
        header: "Updated",
        cell: (row) => <div className="text-sm text-zinc-600">{new Date(row.estimate.updated_at).toLocaleString()}</div>,
        className: "whitespace-nowrap",
      },
    ],
    [],
  );

  return (
    <ListPageShell
      title="Estimates"
      description="Create, send, and track customer approvals."
      actions={
        <Button
          variant="outline"
          size="sm"
          onClick={() => {
            if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
            if (!auth.can("estimates.manage")) return;
            router.push(`/app/${tenantSlug}/estimates/new`);
          }}
          disabled={typeof tenantSlug !== "string" || tenantSlug.length === 0 || !auth.can("estimates.manage")}
        >
          New estimate
        </Button>
      }
      filters={
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div className="text-sm font-semibold text-[var(--rb-text)]">Search</div>
          <input
            value={q}
            onChange={(e) => setQ(e.target.value)}
            placeholder="Search estimate, case, client, status..."
            className="h-10 w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 text-sm sm:max-w-[420px]"
          />
        </div>
      }
      loading={loading}
      error={error}
      empty={!loading && !error && rows.length === 0}
      emptyTitle="No estimates found"
      emptyDescription="Try adjusting your search."
    >
      <Card className="shadow-none">
        <CardContent className="pt-5">
          <DataTable
            title={typeof tenantSlug === "string" ? `Estimates · ${tenantSlug}` : "Estimates"}
            data={pageRows}
            loading={loading}
            emptyMessage="No estimates."
            columns={columns}
            getRowId={(row) => row.estimate.id}
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
              totalRows,
            }}
            onRowClick={(row) => {
              if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
              router.push(`/app/${tenantSlug}/estimates/${row.estimate.id}`);
            }}
          />
        </CardContent>
      </Card>
    </ListPageShell>
  );
}
