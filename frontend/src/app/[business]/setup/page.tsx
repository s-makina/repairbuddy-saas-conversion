"use client";

import React, { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import Link from "next/link";
import { RequireAuth } from "@/components/RequireAuth";
import { Preloader } from "@/components/Preloader";
import { Alert } from "@/components/ui/Alert";
import { Button } from "@/components/ui/Button";
import { Badge } from "@/components/ui/Badge";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/Card";
import { Input } from "@/components/ui/Input";
import { PageHeader } from "@/components/ui/PageHeader";
import { useAuth } from "@/lib/auth";
import { ApiError } from "@/lib/api";
import { notify } from "@/lib/notify";
import type { Tenant } from "@/lib/types";
import { completeSetup, getSetup, updateSetup } from "@/lib/setup";

type StepId = "welcome" | "business" | "address" | "branding" | "preferences" | "tax" | "team" | "finish";

type WizardState = {
  name: string;
  display_name: string;
  primary_contact_name: string;
  contact_email: string;
  contact_phone: string;

  registration_number: string;

  billing_country: string;
  billing_vat_number: string;
  currency: string;

  address_line1: string;
  address_line2: string;
  address_city: string;
  address_state: string;
  address_postal_code: string;

  brand_color: string;

  support_email: string;
  support_phone: string;
  website: string;
  document_footer: string;

  timezone: string;
  language: string;

  working_hours: string;
  default_labor_rate: string;
  warranty_terms: string;
  notify_status_change: boolean;
  notify_invoice_created: boolean;

  tax_registered: boolean;
  invoice_prefix: string;

  team_invites: string;
  team_default_role: string;
};

const stepOrder: StepId[] = ["welcome", "business", "address", "branding", "preferences", "tax", "team", "finish"];

const skippableSteps: StepId[] = ["branding", "preferences", "tax", "team"];

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

function stepLabel(step: StepId): string {
  if (step === "welcome") return "Welcome";
  if (step === "business") return "Business";
  if (step === "address") return "Address";
  if (step === "branding") return "Brand";
  if (step === "preferences") return "Operations";
  if (step === "tax") return "Tax";
  if (step === "team") return "Team";
  return "Finish";
}

function stepDescription(step: StepId): string {
  if (step === "welcome") return "Set expectations and start setup.";
  if (step === "business") return "Confirm your business details.";
  if (step === "address") return "Add your address and locale details.";
  if (step === "branding") return "Upload a logo and configure customer-facing info.";
  if (step === "preferences") return "Set default operations and notification preferences.";
  if (step === "tax") return "Configure VAT and invoice numbering (optional).";
  if (step === "team") return "Invite your team (optional).";
  return "Review and complete setup.";
}

function clampStepId(step: unknown): StepId {
  if (typeof step === "string" && (stepOrder as string[]).includes(step)) {
    return step as StepId;
  }
  return "welcome";
}

export default function BusinessSetupPage() {
  const auth = useAuth();
  const router = useRouter();
  const params = useParams() as { business?: string; tenant?: string };
  const business = params.business ?? params.tenant;

  const [loading, setLoading] = useState(true);
  const [tenant, setTenant] = useState<Tenant | null>(null);

  const [step, setStep] = useState<StepId>("welcome");
  const [form, setForm] = useState<WizardState>({
    name: "",
    display_name: "",
    primary_contact_name: "",
    contact_email: "",
    contact_phone: "",
    registration_number: "",
    billing_country: "",
    billing_vat_number: "",
    currency: "USD",
    address_line1: "",
    address_line2: "",
    address_city: "",
    address_state: "",
    address_postal_code: "",
    brand_color: "#2563eb",
    support_email: "",
    support_phone: "",
    website: "",
    document_footer: "",
    timezone: "UTC",
    language: "en",
    working_hours: "Mon–Fri 09:00–17:00",
    default_labor_rate: "",
    warranty_terms: "",
    notify_status_change: true,
    notify_invoice_created: false,

    tax_registered: false,
    invoice_prefix: "RB",

    team_invites: "",
    team_default_role: "member",
  });

  const [setupState, setSetupState] = useState<Record<string, unknown> | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);
  const [uploadingLogo, setUploadingLogo] = useState(false);
  const [completing, setCompleting] = useState(false);

  const initializedRef = useRef(false);
  const dirtyRef = useRef(false);
  const saveTimerRef = useRef<number | null>(null);

  const stepIndex = stepOrder.indexOf(step);
  const progress = Math.max(0, stepIndex) / (stepOrder.length - 1);

  const timezoneOptions = useMemo(() => {
    try {
      const anyIntl = Intl as unknown as { supportedValuesOf?: (key: string) => string[] };
      const values = typeof anyIntl.supportedValuesOf === "function" ? anyIntl.supportedValuesOf("timeZone") : null;
      return Array.isArray(values) && values.length > 0 ? values : fallbackTimezones;
    } catch {
      return fallbackTimezones;
    }
  }, []);

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
  const canSkip = skippableSteps.includes(step) && canGoNext;

  const validateStep = useCallback(
    (target: StepId): string | null => {
      if (target === "welcome") {
        return null;
      }
      if (target === "business") {
        if (!form.name.trim()) return "Business name is required.";
        if (!form.primary_contact_name.trim()) return "Primary contact person name is required.";
        if (!form.contact_email.trim()) return "Primary contact email is required.";
        if (!form.contact_phone.trim()) return "Primary contact phone is required.";
      }
      if (target === "address") {
        if (!form.billing_country.trim()) return "Billing country is required.";
        if (form.billing_country.trim().length !== 2) return "Billing country must be a 2-letter code (e.g. US).";
        if (!form.currency.trim()) return "Currency is required.";
        if (form.currency.trim().length !== 3) return "Currency must be a 3-letter code (e.g. USD).";
        if (!form.address_line1.trim()) return "Address line 1 is required.";
        if (!form.address_city.trim()) return "City is required.";
        if (!form.address_postal_code.trim()) return "Postal code is required.";
      }
      if (target === "preferences") {
        if (!form.timezone.trim()) return "Timezone is required.";
      }
      if (target === "tax") {
        if (form.tax_registered && !form.billing_vat_number.trim()) {
          return "VAT number is required when tax/VAT registered is enabled.";
        }
      }
      if (target === "finish") {
        if (!form.name.trim()) return "Business name is required.";
        if (!form.primary_contact_name.trim()) return "Primary contact person name is required.";
        if (!form.contact_email.trim()) return "Primary contact email is required.";
        if (!form.contact_phone.trim()) return "Primary contact phone is required.";
        if (!form.billing_country.trim() || form.billing_country.trim().length !== 2) return "Billing country is required.";
        if (!form.currency.trim() || form.currency.trim().length !== 3) return "Currency is required.";
        if (!form.address_line1.trim()) return "Address line 1 is required.";
        if (!form.address_city.trim()) return "City is required.";
        if (!form.address_postal_code.trim()) return "Postal code is required.";
        if (!form.timezone.trim()) return "Timezone is required.";
        if (form.tax_registered && !form.billing_vat_number.trim()) {
          return "VAT number is required when tax/VAT registered is enabled.";
        }
      }
      return null;
    },
    [form],
  );

  const persist = useCallback(
    async (nextStep: StepId, showStatus: boolean) => {
      if (typeof business !== "string" || business.length === 0) return;

      const nextSetupState: Record<string, unknown> = {
        ...(setupState ?? {}),
        wizard: {
          step: nextStep,
          saved_at: new Date().toISOString(),
        },
        identity: {
          display_name: form.display_name.trim() || null,
          primary_contact_name: form.primary_contact_name.trim() || null,
          registration_number: form.registration_number.trim() || null,
        },
        branding: {
          support_email: form.support_email.trim() || null,
          support_phone: form.support_phone.trim() || null,
          website: form.website.trim() || null,
          document_footer: form.document_footer.trim() || null,
        },
        operations: {
          working_hours: form.working_hours.trim() || null,
          default_labor_rate: form.default_labor_rate.trim() || null,
          warranty_terms: form.warranty_terms.trim() || null,
          notify_status_change: Boolean(form.notify_status_change),
          notify_invoice_created: Boolean(form.notify_invoice_created),
        },
        tax: {
          tax_registered: Boolean(form.tax_registered),
          invoice_prefix: form.invoice_prefix.trim() || null,
        },
        team: {
          invites: form.team_invites.trim() || null,
          default_role: form.team_default_role.trim() || null,
        },
      };

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
          language: "en",
          brand_color: form.brand_color.trim() || null,
          setup_step: nextStep,
          setup_state: nextSetupState,
        });

        setTenant(payload.tenant);
        setSetupState(nextSetupState);

        if (showStatus) {
          notify.success("Saved.");
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
        const state = (payload.setup.state ?? {}) as Record<string, unknown>;
        const identity = (state.identity ?? {}) as Record<string, unknown>;
        const branding = (state.branding ?? {}) as Record<string, unknown>;
        const operations = (state.operations ?? {}) as Record<string, unknown>;
        const tax = (state.tax ?? {}) as Record<string, unknown>;
        const team = (state.team ?? {}) as Record<string, unknown>;

        setForm({
          name: t.name ?? "",
          display_name: typeof identity.display_name === "string" ? identity.display_name : "",
          primary_contact_name: typeof identity.primary_contact_name === "string" ? identity.primary_contact_name : "",
          contact_email: t.contact_email ?? "",
          contact_phone: t.contact_phone ?? "",
          registration_number: typeof identity.registration_number === "string" ? identity.registration_number : "",
          billing_country: (t.billing_country ?? "").toUpperCase(),
          billing_vat_number: t.billing_vat_number ?? "",
          currency: (t.currency ?? "USD").toUpperCase(),
          address_line1: typeof addr.line1 === "string" ? addr.line1 : "",
          address_line2: typeof addr.line2 === "string" ? addr.line2 : "",
          address_city: typeof addr.city === "string" ? addr.city : "",
          address_state: typeof addr.state === "string" ? addr.state : "",
          address_postal_code: typeof addr.postal_code === "string" ? addr.postal_code : "",
          brand_color: t.brand_color ?? "#2563eb",
          support_email: typeof branding.support_email === "string" ? branding.support_email : "",
          support_phone: typeof branding.support_phone === "string" ? branding.support_phone : "",
          website: typeof branding.website === "string" ? branding.website : "",
          document_footer: typeof branding.document_footer === "string" ? branding.document_footer : "",
          timezone: t.timezone ?? "UTC",
          language: "en",
          working_hours: typeof operations.working_hours === "string" ? operations.working_hours : "Mon–Fri 09:00–17:00",
          default_labor_rate: typeof operations.default_labor_rate === "string" ? operations.default_labor_rate : "",
          warranty_terms: typeof operations.warranty_terms === "string" ? operations.warranty_terms : "",
          notify_status_change: typeof operations.notify_status_change === "boolean" ? operations.notify_status_change : true,
          notify_invoice_created: typeof operations.notify_invoice_created === "boolean" ? operations.notify_invoice_created : false,

          tax_registered: typeof tax.tax_registered === "boolean" ? tax.tax_registered : false,
          invoice_prefix: typeof tax.invoice_prefix === "string" ? tax.invoice_prefix : "RB",

          team_invites: typeof team.invites === "string" ? team.invites : "",
          team_default_role: typeof team.default_role === "string" ? team.default_role : "member",
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

    const err = validateStep(step);
    if (err) {
      setError(err);
      return;
    }

    setError(null);
    setStep(nextStep);
    await persist(nextStep, true);
  }, [canGoNext, persist, step, stepIndex, validateStep]);

  const onSkip = useCallback(async () => {
    if (!canSkip) return;
    const nextStep = stepOrder[stepIndex + 1] ?? step;
    setError(null);
    setStep(nextStep);
    await persist(nextStep, true);
  }, [canSkip, persist, step, stepIndex]);

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
        notify.success("Logo uploaded.");
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

      if (!billingAddressJson) {
        throw new Error("Billing address is required.");
      }

      await completeSetup(business, {
        name: form.name.trim(),
        contact_email: form.contact_email.trim(),
        contact_phone: form.contact_phone.trim(),
        billing_country: form.billing_country.trim().toUpperCase(),
        billing_address_json: billingAddressJson,
        currency: form.currency.trim().toUpperCase(),
        timezone: form.timezone.trim(),
        language: "en",
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
  }, [auth, billingAddressJson, business, form.billing_country, form.contact_email, form.contact_phone, form.currency, form.language, form.name, form.timezone, persist, router, validateStep]);

  if (auth.loading || loading) {
    return <Preloader label="Loading setup" />;
  }

  if (typeof business !== "string" || business.length === 0) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-[var(--rb-surface)] px-6">
          <Card className="w-full max-w-lg shadow-none">
            <CardHeader>
              <CardTitle className="text-base">Setup wizard</CardTitle>
              <CardDescription>Business is missing.</CardDescription>
            </CardHeader>
          </Card>
      </div>
    );
  }

  const tenantName = tenant?.name ?? business;

  return (
    <RequireAuth>
      <div className="min-h-screen text-[var(--rb-text)] [background:radial-gradient(1200px_circle_at_20%_0%,color-mix(in_srgb,var(--rb-blue),white_88%)_0%,transparent_55%),radial-gradient(900px_circle_at_80%_15%,color-mix(in_srgb,var(--rb-orange),white_86%)_0%,transparent_60%),var(--rb-surface)]">
        <header className="sticky top-0 z-20 border-b border-[var(--rb-border)] bg-white/70 backdrop-blur">
          <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
            <div className="flex items-center gap-3">
              <Link href="/" className="font-semibold tracking-tight text-[var(--rb-text)]">
                99smartx
              </Link>
              <Badge variant="info" className="hidden sm:inline-flex">
                Setup
              </Badge>
            </div>

            <nav className="hidden items-center gap-6 text-sm text-zinc-600 md:flex">
              <Link href="/#features" className="hover:text-[var(--rb-text)]">
                Features
              </Link>
              <Link href="/#pricing" className="hover:text-[var(--rb-text)]">
                Pricing
              </Link>
              <Link href="/#faq" className="hover:text-[var(--rb-text)]">
                FAQ
              </Link>
            </nav>

            <div className="flex items-center gap-2">
              <Button variant="outline" onClick={() => router.replace(`/${business}/plans`)}>
                Back
              </Button>
            </div>
          </div>
        </header>

        <main>
          <section className="mx-auto w-full max-w-6xl px-4 py-10">
            <div className="mx-auto w-full max-w-5xl space-y-6">
                <PageHeader
                  title="Business setup"
                  description={`Finish setting up ${tenantName} to start using the app.`}
                  actions={
                    <div className="flex items-center gap-2">
                      <div className="hidden sm:block text-xs text-zinc-500">{saving ? "Saving..." : "Autosave on"}</div>
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

                <div className="grid gap-6 lg:grid-cols-[260px_1fr]">
                  <Card className="shadow-none lg:sticky lg:top-6 lg:self-start">
                    <CardHeader className="pb-3">
                      <CardTitle className="text-base">Setup steps</CardTitle>
                      <CardDescription>Complete the steps to start using the app.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                      <div className="h-2 w-full rounded-full bg-[var(--rb-border)] overflow-hidden">
                        <div
                          className="h-full bg-[linear-gradient(90deg,var(--rb-blue),var(--rb-orange))]"
                          style={{ width: `${Math.round(progress * 100)}%` }}
                        />
                      </div>

                      <nav aria-label="Setup steps" className="space-y-1">
                        {stepOrder.map((s, idx) => {
                          const isCurrent = idx === stepIndex;
                          const isCompleted = idx < stepIndex;
                          const isAvailable = idx <= stepIndex;

                          return (
                            <button
                              key={s}
                              type="button"
                              disabled={!isAvailable || saving || completing}
                              onClick={() => {
                                if (!isAvailable) return;
                                setError(null);
                                setStep(s);
                                void persist(s, true);
                              }}
                              className={
                                "w-full rounded-[var(--rb-radius-md)] border px-3 py-2 text-left transition " +
                                (isCurrent
                                  ? "border-[color:color-mix(in_srgb,var(--rb-blue),white_65%)] bg-[color:color-mix(in_srgb,var(--rb-blue),white_92%)]"
                                  : isCompleted
                                    ? "border-[color:color-mix(in_srgb,var(--rb-blue),white_75%)] bg-white hover:bg-[var(--rb-surface-muted)]"
                                    : "border-[var(--rb-border)] bg-white opacity-60")
                              }
                            >
                              <div className="flex items-center gap-3">
                                <div
                                  className={
                                    "flex h-7 w-7 shrink-0 items-center justify-center rounded-full border text-xs font-semibold " +
                                    (isCurrent
                                      ? "border-[var(--rb-blue)] bg-[var(--rb-blue)] text-white"
                                      : isCompleted
                                        ? "border-[var(--rb-blue)] bg-[color:color-mix(in_srgb,var(--rb-blue),white_90%)] text-[var(--rb-blue)]"
                                        : "border-[var(--rb-border)] bg-white text-zinc-600")
                                  }
                                >
                                  {isCompleted ? (
                                    <svg viewBox="0 0 24 24" className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                      <path d="M20 6L9 17l-5-5" />
                                    </svg>
                                  ) : (
                                    idx + 1
                                  )}
                                </div>
                                <div className="min-w-0">
                                  <div className="text-sm font-semibold text-[var(--rb-text)]">{stepLabel(s)}</div>
                                  <div className="mt-0.5 text-xs text-zinc-600 line-clamp-2">{stepDescription(s)}</div>
                                </div>
                              </div>
                            </button>
                          );
                        })}
                      </nav>
                    </CardContent>
                  </Card>

                  <Card className="shadow-none flex min-h-[calc(100vh-220px)] flex-col">
                    <CardHeader>
                      <div className="flex items-start justify-between gap-4">
                        <div>
                          <CardTitle className="text-base">{stepLabel(step)}</CardTitle>
                          <CardDescription>{stepDescription(step)}</CardDescription>
                        </div>
                        <div className="text-right">
                          <div className="text-xs text-zinc-500">Step {stepIndex + 1} of {stepOrder.length}</div>
                          <div className="mt-2 flex items-center justify-end gap-2">
                            <div className="flex items-center gap-1" aria-label="Progress">
                              {stepOrder.map((_, idx) => {
                                const isDone = idx < stepIndex;
                                const isNow = idx === stepIndex;
                                return (
                                  <span
                                    key={idx}
                                    className={
                                      "h-1.5 w-5 rounded-full transition " +
                                      (isNow
                                        ? "bg-[var(--rb-blue)]"
                                        : isDone
                                          ? "bg-[color:color-mix(in_srgb,var(--rb-blue),white_55%)]"
                                          : "bg-[var(--rb-border)]")
                                    }
                                  />
                                );
                              })}
                            </div>
                            <div className="rounded-full border border-[var(--rb-border)] bg-white px-2 py-1 text-[11px] font-medium text-zinc-600">
                              {Math.round(progress * 100)}%
                            </div>
                          </div>
                        </div>
                      </div>
                    </CardHeader>

                    <CardContent className="flex-1 space-y-6">
                {step === "welcome" ? (
                  <div className="space-y-6">
                    <div className="relative overflow-hidden rounded-[var(--rb-radius-lg)] border border-[color:color-mix(in_srgb,var(--rb-blue),white_80%)] bg-[linear-gradient(135deg,color-mix(in_srgb,var(--rb-blue),white_90%),white_70%)] p-6">
                      <div className="absolute -right-16 -top-16 h-56 w-56 rounded-full bg-[color:color-mix(in_srgb,var(--rb-blue),white_75%)] blur-2xl" aria-hidden="true" />
                      <div className="relative flex items-start gap-4">
                        <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-[color:color-mix(in_srgb,var(--rb-blue),white_70%)] bg-white/70 text-[var(--rb-blue)]">
                          <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                            <path d="M12 2l1.7 5.3L19 9l-5.3 1.7L12 16l-1.7-5.3L5 9l5.3-1.7L12 2z" />
                            <path d="M5 14l.9 2.8L9 18l-3.1 1.2L5 22l-.9-2.8L1 18l3.1-1.2L5 14z" />
                          </svg>
                        </div>
                        <div className="min-w-0">
                          <div className="text-xl font-semibold text-[var(--rb-text)]">Welcome — let’s get your business ready</div>
                          <div className="mt-1 text-sm text-zinc-700">
                            This wizard saves as you go and takes about 3–5 minutes. You can skip optional steps and come back later.
                          </div>
                        </div>
                      </div>

                      <div className="relative mt-5 grid gap-3 sm:grid-cols-3">
                        <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white/70 px-4 py-3">
                          <div className="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Time</div>
                          <div className="mt-1 text-sm font-medium text-[var(--rb-text)]">3–5 minutes</div>
                        </div>
                        <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white/70 px-4 py-3">
                          <div className="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Saving</div>
                          <div className="mt-1 text-sm font-medium text-[var(--rb-text)]">Auto-saved</div>
                        </div>
                        <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white/70 px-4 py-3">
                          <div className="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Flexibility</div>
                          <div className="mt-1 text-sm font-medium text-[var(--rb-text)]">Optional steps</div>
                        </div>
                      </div>
                    </div>

                    <div>
                      <div className="text-sm font-medium text-[var(--rb-text)]">What you’ll configure</div>
                      <div className="mt-2 grid gap-2 sm:grid-cols-2">
                        {stepOrder
                          .filter((s) => s !== "welcome")
                          .map((s) => {
                            const isOptional = skippableSteps.includes(s);
                            return (
                              <div
                                key={s}
                                className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white px-4 py-3"
                              >
                                <div className="flex items-center justify-between gap-3">
                                  <div className="text-sm font-semibold text-[var(--rb-text)]">{stepLabel(s)}</div>
                                  {isOptional ? (
                                    <span className="rounded-full border border-[var(--rb-border)] bg-[var(--rb-surface-muted)] px-2 py-0.5 text-[11px] font-medium text-zinc-600">
                                      Optional
                                    </span>
                                  ) : null}
                                </div>
                                <div className="mt-1 text-xs text-zinc-600">{stepDescription(s)}</div>
                              </div>
                            );
                          })}
                      </div>
                    </div>

                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                      <Button
                        variant="primary"
                        size="lg"
                        disabled={saving || completing}
                        onClick={() => {
                          void onNext();
                        }}
                      >
                        Start setup
                      </Button>
                      <div className="text-xs text-zinc-500">You can adjust everything later from Settings.</div>
                    </div>
                  </div>
                ) : null}

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

                  <div className="sm:col-span-2">
                    <label className="text-sm font-medium text-[var(--rb-text)]">Display name (optional)</label>
                    <div className="mt-1">
                      <Input
                        value={form.display_name}
                        onChange={(e) => setField("display_name", e.target.value)}
                        placeholder="Defaults to business name"
                      />
                    </div>
                  </div>

                  <div className="sm:col-span-2">
                    <label className="text-sm font-medium text-[var(--rb-text)]">Primary contact person name</label>
                    <div className="mt-1">
                      <Input
                        value={form.primary_contact_name}
                        onChange={(e) => setField("primary_contact_name", e.target.value)}
                        placeholder="e.g. Alex Johnson"
                        autoComplete="name"
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

                  <div className="sm:col-span-2">
                    <label className="text-sm font-medium text-[var(--rb-text)]">Business registration number (optional)</label>
                    <div className="mt-1">
                      <Input
                        value={form.registration_number}
                        onChange={(e) => setField("registration_number", e.target.value)}
                        placeholder="e.g. CRN-123456"
                      />
                    </div>
                  </div>
                </div>
              ) : null}

              {step === "tax" ? (
                <div className="grid gap-4 sm:grid-cols-2">
                  <div className="sm:col-span-2">
                    <label className="flex items-center gap-2 text-sm text-[var(--rb-text)]">
                      <input
                        type="checkbox"
                        checked={form.tax_registered}
                        onChange={(e) => setField("tax_registered", e.target.checked)}
                      />
                      Tax/VAT registered?
                    </label>
                    <div className="mt-2 text-xs text-zinc-500">
                      If enabled, VAT number becomes required to complete setup.
                    </div>
                  </div>

                  <div className="sm:col-span-2">
                    <label className="text-sm font-medium text-[var(--rb-text)]">VAT number</label>
                    <div className="mt-1">
                      <Input
                        value={form.billing_vat_number}
                        onChange={(e) => setField("billing_vat_number", e.target.value)}
                        placeholder="EU123456789"
                      />
                    </div>
                  </div>

                  <div>
                    <label className="text-sm font-medium text-[var(--rb-text)]">Invoice prefix (optional)</label>
                    <div className="mt-1">
                      <Input
                        value={form.invoice_prefix}
                        onChange={(e) => setField("invoice_prefix", e.target.value)}
                        placeholder="RB"
                      />
                    </div>
                  </div>

                  <div>
                    <label className="text-sm font-medium text-[var(--rb-text)]">Invoice format preview</label>
                    <div className="mt-2 rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm text-zinc-700">
                      {(form.invoice_prefix.trim() || "RB") + "-" + business + "-YYYY-000001"}
                    </div>
                  </div>
                </div>
              ) : null}

              {step === "team" ? (
                <div className="space-y-4">
                  <div>
                    <label className="text-sm font-medium text-[var(--rb-text)]">Invite team members (optional)</label>
                    <div className="mt-1">
                      <Input
                        value={form.team_invites}
                        onChange={(e) => setField("team_invites", e.target.value)}
                        placeholder="Emails separated by commas"
                      />
                    </div>
                    <div className="mt-2 text-xs text-zinc-500">Invites are stored for later processing (email sending not implemented yet).</div>
                  </div>

                  <div>
                    <label className="text-sm font-medium text-[var(--rb-text)]">Default role for invites (optional)</label>
                    <div className="mt-1">
                      <Input
                        value={form.team_default_role}
                        onChange={(e) => setField("team_default_role", e.target.value)}
                        placeholder="member"
                      />
                    </div>
                    <div className="mt-2 text-xs text-zinc-500">Example values: owner, member, technician, front_desk.</div>
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
                    <div className="text-sm font-semibold text-[var(--rb-text)]">Address</div>
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
                        className="h-10 w-14 rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white shadow-sm"
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
                          className="block w-full text-sm text-zinc-700 file:mr-3 file:rounded-[var(--rb-radius-sm)] file:border-0 file:bg-[var(--rb-surface-muted)] file:px-3 file:py-2 file:text-sm file:font-medium file:text-[var(--rb-text)] hover:file:bg-[color:color-mix(in_srgb,var(--rb-surface-muted),black_4%)]"
                        />
                      </div>
                    </div>
                    <div className="mt-2 text-xs text-zinc-500">
                      {uploadingLogo ? "Uploading..." : "PNG/JPG/WEBP up to 5MB"}
                    </div>
                  </div>

                  <div className="sm:col-span-2">
                    <div className="text-sm font-semibold text-[var(--rb-text)]">Customer-facing contact (optional)</div>
                    <div className="mt-2 grid gap-3 sm:grid-cols-2">
                      <Input
                        value={form.support_email}
                        onChange={(e) => setField("support_email", e.target.value)}
                        placeholder="Public support email"
                        autoComplete="email"
                      />
                      <Input
                        value={form.support_phone}
                        onChange={(e) => setField("support_phone", e.target.value)}
                        placeholder="Public support phone"
                        autoComplete="tel"
                      />
                      <div className="sm:col-span-2">
                        <Input
                          value={form.website}
                          onChange={(e) => setField("website", e.target.value)}
                          placeholder="Website (optional)"
                        />
                      </div>
                      <div className="sm:col-span-2">
                        <Input
                          value={form.document_footer}
                          onChange={(e) => setField("document_footer", e.target.value)}
                          placeholder="Document footer text (optional)"
                        />
                      </div>
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
                        list="rb-timezones"
                      />
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
                          value={form.working_hours}
                          onChange={(e) => setField("working_hours", e.target.value)}
                          placeholder="Working hours (e.g. Mon–Fri 09:00–17:00)"
                        />
                      </div>
                      <Input
                        value={form.default_labor_rate}
                        onChange={(e) => setField("default_labor_rate", e.target.value)}
                        placeholder="Default labor rate (optional)"
                      />
                      <Input
                        value={form.warranty_terms}
                        onChange={(e) => setField("warranty_terms", e.target.value)}
                        placeholder="Default warranty terms (optional)"
                      />
                      <label className="sm:col-span-2 flex items-center gap-2 text-sm text-[var(--rb-text)]">
                        <input
                          type="checkbox"
                          checked={form.notify_status_change}
                          onChange={(e) => setField("notify_status_change", e.target.checked)}
                        />
                        Email on status change
                      </label>
                      <label className="sm:col-span-2 flex items-center gap-2 text-sm text-[var(--rb-text)]">
                        <input
                          type="checkbox"
                          checked={form.notify_invoice_created}
                          onChange={(e) => setField("notify_invoice_created", e.target.checked)}
                        />
                        Email on invoice created
                      </label>
                    </div>
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
                      <div className="mt-1 text-xs text-zinc-500">Primary contact: {form.primary_contact_name.trim() || "—"}</div>
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
                      <div className="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Tax & invoicing</div>
                      <div className="mt-1 text-sm text-[var(--rb-text)]">
                        Registered: {form.tax_registered ? "Yes" : "No"}
                      </div>
                      <div className="mt-1 text-xs text-zinc-500">Prefix: {form.invoice_prefix.trim() || "—"}</div>
                    </div>
                    <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-4">
                      <div className="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Branding</div>
                      <div className="mt-1 text-sm text-[var(--rb-text)]">Color: {form.brand_color}</div>
                      <div className="mt-1 text-xs text-zinc-500">Logo: {tenant?.logo_url ? "Uploaded" : "—"}</div>
                    </div>
                    <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-4">
                      <div className="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Team</div>
                      <div className="mt-1 text-xs text-zinc-500">Invites: {form.team_invites.trim() ? "Drafted" : "—"}</div>
                      <div className="mt-1 text-xs text-zinc-500">Role: {form.team_default_role.trim() || "—"}</div>
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

              </CardContent>

              <div className="sticky bottom-0 border-t border-[var(--rb-border)] bg-white/90 px-5 py-4 backdrop-blur">
                <div className="flex items-center justify-between gap-4">
                  <Button variant="ghost" disabled={!canGoBack || saving || completing} onClick={() => void onBack()}>
                    <span className="inline-flex items-center gap-2">
                      <svg viewBox="0 0 24 24" className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                        <path d="M15 18l-6-6 6-6" />
                      </svg>
                      Back
                    </span>
                  </Button>

                  <div className="flex items-center gap-3">
                    <div className="hidden sm:block text-xs text-zinc-500">
                      {stepLabel(step)}
                      <span className="mx-2">•</span>
                      {Math.round(progress * 100)}%
                    </div>

                    {canSkip ? (
                      <Button variant="outline" disabled={saving || completing} onClick={() => void onSkip()}>
                        Skip for now
                      </Button>
                    ) : null}

                    {step !== "finish" ? (
                      <Button variant="primary" disabled={!canGoNext || saving || completing} onClick={() => void onNext()}>
                        <span className="inline-flex items-center gap-2">
                          {step === "welcome" ? "Start" : "Next"}
                          <svg viewBox="0 0 24 24" className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                            <path d="M9 18l6-6-6-6" />
                          </svg>
                        </span>
                      </Button>
                    ) : null}
                  </div>
                </div>
              </div>
            </Card>
          </div>

          <footer className="mt-12 border-t border-[var(--rb-border)] pt-8 text-xs text-zinc-600">
            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
              <div>© {new Date().getFullYear()} 99smartx</div>
              <div className="flex items-center gap-4">
                <Link href="/login" className="hover:text-[var(--rb-text)]">
                  Login
                </Link>
                <Link href="/register" className="hover:text-[var(--rb-text)]">
                  Register
                </Link>
              </div>
            </div>
          </footer>

            </div>
          </section>
        </main>
      </div>
    </RequireAuth>
  );
}
