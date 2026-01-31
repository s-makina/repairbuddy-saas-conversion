"use client";

import React, { useEffect } from "react";
import { usePathname, useRouter } from "next/navigation";
import { useAuth } from "@/lib/auth";
import { Preloader } from "@/components/Preloader";
import { computeGateRedirect, getGate } from "@/lib/gate";
import { apiFetch, ApiError } from "@/lib/api";

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

  const setPasswordPath = "/set-password";
  const isOnSetPassword = pathname === setPasswordPath || pathname.startsWith(`${setPasswordPath}/`);

  const tenantSlug = auth.tenant?.slug ?? null;
  const setupPath = tenantSlug ? `/${tenantSlug}/setup` : null;
  const isOnSetupPath = setupPath ? (pathname === setupPath || pathname.startsWith(`${setupPath}/`)) : false;

  const branchSelectPath = tenantSlug ? `/app/${tenantSlug}/branches/select` : null;
  const isOnBranchSelect = branchSelectPath
    ? pathname === branchSelectPath || pathname.startsWith(`${branchSelectPath}/`)
    : false;

  useEffect(() => {
    if (auth.loading) return;

    if (!auth.isAuthenticated) {
      router.replace(`/login?next=${encodeURIComponent(pathname)}`);
      return;
    }

    if (auth.user?.must_change_password && !isOnSetPassword) {
      router.replace(`${setPasswordPath}?next=${encodeURIComponent(pathname)}`);
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

    if (!auth.isAdmin && tenantSlug) {
      void apiFetch<{ mfa: { enforced: boolean; compliant: boolean } }>(`/api/${tenantSlug}/app/security-status`)
        .then(({ mfa }) => {
          const isOnSecurity = pathname === `/app/${tenantSlug}/security` || pathname.startsWith(`/app/${tenantSlug}/security/`);
          if (mfa.enforced && !mfa.compliant && !isOnSecurity) {
            router.replace(`/app/${tenantSlug}/security`);
            return;
          }

          return getGate(tenantSlug).then(({ gate }) => {
            const expected = computeGateRedirect(tenantSlug, gate);

            const isOnPlans = pathname === `/${tenantSlug}/plans` || pathname.startsWith(`/${tenantSlug}/plans/`);
            const isOnCheckout = pathname === `/${tenantSlug}/checkout` || pathname.startsWith(`/${tenantSlug}/checkout/`);
            const isOnSuspended = pathname === `/${tenantSlug}/suspended` || pathname.startsWith(`/${tenantSlug}/suspended/`);
            const isOnApp = pathname === `/app/${tenantSlug}` || pathname.startsWith(`/app/${tenantSlug}/`);

            const canAccessPlansDuringCheckout = expected === `/${tenantSlug}/checkout` && isOnPlans;

            const isOnExpected =
              (expected === `/${tenantSlug}/plans` && isOnPlans) ||
              (expected === `/${tenantSlug}/checkout` && isOnCheckout) ||
              (expected === `/${tenantSlug}/suspended` && isOnSuspended) ||
              (expected === `/${tenantSlug}/setup` && isOnSetupPath) ||
              (expected === `/app/${tenantSlug}` && isOnApp) ||
              canAccessPlansDuringCheckout;

            if (!isOnExpected) {
              router.replace(expected);
            }
          });
        })
        .catch((err) => {
          if (err instanceof ApiError && err.status === 428) {
            const code =
              err.data && typeof err.data === "object" && "code" in (err.data as Record<string, unknown>)
                ? String((err.data as Record<string, unknown>).code)
                : null;

            if (code === "password_change_required") {
              if (!isOnSetPassword) {
                router.replace(`${setPasswordPath}?next=${encodeURIComponent(pathname)}`);
              }
              return;
            }

            if (!isOnBranchSelect && branchSelectPath) {
              router.replace(`${branchSelectPath}?next=${encodeURIComponent(pathname)}`);
            }
            return;
          }

          if (err instanceof ApiError && err.status === 403) {
            return;
          }

          // If gate lookup fails, fall back to setup gating.
          if (!auth.tenant?.setup_completed_at && !isOnSetupPath) {
            router.replace(setupPath ?? "/app");
          }
        });
    }
  }, [adminOnly, auth, branchSelectPath, isOnBranchSelect, isOnSetupPath, pathname, requiredPermission, router, setupPath, tenantSlug]);

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

  return <>{children}</>;
}
