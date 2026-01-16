"use client";

import React from "react";
import { cn } from "@/lib/cn";

export type AlertVariant = "info" | "success" | "warning" | "danger";

function AlertIcon({ variant, className }: { variant: AlertVariant; className?: string }) {
  const common = "h-4 w-4 shrink-0";

  if (variant === "success") {
    return (
      <svg viewBox="0 0 24 24" className={cn(common, className)} fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
        <path d="M20 6L9 17l-5-5" />
      </svg>
    );
  }

  if (variant === "warning") {
    return (
      <svg viewBox="0 0 24 24" className={cn(common, className)} fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
        <path d="M12 9v4" />
        <path d="M12 17h.01" />
        <path d="M10.3 3.6l-8.3 14.3A2 2 0 0 0 3.7 21h16.6a2 2 0 0 0 1.7-3.1L13.7 3.6a2 2 0 0 0-3.4 0z" />
      </svg>
    );
  }

  if (variant === "danger") {
    return (
      <svg viewBox="0 0 24 24" className={cn(common, className)} fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
        <path d="M18 6L6 18" />
        <path d="M6 6l12 12" />
      </svg>
    );
  }

  return (
    <svg viewBox="0 0 24 24" className={cn(common, className)} fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M12 16v-4" />
      <path d="M12 8h.01" />
      <path d="M12 22a10 10 0 1 0-10-10 10 10 0 0 0 10 10z" />
    </svg>
  );
}

export function Alert({
  className,
  variant = "info",
  title,
  children,
  ...props
}: React.HTMLAttributes<HTMLDivElement> & {
  variant?: AlertVariant;
  title?: string;
}) {
  const variants: Record<AlertVariant, { wrapper: string; icon: string; title: string }> = {
    info: {
      wrapper:
        "border-[color:color-mix(in_srgb,var(--rb-blue),white_75%)] bg-[color:color-mix(in_srgb,var(--rb-blue),white_92%)] text-[var(--rb-text)]",
      icon: "text-[var(--rb-blue)]",
      title: "text-[var(--rb-blue)]",
    },
    success: {
      wrapper: "border-[color:color-mix(in_srgb,#16a34a,white_75%)] bg-[color:color-mix(in_srgb,#16a34a,white_92%)] text-[var(--rb-text)]",
      icon: "text-[#166534]",
      title: "text-[#166534]",
    },
    warning: {
      wrapper:
        "border-[color:color-mix(in_srgb,var(--rb-orange),white_70%)] bg-[color:color-mix(in_srgb,var(--rb-orange),white_92%)] text-[var(--rb-text)]",
      icon: "text-[color:color-mix(in_srgb,var(--rb-orange),black_20%)]",
      title: "text-[color:color-mix(in_srgb,var(--rb-orange),black_20%)]",
    },
    danger: {
      wrapper: "border-[color:color-mix(in_srgb,#dc2626,white_75%)] bg-[color:color-mix(in_srgb,#dc2626,white_92%)] text-[var(--rb-text)]",
      icon: "text-[#991b1b]",
      title: "text-[#991b1b]",
    },
  };

  const v = variants[variant];

  return (
    <div
      role="alert"
      className={cn("rounded-[var(--rb-radius-md)] border px-4 py-3", v.wrapper, className)}
      {...props}
    >
      <div className="flex gap-3">
        <AlertIcon variant={variant} className={cn("mt-0.5", v.icon)} />
        <div className="min-w-0">
          {title ? <div className={cn("text-sm font-semibold", v.title)}>{title}</div> : null}
          {children ? <div className={cn("text-sm text-zinc-700", title ? "mt-1" : "")}>
            {children}
          </div> : null}
        </div>
      </div>
    </div>
  );
}
