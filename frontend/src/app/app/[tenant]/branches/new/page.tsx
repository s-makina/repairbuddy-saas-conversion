"use client";

import React, { useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { apiFetch, ApiError } from "@/lib/api";
import type { Branch } from "@/lib/types";
import { RequireAuth } from "@/components/RequireAuth";
import { PageHeader } from "@/components/ui/PageHeader";
import { Card, CardContent } from "@/components/ui/Card";
import { Button } from "@/components/ui/Button";

export default function NewBranchPage() {
  const router = useRouter();
  const params = useParams() as { tenant?: string; business?: string };
  const tenant = params.business ?? params.tenant;

  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const [name, setName] = useState("");
  const [code, setCode] = useState("");
  const [phone, setPhone] = useState("");
  const [email, setEmail] = useState("");
  const [addressLine1, setAddressLine1] = useState("");
  const [addressLine2, setAddressLine2] = useState("");
  const [addressCity, setAddressCity] = useState("");
  const [addressState, setAddressState] = useState("");
  const [addressPostalCode, setAddressPostalCode] = useState("");
  const [addressCountry, setAddressCountry] = useState("");
  const [isActive, setIsActive] = useState(true);

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (typeof tenant !== "string" || tenant.length === 0) return;

    setBusy(true);
    setError(null);

    try {
      const payload = {
        name,
        code,
        phone: phone.trim() !== "" ? phone.trim() : null,
        email: email.trim() !== "" ? email.trim() : null,
        address_line1: addressLine1.trim() !== "" ? addressLine1.trim() : null,
        address_line2: addressLine2.trim() !== "" ? addressLine2.trim() : null,
        address_city: addressCity.trim() !== "" ? addressCity.trim() : null,
        address_state: addressState.trim() !== "" ? addressState.trim() : null,
        address_postal_code: addressPostalCode.trim() !== "" ? addressPostalCode.trim() : null,
        address_country: addressCountry.trim() !== "" ? addressCountry.trim().toUpperCase() : null,
        is_active: isActive,
      };

      await apiFetch<{ branch: Branch }>(`/api/${tenant}/app/branches`, {
        method: "POST",
        body: payload,
      });

      router.replace(`/app/${tenant}/branches`);
    } catch (e) {
      if (e instanceof ApiError) {
        setError(e.message);
      } else {
        setError("Failed to create branch.");
      }
    } finally {
      setBusy(false);
    }
  }

  return (
    <RequireAuth requiredPermission="branches.manage">
      <div className="space-y-6">
        <PageHeader title="Create branch" description="Add another location under this business." />

        {error ? <div className="text-sm text-red-600">{error}</div> : null}

        <Card className="shadow-none">
          <CardContent className="pt-5">
            <form className="grid gap-3" onSubmit={onSubmit}>
              <div className="space-y-1">
                <label className="text-sm font-medium" htmlFor="branch_name">
                  Name
                </label>
                <input
                  id="branch_name"
                  className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  required
                  disabled={busy}
                />
              </div>

              <div className="space-y-1">
                <label className="text-sm font-medium" htmlFor="branch_code">
                  Code
                </label>
                <input
                  id="branch_code"
                  className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                  value={code}
                  onChange={(e) => setCode(e.target.value)}
                  required
                  maxLength={16}
                  disabled={busy}
                />
              </div>

              <div className="space-y-1">
                <label className="text-sm font-medium" htmlFor="branch_phone">
                  Phone
                </label>
                <input
                  id="branch_phone"
                  className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                  value={phone}
                  onChange={(e) => setPhone(e.target.value)}
                  disabled={busy}
                  maxLength={64}
                />
              </div>

              <div className="space-y-1">
                <label className="text-sm font-medium" htmlFor="branch_email">
                  Email
                </label>
                <input
                  id="branch_email"
                  type="email"
                  className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  disabled={busy}
                  maxLength={255}
                />
              </div>

              <div className="space-y-1">
                <label className="text-sm font-medium" htmlFor="branch_address_line1">
                  Address line 1
                </label>
                <input
                  id="branch_address_line1"
                  className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                  value={addressLine1}
                  onChange={(e) => setAddressLine1(e.target.value)}
                  disabled={busy}
                  maxLength={255}
                />
              </div>

              <div className="space-y-1">
                <label className="text-sm font-medium" htmlFor="branch_address_line2">
                  Address line 2
                </label>
                <input
                  id="branch_address_line2"
                  className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                  value={addressLine2}
                  onChange={(e) => setAddressLine2(e.target.value)}
                  disabled={busy}
                  maxLength={255}
                />
              </div>

              <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div className="space-y-1">
                  <label className="text-sm font-medium" htmlFor="branch_address_city">
                    City
                  </label>
                  <input
                    id="branch_address_city"
                    className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                    value={addressCity}
                    onChange={(e) => setAddressCity(e.target.value)}
                    disabled={busy}
                    maxLength={255}
                  />
                </div>

                <div className="space-y-1">
                  <label className="text-sm font-medium" htmlFor="branch_address_state">
                    State
                  </label>
                  <input
                    id="branch_address_state"
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
                  <label className="text-sm font-medium" htmlFor="branch_address_postal_code">
                    Postal code
                  </label>
                  <input
                    id="branch_address_postal_code"
                    className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                    value={addressPostalCode}
                    onChange={(e) => setAddressPostalCode(e.target.value)}
                    disabled={busy}
                    maxLength={64}
                  />
                </div>

                <div className="space-y-1">
                  <label className="text-sm font-medium" htmlFor="branch_address_country">
                    Country (2-letter)
                  </label>
                  <input
                    id="branch_address_country"
                    className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm uppercase"
                    value={addressCountry}
                    onChange={(e) => setAddressCountry(e.target.value)}
                    disabled={busy}
                    maxLength={2}
                  />
                </div>
              </div>

              <label className="flex items-center gap-2 rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm">
                <input type="checkbox" checked={isActive} onChange={(e) => setIsActive(e.target.checked)} disabled={busy} />
                <span>Active</span>
              </label>

              <div className="flex items-center justify-end gap-2 pt-2">
                <Button
                  variant="outline"
                  onClick={() => {
                    if (typeof tenant === "string" && tenant.length > 0) {
                      router.push(`/app/${tenant}/branches`);
                    }
                  }}
                  disabled={busy}
                >
                  Cancel
                </Button>
                <Button type="submit" disabled={busy}>
                  {busy ? "Saving..." : "Create"}
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </RequireAuth>
  );
}
