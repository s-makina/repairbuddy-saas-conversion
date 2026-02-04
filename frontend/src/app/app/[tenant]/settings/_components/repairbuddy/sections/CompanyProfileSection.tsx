"use client";

import React, { useEffect, useMemo, useState } from "react";
import { ApiError } from "@/lib/api";
import type { Tenant } from "@/lib/types";
import { Alert } from "@/components/ui/Alert";
import { Button } from "@/components/ui/Button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/Card";
import { Input } from "@/components/ui/Input";
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

export function CompanyProfileSection({ tenantSlug }: { tenantSlug: string }) {
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [uploadingLogo, setUploadingLogo] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [status, setStatus] = useState<string | null>(null);

  const [data, setData] = useState<SettingsPayload | null>(null);

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
        setTimezone(t.timezone ?? "UTC");

        const state = (res.setup?.state ?? {}) as Record<string, unknown>;
        const branding = (state.branding ?? {}) as Record<string, unknown>;
        const operations = (state.operations ?? {}) as Record<string, unknown>;

        setSupportEmail(typeof branding.support_email === "string" ? branding.support_email : "");
        setSupportPhone(typeof branding.support_phone === "string" ? branding.support_phone : "");
        setWebsite(typeof branding.website === "string" ? branding.website : "");
        setDocumentFooter(typeof branding.document_footer === "string" ? branding.document_footer : "");

        setWorkingHours(typeof operations.working_hours === "string" ? operations.working_hours : "Mon–Fri 09:00–17:00");
        setDefaultLaborRate(typeof operations.default_labor_rate === "string" ? operations.default_labor_rate : "");
        setWarrantyTerms(typeof operations.warranty_terms === "string" ? operations.warranty_terms : "");
        setNotifyStatusChange(typeof operations.notify_status_change === "boolean" ? operations.notify_status_change : true);
        setNotifyInvoiceCreated(typeof operations.notify_invoice_created === "boolean" ? operations.notify_invoice_created : false);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load settings.");
        setData(null);
      } finally {
        if (!alive) return;
        setLoading(false);
      }
    }

    void load();

    return () => {
      alive = false;
    };
  }, [tenantSlug]);

  const initial = data?.tenant;
  const initialSetupState = useMemo(() => (data?.setup?.state ?? null) as Record<string, unknown> | null, [data?.setup?.state]);

  const hasChanges = useMemo(() => {
    if (!initial) return false;

    const s = (initialSetupState ?? {}) as Record<string, unknown>;
    const branding = (s.branding ?? {}) as Record<string, unknown>;
    const operations = (s.operations ?? {}) as Record<string, unknown>;

    return (
      (timezone.trim() || "") !== ((initial.timezone ?? "").trim() || "") ||
      (supportEmail.trim() || "") !== (typeof branding.support_email === "string" ? branding.support_email.trim() : "") ||
      (supportPhone.trim() || "") !== (typeof branding.support_phone === "string" ? branding.support_phone.trim() : "") ||
      (website.trim() || "") !== (typeof branding.website === "string" ? branding.website.trim() : "") ||
      (documentFooter.trim() || "") !== (typeof branding.document_footer === "string" ? branding.document_footer.trim() : "") ||
      (workingHours.trim() || "") !== (typeof operations.working_hours === "string" ? operations.working_hours.trim() : "") ||
      (defaultLaborRate.trim() || "") !== (typeof operations.default_labor_rate === "string" ? operations.default_labor_rate.trim() : "") ||
      (warrantyTerms.trim() || "") !== (typeof operations.warranty_terms === "string" ? operations.warranty_terms.trim() : "") ||
      notifyStatusChange !== (typeof operations.notify_status_change === "boolean" ? operations.notify_status_change : true) ||
      notifyInvoiceCreated !== (typeof operations.notify_invoice_created === "boolean" ? operations.notify_invoice_created : false)
    );
  }, [
    defaultLaborRate,
    documentFooter,
    initial,
    initialSetupState,
    notifyInvoiceCreated,
    notifyStatusChange,
    supportEmail,
    supportPhone,
    timezone,
    warrantyTerms,
    website,
    workingHours,
  ]);

  const onUploadLogo = React.useCallback(
    async (file: File) => {
      setUploadingLogo(true);
      setError(null);
      try {
        const payload = await updateSetup(String(tenantSlug), { logo: file });
        setData((prev) =>
          prev
            ? {
                ...prev,
                tenant: payload.tenant,
              }
            : prev,
        );
      } catch (e) {
        setError(e instanceof Error ? e.message : "Failed to upload logo.");
      } finally {
        setUploadingLogo(false);
      }
    },
    [tenantSlug],
  );

  async function onSave(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setStatus(null);

    if (!timezone.trim()) {
      setError("Timezone is required.");
      return;
    }

    setSaving(true);

    try {
      const prevSetupState = (data?.setup?.state ?? {}) as Record<string, unknown>;
      const nextSetupState: Record<string, unknown> = {
        ...prevSetupState,
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
      };

      const payload = await updateSetup(String(tenantSlug), {
        timezone: timezone.trim() || null,
        language: "en",
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
    <div className="space-y-4">
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
              <CardTitle>Branding</CardTitle>
              <CardDescription>Customer-facing visuals and contact information.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid gap-4 sm:grid-cols-2">
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
                    <Input value={supportEmail} onChange={(e) => setSupportEmail(e.target.value)} placeholder="Public support email" disabled={saving} />
                    <Input value={supportPhone} onChange={(e) => setSupportPhone(e.target.value)} placeholder="Public support phone" disabled={saving} />
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
                      <input type="checkbox" checked={notifyStatusChange} onChange={(e) => setNotifyStatusChange(e.target.checked)} disabled={saving} />
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

          <div className="flex items-center justify-end gap-2">
            <Button type="submit" disabled={saving || !hasChanges}>
              {saving ? "Saving..." : "Save changes"}
            </Button>
          </div>
        </form>
      ) : null}
    </div>
  );
}
