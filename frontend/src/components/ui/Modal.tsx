"use client";

import React, { useEffect } from "react";
import { cn } from "@/lib/cn";

export function Modal({
  open,
  onClose,
  title,
  children,
  footer,
  className,
}: {
  open: boolean;
  onClose: () => void;
  title?: React.ReactNode;
  children: React.ReactNode;
  footer?: React.ReactNode;
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

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center px-4">
      <button
        type="button"
        className="absolute inset-0 bg-black/40"
        aria-label="Close"
        onClick={onClose}
      />
      <div
        role="dialog"
        aria-modal="true"
        className={cn(
          "relative w-full max-w-lg rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)] bg-white shadow-[var(--rb-shadow)]",
          className,
        )}
      >
        {title ? (
          <div className="border-b border-[var(--rb-border)] px-5 py-4">
            <div className="text-sm font-semibold text-[var(--rb-text)]">{title}</div>
          </div>
        ) : null}

        <div className="px-5 py-4">{children}</div>

        {footer ? <div className="border-t border-[var(--rb-border)] px-5 py-4">{footer}</div> : null}
      </div>
    </div>
  );
}
