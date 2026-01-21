"use client";

import Image from "next/image";
import Link from "next/link";
import React from "react";
import { usePathname } from "next/navigation";
import { useAuth } from "@/lib/auth";
import { cn } from "@/lib/cn";
import { apiFetch } from "@/lib/api";
import { Avatar } from "@/components/ui/Avatar";
import { Card } from "@/components/ui/Card";
import { Button } from "@/components/ui/Button";
import { UserMenu } from "@/components/ui/UserMenu";

type DashboardHeaderConfig = {
  title?: React.ReactNode;
  subtitle?: React.ReactNode;
  breadcrumb?: React.ReactNode;
  actions?: React.ReactNode;
};

type DashboardHeaderContextValue = {
  setHeader: (next: DashboardHeaderConfig | null) => void;
};

const DashboardHeaderContext = React.createContext<DashboardHeaderContextValue | null>(null);

export function useDashboardHeader() {
  const ctx = React.useContext(DashboardHeaderContext);
  if (!ctx) {
    throw new Error("useDashboardHeader must be used within <DashboardShell />");
  }
  return ctx;
}

function MenuIcon({
  name,
  className,
}: {
  name:
    | "home"
    | "admin"
    | "dashboard"
    | "calendar"
    | "wrench"
    | "file"
    | "services"
    | "devices"
    | "tags"
    | "parts"
    | "payments"
    | "reports"
    | "calculator"
    | "users"
    | "review"
    | "clock"
    | "printer"
    | "shield"
    | "profile"
    | "settings"
    | "default";
  className?: string;
}) {
  const common = "h-4 w-4 shrink-0";
  const base = "fill-none stroke-current";
  const commonProps = {
    viewBox: "0 0 24 24",
    className: cn(common, className),
    strokeWidth: 1.8,
    strokeLinecap: "round" as const,
    strokeLinejoin: "round" as const,
    "aria-hidden": true,
  };

  switch (name) {
    case "home":
      return (
        <svg {...commonProps} className={cn(common, base, className)}>
          <path d="M3 11l9-8 9 8" />
          <path d="M5 10v10a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V10" />
        </svg>
      );
    case "admin":
      return (
        <svg {...commonProps} className={cn(common, base, className)}>
          <path d="M12 3l8 4v6c0 5-3.4 9.2-8 10-4.6-.8-8-5-8-10V7l8-4z" />
          <path d="M12 7v6" />
          <path d="M12 17h.01" />
        </svg>
      );
    case "dashboard":
      return (
        <svg {...commonProps} className={cn(common, base, className)}>
          <path d="M4 13a8 8 0 1 1 16 0" />
          <path d="M12 13l3-3" />
          <path d="M7 21h10" />
        </svg>
      );
    case "calendar":
      return (
        <svg {...commonProps} className={cn(common, base, className)}>
          <path d="M8 3v3" />
          <path d="M16 3v3" />
          <path d="M4 7h16" />
          <path d="M5 7v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7" />
          <path d="M8 11h4" />
        </svg>
      );
    case "wrench":
      return (
        <svg {...commonProps} className={cn(common, base, className)}>
          <path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L4 17v3h3l5.3-5.3a4 4 0 0 0 5.4-5.4l-2.2 2.2-2.8-2.8 2-2.4z" />
        </svg>
      );
    case "file":
      return (
        <svg {...commonProps} className={cn(common, base, className)}>
          <path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z" />
          <path d="M14 2v6h6" />
        </svg>
      );
    case "services":
      return (
        <svg {...commonProps} className={cn(common, base, className)}>
          <path d="M12 2l1.6 3.8L17 7.4l-3.4 1.6L12 13l-1.6-4L7 7.4l3.4-1.6L12 2z" />
          <path d="M19 13l.9 2.1L22 16l-2.1.9L19 19l-.9-2.1L16 16l2.1-.9L19 13z" />
        </svg>
      );
    case "devices":
      return (
        <svg {...commonProps} className={cn(common, base, className)}>
          <path d="M8 4h8a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z" />
          <path d="M10 21h4" />
          <path d="M12 17h.01" />
        </svg>
      );
    case "tags":
      return (
        <svg {...commonProps} className={cn(common, base, className)}>
          <path d="M20 12l-8 8-10-10V2h8l10 10z" />
          <path d="M7 7h.01" />
        </svg>
      );
    case "parts":
      return (
        <svg {...commonProps} className={cn(common, base, className)}>
          <path d="M12 3a4 4 0 0 1 4 4v2h2a4 4 0 0 1 0 8h-2v2a4 4 0 0 1-8 0v-2H6a4 4 0 0 1 0-8h2V7a4 4 0 0 1 4-4z" />
        </svg>
      );
    case "payments":
      return (
        <svg {...commonProps} className={cn(common, base, className)}>
          <path d="M4 7h16a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2z" />
          <path d="M2 11h20" />
          <path d="M6 15h4" />
        </svg>
      );
    case "reports":
      return (
        <svg {...commonProps} className={cn(common, base, className)}>
          <path d="M4 19V5" />
          <path d="M4 19h16" />
          <path d="M8 16v-6" />
          <path d="M12 16V8" />
          <path d="M16 16v-3" />
        </svg>
      );
    case "calculator":
      return (
        <svg {...commonProps} className={cn(common, base, className)}>
          <path d="M7 3h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z" />
          <path d="M8 7h8" />
          <path d="M8 11h2" />
          <path d="M12 11h2" />
          <path d="M16 11h0" />
          <path d="M8 15h2" />
          <path d="M12 15h2" />
          <path d="M16 15h0" />
        </svg>
      );
    case "users":
      return (
        <svg {...commonProps} className={cn(common, base, className)}>
          <path d="M16 11a3 3 0 1 0-6 0" />
          <path d="M19 21v-1a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v1" />
          <path d="M12 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" />
        </svg>
      );
    case "review":
      return (
        <svg {...commonProps} className={cn(common, base, className)}>
          <path d="M12 3l2.8 5.7L21 10l-4.5 4.4L17.6 21 12 18l-5.6 3 1.1-6.6L3 10l6.2-1.3L12 3z" />
        </svg>
      );
    case "clock":
      return (
        <svg {...commonProps} className={cn(common, base, className)}>
          <path d="M12 22a10 10 0 1 0-10-10 10 10 0 0 0 10 10z" />
          <path d="M12 6v6l4 2" />
        </svg>
      );
    case "printer":
      return (
        <svg {...commonProps} className={cn(common, base, className)}>
          <path d="M7 9V3h10v6" />
          <path d="M6 17H4a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-2" />
          <path d="M7 14h10v7H7z" />
        </svg>
      );
    case "shield":
      return (
        <svg {...commonProps} className={cn(common, base, className)}>
          <path d="M12 3l8 4v6c0 5-3.4 9.2-8 10-4.6-.8-8-5-8-10V7l8-4z" />
          <path d="M9.5 12l1.8 1.8L14.8 10" />
        </svg>
      );
    case "profile":
      return (
        <svg {...commonProps} className={cn(common, base, className)}>
          <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4z" />
          <path d="M4 21a8 8 0 0 1 16 0" />
        </svg>
      );
    case "settings":
      return (
        <svg {...commonProps} className={cn(common, base, className)}>
          <path d="M12 15.5a3.5 3.5 0 1 0-3.5-3.5 3.5 3.5 0 0 0 3.5 3.5z" />
          <path d="M19.4 15a7.8 7.8 0 0 0 .1-2l2-1.5-2-3.4-2.4 1a7.8 7.8 0 0 0-1.8-1l-.4-2.7H9l-.4 2.7c-.6.2-1.2.6-1.8 1l-2.4-1-2 3.4L4.5 13a7.8 7.8 0 0 0 0 2l-2 1.5 2 3.4 2.4-1c.6.4 1.2.7 1.8 1l.4 2.7h6.2l.4-2.7c.6-.2 1.2-.6 1.8-1l2.4 1 2-3.4-2-1.5z" />
        </svg>
      );
    default:
      return (
        <svg {...commonProps} className={cn(common, base, className)}>
          <path d="M12 22a10 10 0 1 0-10-10 10 10 0 0 0 10 10z" />
          <path d="M12 8h.01" />
          <path d="M12 12v4" />
        </svg>
      );
  }
}

function ChevronRightIcon({ className }: { className?: string }) {
  return (
    <svg
      viewBox="0 0 24 24"
      className={cn("h-4 w-4 shrink-0 fill-none stroke-current", className)}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
    >
      <path d="M9 18l6-6-6-6" />
    </svg>
  );
}

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

  const [header, setHeader] = React.useState<DashboardHeaderConfig | null>(null);

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

  const tenantBaseHref = tenantSlug ? `/app/${tenantSlug}` : null;
  const tenantPlaceholderHref = React.useCallback(
    (path: string) => (tenantBaseHref ? `${tenantBaseHref}/placeholder/${path}` : "/app"),
    [tenantBaseHref],
  );

  type NavItem = {
    label: string;
    href: string;
    icon: React.ComponentProps<typeof MenuIcon>["name"];
    show: boolean;
  };

  type NavSection = {
    title?: string;
    items: NavItem[];
  };

  const navSections = React.useMemo<NavSection[]>(
    () =>
      [
        {
          items: [
            { label: "Home", href: "/", icon: "home", show: true },
            { label: "Dashboard", href: "/admin", icon: "dashboard", show: auth.can("admin.access") },
            { label: "Tenants", href: "/admin/tenants", icon: "admin", show: auth.can("admin.tenants.read") },
            {
              label: auth.can("admin.access") ? "Tenant Dashboard" : "Dashboard",
              href: tenantBaseHref ?? "/app",
              icon: "dashboard",
              show: Boolean(tenantBaseHref) && auth.can("dashboard.view"),
            },
          ],
        },
        {
          title: "Billing",
          items: [
            { label: "Plans", href: "/admin/billing/plans", icon: "payments", show: auth.can("admin.billing.read") },
            { label: "Plan Builder", href: "/admin/billing/builder", icon: "file", show: auth.can("admin.billing.read") },
            { label: "Entitlements", href: "/admin/billing/entitlements", icon: "tags", show: auth.can("admin.billing.read") },
            {
              label: "Tenant Billing",
              href: "/admin/billing/tenants",
              icon: "users",
              show: auth.can("admin.billing.read") && auth.can("admin.tenants.read"),
            },
          ],
        },
        {
          title: "Operations",
          items: [
            { label: "Appointments", href: tenantPlaceholderHref("appointments"), icon: "calendar", show: Boolean(tenantBaseHref) && auth.can("appointments.view") },
            { label: "Jobs", href: tenantPlaceholderHref("jobs"), icon: "wrench", show: Boolean(tenantBaseHref) && auth.can("jobs.view") },
            { label: "Estimates", href: tenantPlaceholderHref("estimates"), icon: "file", show: Boolean(tenantBaseHref) && auth.can("estimates.view") },
            { label: "Services", href: tenantPlaceholderHref("services"), icon: "services", show: Boolean(tenantBaseHref) && auth.can("services.view") },
          ],
        },
        {
          title: "Inventory",
          items: [
            { label: "Devices", href: tenantPlaceholderHref("devices"), icon: "devices", show: Boolean(tenantBaseHref) && auth.can("devices.view") },
            { label: "Device Brands", href: tenantPlaceholderHref("device-brands"), icon: "tags", show: Boolean(tenantBaseHref) && auth.can("device_brands.view") },
            { label: "Device Types", href: tenantPlaceholderHref("device-types"), icon: "tags", show: Boolean(tenantBaseHref) && auth.can("device_types.view") },
            { label: "Parts", href: tenantPlaceholderHref("parts"), icon: "parts", show: Boolean(tenantBaseHref) && auth.can("parts.view") },
          ],
        },
        {
          title: "Finance",
          items: [
            { label: "Payments", href: tenantPlaceholderHref("payments"), icon: "payments", show: Boolean(tenantBaseHref) && auth.can("payments.view") },
            { label: "Reports", href: tenantPlaceholderHref("reports"), icon: "reports", show: Boolean(tenantBaseHref) && auth.can("reports.view") },
            { label: "Expenses", href: tenantPlaceholderHref("expenses"), icon: "calculator", show: Boolean(tenantBaseHref) && auth.can("expenses.view") },
            {
              label: "Expense Categories",
              href: tenantPlaceholderHref("expense-categories"),
              icon: "calculator",
              show: Boolean(tenantBaseHref) && auth.can("expense_categories.view"),
            },
          ],
        },
        {
          title: "People",
          items: [
            { label: "Clients", href: tenantPlaceholderHref("clients"), icon: "users", show: Boolean(tenantBaseHref) && auth.can("clients.view") },
            { label: "Customer Devices", href: tenantPlaceholderHref("customer-devices"), icon: "devices", show: Boolean(tenantBaseHref) && auth.can("customer_devices.view") },
            { label: "Technicians", href: tenantPlaceholderHref("technicians"), icon: "users", show: Boolean(tenantBaseHref) && auth.can("technicians.view") },
            { label: "Managers", href: tenantPlaceholderHref("managers"), icon: "users", show: Boolean(tenantBaseHref) && auth.can("managers.view") },
            { label: "Users", href: tenantSlug ? `/app/${tenantSlug}/users` : "/app", icon: "users", show: Boolean(tenantSlug) && auth.can("users.manage") },
            { label: "Roles", href: tenantSlug ? `/app/${tenantSlug}/roles` : "/app", icon: "shield", show: Boolean(tenantSlug) && auth.can("roles.manage") },
          ],
        },
        {
          title: "Quality",
          items: [
            { label: "Job Reviews", href: tenantPlaceholderHref("job-reviews"), icon: "review", show: Boolean(tenantBaseHref) && auth.can("job_reviews.view") },
          ],
        },
        {
          title: "Tools",
          items: [
            { label: "Time Logs", href: tenantPlaceholderHref("time-logs"), icon: "clock", show: Boolean(tenantBaseHref) && auth.can("time_logs.view") },
            { label: "Manage Hourly Rates", href: tenantPlaceholderHref("hourly-rates"), icon: "clock", show: Boolean(tenantBaseHref) && auth.can("hourly_rates.view") },
            { label: "Reminder Logs", href: tenantPlaceholderHref("reminder-logs"), icon: "clock", show: Boolean(tenantBaseHref) && auth.can("reminder_logs.view") },
            { label: "Print Screen", href: tenantPlaceholderHref("print-screen"), icon: "printer", show: Boolean(tenantBaseHref) && auth.can("print_screen.view") },
          ],
        },
        {
          title: "Account",
          items: [
            {
              label: "Security",
              href: tenantSlug ? `/app/${tenantSlug}/security` : "/app",
              icon: "shield",
              show: Boolean(tenantSlug) && auth.can("security.manage"),
            },
            {
              label: "Profile",
              href: auth.isAdmin ? "/admin/profile" : tenantSlug ? `/app/${tenantSlug}/profile` : "/app",
              icon: "profile",
              show: auth.isAuthenticated && auth.can("profile.manage"),
            },
            {
              label: "Settings",
              href: auth.isAdmin ? "/admin/settings" : tenantSlug ? `/app/${tenantSlug}/settings` : "/app",
              icon: "settings",
              show: auth.isAuthenticated && auth.can("settings.manage"),
            },
          ],
        },
      ],
    [auth, tenantBaseHref, tenantPlaceholderHref, tenantSlug],
  );

  const activeNavHref = React.useMemo(() => {
    const allItems = navSections.flatMap((s) => s.items).filter((x) => x.show);

    const matches = allItems
      .map((item) => {
        if (item.href === "/") {
          return pathname === "/" ? { href: item.href, score: 1 } : { href: item.href, score: -1 };
        }

        const isMatch = pathname === item.href || pathname.startsWith(`${item.href}/`);
        return isMatch ? { href: item.href, score: item.href.length } : { href: item.href, score: -1 };
      })
      .filter((m) => m.score >= 0)
      .sort((a, b) => b.score - a.score);

    return matches[0]?.href ?? null;
  }, [navSections, pathname]);

  const resolvedNavSections = React.useMemo(() => {
    return navSections
      .map((section, idx) => {
        const key = section.title ? `section:${section.title}` : `section:${idx}`;
        const items = section.items.filter((x) => x.show);
        const hasActiveItem = !!activeNavHref && items.some((item) => item.href === activeNavHref);

        return {
          key,
          title: section.title,
          items,
          hasActiveItem,
        };
      })
      .filter((section) => section.items.length > 0);
  }, [activeNavHref, navSections]);

  const [openSections, setOpenSections] = React.useState<Record<string, boolean>>({});

  const sidebarStateKey = React.useMemo(() => {
    const scope = auth.user?.email || auth.user?.name || "anon";
    return `rb.sidebar.openSections:v1:${scope}`;
  }, [auth.user?.email, auth.user?.name]);

  React.useEffect(() => {
    if (typeof window === "undefined") return;
    try {
      const raw = window.localStorage.getItem(sidebarStateKey);
      if (!raw) return;
      const parsed = JSON.parse(raw);
      if (parsed && typeof parsed === "object") {
        setOpenSections(parsed as Record<string, boolean>);
      }
    } catch {
      // ignore
    }
  }, [sidebarStateKey]);

  React.useEffect(() => {
    setOpenSections((prev) => {
      const active = resolvedNavSections.find((s) => Boolean(s.title) && s.hasActiveItem);
      if (!active) return prev;

      const next: Record<string, boolean> = { ...prev };
      let changed = false;

      for (const s of resolvedNavSections) {
        if (!s.title) continue;
        const shouldBeOpen = s.key === active.key;
        if (next[s.key] !== shouldBeOpen) {
          next[s.key] = shouldBeOpen;
          changed = true;
        }
      }

      return changed ? next : prev;
    });
  }, [resolvedNavSections]);

  React.useEffect(() => {
    if (typeof window === "undefined") return;
    try {
      window.localStorage.setItem(sidebarStateKey, JSON.stringify(openSections));
    } catch {
      // ignore
    }
  }, [openSections, sidebarStateKey]);

  const pageLabel = (() => {
    if (pathname === "/admin") return "Dashboard";

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
    return "/app";
  })();

  const settingsHref = (() => {
    if (auth.isAdmin) return "/admin/settings";
    if (tenantSlug) return `/app/${tenantSlug}/settings`;
    return "/app";
  })();

  const securityHref = (() => {
    if (!tenantSlug) return null;
    if (!auth.can("security.manage")) return null;
    return `/app/${tenantSlug}/security`;
  })();

  const headerCtx = React.useMemo<DashboardHeaderContextValue>(() => {
    return { setHeader };
  }, []);

  return (
    <DashboardHeaderContext.Provider value={headerCtx}>
      <div className="min-h-screen bg-[var(--rb-surface-muted)] text-[var(--rb-text)]">
        <div className="flex min-h-screen">
        <aside className="sticky top-0 flex h-screen w-[280px] shrink-0 flex-col bg-[var(--rb-blue)] text-white">
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

          <nav className="flex flex-1 flex-col gap-3 overflow-y-auto px-2 py-3">
            {resolvedNavSections.map((section) => {
              if (!section.title) {
                return (
                  <div key={section.key} className="space-y-1">
                    {section.items.map((item) => {
                      const isActive = !!activeNavHref && item.href === activeNavHref;

                      return (
                        <Link
                          key={item.href}
                          href={item.href}
                          className={cn(
                            "block rounded-[var(--rb-radius-sm)] px-3 py-2 text-sm font-medium",
                            isActive
                              ? "bg-[var(--rb-orange)] text-white"
                              : "text-white/90 hover:bg-[var(--rb-orange)] hover:text-white",
                          )}
                        >
                          <span className="flex items-center gap-2">
                            <MenuIcon name={item.icon ?? "default"} className="text-white/80" />
                            <span className="min-w-0 truncate">{item.label}</span>
                          </span>
                        </Link>
                      );
                    })}
                  </div>
                );
              }

              const isOpen = openSections[section.key] ?? false;
              const contentId = `sidebar-${section.key.replace(/[^a-zA-Z0-9_-]/g, "-")}`;

              return (
                <div key={section.key} className="space-y-1">
                  <button
                    type="button"
                    onClick={() => {
                      setOpenSections((prev) => {
                        const willOpen = !(prev[section.key] ?? false);
                        const next: Record<string, boolean> = { ...prev };
                        for (const s of resolvedNavSections) {
                          if (!s.title) continue;
                          next[s.key] = false;
                        }
                        next[section.key] = willOpen;
                        return next;
                      });
                    }}
                    className={cn(
                      "flex w-full items-center justify-between rounded-[var(--rb-radius-sm)] px-3 py-2 text-left",
                      "text-[11px] font-semibold uppercase tracking-wider",
                      section.hasActiveItem ? "bg-white/10 text-white" : "text-white/70 hover:bg-white/10 hover:text-white",
                    )}
                    aria-expanded={isOpen}
                    aria-controls={contentId}
                  >
                    <span>{section.title}</span>
                    <ChevronRightIcon
                      className={cn(
                        "text-white/70 transition-transform motion-reduce:transition-none",
                        isOpen ? "rotate-90" : "rotate-0",
                      )}
                    />
                  </button>

                  <div
                    id={contentId}
                    aria-hidden={!isOpen}
                    className={cn(
                      "overflow-hidden pl-2",
                      "transition-[max-height,opacity] duration-200 ease-out motion-reduce:transition-none",
                      isOpen ? "max-h-[560px] opacity-100" : "max-h-0 opacity-0",
                    )}
                  >
                    <div className={cn("space-y-1", isOpen ? "pt-1" : "pt-0", !isOpen ? "pointer-events-none" : "")}
                    >
                      {section.items.map((item) => {
                        const isActive = !!activeNavHref && item.href === activeNavHref;

                        return (
                          <Link
                            key={item.href}
                            href={item.href}
                            className={cn(
                              "block rounded-[var(--rb-radius-sm)] px-3 py-2 text-sm font-medium",
                              isActive
                                ? "bg-[var(--rb-orange)] text-white"
                                : "text-white/90 hover:bg-[var(--rb-orange)] hover:text-white",
                            )}
                          >
                            <span className="flex items-center gap-2">
                              <MenuIcon name={item.icon ?? "default"} className="text-white/80" />
                              <span className="min-w-0 truncate">{item.label}</span>
                            </span>
                          </Link>
                        );
                      })}
                    </div>
                  </div>
                </div>
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
            {auth.isImpersonating ? (
              <Card className="mt-6 border border-amber-200 bg-amber-50 px-5 py-3">
                <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                  <div className="text-sm text-amber-900">
                    <span className="font-semibold">Impersonation active.</span>{" "}
                    <span>
                      Acting as {auth.user?.email}
                      {auth.actorUser?.email ? ` (by ${auth.actorUser.email})` : ""}.
                    </span>
                  </div>
                  <div className="flex items-center gap-2">
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={async () => {
                        const sessionId = auth.impersonation?.session_id;
                        try {
                          if (sessionId) {
                            await apiFetch(`/api/admin/impersonation/${sessionId}/stop`, {
                              method: "POST",
                              impersonationSessionId: null,
                              body: {
                                reason: "ended_by_user",
                              },
                            });
                          }
                        } finally {
                          auth.clearImpersonation();
                          await auth.refresh();
                        }
                      }}
                    >
                      Exit impersonation
                    </Button>
                  </div>
                </div>
              </Card>
            ) : null}

              <Card className="mt-6 px-5 py-4">
                <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                  <div className="min-w-0">
                    <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">
                      {header?.breadcrumb ?? breadcrumbText}
                    </div>
                    <div className="mt-1 truncate text-lg font-semibold text-[var(--rb-text)]">
                      {header?.title ?? pageLabel}
                    </div>
                    {header?.subtitle ? <div className="mt-1 text-sm text-zinc-600">{header.subtitle}</div> : null}
                  </div>

                  <div className="flex items-center gap-2">
                    {header?.actions ? <div className="flex items-center gap-2">{header.actions}</div> : null}
                    <UserMenu
                      userName={userName}
                      userEmail={userEmail}
                      avatarUrl={userAvatarUrl}
                      profileHref={profileHref}
                      settingsHref={settingsHref}
                      securityHref={securityHref}
                      onLogout={auth.logout}
                    />
                  </div>
                </div>
              </Card>
            </div>

            <main className="mx-auto mt-6 w-full max-w-6xl px-4 pb-8">{children}</main>
          </div>
        </div>
      </div>
    </DashboardHeaderContext.Provider>
  );
}
