"use client";

import React from "react";
import { usePathname, useRouter, useSearchParams } from "next/navigation";

function parsePositiveInt(value: string | null, fallback: number): number {
  if (!value) return fallback;
  const n = Number(value);
  if (!Number.isFinite(n)) return fallback;
  const i = Math.floor(n);
  if (i <= 0) return fallback;
  return i;
}

type UpdateValue = string | number | boolean | null | undefined;

function normalizeUpdateValue(v: UpdateValue): string | null {
  if (v === null || v === undefined) return null;
  if (typeof v === "boolean") return v ? "1" : "0";
  const s = String(v);
  if (s.trim().length === 0) return null;
  return s;
}

export function useUrlDataGridState({
  queryKey = "q",
  pageKey = "page",
  pageSizeKey = "per_page",
  defaultPageSize = 10,
}: {
  queryKey?: string;
  pageKey?: string;
  pageSizeKey?: string;
  defaultPageSize?: number;
} = {}) {
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();

  const query = searchParams.get(queryKey) ?? "";
  const page = parsePositiveInt(searchParams.get(pageKey), 1);
  const perPage = parsePositiveInt(searchParams.get(pageSizeKey), defaultPageSize);

  const pageIndex = Math.max(0, page - 1);
  const pageSize = perPage;

  const replaceParams = React.useCallback(
    (next: URLSearchParams) => {
      const qs = next.toString();
      const url = qs.length > 0 ? `${pathname}?${qs}` : pathname;
      router.replace(url, { scroll: false });
    },
    [pathname, router],
  );

  const updateParams = React.useCallback(
    (updates: Record<string, UpdateValue>, opts: { resetPage?: boolean } = {}) => {
      const next = new URLSearchParams(searchParams.toString());

      for (const [k, v] of Object.entries(updates)) {
        const norm = normalizeUpdateValue(v);
        if (norm === null) {
          next.delete(k);
        } else {
          next.set(k, norm);
        }
      }

      if (opts.resetPage) {
        next.set(pageKey, "1");
      }

      const currentQs = searchParams.toString();
      const nextQs = next.toString();
      if (currentQs === nextQs) return;

      replaceParams(next);
    },
    [pageKey, replaceParams, searchParams],
  );

  const onQueryChange = React.useCallback(
    (value: string) => {
      updateParams({ [queryKey]: value });
    },
    [queryKey, updateParams],
  );

  const onPageIndexChange = React.useCallback(
    (value: number) => {
      const nextIndex = Number.isFinite(value) ? Math.max(0, Math.floor(value)) : 0;
      updateParams({ [pageKey]: String(nextIndex + 1) });
    },
    [pageKey, updateParams],
  );

  const onPageSizeChange = React.useCallback(
    (value: number) => {
      const nextSize = Number.isFinite(value) ? Math.max(1, Math.floor(value)) : defaultPageSize;
      updateParams({ [pageSizeKey]: String(nextSize) });
    },
    [defaultPageSize, pageSizeKey, updateParams],
  );

  const setParam = React.useCallback(
    (key: string, value: UpdateValue, opts: { resetPage?: boolean } = {}) => {
      updateParams({ [key]: value }, opts);
    },
    [updateParams],
  );

  const getParam = React.useCallback(
    (key: string) => {
      return searchParams.get(key);
    },
    [searchParams],
  );

  return {
    query,
    onQueryChange,
    pageIndex,
    onPageIndexChange,
    pageSize,
    onPageSizeChange,
    getParam,
    setParam,
    updateParams,
  };
}
