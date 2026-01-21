"use client";

import React, { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { createPortal } from "react-dom";
import { cn } from "@/lib/cn";

export function DropdownMenu({
  trigger,
  children,
  align = "right",
  className,
}: {
  trigger: (args: { open: boolean; toggle: () => void }) => React.ReactNode;
  children: (args: { close: () => void }) => React.ReactNode;
  align?: "left" | "right";
  className?: string;
}) {
  const [open, setOpen] = useState(false);
  const triggerRef = useRef<HTMLSpanElement | null>(null);
  const menuRef = useRef<HTMLDivElement | null>(null);
  const [pos, setPos] = useState<{ top: number; left: number } | null>(null);

  const menuStyle = useMemo(() => {
    if (!pos) return undefined;
    return {
      position: "fixed" as const,
      top: pos.top,
      left: pos.left,
    };
  }, [pos]);

  const updatePosition = useCallback(() => {
    const el = triggerRef.current;
    if (!el) return;
    const rect = el.getBoundingClientRect();
    const top = rect.bottom + 8;
    const left = align === "right" ? rect.right : rect.left;
    setPos({ top, left });
  }, [align]);

  useEffect(() => {
    if (!open) return;
    updatePosition();
  }, [open, updatePosition]);

  useEffect(() => {
    function onDocMouseDown(e: MouseEvent) {
      if (!open) return;
      if (!(e.target instanceof Node)) return;

      const triggerNode = triggerRef.current;
      const menuNode = menuRef.current;

      if (triggerNode && triggerNode.contains(e.target)) return;
      if (menuNode && menuNode.contains(e.target)) return;

      setOpen(false);
    }

    function onDocKeyDown(e: KeyboardEvent) {
      if (!open) return;
      if (e.key === "Escape") {
        setOpen(false);
      }
    }

    function onWindowScrollOrResize() {
      if (!open) return;
      updatePosition();
    }

    document.addEventListener("mousedown", onDocMouseDown);
    document.addEventListener("keydown", onDocKeyDown);
    window.addEventListener("scroll", onWindowScrollOrResize, true);
    window.addEventListener("resize", onWindowScrollOrResize);

    return () => {
      document.removeEventListener("mousedown", onDocMouseDown);
      document.removeEventListener("keydown", onDocKeyDown);
      window.removeEventListener("scroll", onWindowScrollOrResize, true);
      window.removeEventListener("resize", onWindowScrollOrResize);
    };
  }, [open, updatePosition]);

  function close() {
    setOpen(false);
  }

  function toggle() {
    setOpen((v) => !v);
  }

  return (
    <div className={cn("inline-block", className)}>
      <span ref={triggerRef} className="inline-block">
        {trigger({ open, toggle })}
      </span>

      {open && typeof document !== "undefined"
        ? createPortal(
            <div
              ref={menuRef}
              role="menu"
              style={menuStyle}
              className={cn(
                "z-50 w-56 overflow-hidden rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)] bg-white shadow-[var(--rb-shadow)]",
                align === "right" ? "-translate-x-full" : "translate-x-0",
              )}
            >
              <div className="py-1">{children({ close })}</div>
            </div>,
            document.body,
          )
        : null}
    </div>
  );
}

export function DropdownMenuItem({
  children,
  onSelect,
  destructive = false,
  disabled = false,
}: {
  children: React.ReactNode;
  onSelect: () => void;
  destructive?: boolean;
  disabled?: boolean;
}) {
  return (
    <button
      role="menuitem"
      type="button"
      className={cn(
        "block w-full px-3 py-2 text-left text-sm",
        destructive ? "text-red-700" : "text-zinc-700",
        disabled ? "opacity-50" : "hover:bg-[var(--rb-surface-muted)]",
      )}
      onClick={(e) => {
        e.stopPropagation();
        onSelect();
      }}
      disabled={disabled}
    >
      {children}
    </button>
  );
}

export function DropdownMenuSeparator() {
  return <div className="my-1 border-t border-[var(--rb-border)]" />;
}
