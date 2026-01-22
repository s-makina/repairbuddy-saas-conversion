"use client";

import React from "react";
import { Modal } from "@/components/ui/Modal";
import { Button } from "@/components/ui/Button";
import { cn } from "@/lib/cn";

export type ResultDialogStatus = "info" | "success" | "warning" | "error";

function ResultIcon({ status, className }: { status: ResultDialogStatus; className?: string }) {
  const common = "h-5 w-5 shrink-0";

  if (status === "success") {
    return (
      <svg viewBox="0 0 24 24" className={cn(common, className)} fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
        <path d="M20 6L9 17l-5-5" />
      </svg>
    );
  }

  if (status === "warning") {
    return (
      <svg viewBox="0 0 24 24" className={cn(common, className)} fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
        <path d="M12 9v4" />
        <path d="M12 17h.01" />
        <path d="M10.3 3.6l-8.3 14.3A2 2 0 0 0 3.7 21h16.6a2 2 0 0 0 1.7-3.1L13.7 3.6a2 2 0 0 0-3.4 0z" />
      </svg>
    );
  }

  if (status === "error") {
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

export function ResultDialog({
  open,
  status,
  title,
  message,
  actionText = "OK",
  onClose,
}: {
  open: boolean;
  status: ResultDialogStatus;
  title: string;
  message?: React.ReactNode;
  actionText?: string;
  onClose: () => void;
}) {
  const variants: Record<ResultDialogStatus, { icon: string; title: string }> = {
    info: { icon: "text-[var(--rb-blue)]", title: "text-[var(--rb-blue)]" },
    success: { icon: "text-[#166534]", title: "text-[#166534]" },
    warning: { icon: "text-[color:color-mix(in_srgb,var(--rb-orange),black_20%)]", title: "text-[color:color-mix(in_srgb,var(--rb-orange),black_20%)]" },
    error: { icon: "text-[#991b1b]", title: "text-[#991b1b]" },
  };

  const v = variants[status];

  return (
    <Modal
      open={open}
      onClose={onClose}
      position="top-center"
      variant="solid"
      title={
        <div className="flex items-center gap-2">
          <ResultIcon status={status} className={v.icon} />
          <div className={cn("text-sm font-semibold", v.title)}>{title}</div>
        </div>
      }
      footer={
        <div className="flex items-center justify-end">
          <Button variant="outline" onClick={onClose}>
            {actionText}
          </Button>
        </div>
      }
    >
      {message ? <div className="text-sm text-zinc-700">{message}</div> : null}
    </Modal>
  );
}
