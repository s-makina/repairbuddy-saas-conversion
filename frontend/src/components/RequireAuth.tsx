"use client";

import React, { useEffect } from "react";
import { usePathname, useRouter } from "next/navigation";
import { useAuth } from "@/lib/auth";
import { Preloader } from "@/components/Preloader";

export function RequireAuth({
  children,
  adminOnly = false,
  requiredPermission,
}: {
  children: React.ReactNode;
  adminOnly?: boolean;
  requiredPermission?: string;
}) {
  const auth = useAuth();
  const router = useRouter();
  const pathname = usePathname();

  const tenantSlug = auth.tenant?.slug ?? null;
  const setupPath = tenantSlug ? `/${tenantSlug}/setup` : null;
  const isOnSetupPath = setupPath ? (pathname === setupPath || pathname.startsWith(`${setupPath}/`)) : false;
  const isSetupIncomplete = Boolean(tenantSlug) && !auth.isAdmin && !auth.tenant?.setup_completed_at;

  useEffect(() => {
    if (auth.loading) return;

    if (!auth.isAuthenticated) {
      router.replace(`/login?next=${encodeURIComponent(pathname)}`);
      return;
    }

    if (auth.user && !auth.user.email_verified_at) {
      router.replace(`/verify-email?email=${encodeURIComponent(auth.user.email)}`);
      return;
    }

    if (adminOnly && !auth.isAdmin) {
      router.replace("/app");
      return;
    }

    if (requiredPermission && !auth.can(requiredPermission)) {
      router.replace("/app");
      return;
    }

    if (isSetupIncomplete && !isOnSetupPath) {
      router.replace(setupPath ?? "/app");
    }
  }, [adminOnly, auth, isOnSetupPath, isSetupIncomplete, pathname, requiredPermission, router, setupPath]);

  if (auth.loading) {
    return <Preloader />;
  }

  if (!auth.isAuthenticated) {
    return null;
  }

  if (auth.user && !auth.user.email_verified_at) {
    return null;
  }

  if (adminOnly && !auth.isAdmin) {
    return null;
  }

  if (requiredPermission && !auth.can(requiredPermission)) {
    return null;
  }

  if (isSetupIncomplete && !isOnSetupPath) {
    return null;
  }

  return <>{children}</>;
}
