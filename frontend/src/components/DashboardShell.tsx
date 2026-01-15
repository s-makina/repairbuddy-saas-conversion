"use client";

import Image from "next/image";
import Link from "next/link";
import React from "react";
import { usePathname } from "next/navigation";
import { useAuth } from "@/lib/auth";
import { cn } from "@/lib/cn";
import { Avatar } from "@/components/ui/Avatar";
import { Card } from "@/components/ui/Card";
import { UserMenu } from "@/components/ui/UserMenu";

export function DashboardShell({
  title,
  children,
}: {
  title: string;
  children: React.ReactNode;
}) {
  void title;
  const auth = useAuth();
  const pathname = usePathname();

  const tenantSlug = auth.tenant?.slug ?? null;

  const userName = auth.user?.name ?? "";
  const userEmail = auth.user?.email ?? "";
  const userAvatarUrl = auth.user?.avatar_url ?? null;

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

  const pageLabel = (() => {
    if (pathname === "/admin") return "Tenants";

    if (pathname.startsWith("/app/")) {
      const parts = pathname.split("/").filter(Boolean);
      if (parts.length === 2) {
        return "Dashboard";
      }

      if (parts.length >= 3) {
        return parts[2]
          .split("-")
          .filter(Boolean)
          .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
          .join(" ");
      }
      return "App";
    }

    if (pathname === "/") return "Home";
    if (pathname === "/login") return "Login";
    if (pathname === "/register") return "Register";
    if (pathname === "/verify-email") return "Verify Email";

    const last = pathname.split("/").filter(Boolean).slice(-1)[0] ?? "";
    return last
      .split("-")
      .filter(Boolean)
      .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
      .join(" ") || "Page";
  })();

  const breadcrumbText = (() => {
    if (pathname.startsWith("/app/")) return `App / ${pageLabel}`;
    if (pathname.startsWith("/admin")) return `Admin / ${pageLabel}`;
    return pageLabel;
  })();

  const profileHref = (() => {
    if (auth.isAdmin) return "/admin/profile";
    if (tenantSlug) return `/app/${tenantSlug}/profile`;
    return "/profile";
  })();

  const settingsHref = (() => {
    if (auth.isAdmin) return "/admin/settings";
    if (tenantSlug) return `/app/${tenantSlug}/settings`;
    return "/settings";
  })();

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
          </div>

          <nav className="flex flex-1 flex-col gap-1 overflow-y-auto px-2 py-3">
            {navItems
              .filter((x) => x.show)
              .map((item) => {
                const isActive =
                  item.href === "/"
                    ? pathname === "/"
                    : pathname === item.href || pathname.startsWith(`${item.href}/`);

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
              <Avatar
                src={userAvatarUrl}
                alt={userName || "User"}
                fallback={userInitials || "U"}
                size={36}
                className="bg-white/15"
              />
              <div className="min-w-0 flex-1">
                <div className="truncate text-sm font-semibold text-white">{userName || "User"}</div>
                <div className="truncate text-xs text-white/70">{userEmail || ""}</div>
              </div>
            </div>
          </div>
        </aside>

        <div className="flex min-w-0 flex-1 flex-col">
          <div className="mx-auto w-full max-w-6xl px-4">
            <Card className="mt-6 px-5 py-4">
              <div className="flex items-center justify-between">
                <div>
                  <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">
                    {breadcrumbText}
                  </div>
                  <div className="mt-1 text-lg font-semibold text-[var(--rb-text)]">{pageLabel}</div>
                </div>

                <UserMenu
                  userName={userName}
                  userEmail={userEmail}
                  avatarUrl={userAvatarUrl}
                  profileHref={profileHref}
                  settingsHref={settingsHref}
                  onLogout={() => void auth.logout()}
                />
              </div>
            </Card>
          </div>

          <main className="mx-auto mt-6 w-full max-w-6xl px-4 pb-8">{children}</main>
        </div>
      </div>
    </div>
  );
}
