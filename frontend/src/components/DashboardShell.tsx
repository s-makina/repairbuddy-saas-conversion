"use client";

import Image from "next/image";
import Link from "next/link";
import React from "react";
import { usePathname } from "next/navigation";
import { useAuth } from "@/lib/auth";
import { cn } from "@/lib/cn";
import { apiFetch } from "@/lib/api";
import type { Branch } from "@/lib/types";
import { Avatar } from "@/components/ui/Avatar";
import { Card } from "@/components/ui/Card";
import { Button } from "@/components/ui/Button";
import { DropdownMenu, DropdownMenuItem, DropdownMenuSeparator } from "@/components/ui/DropdownMenu";
import { UserMenu } from "@/components/ui/UserMenu";
import {
  BarChart3,
  Building2,
  Calculator,
  Calendar,
  Check,
  ChevronDown,
  ChevronRight,
  CircleHelp,
  Clock,
  CreditCard,
  FileText,
  Home,
  LayoutDashboard,
  Package,
  Printer,
  Settings,
  Sparkles,
  Star,
  Tag,
  User,
  Users,
  Wrench,
  Laptop,
  Shield,
} from "lucide-react";

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
    | "building"
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
  const iconClassName = cn(common, className);

  const icons: Record<
    React.ComponentProps<typeof MenuIcon>["name"],
    React.ComponentType<{ className?: string; "aria-hidden"?: boolean }>
  > = {
    home: Home,
    admin: Shield,
    dashboard: LayoutDashboard,
    building: Building2,
    calendar: Calendar,
    wrench: Wrench,
    file: FileText,
    services: Sparkles,
    devices: Laptop,
    tags: Tag,
    parts: Package,
    payments: CreditCard,
    reports: BarChart3,
    calculator: Calculator,
    users: Users,
    review: Star,
    clock: Clock,
    printer: Printer,
    shield: Shield,
    profile: User,
    settings: Settings,
    default: CircleHelp,
  };

  const Icon = icons[name] ?? CircleHelp;

  return <Icon className={iconClassName} aria-hidden />;
}

function ChevronRightIcon({ className }: { className?: string }) {
  return <ChevronRight className={cn("h-4 w-4 shrink-0", className)} aria-hidden />;
}

function ChevronDownIcon({ className }: { className?: string }) {
  return <ChevronDown className={cn("h-4 w-4 shrink-0", className)} aria-hidden />;
}

function CheckIcon({ className }: { className?: string }) {
  return <Check className={cn("h-4 w-4 shrink-0", className)} aria-hidden />;
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

  const showSetupBanner = Boolean(!auth.isAdmin && tenantSlug && auth.tenant && !auth.tenant.setup_completed_at);
  const setupHref = tenantSlug ? `/${tenantSlug}/setup` : "/app";

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

  const [branches, setBranches] = React.useState<Branch[]>([]);
  const [activeBranchId, setActiveBranchId] = React.useState<number | null>(null);
  const [branchesLoading, setBranchesLoading] = React.useState(false);
  const [branchSwitchBusy, setBranchSwitchBusy] = React.useState<number | null>(null);

  React.useEffect(() => {
    let alive = true;

    async function loadBranches() {
      if (!tenantSlug || auth.isAdmin) return;

      setBranchesLoading(true);
      try {
        const [listRes, currentRes] = await Promise.all([
          apiFetch<{ branches: Branch[] }>(`/api/${tenantSlug}/app/branches`),
          apiFetch<{ active_branch_id: number | null; branch: Branch | null }>(`/api/${tenantSlug}/app/branches/current`),
        ]);

        if (!alive) return;
        setBranches(Array.isArray(listRes.branches) ? listRes.branches : []);
        setActiveBranchId(typeof currentRes.active_branch_id === "number" ? currentRes.active_branch_id : null);
      } catch {
        if (!alive) return;
        setBranches([]);
        setActiveBranchId(null);
      } finally {
        if (!alive) return;
        setBranchesLoading(false);
      }
    }

    void loadBranches();

    return () => {
      alive = false;
    };
  }, [auth.isAdmin, tenantSlug]);

  const activeBranchLabel = React.useMemo(() => {
    const b = branches.find((x) => x.id === activeBranchId) ?? null;
    if (!b) return branchesLoading ? "Loading..." : "Select branch";
    return `${b.code} - ${b.name}`;
  }, [activeBranchId, branches, branchesLoading]);

  const activeBranch = React.useMemo(() => {
    return branches.find((x) => x.id === activeBranchId) ?? null;
  }, [activeBranchId, branches]);

  async function switchBranch(branchId: number) {
    if (!tenantSlug) return;
    setBranchSwitchBusy(branchId);
    try {
      await apiFetch(`/api/${tenantSlug}/app/branches/active`, {
        method: "POST",
        body: { branch_id: branchId },
      });
      setActiveBranchId(branchId);
      if (typeof window !== "undefined") {
        window.dispatchEvent(new CustomEvent("rb:branch-changed", { detail: { branchId } }));
      }
    } finally {
      setBranchSwitchBusy(null);
    }
  }

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
            { label: "Businesses", href: "/admin/businesses", icon: "admin", show: auth.can("admin.tenants.read") },
          ],
        },
        {
          title: "Overview",
          items: [
            {
              label: "Dashboard",
              href: "/admin",
              icon: "dashboard",
              show: auth.can("admin.access"),
            },
            {
              label: auth.can("admin.access") ? "Business Dashboard" : "Dashboard",
              href: tenantBaseHref ?? "/app",
              icon: "dashboard",
              show: Boolean(tenantBaseHref) && auth.can("dashboard.view"),
            },
            {
              label: "Appointments",
              href: tenantSlug ? `/app/${tenantSlug}/calendar` : "/app",
              icon: "calendar",
              show: Boolean(tenantBaseHref) && auth.can("appointments.view"),
            },
            {
              label: "Business Settings",
              href: tenantSlug ? `/app/${tenantSlug}/business-settings` : "/app",
              icon: "services",
              show: auth.isAuthenticated && Boolean(tenantSlug) && auth.can("settings.manage"),
            },
            {
              label: "Statuses",
              href: tenantSlug ? `/app/${tenantSlug}/business-settings?section=job-statuses` : "/app",
              icon: "tags",
              show: auth.isAuthenticated && Boolean(tenantSlug) && auth.can("settings.manage") && auth.can("jobs.view"),
            },
            {
              label: "Users",
              href: tenantSlug ? `/app/${tenantSlug}/users` : "/app",
              icon: "users",
              show: auth.isAuthenticated && Boolean(tenantSlug) && auth.can("users.manage"),
            },
            {
              label: "Roles",
              href: tenantSlug ? `/app/${tenantSlug}/roles` : "/app",
              icon: "shield",
              show: auth.isAuthenticated && Boolean(tenantSlug) && auth.can("roles.manage"),
            },
            {
              label: "Branches",
              href: tenantSlug ? `/app/${tenantSlug}/branches` : "/app",
              icon: "home",
              show: auth.isAuthenticated && Boolean(tenantSlug) && auth.can("branches.manage"),
            },
            {
              label: "Settings",
              href: auth.isAdmin ? "/admin/settings" : tenantSlug ? `/app/${tenantSlug}/settings` : "/app",
              icon: "settings",
              show: auth.isAuthenticated && auth.isAdmin && auth.can("settings.manage"),
            },
            {
              label: "Security",
              href: tenantSlug ? `/app/${tenantSlug}/security` : "/app",
              icon: "shield",
              show: auth.isAuthenticated && Boolean(tenantSlug) && auth.can("security.manage"),
            },
          ],
        },
        {
          title: "Billing",
          items: [
            { label: "Plans", href: "/admin/billing/plans", icon: "payments", show: auth.can("admin.billing.read") },
            { label: "Plan Builder", href: "/admin/billing/builder", icon: "file", show: auth.can("admin.billing.read") },
            { label: "Intervals", href: "/admin/billing/intervals", icon: "calendar", show: auth.can("admin.billing.read") },
            { label: "Entitlements", href: "/admin/billing/entitlements", icon: "tags", show: auth.can("admin.billing.read") },
            { label: "Currencies", href: "/admin/billing/currencies", icon: "payments", show: auth.can("admin.billing.read") },
            {
              label: "Business Billing",
              href: "/admin/billing/businesses",
              icon: "users",
              show: auth.can("admin.billing.read") && auth.can("admin.tenants.read"),
            },
          ],
        },
        {
          title: "Operations",
          items: [
            // { label: "Appointments", href: tenantSlug ? `/app/${tenantSlug}/appointments` : "/app", icon: "calendar", show: Boolean(tenantBaseHref) && auth.can("appointments.view") },
            { label: "Jobs", href: tenantSlug ? `/app/${tenantSlug}/jobs` : "/app", icon: "wrench", show: Boolean(tenantBaseHref) && auth.can("jobs.view") },
            { label: "Estimates", href: tenantSlug ? `/app/${tenantSlug}/estimates` : "/app", icon: "file", show: Boolean(tenantBaseHref) && auth.can("estimates.view") },
            { label: "Services", href: tenantSlug ? `/app/${tenantSlug}/services` : "/app", icon: "services", show: Boolean(tenantBaseHref) && auth.can("services.view") },
            { label: "Service Types", href: tenantSlug ? `/app/${tenantSlug}/service-types` : "/app", icon: "services", show: Boolean(tenantBaseHref) && auth.can("service_types.view") },
            { label: "Service Price Overrides", href: tenantSlug ? `/app/${tenantSlug}/service-price-overrides` : "/app", icon: "services", show: auth.isAuthenticated && Boolean(tenantSlug) && auth.can("settings.manage") },
          ],
        },
        {
          title: "Inventory",
          items: [
            { label: "Devices", href: tenantSlug ? `/app/${tenantSlug}/devices` : "/app", icon: "devices", show: Boolean(tenantBaseHref) && auth.can("devices.view") },
            { label: "Device Brands", href: tenantSlug ? `/app/${tenantSlug}/device-brands` : "/app", icon: "tags", show: Boolean(tenantBaseHref) && auth.can("device_brands.view") },
            { label: "Device Types", href: tenantSlug ? `/app/${tenantSlug}/device-types` : "/app", icon: "tags", show: Boolean(tenantBaseHref) && auth.can("device_types.view") },
            { label: "Device Fields", href: tenantSlug ? `/app/${tenantSlug}/device-field-definitions` : "/app", icon: "tags", show: auth.isAuthenticated && Boolean(tenantSlug) && auth.can("settings.manage") },
            { label: "Parts", href: tenantSlug ? `/app/${tenantSlug}/parts` : "/app", icon: "parts", show: Boolean(tenantBaseHref) && auth.can("parts.view") },
            { label: "Part Brands", href: tenantSlug ? `/app/${tenantSlug}/part-brands` : "/app", icon: "tags", show: Boolean(tenantBaseHref) && auth.can("parts.view") },
            { label: "Part Types", href: tenantSlug ? `/app/${tenantSlug}/part-types` : "/app", icon: "tags", show: Boolean(tenantBaseHref) && auth.can("parts.view") },
          ],
        },
        {
          title: "Finance",
          items: [
            { label: "Payments", href: tenantSlug ? `/app/${tenantSlug}/payments` : "/app", icon: "payments", show: Boolean(tenantBaseHref) && auth.can("payments.view") },
            { label: "Reports", href: tenantSlug ? `/app/${tenantSlug}/reports` : "/app", icon: "reports", show: Boolean(tenantBaseHref) && auth.can("reports.view") },
            { label: "Expenses", href: tenantSlug ? `/app/${tenantSlug}/expenses` : "/app", icon: "calculator", show: Boolean(tenantBaseHref) && auth.can("expenses.view") },
            {
              label: "Expense Categories",
              href: tenantSlug ? `/app/${tenantSlug}/expense-categories` : "/app",
              icon: "calculator",
              show: Boolean(tenantBaseHref) && auth.can("expense_categories.view"),
            },
          ],
        },
        {
          title: "People",
          items: [
            { label: "Customers", href: tenantSlug ? `/app/${tenantSlug}/clients` : "/app", icon: "users", show: Boolean(tenantBaseHref) && auth.can("clients.view") },
            { label: "Customer Devices", href: tenantSlug ? `/app/${tenantSlug}/customer-devices` : "/app", icon: "devices", show: Boolean(tenantBaseHref) && auth.can("customer_devices.view") },
            { label: "Technicians", href: tenantSlug ? `/app/${tenantSlug}/technicians` : "/app", icon: "users", show: Boolean(tenantBaseHref) && auth.can("technicians.view") },
            { label: "Managers", href: tenantSlug ? `/app/${tenantSlug}/managers` : "/app", icon: "users", show: Boolean(tenantBaseHref) && auth.can("managers.view") },
            { label: "Users", href: tenantSlug ? `/app/${tenantSlug}/users` : "/app", icon: "users", show: Boolean(tenantSlug) && auth.can("users.manage") },
            { label: "Roles", href: tenantSlug ? `/app/${tenantSlug}/roles` : "/app", icon: "shield", show: Boolean(tenantSlug) && auth.can("roles.manage") },
            { label: "Branches", href: tenantSlug ? `/app/${tenantSlug}/branches` : "/app", icon: "home", show: Boolean(tenantSlug) && auth.can("branches.manage") },
          ],
        },
        {
          title: "Quality",
          items: [
            { label: "Job Reviews", href: tenantSlug ? `/app/${tenantSlug}/job-reviews` : "/app", icon: "review", show: Boolean(tenantBaseHref) && auth.can("job_reviews.view") },
          ],
        },
        {
          title: "Tools",
          items: [
            { label: "Time Logs", href: tenantSlug ? `/app/${tenantSlug}/time-logs` : "/app", icon: "clock", show: Boolean(tenantBaseHref) && auth.can("time_logs.view") },
            { label: "Manage Hourly Rates", href: tenantSlug ? `/app/${tenantSlug}/hourly-rates` : "/app", icon: "clock", show: Boolean(tenantBaseHref) && auth.can("hourly_rates.view") },
            { label: "Reminder Logs", href: tenantSlug ? `/app/${tenantSlug}/reminder-logs` : "/app", icon: "clock", show: Boolean(tenantBaseHref) && auth.can("reminder_logs.view") },
            { label: "Print Screen", href: tenantSlug ? `/app/${tenantSlug}/print-screen` : "/app", icon: "printer", show: Boolean(tenantBaseHref) && auth.can("print_screen.view") },
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
              href: "/app/profile",
              icon: "users",
              show: auth.isAuthenticated && auth.can("profile.manage"),
            },
            {
              label: "Settings",
              href: auth.isAdmin ? "/admin/settings" : "/app/settings",
              icon: "settings",
              show: auth.isAuthenticated && auth.isAdmin && auth.can("settings.manage"),
            },
            {
              label: "Business Settings",
              href: tenantSlug ? `/app/${tenantSlug}/business-settings` : "/app",
              icon: "services",
              show: auth.isAuthenticated && Boolean(tenantSlug) && auth.can("settings.manage"),
            },
          ],
        },
      ],
    [auth, tenantBaseHref, tenantSlug],
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
      const next: Record<string, boolean> = { ...prev };
      let changed = false;

      const overview = resolvedNavSections.find((s) => s.title === "Overview");
      if (overview && next[overview.key] === undefined) {
        next[overview.key] = true;
        changed = true;
      }

      if (!active) {
        return changed ? next : prev;
      }

      for (const s of resolvedNavSections) {
        if (!s.title) continue;
        if (s.title === "Overview") continue;
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
                alt="99smartx"
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
                          if (section.title !== "Overview" && s.title === "Overview") continue;
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
                    {!auth.isAdmin && tenantSlug ? (
                      <div className="relative">
                        <DropdownMenu
                          align="right"
                          trigger={({ open, toggle }) => {
                            const disabled = branchesLoading || branchSwitchBusy !== null || branches.length === 0;

                            return (
                              <button
                                type="button"
                                onClick={toggle}
                                disabled={disabled}
                                className={cn(
                                  "group inline-flex h-9 max-w-[320px] items-center gap-2.5 rounded-[var(--rb-radius-sm)] border px-2.5",
                                  "bg-white text-zinc-700 border-zinc-200",
                                  "hover:bg-[var(--rb-surface-muted)]",
                                  "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--rb-orange)]",
                                  disabled ? "pointer-events-none opacity-60" : "",
                                )}
                                aria-haspopup="menu"
                                aria-expanded={open}
                                aria-label="Active shop"
                              >
                                <MenuIcon name="building" className="h-4 w-4 text-zinc-500" />
                                <span className="min-w-0 flex-1 text-left">
                                  {/* <span className="block truncate text-[11px] font-semibold uppercase tracking-wider text-zinc-500">
                                    Shop
                                  </span> */}
                                  <span className="block truncate text-sm font-semibold text-[var(--rb-text)]">
                                    {activeBranch?.name || activeBranchLabel}
                                  </span>
                                </span>
                                <ChevronDownIcon className={cn("text-zinc-500 transition-transform", open ? "rotate-180" : "rotate-0")} />
                              </button>
                            );
                          }}
                        >
                          {({ close }) => {
                            const activeBranches = branches.filter((b) => b.is_active);

                            return (
                              <div>
                                <div className="px-3 py-2">
                                  <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Shop</div>
                                  <div className="mt-1 truncate text-sm font-semibold text-[var(--rb-text)]">
                                    {activeBranch?.name || activeBranchLabel}
                                  </div>
                                </div>
                                <DropdownMenuSeparator />

                                {branchesLoading ? (
                                  <DropdownMenuItem onSelect={() => close()} disabled>
                                    Loading shops...
                                  </DropdownMenuItem>
                                ) : activeBranches.length === 0 ? (
                                  <DropdownMenuItem onSelect={() => close()} disabled>
                                    No shops available
                                  </DropdownMenuItem>
                                ) : (
                                  activeBranches.map((b) => {
                                    const isActive = b.id === activeBranchId;
                                    const busy = branchSwitchBusy === b.id;

                                    return (
                                      <DropdownMenuItem
                                        key={b.id}
                                        disabled={branchSwitchBusy !== null}
                                        onSelect={() => {
                                          close();
                                          if (!busy && !isActive) {
                                            void switchBranch(b.id);
                                          }
                                        }}
                                      >
                                        <span className="flex w-full items-center justify-between gap-3">
                                          <span className="min-w-0">
                                            <span className="block truncate text-sm font-medium text-zinc-800">{b.name}</span>
                                            <span className="block truncate text-[11px] font-medium text-zinc-500">{b.code}</span>
                                          </span>
                                          <span className="shrink-0">
                                            {busy ? (
                                              <span className="text-xs font-medium text-zinc-500">Switching...</span>
                                            ) : isActive ? (
                                              <CheckIcon className="text-[var(--rb-blue)]" />
                                            ) : null}
                                          </span>
                                        </span>
                                      </DropdownMenuItem>
                                    );
                                  })
                                )}
                              </div>
                            );
                          }}
                        </DropdownMenu>
                      </div>
                    ) : null}
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

            {showSetupBanner ? (
              <Card className="mx-auto mt-6 w-full max-w-6xl px-5 py-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                  <div className="min-w-0">
                    <div className="text-sm font-semibold text-[var(--rb-text)]">Finish setup</div>
                    <div className="mt-1 text-sm text-zinc-600">
                      Your workspace isn&apos;t fully configured yet. Some features may be limited until setup is complete.
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    <Link
                      href={setupHref}
                      className="inline-flex h-10 items-center justify-center whitespace-nowrap rounded-[var(--rb-radius-sm)] bg-[var(--rb-blue)] px-4 text-sm font-medium text-white transition-colors hover:opacity-90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--rb-orange)] focus-visible:ring-offset-2 focus-visible:ring-offset-white"
                    >
                      Resume setup
                    </Link>
                  </div>
                </div>
              </Card>
            ) : null}

            <main className="mx-auto mt-6 w-full max-w-6xl px-4 pb-8">{children}</main>
          </div>
        </div>
      </div>
    </DashboardHeaderContext.Provider>
  );
}
