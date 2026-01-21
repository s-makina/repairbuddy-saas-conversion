"use client";

import React, { useEffect, useMemo, useState } from "react";
import { useParams } from "next/navigation";
import { apiFetch, ApiError } from "@/lib/api";
import type { Tenant } from "@/lib/types";
import { useAuth } from "@/lib/auth";
import { RequireAuth } from "@/components/RequireAuth";
import { Alert } from "@/components/ui/Alert";
import { Button } from "@/components/ui/Button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/Card";
import { Input } from "@/components/ui/Input";
import { PageHeader } from "@/components/ui/PageHeader";

type SettingsPayload = {
  tenant: Tenant;
};

export default function TenantSettingsPage() {
  const auth = useAuth();
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [status, setStatus] = useState<string | null>(null);

  const [data, setData] = useState<SettingsPayload | null>(null);

  const [name, setName] = useState("");
  const [contactEmail, setContactEmail] = useState("");
  const [currency, setCurrency] = useState("USD");
  const [billingCountry, setBillingCountry] = useState("");
  const [billingVatNumber, setBillingVatNumber] = useState("");

  const [addressLine1, setAddressLine1] = useState("");
  const [addressLine2, setAddressLine2] = useState("");
  const [addressCity, setAddressCity] = useState("");
  const [addressState, setAddressState] = useState("");
  const [addressPostalCode, setAddressPostalCode] = useState("");

  useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setError(null);
        setStatus(null);
        setLoading(true);

        const res = await apiFetch<SettingsPayload>(`/api/${tenantSlug}/app/settings`);
        if (!alive) return;

        setData(res);

        const t = res.tenant;
        setName(t.name ?? "");
        setContactEmail(t.contact_email ?? "");
        setCurrency((t.currency ?? "USD").toUpperCase());
        setBillingCountry((t.billing_country ?? "").toUpperCase());
        setBillingVatNumber(t.billing_vat_number ?? "");

        const addr = (t.billing_address_json ?? {}) as Record<string, unknown>;
        setAddressLine1(typeof addr.line1 === "string" ? addr.line1 : "");
        setAddressLine2(typeof addr.line2 === "string" ? addr.line2 : "");
        setAddressCity(typeof addr.city === "string" ? addr.city : "");
        setAddressState(typeof addr.state === "string" ? addr.state : "");
        setAddressPostalCode(typeof addr.postal_code === "string" ? addr.postal_code : "");
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load settings.");
        setData(null);
      } finally {
        if (!alive) return;
        setLoading(false);
      }
    }

    if (typeof tenantSlug === "string" && tenantSlug.length > 0) {
      void load();
    } else {
      setLoading(false);
      setError("Business is missing.");
    }

    return () => {
      alive = false;
    };
  }, [tenantSlug]);

  const billingAddressJson = useMemo(() => {
    const out: Record<string, string> = {};
    if (addressLine1.trim()) out.line1 = addressLine1.trim();
    if (addressLine2.trim()) out.line2 = addressLine2.trim();
    if (addressCity.trim()) out.city = addressCity.trim();
    if (addressState.trim()) out.state = addressState.trim();
    if (addressPostalCode.trim()) out.postal_code = addressPostalCode.trim();
    return Object.keys(out).length > 0 ? out : null;
  }, [addressCity, addressLine1, addressLine2, addressPostalCode, addressState]);

  const initial = data?.tenant;

  const hasChanges = useMemo(() => {
    if (!initial) return false;

    const initAddr = (initial.billing_address_json ?? {}) as Record<string, unknown>;
    const initAddrNormalized: Record<string, string> = {
      line1: typeof initAddr.line1 === "string" ? initAddr.line1 : "",
      line2: typeof initAddr.line2 === "string" ? initAddr.line2 : "",
      city: typeof initAddr.city === "string" ? initAddr.city : "",
      state: typeof initAddr.state === "string" ? initAddr.state : "",
      postal_code: typeof initAddr.postal_code === "string" ? initAddr.postal_code : "",
    };

    const currentAddrNormalized: Record<string, string> = {
      line1: addressLine1,
      line2: addressLine2,
      city: addressCity,
      state: addressState,
      postal_code: addressPostalCode,
    };

    const addrChanged = JSON.stringify(initAddrNormalized) !== JSON.stringify(currentAddrNormalized);

    return (
      name.trim() !== (initial.name ?? "").trim() ||
      (contactEmail.trim() || "") !== ((initial.contact_email ?? "").trim() || "") ||
      currency.trim().toUpperCase() !== (initial.currency ?? "USD").toUpperCase() ||
      billingCountry.trim().toUpperCase() !== (initial.billing_country ?? "").toUpperCase() ||
      (billingVatNumber.trim() || "") !== ((initial.billing_vat_number ?? "").trim() || "") ||
      addrChanged
    );
  }, [
    addressCity,
    addressLine1,
    addressLine2,
    addressPostalCode,
    addressState,
    billingCountry,
    billingVatNumber,
    contactEmail,
    currency,
    initial,
    name,
  ]);

  async function onSave(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setStatus(null);

    if (!name.trim()) {
      setError("Company name is required.");
      return;
    }

    const cur = currency.trim().toUpperCase();
    if (cur.length !== 3) {
      setError("Currency must be a 3-letter code (e.g. USD).");
      return;
    }

    const country = billingCountry.trim().toUpperCase();
    if (country && country.length !== 2) {
      setError("Billing country must be a 2-letter code (e.g. US).");
      return;
    }

    setSaving(true);

    try {
      const res = await apiFetch<SettingsPayload>(`/api/${tenantSlug}/app/settings`, {
        method: "PATCH",
        body: {
          name: name.trim(),
          contact_email: contactEmail.trim() || null,
          currency: cur,
          billing_country: country || null,
          billing_vat_number: billingVatNumber.trim() || null,
          billing_address_json: billingAddressJson,
        },
      });

      setData(res);
      await auth.refresh();
      setStatus("Settings saved.");
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError("Failed to save settings.");
      }
    } finally {
      setSaving(false);
    }
  }

  return (
    <RequireAuth requiredPermission="settings.manage">
      <div className="space-y-6">
        <PageHeader
          title="Settings"
          description="Company and billing settings for this business."
          actions={
            <Button variant="outline" size="sm" onClick={() => void auth.refresh()} disabled={auth.loading}>
              Refresh session
            </Button>
          }
        />

        {auth.isImpersonating ? (
          <Alert variant="warning" title="Impersonation active">
            Any changes you make here will be audited.
          </Alert>
        ) : null}

        {error ? (
          <Alert variant="danger" title="Could not load or save settings">
            {error}
          </Alert>
        ) : null}

        {status ? (
          <Alert variant="success" title="Success">
            {status}
          </Alert>
        ) : null}

        {loading ? <div className="text-sm text-zinc-500">Loading settings...</div> : null}

        {!loading && data ? (
          <form className="space-y-6" onSubmit={onSave}>
            <Card>
              <CardHeader>
                <CardTitle>Company</CardTitle>
                <CardDescription>Basic business identity and contact information.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <div className="space-y-1">
                    <label className="text-sm font-medium" htmlFor="tenant_name">
                      Company name
                    </label>
                    <Input id="tenant_name" value={name} onChange={(e) => setName(e.target.value)} disabled={saving} required />
                  </div>
                  <div className="space-y-1">
                    <label className="text-sm font-medium" htmlFor="tenant_contact_email">
                      Contact email
                    </label>
                    <Input
                      id="tenant_contact_email"
                      value={contactEmail}
                      onChange={(e) => setContactEmail(e.target.value)}
                      disabled={saving}
                      type="email"
                      placeholder="billing@company.com"
                    />
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Billing</CardTitle>
                <CardDescription>Used for invoices and tax calculation.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                  <div className="space-y-1">
                    <label className="text-sm font-medium" htmlFor="tenant_currency">
                      Currency
                    </label>
                    <Input
                      id="tenant_currency"
                      value={currency}
                      onChange={(e) => setCurrency(e.target.value.toUpperCase().slice(0, 3))}
                      disabled={saving}
                      placeholder="USD"
                    />
                  </div>
                  <div className="space-y-1">
                    <label className="text-sm font-medium" htmlFor="tenant_billing_country">
                      Billing country
                    </label>
                    <Input
                      id="tenant_billing_country"
                      value={billingCountry}
                      onChange={(e) => setBillingCountry(e.target.value.toUpperCase().slice(0, 2))}
                      disabled={saving}
                      placeholder="US"
                    />
                  </div>
                  <div className="space-y-1">
                    <label className="text-sm font-medium" htmlFor="tenant_billing_vat">
                      VAT number
                    </label>
                    <Input
                      id="tenant_billing_vat"
                      value={billingVatNumber}
                      onChange={(e) => setBillingVatNumber(e.target.value)}
                      disabled={saving}
                      placeholder="Optional"
                    />
                  </div>
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <div className="space-y-1">
                    <label className="text-sm font-medium" htmlFor="addr1">
                      Address line 1
                    </label>
                    <Input id="addr1" value={addressLine1} onChange={(e) => setAddressLine1(e.target.value)} disabled={saving} />
                  </div>
                  <div className="space-y-1">
                    <label className="text-sm font-medium" htmlFor="addr2">
                      Address line 2
                    </label>
                    <Input id="addr2" value={addressLine2} onChange={(e) => setAddressLine2(e.target.value)} disabled={saving} />
                  </div>
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                  <div className="space-y-1">
                    <label className="text-sm font-medium" htmlFor="city">
                      City
                    </label>
                    <Input id="city" value={addressCity} onChange={(e) => setAddressCity(e.target.value)} disabled={saving} />
                  </div>
                  <div className="space-y-1">
                    <label className="text-sm font-medium" htmlFor="state">
                      State/Region
                    </label>
                    <Input id="state" value={addressState} onChange={(e) => setAddressState(e.target.value)} disabled={saving} />
                  </div>
                  <div className="space-y-1">
                    <label className="text-sm font-medium" htmlFor="postal">
                      Postal code
                    </label>
                    <Input id="postal" value={addressPostalCode} onChange={(e) => setAddressPostalCode(e.target.value)} disabled={saving} />
                  </div>
                </div>

                <div className="flex items-center justify-end gap-2">
                  <Button type="submit" disabled={saving || !hasChanges}>
                    {saving ? "Saving..." : "Save changes"}
                  </Button>
                </div>
              </CardContent>
            </Card>
          </form>
        ) : null}
      </div>
    </RequireAuth>
  );
}
