"use client";

import React from "react";
import { useParams } from "next/navigation";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { ListPageShell } from "@/components/shells/ListPageShell";

export default function TenantPrintScreenPage() {
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [type, setType] = React.useState<"job" | "estimate" | "invoice">("job");
  const [id, setId] = React.useState("job_001");

  return (
    <ListPageShell
      title="Print Screen"
      description="Print-friendly output hub (mock)."
      actions={
        <Button disabled variant="outline" size="sm">
          Print
        </Button>
      }
      loading={false}
      error={null}
      empty={false}
    >
      <Card className="shadow-none">
        <CardContent className="pt-5">
          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Document type</div>
              <select
                value={type}
                onChange={(e) => setType(e.target.value as "job" | "estimate" | "invoice")}
                className="mt-2 h-10 w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 text-sm"
              >
                <option value="job">Job</option>
                <option value="estimate">Estimate</option>
                <option value="invoice">Invoice</option>
              </select>
            </div>
            <div>
              <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">ID</div>
              <input
                value={id}
                onChange={(e) => setId(e.target.value)}
                className="mt-2 h-10 w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 text-sm"
                placeholder="job_001 / estimate_001 / inv_001"
              />
            </div>
            <div className="sm:col-span-2">
              <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-4">
                <div className="text-sm font-semibold text-[var(--rb-text)]">Preview</div>
                <div className="mt-2 text-sm text-zinc-700">
                  Tenant: {typeof tenantSlug === "string" ? tenantSlug : "—"}
                </div>
                <div className="mt-1 text-sm text-zinc-700">
                  Document: {type} / {id}
                </div>
                <div className="mt-3 text-sm text-zinc-600">This is a placeholder print preview. Printable layouts are implemented in later phases.</div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </ListPageShell>
  );
}
