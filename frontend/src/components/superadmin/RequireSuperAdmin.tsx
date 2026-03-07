"use client";

import React, { useEffect } from "react";
import { usePathname, useRouter } from "next/navigation";
import { useAuth } from "@/lib/auth";
import { Preloader } from "@/components/Preloader";

export function RequireSuperAdmin({ children }: { children: React.ReactNode }) {
  const auth = useAuth();
  const router = useRouter();
  const pathname = usePathname();

  useEffect(() => {
    if (auth.loading) return;

    if (!auth.isAuthenticated) {
      router.replace(`/superadmin/login?next=${encodeURIComponent(pathname)}`);
      return;
    }

    if (!auth.isAdmin) {
      router.replace(`/superadmin/login?error=access_denied`);
      return;
    }
  }, [auth.loading, auth.isAuthenticated, auth.isAdmin, pathname, router]);

  if (auth.loading) return <Preloader />;
  if (!auth.isAuthenticated || !auth.isAdmin) return <Preloader />;

  return <>{children}</>;
}
