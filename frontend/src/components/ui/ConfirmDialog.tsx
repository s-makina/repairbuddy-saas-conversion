"use client";

import React from "react";
import { Modal } from "@/components/ui/Modal";
import { Button } from "@/components/ui/Button";

export function ConfirmDialog({
  open,
  title,
  message,
  confirmText = "Confirm",
  confirmVariant = "primary",
  cancelText = "Cancel",
  busy = false,
  onCancel,
  onConfirm,
}: {
  open: boolean;
  title: string;
  message: React.ReactNode;
  confirmText?: string;
  confirmVariant?: "primary" | "secondary" | "outline" | "ghost";
  cancelText?: string;
  busy?: boolean;
  onCancel: () => void;
  onConfirm: () => void;
}) {
  return (
    <Modal
      open={open}
      onClose={() => {
        if (!busy) onCancel();
      }}
      title={title}
      footer={
        <div className="flex items-center justify-end gap-2">
          <Button variant="outline" onClick={onCancel} disabled={busy}>
            {cancelText}
          </Button>
          <Button variant={confirmVariant} onClick={onConfirm} disabled={busy}>
            {busy ? "Working..." : confirmText}
          </Button>
        </div>
      }
    >
      <div className="text-sm text-zinc-700">{message}</div>
    </Modal>
  );
}
