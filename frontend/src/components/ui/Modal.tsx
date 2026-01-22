"use client";

import React, { useEffect } from "react";
import { cn } from "@/lib/cn";

export function Modal({
  open,
  onClose,
  title,
  children,
  footer,
  position = "center",
  variant = "glass",
  className,
}: {
  open: boolean;
  onClose: () => void;
  title?: React.ReactNode;
  children: React.ReactNode;
  footer?: React.ReactNode;
  position?: "center" | "top-center";
  variant?: "glass" | "solid";
  className?: string;
}) {
  useEffect(() => {
    function onKeyDown(e: KeyboardEvent) {
      if (!open) return;
      if (e.key === "Escape") onClose();
    }

    document.addEventListener("keydown", onKeyDown);
    return () => document.removeEventListener("keydown", onKeyDown);
  }, [onClose, open]);

  if (!open) return null;

  const containerClassName =
    position === "top-center"
      ? "fixed inset-0 z-50 flex items-start justify-center px-4 pt-8"
      : "fixed inset-0 z-50 flex items-center justify-center px-4";

  const panelClassName =
    variant === "solid"
      ? cn(
          "relative w-full max-w-lg overflow-hidden rounded-[var(--rb-radius-lg)]",
          "border border-[var(--rb-border)]",
          "bg-white",
          "shadow-[var(--rb-shadow)]",
        )
      : cn(
          "relative w-full max-w-lg overflow-hidden rounded-[var(--rb-radius-lg)]",
          "border border-[color:color-mix(in_srgb,var(--rb-border),white_55%)]",
          "bg-white/75 backdrop-blur-xl",
          "shadow-[0_18px_55px_-18px_rgba(0,0,0,0.55)]",
          "ring-1 ring-white/25",
        );

  const showInnerGlow = variant === "glass";

  return (
    <div className={containerClassName}>
      <button
        type="button"
        className={cn(
          "absolute inset-0",
          "bg-black/35 backdrop-blur-md",
          "bg-[radial-gradient(900px_600px_at_20%_20%,color-mix(in_srgb,#16a34a,transparent_82%)_0%,transparent_60%),radial-gradient(900px_600px_at_80%_30%,color-mix(in_srgb,var(--rb-blue),transparent_82%)_0%,transparent_60%),radial-gradient(900px_600px_at_50%_85%,color-mix(in_srgb,var(--rb-orange),transparent_85%)_0%,transparent_65%)]",
        )}
        aria-label="Close"
        onClick={onClose}
      />
      <div
        role="dialog"
        aria-modal="true"
        className={cn(
          panelClassName,
          className,
        )}
      >
        {showInnerGlow ? (
          <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(650px_420px_at_20%_15%,color-mix(in_srgb,#16a34a,transparent_88%)_0%,transparent_60%),radial-gradient(650px_420px_at_80%_5%,color-mix(in_srgb,var(--rb-blue),transparent_90%)_0%,transparent_60%)]" />
        ) : null}
        {title ? (
          <div
            className={cn(
              "relative px-5 py-4",
              variant === "solid"
                ? "border-b border-[var(--rb-border)]"
                : "border-b border-[color:color-mix(in_srgb,var(--rb-border),white_60%)]",
            )}
          >
            <div className="text-sm font-semibold text-[var(--rb-text)]">{title}</div>
          </div>
        ) : null}

        <div className="relative px-5 py-4">{children}</div>

        {footer ? (
          <div
            className={cn(
              "relative px-5 py-4",
              variant === "solid"
                ? "border-t border-[var(--rb-border)]"
                : "border-t border-[color:color-mix(in_srgb,var(--rb-border),white_60%)]",
            )}
          >
            {footer}
          </div>
        ) : null}
      </div>
    </div>
  );
}
