"use client";

import React, { createContext, useContext, useId, useMemo, useState } from "react";
import { cn } from "@/lib/cn";

type TabsContextValue = {
  value: string;
  setValue: (next: string) => void;
  baseId: string;
};

const TabsContext = createContext<TabsContextValue | null>(null);

function useTabsContext() {
  const ctx = useContext(TabsContext);
  if (!ctx) throw new Error("Tabs components must be used within <Tabs />");
  return ctx;
}

export function Tabs({
  value,
  defaultValue,
  onValueChange,
  className,
  children,
}: {
  value?: string;
  defaultValue?: string;
  onValueChange?: (next: string) => void;
  className?: string;
  children: React.ReactNode;
}) {
  const baseId = useId();
  const [internal, setInternal] = useState<string>(defaultValue ?? "");

  const current = typeof value === "string" ? value : internal;

  const setValue = (next: string) => {
    if (!next) return;
    if (onValueChange) onValueChange(next);
    if (typeof value !== "string") setInternal(next);
  };

  const ctx = useMemo<TabsContextValue>(() => ({ value: current, setValue, baseId }), [baseId, current]);

  return (
    <TabsContext.Provider value={ctx}>
      <div className={cn("space-y-4", className)}>{children}</div>
    </TabsContext.Provider>
  );
}

export function TabsList({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) {
  return (
    <div
      className={cn(
        "inline-flex items-center gap-1 rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)] bg-white p-1 shadow-[var(--rb-shadow)]",
        className,
      )}
      role="tablist"
      {...props}
    />
  );
}

export function TabsTrigger({
  value,
  className,
  ...props
}: React.ButtonHTMLAttributes<HTMLButtonElement> & { value: string }) {
  const ctx = useTabsContext();
  const selected = ctx.value === value;

  return (
    <button
      type="button"
      role="tab"
      aria-selected={selected}
      aria-controls={`${ctx.baseId}-panel-${value}`}
      id={`${ctx.baseId}-tab-${value}`}
      onClick={() => ctx.setValue(value)}
      className={cn(
        "h-9 rounded-[var(--rb-radius-sm)] px-3 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--rb-orange)] focus-visible:ring-offset-2 focus-visible:ring-offset-white",
        selected ? "bg-[var(--rb-surface-muted)] text-[var(--rb-text)]" : "text-zinc-600 hover:bg-[var(--rb-surface-muted)]",
        className,
      )}
      {...props}
    />
  );
}

export function TabsContent({
  value,
  className,
  children,
  ...props
}: React.HTMLAttributes<HTMLDivElement> & { value: string }) {
  const ctx = useTabsContext();
  const selected = ctx.value === value;

  if (!selected) return null;

  return (
    <div
      role="tabpanel"
      id={`${ctx.baseId}-panel-${value}`}
      aria-labelledby={`${ctx.baseId}-tab-${value}`}
      className={cn(className)}
      {...props}
    >
      {children}
    </div>
  );
}
