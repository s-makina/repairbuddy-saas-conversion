"use client";

import { useSearchParams } from "next/navigation";

export type DetailTabKey = "overview" | "devices" | "timeline" | "messages" | "financial" | "print";

export function isDetailTabKey(value: string | null | undefined): value is DetailTabKey {
  return value === "overview" || value === "devices" || value === "timeline" || value === "messages" || value === "financial" || value === "print";
}

export function useDetailTab(defaultTab: DetailTabKey = "overview"): DetailTabKey {
  const searchParams = useSearchParams();
  const requestedTab = searchParams?.get("tab") ?? null;
  return isDetailTabKey(requestedTab) ? requestedTab : defaultTab;
}
