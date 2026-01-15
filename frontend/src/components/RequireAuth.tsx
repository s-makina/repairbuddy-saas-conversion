"use client";

import React, { useEffect } from "react";
import { usePathname, useRouter } from "next/navigation";
import { useAuth } from "@/lib/auth";

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
    }
  }, [adminOnly, auth, requiredPermission, pathname, router]);

  if (auth.loading) {
    return (
      <div className="min-h-screen flex items-center justify-center text-sm text-zinc-500">
        Loading...
      </div>
    );
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

  return <>{children}</>;
}
