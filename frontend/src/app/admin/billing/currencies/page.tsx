"use client";

import React, { useEffect, useMemo, useState } from "react";
import { RequireAuth } from "@/components/RequireAuth";
import { useDashboardHeader } from "@/components/DashboardShell";
import { Alert } from "@/components/ui/Alert";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/Card";
import { ConfirmDialog } from "@/components/ui/ConfirmDialog";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { Input } from "@/components/ui/Input";
import { Modal } from "@/components/ui/Modal";
import { apiFetch, ApiError } from "@/lib/api";
import { useAuth } from "@/lib/auth";

type CurrencyRow = { code: string; symbol: string; name: string; is_active: boolean };

type AdminSettingsCurrenciesPayload = {
  currencies: { code: string; symbol: string | null; name: string; is_active: boolean }[];
};

export default function AdminBillingCurrenciesPage() {
  const auth = useAuth();
  const dashboardHeader = useDashboardHeader();

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [status, setStatus] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  const [query, setQuery] = useState("");

  const [currenciesDraft, setCurrenciesDraft] = useState<CurrencyRow[]>([]);
  const [currenciesOriginal, setCurrenciesOriginal] = useState<CurrencyRow[]>([]);

  const [createOpen, setCreateOpen] = useState(false);
  const [newCurrencyCode, setNewCurrencyCode] = useState("");
  const [newCurrencySymbol, setNewCurrencySymbol] = useState("");
  const [newCurrencyName, setNewCurrencyName] = useState("");
  const [newCurrencyActive, setNewCurrencyActive] = useState(true);

  const [removeCode, setRemoveCode] = useState<string | null>(null);

  const canWrite = auth.can("admin.billing.write");

  function normalizeCurrencies(list: CurrencyRow[]) {
    return (Array.isArray(list) ? list : [])
      .map((c) => ({
        code: (c.code ?? "").trim().toUpperCase(),
        symbol: (c.symbol ?? "").trim(),
        name: (c.name ?? "").trim(),
        is_active: Boolean(c.is_active),
      }))
      .filter((c) => c.code.length > 0)
      .sort((a, b) => a.code.localeCompare(b.code));
  }

  const reload = React.useCallback(async () => {
    try {
      setError(null);
      setStatus(null);
      setLoading(true);

      const res = await apiFetch<AdminSettingsCurrenciesPayload>("/api/admin/settings");

      const next =
        Array.isArray(res.currencies)
          ? res.currencies.map((c) => ({
              code: (c.code ?? "").toUpperCase(),
              symbol: c.symbol ?? "",
              name: c.name ?? "",
              is_active: Boolean(c.is_active),
            }))
          : [];

      const normalized = normalizeCurrencies(next);
      setCurrenciesDraft(normalized);
      setCurrenciesOriginal(normalized);
    } catch (e) {
      setError(e instanceof Error ? e.message : "Failed to load currencies.");
      setCurrenciesDraft([]);
      setCurrenciesOriginal([]);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    dashboardHeader.setHeader({
      breadcrumb: "Admin / Billing",
      title: "Currencies",
      subtitle: "Manage supported currencies (code, symbol, name, status)",
      actions: (
        <div className="flex items-center gap-2">
          {canWrite ? (
            <Button
              variant="secondary"
              size="sm"
              onClick={() => {
                setNewCurrencyCode("");
                setNewCurrencySymbol("");
                setNewCurrencyName("");
                setNewCurrencyActive(true);
                setCreateOpen(true);
              }}
              disabled={loading || busy}
            >
              Add currency
            </Button>
          ) : null}
          <Button variant="outline" size="sm" onClick={() => void reload()} disabled={loading || busy}>
            Refresh
          </Button>
        </div>
      ),
    });

    return () => dashboardHeader.setHeader(null);
  }, [busy, canWrite, dashboardHeader, loading, reload]);

  useEffect(() => {
    void reload();
  }, [reload]);

  function addCurrencyRow() {
    const code = newCurrencyCode.trim().toUpperCase();
    if (!/^[A-Z]{3}$/.test(code)) return;
    const name = newCurrencyName.trim();
    if (!name) return;

    const next: CurrencyRow = {
      code,
      symbol: newCurrencySymbol.trim(),
      name,
      is_active: Boolean(newCurrencyActive),
    };

    setCurrenciesDraft((prev) => {
      const filtered = prev.filter((r) => r.code !== code);
      return [...filtered, next].sort((a, b) => a.code.localeCompare(b.code));
    });

    setNewCurrencyCode("");
    setNewCurrencySymbol("");
    setNewCurrencyName("");
    setNewCurrencyActive(true);
  }

  function removeCurrencyRow(code: string) {
    setCurrenciesDraft((prev) => prev.filter((r) => r.code !== code));
  }

  const updateCurrencyRow = React.useCallback((code: string, patch: Partial<CurrencyRow>) => {
    setCurrenciesDraft((prev) => prev.map((r) => (r.code === code ? { ...r, ...patch } : r)));
  }, []);

  const isDirty = useMemo(() => {
    const a = JSON.stringify(normalizeCurrencies(currenciesDraft));
    const b = JSON.stringify(normalizeCurrencies(currenciesOriginal));
    return a !== b;
  }, [currenciesDraft, currenciesOriginal]);

  async function onSaveCurrencies() {
    if (!canWrite) return;
    if (!isDirty) return;

    setError(null);
    setStatus(null);
    setBusy(true);

    try {
      const res = await apiFetch<AdminSettingsCurrenciesPayload>("/api/admin/settings", {
        method: "PATCH",
        body: {
          currencies: normalizeCurrencies(currenciesDraft).map((c) => ({
            code: c.code.trim().toUpperCase(),
            symbol: c.symbol.trim() ? c.symbol.trim() : null,
            name: c.name.trim(),
            is_active: Boolean(c.is_active),
          })),
        },
      });

      const next =
        Array.isArray(res.currencies)
          ? res.currencies.map((c) => ({
              code: (c.code ?? "").toUpperCase(),
              symbol: c.symbol ?? "",
              name: c.name ?? "",
              is_active: Boolean(c.is_active),
            }))
          : [];

      const normalized = normalizeCurrencies(next);
      setCurrenciesDraft(normalized);
      setCurrenciesOriginal(normalized);

      setStatus("Currencies updated.");
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError("Failed to update currencies.");
      }
    } finally {
      setBusy(false);
    }
  }

  const canAddCurrency = useMemo(() => {
    return /^[A-Z]{3}$/.test(newCurrencyCode.trim().toUpperCase()) && Boolean(newCurrencyName.trim());
  }, [newCurrencyCode, newCurrencyName]);

  const filteredDraft = useMemo(() => {
    const q = query.trim().toLowerCase();
    if (!q) return currenciesDraft;
    return currenciesDraft.filter((c) => {
      const text = `${c.code} ${c.symbol} ${c.name}`.toLowerCase();
      return text.includes(q);
    });
  }, [currenciesDraft, query]);

  const columns = useMemo((): Array<DataTableColumn<CurrencyRow>> => {
    return [
      {
        id: "code",
        header: "Code",
        cell: (row) => <span className="font-mono text-sm">{row.code}</span>,
      },
      {
        id: "name",
        header: "Name",
        cell: (row) => (
          <Input
            value={row.name}
            onChange={(e) => updateCurrencyRow(row.code, { name: e.target.value })}
            disabled={busy || !canWrite}
          />
        ),
        className: "min-w-[240px]",
      },
      {
        id: "symbol",
        header: "Symbol",
        cell: (row) => (
          <Input
            value={row.symbol}
            onChange={(e) => updateCurrencyRow(row.code, { symbol: e.target.value })}
            disabled={busy || !canWrite}
          />
        ),
        className: "min-w-[140px]",
      },
      {
        id: "status",
        header: "Status",
        cell: (row) => (
          <div className="flex items-center gap-2">
            <Badge variant={row.is_active ? "success" : "default"}>{row.is_active ? "Active" : "Inactive"}</Badge>
            <label className="flex items-center gap-2 text-sm text-zinc-700">
              <input
                type="checkbox"
                checked={row.is_active}
                onChange={(e) => updateCurrencyRow(row.code, { is_active: e.target.checked })}
                disabled={busy || !canWrite}
              />
            </label>
          </div>
        ),
        className: "min-w-[200px]",
      },
      {
        id: "actions",
        header: "",
        cell: (row) => (
          <div className="flex justify-end">
            <Button
              type="button"
              variant="ghost"
              onClick={() => setRemoveCode(row.code)}
              disabled={busy || !canWrite}
            >
              Remove
            </Button>
          </div>
        ),
        className: "w-[1%] whitespace-nowrap",
      },
    ];
  }, [busy, canWrite, updateCurrencyRow]);

  return (
    <RequireAuth requiredPermission="admin.billing.read">
      <div className="space-y-6">
        {error ? (
          <Alert variant="danger" title="Something went wrong">
            {error}
          </Alert>
        ) : null}

        {status ? (
          <Alert variant="success" title="Status">
            {status}
          </Alert>
        ) : null}

        {loading ? <div className="text-sm text-zinc-500">Loading currencies...</div> : null}

        <div className="grid grid-cols-1 gap-4">
          <Card>
            <CardHeader>
              <CardTitle>Currencies</CardTitle>
              <CardDescription>Manage supported currencies (code, symbol, name, status).</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                  <div className="space-y-1">
                    <label className="text-sm font-medium" htmlFor="currency_search">
                      Search
                    </label>
                    <Input
                      id="currency_search"
                      value={query}
                      onChange={(e) => setQuery(e.target.value)}
                      placeholder="Search by code, name, symbol..."
                      disabled={loading}
                    />
                  </div>

                  <div className="text-sm text-zinc-600 sm:pt-6">{filteredDraft.length} currency(ies)</div>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                  {isDirty ? <Badge variant="warning">Unsaved changes</Badge> : <Badge variant="success">Saved</Badge>}
                  <Button
                    type="button"
                    variant="outline"
                    onClick={() => void reload()}
                    disabled={loading || busy}
                  >
                    Discard changes
                  </Button>
                  <Button
                    type="button"
                    onClick={() => void onSaveCurrencies()}
                    disabled={busy || loading || !canWrite || !isDirty}
                  >
                    {busy ? "Saving..." : "Save changes"}
                  </Button>
                </div>
              </div>

              {!canWrite ? <div className="text-sm text-zinc-600">You have read-only access.</div> : null}

              <DataTable
                data={filteredDraft}
                columns={columns}
                getRowId={(row) => row.code}
                loading={loading}
                emptyMessage={query.trim().length > 0 ? "No currencies match your search." : "No currencies configured."}
                className="pt-1"
              />
            </CardContent>
          </Card>
        </div>

        <Modal
          open={createOpen}
          onClose={() => {
            if (!busy) setCreateOpen(false);
          }}
          title="Add currency"
          footer={
            <div className="flex items-center justify-end gap-2">
              <Button
                variant="outline"
                onClick={() => setCreateOpen(false)}
                disabled={busy}
              >
                Cancel
              </Button>
              <Button
                variant="secondary"
                onClick={() => {
                  addCurrencyRow();
                  setCreateOpen(false);
                }}
                disabled={busy || !canWrite || !canAddCurrency}
              >
                Add
              </Button>
            </div>
          }
        >
          <div className="space-y-3">
            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <div className="space-y-1">
                <label className="text-sm font-medium" htmlFor="currency_code_new">
                  Code
                </label>
                <Input
                  id="currency_code_new"
                  value={newCurrencyCode}
                  onChange={(e) => setNewCurrencyCode(e.target.value.toUpperCase().replace(/[^A-Z]/g, "").slice(0, 3))}
                  type="text"
                  placeholder="USD"
                  disabled={busy || !canWrite}
                />
              </div>
              <div className="space-y-1">
                <label className="text-sm font-medium" htmlFor="currency_symbol_new">
                  Symbol
                </label>
                <Input
                  id="currency_symbol_new"
                  value={newCurrencySymbol}
                  onChange={(e) => setNewCurrencySymbol(e.target.value)}
                  type="text"
                  placeholder="$"
                  disabled={busy || !canWrite}
                />
              </div>
            </div>
            <div className="space-y-1">
              <label className="text-sm font-medium" htmlFor="currency_name_new">
                Name
              </label>
              <Input
                id="currency_name_new"
                value={newCurrencyName}
                onChange={(e) => setNewCurrencyName(e.target.value)}
                type="text"
                placeholder="US Dollar"
                disabled={busy || !canWrite}
              />
            </div>
            <div className="space-y-1">
              <div className="text-sm font-medium">Status</div>
              <label className="flex h-10 items-center gap-2 rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 text-sm text-[var(--rb-text)]">
                <input
                  type="checkbox"
                  checked={newCurrencyActive}
                  onChange={(e) => setNewCurrencyActive(e.target.checked)}
                  disabled={busy || !canWrite}
                />
                Active
              </label>
            </div>
          </div>
        </Modal>

        <ConfirmDialog
          open={removeCode !== null}
          title="Remove currency"
          message={<span>Remove currency <span className="font-mono">{removeCode}</span> from the supported list?</span>}
          confirmText="Remove"
          confirmVariant="outline"
          busy={busy}
          onCancel={() => setRemoveCode(null)}
          onConfirm={() => {
            if (removeCode) removeCurrencyRow(removeCode);
            setRemoveCode(null);
          }}
        />
      </div>
    </RequireAuth>
  );
}
