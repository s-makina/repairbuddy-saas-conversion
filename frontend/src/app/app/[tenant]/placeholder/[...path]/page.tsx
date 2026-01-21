"use client";

import React from "react";
import { useParams } from "next/navigation";
import { PageHeader } from "@/components/ui/PageHeader";
import { Card, CardContent } from "@/components/ui/Card";

export default function TenantPlaceholderPage() {
  const params = useParams() as { tenant?: string; business?: string; path?: string[] };
  const path = Array.isArray(params.path) ? params.path : [];
  const tenantSlug = params.business ?? params.tenant;

  const title = path.length > 0 ? path.join(" / ") : "Placeholder";

  return (
    <div className="space-y-6">
      <PageHeader title={title} description="Coming soon." />

      <Card className="shadow-none">
        <CardContent className="pt-5">
          <div className="text-sm text-zinc-700">This screen is a Phase 1 placeholder.</div>
          <div className="mt-2 text-xs text-zinc-500">Tenant: {tenantSlug}</div>
        </CardContent>
      </Card>
    </div>
  );
}
