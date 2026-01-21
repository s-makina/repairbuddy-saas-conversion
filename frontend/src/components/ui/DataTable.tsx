"use client";

import React, { useId, useMemo, useState } from "react";
import { cn } from "@/lib/cn";
import { Button } from "@/components/ui/Button";
import { DropdownMenu, DropdownMenuItem, DropdownMenuSeparator } from "@/components/ui/DropdownMenu";
import { Skeleton } from "@/components/ui/Skeleton";
import { apiDownload } from "@/lib/api";

export type DataTableColumn<T> = {
  id: string;
  header: React.ReactNode;
  cell: (row: T) => React.ReactNode;
  sortId?: string;
  hiddenByDefault?: boolean;
  className?: string;
  headerClassName?: string;
};

export type DataTableFilterOption<V> = {
  label: string;
  value: V;
};

export type DataTableFilterValue = string | number;

export type DataTableFilter<T, V extends DataTableFilterValue> = {
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
  sort?: {
    id: string;
    dir: "asc" | "desc";
  } | null;
  onSortChange?: (value: { id: string; dir: "asc" | "desc" } | null) => void;
};

export type DataTableExportFormat = "csv" | "xlsx" | "pdf";

export type DataTableExportConfig = {
  url: string;
  formats?: DataTableExportFormat[];
  filename?: (args: { format: DataTableExportFormat }) => string;
  extraQuery?: Record<string, string | number | boolean | null | undefined>;
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
  exportConfig,
  columnVisibilityKey,
  onRowClick,
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
  filters?: Array<DataTableFilter<T, DataTableFilterValue>>;
  pageSizeOptions?: number[];
  initialPageSize?: number;
  server?: DataTableServerState;
  exportConfig?: DataTableExportConfig;
  columnVisibilityKey?: string;
  onRowClick?: (row: T) => void;
  className?: string;
}) {
  const [localQuery, setLocalQuery] = useState("");
  const [localPageIndex, setLocalPageIndex] = useState(0);
  const [localPageSize, setLocalPageSize] = useState(initialPageSize);
  const [exportBusyFormat, setExportBusyFormat] = useState<DataTableExportFormat | null>(null);
  const [exportError, setExportError] = useState<string | null>(null);
  const [visVersion, setVisVersion] = useState(0);
  const idBase = useId();

  const isServer = !!server;

  const query = isServer ? server.query : localQuery;
  const setQuery = isServer ? server.onQueryChange : setLocalQuery;
  const pageIndex = isServer ? server.pageIndex : localPageIndex;
  const setPageIndex = isServer ? server.onPageIndexChange : setLocalPageIndex;
  const pageSize = isServer ? server.pageSize : localPageSize;
  const setPageSize = isServer ? server.onPageSizeChange : setLocalPageSize;

  const sort = isServer ? server.sort ?? null : null;
  const setSort = isServer ? server.onSortChange : undefined;

  const visibleColumns = useMemo(() => {
    const all = Array.isArray(columns) ? columns : [];
    if (!columnVisibilityKey || typeof window === "undefined") {
      const vis = all.filter((c) => !c.hiddenByDefault);
      return vis.length > 0 ? vis : all.slice(0, 1);
    }

    void visVersion;

    try {
      const raw = window.localStorage.getItem(columnVisibilityKey);
      if (!raw) {
        const vis = all.filter((c) => !c.hiddenByDefault);
        return vis.length > 0 ? vis : all.slice(0, 1);
      }
      const parsed = JSON.parse(raw) as Record<string, boolean>;
      const vis = all.filter((c) => {
        const v = parsed[c.id];
        if (typeof v === "boolean") return v;
        return !c.hiddenByDefault;
      });
      return vis.length > 0 ? vis : all.slice(0, 1);
    } catch {
      const vis = all.filter((c) => !c.hiddenByDefault);
      return vis.length > 0 ? vis : all.slice(0, 1);
    }
  }, [columnVisibilityKey, columns, visVersion]);

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

  const loadingRowCount = Math.max(1, Math.min(8, pageSize || 10));

  const pageRows = useMemo(() => {
    if (isServer) return filteredData;
    const start = clampedPageIndex * pageSize;
    const end = start + pageSize;
    return filteredData.slice(start, end);
  }, [clampedPageIndex, filteredData, isServer, pageSize]);

  const canPrev = clampedPageIndex > 0;
  const canNext = clampedPageIndex < totalPages - 1;

  const showToolbar = !!search || filters.length > 0 || !!exportConfig || !!columnVisibilityKey;

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

  function toggleColumn(colId: string, nextVisible: boolean) {
    if (!columnVisibilityKey || typeof window === "undefined") return;

    if (!nextVisible && visibleColumns.length <= 1) return;

    try {
      const raw = window.localStorage.getItem(columnVisibilityKey);
      const parsed = (raw ? (JSON.parse(raw) as Record<string, boolean>) : {}) ?? {};
      parsed[colId] = nextVisible;
      window.localStorage.setItem(columnVisibilityKey, JSON.stringify(parsed));
      setVisVersion((v) => v + 1);
    } catch {
      // ignore
    }
  }

  async function onExport(format: DataTableExportFormat) {
    if (!exportConfig) return;
    if (exportBusyFormat) return;

    setExportError(null);
    setExportBusyFormat(format);

    try {
      const qs = new URLSearchParams();
      if (query.trim().length > 0) qs.set("q", query.trim());

      for (const f of filters) {
        const v = f.value;
        if (v === null || v === undefined) continue;
        if (String(v).length === 0) continue;
        qs.set(f.id, String(v));
      }

      if (isServer && sort?.id && sort?.dir) {
        qs.set("sort", sort.id);
        qs.set("dir", sort.dir);
      }

      qs.set("format", format);

      if (exportConfig.extraQuery) {
        for (const [k, v] of Object.entries(exportConfig.extraQuery)) {
          if (v === null || v === undefined) continue;
          qs.set(k, String(v));
        }
      }

      const url = `${exportConfig.url}${exportConfig.url.includes("?") ? "&" : "?"}${qs.toString()}`;
      const filename = exportConfig.filename?.({ format });
      await apiDownload(url, { filename });
    } catch (err) {
      setExportError(err instanceof Error ? err.message : "Export failed.");
    } finally {
      setExportBusyFormat(null);
    }
  }

  function renderSortIcon(col: DataTableColumn<T>) {
    if (!isServer) return null;
    if (!col.sortId) return null;
    if (!sort || sort.id !== col.sortId) return <span className="ml-1 text-zinc-400">↕</span>;
    return sort.dir === "asc" ? <span className="ml-1 text-zinc-700">↑</span> : <span className="ml-1 text-zinc-700">↓</span>;
  }

  function onToggleSort(col: DataTableColumn<T>) {
    if (!isServer) return;
    if (!col.sortId) return;
    if (!setSort) return;

    if (!sort || sort.id !== col.sortId) {
      setSort({ id: col.sortId, dir: "asc" });
      setPageIndex(0);
      return;
    }

    if (sort.dir === "asc") {
      setSort({ id: col.sortId, dir: "desc" });
      setPageIndex(0);
      return;
    }

    setSort(null);
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
                <label className="text-sm font-medium" htmlFor={`${idBase}_datatable_search`}>
                  Search
                </label>
                <input
                  id={`${idBase}_datatable_search`}
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
                <label className="text-sm font-medium" htmlFor={`${idBase}_datatable_filter_${f.id}`}>
                  {f.label}
                </label>
                <select
                  id={`${idBase}_datatable_filter_${f.id}`}
                  className="w-full min-w-[180px] rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                  value={String(f.value)}
                  onChange={(e) => {
                    const selected = f.options.find((opt) => String(opt.value) === e.target.value);
                    const nextValue: DataTableFilterValue = selected ? selected.value : e.target.value;
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

          <div className="flex flex-col items-start gap-2 md:items-end">
            <div className="text-sm text-zinc-600">{totalRows} result(s)</div>

            <div className="flex flex-wrap items-center gap-2">
              {exportConfig ? (
                <DropdownMenu
                  align="right"
                  trigger={({ toggle }) => (
                    <Button variant="outline" size="sm" onClick={toggle} disabled={!!exportBusyFormat}>
                      {exportBusyFormat ? "Exporting..." : "Export"}
                    </Button>
                  )}
                >
                  {({ close }) => {
                    const formats = exportConfig.formats ?? ["csv", "xlsx", "pdf"];
                    return (
                      <>
                        {formats.map((fmt) => (
                          <DropdownMenuItem
                            key={fmt}
                            onSelect={() => {
                              close();
                              void onExport(fmt);
                            }}
                            disabled={!!exportBusyFormat}
                          >
                            Export {fmt.toUpperCase()}
                          </DropdownMenuItem>
                        ))}
                      </>
                    );
                  }}
                </DropdownMenu>
              ) : null}

              {columnVisibilityKey ? (
                <DropdownMenu
                  align="right"
                  trigger={({ toggle }) => (
                    <Button variant="outline" size="sm" onClick={toggle}>
                      Columns
                    </Button>
                  )}
                >
                  {({ close }) => (
                    <>
                      <DropdownMenuItem
                        onSelect={() => {
                          close();
                          for (const c of columns) {
                            toggleColumn(c.id, true);
                          }
                        }}
                      >
                        Show all
                      </DropdownMenuItem>
                      <DropdownMenuItem
                        onSelect={() => {
                          close();
                          for (const c of columns) {
                            toggleColumn(c.id, !c.hiddenByDefault);
                          }
                        }}
                      >
                        Reset defaults
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />

                      {columns.map((c) => {
                        const isVisible = visibleColumns.some((vc) => vc.id === c.id);
                        return (
                          <DropdownMenuItem
                            key={c.id}
                            onSelect={() => {
                              toggleColumn(c.id, !isVisible);
                            }}
                          >
                            <span className="mr-2 inline-block w-4">{isVisible ? "✓" : ""}</span>
                            {typeof c.header === "string" ? c.header : c.id}
                          </DropdownMenuItem>
                        );
                      })}
                    </>
                  )}
                </DropdownMenu>
              ) : null}
            </div>
          </div>
        </div>
      ) : null}

      {exportError ? <div className="text-sm text-red-600">{exportError}</div> : null}

      <div className="overflow-x-auto rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white">
        <table className="min-w-full border-collapse text-left text-sm">
          <thead className="bg-[var(--rb-surface-muted)]">
            <tr>
              {visibleColumns.map((c) => (
                <th
                  key={c.id}
                  className={cn(
                    "whitespace-nowrap border-b border-[var(--rb-border)] px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-600",
                    c.headerClassName,
                  )}
                >
                  {isServer && c.sortId && setSort ? (
                    <button
                      type="button"
                      onClick={() => onToggleSort(c)}
                      className="inline-flex items-center gap-1 hover:text-zinc-900"
                    >
                      <span>{c.header}</span>
                      {renderSortIcon(c)}
                    </button>
                  ) : (
                    c.header
                  )}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {loading ? (
              Array.from({ length: loadingRowCount }).map((_, rowIdx) => (
                <tr key={`loading_${rowIdx}`} className="border-t border-[var(--rb-border)]">
                  {visibleColumns.map((c, colIdx) => (
                    <td key={c.id} className={cn("px-3 py-2 align-middle", c.className)}>
                      <Skeleton
                        className={cn(
                          "h-3 rounded-[var(--rb-radius-sm)]",
                          colIdx === 0 ? "w-3/4" : colIdx === visibleColumns.length - 1 ? "w-16" : "w-full",
                        )}
                      />
                    </td>
                  ))}
                </tr>
              ))
            ) : pageRows.length === 0 ? (
              <tr>
                <td
                  className="px-3 py-3 text-sm text-zinc-600"
                  colSpan={Math.max(1, visibleColumns.length)}
                >
                  {emptyMessage}
                </td>
              </tr>
            ) : (
              pageRows.map((row) => (
                <tr
                  key={String(getRowId(row))}
                  className={cn(
                    "border-t border-[var(--rb-border)]",
                    onRowClick ? "cursor-pointer hover:bg-[var(--rb-surface-muted)]" : null,
                  )}
                  tabIndex={onRowClick ? 0 : undefined}
                  role={onRowClick ? "button" : undefined}
                  onClick={
                    onRowClick
                      ? () => {
                          onRowClick(row);
                        }
                      : undefined
                  }
                  onKeyDown={
                    onRowClick
                      ? (e) => {
                          if (e.key === "Enter" || e.key === " ") {
                            e.preventDefault();
                            onRowClick(row);
                          }
                        }
                      : undefined
                  }
                >
                  {visibleColumns.map((c) => (
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
