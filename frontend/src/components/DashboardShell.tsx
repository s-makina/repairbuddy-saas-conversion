"use client";

import Image from "next/image";
import Link from "next/link";
import React from "react";
import { usePathname } from "next/navigation";
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
  const pathname = usePathname();

  const tenantSlug = auth.tenant?.slug ?? null;

  const userName = auth.user?.name ?? "";
  const userEmail = auth.user?.email ?? "";
  const userInitials = userName
    .split(" ")
    .filter(Boolean)
    .slice(0, 2)
    .map((p) => p[0]?.toUpperCase())
    .join("")
    .slice(0, 2);

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
        <aside className="sticky top-0 flex h-screen w-[220px] shrink-0 flex-col bg-[var(--rb-blue)] text-white">
          <div className="border-b border-white/10 bg-white px-4 py-5 text-[var(--rb-text)]">
            <div className="flex items-center gap-3">
              <Image
                alt="RepairBuddy"
                src="/brand/repair-buddy-logo.png"
                width={140}
                height={36}
                priority
              />
            </div>
            <div className="mt-2 text-xs text-zinc-600">{title}</div>
          </div>

          <nav className="flex flex-1 flex-col gap-1 overflow-y-auto px-2 py-3">
            {navItems
              .filter((x) => x.show)
              .map((item) => {
                const isActive = pathname === item.href || pathname.startsWith(`${item.href}/`);

                return (
                  <Link
                    key={item.href}
                    href={item.href}
                    className={cn(
                      "rounded-[var(--rb-radius-sm)] px-3 py-2 text-sm font-medium",
                      isActive
                        ? "bg-[var(--rb-orange)] text-white"
                        : "text-white/90 hover:bg-[var(--rb-orange)] hover:text-white",
                    )}
                  >
                    {item.label}
                  </Link>
                );
              })}
          </nav>

          <div className="border-t border-white/10 p-3">
            <div className="flex items-center gap-3 rounded-[var(--rb-radius-md)] bg-white/5 px-3 py-3">
              <div className="flex h-9 w-9 items-center justify-center rounded-full bg-white/15 text-sm font-semibold">
                {userInitials || "U"}
              </div>
              <div className="min-w-0 flex-1">
                <div className="truncate text-sm font-semibold text-white">
                  {userName || "User"}
                </div>
                <div className="truncate text-xs text-white/70">{userEmail || ""}</div>
              </div>
            </div>

            <div className="mt-3">
              <Button
                className="w-full justify-center border-white/20 bg-transparent text-white hover:bg-white/10"
                variant="outline"
                onClick={() => void auth.logout()}
                type="button"
              >
                Logout
              </Button>
            </div>
          </div>
        </aside>

        <div className="flex min-w-0 flex-1 flex-col">
          <main className="mx-auto w-full max-w-6xl px-4 py-8">{children}</main>
        </div>
      </div>
    </div>
  );
}
