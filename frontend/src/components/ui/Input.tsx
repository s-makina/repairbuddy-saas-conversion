"use client";

import React from "react";
import { cn } from "@/lib/cn";

export function Input({ className, type, ...props }: React.InputHTMLAttributes<HTMLInputElement>) {
  return (
    <input
      type={type}
      className={cn(
        "w-full rounded-[var(--rb-radius-sm)] border border-zinc-300 bg-white px-3 py-2 text-sm text-[var(--rb-text)] outline-none transition",
        "placeholder:text-zinc-400",
        "focus-visible:ring-2 focus-visible:ring-[color:color-mix(in_srgb,var(--rb-orange),white_65%)] focus-visible:ring-offset-2 focus-visible:ring-offset-white",
        "disabled:cursor-not-allowed disabled:bg-[var(--rb-surface-muted)] disabled:opacity-70",
        className,
      )}
      {...props}
    />
  );
}
