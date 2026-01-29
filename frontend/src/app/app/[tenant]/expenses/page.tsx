"use client";

import React from "react";
import { useParams } from "next/navigation";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { ListPageShell } from "@/components/shells/ListPageShell";
import { mockApi } from "@/mock/mockApi";
import type { Expense, ExpenseCategory, Job } from "@/mock/types";
import { formatMoney } from "@/lib/money";

export default function TenantExpensesPage() {
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [expenses, setExpenses] = React.useState<Expense[]>([]);
  const [jobs, setJobs] = React.useState<Job[]>([]);
  const [categories, setCategories] = React.useState<ExpenseCategory[]>([]);

  const [query, setQuery] = React.useState("");
  const [pageIndex, setPageIndex] = React.useState(0);
  const [pageSize, setPageSize] = React.useState(10);

  React.useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);
        const [e, j, c] = await Promise.all([mockApi.listExpenses(), mockApi.listJobs(), mockApi.listExpenseCategories()]);
        if (!alive) return;
        setExpenses(Array.isArray(e) ? e : []);
        setJobs(Array.isArray(j) ? j : []);
        setCategories(Array.isArray(c) ? c : []);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load expenses.");
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

  const jobById = React.useMemo(() => new Map(jobs.map((j) => [j.id, j])), [jobs]);
  const catById = React.useMemo(() => new Map(categories.map((c) => [c.id, c])), [categories]);

  const filtered = React.useMemo(() => {
    const needle = query.trim().toLowerCase();
    if (!needle) return expenses;

    return expenses.filter((e) => {
      const jobCase = e.job_id ? (jobById.get(e.job_id)?.case_number ?? e.job_id) : "";
      const catName = e.category_id ? (catById.get(e.category_id)?.name ?? e.category_id) : "";
      const hay = `${e.id} ${e.label} ${jobCase} ${catName}`.toLowerCase();
      return hay.includes(needle);
    });
  }, [catById, expenses, jobById, query]);

  const totalRows = filtered.length;
  const pageRows = React.useMemo(() => {
    const start = pageIndex * pageSize;
    const end = start + pageSize;
    return filtered.slice(start, end);
  }, [filtered, pageIndex, pageSize]);

  const columns = React.useMemo<Array<DataTableColumn<Expense>>>(
    () => [
      {
        id: "label",
        header: "Expense",
        cell: (row) => (
          <div className="min-w-0">
            <div className="truncate font-semibold text-[var(--rb-text)]">{row.label}</div>
            <div className="truncate text-xs text-zinc-600">{row.id}</div>
          </div>
        ),
        className: "min-w-[280px]",
      },
      {
        id: "category",
        header: "Category",
        cell: (row) => <div className="text-sm text-zinc-700">{row.category_id ? catById.get(row.category_id)?.name ?? row.category_id : "—"}</div>,
        className: "min-w-[200px]",
      },
      {
        id: "job",
        header: "Job",
        cell: (row) => <div className="text-sm text-zinc-700">{row.job_id ? (jobById.get(row.job_id)?.case_number ?? row.job_id) : "—"}</div>,
        className: "min-w-[160px]",
      },
      {
        id: "amount",
        header: "Amount",
        cell: (row) => (
          <div className="text-sm font-semibold text-[var(--rb-text)] whitespace-nowrap">
            {formatMoney({ amountCents: row.amount.amount_cents, currency: row.amount.currency })}
          </div>
        ),
        className: "whitespace-nowrap",
      },
      {
        id: "created",
        header: "Created",
        cell: (row) => <div className="text-sm text-zinc-600">{new Date(row.created_at).toLocaleString()}</div>,
        className: "whitespace-nowrap",
      },
    ],
    [catById, jobById],
  );

  return (
    <ListPageShell
      title="Expenses"
      description="Track costs and job-related expenses."
      actions={
        <Button disabled variant="outline" size="sm">
          New expense
        </Button>
      }
      loading={loading}
      error={error}
      empty={!loading && !error && expenses.length === 0}
      emptyTitle="No expenses"
      emptyDescription="Expenses can be linked to jobs and categories."
    >
      <Card className="shadow-none">
        <CardContent className="pt-5">
          <DataTable
            title={typeof tenantSlug === "string" ? `Expenses · ${tenantSlug}` : "Expenses"}
            data={pageRows}
            loading={loading}
            emptyMessage="No expenses."
            columns={columns}
            getRowId={(row) => row.id}
            search={{
              placeholder: "Search expenses...",
            }}
            server={{
              query,
              onQueryChange: (value) => {
                setQuery(value);
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
