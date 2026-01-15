import React from "react";
import { cn } from "@/lib/cn";

export type BadgeVariant = "default" | "info" | "success" | "warning" | "danger";

export function Badge({
  className,
  variant = "default",
  ...props
}: React.HTMLAttributes<HTMLSpanElement> & { variant?: BadgeVariant }) {
  const variants: Record<BadgeVariant, string> = {
    default: "bg-[var(--rb-surface-muted)] text-[var(--rb-text)] border-[var(--rb-border)]",
    info: "bg-[color:color-mix(in_srgb,var(--rb-blue),white_85%)] text-[var(--rb-blue)] border-[color:color-mix(in_srgb,var(--rb-blue),white_70%)]",
    success: "bg-[color:color-mix(in_srgb,#16a34a,white_85%)] text-[#166534] border-[color:color-mix(in_srgb,#16a34a,white_70%)]",
    warning: "bg-[color:color-mix(in_srgb,var(--rb-orange),white_85%)] text-[color:color-mix(in_srgb,var(--rb-orange),black_20%)] border-[color:color-mix(in_srgb,var(--rb-orange),white_70%)]",
    danger: "bg-[color:color-mix(in_srgb,#dc2626,white_85%)] text-[#991b1b] border-[color:color-mix(in_srgb,#dc2626,white_70%)]",
  };

  return (
    <span
      className={cn(
        "inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium",
        variants[variant],
        className,
      )}
      {...props}
    />
  );
}
