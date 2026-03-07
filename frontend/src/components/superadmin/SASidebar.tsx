"use client";

import React, { useState, useEffect } from "react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { getAdminBusinessStats } from "@/lib/superadmin";
import { useAuth } from "@/lib/auth";

/* ── SVG Icon Paths ── */
const icons = {
  wrench: (
    <path
      d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"
    />
  ),
  search: (
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
  ),
  dashboard: (
    <>
      <rect x="3" y="3" width="7" height="7" rx="1.5" strokeWidth="2" />
      <rect x="14" y="3" width="7" height="7" rx="1.5" strokeWidth="2" />
      <rect x="14" y="14" width="7" height="7" rx="1.5" strokeWidth="2" />
      <rect x="3" y="14" width="7" height="7" rx="1.5" strokeWidth="2" />
    </>
  ),
  activity: (
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
      d="M13 10V3L4 14h7v7l9-11h-7z" />
  ),
  businesses: (
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
  ),
  users: (
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
      d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
  ),
  shield: (
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
      d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
  ),
  billing: (
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
      d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v10a2 2 0 002 2z" />
  ),
  plus: (
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
      d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
  ),
  entitlements: (
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
      d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
  ),
  currency: (
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
      d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
  ),
  clock: (
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
  ),
  audit: (
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
  ),
  analytics: (
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
      d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
  ),
  settings: (
    <>
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
    </>
  ),
  signout: (
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
      d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
  ),
};

function Icon({ name }: { name: keyof typeof icons }) {
  return (
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5">
      {icons[name]}
    </svg>
  );
}

type NavItem = {
  label: string;
  icon: keyof typeof icons;
  href: string;
  badge?: number;
  count?: string;
};

type NavSection = {
  label?: string;
  items: NavItem[];
};

export function SASidebar() {
  const pathname = usePathname();
  const router = useRouter();
  const { user, logout } = useAuth();
  const [businessCount, setBusinessCount] = useState<string | undefined>(undefined);
  const [loggingOut, setLoggingOut] = useState(false);

  const handleLogout = async () => {
    if (loggingOut) return;
    setLoggingOut(true);
    try {
      await logout();
    } finally {
      router.replace("/superadmin/login");
    }
  };

  // Derive initials from user name
  const initials = user?.name
    ? user.name.trim().split(/\s+/).map((w) => w[0]).slice(0, 2).join("").toUpperCase()
    : "SA";

  useEffect(() => {
    const controller = new AbortController();

    getAdminBusinessStats()
      .then((stats) => {
        if (!controller.signal.aborted) {
          setBusinessCount(stats.total.toLocaleString());
        }
      })
      .catch(() => {
        // Silent failure — sidebar remains functional without a count
      });

    return () => {
      controller.abort();
    };
  }, []);

  const navSections: NavSection[] = [
    {
      label: "Overview",
      items: [
        { label: "Dashboard", icon: "dashboard", href: "/superadmin" },
        { label: "Activity Feed", icon: "activity", href: "/superadmin/activity", badge: 4 },
      ],
    },
    {
      label: "Business Management",
      items: [
        { label: "All Businesses", icon: "businesses", href: "/superadmin/businesses", count: businessCount },
        { label: "Users Directory", icon: "users", href: "/superadmin/users" },
        { label: "Impersonation Log", icon: "shield", href: "/superadmin/impersonation" },
      ],
    },
    {
      label: "Billing & Subscriptions",
      items: [
        { label: "Billing Plans", icon: "billing", href: "/superadmin/billing/plans" },
        { label: "Plan Builder", icon: "plus", href: "/superadmin/billing/builder" },
        { label: "Entitlements", icon: "entitlements", href: "/superadmin/billing/entitlements" },
        { label: "Currencies", icon: "currency", href: "/superadmin/billing/currencies" },
        { label: "Billing Intervals", icon: "clock", href: "/superadmin/billing/intervals" },
      ],
    },
    {
      label: "Platform",
      items: [
        { label: "Audit Logs", icon: "audit", href: "/superadmin/audit" },
        { label: "Analytics", icon: "analytics", href: "/superadmin/analytics" },
        { label: "Settings", icon: "settings", href: "/superadmin/settings" },
      ],
    },
  ];

  return (
    <aside className="sa-sidebar">
      {/* Brand */}
      <div className="sa-brand">
        <div className="sa-logo-mark">
          <Icon name="wrench" />
        </div>
        <div>
          <div className="sa-brand-txt">99SmartX</div>
        </div>
      </div>

      {/* Search */}
      <div className="sa-search">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input type="text" placeholder="Quick search…" />
      </div>

      {/* Navigation sections */}
      {navSections.map((section, si) => (
        <React.Fragment key={si}>
          <div className="sa-nav-sec">
            {section.label && <div className="sa-nav-lbl">{section.label}</div>}
            {section.items.map((item) => {
              const isActive = pathname === item.href;
              return (
                <Link
                  key={item.href}
                  href={item.href}
                  className={`sa-nv${isActive ? " active" : ""}`}
                >
                  <Icon name={item.icon} />
                  {item.label}
                  {item.badge != null && <span className="sa-nv-badge">{item.badge}</span>}
                  {item.count != null && <span className="sa-nv-count">{item.count}</span>}
                </Link>
              );
            })}
          </div>
          <hr className="sa-hr" />
        </React.Fragment>
      ))}

      {/* Sign out */}
      <div className="sa-nav-sec">
        <button
          className="sa-nv sa-nv-signout"
          type="button"
          onClick={handleLogout}
          disabled={loggingOut}
          style={{ opacity: loggingOut ? 0.6 : 1, cursor: loggingOut ? "wait" : "pointer" }}
        >
          <Icon name="signout" />
          {loggingOut ? "Signing out…" : "Sign Out"}
        </button>
      </div>

      {/* User card */}
      <div className="sa-user">
        <div className="sa-avatar">{initials}</div>
        <div>
          <div className="sa-uname">{user?.name ?? "Super Admin"}</div>
          <div className="sa-urole">{user?.email ?? "Platform Administrator"}</div>
        </div>
      </div>
    </aside>
  );
}
