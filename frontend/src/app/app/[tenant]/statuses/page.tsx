"use client";

import React from "react";
import { useParams, useRouter } from "next/navigation";
import { useAuth } from "@/lib/auth";

export default function TenantStatusesPage() {
  const auth = useAuth();
  const router = useRouter();
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  React.useEffect(() => {
    if (auth.loading) return;
    if (!auth.isAuthenticated) return;
    if (!auth.can("jobs.view") || !auth.can("settings.manage")) {
      router.replace("/app");
      return;
    }
    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) {
      router.replace("/app");
      return;
    }

    router.replace(`/app/${tenantSlug}/business-settings?section=job-statuses`);
  }, [auth, auth.isAuthenticated, auth.loading, router, tenantSlug]);

  return null;
}
