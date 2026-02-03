"use client";

import React, { useEffect, useMemo, useState } from "react";
import { useParams } from "next/navigation";
import { Input } from "@/components/ui/Input";
import { Select } from "@/components/ui/Select";
import { Button } from "@/components/ui/Button";
import { Badge } from "@/components/ui/Badge";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/Card";
import { DropdownMenu, DropdownMenuItem, DropdownMenuSeparator } from "@/components/ui/DropdownMenu";
import { Modal } from "@/components/ui/Modal";
import { ConfirmDialog } from "@/components/ui/ConfirmDialog";
import { Alert } from "@/components/ui/Alert";
import type { RepairBuddySettingsDraft } from "@/app/app/[tenant]/settings/_components/repairbuddy/types";
import { ApiError } from "@/lib/api";
import { getTenantCurrencies, updateTenantCurrencies, type TenantCurrency } from "@/lib/tenant-currencies";

export function CurrencySection({
  draft,
  updateCurrency,
}: {
  draft: RepairBuddySettingsDraft;
  updateCurrency: (patch: Partial<RepairBuddySettingsDraft["currency"]>) => void;
}) {
  const c = draft.currency;

  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loadingCurrencies, setLoadingCurrencies] = useState(true);
  const [currenciesError, setCurrenciesError] = useState<string | null>(null);
  const [currenciesStatus, setCurrenciesStatus] = useState<string | null>(null);
  const [busyCurrencies, setBusyCurrencies] = useState(false);

  const [currenciesDraft, setCurrenciesDraft] = useState<TenantCurrency[]>([]);
  const [currenciesOriginal, setCurrenciesOriginal] = useState<TenantCurrency[]>([]);

  const [createOpen, setCreateOpen] = useState(false);
  const [newCode, setNewCode] = useState("");
  const [newSymbol, setNewSymbol] = useState("");
  const [newName, setNewName] = useState("");
  const [newActive, setNewActive] = useState(true);
  const [newDefault, setNewDefault] = useState(false);

  const [removeCode, setRemoveCode] = useState<string | null>(null);

  function normalize(list: TenantCurrency[]) {
    const next = (Array.isArray(list) ? list : [])
      .map((r) => ({
        code: (r.code ?? "").trim().toUpperCase(),
        symbol: r.symbol ?? null,
        name: (r.name ?? "").trim(),
        is_active: Boolean(r.is_active),
        is_default: Boolean(r.is_default),
      }))
      .filter((r) => /^[A-Z]{3}$/.test(r.code) && r.name.length > 0);

    const hasDefault = next.some((r) => r.is_default && r.is_active);
    if (!hasDefault) {
      const firstActive = next.find((r) => r.is_active);
      if (firstActive) firstActive.is_default = true;
      else if (next[0]) next[0].is_default = true;
    }

    let defaultSeen = false;
    return next
      .map((r) => {
        const v = { ...r };
        if (!v.is_active) v.is_default = false;
        if (v.is_default) {
          if (defaultSeen) v.is_default = false;
          defaultSeen = true;
        }
        return v;
      })
      .sort((a, b) => a.code.localeCompare(b.code));
  }

  const reloadCurrencies = React.useCallback(async () => {
    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;

    setLoadingCurrencies(true);
    setCurrenciesError(null);
    setCurrenciesStatus(null);

    try {
      const res = await getTenantCurrencies(String(tenantSlug));
      const normalized = normalize(Array.isArray(res.currencies) ? res.currencies : []);
      setCurrenciesDraft(normalized);
      setCurrenciesOriginal(normalized);

      const defaultCode = normalized.find((x) => x.is_default)?.code ?? null;
      if (defaultCode && /^[A-Z]{3}$/.test(defaultCode)) {
        updateCurrency({ currency: defaultCode });
      }
    } catch (e) {
      setCurrenciesError(e instanceof Error ? e.message : "Failed to load currencies.");
      setCurrenciesDraft([]);
      setCurrenciesOriginal([]);
    } finally {
      setLoadingCurrencies(false);
    }
  }, [tenantSlug, updateCurrency]);

  useEffect(() => {
    void reloadCurrencies();
  }, [reloadCurrencies]);

  const isDirtyCurrencies = useMemo(() => {
    return JSON.stringify(normalize(currenciesDraft)) !== JSON.stringify(normalize(currenciesOriginal));
  }, [currenciesDraft, currenciesOriginal]);

  const defaultCode = useMemo(() => {
    return currenciesDraft.find((x) => x.is_default)?.code ?? "";
  }, [currenciesDraft]);

  const activeCurrencies = useMemo(() => {
    return currenciesDraft.filter((x) => x.is_active);
  }, [currenciesDraft]);

  const updateRow = React.useCallback((code: string, patch: Partial<TenantCurrency>) => {
    setCurrenciesDraft((prev) => {
      const next = prev.map((r) => (r.code === code ? { ...r, ...patch } : r));

      if (patch.is_default) {
        return next.map((r) => (r.code === code ? { ...r, is_default: true, is_active: true } : { ...r, is_default: false }));
      }

      if (patch.is_active === false) {
        return next.map((r) => (r.code === code ? { ...r, is_active: false, is_default: false } : r));
      }

      return next;
    });
  }, []);

  const codeExists = useMemo(() => {
    const code = newCode.trim().toUpperCase();
    if (!/^[A-Z]{3}$/.test(code)) return false;
    return currenciesDraft.some((r) => r.code === code);
  }, [currenciesDraft, newCode]);

  function addRow() {
    const code = newCode.trim().toUpperCase();
    if (!/^[A-Z]{3}$/.test(code)) return;
    const name = newName.trim();
    if (!name) return;
    if (currenciesDraft.some((r) => r.code === code)) return;

    const next: TenantCurrency = {
      code,
      symbol: newSymbol.trim() ? newSymbol.trim() : null,
      name,
      is_active: Boolean(newActive),
      is_default: Boolean(newDefault),
    };

    setCurrenciesDraft((prev) => {
      const filtered = prev.filter((r) => r.code !== code);
      let combined = [...filtered, next];

      if (next.is_default) {
        combined = combined.map((r) => (r.code === code ? { ...r, is_default: true, is_active: true } : { ...r, is_default: false }));
      }

      return normalize(combined);
    });

    setNewCode("");
    setNewSymbol("");
    setNewName("");
    setNewActive(true);
    setNewDefault(false);
  }

  function removeRow(code: string) {
    setCurrenciesDraft((prev) => normalize(prev.filter((r) => r.code !== code)));
  }

  async function onSaveCurrencies() {
    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
    if (!isDirtyCurrencies) return;

    setCurrenciesError(null);
    setCurrenciesStatus(null);
    setBusyCurrencies(true);

    try {
      const normalized = normalize(currenciesDraft);
      const res = await updateTenantCurrencies(
        String(tenantSlug),
        normalized.map((r) => ({
          code: r.code,
          symbol: r.symbol ?? null,
          name: r.name,
          is_active: Boolean(r.is_active),
          is_default: Boolean(r.is_default),
        })),
      );

      const next = normalize(Array.isArray(res.currencies) ? res.currencies : []);
      setCurrenciesDraft(next);
      setCurrenciesOriginal(next);

      const defaultCode = next.find((x) => x.is_default)?.code ?? null;
      if (defaultCode && /^[A-Z]{3}$/.test(defaultCode)) {
        updateCurrency({ currency: defaultCode });
      }

      setCurrenciesStatus("Currencies updated.");
    } catch (e) {
      if (e instanceof ApiError) setCurrenciesError(e.message);
      else setCurrenciesError(e instanceof Error ? e.message : "Failed to save currencies.");
    } finally {
      setBusyCurrencies(false);
    }
  }

  const canAdd = useMemo(() => {
    return /^[A-Z]{3}$/.test(newCode.trim().toUpperCase()) && Boolean(newName.trim()) && !codeExists;
  }, [codeExists, newCode, newName]);

  const separatorsConflict = useMemo(() => {
    const a = (c.thousandSeparator ?? "").trim();
    const b = (c.decimalSeparator ?? "").trim();
    return a.length > 0 && b.length > 0 && a === b;
  }, [c.decimalSeparator, c.thousandSeparator]);

  return (
    <div className="space-y-4">
      <Card>
        <CardHeader>
          <CardTitle>Business currencies</CardTitle>
          <CardDescription>Manage currencies for this business. Set the default from the Actions menu.</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
      {currenciesError ? (
        <Alert variant="danger" title="Could not load currencies">
          {currenciesError}
        </Alert>
      ) : null}

      {currenciesStatus ? (
        <Alert variant="success" title="Status">
          {currenciesStatus}
        </Alert>
      ) : null}

      <div className="flex flex-wrap items-center justify-between gap-2">
        <div className="flex flex-wrap items-center gap-2">
          {isDirtyCurrencies ? <Badge variant="warning">Unsaved changes</Badge> : <Badge variant="success">Saved</Badge>}
          {defaultCode ? <Badge variant="info">Default: {defaultCode}</Badge> : null}
        </div>

        <div className="flex flex-wrap items-center gap-2">
          <Button type="button" variant="outline" size="sm" onClick={() => void reloadCurrencies()} disabled={loadingCurrencies || busyCurrencies}>
            Refresh
          </Button>
          <Button
            type="button"
            variant="outline"
            size="sm"
            onClick={() => {
              setCurrenciesDraft(normalize(currenciesOriginal));
              setCurrenciesError(null);
              setCurrenciesStatus(null);
            }}
            disabled={loadingCurrencies || busyCurrencies || !isDirtyCurrencies}
          >
            Reset
          </Button>
          <Button type="button" variant="secondary" size="sm" onClick={() => void onSaveCurrencies()} disabled={loadingCurrencies || busyCurrencies || !isDirtyCurrencies}>
            {busyCurrencies ? "Saving..." : "Save"}
          </Button>
          <Button
            type="button"
            variant="outline"
            size="sm"
            onClick={() => {
              setNewCode("");
              setNewSymbol("");
              setNewName("");
              setNewActive(true);
              setNewDefault(false);
              setCreateOpen(true);
            }}
            disabled={loadingCurrencies || busyCurrencies}
          >
            Add currency
          </Button>
        </div>
      </div>

      {loadingCurrencies ? <div className="text-sm text-zinc-500">Loading currencies...</div> : null}

      {!loadingCurrencies ? (
        <div className="overflow-x-auto rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white">
          <table className="min-w-full border-collapse text-left text-sm">
            <thead className="bg-[var(--rb-surface-muted)]">
              <tr>
                <th className="whitespace-nowrap border-b border-[var(--rb-border)] px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-600">Code</th>
                <th className="whitespace-nowrap border-b border-[var(--rb-border)] px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-600">Name</th>
                <th className="whitespace-nowrap border-b border-[var(--rb-border)] px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-600">Symbol</th>
                <th className="whitespace-nowrap border-b border-[var(--rb-border)] px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-600">Status</th>
                <th className="whitespace-nowrap border-b border-[var(--rb-border)] px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-600" />
              </tr>
            </thead>
            <tbody>
              {currenciesDraft.length === 0 ? (
                <tr>
                  <td className="px-3 py-3 text-sm text-zinc-600" colSpan={5}>
                    No currencies configured. Add a currency to get started.
                  </td>
                </tr>
              ) : (
                currenciesDraft.map((row) => (
                  <tr key={row.code} className="border-t border-[var(--rb-border)]">
                    <td className="px-3 py-2 align-middle">
                      <div className="flex items-center gap-2">
                        <span className="font-mono text-sm">{row.code}</span>
                        {row.is_default ? <Badge variant="info">Default</Badge> : null}
                      </div>
                    </td>
                    <td className="px-3 py-2 align-middle">
                      <Input value={row.name} onChange={(e) => updateRow(row.code, { name: e.target.value })} disabled={busyCurrencies} placeholder="Currency name" />
                    </td>
                    <td className="px-3 py-2 align-middle">
                      <Input value={row.symbol ?? ""} onChange={(e) => updateRow(row.code, { symbol: e.target.value })} disabled={busyCurrencies} placeholder="$" />
                    </td>
                    <td className="px-3 py-2 align-middle">
                      <Badge variant={row.is_active ? "success" : "default"}>{row.is_active ? "Active" : "Inactive"}</Badge>
                    </td>
                    <td className="px-3 py-2 align-middle text-right">
                      <DropdownMenu
                        align="right"
                        trigger={({ toggle }) => (
                          <Button variant="outline" size="sm" onClick={toggle} disabled={busyCurrencies}>
                            Actions
                          </Button>
                        )}
                      >
                        {({ close }) => (
                          <>
                            <DropdownMenuItem
                              onSelect={() => {
                                close();
                                updateRow(row.code, { is_default: true });
                              }}
                              disabled={busyCurrencies || row.is_default || !row.is_active}
                            >
                              Set default
                            </DropdownMenuItem>
                            <DropdownMenuItem
                              onSelect={() => {
                                close();
                                updateRow(row.code, { is_active: !row.is_active });
                              }}
                              disabled={busyCurrencies || row.is_default}
                            >
                              {row.is_active ? "Disable" : "Enable"}
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                              onSelect={() => {
                                close();
                                setRemoveCode(row.code);
                              }}
                              destructive
                              disabled={busyCurrencies || row.is_default}
                            >
                              Remove
                            </DropdownMenuItem>
                          </>
                        )}
                      </DropdownMenu>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      ) : null}

      {activeCurrencies.length === 0 && !loadingCurrencies ? (
        <Alert variant="warning" title="No active currency">
          Enable at least one currency so you can set a default.
        </Alert>
      ) : null}

      </CardContent>
      </Card>

      <Modal
        open={createOpen}
        onClose={() => {
          if (!busyCurrencies) setCreateOpen(false);
        }}
        title="Add currency"
        footer={
          <div className="flex items-center justify-end gap-2">
            <Button variant="outline" onClick={() => setCreateOpen(false)} disabled={busyCurrencies}>
              Cancel
            </Button>
            <Button
              variant="secondary"
              onClick={() => {
                addRow();
                setCreateOpen(false);
              }}
              disabled={busyCurrencies || !canAdd}
            >
              Add
            </Button>
          </div>
        }
      >
        <div className="space-y-3">
          <Alert variant="info" title="Tip">
            Add a 3-letter ISO code (example: USD). You can set it active immediately, and optionally set it as the default.
          </Alert>

          <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div className="space-y-1">
              <label className="text-sm font-medium" htmlFor="currency_code_new">
                Code
              </label>
              <Input
                id="currency_code_new"
                value={newCode}
                onChange={(e) => setNewCode(e.target.value.toUpperCase().replace(/[^A-Z]/g, "").slice(0, 3))}
                type="text"
                placeholder="USD"
                disabled={busyCurrencies}
              />
              {codeExists ? <div className="text-xs text-zinc-500">This currency is already added.</div> : null}
            </div>
            <div className="space-y-1">
              <label className="text-sm font-medium" htmlFor="currency_symbol_new">
                Symbol
              </label>
              <Input
                id="currency_symbol_new"
                value={newSymbol}
                onChange={(e) => setNewSymbol(e.target.value)}
                type="text"
                placeholder="$"
                disabled={busyCurrencies}
              />
            </div>
          </div>
          <div className="space-y-1">
            <label className="text-sm font-medium" htmlFor="currency_name_new">
              Name
            </label>
            <Input
              id="currency_name_new"
              value={newName}
              onChange={(e) => setNewName(e.target.value)}
              type="text"
              placeholder="US Dollar"
              disabled={busyCurrencies}
            />
          </div>
          <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div className="space-y-1">
              <div className="text-sm font-medium">Active</div>
              <label className="flex h-10 items-center gap-2 rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 text-sm text-[var(--rb-text)]">
                <input type="checkbox" checked={newActive} onChange={(e) => setNewActive(e.target.checked)} disabled={busyCurrencies || newDefault} />
                Active
              </label>
            </div>
            <div className="space-y-1">
              <div className="text-sm font-medium">Default</div>
              <label className="flex h-10 items-center gap-2 rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 text-sm text-[var(--rb-text)]">
                <input
                  type="checkbox"
                  checked={newDefault}
                  onChange={(e) => {
                    const v = e.target.checked;
                    setNewDefault(v);
                    if (v) setNewActive(true);
                  }}
                  disabled={busyCurrencies}
                />
                Set as default
              </label>
            </div>
          </div>
        </div>
      </Modal>

      <ConfirmDialog
        open={removeCode !== null}
        title="Remove currency"
        message={
          <span>
            Remove currency <span className="font-mono">{removeCode}</span> from this business?
          </span>
        }
        confirmText="Remove"
        confirmVariant="outline"
        busy={busyCurrencies}
        onCancel={() => setRemoveCode(null)}
        onConfirm={() => {
          if (removeCode) removeRow(removeCode);
          setRemoveCode(null);
        }}
      />

      <Card>
        <CardHeader>
          <CardTitle>Currency formatting</CardTitle>
          <CardDescription>Formatting changes are saved with the main “Save” button at the top of this page.</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          {separatorsConflict ? (
            <Alert variant="warning" title="Check your separators">
              Thousand and decimal separators are the same. This can make numbers ambiguous.
            </Alert>
          ) : null}

          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1">
              <label className="text-sm font-medium">Default currency code</label>
              <Input value={defaultCode || c.currency} placeholder="USD" disabled />
            </div>
            <div className="space-y-1">
              <label className="text-sm font-medium">Currency position</label>
              <Select
                value={c.currencyPosition}
                onChange={(e) => updateCurrency({ currencyPosition: e.target.value as RepairBuddySettingsDraft["currency"]["currencyPosition"] })}
              >
                <option value="left">Left</option>
                <option value="right">Right</option>
                <option value="left_space">Left (space)</option>
                <option value="right_space">Right (space)</option>
              </Select>
            </div>
            <div className="space-y-1">
              <label className="text-sm font-medium">Thousand separator</label>
              <Input
                value={c.thousandSeparator}
                onChange={(e) => updateCurrency({ thousandSeparator: e.target.value.slice(0, 1) })}
                placeholder="," 
                maxLength={1}
              />
            </div>
            <div className="space-y-1">
              <label className="text-sm font-medium">Decimal separator</label>
              <Input
                value={c.decimalSeparator}
                onChange={(e) => updateCurrency({ decimalSeparator: e.target.value.slice(0, 1) })}
                placeholder="."
                maxLength={1}
              />
            </div>
            <div className="space-y-1">
              <label className="text-sm font-medium">Number of decimals</label>
              <Input
                value={String(c.numberOfDecimals)}
                onChange={(e) => {
                  const raw = e.target.value;
                  const n = Number.parseInt(raw, 10);
                  const safe = Number.isFinite(n) ? Math.min(6, Math.max(0, n)) : 0;
                  updateCurrency({ numberOfDecimals: safe });
                }}
                type="number"
                min={0}
                max={6}
              />
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
