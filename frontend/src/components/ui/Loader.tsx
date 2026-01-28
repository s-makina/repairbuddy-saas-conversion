"use client";

import React from "react";
import { cn } from "@/lib/cn";

type LoaderProps = {
  label?: string;
  fullscreen?: boolean;
  size?: "sm" | "md" | "lg";
  className?: string;
};

const sizeClasses: Record<NonNullable<LoaderProps["size"]>, string> = {
  sm: "h-4 w-4 border-2",
  md: "h-6 w-6 border-2",
  lg: "h-8 w-8 border-[3px]",
};

export function Loader({
  label = "Loading",
  fullscreen = false,
  size = "md",
  className,
}: LoaderProps) {
  return (
    <div
      className={cn(
        fullscreen
          ? "min-h-screen flex items-center justify-center bg-[var(--rb-surface-muted)]"
          : "flex items-center justify-center",
        className,
      )}
      role="status"
      aria-live="polite"
    >
      <div className="flex items-center gap-3">
        <div
          className={cn(
            "rounded-full border-[var(--rb-border)] border-t-[var(--rb-orange)] animate-spin",
            sizeClasses[size],
          )}
          aria-hidden="true"
        />
        {label ? <div className="text-sm font-medium text-[var(--rb-text)]">{label}...</div> : null}
      </div>
    </div>
  );
}
