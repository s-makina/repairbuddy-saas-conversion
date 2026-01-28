"use client";

import React from "react";
import { useParams } from "next/navigation";
import { Button } from "@/components/ui/Button";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { ListPageShell } from "@/components/shells/ListPageShell";
import { mockApi } from "@/mock/mockApi";
import type { ExpenseCategory } from "@/mock/types";

export default function TenantExpenseCategoriesPage() {
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [categories, setCategories] = React.useState<ExpenseCategory[]>([]);

  React.useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);
        const res = await mockApi.listExpenseCategories();
        if (!alive) return;
        setCategories(Array.isArray(res) ? res : []);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load expense categories.");
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

  const columns = React.useMemo<Array<DataTableColumn<ExpenseCategory>>>(
    () => [
      {
        id: "name",
        header: "Category",
        cell: (row) => (
          <div className="min-w-0">
            <div className="truncate font-semibold text-[var(--rb-text)]">{row.name}</div>
            <div className="truncate text-xs text-zinc-600">{row.id}</div>
          </div>
        ),
        className: "min-w-[280px]",
      },
    ],
    [],
  );

  return (
    <ListPageShell
      title="Expense Categories"
      description="Categories used to classify expenses."
      actions={
        <Button disabled variant="outline" size="sm">
          New category
        </Button>
      }
      loading={loading}
      error={error}
      empty={!loading && !error && categories.length === 0}
      emptyTitle="No categories"
      emptyDescription="Create categories to organize expenses."
    >
      <DataTable
        title={typeof tenantSlug === "string" ? `Expense Categories · ${tenantSlug}` : "Expense Categories"}
        data={categories}
        loading={loading}
        emptyMessage="No categories."
        columns={columns}
        getRowId={(row) => row.id}
      />
    </ListPageShell>
  );
}
