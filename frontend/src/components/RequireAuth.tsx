"use client";

import React, { useEffect } from "react";
import { usePathname, useRouter } from "next/navigation";
import { useAuth } from "@/lib/auth";

export function RequireAuth({
  children,
  adminOnly = false,
}: {
  children: React.ReactNode;
  adminOnly?: boolean;
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

    if (adminOnly && !auth.isAdmin) {
      router.replace("/app");
    }
  }, [adminOnly, auth.isAdmin, auth.isAuthenticated, auth.loading, pathname, router]);

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

  if (adminOnly && !auth.isAdmin) {
    return null;
  }

  return <>{children}</>;
}
