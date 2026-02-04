"use client";

import React from "react";
import { useParams } from "next/navigation";
import { RequireAuth } from "@/components/RequireAuth";
import { PageHeader } from "@/components/ui/PageHeader";
import { RepairBuddySettingsTab } from "@/app/app/[tenant]/settings/_components/repairbuddy/RepairBuddySettingsTab";

export default function TenantBusinessSettingsPage() {
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  if (!tenantSlug) {
    return (
      <RequireAuth requiredPermission="settings.manage">
        <div className="space-y-6">
          <PageHeader title="Business Settings" description="Operational settings for this business." />
          <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white px-4 py-3 text-sm text-zinc-700">
            Missing tenant in route.
          </div>
        </div>
      </RequireAuth>
    );
  }

  return (
    <RequireAuth requiredPermission="settings.manage">
      <div className="space-y-6">
        <PageHeader title="Business Settings" description="Operational settings for this business." />

        <RepairBuddySettingsTab tenantSlug={tenantSlug} />
      </div>
    </RequireAuth>
  );
}
