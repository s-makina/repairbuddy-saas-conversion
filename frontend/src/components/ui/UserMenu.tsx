"use client";

import Link from "next/link";
import React, { useEffect, useMemo, useRef, useState } from "react";
import { usePathname, useRouter } from "next/navigation";
import { cn } from "@/lib/cn";
import { Avatar } from "@/components/ui/Avatar";

export function UserMenu({
  userName,
  userEmail,
  avatarUrl,
  profileHref,
  settingsHref,
  securityHref,
  onLogout,
  className,
}: {
  userName: string;
  userEmail: string;
  avatarUrl?: string | null;
  profileHref: string;
  settingsHref: string;
  securityHref?: string | null;
  onLogout: () => void | Promise<void>;
  className?: string;
}) {
  const [open, setOpen] = useState(false);
  const [loggingOut, setLoggingOut] = useState(false);
  const rootRef = useRef<HTMLDivElement | null>(null);
  const router = useRouter();
  const pathname = usePathname();

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

  useEffect(() => {
    if (!open) return;
    setOpen(false);
  }, [pathname]);

  const canShowSecurity = Boolean(securityHref);

  async function handleLogout() {
    if (loggingOut) return;
    setOpen(false);
    setLoggingOut(true);
    try {
      await onLogout();
    } finally {
      router.replace("/login");
      setLoggingOut(false);
    }
  }

  return (
    <div ref={rootRef} className={cn("relative z-50", className)}>
      <button
        type="button"
        onClick={() => setOpen((v) => !v)}
        disabled={loggingOut}
        className={cn(
          "group inline-flex items-center gap-2 rounded-full border px-3 py-1.5",
          "bg-[var(--rb-surface-muted)] text-zinc-600 border-[var(--rb-border)]",
          "hover:bg-white hover:text-[var(--rb-text)]",
          "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[rgba(6,62,112,0.20)] focus-visible:ring-offset-2 focus-visible:ring-offset-white",
        )}
        aria-haspopup="menu"
        aria-expanded={open}
        aria-label="Account menu"
      >
        <Avatar
          src={avatarUrl}
          alt={userName || "User"}
          fallback={initials}
          size={32}
          className="bg-[var(--rb-blue)] text-white"
        />
        <span className="hidden max-w-[160px] truncate text-sm font-semibold text-current sm:inline">
          {userName || "User"}
        </span>
      </button>

      {open ? (
        <div
          role="menu"
          className="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)] bg-white shadow-[var(--rb-shadow)]"
        >
          <div className="border-b border-[var(--rb-border)] px-3 py-2">
            <div className="truncate text-sm font-semibold text-[var(--rb-text)]">{userName || "User"}</div>
            <div className="truncate text-xs text-zinc-600">{userEmail || ""}</div>
          </div>

          <div className="py-1">
            {canShowSecurity ? (
              <Link
                role="menuitem"
                className="block px-3 py-2 text-sm text-zinc-700 hover:bg-[var(--rb-surface-muted)]"
                href={securityHref as string}
                onClick={() => setOpen(false)}
              >
                Security
              </Link>
            ) : null}
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
            <div className="my-1 h-px bg-[var(--rb-border)]" />
            <button
              role="menuitem"
              type="button"
              disabled={loggingOut}
              className={cn(
                "block w-full px-3 py-2 text-left text-sm",
                loggingOut ? "text-zinc-400" : "text-zinc-700 hover:bg-[var(--rb-surface-muted)]",
              )}
              onClick={() => {
                void handleLogout();
              }}
            >
              {loggingOut ? "Logging out…" : "Logout"}
            </button>
          </div>
        </div>
      ) : null}
    </div>
  );
}
