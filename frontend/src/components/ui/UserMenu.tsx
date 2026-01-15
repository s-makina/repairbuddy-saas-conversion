"use client";

import Link from "next/link";
import React, { useEffect, useMemo, useRef, useState } from "react";
import { cn } from "@/lib/cn";
import { Avatar } from "@/components/ui/Avatar";

export function UserMenu({
  userName,
  userEmail,
  avatarUrl,
  profileHref,
  settingsHref,
  onLogout,
  className,
}: {
  userName: string;
  userEmail: string;
  avatarUrl?: string | null;
  profileHref: string;
  settingsHref: string;
  onLogout: () => void;
  className?: string;
}) {
  const [open, setOpen] = useState(false);
  const rootRef = useRef<HTMLDivElement | null>(null);

  const initials = useMemo(() => {
    const val = userName
      .split(" ")
      .filter(Boolean)
      .slice(0, 2)
      .map((p) => p[0]?.toUpperCase())
      .join("")
      .slice(0, 2);

    return val || "U";
  }, [userName]);

  useEffect(() => {
    function onDocMouseDown(e: MouseEvent) {
      if (!open) return;
      const node = rootRef.current;
      if (!node) return;
      if (e.target instanceof Node && !node.contains(e.target)) {
        setOpen(false);
      }
    }

    function onDocKeyDown(e: KeyboardEvent) {
      if (!open) return;
      if (e.key === "Escape") {
        setOpen(false);
      }
    }

    document.addEventListener("mousedown", onDocMouseDown);
    document.addEventListener("keydown", onDocKeyDown);

    return () => {
      document.removeEventListener("mousedown", onDocMouseDown);
      document.removeEventListener("keydown", onDocKeyDown);
    };
  }, [open]);

  return (
    <div ref={rootRef} className={cn("relative", className)}>
      <button
        type="button"
        onClick={() => setOpen((v) => !v)}
        className="inline-flex items-center gap-2 rounded-[var(--rb-radius-sm)] px-2 py-1.5 hover:bg-[var(--rb-surface-muted)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--rb-orange)] focus-visible:ring-offset-2"
        aria-haspopup="menu"
        aria-expanded={open}
      >
        <Avatar src={avatarUrl} alt={userName || "User"} fallback={initials} size={32} className="bg-[var(--rb-blue)]" />
        <span className="hidden max-w-[160px] truncate text-sm font-medium text-[var(--rb-text)] sm:inline">
          {userName || "User"}
        </span>
      </button>

      {open ? (
        <div
          role="menu"
          className="absolute right-0 mt-2 w-56 overflow-hidden rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)] bg-white shadow-[var(--rb-shadow)]"
        >
          <div className="border-b border-[var(--rb-border)] px-3 py-2">
            <div className="truncate text-sm font-semibold text-[var(--rb-text)]">{userName || "User"}</div>
            <div className="truncate text-xs text-zinc-600">{userEmail || ""}</div>
          </div>

          <div className="py-1">
            <Link
              role="menuitem"
              className="block px-3 py-2 text-sm text-zinc-700 hover:bg-[var(--rb-surface-muted)]"
              href={profileHref}
              onClick={() => setOpen(false)}
            >
              Profile
            </Link>
            <Link
              role="menuitem"
              className="block px-3 py-2 text-sm text-zinc-700 hover:bg-[var(--rb-surface-muted)]"
              href={settingsHref}
              onClick={() => setOpen(false)}
            >
              Settings
            </Link>
            <button
              role="menuitem"
              type="button"
              className="block w-full px-3 py-2 text-left text-sm text-zinc-700 hover:bg-[var(--rb-surface-muted)]"
              onClick={() => {
                setOpen(false);
                onLogout();
              }}
            >
              Logout
            </button>
          </div>
        </div>
      ) : null}
    </div>
  );
}
