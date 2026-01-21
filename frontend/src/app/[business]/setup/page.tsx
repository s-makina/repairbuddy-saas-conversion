"use client";

import React, { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { RequireAuth } from "@/components/RequireAuth";
import { Preloader } from "@/components/Preloader";
import { Alert } from "@/components/ui/Alert";
import { Button } from "@/components/ui/Button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/Card";
import { Input } from "@/components/ui/Input";
import { PageHeader } from "@/components/ui/PageHeader";
import { useAuth } from "@/lib/auth";
import { ApiError } from "@/lib/api";
import type { Tenant } from "@/lib/types";
import { completeSetup, getSetup, updateSetup } from "@/lib/setup";

type StepId = "business" | "address" | "branding" | "preferences" | "finish";

type WizardState = {
  name: string;
  contact_email: string;
  contact_phone: string;

  billing_country: string;
  billing_vat_number: string;
  currency: string;

  address_line1: string;
  address_line2: string;
  address_city: string;
  address_state: string;
  address_postal_code: string;

  brand_color: string;

  timezone: string;
  language: string;
};

const stepOrder: StepId[] = ["business", "address", "branding", "preferences", "finish"];

function clampStepId(step: unknown): StepId {
  if (typeof step === "string" && (stepOrder as string[]).includes(step)) {
    return step as StepId;
  }
  return "business";
}

export default function BusinessSetupPage() {
  const auth = useAuth();
  const router = useRouter();
  const params = useParams() as { business?: string; tenant?: string };
  const business = params.business ?? params.tenant;

  const [loading, setLoading] = useState(true);
  const [tenant, setTenant] = useState<Tenant | null>(null);

  const [step, setStep] = useState<StepId>("business");
  const [form, setForm] = useState<WizardState>({
    name: "",
    contact_email: "",
    contact_phone: "",
    billing_country: "",
    billing_vat_number: "",
    currency: "USD",
    address_line1: "",
    address_line2: "",
    address_city: "",
    address_state: "",
    address_postal_code: "",
    brand_color: "#2563eb",
    timezone: "UTC",
    language: "en",
  });

  const [setupState, setSetupState] = useState<Record<string, unknown> | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [status, setStatus] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);
  const [uploadingLogo, setUploadingLogo] = useState(false);
  const [completing, setCompleting] = useState(false);

  const initializedRef = useRef(false);
  const dirtyRef = useRef(false);
  const saveTimerRef = useRef<number | null>(null);

  const stepIndex = stepOrder.indexOf(step);
  const progress = Math.max(0, stepIndex) / (stepOrder.length - 1);

  const billingAddressJson = useMemo(() => {
    const out: Record<string, string> = {};
    if (form.address_line1.trim()) out.line1 = form.address_line1.trim();
    if (form.address_line2.trim()) out.line2 = form.address_line2.trim();
    if (form.address_city.trim()) out.city = form.address_city.trim();
    if (form.address_state.trim()) out.state = form.address_state.trim();
    if (form.address_postal_code.trim()) out.postal_code = form.address_postal_code.trim();
    return Object.keys(out).length > 0 ? out : null;
  }, [
    form.address_city,
    form.address_line1,
    form.address_line2,
    form.address_postal_code,
    form.address_state,
  ]);

  const canGoBack = stepIndex > 0;
  const canGoNext = stepIndex < stepOrder.length - 1;

  const validateStep = useCallback(
    (target: StepId): string | null => {
      if (target === "business") {
        if (!form.name.trim()) return "Business name is required.";
      }
      if (target === "address") {
        if (!form.billing_country.trim()) return "Billing country is required.";
        if (form.billing_country.trim().length !== 2) return "Billing country must be a 2-letter code (e.g. US).";
      }
      if (target === "preferences") {
        if (!form.timezone.trim()) return "Timezone is required.";
        if (!form.language.trim()) return "Language is required.";
      }
      if (target === "finish") {
        if (!form.name.trim()) return "Business name is required.";
        if (!form.billing_country.trim() || form.billing_country.trim().length !== 2) return "Billing country is required.";
        if (!form.timezone.trim()) return "Timezone is required.";
        if (!form.language.trim()) return "Language is required.";
      }
      return null;
    },
    [form],
  );

  const persist = useCallback(
    async (nextStep: StepId, showStatus: boolean) => {
      if (typeof business !== "string" || business.length === 0) return;

      setSaving(true);
      setError(null);
      try {
        const payload = await updateSetup(business, {
          name: form.name.trim() || undefined,
          contact_email: form.contact_email.trim() || null,
          contact_phone: form.contact_phone.trim() || null,
          billing_country: form.billing_country.trim() ? form.billing_country.trim().toUpperCase() : null,
          billing_vat_number: form.billing_vat_number.trim() || null,
          billing_address_json: billingAddressJson,
          currency: form.currency.trim() ? form.currency.trim().toUpperCase() : null,
          timezone: form.timezone.trim() || null,
          language: form.language.trim() || null,
          brand_color: form.brand_color.trim() || null,
          setup_step: nextStep,
          setup_state: {
            ...(setupState ?? {}),
            wizard: {
              step: nextStep,
              saved_at: new Date().toISOString(),
            },
          },
        });

        setTenant(payload.tenant);
        setSetupState((prev) => ({
          ...(prev ?? {}),
          wizard: {
            step: nextStep,
            saved_at: new Date().toISOString(),
          },
        }));

        if (showStatus) {
          setStatus("Saved.");
          window.setTimeout(() => setStatus(null), 1500);
        }

        dirtyRef.current = false;
      } catch (e) {
        if (e instanceof ApiError) {
          setError(e.message);
        } else {
          setError(e instanceof Error ? e.message : "Failed to save setup.");
        }
      } finally {
        setSaving(false);
      }
    },
    [billingAddressJson, business, form, setupState],
  );

  useEffect(() => {
    if (auth.loading) return;
    if (auth.isAuthenticated && auth.isAdmin) {
      router.replace("/admin");
      return;
    }
  }, [auth.isAdmin, auth.isAuthenticated, auth.loading, router]);

  useEffect(() => {
    let alive = true;

    async function load() {
      if (typeof business !== "string" || business.length === 0) return;

      setLoading(true);
      setError(null);

      try {
        const payload = await getSetup(business);
        if (!alive) return;

        setTenant(payload.tenant);
        setSetupState(payload.setup.state ?? null);

        const t = payload.tenant;
        const addr = (t.billing_address_json ?? {}) as Record<string, unknown>;

        setForm({
          name: t.name ?? "",
          contact_email: t.contact_email ?? "",
          contact_phone: t.contact_phone ?? "",
          billing_country: (t.billing_country ?? "").toUpperCase(),
          billing_vat_number: t.billing_vat_number ?? "",
          currency: (t.currency ?? "USD").toUpperCase(),
          address_line1: typeof addr.line1 === "string" ? addr.line1 : "",
          address_line2: typeof addr.line2 === "string" ? addr.line2 : "",
          address_city: typeof addr.city === "string" ? addr.city : "",
          address_state: typeof addr.state === "string" ? addr.state : "",
          address_postal_code: typeof addr.postal_code === "string" ? addr.postal_code : "",
          brand_color: t.brand_color ?? "#2563eb",
          timezone: t.timezone ?? "UTC",
          language: t.language ?? "en",
        });

        const wizardStep =
          payload.setup.state && typeof payload.setup.state === "object"
            ? (((payload.setup.state as Record<string, unknown>).wizard as Record<string, unknown> | undefined)?.step ?? null)
            : null;

        const initialStep = clampStepId(payload.setup.step ?? wizardStep);
        setStep(initialStep);
        initializedRef.current = true;
        dirtyRef.current = false;
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load setup.");
      } finally {
        if (!alive) return;
        setLoading(false);
      }
    }

    void load();

    return () => {
      alive = false;
    };
  }, [business]);

  useEffect(() => {
    if (!initializedRef.current) return;
    if (!dirtyRef.current) return;
    if (typeof business !== "string" || business.length === 0) return;

    if (saveTimerRef.current) {
      window.clearTimeout(saveTimerRef.current);
    }

    saveTimerRef.current = window.setTimeout(() => {
      void persist(step, false);
    }, 800);

    return () => {
      if (saveTimerRef.current) {
        window.clearTimeout(saveTimerRef.current);
      }
    };
  }, [business, form, persist, step]);

  const setField = useCallback(<K extends keyof WizardState>(key: K, value: WizardState[K]) => {
    setForm((prev) => ({
      ...prev,
      [key]: value,
    }));
    dirtyRef.current = true;
  }, []);

  const onNext = useCallback(async () => {
    if (!canGoNext) return;
    const nextStep = stepOrder[stepIndex + 1] ?? step;

    const err = validateStep(nextStep);
    if (err) {
      setError(err);
      return;
    }

    setError(null);
    setStep(nextStep);
    await persist(nextStep, true);
  }, [canGoNext, persist, step, stepIndex, validateStep]);

  const onBack = useCallback(async () => {
    if (!canGoBack) return;
    const prevStep = stepOrder[stepIndex - 1] ?? step;
    setError(null);
    setStep(prevStep);
    await persist(prevStep, true);
  }, [canGoBack, persist, step, stepIndex]);

  const onUploadLogo = useCallback(
    async (file: File) => {
      if (typeof business !== "string" || business.length === 0) return;

      setUploadingLogo(true);
      setError(null);
      try {
        const payload = await updateSetup(business, { logo: file });
        setTenant(payload.tenant);
        setStatus("Logo uploaded.");
        window.setTimeout(() => setStatus(null), 1500);
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
    [business],
  );

  const onComplete = useCallback(async () => {
    if (typeof business !== "string" || business.length === 0) return;

    const err = validateStep("finish");
    if (err) {
      setError(err);
      return;
    }

    setCompleting(true);
    setError(null);

    try {
      await persist("finish", false);
      await completeSetup(business, {
        name: form.name.trim(),
        billing_country: form.billing_country.trim().toUpperCase(),
        timezone: form.timezone.trim(),
        language: form.language.trim(),
      });

      await auth.refresh();
      router.replace(`/app/${business}`);
    } catch (e) {
      if (e instanceof ApiError) {
        setError(e.message);
      } else {
        setError(e instanceof Error ? e.message : "Failed to complete setup.");
      }
    } finally {
      setCompleting(false);
    }
  }, [auth, business, form.billing_country, form.language, form.name, form.timezone, persist, router, validateStep]);

  if (auth.loading || loading) {
    return <Preloader label="Loading setup" />;
  }

  if (typeof business !== "string" || business.length === 0) {
    return (
      <div className="min-h-screen flex items-center justify-center px-6 bg-[var(--rb-surface-muted)]">
        <Card className="w-full max-w-lg shadow-none">
          <CardHeader>
            <CardTitle>Setup wizard</CardTitle>
            <CardDescription>Business is missing.</CardDescription>
          </CardHeader>
        </Card>
      </div>
    );
  }

  const tenantName = tenant?.name ?? business;

  return (
    <RequireAuth>
      <div className="min-h-screen bg-[var(--rb-surface-muted)]">
        <div className="mx-auto w-full max-w-3xl px-6 py-10 space-y-6">
          <PageHeader
            title="Business setup"
            description={`Finish setting up ${tenantName} to start using the app.`}
            actions={
              <div className="flex items-center gap-2">
                <Button
                  variant="outline"
                  size="sm"
                  disabled={saving}
                  onClick={() => {
                    void persist(step, true);
                  }}
                >
                  Save
                </Button>
              </div>
            }
          />

          {error ? <Alert variant="danger" title="Setup error">{error}</Alert> : null}
          {status ? <Alert variant="success" title="Status">{status}</Alert> : null}

          <Card className="shadow-none">
            <CardHeader>
              <div className="flex items-center justify-between gap-4">
                <div>
                  <CardTitle>Step {stepIndex + 1} of {stepOrder.length}</CardTitle>
                  <CardDescription>
                    {step === "business" ? "Confirm your business details." : null}
                    {step === "address" ? "Add billing location and address." : null}
                    {step === "branding" ? "Upload a logo and choose a brand color." : null}
                    {step === "preferences" ? "Choose default locale settings." : null}
                    {step === "finish" ? "Review and complete setup." : null}
                  </CardDescription>
                </div>
                <div className="text-xs text-zinc-500">{saving ? "Saving..." : "Autosave on"}</div>
              </div>
              <div className="mt-4 h-2 w-full rounded-full bg-[var(--rb-border)] overflow-hidden">
                <div
                  className="h-full bg-[var(--rb-blue)]"
                  style={{ width: `${Math.round(progress * 100)}%` }}
                />
              </div>
              <div className="mt-4 grid grid-cols-5 gap-2 text-xs">
                {stepOrder.map((s, idx) => (
                  <div
                    key={s}
                    className={
                      "rounded-[var(--rb-radius-sm)] border px-2 py-1 text-center " +
                      (idx <= stepIndex
                        ? "border-[color:color-mix(in_srgb,var(--rb-blue),white_70%)] bg-[color:color-mix(in_srgb,var(--rb-blue),white_92%)] text-[var(--rb-blue)]"
                        : "border-[var(--rb-border)] bg-white text-zinc-500")
                    }
                  >
                    {s === "business" ? "Business" : null}
                    {s === "address" ? "Address" : null}
                    {s === "branding" ? "Brand" : null}
                    {s === "preferences" ? "Defaults" : null}
                    {s === "finish" ? "Finish" : null}
                  </div>
                ))}
              </div>
            </CardHeader>

            <CardContent className="space-y-6">
              {step === "business" ? (
                <div className="grid gap-4 sm:grid-cols-2">
                  <div className="sm:col-span-2">
                    <label className="text-sm font-medium text-[var(--rb-text)]">Business name</label>
                    <div className="mt-1">
                      <Input
                        value={form.name}
                        onChange={(e) => setField("name", e.target.value)}
                        placeholder="e.g. RepairBuddy Electronics"
                        autoComplete="organization"
                      />
                    </div>
                  </div>

                  <div>
                    <label className="text-sm font-medium text-[var(--rb-text)]">Contact email</label>
                    <div className="mt-1">
                      <Input
                        type="email"
                        value={form.contact_email}
                        onChange={(e) => setField("contact_email", e.target.value)}
                        placeholder="billing@company.com"
                        autoComplete="email"
                      />
                    </div>
                  </div>

                  <div>
                    <label className="text-sm font-medium text-[var(--rb-text)]">Contact phone</label>
                    <div className="mt-1">
                      <Input
                        value={form.contact_phone}
                        onChange={(e) => setField("contact_phone", e.target.value)}
                        placeholder="+1 555 123 4567"
                        autoComplete="tel"
                      />
                    </div>
                  </div>
                </div>
              ) : null}

              {step === "address" ? (
                <div className="grid gap-4 sm:grid-cols-2">
                  <div>
                    <label className="text-sm font-medium text-[var(--rb-text)]">Billing country (2-letter)</label>
                    <div className="mt-1">
                      <Input
                        value={form.billing_country}
                        onChange={(e) => setField("billing_country", e.target.value.toUpperCase())}
                        placeholder="US"
                        maxLength={2}
                      />
                    </div>
                  </div>

                  <div>
                    <label className="text-sm font-medium text-[var(--rb-text)]">Currency</label>
                    <div className="mt-1">
                      <Input
                        value={form.currency}
                        onChange={(e) => setField("currency", e.target.value.toUpperCase())}
                        placeholder="USD"
                        maxLength={3}
                      />
                    </div>
                  </div>

                  <div className="sm:col-span-2">
                    <label className="text-sm font-medium text-[var(--rb-text)]">VAT number (optional)</label>
                    <div className="mt-1">
                      <Input
                        value={form.billing_vat_number}
                        onChange={(e) => setField("billing_vat_number", e.target.value)}
                        placeholder="EU123456789"
                      />
                    </div>
                  </div>

                  <div className="sm:col-span-2">
                    <div className="text-sm font-semibold text-[var(--rb-text)]">Billing address (optional)</div>
                    <div className="mt-1 grid gap-3 sm:grid-cols-2">
                      <div className="sm:col-span-2">
                        <Input
                          value={form.address_line1}
                          onChange={(e) => setField("address_line1", e.target.value)}
                          placeholder="Address line 1"
                          autoComplete="address-line1"
                        />
                      </div>
                      <div className="sm:col-span-2">
                        <Input
                          value={form.address_line2}
                          onChange={(e) => setField("address_line2", e.target.value)}
                          placeholder="Address line 2"
                          autoComplete="address-line2"
                        />
                      </div>
                      <Input
                        value={form.address_city}
                        onChange={(e) => setField("address_city", e.target.value)}
                        placeholder="City"
                        autoComplete="address-level2"
                      />
                      <Input
                        value={form.address_state}
                        onChange={(e) => setField("address_state", e.target.value)}
                        placeholder="State / Region"
                        autoComplete="address-level1"
                      />
                      <Input
                        value={form.address_postal_code}
                        onChange={(e) => setField("address_postal_code", e.target.value)}
                        placeholder="Postal code"
                        autoComplete="postal-code"
                      />
                    </div>
                  </div>
                </div>
              ) : null}

              {step === "branding" ? (
                <div className="grid gap-4 sm:grid-cols-2">
                  <div>
                    <label className="text-sm font-medium text-[var(--rb-text)]">Brand color</label>
                    <div className="mt-2 flex items-center gap-3">
                      <input
                        type="color"
                        value={form.brand_color}
                        onChange={(e) => setField("brand_color", e.target.value)}
                        className="h-10 w-14 rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white"
                        aria-label="Brand color"
                      />
                      <Input
                        value={form.brand_color}
                        onChange={(e) => setField("brand_color", e.target.value)}
                        placeholder="#2563eb"
                      />
                    </div>
                  </div>

                  <div>
                    <label className="text-sm font-medium text-[var(--rb-text)]">Logo</label>
                    <div className="mt-2 flex items-center gap-3">
                      {tenant?.logo_url ? (
                        // eslint-disable-next-line @next/next/no-img-element
                        <img
                          src={tenant.logo_url}
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
                          className="block w-full text-sm text-zinc-700 file:mr-3 file:rounded-[var(--rb-radius-sm)] file:border-0 file:bg-[var(--rb-surface-muted)] file:px-3 file:py-2 file:text-sm file:font-medium file:text-[var(--rb-text)]"
                        />
                      </div>
                    </div>
                    <div className="mt-2 text-xs text-zinc-500">
                      {uploadingLogo ? "Uploading..." : "PNG/JPG/WEBP up to 5MB"}
                    </div>
                  </div>
                </div>
              ) : null}

              {step === "preferences" ? (
                <div className="grid gap-4 sm:grid-cols-2">
                  <div>
                    <label className="text-sm font-medium text-[var(--rb-text)]">Timezone</label>
                    <div className="mt-1">
                      <Input
                        value={form.timezone}
                        onChange={(e) => setField("timezone", e.target.value)}
                        placeholder="UTC"
                      />
                    </div>
                    <div className="mt-2 text-xs text-zinc-500">Example: UTC, Europe/Berlin, America/New_York</div>
                  </div>

                  <div>
                    <label className="text-sm font-medium text-[var(--rb-text)]">Language</label>
                    <div className="mt-1">
                      <Input
                        value={form.language}
                        onChange={(e) => setField("language", e.target.value)}
                        placeholder="en"
                        maxLength={16}
                      />
                    </div>
                    <div className="mt-2 text-xs text-zinc-500">Example: en, de, fr</div>
                  </div>
                </div>
              ) : null}

              {step === "finish" ? (
                <div className="space-y-4">
                  <Alert variant="info" title="Review">
                    Please confirm the required fields. Clicking complete will finish setup and take you to the dashboard.
                  </Alert>

                  <div className="grid gap-3 sm:grid-cols-2">
                    <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-4">
                      <div className="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Business</div>
                      <div className="mt-1 text-sm font-medium text-[var(--rb-text)]">{form.name.trim() || "—"}</div>
                      <div className="mt-1 text-xs text-zinc-500">Contact: {form.contact_email.trim() || "—"}</div>
                    </div>
                    <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-4">
                      <div className="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Defaults</div>
                      <div className="mt-1 text-sm text-[var(--rb-text)]">Timezone: {form.timezone.trim() || "—"}</div>
                      <div className="mt-1 text-sm text-[var(--rb-text)]">Language: {form.language.trim() || "—"}</div>
                    </div>
                    <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-4">
                      <div className="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Billing</div>
                      <div className="mt-1 text-sm text-[var(--rb-text)]">
                        Country: {form.billing_country.trim() || "—"} | Currency: {form.currency.trim() || "—"}
                      </div>
                      <div className="mt-1 text-xs text-zinc-500">VAT: {form.billing_vat_number.trim() || "—"}</div>
                    </div>
                    <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-4">
                      <div className="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Branding</div>
                      <div className="mt-1 text-sm text-[var(--rb-text)]">Color: {form.brand_color}</div>
                      <div className="mt-1 text-xs text-zinc-500">Logo: {tenant?.logo_url ? "Uploaded" : "—"}</div>
                    </div>
                  </div>

                  <Button
                    variant="primary"
                    disabled={completing || saving}
                    onClick={() => {
                      void onComplete();
                    }}
                  >
                    {completing ? "Completing..." : "Complete setup"}
                  </Button>
                </div>
              ) : null}

              <div className="flex items-center justify-between pt-2">
                <Button variant="outline" disabled={!canGoBack || saving || completing} onClick={() => void onBack()}>
                  Back
                </Button>

                <div className="flex items-center gap-2">
                  {step !== "finish" ? (
                    <Button variant="primary" disabled={!canGoNext || saving || completing} onClick={() => void onNext()}>
                      Next
                    </Button>
                  ) : null}
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </RequireAuth>
  );
}
