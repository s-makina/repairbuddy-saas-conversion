"use client";

import React, { useId, useMemo, useState } from "react";
import { cn } from "@/lib/cn";
import { Button } from "@/components/ui/Button";
import { CardSkeleton } from "@/components/ui/Skeleton";

export type DataGridFilterOption<V> = {
  label: string;
  value: V;
};

export type DataGridFilterValue = string | number;

export type DataGridFilter<T, V extends DataGridFilterValue> = {
  id: string;
  label: string;
  value: V;
  options: Array<DataGridFilterOption<V>>;
  onChange: (value: V) => void;
  predicate?: (row: T, value: V) => boolean;
};

export type DataGridServerState = {
  query: string;
  onQueryChange: (value: string) => void;
  pageIndex: number;
  onPageIndexChange: (value: number) => void;
  pageSize: number;
  onPageSizeChange: (value: number) => void;
  totalRows: number;
};

export function DataGrid<T>({
  title,
  data,
  renderItem,
  getItemId,
  loading = false,
  emptyMessage = "No data.",
  search,
  filters = [],
  pageSizeOptions = [10, 25, 50],
  initialPageSize = 10,
  server,
  onItemClick,
  className,
  gridClassName,
}: {
  title?: React.ReactNode;
  data: T[];
  renderItem: (row: T) => React.ReactNode;
  getItemId: (row: T) => string | number;
  loading?: boolean;
  emptyMessage?: string;
  search?: {
    placeholder?: string;
    getSearchText?: (row: T) => string;
  };
  filters?: Array<DataGridFilter<T, DataGridFilterValue>>;
  pageSizeOptions?: number[];
  initialPageSize?: number;
  server?: DataGridServerState;
  onItemClick?: (row: T) => void;
  className?: string;
  gridClassName?: string;
}) {
  const [localQuery, setLocalQuery] = useState("");
  const [localPageIndex, setLocalPageIndex] = useState(0);
  const [localPageSize, setLocalPageSize] = useState(initialPageSize);
  const idBase = useId();

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

  const showToolbar = !!search || filters.length > 0;
  const loadingItemCount = Math.max(1, Math.min(12, pageSize || 10));

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

      {showToolbar ? (
        <div className="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
          <div className="flex flex-col gap-3 md:flex-row md:items-end">
            {search ? (
              <div className="space-y-1">
                <label className="text-sm font-medium" htmlFor={`${idBase}_datagrid_search`}>
                  Search
                </label>
                <input
                  id={`${idBase}_datagrid_search`}
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
                <label className="text-sm font-medium" htmlFor={`${idBase}_datagrid_filter_${f.id}`}>
                  {f.label}
                </label>
                <select
                  id={`${idBase}_datagrid_filter_${f.id}`}
                  className="w-full min-w-[180px] rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                  value={String(f.value)}
                  onChange={(e) => {
                    const selected = f.options.find((opt) => String(opt.value) === e.target.value);
                    const nextValue: DataGridFilterValue = selected ? selected.value : e.target.value;
                    f.onChange(nextValue);
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

      {loading ? (
        <div className={cn("grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4", gridClassName)}>
          {Array.from({ length: loadingItemCount }).map((_, idx) => (
            <CardSkeleton key={idx} lines={3} className="shadow-none" />
          ))}
        </div>
      ) : pageRows.length === 0 ? (
        <div className="text-sm text-zinc-600">{emptyMessage}</div>
      ) : (
        <div className={cn("grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4", gridClassName)}>
          {pageRows.map((row) => {
            const clickable = !!onItemClick;
            return (
              <div
                key={String(getItemId(row))}
                className={cn(clickable ? "cursor-pointer" : null)}
                tabIndex={clickable ? 0 : undefined}
                role={clickable ? "button" : undefined}
                onClick={
                  clickable
                    ? () => {
                        onItemClick?.(row);
                      }
                    : undefined
                }
                onKeyDown={
                  clickable
                    ? (e) => {
                        if (e.key === "Enter" || e.key === " ") {
                          e.preventDefault();
                          onItemClick?.(row);
                        }
                      }
                    : undefined
                }
              >
                {renderItem(row)}
              </div>
            );
          })}
        </div>
      )}

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
          <div className="text-sm text-zinc-600">Items per page</div>
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
