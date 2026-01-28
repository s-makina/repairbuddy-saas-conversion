"use client";

import React from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { cn } from "@/lib/cn";

type PortalNavItem = {
  label: string;
  href: (tenantSlug: string) => string;
};

const portalNav: PortalNavItem[] = [
  { label: "Dashboard", href: (t) => `/t/${t}/portal` },
  { label: "Tickets/Jobs", href: (t) => `/t/${t}/portal/tickets` },
  { label: "Estimates", href: (t) => `/t/${t}/portal/estimates` },
  { label: "Reviews", href: (t) => `/t/${t}/portal/reviews` },
  { label: "My Devices", href: (t) => `/t/${t}/portal/devices` },
  { label: "Booking", href: (t) => `/t/${t}/portal/booking` },
  { label: "Profile", href: (t) => `/t/${t}/portal/profile` },
];

export function PortalShell({ tenantSlug, title, subtitle, actions, children }: { tenantSlug: string; title?: string; subtitle?: string; actions?: React.ReactNode; children: React.ReactNode }) {
  const pathname = usePathname();

  const activeHref = React.useMemo(() => {
    const all = portalNav.map((x) => x.href(tenantSlug));
    const matches = all
      .map((href) => {
        const isMatch = pathname === href || pathname.startsWith(`${href}/`);
        return isMatch ? { href, score: href.length } : { href, score: -1 };
      })
      .filter((m) => m.score >= 0)
      .sort((a, b) => b.score - a.score);
    return matches[0]?.href ?? null;
  }, [pathname, tenantSlug]);

  return (
    <div className="min-h-screen bg-[var(--rb-surface-muted)] text-[var(--rb-text)]">
      <header className="border-b border-[var(--rb-border)] bg-white">
        <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-4">
          <div className="min-w-0">
            <div className="flex items-center gap-3">
              <Link href={`/t/${tenantSlug}/status`} className="text-sm font-semibold text-[var(--rb-text)]">
                RepairBuddy
              </Link>
              <div className="text-xs text-zinc-500">Business: {tenantSlug}</div>
            </div>
            {title ? <div className="mt-1 truncate text-lg font-semibold text-[var(--rb-text)]">{title}</div> : null}
            {subtitle ? <div className="mt-1 text-sm text-zinc-600">{subtitle}</div> : null}
          </div>
          {actions ? <div className="flex items-center gap-2">{actions}</div> : null}
        </div>
      </header>

      <div className="mx-auto w-full max-w-6xl px-4 py-6">
        <div className="grid grid-cols-1 gap-6 lg:grid-cols-[260px_1fr]">
          <aside className="rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)] bg-white shadow-[var(--rb-shadow)]">
            <div className="border-b border-[var(--rb-border)] px-5 py-4">
              <div className="text-sm font-semibold text-[var(--rb-text)]">My Account</div>
            </div>
            <nav className="space-y-1 p-2">
              {portalNav.map((item) => {
                const href = item.href(tenantSlug);
                const isActive = !!activeHref && href === activeHref;
                return (
                  <Link
                    key={href}
                    href={href}
                    className={cn(
                      "block rounded-[var(--rb-radius-sm)] px-3 py-2 text-sm transition-colors",
                      isActive
                        ? "bg-[var(--rb-surface-muted)] font-medium text-[var(--rb-text)]"
                        : "text-zinc-700 hover:bg-[var(--rb-surface-muted)]",
                    )}
                  >
                    {item.label}
                  </Link>
                );
              })}
            </nav>
          </aside>

          <main className="min-w-0">{children}</main>
        </div>
      </div>
    </div>
  );
}
