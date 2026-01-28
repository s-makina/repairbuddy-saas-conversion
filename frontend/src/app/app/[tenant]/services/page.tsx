"use client";

import React from "react";
import { useParams } from "next/navigation";
import { Button } from "@/components/ui/Button";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { ListPageShell } from "@/components/shells/ListPageShell";
import { mockApi } from "@/mock/mockApi";
import type { Service } from "@/mock/types";
import { formatMoney } from "@/lib/money";

export default function TenantServicesPage() {
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [services, setServices] = React.useState<Service[]>([]);
  const [q, setQ] = React.useState("");

  React.useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);
        const res = await mockApi.listServices();
        if (!alive) return;
        setServices(Array.isArray(res) ? res : []);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load services.");
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
    if (!needle) return services;
    return services.filter((s) => `${s.name} ${s.description ?? ""} ${s.id}`.toLowerCase().includes(needle));
  }, [q, services]);

  const columns = React.useMemo<Array<DataTableColumn<Service>>>(
    () => [
      {
        id: "name",
        header: "Service",
        cell: (row) => (
          <div className="min-w-0">
            <div className="truncate font-semibold text-[var(--rb-text)]">{row.name}</div>
            <div className="truncate text-xs text-zinc-600">{row.id}</div>
          </div>
        ),
        className: "min-w-[260px]",
      },
      {
        id: "desc",
        header: "Description",
        cell: (row) => <div className="text-sm text-zinc-700">{row.description ?? "—"}</div>,
        className: "min-w-[320px]",
      },
      {
        id: "price",
        header: "Base price",
        cell: (row) => {
          if (!row.base_price) return <div className="text-sm text-zinc-600">—</div>;
          return (
            <div className="text-sm font-semibold text-[var(--rb-text)] whitespace-nowrap">
              {formatMoney({ amountCents: row.base_price.amount_cents, currency: row.base_price.currency })}
            </div>
          );
        },
        className: "whitespace-nowrap",
      },
    ],
    [],
  );

  return (
    <ListPageShell
      title="Services"
      description="Service catalog used for estimates and jobs."
      actions={
        <Button disabled variant="outline" size="sm">
          New service
        </Button>
      }
      filters={
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div className="text-sm font-semibold text-[var(--rb-text)]">Search</div>
          <input
            value={q}
            onChange={(e) => setQ(e.target.value)}
            placeholder="Search services..."
            className="h-10 w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 text-sm sm:max-w-[420px]"
          />
        </div>
      }
      loading={loading}
      error={error}
      empty={!loading && !error && filtered.length === 0}
      emptyTitle="No services"
      emptyDescription="Add services to standardize pricing."
    >
      <DataTable
        title={typeof tenantSlug === "string" ? `Services · ${tenantSlug}` : "Services"}
        data={filtered}
        loading={loading}
        emptyMessage="No services."
        columns={columns}
        getRowId={(row) => row.id}
      />
    </ListPageShell>
  );
}
