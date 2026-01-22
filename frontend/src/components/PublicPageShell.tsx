"use client";

import Link from "next/link";
import React from "react";
import { Badge } from "@/components/ui/Badge";

export function PublicPageShell({
  badge,
  actions,
  centerContent = false,
  children,
}: {
  badge?: React.ReactNode;
  actions?: React.ReactNode;
  centerContent?: boolean;
  children: React.ReactNode;
}) {
  return (
    <div className="flex min-h-screen flex-col text-[var(--rb-text)] [background:radial-gradient(1200px_circle_at_20%_0%,color-mix(in_srgb,var(--rb-blue),white_88%)_0%,transparent_55%),radial-gradient(900px_circle_at_80%_15%,color-mix(in_srgb,var(--rb-orange),white_86%)_0%,transparent_60%),var(--rb-surface)]">
      <header className="sticky top-0 z-20 border-b border-[var(--rb-border)] bg-white/70 backdrop-blur">
        <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
          <div className="flex items-center gap-3">
            <Link href="/" className="font-semibold tracking-tight text-[var(--rb-text)]">
              99smartx
            </Link>
            {badge ? (
              <Badge variant="info" className="hidden sm:inline-flex">
                {badge}
              </Badge>
            ) : null}
          </div>

          <nav className="hidden items-center gap-6 text-sm text-zinc-600 md:flex">
            <Link href="/#features" className="hover:text-[var(--rb-text)]">
              Features
            </Link>
            <Link href="/#pricing" className="hover:text-[var(--rb-text)]">
              Pricing
            </Link>
            <Link href="/#faq" className="hover:text-[var(--rb-text)]">
              FAQ
            </Link>
          </nav>

          <div className="flex items-center gap-2">{actions}</div>
        </div>
      </header>

      <main className={centerContent ? "flex flex-1 items-center" : "flex-1"}>
        {centerContent ? <div className="w-full py-10">{children}</div> : children}
      </main>

      <footer
        className={
          (centerContent ? "border-t border-[var(--rb-border)] pt-8" : "mt-12 border-t border-[var(--rb-border)] pt-8") +
          " text-xs text-zinc-600"
        }
      >
        <div className="mx-auto flex max-w-6xl flex-col gap-2 px-4 sm:flex-row sm:items-center sm:justify-between">
          <div>© {new Date().getFullYear()} 99smartx</div>
          <div className="flex items-center gap-4">
            <Link href="/login" className="hover:text-[var(--rb-text)]">
              Login
            </Link>
            <Link href="/register" className="hover:text-[var(--rb-text)]">
              Register
            </Link>
          </div>
        </div>
      </footer>
    </div>
  );
}
