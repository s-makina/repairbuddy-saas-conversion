"use client";

import React from "react";
import { useParams } from "next/navigation";
import { Button } from "@/components/ui/Button";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { ListPageShell } from "@/components/shells/ListPageShell";
import { mockApi } from "@/mock/mockApi";
import type { Part } from "@/mock/types";
import { formatMoney } from "@/lib/money";

export default function TenantPartsPage() {
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [parts, setParts] = React.useState<Part[]>([]);
  const [q, setQ] = React.useState("");

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

  const filtered = React.useMemo(() => {
    const needle = q.trim().toLowerCase();
    if (!needle) return parts;
    return parts.filter((p) => `${p.name} ${p.sku ?? ""} ${p.id}`.toLowerCase().includes(needle));
  }, [parts, q]);

  const columns = React.useMemo<Array<DataTableColumn<Part>>>(
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
      <DataTable
        title={typeof tenantSlug === "string" ? `Parts · ${tenantSlug}` : "Parts"}
        data={filtered}
        loading={loading}
        emptyMessage="No parts."
        columns={columns}
        getRowId={(row) => row.id}
      />
    </ListPageShell>
  );
}
