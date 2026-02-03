"use client";

import React from "react";
import { useParams } from "next/navigation";
import { RequireAuth } from "@/components/RequireAuth";
import { EstimateWizard } from "@/components/repairbuddy/EstimateWizard";

export default function NewEstimatePage() {
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;
  if (typeof tenantSlug !== "string" || tenantSlug.trim().length === 0) {
    return (
      <RequireAuth requiredPermission="estimates.manage">
        <div className="text-sm text-zinc-600">Tenant is missing.</div>
      </RequireAuth>
    );
  }

  return (
    <RequireAuth requiredPermission="estimates.manage">
      <EstimateWizard tenantSlug={tenantSlug} />
    </RequireAuth>
  );
}
