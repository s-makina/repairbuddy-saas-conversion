"use client";

import React, { useMemo, useState } from "react";
import { cn } from "@/lib/cn";
import { Button } from "@/components/ui/Button";

export type DataTableColumn<T> = {
  id: string;
  header: React.ReactNode;
  cell: (row: T) => React.ReactNode;
  className?: string;
  headerClassName?: string;
};

export type DataTableFilterOption<V> = {
  label: string;
  value: V;
};

export type DataTableFilter<T, V extends string | number> = {
  id: string;
  label: string;
  value: V;
  options: Array<DataTableFilterOption<V>>;
  onChange: (value: V) => void;
  predicate?: (row: T, value: V) => boolean;
};

export type DataTableServerState = {
  query: string;
  onQueryChange: (value: string) => void;
  pageIndex: number;
  onPageIndexChange: (value: number) => void;
  pageSize: number;
  onPageSizeChange: (value: number) => void;
  totalRows: number;
};

export function DataTable<T>({
  title,
  data,
  columns,
  getRowId,
  loading = false,
  emptyMessage = "No data.",
  search,
  filters = [],
  pageSizeOptions = [10, 25, 50],
  initialPageSize = 10,
  server,
  className,
}: {
  title?: React.ReactNode;
  data: T[];
  columns: Array<DataTableColumn<T>>;
  getRowId: (row: T) => string | number;
  loading?: boolean;
  emptyMessage?: string;
  search?: {
    placeholder?: string;
    getSearchText?: (row: T) => string;
  };
  filters?: Array<DataTableFilter<T, any>>;
  pageSizeOptions?: number[];
  initialPageSize?: number;
  server?: DataTableServerState;
  className?: string;
}) {
  const [localQuery, setLocalQuery] = useState("");
  const [localPageIndex, setLocalPageIndex] = useState(0);
  const [localPageSize, setLocalPageSize] = useState(initialPageSize);

  const isServer = !!server;

  const query = isServer ? server.query : localQuery;
  const setQuery = isServer ? server.onQueryChange : setLocalQuery;
  const pageIndex = isServer ? server.pageIndex : localPageIndex;
  const setPageIndex = isServer ? server.onPageIndexChange : setLocalPageIndex;
  const pageSize = isServer ? server.pageSize : localPageSize;
  const setPageSize = isServer ? server.onPageSizeChange : setLocalPageSize;

  const filteredData = useMemo(() => {
    if (isServer) return Array.isArray(data) ? data : [];

    const q = query.trim().toLowerCase();

    return (Array.isArray(data) ? data : []).filter((row) => {
      if (search && search.getSearchText && q.length > 0) {
        const text = search.getSearchText(row).toLowerCase();
        if (!text.includes(q)) return false;
      }

      for (const f of filters) {
        if (f.predicate && !f.predicate(row, f.value)) return false;
      }

      return true;
    });
  }, [data, filters, isServer, query, search]);

  const totalRows = isServer ? server.totalRows : filteredData.length;
  const totalPages = Math.max(1, Math.ceil(totalRows / pageSize));
  const clampedPageIndex = Math.min(pageIndex, totalPages - 1);

  const pageRows = useMemo(() => {
    if (isServer) return filteredData;
    const start = clampedPageIndex * pageSize;
    const end = start + pageSize;
    return filteredData.slice(start, end);
  }, [clampedPageIndex, filteredData, isServer, pageSize]);

  const canPrev = clampedPageIndex > 0;
  const canNext = clampedPageIndex < totalPages - 1;

  function onPrev() {
    setPageIndex(Math.max(0, clampedPageIndex - 1));
  }

  function onNext() {
    setPageIndex(Math.min(totalPages - 1, clampedPageIndex + 1));
  }

  function onChangePageSize(next: number) {
    setPageSize(next);
    setPageIndex(0);
  }

  return (
    <div className={cn("space-y-3", className)}>
      {title ? <div className="text-sm font-semibold text-[var(--rb-text)]">{title}</div> : null}

      {search || filters.length > 0 ? (
        <div className="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
          <div className="flex flex-col gap-3 md:flex-row md:items-end">
            {search ? (
              <div className="space-y-1">
                <label className="text-sm font-medium" htmlFor="datatable_search">
                  Search
                </label>
                <input
                  id="datatable_search"
                  className="w-full min-w-[220px] rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                  value={query}
                  onChange={(e) => {
                    setQuery(e.target.value);
                    setPageIndex(0);
                  }}
                  placeholder={search.placeholder ?? "Search..."}
                />
              </div>
            ) : null}

            {filters.map((f) => (
              <div key={f.id} className="space-y-1">
                <label className="text-sm font-medium" htmlFor={`datatable_filter_${f.id}`}>
                  {f.label}
                </label>
                <select
                  id={`datatable_filter_${f.id}`}
                  className="w-full min-w-[180px] rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                  value={String(f.value)}
                  onChange={(e) => {
                    f.onChange(e.target.value as any);
                    setPageIndex(0);
                  }}
                >
                  {f.options.map((opt) => (
                    <option key={String(opt.value)} value={String(opt.value)}>
                      {opt.label}
                    </option>
                  ))}
                </select>
              </div>
            ))}
          </div>

          <div className="text-sm text-zinc-600">{totalRows} result(s)</div>
        </div>
      ) : null}

      <div className="overflow-x-auto rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white">
        <table className="min-w-full border-collapse text-left text-sm">
          <thead className="bg-[var(--rb-surface-muted)]">
            <tr>
              {columns.map((c) => (
                <th
                  key={c.id}
                  className={cn(
                    "whitespace-nowrap border-b border-[var(--rb-border)] px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-600",
                    c.headerClassName,
                  )}
                >
                  {c.header}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {loading ? (
              <tr>
                <td
                  className="px-3 py-3 text-sm text-zinc-600"
                  colSpan={Math.max(1, columns.length)}
                >
                  Loading...
                </td>
              </tr>
            ) : pageRows.length === 0 ? (
              <tr>
                <td
                  className="px-3 py-3 text-sm text-zinc-600"
                  colSpan={Math.max(1, columns.length)}
                >
                  {emptyMessage}
                </td>
              </tr>
            ) : (
              pageRows.map((row) => (
                <tr key={String(getRowId(row))} className="border-t border-[var(--rb-border)]">
                  {columns.map((c) => (
                    <td key={c.id} className={cn("px-3 py-2 align-middle", c.className)}>
                      {c.cell(row)}
                    </td>
                  ))}
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div className="flex items-center gap-2">
          <Button variant="outline" size="sm" onClick={onPrev} disabled={!canPrev}>
            Prev
          </Button>
          <Button variant="outline" size="sm" onClick={onNext} disabled={!canNext}>
            Next
          </Button>
          <div className="text-sm text-zinc-600">
            Page {clampedPageIndex + 1} / {totalPages}
          </div>
        </div>

        <div className="flex items-center gap-2">
          <div className="text-sm text-zinc-600">Rows per page</div>
          <select
            className="rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
            value={String(pageSize)}
            onChange={(e) => onChangePageSize(Number(e.target.value))}
          >
            {pageSizeOptions.map((n) => (
              <option key={n} value={n}>
                {n}
              </option>
            ))}
          </select>
        </div>
      </div>
    </div>
  );
}
