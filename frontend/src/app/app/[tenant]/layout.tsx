"use client";

import React from "react";
import { DashboardShell } from "@/components/DashboardShell";
import { RequireAuth } from "@/components/RequireAuth";

export default function TenantAppLayout({ children }: { children: React.ReactNode }) {
  return (
    <RequireAuth>
      <DashboardShell title="Tenant dashboard">{children}</DashboardShell>
    </RequireAuth>
  );
}
