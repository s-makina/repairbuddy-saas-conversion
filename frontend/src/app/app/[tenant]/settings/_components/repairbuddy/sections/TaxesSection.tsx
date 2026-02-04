"use client";

import React, { useEffect, useMemo, useState } from "react";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { Input } from "@/components/ui/Input";
import { Modal } from "@/components/ui/Modal";
import { Select } from "@/components/ui/Select";
import { SectionShell } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/SectionShell";
import type { RepairBuddyTax, RepairBuddySettingsDraft } from "@/app/app/[tenant]/settings/_components/repairbuddy/types";
import { updateRepairBuddySettings } from "@/lib/repairbuddy-settings";
import { getSetup, updateSetup } from "@/lib/setup";
import {
  createRepairBuddyTax,
  deleteRepairBuddyTax,
  getRepairBuddyTaxes,
  setRepairBuddyTaxActive,
  setRepairBuddyTaxDefault,
  updateRepairBuddyTax,
  type ApiRepairBuddyTax,
} from "@/lib/repairbuddy-taxes";

export function TaxesSection({
  tenantSlug,
  draft,
  updateTaxes,
  isMock,
}: {
  tenantSlug: string;
  draft: RepairBuddySettingsDraft;
  updateTaxes: (patch: Partial<RepairBuddySettingsDraft["taxes"]>) => void;
  isMock: boolean;
}) {
  const [addOpen, setAddOpen] = useState(false);
  const [editOpen, setEditOpen] = useState(false);
  const [busy, setBusy] = useState(false);
  const [savingMeta, setSavingMeta] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [status, setStatus] = useState<string | null>(null);

  const [taxRegistered, setTaxRegistered] = useState(false);
  const [billingVatNumber, setBillingVatNumber] = useState("");
  const [invoicePrefix, setInvoicePrefix] = useState("RB");

  const [taxes, setTaxes] = useState<ApiRepairBuddyTax[]>([]);
  const [loadingTaxes, setLoadingTaxes] = useState(false);

  const [query, setQuery] = useState("");
  const [pageIndex, setPageIndex] = useState(0);
  const [pageSize, setPageSize] = useState(10);
  const t = draft.taxes;

  async function persistTaxesPrefs(next: RepairBuddySettingsDraft["taxes"]) {
    if (isMock) return;
    try {
      await updateRepairBuddySettings(String(tenantSlug), {
        ...draft,
        taxes: next,
      });
    } catch (e) {
      setError(e instanceof Error ? e.message : "Failed to save tax preferences.");
    }
  }

  useEffect(() => {
    let alive = true;
    async function load() {
      setError(null);
      setLoadingTaxes(true);
      try {
        const [res, setupRes] = await Promise.all([getRepairBuddyTaxes(String(tenantSlug)), getSetup(String(tenantSlug))]);
        if (!alive) return;
        setTaxes(Array.isArray(res.taxes) ? res.taxes : []);

        const vat = typeof setupRes.tenant?.billing_vat_number === "string" ? setupRes.tenant.billing_vat_number : "";
        setBillingVatNumber(vat);

        const state = (setupRes.setup?.state ?? {}) as Record<string, unknown>;
        const taxState = (state.tax ?? {}) as Record<string, unknown>;
        setTaxRegistered(typeof taxState.tax_registered === "boolean" ? taxState.tax_registered : false);
        setInvoicePrefix(typeof taxState.invoice_prefix === "string" ? taxState.invoice_prefix : "RB");
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load taxes.");
      } finally {
        if (!alive) return;
        setLoadingTaxes(false);
      }
    }
    void load();
    return () => {
      alive = false;
    };
  }, [tenantSlug]);

  async function saveTaxMeta() {
    if (isMock) return;
    setStatus(null);
    setError(null);
    setSavingMeta(true);
    try {
      const setupRes = await getSetup(String(tenantSlug));
      const prevState = (setupRes.setup?.state ?? {}) as Record<string, unknown>;
      const prevTax = (prevState.tax ?? {}) as Record<string, unknown>;
      const nextState: Record<string, unknown> = {
        ...prevState,
        tax: {
          ...prevTax,
          tax_registered: Boolean(taxRegistered),
          invoice_prefix: invoicePrefix.trim() || null,
        },
      };

      await updateSetup(String(tenantSlug), {
        billing_vat_number: billingVatNumber.trim() || null,
        setup_state: nextState,
      });
      setStatus("Saved.");
    } catch (e) {
      setError(e instanceof Error ? e.message : "Failed to save tax settings.");
    } finally {
      setSavingMeta(false);
    }
  }

  useEffect(() => {
    function onBranchChanged() {
      setStatus(null);
      setError(null);
      setEditOpen(false);
      setAddOpen(false);
      void refresh();
    }

    if (typeof window === "undefined") return;

    window.addEventListener("rb:branch-changed", onBranchChanged);
    return () => {
      window.removeEventListener("rb:branch-changed", onBranchChanged);
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [tenantSlug]);

  function syncDraft(nextTaxes: ApiRepairBuddyTax[]) {
    const mapped: RepairBuddyTax[] = nextTaxes.map((x) => ({
      id: String(x.id),
      name: x.name,
      ratePercent: Number(x.rate),
      status: x.is_active ? "active" : "inactive",
    }));

    const defaultFromDb = nextTaxes.find((x) => x.is_default) ?? null;
    const nextIds = new Set(nextTaxes.map((x) => String(x.id)));
    const active = nextTaxes.filter((x) => x.is_active);
    const activeIds = new Set(active.map((x) => String(x.id)));

    const currentDefault = t.defaultTaxId;
    const currentIsKnown = typeof currentDefault === "string" && currentDefault.length > 0 && nextIds.has(currentDefault);
    const currentIsActive = currentIsKnown && activeIds.has(currentDefault);

    const fallbackDefault = active.length > 0 ? String(active[0]?.id) : "";
    const nextDefaultTaxId = defaultFromDb ? String(defaultFromDb.id) : currentIsActive ? currentDefault : fallbackDefault;

    updateTaxes({
      taxes: mapped,
      defaultTaxId: nextDefaultTaxId,
    });
  }

  useEffect(() => {
    syncDraft(taxes);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [taxes]);

  const activeTaxes = useMemo(() => taxes.filter((x) => x.is_active), [taxes]);

  const defaultTaxSelectValue = useMemo(() => {
    const activeIds = new Set(activeTaxes.map((x) => String(x.id)));
    if (t.defaultTaxId && activeIds.has(String(t.defaultTaxId))) return String(t.defaultTaxId);
    return "";
  }, [activeTaxes, t.defaultTaxId]);

  const filtered = useMemo(() => {
    const needle = query.trim().toLowerCase();
    if (!needle) return taxes;
    return taxes.filter((x) => `${x.id} ${x.name} ${x.rate}`.toLowerCase().includes(needle));
  }, [query, taxes]);

  const totalRows = filtered.length;
  useEffect(() => {
    const totalPages = Math.max(1, Math.ceil(totalRows / pageSize));
    const clamped = Math.min(pageIndex, totalPages - 1);
    if (clamped !== pageIndex) setPageIndex(clamped);
  }, [pageIndex, pageSize, totalRows]);

  const pageRows = useMemo(() => {
    const start = pageIndex * pageSize;
    const end = start + pageSize;
    return filtered.slice(start, end);
  }, [filtered, pageIndex, pageSize]);

  const columns = useMemo<Array<DataTableColumn<ApiRepairBuddyTax>>>(
    () => [
      { id: "name", header: "Name", cell: (x) => <div className="text-sm text-zinc-700">{x.name}</div>, className: "min-w-[220px]" },
      { id: "rate", header: "Rate (%)", cell: (x) => <div className="text-sm text-zinc-700">{Number(x.rate)}</div>, className: "whitespace-nowrap" },
      {
        id: "active",
        header: "Active",
        cell: (x) => <div className="text-sm text-zinc-700">{x.is_active ? "Yes" : "No"}</div>,
        className: "whitespace-nowrap",
      },
      {
        id: "default",
        header: "Default",
        cell: (x) => <div className="text-sm text-zinc-700">{x.is_default ? "Yes" : "No"}</div>,
        className: "whitespace-nowrap",
      },
      {
        id: "actions",
        header: "Actions",
        cell: (row) => (
          <div className="flex items-center gap-2">
            <Button
              size="sm"
              variant="outline"
              disabled={isMock || busy}
              onClick={() => {
                setEditId(String(row.id));
                setEditName(row.name);
                setEditRate(String(row.rate));
                setEditActive(row.is_active);
                setEditDefault(row.is_default);
                setEditOpen(true);
              }}
            >
              Edit
            </Button>
            <Button
              size="sm"
              variant="outline"
              disabled={isMock || busy}
              onClick={() => void onDelete(row)}
            >
              Delete
            </Button>
          </div>
        ),
        className: "whitespace-nowrap",
      },
    ],
    [busy, isMock],
  );

  const [addName, setAddName] = useState("");
  const [addRate, setAddRate] = useState("0");
  const [addActive, setAddActive] = useState(true);
  const [addDefault, setAddDefault] = useState(false);

  const [editId, setEditId] = useState<string | null>(null);
  const [editName, setEditName] = useState("");
  const [editRate, setEditRate] = useState("0");
  const [editActive, setEditActive] = useState(true);
  const [editDefault, setEditDefault] = useState(false);

  async function refresh() {
    setLoadingTaxes(true);
    try {
      const res = await getRepairBuddyTaxes(String(tenantSlug));
      setTaxes(Array.isArray(res.taxes) ? res.taxes : []);
    } catch (e) {
      setError(e instanceof Error ? e.message : "Failed to load taxes.");
    } finally {
      setLoadingTaxes(false);
    }
  }

  async function onAdd() {
    setStatus(null);
    setError(null);
    setBusy(true);
    try {
      const name = addName.trim();
      const rate = Number(addRate);
      if (!name) throw new Error("Name is required.");
      if (!Number.isFinite(rate)) throw new Error("Rate is invalid.");

      await createRepairBuddyTax(String(tenantSlug), {
        name,
        rate,
        is_active: addActive,
        is_default: addDefault,
      });
      await refresh();
      setAddOpen(false);
      setAddName("");
      setAddRate("0");
      setAddActive(true);
      setAddDefault(false);
      setStatus("Tax created.");
    } catch (e) {
      setError(e instanceof Error ? e.message : "Failed to create tax.");
    } finally {
      setBusy(false);
    }
  }

  async function onEditSave() {
    if (!editId) return;
    setStatus(null);
    setError(null);
    setBusy(true);
    try {
      const id = Number(editId);
      if (!Number.isFinite(id)) throw new Error("Invalid tax.");

      const name = editName.trim();
      const rate = Number(editRate);
      if (!name) throw new Error("Name is required.");
      if (!Number.isFinite(rate)) throw new Error("Rate is invalid.");

      await updateRepairBuddyTax(String(tenantSlug), id, { name, rate });
      await setRepairBuddyTaxActive(String(tenantSlug), id, editActive);
      if (editDefault) {
        await setRepairBuddyTaxDefault(String(tenantSlug), id);
      }

      await refresh();
      setEditOpen(false);
      setStatus("Tax updated.");
    } catch (e) {
      setError(e instanceof Error ? e.message : "Failed to update tax.");
    } finally {
      setBusy(false);
    }
  }

  async function onDelete(row: ApiRepairBuddyTax) {
    if (!globalThis.confirm(`Delete tax "${row.name}"?`)) return;
    setStatus(null);
    setError(null);
    setBusy(true);
    try {
      setTaxes((prev) => prev.filter((t) => String(t.id) !== String(row.id)));
      await deleteRepairBuddyTax(String(tenantSlug), Number(row.id));
      await refresh();
      setStatus("Tax deleted.");
    } catch (e) {
      setError(e instanceof Error ? e.message : "Failed to delete tax.");
      void refresh();
    } finally {
      setBusy(false);
    }
  }

  return (
    <SectionShell title="Taxes" description="Tax rates and invoice calculation preferences.">
      {error ? <div className="text-sm text-red-600">{error}</div> : null}
      {status ? <div className="text-sm text-green-700">{status}</div> : null}

      <Card className="shadow-none">
        <CardContent className="pt-5">
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="sm:col-span-2">
              <label className="flex items-center gap-2 text-sm">
                <input type="checkbox" checked={taxRegistered} onChange={(e) => setTaxRegistered(e.target.checked)} disabled={savingMeta || busy} />
                Tax/VAT registered?
              </label>
            </div>
            <div className="space-y-1">
              <label className="text-sm font-medium">VAT number</label>
              <Input value={billingVatNumber} onChange={(e) => setBillingVatNumber(e.target.value)} placeholder="EU123456789" disabled={savingMeta || busy} />
            </div>
            <div className="space-y-1">
              <label className="text-sm font-medium">Invoice prefix (optional)</label>
              <Input value={invoicePrefix} onChange={(e) => setInvoicePrefix(e.target.value)} placeholder="RB" disabled={savingMeta || busy} />
            </div>
            <div className="sm:col-span-2 flex items-center justify-end gap-2">
              <Button variant="outline" disabled={isMock || savingMeta || busy} onClick={() => void saveTaxMeta()}>
                {savingMeta ? "Saving..." : "Save"}
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <label className="flex items-center gap-2 text-sm">
        <input
          type="checkbox"
          checked={t.enableTaxes}
          onChange={(e) => {
            const next = e.target.checked;
            updateTaxes({ enableTaxes: next });
            void persistTaxesPrefs({ ...t, enableTaxes: next });
          }}
        />
        Enable taxes
      </label>

      <div className="flex items-center justify-between gap-3">
        <div className="text-sm text-zinc-600">Manage tax rates for this business.</div>
        <Button variant="outline" disabled={isMock || busy} onClick={() => setAddOpen(true)}>
          Add tax
        </Button>
      </div>

      <Card className="shadow-none">
        <CardContent className="pt-5">
          <DataTable
            title="Taxes"
            data={pageRows}
            columns={columns}
            getRowId={(row) => row.id}
            loading={loadingTaxes}
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
          <Select
            value={defaultTaxSelectValue}
            disabled={activeTaxes.length === 0 || busy || loadingTaxes}
            onChange={async (e) => {
              const next = e.target.value;
              updateTaxes({ defaultTaxId: next });
              void persistTaxesPrefs({ ...t, defaultTaxId: next });
              if (!next) return;
              const id = Number(next);
              if (!Number.isFinite(id) || id <= 0) return;
              try {
                setBusy(true);
                await setRepairBuddyTaxDefault(String(tenantSlug), id);
                await refresh();
              } catch (err) {
                setError(err instanceof Error ? err.message : "Failed to set default tax.");
              } finally {
                setBusy(false);
              }
            }}
          >
            <option value="">None</option>
            {activeTaxes.map((x) => (
              <option key={x.id} value={String(x.id)}>
                {x.name} ({Number(x.rate)}%)
              </option>
            ))}
          </Select>
        </div>
        <div className="space-y-1">
          <label className="text-sm font-medium">Invoice amounts</label>
          <Select
            value={t.invoiceAmounts}
            onChange={(e) => {
              const next = e.target.value as RepairBuddySettingsDraft["taxes"]["invoiceAmounts"];
              updateTaxes({ invoiceAmounts: next });
              void persistTaxesPrefs({ ...t, invoiceAmounts: next });
            }}
          >
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
            <Button disabled={isMock || busy} onClick={() => void onAdd()}>
              {busy ? "Saving..." : "Save"}
            </Button>
          </div>
        }
      >
        <div className="space-y-3">
          <div className="space-y-1">
            <label className="text-sm font-medium">Name</label>
            <Input value={addName} onChange={(e) => setAddName(e.target.value)} placeholder="e.g. VAT" />
          </div>
          <div className="space-y-1">
            <label className="text-sm font-medium">Rate (%)</label>
            <Input type="number" min={0} value={addRate} onChange={(e) => setAddRate(e.target.value)} />
          </div>
          <div className="grid gap-3 sm:grid-cols-2">
            <div className="space-y-1">
              <label className="text-sm font-medium">Active</label>
              <Select value={addActive ? "yes" : "no"} onChange={(e) => setAddActive(e.target.value === "yes")}>
                <option value="yes">Yes</option>
                <option value="no">No</option>
              </Select>
            </div>
            <div className="space-y-1">
              <label className="text-sm font-medium">Default</label>
              <Select value={addDefault ? "yes" : "no"} onChange={(e) => setAddDefault(e.target.value === "yes")}>
                <option value="no">No</option>
                <option value="yes">Yes</option>
              </Select>
            </div>
          </div>
        </div>
      </Modal>

      <Modal
        open={editOpen}
        onClose={() => setEditOpen(false)}
        title="Edit tax"
        footer={
          <div className="flex items-center justify-end gap-2">
            <Button variant="outline" onClick={() => setEditOpen(false)}>
              Close
            </Button>
            <Button disabled={isMock || busy} onClick={() => void onEditSave()}>
              {busy ? "Saving..." : "Save"}
            </Button>
          </div>
        }
      >
        <div className="space-y-3">
          <div className="space-y-1">
            <label className="text-sm font-medium">Name</label>
            <Input value={editName} onChange={(e) => setEditName(e.target.value)} />
          </div>
          <div className="space-y-1">
            <label className="text-sm font-medium">Rate (%)</label>
            <Input type="number" min={0} value={editRate} onChange={(e) => setEditRate(e.target.value)} />
          </div>
          <div className="grid gap-3 sm:grid-cols-2">
            <div className="space-y-1">
              <label className="text-sm font-medium">Active</label>
              <Select value={editActive ? "yes" : "no"} onChange={(e) => setEditActive(e.target.value === "yes")}>
                <option value="yes">Yes</option>
                <option value="no">No</option>
              </Select>
            </div>
            <div className="space-y-1">
              <label className="text-sm font-medium">Default</label>
              <Select value={editDefault ? "yes" : "no"} onChange={(e) => setEditDefault(e.target.value === "yes")}>
                <option value="no">No</option>
                <option value="yes">Yes</option>
              </Select>
            </div>
          </div>
        </div>
      </Modal>
    </SectionShell>
  );
}
