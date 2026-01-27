"use client";

import React from "react";
import { useParams } from "next/navigation";
import { RequireAuth } from "@/components/RequireAuth";
import { PageHeader } from "@/components/ui/PageHeader";
import { RepairBuddySettingsTab } from "@/app/app/[tenant]/settings/_components/repairbuddy/RepairBuddySettingsTab";

export default function TenantBusinessSettingsPage() {
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  return (
    <RequireAuth requiredPermission="settings.manage">
      <div className="space-y-6">
        <PageHeader title="Business Settings" description="Operational settings for this business." />

        <RepairBuddySettingsTab tenantSlug={String(tenantSlug)} />
      </div>
    </RequireAuth>
  );
}
