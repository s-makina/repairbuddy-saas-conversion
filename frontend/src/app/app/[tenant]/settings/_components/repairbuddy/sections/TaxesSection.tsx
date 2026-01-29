"use client";

import React, { useMemo, useState } from "react";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { Input } from "@/components/ui/Input";
import { Modal } from "@/components/ui/Modal";
import { Select } from "@/components/ui/Select";
import { SectionShell } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/SectionShell";
import type { RepairBuddyTax, RepairBuddySettingsDraft } from "@/app/app/[tenant]/settings/_components/repairbuddy/types";

export function TaxesSection({
  draft,
  updateTaxes,
  isMock,
}: {
  draft: RepairBuddySettingsDraft;
  updateTaxes: (patch: Partial<RepairBuddySettingsDraft["taxes"]>) => void;
  isMock: boolean;
}) {
  const [addOpen, setAddOpen] = useState(false);
  const [query, setQuery] = useState("");
  const [pageIndex, setPageIndex] = useState(0);
  const [pageSize, setPageSize] = useState(10);
  const t = draft.taxes;

  const activeTaxes = useMemo(() => t.taxes.filter((x) => x.status === "active"), [t.taxes]);

  const filtered = useMemo(() => {
    const needle = query.trim().toLowerCase();
    if (!needle) return t.taxes;
    return t.taxes.filter((x) => `${x.id} ${x.name} ${x.ratePercent} ${x.status}`.toLowerCase().includes(needle));
  }, [query, t.taxes]);

  const totalRows = filtered.length;
  const pageRows = useMemo(() => {
    const start = pageIndex * pageSize;
    const end = start + pageSize;
    return filtered.slice(start, end);
  }, [filtered, pageIndex, pageSize]);

  const columns = useMemo<Array<DataTableColumn<RepairBuddyTax>>>(
    () => [
      { id: "name", header: "Name", cell: (x) => <div className="text-sm text-zinc-700">{x.name}</div>, className: "min-w-[220px]" },
      { id: "rate", header: "Rate (%)", cell: (x) => <div className="text-sm text-zinc-700">{x.ratePercent}</div>, className: "whitespace-nowrap" },
      { id: "status", header: "Status", cell: (x) => <div className="text-sm text-zinc-700">{x.status}</div>, className: "whitespace-nowrap" },
      {
        id: "actions",
        header: "Actions",
        cell: () => (
          <Button size="sm" variant="outline" disabled={isMock}>
            Edit
          </Button>
        ),
        className: "whitespace-nowrap",
      },
    ],
    [isMock],
  );

  return (
    <SectionShell title="Taxes" description="Tax rates and invoice calculation preferences.">
      <label className="flex items-center gap-2 text-sm">
        <input type="checkbox" checked={t.enableTaxes} onChange={(e) => updateTaxes({ enableTaxes: e.target.checked })} />
        Enable taxes
      </label>

      <div className="flex items-center justify-between gap-3">
        <div className="text-sm text-zinc-600">Taxes table (UI-only; add/edit disabled).</div>
        <div className="inline-flex" onClick={() => setAddOpen(true)}>
          <Button variant="outline" disabled={isMock} className="pointer-events-none">
            Add tax
          </Button>
        </div>
      </div>

      <Card className="shadow-none">
        <CardContent className="pt-5">
          <DataTable
            title="Taxes"
            data={pageRows}
            columns={columns}
            getRowId={(row) => row.id}
            emptyMessage="No tax rates."
            search={{ placeholder: "Search taxes..." }}
            server={{
              query,
              onQueryChange: (value) => {
                setQuery(value);
                setPageIndex(0);
              },
              pageIndex,
              onPageIndexChange: setPageIndex,
              pageSize,
              onPageSizeChange: (value) => {
                setPageSize(value);
                setPageIndex(0);
              },
              totalRows,
            }}
          />
        </CardContent>
      </Card>

      <div className="grid gap-4 sm:grid-cols-2">
        <div className="space-y-1">
          <label className="text-sm font-medium">Default tax</label>
          <Select value={t.defaultTaxId} onChange={(e) => updateTaxes({ defaultTaxId: e.target.value })}>
            {activeTaxes.map((x) => (
              <option key={x.id} value={x.id}>
                {x.name} ({x.ratePercent}%)
              </option>
            ))}
          </Select>
        </div>
        <div className="space-y-1">
          <label className="text-sm font-medium">Invoice amounts</label>
          <Select value={t.invoiceAmounts} onChange={(e) => updateTaxes({ invoiceAmounts: e.target.value as RepairBuddySettingsDraft["taxes"]["invoiceAmounts"] })}>
            <option value="exclusive">Exclusive</option>
            <option value="inclusive">Inclusive</option>
          </Select>
        </div>
      </div>

      <Modal
        open={addOpen}
        onClose={() => setAddOpen(false)}
        title="Add tax"
        footer={
          <div className="flex items-center justify-end gap-2">
            <Button variant="outline" onClick={() => setAddOpen(false)}>
              Close
            </Button>
            <Button disabled={isMock}>Save</Button>
          </div>
        }
      >
        <AddTaxForm />
      </Modal>
    </SectionShell>
  );
}

function AddTaxForm() {
  const [draft, setDraft] = useState<RepairBuddyTax>(() => ({ id: "", name: "", ratePercent: 0, status: "active" }));

  return (
    <div className="space-y-3">
      <div className="space-y-1">
        <label className="text-sm font-medium">Name</label>
        <Input value={draft.name} onChange={(e) => setDraft((p) => ({ ...p, name: e.target.value }))} placeholder="e.g. VAT" />
      </div>
      <div className="space-y-1">
        <label className="text-sm font-medium">Rate (%)</label>
        <Input
          type="number"
          min={0}
          value={String(draft.ratePercent)}
          onChange={(e) => setDraft((p) => ({ ...p, ratePercent: Number(e.target.value || 0) }))}
        />
      </div>
    </div>
  );
}
