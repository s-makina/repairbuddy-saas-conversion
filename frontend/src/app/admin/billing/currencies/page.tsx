"use client";

import React, { useEffect, useMemo, useState } from "react";
import { RequireAuth } from "@/components/RequireAuth";
import { useDashboardHeader } from "@/components/DashboardShell";
import { Alert } from "@/components/ui/Alert";
import { Button } from "@/components/ui/Button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/Card";
import { Input } from "@/components/ui/Input";
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

  const [currenciesDraft, setCurrenciesDraft] = useState<CurrencyRow[]>([]);
  const [newCurrencyCode, setNewCurrencyCode] = useState("");
  const [newCurrencySymbol, setNewCurrencySymbol] = useState("");
  const [newCurrencyName, setNewCurrencyName] = useState("");
  const [newCurrencyActive, setNewCurrencyActive] = useState(true);

  const canWrite = auth.can("admin.billing.write");

  const reload = React.useCallback(async () => {
    try {
      setError(null);
      setStatus(null);
      setLoading(true);

      const res = await apiFetch<AdminSettingsCurrenciesPayload>("/api/admin/settings");

      setCurrenciesDraft(
        Array.isArray(res.currencies)
          ? res.currencies.map((c) => ({
              code: (c.code ?? "").toUpperCase(),
              symbol: c.symbol ?? "",
              name: c.name ?? "",
              is_active: Boolean(c.is_active),
            }))
          : [],
      );
    } catch (e) {
      setError(e instanceof Error ? e.message : "Failed to load currencies.");
      setCurrenciesDraft([]);
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
          <Button variant="outline" size="sm" onClick={() => void reload()} disabled={loading || busy}>
            Refresh
          </Button>
        </div>
      ),
    });

    return () => dashboardHeader.setHeader(null);
  }, [busy, dashboardHeader, loading, reload]);

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

  function updateCurrencyRow(code: string, patch: Partial<CurrencyRow>) {
    setCurrenciesDraft((prev) => prev.map((r) => (r.code === code ? { ...r, ...patch } : r)));
  }

  async function onSaveCurrencies(e: React.FormEvent) {
    e.preventDefault();
    if (!canWrite) return;

    setError(null);
    setStatus(null);
    setBusy(true);

    try {
      const res = await apiFetch<AdminSettingsCurrenciesPayload>("/api/admin/settings", {
        method: "PATCH",
        body: {
          currencies: currenciesDraft.map((c) => ({
            code: c.code.trim().toUpperCase(),
            symbol: c.symbol.trim() ? c.symbol.trim() : null,
            name: c.name.trim(),
            is_active: Boolean(c.is_active),
          })),
        },
      });

      setCurrenciesDraft(
        Array.isArray(res.currencies)
          ? res.currencies.map((c) => ({
              code: (c.code ?? "").toUpperCase(),
              symbol: c.symbol ?? "",
              name: c.name ?? "",
              is_active: Boolean(c.is_active),
            }))
          : [],
      );

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
              <form className="space-y-3" onSubmit={onSaveCurrencies}>
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-4">
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
                      <input type="checkbox" checked={newCurrencyActive} onChange={(e) => setNewCurrencyActive(e.target.checked)} disabled={busy || !canWrite} />
                      Active
                    </label>
                  </div>
                </div>

                <div className="flex flex-wrap gap-2">
                  <Button type="button" variant="outline" onClick={() => addCurrencyRow()} disabled={busy || !canWrite || !canAddCurrency}>
                    Add currency
                  </Button>
                  <Button type="submit" disabled={busy || !canWrite}>
                    {busy ? "Saving..." : "Save changes"}
                  </Button>
                </div>
              </form>

              {!canWrite ? <div className="text-sm text-zinc-600">You have read-only access.</div> : null}

              <div className="space-y-2">
                {currenciesDraft.length === 0 ? <div className="text-sm text-zinc-600">—</div> : null}
                {currenciesDraft.map((c) => (
                  <div key={c.code} className="grid grid-cols-1 gap-2 rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)] bg-white p-3 sm:grid-cols-12 sm:items-end">
                    <div className="sm:col-span-2">
                      <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Code</div>
                      <div className="mt-1 text-sm font-semibold text-[var(--rb-text)]">{c.code}</div>
                    </div>
                    <div className="sm:col-span-3">
                      <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Symbol</div>
                      <Input value={c.symbol} onChange={(e) => updateCurrencyRow(c.code, { symbol: e.target.value })} disabled={busy || !canWrite} />
                    </div>
                    <div className="sm:col-span-5">
                      <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Name</div>
                      <Input value={c.name} onChange={(e) => updateCurrencyRow(c.code, { name: e.target.value })} disabled={busy || !canWrite} />
                    </div>
                    <div className="sm:col-span-1">
                      <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Active</div>
                      <label className="mt-2 flex items-center justify-center gap-2">
                        <input type="checkbox" checked={c.is_active} onChange={(e) => updateCurrencyRow(c.code, { is_active: e.target.checked })} disabled={busy || !canWrite} />
                      </label>
                    </div>
                    <div className="sm:col-span-1 sm:flex sm:justify-end">
                      <Button type="button" variant="ghost" onClick={() => removeCurrencyRow(c.code)} disabled={busy || !canWrite}>
                        Remove
                      </Button>
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </RequireAuth>
  );
}
