"use client";

import React from "react";
import { useParams, useRouter } from "next/navigation";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { ListPageShell } from "@/components/shells/ListPageShell";
import { apiFetch, ApiError } from "@/lib/api";
import { formatMoney } from "@/lib/money";

type ApiPart = {
  id: number;
  name: string;
  sku: string | null;
  price: { currency: string; amount_cents: number } | null;
  stock: number | null;
};

export default function TenantPartsPage() {
  const router = useRouter();
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [parts, setParts] = React.useState<ApiPart[]>([]);
  const [q, setQ] = React.useState("");

  const [pageIndex, setPageIndex] = React.useState(0);
  const [pageSize, setPageSize] = React.useState(10);

  React.useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);

        if (typeof tenantSlug !== "string" || tenantSlug.length === 0) {
          throw new Error("Business is missing.");
        }

        const res = await apiFetch<{ parts: ApiPart[] }>(`/api/${tenantSlug}/app/repairbuddy/parts`);
        if (!alive) return;
        setParts(Array.isArray(res?.parts) ? res.parts : []);
      } catch (e) {
        if (!alive) return;

        if (e instanceof ApiError && e.status === 428 && typeof tenantSlug === "string" && tenantSlug.length > 0) {
          const next = `/app/${tenantSlug}/parts`;
          router.replace(`/app/${tenantSlug}/branches/select?next=${encodeURIComponent(next)}`);
          return;
        }

        setError(e instanceof Error ? e.message : "Failed to load parts.");
      } finally {
        if (!alive) return;
        setLoading(false);
      }
    }

    void load();

    return () => {
      alive = false;
    };
  }, [router, tenantSlug]);

  const filtered = React.useMemo(() => {
    const needle = q.trim().toLowerCase();
    if (!needle) return parts;
    return parts.filter((p) => `${p.name} ${p.sku ?? ""} ${p.id}`.toLowerCase().includes(needle));
  }, [parts, q]);

  const totalRows = filtered.length;
  const pageRows = React.useMemo(() => {
    const start = pageIndex * pageSize;
    const end = start + pageSize;
    return filtered.slice(start, end);
  }, [filtered, pageIndex, pageSize]);

  const columns = React.useMemo<Array<DataTableColumn<ApiPart>>>(
    () => [
      {
        id: "name",
        header: "Part",
        cell: (row) => (
          <div className="min-w-0">
            <div className="truncate font-semibold text-[var(--rb-text)]">{row.name}</div>
            <div className="truncate text-xs text-zinc-600">{row.id}</div>
          </div>
        ),
        className: "min-w-[280px]",
      },
      {
        id: "sku",
        header: "SKU",
        cell: (row) => <div className="text-sm text-zinc-700">{row.sku ?? "—"}</div>,
        className: "min-w-[200px]",
      },
      {
        id: "price",
        header: "Price",
        cell: (row) => {
          if (!row.price) return <div className="text-sm text-zinc-600">—</div>;
          return (
            <div className="text-sm font-semibold text-[var(--rb-text)] whitespace-nowrap">
              {formatMoney({ amountCents: row.price.amount_cents, currency: row.price.currency })}
            </div>
          );
        },
        className: "whitespace-nowrap",
      },
      {
        id: "stock",
        header: "Stock",
        cell: (row) => <div className="text-sm text-zinc-700">{row.stock ?? "—"}</div>,
        className: "whitespace-nowrap",
      },
    ],
    [],
  );

  return (
    <ListPageShell
      title="Parts"
      description="Inventory catalog and pricing for parts."
      actions={
        <Button disabled variant="outline" size="sm">
          New part
        </Button>
      }
      filters={
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div className="text-sm font-semibold text-[var(--rb-text)]">Search</div>
          <input
            value={q}
            onChange={(e) => setQ(e.target.value)}
            placeholder="Search parts..."
            className="h-10 w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 text-sm sm:max-w-[420px]"
          />
        </div>
      }
      loading={loading}
      error={error}
      empty={!loading && !error && filtered.length === 0}
      emptyTitle="No parts"
      emptyDescription="Add parts to track costs and availability."
    >
      <Card className="shadow-none">
        <CardContent className="pt-5">
          <DataTable
            title={typeof tenantSlug === "string" ? `Parts · ${tenantSlug}` : "Parts"}
            data={pageRows}
            loading={loading}
            emptyMessage="No parts."
            columns={columns}
            getRowId={(row) => row.id}
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
          />
        </CardContent>
      </Card>
    </ListPageShell>
  );
}
