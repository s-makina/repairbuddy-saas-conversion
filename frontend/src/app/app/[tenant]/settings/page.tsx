"use client";

import React, { useCallback, useEffect, useMemo, useState } from "react";
import { useParams } from "next/navigation";
import { ApiError } from "@/lib/api";
import type { Tenant } from "@/lib/types";
import { useAuth } from "@/lib/auth";
import { RequireAuth } from "@/components/RequireAuth";
import { Alert } from "@/components/ui/Alert";
import { Button } from "@/components/ui/Button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/Card";
import { Input } from "@/components/ui/Input";
import { PageHeader } from "@/components/ui/PageHeader";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/Tabs";
import { getSetup, updateSetup } from "@/lib/setup";

type SettingsPayload = {
  tenant: Tenant;
  setup: {
    completed_at?: string | null;
    step?: string | null;
    state?: Record<string, unknown> | null;
  };
};

const fallbackTimezones = [
  "UTC",
  "Europe/London",
  "Europe/Berlin",
  "Europe/Paris",
  "Africa/Cairo",
  "Asia/Dubai",
  "Asia/Riyadh",
  "Asia/Kolkata",
  "Asia/Singapore",
  "Asia/Tokyo",
  "Australia/Sydney",
  "America/New_York",
  "America/Chicago",
  "America/Denver",
  "America/Los_Angeles",
];

export default function TenantSettingsPage() {
  const auth = useAuth();
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [uploadingLogo, setUploadingLogo] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [status, setStatus] = useState<string | null>(null);
  const [activeTab, setActiveTab] = useState("company");

  const [data, setData] = useState<SettingsPayload | null>(null);

  const [name, setName] = useState("");
  const [displayName, setDisplayName] = useState("");
  const [primaryContactName, setPrimaryContactName] = useState("");
  const [registrationNumber, setRegistrationNumber] = useState("");
  const [contactEmail, setContactEmail] = useState("");
  const [contactPhone, setContactPhone] = useState("");
  const [currency, setCurrency] = useState("USD");
  const [billingCountry, setBillingCountry] = useState("");
  const [billingVatNumber, setBillingVatNumber] = useState("");

  const [addressLine1, setAddressLine1] = useState("");
  const [addressLine2, setAddressLine2] = useState("");
  const [addressCity, setAddressCity] = useState("");
  const [addressState, setAddressState] = useState("");
  const [addressPostalCode, setAddressPostalCode] = useState("");

  const [brandColor, setBrandColor] = useState("#2563eb");
  const [supportEmail, setSupportEmail] = useState("");
  const [supportPhone, setSupportPhone] = useState("");
  const [website, setWebsite] = useState("");
  const [documentFooter, setDocumentFooter] = useState("");

  const [timezone, setTimezone] = useState("UTC");
  const [workingHours, setWorkingHours] = useState("Mon–Fri 09:00–17:00");
  const [defaultLaborRate, setDefaultLaborRate] = useState("");
  const [warrantyTerms, setWarrantyTerms] = useState("");
  const [notifyStatusChange, setNotifyStatusChange] = useState(true);
  const [notifyInvoiceCreated, setNotifyInvoiceCreated] = useState(false);

  const [taxRegistered, setTaxRegistered] = useState(false);
  const [invoicePrefix, setInvoicePrefix] = useState("RB");

  const [teamInvites, setTeamInvites] = useState("");
  const [teamDefaultRole, setTeamDefaultRole] = useState("member");

  const timezoneOptions = useMemo(() => {
    try {
      const anyIntl = Intl as unknown as { supportedValuesOf?: (key: string) => string[] };
      const values = typeof anyIntl.supportedValuesOf === "function" ? anyIntl.supportedValuesOf("timeZone") : null;
      return Array.isArray(values) && values.length > 0 ? values : fallbackTimezones;
    } catch {
      return fallbackTimezones;
    }
  }, []);

  useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setError(null);
        setStatus(null);
        setLoading(true);

        const res = (await getSetup(String(tenantSlug))) as SettingsPayload;
        if (!alive) return;

        setData(res);

        const t = res.tenant;
        setName(t.name ?? "");
        setContactEmail(t.contact_email ?? "");
        setContactPhone(t.contact_phone ?? "");
        setCurrency((t.currency ?? "USD").toUpperCase());
        setBillingCountry((t.billing_country ?? "").toUpperCase());
        setBillingVatNumber(t.billing_vat_number ?? "");
        setBrandColor(t.brand_color ?? "#2563eb");
        setTimezone(t.timezone ?? "UTC");

        const addr = (t.billing_address_json ?? {}) as Record<string, unknown>;
        setAddressLine1(typeof addr.line1 === "string" ? addr.line1 : "");
        setAddressLine2(typeof addr.line2 === "string" ? addr.line2 : "");
        setAddressCity(typeof addr.city === "string" ? addr.city : "");
        setAddressState(typeof addr.state === "string" ? addr.state : "");
        setAddressPostalCode(typeof addr.postal_code === "string" ? addr.postal_code : "");

        const state = (res.setup?.state ?? {}) as Record<string, unknown>;
        const identity = (state.identity ?? {}) as Record<string, unknown>;
        const branding = (state.branding ?? {}) as Record<string, unknown>;
        const operations = (state.operations ?? {}) as Record<string, unknown>;
        const tax = (state.tax ?? {}) as Record<string, unknown>;
        const team = (state.team ?? {}) as Record<string, unknown>;

        setDisplayName(typeof identity.display_name === "string" ? identity.display_name : "");
        setPrimaryContactName(typeof identity.primary_contact_name === "string" ? identity.primary_contact_name : "");
        setRegistrationNumber(typeof identity.registration_number === "string" ? identity.registration_number : "");

        setSupportEmail(typeof branding.support_email === "string" ? branding.support_email : "");
        setSupportPhone(typeof branding.support_phone === "string" ? branding.support_phone : "");
        setWebsite(typeof branding.website === "string" ? branding.website : "");
        setDocumentFooter(typeof branding.document_footer === "string" ? branding.document_footer : "");

        setWorkingHours(typeof operations.working_hours === "string" ? operations.working_hours : "Mon–Fri 09:00–17:00");
        setDefaultLaborRate(typeof operations.default_labor_rate === "string" ? operations.default_labor_rate : "");
        setWarrantyTerms(typeof operations.warranty_terms === "string" ? operations.warranty_terms : "");
        setNotifyStatusChange(typeof operations.notify_status_change === "boolean" ? operations.notify_status_change : true);
        setNotifyInvoiceCreated(typeof operations.notify_invoice_created === "boolean" ? operations.notify_invoice_created : false);

        setTaxRegistered(typeof tax.tax_registered === "boolean" ? tax.tax_registered : false);
        setInvoicePrefix(typeof tax.invoice_prefix === "string" ? tax.invoice_prefix : "RB");

        setTeamInvites(typeof team.invites === "string" ? team.invites : "");
        setTeamDefaultRole(typeof team.default_role === "string" ? team.default_role : "member");
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
  const initialSetupState = useMemo(() => (data?.setup?.state ?? null) as Record<string, unknown> | null, [data?.setup?.state]);

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

    const s = (initialSetupState ?? {}) as Record<string, unknown>;
    const identity = (s.identity ?? {}) as Record<string, unknown>;
    const branding = (s.branding ?? {}) as Record<string, unknown>;
    const operations = (s.operations ?? {}) as Record<string, unknown>;
    const tax = (s.tax ?? {}) as Record<string, unknown>;
    const team = (s.team ?? {}) as Record<string, unknown>;

    return (
      name.trim() !== (initial.name ?? "").trim() ||
      (displayName.trim() || "") !== (typeof identity.display_name === "string" ? identity.display_name.trim() : "") ||
      (primaryContactName.trim() || "") !==
        (typeof identity.primary_contact_name === "string" ? identity.primary_contact_name.trim() : "") ||
      (registrationNumber.trim() || "") !== (typeof identity.registration_number === "string" ? identity.registration_number.trim() : "") ||
      (contactEmail.trim() || "") !== ((initial.contact_email ?? "").trim() || "") ||
      (contactPhone.trim() || "") !== ((initial.contact_phone ?? "").trim() || "") ||
      currency.trim().toUpperCase() !== (initial.currency ?? "USD").toUpperCase() ||
      billingCountry.trim().toUpperCase() !== (initial.billing_country ?? "").toUpperCase() ||
      (billingVatNumber.trim() || "") !== ((initial.billing_vat_number ?? "").trim() || "") ||
      (brandColor.trim() || "") !== ((initial.brand_color ?? "").trim() || "") ||
      (timezone.trim() || "") !== ((initial.timezone ?? "").trim() || "") ||
      (supportEmail.trim() || "") !== (typeof branding.support_email === "string" ? branding.support_email.trim() : "") ||
      (supportPhone.trim() || "") !== (typeof branding.support_phone === "string" ? branding.support_phone.trim() : "") ||
      (website.trim() || "") !== (typeof branding.website === "string" ? branding.website.trim() : "") ||
      (documentFooter.trim() || "") !== (typeof branding.document_footer === "string" ? branding.document_footer.trim() : "") ||
      (workingHours.trim() || "") !== (typeof operations.working_hours === "string" ? operations.working_hours.trim() : "") ||
      (defaultLaborRate.trim() || "") !== (typeof operations.default_labor_rate === "string" ? operations.default_labor_rate.trim() : "") ||
      (warrantyTerms.trim() || "") !== (typeof operations.warranty_terms === "string" ? operations.warranty_terms.trim() : "") ||
      notifyStatusChange !== (typeof operations.notify_status_change === "boolean" ? operations.notify_status_change : true) ||
      notifyInvoiceCreated !== (typeof operations.notify_invoice_created === "boolean" ? operations.notify_invoice_created : false) ||
      taxRegistered !== (typeof tax.tax_registered === "boolean" ? tax.tax_registered : false) ||
      (invoicePrefix.trim() || "") !== (typeof tax.invoice_prefix === "string" ? tax.invoice_prefix.trim() : "") ||
      (teamInvites.trim() || "") !== (typeof team.invites === "string" ? team.invites.trim() : "") ||
      (teamDefaultRole.trim() || "") !== (typeof team.default_role === "string" ? team.default_role.trim() : "") ||
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
    contactPhone,
    currency,
    brandColor,
    defaultLaborRate,
    displayName,
    documentFooter,
    initial,
    initialSetupState,
    invoicePrefix,
    name,
    notifyInvoiceCreated,
    notifyStatusChange,
    primaryContactName,
    registrationNumber,
    supportEmail,
    supportPhone,
    taxRegistered,
    teamDefaultRole,
    teamInvites,
    timezone,
    warrantyTerms,
    website,
    workingHours,
  ]);

  const onUploadLogo = useCallback(
    async (file: File) => {
      if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;

      setUploadingLogo(true);
      setError(null);
      setStatus(null);
      try {
        const payload = await updateSetup(tenantSlug, { logo: file });
        setData((prev) => (prev ? { ...prev, tenant: payload.tenant } : prev));
        await auth.refresh();
        setStatus("Logo uploaded.");
      } catch (e) {
        if (e instanceof ApiError) {
          setError(e.message);
        } else {
          setError(e instanceof Error ? e.message : "Failed to upload logo.");
        }
      } finally {
        setUploadingLogo(false);
      }
    },
    [auth, tenantSlug],
  );

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

    if (!timezone.trim()) {
      setError("Timezone is required.");
      return;
    }

    setSaving(true);

    try {
      const prevSetupState = (data?.setup?.state ?? {}) as Record<string, unknown>;
      const nextSetupState: Record<string, unknown> = {
        ...prevSetupState,
        identity: {
          display_name: displayName.trim() || null,
          primary_contact_name: primaryContactName.trim() || null,
          registration_number: registrationNumber.trim() || null,
        },
        branding: {
          support_email: supportEmail.trim() || null,
          support_phone: supportPhone.trim() || null,
          website: website.trim() || null,
          document_footer: documentFooter.trim() || null,
        },
        operations: {
          working_hours: workingHours.trim() || null,
          default_labor_rate: defaultLaborRate.trim() || null,
          warranty_terms: warrantyTerms.trim() || null,
          notify_status_change: Boolean(notifyStatusChange),
          notify_invoice_created: Boolean(notifyInvoiceCreated),
        },
        tax: {
          tax_registered: Boolean(taxRegistered),
          invoice_prefix: invoicePrefix.trim() || null,
        },
        team: {
          invites: teamInvites.trim() || null,
          default_role: teamDefaultRole.trim() || null,
        },
      };

      const payload = await updateSetup(String(tenantSlug), {
        name: name.trim() || undefined,
        contact_email: contactEmail.trim() || null,
        contact_phone: contactPhone.trim() || null,
        billing_country: country || null,
        billing_vat_number: billingVatNumber.trim() || null,
        billing_address_json: billingAddressJson,
        currency: cur,
        timezone: timezone.trim() || null,
        language: "en",
        brand_color: brandColor.trim() || null,
        setup_state: nextSetupState,
      });

      setData((prev) =>
        prev
          ? {
              ...prev,
              tenant: payload.tenant,
              setup: {
                ...prev.setup,
                state: nextSetupState,
              },
            }
          : prev,
      );
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
            <Tabs value={activeTab} onValueChange={setActiveTab} defaultValue="company">
              <TabsList>
                <TabsTrigger value="company">Company</TabsTrigger>
                <TabsTrigger value="billing">Billing</TabsTrigger>
                <TabsTrigger value="branding">Branding</TabsTrigger>
                <TabsTrigger value="operations">Operations</TabsTrigger>
                <TabsTrigger value="tax">Tax</TabsTrigger>
                <TabsTrigger value="team">Team</TabsTrigger>
              </TabsList>

              <TabsContent value="company">
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
                        <Input
                          id="tenant_name"
                          value={name}
                          onChange={(e) => setName(e.target.value)}
                          disabled={saving}
                          required
                        />
                      </div>
                      <div className="space-y-1">
                        <label className="text-sm font-medium" htmlFor="tenant_display_name">
                          Display name
                        </label>
                        <Input
                          id="tenant_display_name"
                          value={displayName}
                          onChange={(e) => setDisplayName(e.target.value)}
                          disabled={saving}
                          placeholder="Defaults to business name"
                        />
                      </div>
                      <div className="space-y-1">
                        <label className="text-sm font-medium" htmlFor="tenant_primary_contact">
                          Primary contact person name
                        </label>
                        <Input
                          id="tenant_primary_contact"
                          value={primaryContactName}
                          onChange={(e) => setPrimaryContactName(e.target.value)}
                          disabled={saving}
                          placeholder="e.g. Alex Johnson"
                        />
                      </div>
                      <div className="space-y-1">
                        <label className="text-sm font-medium" htmlFor="tenant_registration_number">
                          Business registration number
                        </label>
                        <Input
                          id="tenant_registration_number"
                          value={registrationNumber}
                          onChange={(e) => setRegistrationNumber(e.target.value)}
                          disabled={saving}
                          placeholder="Optional"
                        />
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
                      <div className="space-y-1">
                        <label className="text-sm font-medium" htmlFor="tenant_contact_phone">
                          Contact phone
                        </label>
                        <Input
                          id="tenant_contact_phone"
                          value={contactPhone}
                          onChange={(e) => setContactPhone(e.target.value)}
                          disabled={saving}
                          placeholder="+1 555 123 4567"
                        />
                      </div>
                    </div>
                  </CardContent>
                </Card>
              </TabsContent>

              <TabsContent value="billing">
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
                        <Input
                          id="addr1"
                          value={addressLine1}
                          onChange={(e) => setAddressLine1(e.target.value)}
                          disabled={saving}
                        />
                      </div>
                      <div className="space-y-1">
                        <label className="text-sm font-medium" htmlFor="addr2">
                          Address line 2
                        </label>
                        <Input
                          id="addr2"
                          value={addressLine2}
                          onChange={(e) => setAddressLine2(e.target.value)}
                          disabled={saving}
                        />
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
                        <Input
                          id="state"
                          value={addressState}
                          onChange={(e) => setAddressState(e.target.value)}
                          disabled={saving}
                        />
                      </div>
                      <div className="space-y-1">
                        <label className="text-sm font-medium" htmlFor="postal">
                          Postal code
                        </label>
                        <Input
                          id="postal"
                          value={addressPostalCode}
                          onChange={(e) => setAddressPostalCode(e.target.value)}
                          disabled={saving}
                        />
                      </div>
                    </div>
                  </CardContent>
                </Card>
              </TabsContent>

              <TabsContent value="branding">
                <Card>
                  <CardHeader>
                    <CardTitle>Branding</CardTitle>
                    <CardDescription>Customer-facing visuals and contact information.</CardDescription>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                      <div>
                        <label className="text-sm font-medium text-[var(--rb-text)]">Brand color</label>
                        <div className="mt-2 flex items-center gap-3">
                          <input
                            type="color"
                            value={brandColor}
                            onChange={(e) => setBrandColor(e.target.value)}
                            className="h-10 w-14 rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white shadow-sm"
                            aria-label="Brand color"
                            disabled={saving}
                          />
                          <Input value={brandColor} onChange={(e) => setBrandColor(e.target.value)} placeholder="#2563eb" disabled={saving} />
                        </div>
                      </div>

                      <div>
                        <label className="text-sm font-medium text-[var(--rb-text)]">Logo</label>
                        <div className="mt-2 flex items-center gap-3">
                          {data?.tenant?.logo_url ? (
                            // eslint-disable-next-line @next/next/no-img-element
                            <img
                              src={data.tenant.logo_url}
                              alt="Logo"
                              className="h-10 w-10 rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white object-contain"
                            />
                          ) : (
                            <div className="h-10 w-10 rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white" />
                          )}

                          <div className="flex-1">
                            <input
                              type="file"
                              accept="image/png,image/jpeg,image/webp"
                              disabled={uploadingLogo}
                              onChange={(e) => {
                                const file = e.target.files && e.target.files[0] ? e.target.files[0] : null;
                                if (file) {
                                  void onUploadLogo(file);
                                }
                              }}
                              className="block w-full text-sm text-zinc-700 file:mr-3 file:rounded-[var(--rb-radius-sm)] file:border-0 file:bg-[var(--rb-surface-muted)] file:px-3 file:py-2 file:text-sm file:font-medium file:text-[var(--rb-text)] hover:file:bg-[color:color-mix(in_srgb,var(--rb-surface-muted),black_4%)]"
                            />
                          </div>
                        </div>
                        <div className="mt-2 text-xs text-zinc-500">{uploadingLogo ? "Uploading..." : "PNG/JPG/WEBP up to 5MB"}</div>
                      </div>

                      <div className="sm:col-span-2">
                        <div className="text-sm font-semibold text-[var(--rb-text)]">Customer-facing contact (optional)</div>
                        <div className="mt-2 grid gap-3 sm:grid-cols-2">
                          <Input
                            value={supportEmail}
                            onChange={(e) => setSupportEmail(e.target.value)}
                            placeholder="Public support email"
                            disabled={saving}
                          />
                          <Input
                            value={supportPhone}
                            onChange={(e) => setSupportPhone(e.target.value)}
                            placeholder="Public support phone"
                            disabled={saving}
                          />
                          <div className="sm:col-span-2">
                            <Input value={website} onChange={(e) => setWebsite(e.target.value)} placeholder="Website (optional)" disabled={saving} />
                          </div>
                          <div className="sm:col-span-2">
                            <Input
                              value={documentFooter}
                              onChange={(e) => setDocumentFooter(e.target.value)}
                              placeholder="Document footer text (optional)"
                              disabled={saving}
                            />
                          </div>
                        </div>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              </TabsContent>

              <TabsContent value="operations">
                <Card>
                  <CardHeader>
                    <CardTitle>Operations</CardTitle>
                    <CardDescription>Defaults and notifications.</CardDescription>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                      <div>
                        <label className="text-sm font-medium text-[var(--rb-text)]">Timezone</label>
                        <div className="mt-1">
                          <Input value={timezone} onChange={(e) => setTimezone(e.target.value)} placeholder="UTC" list="rb-timezones" disabled={saving} />
                          <datalist id="rb-timezones">
                            {timezoneOptions.map((tz) => (
                              <option key={tz} value={tz} />
                            ))}
                          </datalist>
                        </div>
                        <div className="mt-2 text-xs text-zinc-500">Example: UTC, Europe/Berlin, America/New_York</div>
                      </div>

                      <div>
                        <label className="text-sm font-medium text-[var(--rb-text)]">Language</label>
                        <div className="mt-1">
                          <Input value="English (en)" disabled />
                        </div>
                        <div className="mt-2 text-xs text-zinc-500">Currently supported language: English</div>
                      </div>

                      <div className="sm:col-span-2">
                        <div className="text-sm font-semibold text-[var(--rb-text)]">Operations defaults (optional)</div>
                        <div className="mt-2 grid gap-3 sm:grid-cols-2">
                          <div className="sm:col-span-2">
                            <Input
                              value={workingHours}
                              onChange={(e) => setWorkingHours(e.target.value)}
                              placeholder="Working hours (e.g. Mon–Fri 09:00–17:00)"
                              disabled={saving}
                            />
                          </div>
                          <Input
                            value={defaultLaborRate}
                            onChange={(e) => setDefaultLaborRate(e.target.value)}
                            placeholder="Default labor rate (optional)"
                            disabled={saving}
                          />
                          <Input
                            value={warrantyTerms}
                            onChange={(e) => setWarrantyTerms(e.target.value)}
                            placeholder="Default warranty terms (optional)"
                            disabled={saving}
                          />
                          <label className="sm:col-span-2 flex items-center gap-2 text-sm text-[var(--rb-text)]">
                            <input
                              type="checkbox"
                              checked={notifyStatusChange}
                              onChange={(e) => setNotifyStatusChange(e.target.checked)}
                              disabled={saving}
                            />
                            Email on status change
                          </label>
                          <label className="sm:col-span-2 flex items-center gap-2 text-sm text-[var(--rb-text)]">
                            <input
                              type="checkbox"
                              checked={notifyInvoiceCreated}
                              onChange={(e) => setNotifyInvoiceCreated(e.target.checked)}
                              disabled={saving}
                            />
                            Email on invoice created
                          </label>
                        </div>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              </TabsContent>

              <TabsContent value="tax">
                <Card>
                  <CardHeader>
                    <CardTitle>Tax</CardTitle>
                    <CardDescription>VAT and invoice numbering preferences.</CardDescription>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                      <div className="sm:col-span-2">
                        <label className="flex items-center gap-2 text-sm text-[var(--rb-text)]">
                          <input type="checkbox" checked={taxRegistered} onChange={(e) => setTaxRegistered(e.target.checked)} disabled={saving} />
                          Tax/VAT registered?
                        </label>
                        <div className="mt-2 text-xs text-zinc-500">If enabled, VAT number may be required for certain billing flows.</div>
                      </div>

                      <div className="sm:col-span-2">
                        <label className="text-sm font-medium text-[var(--rb-text)]">VAT number</label>
                        <div className="mt-1">
                          <Input
                            value={billingVatNumber}
                            onChange={(e) => setBillingVatNumber(e.target.value)}
                            placeholder="EU123456789"
                            disabled={saving}
                          />
                        </div>
                      </div>

                      <div>
                        <label className="text-sm font-medium text-[var(--rb-text)]">Invoice prefix (optional)</label>
                        <div className="mt-1">
                          <Input value={invoicePrefix} onChange={(e) => setInvoicePrefix(e.target.value)} placeholder="RB" disabled={saving} />
                        </div>
                      </div>

                      <div>
                        <label className="text-sm font-medium text-[var(--rb-text)]">Invoice format preview</label>
                        <div className="mt-2 rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm text-zinc-700">
                          {(invoicePrefix.trim() || "RB") + "-" + String(tenantSlug || "TENANT") + "-YYYY-000001"}
                        </div>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              </TabsContent>

              <TabsContent value="team">
                <Card>
                  <CardHeader>
                    <CardTitle>Team</CardTitle>
                    <CardDescription>Invite defaults (email sending not implemented yet).</CardDescription>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div>
                      <label className="text-sm font-medium text-[var(--rb-text)]">Invite team members (optional)</label>
                      <div className="mt-1">
                        <Input
                          value={teamInvites}
                          onChange={(e) => setTeamInvites(e.target.value)}
                          placeholder="Emails separated by commas"
                          disabled={saving}
                        />
                      </div>
                      <div className="mt-2 text-xs text-zinc-500">Invites are stored for later processing.</div>
                    </div>

                    <div>
                      <label className="text-sm font-medium text-[var(--rb-text)]">Default role for invites (optional)</label>
                      <div className="mt-1">
                        <Input
                          value={teamDefaultRole}
                          onChange={(e) => setTeamDefaultRole(e.target.value)}
                          placeholder="member"
                          disabled={saving}
                        />
                      </div>
                      <div className="mt-2 text-xs text-zinc-500">Example values: owner, member, technician, front_desk.</div>
                    </div>
                  </CardContent>
                </Card>
              </TabsContent>
            </Tabs>

            <div className="flex items-center justify-end gap-2">
              <Button type="submit" disabled={saving || !hasChanges}>
                {saving ? "Saving..." : "Save changes"}
              </Button>
            </div>
          </form>
        ) : null}
      </div>
    </RequireAuth>
  );
}
