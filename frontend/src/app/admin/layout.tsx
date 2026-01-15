"use client";

import React from "react";
import { DashboardShell } from "@/components/DashboardShell";
import { RequireAuth } from "@/components/RequireAuth";

export default function AdminLayout({ children }: { children: React.ReactNode }) {
  return (
    <RequireAuth adminOnly>
      <DashboardShell title="Admin dashboard">{children}</DashboardShell>
    </RequireAuth>
  );
}
