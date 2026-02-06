"use client";

import React from "react";
import { LayoutGrid, Table2 } from "lucide-react";
import { cn } from "@/lib/cn";
import { Button } from "@/components/ui/Button";

export type DataViewMode = "table" | "grid";

export function DataViewToggle({
  value,
  onChange,
  disabled = false,
  className,
}: {
  value: DataViewMode;
  onChange: (value: DataViewMode) => void;
  disabled?: boolean;
  className?: string;
}) {
  return (
    <div className={cn("inline-flex items-center rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white", className)}>
      <Button
        type="button"
        size="sm"
        variant={value === "table" ? "outline" : "ghost"}
        className="rounded-none border-0 px-2"
        onClick={() => onChange("table")}
        disabled={disabled}
        aria-label="Table view"
        title="Table view"
      >
        <Table2 className="h-4 w-4" />
      </Button>
      <div className="h-6 w-px bg-[var(--rb-border)]" />
      <Button
        type="button"
        size="sm"
        variant={value === "grid" ? "outline" : "ghost"}
        className="rounded-none border-0 px-2"
        onClick={() => onChange("grid")}
        disabled={disabled}
        aria-label="Grid view"
        title="Grid view"
      >
        <LayoutGrid className="h-4 w-4" />
      </Button>
    </div>
  );
}
