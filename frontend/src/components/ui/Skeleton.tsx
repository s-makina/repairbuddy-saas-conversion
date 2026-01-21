"use client";

import React from "react";
import { cn } from "@/lib/cn";
import { Card, CardContent } from "@/components/ui/Card";

type SkeletonProps = React.HTMLAttributes<HTMLDivElement> & {
  "aria-hidden"?: boolean;
};

export function Skeleton({ className, "aria-hidden": ariaHidden = true, ...props }: SkeletonProps) {
  return <div aria-hidden={ariaHidden} className={cn("rb-skeleton", className)} {...props} />;
}

export function CardSkeleton({
  lines = 3,
  className,
}: {
  lines?: number;
  className?: string;
}) {
  const safeLines = Math.max(1, Math.min(lines, 12));

  return (
    <Card className={cn("overflow-hidden", className)}>
      <CardContent className="space-y-3 pt-5">
        <Skeleton className="h-4 w-32 rounded-[var(--rb-radius-sm)]" />
        {Array.from({ length: safeLines }).map((_, idx) => (
          <Skeleton
            key={idx}
            className={cn(
              "h-3 rounded-[var(--rb-radius-sm)]",
              idx === safeLines - 1 ? "w-3/5" : "w-full",
            )}
          />
        ))}
      </CardContent>
    </Card>
  );
}

export function TableSkeleton({
  rows = 6,
  columns = 5,
  className,
}: {
  rows?: number;
  columns?: number;
  className?: string;
}) {
  const safeRows = Math.max(1, Math.min(rows, 20));
  const safeCols = Math.max(1, Math.min(columns, 10));

  return (
    <div
      className={cn(
        "overflow-hidden rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)] bg-white shadow-[var(--rb-shadow)]",
        className,
      )}
    >
      <div className="border-b border-[var(--rb-border)] px-5 py-4">
        <div className="grid gap-3" style={{ gridTemplateColumns: `repeat(${safeCols}, minmax(0, 1fr))` }}>
          {Array.from({ length: safeCols }).map((_, idx) => (
            <Skeleton key={idx} className="h-3 w-full rounded-[var(--rb-radius-sm)]" />
          ))}
        </div>
      </div>
      <div className="px-5 pb-5 pt-4">
        <div className="space-y-3">
          {Array.from({ length: safeRows }).map((_, rowIdx) => (
            <div
              key={rowIdx}
              className="grid gap-3"
              style={{ gridTemplateColumns: `repeat(${safeCols}, minmax(0, 1fr))` }}
            >
              {Array.from({ length: safeCols }).map((_, colIdx) => (
                <Skeleton
                  key={colIdx}
                  className={cn(
                    "h-3 rounded-[var(--rb-radius-sm)]",
                    colIdx === 0 ? "w-3/4" : "w-full",
                  )}
                />
              ))}
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
