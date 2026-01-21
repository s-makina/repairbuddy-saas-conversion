import React from "react";
import { Card, CardContent } from "@/components/ui/Card";
import { Skeleton } from "@/components/ui/Skeleton";

export default function AdminLoading() {
  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <Skeleton className="h-7 w-36 rounded-[var(--rb-radius-sm)]" />
          <Skeleton className="mt-2 h-4 w-64 rounded-[var(--rb-radius-sm)]" />
        </div>
        <Skeleton className="h-9 w-24 rounded-[var(--rb-radius-sm)]" />
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {Array.from({ length: 4 }).map((_, idx) => (
          <Card key={idx} className="relative overflow-hidden">
            <CardContent className="pt-5">
              <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                  <Skeleton className="h-3 w-24 rounded-[var(--rb-radius-sm)]" />
                  <div className="mt-2">
                    <Skeleton className="h-8 w-28 rounded-[var(--rb-radius-sm)]" />
                  </div>
                </div>
                <Skeleton className="h-5 w-16 rounded-full" />
              </div>
              <div className="mt-3 h-px w-full bg-[var(--rb-border)]" />
            </CardContent>
          </Card>
        ))}
      </div>

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
        {Array.from({ length: 3 }).map((_, idx) => (
          <Card key={idx}>
            <CardContent className="pt-5">
              <Skeleton className="h-4 w-28 rounded-[var(--rb-radius-sm)]" />
              <div className="mt-4 flex flex-wrap gap-2">
                <Skeleton className="h-5 w-24 rounded-full" />
                <Skeleton className="h-5 w-20 rounded-full" />
                <Skeleton className="h-5 w-28 rounded-full" />
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      <Card>
        <CardContent className="pt-5">
          <div className="flex flex-wrap items-baseline justify-between gap-3">
            <div>
              <Skeleton className="h-4 w-44 rounded-[var(--rb-radius-sm)]" />
              <Skeleton className="mt-2 h-4 w-64 rounded-[var(--rb-radius-sm)]" />
            </div>
            <Skeleton className="h-4 w-16 rounded-[var(--rb-radius-sm)]" />
          </div>

          <div className="mt-4">
            <Skeleton className="h-40 w-full rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)]" />
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
