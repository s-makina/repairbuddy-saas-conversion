"use client";

import React, { useEffect, useMemo, useState } from "react";
import { RequireAuth } from "@/components/RequireAuth";
import { useDashboardHeader } from "@/components/DashboardShell";
import { Alert } from "@/components/ui/Alert";
import { Button } from "@/components/ui/Button";
import { DataTable } from "@/components/ui/DataTable";
import { getBillingCatalog } from "@/lib/billing";
import type { EntitlementDefinition } from "@/lib/types";

export default function AdminBillingEntitlementsPage() {
  const dashboardHeader = useDashboardHeader();

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [definitions, setDefinitions] = useState<EntitlementDefinition[]>([]);
  const [reloadNonce, setReloadNonce] = useState(0);

  useEffect(() => {
    dashboardHeader.setHeader({
      breadcrumb: "Admin / Billing",
      title: "Entitlements",
      subtitle: "Entitlement definitions used by billing plan versions",
      actions: (
        <Button variant="outline" size="sm" onClick={() => setReloadNonce((v) => v + 1)} disabled={loading}>
          Refresh
        </Button>
      ),
    });

    return () => dashboardHeader.setHeader(null);
  }, [dashboardHeader, loading]);

  useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);

        const res = await getBillingCatalog({ includeInactive: true });
        if (!alive) return;

        setDefinitions(Array.isArray(res.entitlement_definitions) ? res.entitlement_definitions : []);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load entitlement definitions.");
        setDefinitions([]);
      } finally {
        if (!alive) return;
        setLoading(false);
      }
    }

    void load();

    return () => {
      alive = false;
    };
  }, [reloadNonce]);

  const rows = useMemo(() => definitions, [definitions]);

  return (
    <RequireAuth requiredPermission="admin.billing.read">
      <div className="space-y-6">
        {error ? (
          <Alert variant="danger" title="Could not load entitlements">
            {error}
          </Alert>
        ) : null}

        <DataTable
          title="Entitlement definitions"
          data={rows}
          loading={loading}
          emptyMessage="No entitlement definitions found."
          getRowId={(d) => d.id}
          search={{
            placeholder: "Search by name or code…",
            getSearchText: (d) => `${d.name} ${d.code} ${d.value_type}`,
          }}
          columns={[
            {
              id: "name",
              header: "Name",
              cell: (d) => (
                <div className="min-w-0">
                  <div className="truncate text-sm font-medium text-zinc-800">{d.name}</div>
                  <div className="truncate text-xs text-zinc-500">{d.code}</div>
                </div>
              ),
              className: "min-w-[220px]",
            },
            {
              id: "type",
              header: "Type",
              cell: (d) => <div className="text-sm text-zinc-700">{d.value_type}</div>,
              className: "whitespace-nowrap",
            },
            {
              id: "description",
              header: "Description",
              cell: (d) => <div className="text-sm text-zinc-700">{d.description ?? "—"}</div>,
            },
          ]}
        />
      </div>
    </RequireAuth>
  );
}
