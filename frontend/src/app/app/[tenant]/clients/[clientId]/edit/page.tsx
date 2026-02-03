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
import { Select } from "@/components/ui/Select";
import { FormRow } from "@/components/ui/FormRow";
import { getTenantCurrencies, type TenantCurrency } from "@/lib/tenant-currencies";

type ApiClient = {
  id: number;
  name: string;
  email: string | null;
  phone: string | null;
  company: string | null;
  tax_id: string | null;
  address_line1: string | null;
  address_line2: string | null;
  address_city: string | null;
  address_state: string | null;
  address_postal_code: string | null;
  address_country: string | null;
  currency: string | null;
  created_at: string;
  jobs_count: number;
};

export default function EditClientPage() {
  const router = useRouter();
  const params = useParams() as { tenant?: string; business?: string; clientId?: string };
  const tenant = params.business ?? params.tenant;
  const clientIdParam = params.clientId;

  const clientId = typeof clientIdParam === "string" && clientIdParam.length > 0 ? Number(clientIdParam) : null;

  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [phone, setPhone] = useState("");
  const [company, setCompany] = useState("");
  const [taxId, setTaxId] = useState("");

  const [addressLine1, setAddressLine1] = useState("");
  const [addressLine2, setAddressLine2] = useState("");
  const [addressCity, setAddressCity] = useState("");
  const [addressState, setAddressState] = useState("");
  const [addressPostalCode, setAddressPostalCode] = useState("");
  const [addressCountry, setAddressCountry] = useState("");

  const [currency, setCurrency] = useState("");
  const [currencyOptions, setCurrencyOptions] = React.useState<TenantCurrency[]>([]);
  const [businessActiveCurrency, setBusinessActiveCurrency] = React.useState<string | null>(null);

  useEffect(() => {
    let alive = true;

    async function load() {
      if (typeof tenant !== "string" || tenant.length === 0) return;
      if (!clientId || !Number.isFinite(clientId) || clientId <= 0) return;

      setLoading(true);
      setError(null);

      try {
        const res = await apiFetch<{ client: ApiClient }>(`/api/${tenant}/app/clients/${clientId}`);
        if (!alive) return;

        const c = res.client;
        setName(c?.name ?? "");
        setEmail(c?.email ?? "");
        setPhone(c?.phone ?? "");
        setCompany(c?.company ?? "");
        setTaxId(c?.tax_id ?? "");

        setAddressLine1(c?.address_line1 ?? "");
        setAddressLine2(c?.address_line2 ?? "");
        setAddressCity(c?.address_city ?? "");
        setAddressState(c?.address_state ?? "");
        setAddressPostalCode(c?.address_postal_code ?? "");
        setAddressCountry(c?.address_country ?? "");

        setCurrency(typeof c?.currency === "string" ? c.currency.toUpperCase() : "");
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load customer.");
      } finally {
        if (!alive) return;
        setLoading(false);
      }
    }

    void load();

    return () => {
      alive = false;
    };
  }, [clientId, tenant]);

  useEffect(() => {
    let alive = true;

    async function loadCurrencies() {
      if (typeof tenant !== "string" || tenant.length === 0) return;
      try {
        const res = await getTenantCurrencies(String(tenant));
        if (!alive) return;

        const list = (Array.isArray(res.currencies) ? res.currencies : []).filter((c) => c && c.is_active);
        setCurrencyOptions(list);

        const active = typeof res.active_currency === "string" ? res.active_currency.toUpperCase() : null;
        setBusinessActiveCurrency(active);

        setCurrency((prev) => {
          if (prev && prev.length === 3) return prev;
          const defaultFromList = list.find((c) => c.is_default)?.code ?? null;
          return active ?? defaultFromList ?? (list[0]?.code ?? prev);
        });
      } catch {
        if (!alive) return;
        setCurrencyOptions([]);
        setBusinessActiveCurrency(null);
      }
    }

    void loadCurrencies();

    return () => {
      alive = false;
    };
  }, [tenant]);

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (typeof tenant !== "string" || tenant.length === 0) return;
    if (!clientId || !Number.isFinite(clientId) || clientId <= 0) return;

    setBusy(true);
    setError(null);

    try {
      const cur = currency.trim().toUpperCase();

      const payload = {
        name: name.trim(),
        email: email.trim(),
        phone: phone.trim() !== "" ? phone.trim() : null,
        company: company.trim() !== "" ? company.trim() : null,
        tax_id: taxId.trim() !== "" ? taxId.trim() : null,
        address_line1: addressLine1.trim() !== "" ? addressLine1.trim() : null,
        address_line2: addressLine2.trim() !== "" ? addressLine2.trim() : null,
        address_city: addressCity.trim() !== "" ? addressCity.trim() : null,
        address_state: addressState.trim() !== "" ? addressState.trim() : null,
        address_postal_code: addressPostalCode.trim() !== "" ? addressPostalCode.trim() : null,
        address_country: addressCountry.trim() !== "" ? addressCountry.trim().toUpperCase() : null,
        currency: cur.length === 3 ? cur : null,
      };

      await apiFetch<{ client: ApiClient }>(`/api/${tenant}/app/clients/${clientId}`, {
        method: "PUT",
        body: payload,
      });

      notify.success("Customer updated.");
      router.replace(`/app/${tenant}/clients/${clientId}`);
    } catch (e) {
      if (e instanceof ApiError) {
        setError(e.message);
      } else {
        setError("Failed to save customer.");
      }
    } finally {
      setBusy(false);
    }
  }

  const invalidClientId = !clientId || !Number.isFinite(clientId) || clientId <= 0;

  return (
    <RequireAuth requiredPermission="clients.view">
      <div className="space-y-6">
        <PageHeader
          title="Edit customer"
          description="Update this customer record."
          actions={
            <Button
              variant="outline"
              size="sm"
              onClick={() => {
                router.back();
              }}
            >
              Back
            </Button>
          }
        />

        {invalidClientId ? <div className="text-sm text-red-600">Customer is invalid.</div> : null}
        {error ? <div className="text-sm text-red-600">{error}</div> : null}

        <Card className="shadow-none">
          <CardContent className="pt-5">
            {loading ? <div className="text-sm text-zinc-600">Loading...</div> : null}

            {!loading && !invalidClientId ? (
              <form className="grid gap-4" onSubmit={onSubmit}>
                <FormRow label="Name" fieldId="client_name" required description="Customer full name.">
                  {({ describedBy, invalid }) => (
                    <Input
                      id="client_name"
                      value={name}
                      onChange={(e) => setName(e.target.value)}
                      required
                      disabled={busy}
                      maxLength={255}
                      aria-describedby={describedBy}
                      aria-invalid={invalid}
                    />
                  )}
                </FormRow>

                <FormRow label="Email" fieldId="client_email" required description="Used for invoices and notifications.">
                  {({ describedBy, invalid }) => (
                    <Input
                      id="client_email"
                      type="email"
                      value={email}
                      onChange={(e) => setEmail(e.target.value)}
                      required
                      disabled={busy}
                      maxLength={255}
                      aria-describedby={describedBy}
                      aria-invalid={invalid}
                    />
                  )}
                </FormRow>

                <FormRow label="Phone" fieldId="client_phone" description="Optional. Include country code if needed.">
                  {({ describedBy, invalid }) => (
                    <Input
                      id="client_phone"
                      value={phone}
                      onChange={(e) => setPhone(e.target.value)}
                      disabled={busy}
                      maxLength={64}
                      aria-describedby={describedBy}
                      aria-invalid={invalid}
                    />
                  )}
                </FormRow>

                <FormRow label="Company" fieldId="client_company" description="Optional company name.">
                  {({ describedBy, invalid }) => (
                    <Input
                      id="client_company"
                      value={company}
                      onChange={(e) => setCompany(e.target.value)}
                      disabled={busy}
                      maxLength={255}
                      aria-describedby={describedBy}
                      aria-invalid={invalid}
                    />
                  )}
                </FormRow>

                <FormRow label="Tax ID" fieldId="client_tax_id" description="Optional. VAT / tax identification number.">
                  {({ describedBy, invalid }) => (
                    <Input
                      id="client_tax_id"
                      value={taxId}
                      onChange={(e) => setTaxId(e.target.value)}
                      disabled={busy}
                      maxLength={64}
                      aria-describedby={describedBy}
                      aria-invalid={invalid}
                    />
                  )}
                </FormRow>

                <FormRow label="Currency" fieldId="client_currency" description="Used for customer-level pricing, invoices, and quotes.">
                  {({ describedBy, invalid }) =>
                    currencyOptions.length > 0 ? (
                      <Select
                        value={currency}
                        onChange={(e) => setCurrency(e.target.value)}
                        disabled={busy}
                        aria-describedby={describedBy}
                        aria-invalid={invalid}
                      >
                        {currencyOptions.map((c) => (
                          <option key={c.code} value={c.code}>
                            {c.code} {c.name ? `- ${c.name}` : ""}
                          </option>
                        ))}
                        {businessActiveCurrency && !currencyOptions.some((c) => c.code === businessActiveCurrency) ? (
                          <option value={businessActiveCurrency}>{businessActiveCurrency}</option>
                        ) : null}
                      </Select>
                    ) : (
                      <Input
                        id="client_currency"
                        className="uppercase"
                        value={currency}
                        onChange={(e) => setCurrency(e.target.value.toUpperCase().slice(0, 3))}
                        disabled={busy}
                        maxLength={3}
                        placeholder="USD"
                        aria-describedby={describedBy}
                        aria-invalid={invalid}
                      />
                    )
                  }
                </FormRow>

                <FormRow label="Address line 1" fieldId="client_address_line1" description="Optional street address.">
                  {({ describedBy, invalid }) => (
                    <Input
                      id="client_address_line1"
                      value={addressLine1}
                      onChange={(e) => setAddressLine1(e.target.value)}
                      disabled={busy}
                      maxLength={255}
                      aria-describedby={describedBy}
                      aria-invalid={invalid}
                    />
                  )}
                </FormRow>

                <FormRow label="Address line 2" fieldId="client_address_line2" description="Optional apartment, suite, etc.">
                  {({ describedBy, invalid }) => (
                    <Input
                      id="client_address_line2"
                      value={addressLine2}
                      onChange={(e) => setAddressLine2(e.target.value)}
                      disabled={busy}
                      maxLength={255}
                      aria-describedby={describedBy}
                      aria-invalid={invalid}
                    />
                  )}
                </FormRow>

                <div className="grid gap-4 sm:grid-cols-2">
                  <FormRow label="City" fieldId="client_address_city">
                    {({ describedBy, invalid }) => (
                      <Input
                        id="client_address_city"
                        value={addressCity}
                        onChange={(e) => setAddressCity(e.target.value)}
                        disabled={busy}
                        maxLength={255}
                        aria-describedby={describedBy}
                        aria-invalid={invalid}
                      />
                    )}
                  </FormRow>

                  <FormRow label="State" fieldId="client_address_state">
                    {({ describedBy, invalid }) => (
                      <Input
                        id="client_address_state"
                        value={addressState}
                        onChange={(e) => setAddressState(e.target.value)}
                        disabled={busy}
                        maxLength={255}
                        aria-describedby={describedBy}
                        aria-invalid={invalid}
                      />
                    )}
                  </FormRow>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                  <FormRow label="Postal code" fieldId="client_address_postal_code">
                    {({ describedBy, invalid }) => (
                      <Input
                        id="client_address_postal_code"
                        value={addressPostalCode}
                        onChange={(e) => setAddressPostalCode(e.target.value)}
                        disabled={busy}
                        maxLength={64}
                        aria-describedby={describedBy}
                        aria-invalid={invalid}
                      />
                    )}
                  </FormRow>

                  <FormRow label="Country (2-letter)" fieldId="client_address_country" description="Example: ZA, US, GB.">
                    {({ describedBy, invalid }) => (
                      <Input
                        id="client_address_country"
                        className="uppercase"
                        value={addressCountry}
                        onChange={(e) => setAddressCountry(e.target.value)}
                        disabled={busy}
                        maxLength={2}
                        aria-describedby={describedBy}
                        aria-invalid={invalid}
                      />
                    )}
                  </FormRow>
                </div>

                <div className="flex items-center justify-end gap-2 pt-2">
                  <Button
                    variant="outline"
                    onClick={() => {
                      if (typeof tenant === "string" && tenant.length > 0 && clientId) {
                        router.push(`/app/${tenant}/clients/${clientId}`);
                      }
                    }}
                    disabled={busy}
                  >
                    Cancel
                  </Button>
                  <Button type="submit" disabled={busy}>
                    {busy ? "Saving..." : "Save"}
                  </Button>
                </div>
              </form>
            ) : null}
          </CardContent>
        </Card>
      </div>
    </RequireAuth>
  );
}
