"use client";

import React from "react";
import { useParams } from "next/navigation";
import { Button } from "@/components/ui/Button";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { ListPageShell } from "@/components/shells/ListPageShell";
import { mockApi } from "@/mock/mockApi";
import type { DeviceBrand } from "@/mock/types";

export default function TenantDeviceBrandsPage() {
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [brands, setBrands] = React.useState<DeviceBrand[]>([]);

  React.useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);
        const res = await mockApi.listDeviceBrands();
        if (!alive) return;
        setBrands(Array.isArray(res) ? res : []);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load device brands.");
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

  const columns = React.useMemo<Array<DataTableColumn<DeviceBrand>>>(
    () => [
      {
        id: "name",
        header: "Brand",
        cell: (row) => (
          <div className="min-w-0">
            <div className="truncate font-semibold text-[var(--rb-text)]">{row.name}</div>
            <div className="truncate text-xs text-zinc-600">{row.id}</div>
          </div>
        ),
        className: "min-w-[260px]",
      },
    ],
    [],
  );

  return (
    <ListPageShell
      title="Device Brands"
      description="Brand taxonomy for devices."
      actions={
        <Button disabled variant="outline" size="sm">
          New brand
        </Button>
      }
      loading={loading}
      error={error}
      empty={!loading && !error && brands.length === 0}
      emptyTitle="No brands"
      emptyDescription="Add brands to organize your devices."
    >
      <DataTable
        title={typeof tenantSlug === "string" ? `Device Brands · ${tenantSlug}` : "Device Brands"}
        data={brands}
        loading={loading}
        emptyMessage="No brands."
        columns={columns}
        getRowId={(row) => row.id}
      />
    </ListPageShell>
  );
}
