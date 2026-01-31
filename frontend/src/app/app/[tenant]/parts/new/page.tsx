"use client";

import React, { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { apiFetch, ApiError } from "@/lib/api";
import { notify } from "@/lib/notify";
import { RequireAuth } from "@/components/RequireAuth";
import { PageHeader } from "@/components/ui/PageHeader";
import { Card, CardContent } from "@/components/ui/Card";
import { Button } from "@/components/ui/Button";
import { Input } from "@/components/ui/Input";
import { FormRow } from "@/components/ui/FormRow";

type ApiPartType = {
  id: number;
  name: string;
  is_active: boolean;
};

type ApiPartBrand = {
  id: number;
  name: string;
  image_url: string | null;
  is_active: boolean;
};

type ApiTax = {
  id: number;
  name: string;
  rate: string;
  is_default: boolean;
};

export default function NewPartPage() {
  const router = useRouter();
  const params = useParams() as { tenant?: string; business?: string };
  const tenant = params.business ?? params.tenant;

  const [loadingLookups, setLoadingLookups] = useState(true);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const [partTypes, setPartTypes] = useState<ApiPartType[]>([]);
  const [partBrands, setPartBrands] = useState<ApiPartBrand[]>([]);
  const [taxes, setTaxes] = useState<ApiTax[]>([]);

  const [name, setName] = useState("");
  const [sku, setSku] = useState("");
  const [typeId, setTypeId] = useState<number | null>(null);
  const [brandId, setBrandId] = useState<number | null>(null);
  const [manufacturingCode, setManufacturingCode] = useState("");
  const [stockCode, setStockCode] = useState("");

  const [price, setPrice] = useState("");
  const [currency, setCurrency] = useState("EUR");
  const [taxId, setTaxId] = useState<number | null>(null);

  const [warranty, setWarranty] = useState("");
  const [capacity, setCapacity] = useState("");
  const [coreFeatures, setCoreFeatures] = useState("");

  const [installationCharges, setInstallationCharges] = useState("");
  const [installationCurrency, setInstallationCurrency] = useState("EUR");
  const [installationMessage, setInstallationMessage] = useState("");

  const [stock, setStock] = useState("");
  const [isActive, setIsActive] = useState(true);

  useEffect(() => {
    let alive = true;

    async function loadLookups() {
      if (typeof tenant !== "string" || tenant.length === 0) return;

      setLoadingLookups(true);
      setError(null);

      try {
        const [typesRes, brandsRes, taxesRes] = await Promise.all([
          apiFetch<{ part_types: ApiPartType[] }>(`/api/${tenant}/app/repairbuddy/part-types?limit=200`),
          apiFetch<{ part_brands: ApiPartBrand[] }>(`/api/${tenant}/app/repairbuddy/part-brands?limit=200`),
          apiFetch<{ taxes: ApiTax[] }>(`/api/${tenant}/app/repairbuddy/taxes?limit=200`),
        ]);

        if (!alive) return;
        setPartTypes(Array.isArray(typesRes.part_types) ? typesRes.part_types : []);
        setPartBrands(Array.isArray(brandsRes.part_brands) ? brandsRes.part_brands : []);
        setTaxes(Array.isArray(taxesRes.taxes) ? taxesRes.taxes : []);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load form data.");
        setPartTypes([]);
        setPartBrands([]);
        setTaxes([]);
      } finally {
        if (!alive) return;
        setLoadingLookups(false);
      }
    }

    void loadLookups();

    return () => {
      alive = false;
    };
  }, [tenant]);

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (typeof tenant !== "string" || tenant.length === 0) return;

    setBusy(true);
    setError(null);

    try {
      const trimmedName = name.trim();
      if (trimmedName.length === 0) {
        setError("Name is required.");
        return;
      }

      const priceText = price.trim();
      const resolvedCurrency = currency.trim().toUpperCase();

      let priceAmountCents: number | null = null;
      let priceCurrency: string | null = null;

      if (priceText.length > 0) {
        const parsed = Number(priceText);
        if (!Number.isFinite(parsed)) {
          setError("Price is invalid.");
          return;
        }
        priceAmountCents = Math.round(parsed * 100);
        priceCurrency = resolvedCurrency.length > 0 ? resolvedCurrency : null;
      }

      const installationText = installationCharges.trim();
      const resolvedInstallationCurrency = installationCurrency.trim().toUpperCase();

      let installationAmountCents: number | null = null;
      let installationCurrencyValue: string | null = null;

      if (installationText.length > 0) {
        const parsed = Number(installationText);
        if (!Number.isFinite(parsed)) {
          setError("Installation charges is invalid.");
          return;
        }
        installationAmountCents = Math.round(parsed * 100);
        installationCurrencyValue = resolvedInstallationCurrency.length > 0 ? resolvedInstallationCurrency : null;
      }

      const stockText = stock.trim();
      let stockValue: number | null = null;
      if (stockText.length > 0) {
        const parsed = Number(stockText);
        if (!Number.isFinite(parsed)) {
          setError("Stock is invalid.");
          return;
        }
        stockValue = Math.trunc(parsed);
      }

      const payload = {
        name: trimmedName,
        sku: sku.trim() !== "" ? sku.trim() : null,
        part_type_id: typeId,
        part_brand_id: brandId,
        manufacturing_code: manufacturingCode.trim() !== "" ? manufacturingCode.trim() : null,
        stock_code: stockCode.trim() !== "" ? stockCode.trim() : null,
        price_amount_cents: priceAmountCents,
        price_currency: priceCurrency,
        tax_id: taxId,
        warranty: warranty.trim() !== "" ? warranty.trim() : null,
        core_features: coreFeatures.trim() !== "" ? coreFeatures.trim() : null,
        capacity: capacity.trim() !== "" ? capacity.trim() : null,
        installation_charges_amount_cents: installationAmountCents,
        installation_charges_currency: installationCurrencyValue,
        installation_message: installationMessage.trim() !== "" ? installationMessage.trim() : null,
        stock: stockValue,
        is_active: isActive,
      };

      await apiFetch(`/api/${tenant}/app/repairbuddy/parts`, {
        method: "POST",
        body: payload,
      });

      notify.success("Part created.");
      router.replace(`/app/${tenant}/parts`);
    } catch (e) {
      if (e instanceof ApiError) {
        setError(e.message);
      } else {
        setError("Failed to create part.");
      }
    } finally {
      setBusy(false);
    }
  }

  const disabled = busy || loadingLookups;

  return (
    <RequireAuth requiredPermission="parts.manage">
      <div className="space-y-6">
        <PageHeader
          title="New part"
          description="Create a new inventory part."
          actions={
            <>
              <Button
                variant="outline"
                size="sm"
                onClick={() => {
                  if (typeof tenant !== "string" || tenant.length === 0) return;
                  router.push(`/app/${tenant}/parts`);
                }}
              >
                Cancel
              </Button>
              <Button variant="primary" size="sm" type="submit" form="rb_part_new_form" disabled={disabled}>
                {busy ? "Saving..." : "Save"}
              </Button>
            </>
          }
        />

        {error ? <div className="text-sm text-red-600">{error}</div> : null}

        <Card className="shadow-none">
          <CardContent className="pt-5">
            <form id="rb_part_new_form" className="space-y-4" onSubmit={onSubmit}>
              <FormRow label="Name" fieldId="part_name" required>
                <Input id="part_name" value={name} onChange={(e) => setName(e.target.value)} disabled={disabled} />
              </FormRow>

              <FormRow label="SKU" fieldId="part_sku">
                <Input id="part_sku" value={sku} onChange={(e) => setSku(e.target.value)} disabled={disabled} />
              </FormRow>

              <FormRow label="Manufacturing code" fieldId="part_manufacturing_code">
                <Input
                  id="part_manufacturing_code"
                  value={manufacturingCode}
                  onChange={(e) => setManufacturingCode(e.target.value)}
                  disabled={disabled}
                />
              </FormRow>

              <FormRow label="Stock code" fieldId="part_stock_code">
                <Input id="part_stock_code" value={stockCode} onChange={(e) => setStockCode(e.target.value)} disabled={disabled} />
              </FormRow>

              <FormRow label="Part type" fieldId="part_type">
                <select
                  id="part_type"
                  className="w-full rounded-[var(--rb-radius-sm)] border border-zinc-300 bg-white px-3 py-2 text-sm text-[var(--rb-text)]"
                  value={typeId ?? ""}
                  onChange={(e) => {
                    const raw = e.target.value;
                    if (!raw) {
                      setTypeId(null);
                      return;
                    }
                    const n = Number(raw);
                    setTypeId(Number.isFinite(n) ? n : null);
                  }}
                  disabled={disabled}
                >
                  <option value="">(none)</option>
                  {partTypes
                    .slice()
                    .sort((a, b) => a.name.localeCompare(b.name))
                    .map((t) => (
                      <option key={t.id} value={t.id}>
                        {t.name}
                      </option>
                    ))}
                </select>
              </FormRow>

              <FormRow label="Part brand" fieldId="part_brand">
                <select
                  id="part_brand"
                  className="w-full rounded-[var(--rb-radius-sm)] border border-zinc-300 bg-white px-3 py-2 text-sm text-[var(--rb-text)]"
                  value={brandId ?? ""}
                  onChange={(e) => {
                    const raw = e.target.value;
                    if (!raw) {
                      setBrandId(null);
                      return;
                    }
                    const n = Number(raw);
                    setBrandId(Number.isFinite(n) ? n : null);
                  }}
                  disabled={disabled}
                >
                  <option value="">(none)</option>
                  {partBrands
                    .slice()
                    .sort((a, b) => a.name.localeCompare(b.name))
                    .map((b) => (
                      <option key={b.id} value={b.id}>
                        {b.name}
                      </option>
                    ))}
                </select>
              </FormRow>

              <FormRow label="Price" fieldId="part_price">
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                  <div className="sm:col-span-2">
                    <Input
                      id="part_price"
                      value={price}
                      onChange={(e) => setPrice(e.target.value)}
                      disabled={disabled}
                      inputMode="decimal"
                    />
                  </div>
                  <div>
                    <Input id="part_currency" value={currency} onChange={(e) => setCurrency(e.target.value)} disabled={disabled} />
                  </div>
                </div>
              </FormRow>

              <FormRow label="Tax" fieldId="part_tax">
                <select
                  id="part_tax"
                  className="w-full rounded-[var(--rb-radius-sm)] border border-zinc-300 bg-white px-3 py-2 text-sm text-[var(--rb-text)]"
                  value={taxId ?? ""}
                  onChange={(e) => {
                    const raw = e.target.value;
                    if (!raw) {
                      setTaxId(null);
                      return;
                    }
                    const n = Number(raw);
                    setTaxId(Number.isFinite(n) ? n : null);
                  }}
                  disabled={disabled}
                >
                  <option value="">(none)</option>
                  {taxes
                    .slice()
                    .sort((a, b) => a.name.localeCompare(b.name))
                    .map((t) => (
                      <option key={t.id} value={t.id}>
                        {t.name}
                      </option>
                    ))}
                </select>
              </FormRow>

              <FormRow label="Warranty" fieldId="part_warranty">
                <Input id="part_warranty" value={warranty} onChange={(e) => setWarranty(e.target.value)} disabled={disabled} />
              </FormRow>

              <FormRow label="Capacity" fieldId="part_capacity">
                <Input id="part_capacity" value={capacity} onChange={(e) => setCapacity(e.target.value)} disabled={disabled} />
              </FormRow>

              <FormRow label="Core features" fieldId="part_core_features">
                <textarea
                  id="part_core_features"
                  className="w-full min-h-[96px] rounded-[var(--rb-radius-sm)] border border-zinc-300 bg-white px-3 py-2 text-sm text-[var(--rb-text)]"
                  value={coreFeatures}
                  onChange={(e) => setCoreFeatures(e.target.value)}
                  disabled={disabled}
                />
              </FormRow>

              <FormRow label="Installation charges" fieldId="part_installation_charges">
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                  <div className="sm:col-span-2">
                    <Input
                      id="part_installation_charges"
                      value={installationCharges}
                      onChange={(e) => setInstallationCharges(e.target.value)}
                      disabled={disabled}
                      inputMode="decimal"
                    />
                  </div>
                  <div>
                    <Input
                      id="part_installation_currency"
                      value={installationCurrency}
                      onChange={(e) => setInstallationCurrency(e.target.value)}
                      disabled={disabled}
                    />
                  </div>
                </div>
              </FormRow>

              <FormRow label="Installation message" fieldId="part_installation_message">
                <Input
                  id="part_installation_message"
                  value={installationMessage}
                  onChange={(e) => setInstallationMessage(e.target.value)}
                  disabled={disabled}
                />
              </FormRow>

              <FormRow label="Stock" fieldId="part_stock">
                <Input id="part_stock" value={stock} onChange={(e) => setStock(e.target.value)} disabled={disabled} inputMode="numeric" />
              </FormRow>

              <div className="grid gap-1 sm:grid-cols-[220px_1fr] sm:gap-4">
                <div className="sm:pt-2">
                  <span className="block text-sm font-medium text-[var(--rb-text)]">Active</span>
                </div>
                <label className="flex items-center gap-2 text-sm">
                  <input type="checkbox" checked={isActive} onChange={(e) => setIsActive(e.target.checked)} disabled={disabled} />
                  Enabled
                </label>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </RequireAuth>
  );
}
