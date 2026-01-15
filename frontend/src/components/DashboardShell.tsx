"use client";

import Link from "next/link";
import React from "react";
import { useAuth } from "@/lib/auth";
import { cn } from "@/lib/cn";
import { Button } from "@/components/ui/Button";

export function DashboardShell({
  title,
  children,
}: {
  title: string;
  children: React.ReactNode;
}) {
  const auth = useAuth();

  const tenantSlug = auth.tenant?.slug ?? null;

  const navItems: Array<{ label: string; href: string; show?: boolean }> = [
    { label: "Home", href: "/", show: true },
    { label: "Admin", href: "/admin", show: auth.isAdmin },
    { label: "App", href: tenantSlug ? `/app/${tenantSlug}` : "/app", show: Boolean(tenantSlug) },
    {
      label: "Security",
      href: tenantSlug ? `/app/${tenantSlug}/security` : "/app",
      show: !auth.isAdmin && Boolean(tenantSlug),
    },
  ];

  return (
    <div className="min-h-screen bg-[var(--rb-surface-muted)] text-[var(--rb-text)]">
      <div className="flex min-h-screen">
        <aside className="hidden w-[200px] shrink-0 bg-[var(--rb-blue)] text-white md:flex md:flex-col">
          <div className="border-b border-white/10 bg-white p-4 text-[var(--rb-text)]">
            <div className="text-sm font-semibold">RepairBuddy</div>
            <div className="mt-0.5 text-xs text-zinc-600">{title}</div>
          </div>

          <nav className="flex flex-1 flex-col px-2 py-3">
            {navItems
              .filter((x) => x.show)
              .map((item) => (
                <Link
                  key={item.href}
                  href={item.href}
                  className={cn(
                    "rounded-[var(--rb-radius-sm)] px-3 py-2 text-sm font-medium text-white/90 hover:bg-[var(--rb-orange)] hover:text-white",
                  )}
                >
                  {item.label}
                </Link>
              ))}

            <div className="mt-auto px-1 pt-3">
              <Button
                className="w-full justify-center"
                variant="outline"
                onClick={() => void auth.logout()}
                type="button"
              >
                Logout
              </Button>
            </div>
          </nav>
        </aside>

        <div className="flex min-w-0 flex-1 flex-col">
          <header className="sticky top-0 z-10 border-b border-[var(--rb-border)] bg-white">
            <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
              <div className="flex flex-col">
                <div className="text-sm font-semibold">RepairBuddy</div>
                <div className="text-xs text-zinc-500">{title}</div>
              </div>

              <nav className="flex items-center gap-2 text-sm">
                {navItems
                  .filter((x) => x.show)
                  .map((item) => (
                    <Link
                      key={item.href}
                      className="rounded-[var(--rb-radius-sm)] px-3 py-2 text-zinc-600 hover:bg-[var(--rb-surface-muted)] hover:text-zinc-900"
                      href={item.href}
                    >
                      {item.label}
                    </Link>
                  ))}
                <Button variant="outline" onClick={() => void auth.logout()} type="button">
                  Logout
                </Button>
              </nav>
            </div>
          </header>

          <main className="mx-auto w-full max-w-6xl px-4 py-8">{children}</main>
        </div>
      </div>
    </div>
  );
}
