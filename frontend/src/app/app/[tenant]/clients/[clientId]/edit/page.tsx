"use client";

import React, { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { apiFetch, ApiError } from "@/lib/api";
import { RequireAuth } from "@/components/RequireAuth";
import { PageHeader } from "@/components/ui/PageHeader";
import { Card, CardContent } from "@/components/ui/Card";
import { Button } from "@/components/ui/Button";

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
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load client.");
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

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (typeof tenant !== "string" || tenant.length === 0) return;
    if (!clientId || !Number.isFinite(clientId) || clientId <= 0) return;

    setBusy(true);
    setError(null);

    try {
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
      };

      await apiFetch<{ client: ApiClient }>(`/api/${tenant}/app/clients/${clientId}`, {
        method: "PUT",
        body: payload,
      });

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
        <PageHeader title="Edit customer" description="Update this customer record." />

        {invalidClientId ? <div className="text-sm text-red-600">Customer is invalid.</div> : null}
        {error ? <div className="text-sm text-red-600">{error}</div> : null}

        <Card className="shadow-none">
          <CardContent className="pt-5">
            {loading ? <div className="text-sm text-zinc-600">Loading...</div> : null}

            {!loading && !invalidClientId ? (
              <form className="grid gap-3" onSubmit={onSubmit}>
                <div className="space-y-1">
                  <label className="text-sm font-medium" htmlFor="client_name">
                    Name
                  </label>
                  <input
                    id="client_name"
                    className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                    required
                    disabled={busy}
                    maxLength={255}
                  />
                </div>

                <div className="space-y-1">
                  <label className="text-sm font-medium" htmlFor="client_email">
                    Email
                  </label>
                  <input
                    id="client_email"
                    type="email"
                    className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    required
                    disabled={busy}
                    maxLength={255}
                  />
                </div>

                <div className="space-y-1">
                  <label className="text-sm font-medium" htmlFor="client_phone">
                    Phone
                  </label>
                  <input
                    id="client_phone"
                    className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                    value={phone}
                    onChange={(e) => setPhone(e.target.value)}
                    disabled={busy}
                    maxLength={64}
                  />
                </div>

                <div className="space-y-1">
                  <label className="text-sm font-medium" htmlFor="client_company">
                    Company
                  </label>
                  <input
                    id="client_company"
                    className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                    value={company}
                    onChange={(e) => setCompany(e.target.value)}
                    disabled={busy}
                    maxLength={255}
                  />
                </div>

                <div className="space-y-1">
                  <label className="text-sm font-medium" htmlFor="client_tax_id">
                    Tax ID
                  </label>
                  <input
                    id="client_tax_id"
                    className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                    value={taxId}
                    onChange={(e) => setTaxId(e.target.value)}
                    disabled={busy}
                    maxLength={64}
                  />
                </div>

                <div className="space-y-1">
                  <label className="text-sm font-medium" htmlFor="client_address_line1">
                    Address line 1
                  </label>
                  <input
                    id="client_address_line1"
                    className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                    value={addressLine1}
                    onChange={(e) => setAddressLine1(e.target.value)}
                    disabled={busy}
                    maxLength={255}
                  />
                </div>

                <div className="space-y-1">
                  <label className="text-sm font-medium" htmlFor="client_address_line2">
                    Address line 2
                  </label>
                  <input
                    id="client_address_line2"
                    className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                    value={addressLine2}
                    onChange={(e) => setAddressLine2(e.target.value)}
                    disabled={busy}
                    maxLength={255}
                  />
                </div>

                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                  <div className="space-y-1">
                    <label className="text-sm font-medium" htmlFor="client_address_city">
                      City
                    </label>
                    <input
                      id="client_address_city"
                      className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                      value={addressCity}
                      onChange={(e) => setAddressCity(e.target.value)}
                      disabled={busy}
                      maxLength={255}
                    />
                  </div>

                  <div className="space-y-1">
                    <label className="text-sm font-medium" htmlFor="client_address_state">
                      State
                    </label>
                    <input
                      id="client_address_state"
                      className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                      value={addressState}
                      onChange={(e) => setAddressState(e.target.value)}
                      disabled={busy}
                      maxLength={255}
                    />
                  </div>
                </div>

                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                  <div className="space-y-1">
                    <label className="text-sm font-medium" htmlFor="client_address_postal_code">
                      Postal code
                    </label>
                    <input
                      id="client_address_postal_code"
                      className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                      value={addressPostalCode}
                      onChange={(e) => setAddressPostalCode(e.target.value)}
                      disabled={busy}
                      maxLength={64}
                    />
                  </div>

                  <div className="space-y-1">
                    <label className="text-sm font-medium" htmlFor="client_address_country">
                      Country (2-letter)
                    </label>
                    <input
                      id="client_address_country"
                      className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm uppercase"
                      value={addressCountry}
                      onChange={(e) => setAddressCountry(e.target.value)}
                      disabled={busy}
                      maxLength={2}
                    />
                  </div>
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
