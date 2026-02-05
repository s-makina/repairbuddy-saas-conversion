"use client";

import React from "react";
import Image from "next/image";
import Link from "next/link";
import { useParams } from "next/navigation";
import { PublicPageShell } from "@/components/PublicPageShell";
import { Card, CardContent } from "@/components/ui/Card";
import { Alert } from "@/components/ui/Alert";
import { Button } from "@/components/ui/Button";
import { Input } from "@/components/ui/Input";
import { Badge } from "@/components/ui/Badge";
import { Select } from "@/components/ui/Select";
import { Skeleton } from "@/components/ui/Skeleton";
import { WizardShell } from "@/components/repairbuddy/wizard/WizardShell";
import { apiFetch, ApiError } from "@/lib/api";
import { cn } from "@/lib/cn";
import { formatMoney } from "@/lib/money";

type BookingConfig = {
  disabled: boolean;
  general: {
    gdprAcceptanceText: string | null;
    gdprLinkLabel: string | null;
    gdprLinkUrl: string | null;
    defaultCountry: string | null;
  };
  devicesBrands: {
    enablePinCodeField: boolean;
    labels: unknown;
    additionalDeviceFields: Array<{ id?: unknown; label?: unknown; type?: unknown; displayInBookingForm?: unknown }>;
  };
  booking: {
    publicBookingMode: "ungrouped" | "grouped" | "warranty";
    publicBookingUiStyle: "wizard" | "images";
    sendBookingQuoteToJobs: boolean;
    customerCreationEmailBehavior: "send_login_credentials" | "no_email";
    turnOffOtherDeviceBrand: boolean;
    turnOffOtherService: boolean;
    turnOffServicePrice: boolean;
    turnOffIdImeiInBooking: boolean;
    defaultType: string;
    defaultBrand: string;
    defaultDevice: string;
  };
};

type ApiDeviceType = { id: number; parent_id: number | null; name: string; description: string | null; image_url: string | null };
type ApiBrand = { id: number; name: string; image_url: string | null };
type ApiDevice = { id: number; model: string; device_type_id: number | null; device_brand_id: number | null; parent_device_id: number | null; is_other: boolean };
type ApiServiceType = { id: number; name: string };
type ApiService = {
  id: number;
  name: string;
  description: string | null;
  service_type: ApiServiceType | null;
  is_active: boolean;
  price: { currency: string; amount_cents: number } | null;
  tax_id: number | null;
};
type ApiServiceGroup = { service_type: ApiServiceType | null; services: ApiService[] };

type ExtraFieldDraft = { key: string; label: string; value_text: string };

type SubmitResult = {
  entity: "job" | "estimate";
  id: number;
  case_number: string;
  customer_id: number;
};

type BookingUiStyle = "wizard" | "images";

function portalSessionKey(tenantSlug: string) {
  return `rb.portal.session:v1:${tenantSlug}`;
}

function safeInt(value: string): number | null {
  const n = Number(value);
  return Number.isFinite(n) && n > 0 ? Math.trunc(n) : null;
}

function errorMessage(e: unknown): string {
  if (e instanceof ApiError) {
    const data = e.data;
    if (data && typeof data === "object" && "errors" in (data as Record<string, unknown>)) {
      const errors = (data as Record<string, unknown>).errors;
      if (errors && typeof errors === "object") {
        const first = Object.values(errors as Record<string, unknown>)[0];
        if (Array.isArray(first) && typeof first[0] === "string") {
          return first[0];
        }
      }
    }
    return e.message;
  }
  return e instanceof Error ? e.message : "Booking failed.";
}

function PublicBookingPageSkeleton() {
  return (
    <div className="space-y-5">
      <div className="space-y-2">
        <Skeleton className="h-6 w-56 rounded-[var(--rb-radius-sm)]" />
        <Skeleton className="h-4 w-4/5 rounded-[var(--rb-radius-sm)]" />
      </div>

      <div className="grid gap-4 lg:grid-cols-[280px_minmax(0,1fr)]">
        <div className="space-y-4">
          <Skeleton className="h-4 w-32 rounded-[var(--rb-radius-sm)]" />
          <div className="space-y-2">
            <Skeleton className="h-10 w-full rounded-[var(--rb-radius-md)]" />
            <Skeleton className="h-10 w-full rounded-[var(--rb-radius-md)]" />
            <Skeleton className="h-10 w-full rounded-[var(--rb-radius-md)]" />
            <Skeleton className="h-10 w-full rounded-[var(--rb-radius-md)]" />
          </div>
          <Skeleton className="h-28 w-full rounded-[var(--rb-radius-md)]" />
        </div>

        <div className="space-y-4">
          <div className="space-y-2">
            <Skeleton className="h-6 w-64 rounded-[var(--rb-radius-sm)]" />
            <Skeleton className="h-4 w-full rounded-[var(--rb-radius-sm)]" />
          </div>
          <div className="space-y-3">
            <Skeleton className="h-10 w-full rounded-[var(--rb-radius-md)]" />
            <Skeleton className="h-10 w-full rounded-[var(--rb-radius-md)]" />
            <Skeleton className="h-10 w-11/12 rounded-[var(--rb-radius-md)]" />
            <Skeleton className="h-10 w-10/12 rounded-[var(--rb-radius-md)]" />
          </div>
          <div className="flex items-center justify-end gap-2 pt-2">
            <Skeleton className="h-10 w-28 rounded-[var(--rb-radius-md)]" />
          </div>
        </div>
      </div>
    </div>
  );
}

export default function PublicBookingPage() {
  const params = useParams() as { tenant?: string };
  const tenantSlug = typeof params.tenant === "string" ? params.tenant : "";

  const [configLoading, setConfigLoading] = React.useState(true);
  const [configError, setConfigError] = React.useState<string | null>(null);
  const [config, setConfig] = React.useState<BookingConfig | null>(null);

  const [step, setStep] = React.useState<1 | 2 | 3 | 4>(1);
  const [flowError, setFlowError] = React.useState<string | null>(null);

  const [uiStyle, setUiStyle] = React.useState<BookingUiStyle>("wizard");

  const [typeId, setTypeId] = React.useState<string>("");
  const [brandId, setBrandId] = React.useState<string>("");
  const [deviceId, setDeviceId] = React.useState<string>("");
  const [otherDeviceLabel, setOtherDeviceLabel] = React.useState<string>("");

  const [serial, setSerial] = React.useState<string>("");
  const [pin, setPin] = React.useState<string>("");
  const [deviceNotes, setDeviceNotes] = React.useState<string>("");

  const [extraFields, setExtraFields] = React.useState<ExtraFieldDraft[]>([]);

  const [serviceId, setServiceId] = React.useState<string>("");
  const [serviceQty, setServiceQty] = React.useState<number>(1);
  const [otherService, setOtherService] = React.useState<string>("");
  const [serviceQuery, setServiceQuery] = React.useState<string>("");

  const [customerFirstName, setCustomerFirstName] = React.useState<string>("");
  const [customerLastName, setCustomerLastName] = React.useState<string>("");
  const [customerEmail, setCustomerEmail] = React.useState<string>("");
  const [customerPhone, setCustomerPhone] = React.useState<string>("");
  const [customerCompany, setCustomerCompany] = React.useState<string>("");
  const [customerTaxId, setCustomerTaxId] = React.useState<string>("");
  const [addressLine1, setAddressLine1] = React.useState<string>("");
  const [addressLine2, setAddressLine2] = React.useState<string>("");
  const [addressCity, setAddressCity] = React.useState<string>("");
  const [addressState, setAddressState] = React.useState<string>("");
  const [addressPostalCode, setAddressPostalCode] = React.useState<string>("");
  const [addressCountry, setAddressCountry] = React.useState<string>("");

  const [jobDetails, setJobDetails] = React.useState<string>("");
  const [gdprAccepted, setGdprAccepted] = React.useState<boolean>(false);
  const [dateOfPurchase, setDateOfPurchase] = React.useState<string>("");

  const [attachments, setAttachments] = React.useState<File[]>([]);

  const [catalogLoading, setCatalogLoading] = React.useState<boolean>(false);
  const [catalogError, setCatalogError] = React.useState<string | null>(null);
  const [deviceTypes, setDeviceTypes] = React.useState<ApiDeviceType[]>([]);
  const [brands, setBrands] = React.useState<ApiBrand[]>([]);
  const [devices, setDevices] = React.useState<ApiDevice[]>([]);
  const [serviceGroups, setServiceGroups] = React.useState<ApiServiceGroup[]>([]);

  const [submitBusy, setSubmitBusy] = React.useState(false);
  const [submitError, setSubmitError] = React.useState<string | null>(null);
  const [submitResult, setSubmitResult] = React.useState<SubmitResult | null>(null);

  React.useEffect(() => {
    let alive = true;

    async function loadConfig() {
      try {
        setConfigLoading(true);
        setConfigError(null);

        if (!tenantSlug) {
          setConfig(null);
          return;
        }

        const c = await apiFetch<BookingConfig>(`/api/t/${tenantSlug}/booking/config`, {
          token: null,
          impersonationSessionId: null,
        });
        if (!alive) return;
        setConfig(c);

        const style = c?.booking?.publicBookingUiStyle;
        if (style === "wizard" || style === "images") {
          setUiStyle(style);
        } else {
          setUiStyle("wizard");
        }

        const nextExtraFields: ExtraFieldDraft[] = (Array.isArray(c?.devicesBrands?.additionalDeviceFields) ? c.devicesBrands.additionalDeviceFields : [])
          .map((f) => {
            const key = typeof f?.id === "string" ? f.id : "";
            const label = typeof f?.label === "string" ? f.label : "";
            const display = typeof f?.displayInBookingForm === "boolean" ? f.displayInBookingForm : true;
            if (!key || !label || !display) return null;
            return { key, label, value_text: "" };
          })
          .filter((x): x is ExtraFieldDraft => Boolean(x));
        setExtraFields(nextExtraFields);

        const nextCountry = (c?.general?.defaultCountry ?? "").trim();
        setAddressCountry(nextCountry);

        if (c?.booking?.defaultType) setTypeId(c.booking.defaultType);
        if (c?.booking?.defaultBrand) setBrandId(c.booking.defaultBrand);
        if (c?.booking?.defaultDevice) setDeviceId(c.booking.defaultDevice);
      } catch (e) {
        if (!alive) return;
        setConfigError(errorMessage(e));
        setConfig(null);
      } finally {
        if (!alive) return;
        setConfigLoading(false);
      }
    }

    void loadConfig();

    return () => {
      alive = false;
    };
  }, [tenantSlug]);

  React.useEffect(() => {
    let alive = true;

    async function loadTypes() {
      try {
        setCatalogLoading(true);
        setCatalogError(null);

        if (!tenantSlug) {
          setDeviceTypes([]);
          return;
        }

        const res = await apiFetch<{ device_types: ApiDeviceType[] }>(`/api/t/${tenantSlug}/booking/device-types`, {
          token: null,
          impersonationSessionId: null,
        });
        if (!alive) return;
        setDeviceTypes(Array.isArray(res?.device_types) ? res.device_types : []);
      } catch (e) {
        if (!alive) return;
        setCatalogError(errorMessage(e));
        setDeviceTypes([]);
      } finally {
        if (!alive) return;
        setCatalogLoading(false);
      }
    }

    void loadTypes();

    return () => {
      alive = false;
    };
  }, [tenantSlug]);

  React.useEffect(() => {
    let alive = true;

    async function loadBrands() {
      try {
        setCatalogError(null);

        if (!tenantSlug) {
          setBrands([]);
          return;
        }

        const nextTypeId = safeInt(typeId);
        const res = await apiFetch<{ brands: ApiBrand[] }>(`/api/t/${tenantSlug}/booking/brands${nextTypeId ? `?typeId=${nextTypeId}` : ""}`, {
          token: null,
          impersonationSessionId: null,
        });
        if (!alive) return;
        setBrands(Array.isArray(res?.brands) ? res.brands : []);
      } catch (e) {
        if (!alive) return;
        setCatalogError(errorMessage(e));
        setBrands([]);
      }
    }

    void loadBrands();

    return () => {
      alive = false;
    };
  }, [tenantSlug, typeId]);

  React.useEffect(() => {
    let alive = true;

    async function loadDevices() {
      try {
        setCatalogError(null);

        if (!tenantSlug) {
          setDevices([]);
          return;
        }

        const nextTypeId = safeInt(typeId);
        const nextBrandId = safeInt(brandId);

        const qs = new URLSearchParams();
        if (nextTypeId) qs.set("typeId", String(nextTypeId));
        if (nextBrandId) qs.set("brandId", String(nextBrandId));

        const res = await apiFetch<{ devices: ApiDevice[] }>(`/api/t/${tenantSlug}/booking/devices${qs.toString() ? `?${qs.toString()}` : ""}`,
          {
            token: null,
            impersonationSessionId: null,
          },
        );
        if (!alive) return;
        setDevices(Array.isArray(res?.devices) ? res.devices : []);
      } catch (e) {
        if (!alive) return;
        setCatalogError(errorMessage(e));
        setDevices([]);
      }
    }

    void loadDevices();

    return () => {
      alive = false;
    };
  }, [tenantSlug, typeId, brandId]);

  React.useEffect(() => {
    let alive = true;

    async function loadServices() {
      try {
        setCatalogError(null);
        if (!tenantSlug || !config) {
          setServiceGroups([]);
          return;
        }

        const nextDeviceId = safeInt(deviceId);
        const qs = new URLSearchParams();
        if (nextDeviceId) qs.set("deviceId", String(nextDeviceId));
        qs.set("mode", config.booking.publicBookingMode);

        const res = await apiFetch<{ mode: string; services?: ApiService[]; groups?: ApiServiceGroup[] }>(
          `/api/t/${tenantSlug}/booking/services?${qs.toString()}`,
          {
            token: null,
            impersonationSessionId: null,
          },
        );
        if (!alive) return;

        if (Array.isArray(res?.groups)) {
          setServiceGroups(res.groups.map((g) => ({ service_type: g.service_type ?? null, services: Array.isArray(g.services) ? g.services : [] })));
        } else {
          const list = Array.isArray(res?.services) ? res.services : [];
          const grouped: Record<string, ApiServiceGroup> = {};
          for (const s of list) {
            const k = s.service_type?.id ? String(s.service_type.id) : "none";
            if (!grouped[k]) grouped[k] = { service_type: s.service_type ?? null, services: [] };
            grouped[k].services.push(s);
          }
          setServiceGroups(Object.values(grouped));
        }
      } catch (e) {
        if (!alive) return;
        setCatalogError(errorMessage(e));
        setServiceGroups([]);
      }
    }

    void loadServices();

    return () => {
      alive = false;
    };
  }, [tenantSlug, deviceId, config]);

  const allowOtherDevice = config ? !config.booking.turnOffOtherDeviceBrand : true;
  const allowOtherService = config ? !config.booking.turnOffOtherService : true;

  const gdprText = (config?.general?.gdprAcceptanceText ?? "").trim();
  const needsGdpr = gdprText.length > 0;

  const selectedService = React.useMemo(() => {
    const sid = safeInt(serviceId);
    if (!sid) return null;
    for (const g of serviceGroups) {
      const found = g.services.find((s) => s.id === sid);
      if (found) return found;
    }
    return null;
  }, [serviceGroups, serviceId]);

  const selectedDeviceLabel = React.useMemo(() => {
    const did = safeInt(deviceId);
    if (did) {
      return devices.find((d) => d.id === did)?.model ?? "";
    }
    return otherDeviceLabel.trim();
  }, [devices, deviceId, otherDeviceLabel]);

  const selectedServiceLabel = React.useMemo(() => {
    const sid = safeInt(serviceId);
    if (sid) return selectedService?.name ?? "";
    return otherService.trim();
  }, [otherService, selectedService, serviceId]);

  const nextDisabled = submitBusy
    ? true
    : step === 1
      ? Boolean(canGoStep2())
      : step === 2
        ? Boolean(canGoStep3())
        : step === 3
          ? Boolean(canGoStep4())
          : false;

  const statusCheckLink = tenantSlug && submitResult?.case_number ? `/t/${tenantSlug}/status?caseNumber=${encodeURIComponent(submitResult.case_number)}` : "";

  function canGoStep2(): string | null {
    if (!allowOtherDevice && !safeInt(deviceId)) return "Please select a device.";
    if (allowOtherDevice && !safeInt(deviceId) && otherDeviceLabel.trim().length === 0) return "Please select a device or enter a device label.";
    return null;
  }

  function canGoStep3(): string | null {
    const selected = safeInt(serviceId);
    if (selected && otherService.trim().length > 0) return "Choose a service or enter other service, not both.";
    if (!allowOtherService && !selected) return "Please select a service.";
    if (allowOtherService && !selected && otherService.trim().length === 0) return "Please select a service or enter other service.";
    if (serviceQty < 1) return "Quantity must be at least 1.";
    return null;
  }

  function canGoStep4(): string | null {
    if (config?.booking?.publicBookingMode === "warranty" && dateOfPurchase.trim().length === 0) return "Date of purchase is required.";
    if (customerFirstName.trim().length === 0) return "First name is required.";
    if (customerLastName.trim().length === 0) return "Last name is required.";
    if (customerEmail.trim().length === 0) return "Email is required.";
    if (jobDetails.trim().length === 0) return "Job details are required.";
    if (needsGdpr && !gdprAccepted) return "Please accept GDPR.";
    return null;
  }

  function currentStepValidationError(nextStep: 1 | 2 | 3 | 4): string | null {
    if (nextStep <= step) return null;
    if (step === 1) return canGoStep2();
    if (step === 2) return canGoStep3();
    if (step === 3) return canGoStep4();
    return null;
  }

  async function submitBooking() {
    setSubmitError(null);
    setFlowError(null);

    if (!tenantSlug) {
      setSubmitError("Tenant slug is required in the URL.");
      return;
    }
    if (!config) {
      setSubmitError("Booking configuration is not loaded.");
      return;
    }

    const stepError = canGoStep4();
    if (stepError) {
      setFlowError(stepError);
      return;
    }

    const nextDeviceId = safeInt(deviceId);
    const nextServiceId = safeInt(serviceId);
    const nextQty = Number.isFinite(serviceQty) ? Math.trunc(serviceQty) : 1;

    const payload = {
      mode: config.booking.publicBookingMode,
      gdprAccepted: needsGdpr ? gdprAccepted : undefined,
      jobDetails: jobDetails.trim(),
      warranty: config.booking.publicBookingMode === "warranty" ? { dateOfPurchase: dateOfPurchase.trim() } : undefined,
      customer: {
        firstName: customerFirstName.trim(),
        lastName: customerLastName.trim(),
        userEmail: customerEmail.trim(),
        phone: customerPhone.trim().length > 0 ? customerPhone.trim() : null,
        company: customerCompany.trim().length > 0 ? customerCompany.trim() : null,
        taxId: customerTaxId.trim().length > 0 ? customerTaxId.trim() : null,
        addressLine1: addressLine1.trim().length > 0 ? addressLine1.trim() : null,
        addressLine2: addressLine2.trim().length > 0 ? addressLine2.trim() : null,
        city: addressCity.trim().length > 0 ? addressCity.trim() : null,
        state: addressState.trim().length > 0 ? addressState.trim() : null,
        postalCode: addressPostalCode.trim().length > 0 ? addressPostalCode.trim() : null,
        country: addressCountry.trim().length > 0 ? addressCountry.trim() : null,
      },
      devices: [
        {
          device_id: nextDeviceId,
          device_label: nextDeviceId ? null : otherDeviceLabel.trim().length > 0 ? otherDeviceLabel.trim() : null,
          serial: config.booking.turnOffIdImeiInBooking ? null : serial.trim().length > 0 ? serial.trim() : null,
          pin: config.devicesBrands.enablePinCodeField ? (pin.trim().length > 0 ? pin.trim() : null) : null,
          notes: deviceNotes.trim().length > 0 ? deviceNotes.trim() : null,
          extra_fields: extraFields
            .map((f) => ({ key: f.key, label: f.label, value_text: f.value_text.trim().length > 0 ? f.value_text.trim() : null }))
            .filter((f) => typeof f.key === "string" && f.key.trim().length > 0),
          services: nextServiceId ? [{ service_id: nextServiceId, qty: nextQty }] : [],
          other_service: otherService.trim().length > 0 ? otherService.trim() : null,
        },
      ],
    };

    const form = new FormData();
    form.set("payload_json", JSON.stringify(payload));
    for (const f of attachments.slice(0, 5)) {
      form.append("attachments[]", f);
    }

    setSubmitBusy(true);
    try {
      const res = await apiFetch<SubmitResult>(`/api/t/${tenantSlug}/booking/submit`, {
        method: "POST",
        body: form,
        token: null,
        impersonationSessionId: null,
      });

      setSubmitResult(res);

      try {
        if (typeof window !== "undefined") {
          window.localStorage.setItem(
            portalSessionKey(tenantSlug),
            JSON.stringify({
              job_id: String(res.id),
              case_number: res.case_number,
            }),
          );
        }
      } catch {
        // ignore
      }
    } catch (e) {
      setSubmitError(errorMessage(e));
    } finally {
      setSubmitBusy(false);
    }
  }

  return (
    <PublicPageShell
      badge={
        <span className="inline-flex items-center gap-2">
          <span>RepairBuddy</span>
          {tenantSlug ? <Badge variant="info">{tenantSlug}</Badge> : null}
        </span>
      }
      actions={
        tenantSlug ? (
          <div className="flex items-center gap-3">
            <Link href={`/t/${tenantSlug}/status`} className="text-sm font-semibold text-[var(--rb-blue)] hover:underline">
              Status check
            </Link>
            <Link href={`/t/${tenantSlug}/portal`} className="text-sm font-semibold text-[var(--rb-blue)] hover:underline">
              Portal
            </Link>
          </div>
        ) : null
      }
      centerContent
    >
      <div className="mx-auto w-full max-w-5xl px-4">
        <Card className="shadow-none">
          <CardContent className="pt-6">
            <div className="space-y-5">
              <div>
                <div className="text-xl font-semibold text-[var(--rb-text)]">Book an appointment</div>
                <div className="mt-1 text-sm text-zinc-600">Tell us what you need and we’ll create your booking and send you a case number.</div>
              </div>

              {!tenantSlug ? (
                <Alert variant="warning" title="Missing tenant">
                  Tenant slug is required in the URL.
                </Alert>
              ) : null}

              {config?.disabled ? (
                <Alert variant="warning" title="Booking disabled">
                  This business has disabled public booking.
                </Alert>
              ) : null}

              {configError ? (
                <Alert variant="danger" title="Could not load booking settings">
                  {configError}
                </Alert>
              ) : null}

              {catalogError ? (
                <Alert variant="danger" title="Could not load catalog">
                  {catalogError}
                </Alert>
              ) : null}

              {configLoading || catalogLoading ? <PublicBookingPageSkeleton /> : null}

              {submitError ? (
                <Alert variant="danger" title="Booking failed">
                  {submitError}
                </Alert>
              ) : null}

              {flowError ? (
                <Alert variant="warning" title="Please review this step">
                  {flowError}
                </Alert>
              ) : null}

              {submitResult ? (
                <Alert variant="success" title="Booking submitted">
                  <div className="space-y-2">
                    <div>
                      Case number: <span className="font-semibold">{submitResult.case_number}</span>
                    </div>
                    <div className="flex flex-wrap items-center gap-3">
                      <Link href={statusCheckLink} className="text-sm font-semibold text-[var(--rb-blue)] hover:underline">
                        Status check
                      </Link>
                      <Link href={`/t/${tenantSlug}/portal`} className="text-sm font-semibold text-[var(--rb-blue)] hover:underline">
                        Portal
                      </Link>
                    </div>
                  </div>
                </Alert>
              ) : null}

              {!configLoading && !catalogLoading && !configError && !catalogError && config && !config.disabled && !submitResult ? (
                <WizardShell
                  steps={[
                    {
                      id: 1,
                      navTitle: "Device",
                      navDescription: "Choose the device you want to book for.",
                      pageTitle: "Choose your device",
                      pageDescription: "Select a type, brand and model (or enter it manually).",
                      footerTitle: "Device",
                    },
                    {
                      id: 2,
                      navTitle: "Service",
                      navDescription: "Pick what you need.",
                      pageTitle: "Select a service",
                      pageDescription: "Choose one service for this device.",
                      footerTitle: "Service",
                    },
                    {
                      id: 3,
                      navTitle: "Details",
                      navDescription: "Your contact details and notes.",
                      pageTitle: "Your details",
                      pageDescription: "Add your info so we can confirm and follow up.",
                      footerTitle: "Details",
                    },
                    {
                      id: 4,
                      navTitle: "Review",
                      navDescription: "Confirm and submit.",
                      pageTitle: "Review & submit",
                      pageDescription: "Double-check everything before submitting.",
                      footerTitle: "Review",
                    },
                  ]}
                  step={step}
                  disabled={submitBusy}
                  nextDisabled={nextDisabled}
                  sidebarTitle="Booking steps"
                  sidebarDescription="Complete the steps to submit your booking."
                  sidebarAriaLabel="Booking steps"
                  sidebarFooter={
                    <div className="space-y-3">
                      <div className="text-xs font-semibold uppercase tracking-wide text-zinc-500">Your booking</div>

                      <div className="space-y-2">
                        <div className="flex items-start justify-between gap-3">
                          <div className="min-w-0">
                            <div className="text-xs text-zinc-500">Device</div>
                            <div className="truncate text-sm font-semibold text-[var(--rb-text)]">{selectedDeviceLabel || "—"}</div>
                          </div>
                          <Button variant="ghost" size="sm" type="button" disabled={submitBusy} onClick={() => setStep(1)}>
                            Edit
                          </Button>
                        </div>

                        <div className="flex items-start justify-between gap-3">
                          <div className="min-w-0">
                            <div className="text-xs text-zinc-500">Service</div>
                            <div className="truncate text-sm font-semibold text-[var(--rb-text)]">{selectedServiceLabel || "—"}</div>
                            <div className="mt-0.5 text-xs text-zinc-500">Qty: {Math.max(1, Number.isFinite(serviceQty) ? Math.trunc(serviceQty) : 1)}</div>
                            {!config.booking.turnOffServicePrice && selectedService?.price ? (
                              <div className="mt-0.5 text-xs text-zinc-500">
                                Starting: {formatMoney({ amountCents: selectedService.price.amount_cents * Math.max(1, Math.trunc(serviceQty || 1)), currency: selectedService.price.currency })}
                              </div>
                            ) : null}
                          </div>
                          <Button variant="ghost" size="sm" type="button" disabled={submitBusy} onClick={() => setStep(2)}>
                            Edit
                          </Button>
                        </div>

                        <div className="flex items-start justify-between gap-3">
                          <div className="min-w-0">
                            <div className="text-xs text-zinc-500">Contact</div>
                            <div className="truncate text-sm font-semibold text-[var(--rb-text)]">
                              {(customerFirstName.trim() || customerLastName.trim())
                                ? `${customerFirstName.trim()} ${customerLastName.trim()}`.trim()
                                : "—"}
                            </div>
                            <div className="truncate text-xs text-zinc-500">{customerEmail.trim() || ""}</div>
                          </div>
                          <Button variant="ghost" size="sm" type="button" disabled={submitBusy} onClick={() => setStep(3)}>
                            Edit
                          </Button>
                        </div>
                      </div>
                    </div>
                  }
                  onStepChange={(next) => {
                    setSubmitError(null);
                    if (next > step) {
                      const err = currentStepValidationError(next as 1 | 2 | 3 | 4);
                      if (err) {
                        setFlowError(err);
                        return;
                      }
                    }
                    setFlowError(null);
                    setStep(next as 1 | 2 | 3 | 4);
                  }}
                  footerRight={
                    <Button disabled={submitBusy} onClick={() => void submitBooking()} type="button">
                      {submitBusy ? "Submitting..." : "Submit booking"}
                    </Button>
                  }
                >
                  {step === 1 ? (
                    <div className="space-y-4">
                      {uiStyle === "wizard" ? (
                        <div className="grid gap-4 sm:grid-cols-2">
                          <div>
                            <div className="text-sm font-semibold text-[var(--rb-text)]">Device type</div>
                            <div className="mt-2">
                              <Select
                                value={typeId}
                                onChange={(e) => {
                                  setFlowError(null);
                                  setTypeId(e.target.value);
                                  setBrandId("");
                                  setDeviceId("");
                                }}
                              >
                                <option value="">Select type</option>
                                {deviceTypes.map((t) => (
                                  <option key={t.id} value={String(t.id)}>
                                    {t.name}
                                  </option>
                                ))}
                              </Select>
                            </div>
                          </div>

                          <div>
                            <div className="text-sm font-semibold text-[var(--rb-text)]">Brand</div>
                            <div className="mt-2">
                              <Select
                                value={brandId}
                                disabled={!typeId}
                                onChange={(e) => {
                                  setFlowError(null);
                                  setBrandId(e.target.value);
                                  setDeviceId("");
                                }}
                              >
                                <option value="">Select brand</option>
                                {brands.map((b) => (
                                  <option key={b.id} value={String(b.id)}>
                                    {b.name}
                                  </option>
                                ))}
                              </Select>
                            </div>
                          </div>

                          <div className="sm:col-span-2">
                            <div className="text-sm font-semibold text-[var(--rb-text)]">Device</div>
                            <div className="mt-2">
                              <Select
                                value={deviceId}
                                disabled={!typeId || !brandId}
                                onChange={(e) => {
                                  setFlowError(null);
                                  setDeviceId(e.target.value);
                                }}
                              >
                                <option value="">Select device</option>
                                {devices.map((d) => (
                                  <option key={d.id} value={String(d.id)}>
                                    {d.model}
                                  </option>
                                ))}
                              </Select>
                            </div>
                            {allowOtherDevice ? <div className="mt-2 text-xs text-zinc-500">If your device is not listed, enter it below.</div> : null}
                          </div>

                          {allowOtherDevice ? (
                            <div className="sm:col-span-2">
                              <div className="text-sm font-semibold text-[var(--rb-text)]">Other device</div>
                              <div className="mt-2">
                                <Input
                                  value={otherDeviceLabel}
                                  onChange={(e) => {
                                    setFlowError(null);
                                    setOtherDeviceLabel(e.target.value);
                                  }}
                                  placeholder="e.g. Custom PC, Unknown model"
                                />
                              </div>
                            </div>
                          ) : null}

                          {!config.booking.turnOffIdImeiInBooking ? (
                            <div>
                              <div className="text-sm font-semibold text-[var(--rb-text)]">Serial / IMEI (optional)</div>
                              <div className="mt-2">
                                <Input value={serial} onChange={(e) => setSerial(e.target.value)} placeholder="Serial, IMEI" />
                              </div>
                            </div>
                          ) : null}

                          {config.devicesBrands.enablePinCodeField ? (
                            <div>
                              <div className="text-sm font-semibold text-[var(--rb-text)]">PIN (optional)</div>
                              <div className="mt-2">
                                <Input value={pin} onChange={(e) => setPin(e.target.value)} placeholder="Device PIN" />
                              </div>
                            </div>
                          ) : null}

                          <div className="sm:col-span-2">
                            <div className="text-sm font-semibold text-[var(--rb-text)]">Device notes (optional)</div>
                            <div className="mt-2">
                              <textarea
                                value={deviceNotes}
                                onChange={(e) => setDeviceNotes(e.target.value)}
                                placeholder="Condition, accessories included, passcode notes, etc."
                                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm text-[var(--rb-text)] shadow-sm outline-none transition placeholder:text-zinc-400 focus-visible:ring-2 focus-visible:ring-[color:color-mix(in_srgb,var(--rb-orange),white_65%)] focus-visible:ring-offset-2 focus-visible:ring-offset-white"
                                rows={4}
                              />
                            </div>
                          </div>

                          {extraFields.length > 0 ? (
                            <div className="sm:col-span-2">
                              <div className="text-sm font-semibold text-[var(--rb-text)]">Additional device info (optional)</div>
                              <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                {extraFields.map((f, idx) => (
                                  <label key={f.key} className="grid gap-1 text-sm">
                                    <span className="font-semibold text-[var(--rb-text)]">{f.label}</span>
                                    <Input
                                      value={f.value_text}
                                      onChange={(e) => {
                                        setExtraFields((prev) => {
                                          const next = prev.slice();
                                          next[idx] = { ...next[idx], value_text: e.target.value };
                                          return next;
                                        });
                                      }}
                                    />
                                  </label>
                                ))}
                              </div>
                            </div>
                          ) : null}
                        </div>
                      ) : (
                        <div className="space-y-5">
                          <div>
                            <div className="text-sm font-semibold text-[var(--rb-text)]">Device type</div>
                            <div className="mt-2 grid gap-3 sm:grid-cols-3">
                              {deviceTypes.map((t) => {
                                const isSelected = typeId === String(t.id);
                                const src = typeof t.image_url === "string" ? t.image_url : null;

                                return (
                                  <button
                                    key={t.id}
                                    type="button"
                                    onClick={() => {
                                      setFlowError(null);
                                      setTypeId(String(t.id));
                                      setBrandId("");
                                      setDeviceId("");
                                    }}
                                    className={cn(
                                      "group flex items-center gap-3 rounded-[var(--rb-radius-md)] border bg-white p-3 text-left transition",
                                      isSelected
                                        ? "border-[color:color-mix(in_srgb,var(--rb-blue),white_60%)] bg-[color:color-mix(in_srgb,var(--rb-blue),white_94%)]"
                                        : "border-[var(--rb-border)] hover:bg-[var(--rb-surface-muted)]",
                                    )}
                                  >
                                    <span className="relative h-10 w-10 shrink-0 overflow-hidden rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-[var(--rb-surface-muted)]">
                                      {src ? (
                                        <Image alt={t.name} src={src} width={40} height={40} className="h-full w-full object-cover" loader={({ src }) => src} unoptimized />
                                      ) : (
                                        <span className="flex h-full w-full items-center justify-center text-xs font-semibold text-zinc-500">{t.name.slice(0, 2).toUpperCase()}</span>
                                      )}
                                    </span>
                                    <span className="min-w-0">
                                      <span className="block truncate text-sm font-semibold text-[var(--rb-text)]">{t.name}</span>
                                      {t.description ? <span className="mt-0.5 block line-clamp-2 text-xs text-zinc-600">{t.description}</span> : null}
                                    </span>
                                  </button>
                                );
                              })}
                            </div>
                          </div>

                          <div>
                            <div className="text-sm font-semibold text-[var(--rb-text)]">Brand</div>
                            {!typeId ? <div className="mt-1 text-xs text-zinc-500">Select a device type to see brands.</div> : null}
                            <div className="mt-2 grid gap-3 sm:grid-cols-4">
                              {brands.map((b) => {
                                const isSelected = brandId === String(b.id);
                                const src = typeof b.image_url === "string" ? b.image_url : null;

                                return (
                                  <button
                                    key={b.id}
                                    type="button"
                                    disabled={!typeId}
                                    onClick={() => {
                                      setFlowError(null);
                                      setBrandId(String(b.id));
                                      setDeviceId("");
                                    }}
                                    className={cn(
                                      "group flex items-center gap-3 rounded-[var(--rb-radius-md)] border bg-white p-3 text-left transition",
                                      !typeId ? "pointer-events-none opacity-60" : "",
                                      isSelected
                                        ? "border-[color:color-mix(in_srgb,var(--rb-blue),white_60%)] bg-[color:color-mix(in_srgb,var(--rb-blue),white_94%)]"
                                        : "border-[var(--rb-border)] hover:bg-[var(--rb-surface-muted)]",
                                    )}
                                  >
                                    <span className="relative h-9 w-9 shrink-0 overflow-hidden rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-[var(--rb-surface-muted)]">
                                      {src ? (
                                        <Image alt={b.name} src={src} width={36} height={36} className="h-full w-full object-cover" loader={({ src }) => src} unoptimized />
                                      ) : (
                                        <span className="flex h-full w-full items-center justify-center text-xs font-semibold text-zinc-500">{b.name.slice(0, 2).toUpperCase()}</span>
                                      )}
                                    </span>
                                    <span className="min-w-0">
                                      <span className="block truncate text-sm font-semibold text-[var(--rb-text)]">{b.name}</span>
                                    </span>
                                  </button>
                                );
                              })}
                            </div>
                          </div>

                          <div>
                            <div className="text-sm font-semibold text-[var(--rb-text)]">Device</div>
                            {!typeId || !brandId ? <div className="mt-1 text-xs text-zinc-500">Select a type and brand to see devices.</div> : null}
                            <div className="mt-2 grid gap-3 sm:grid-cols-3">
                              {devices.map((d) => {
                                const isSelected = deviceId === String(d.id);

                                return (
                                  <button
                                    key={d.id}
                                    type="button"
                                    disabled={!typeId || !brandId}
                                    onClick={() => {
                                      setFlowError(null);
                                      setDeviceId(String(d.id));
                                    }}
                                    className={cn(
                                      "rounded-[var(--rb-radius-md)] border bg-white p-3 text-left transition",
                                      !typeId || !brandId ? "pointer-events-none opacity-60" : "hover:bg-[var(--rb-surface-muted)]",
                                      isSelected
                                        ? "border-[color:color-mix(in_srgb,var(--rb-blue),white_60%)] bg-[color:color-mix(in_srgb,var(--rb-blue),white_94%)]"
                                        : "border-[var(--rb-border)]",
                                    )}
                                  >
                                    <div className="text-sm font-semibold text-[var(--rb-text)]">{d.model}</div>
                                  </button>
                                );
                              })}
                            </div>
                            {allowOtherDevice ? <div className="mt-2 text-xs text-zinc-500">If your device is not listed, enter it below.</div> : null}
                          </div>

                          {allowOtherDevice ? (
                            <div>
                              <div className="text-sm font-semibold text-[var(--rb-text)]">Other device</div>
                              <div className="mt-2">
                                <Input
                                  value={otherDeviceLabel}
                                  onChange={(e) => {
                                    setFlowError(null);
                                    setOtherDeviceLabel(e.target.value);
                                  }}
                                  placeholder="e.g. Custom PC, Unknown model"
                                />
                              </div>
                            </div>
                          ) : null}

                          <div className="grid gap-4 sm:grid-cols-2">
                            {!config.booking.turnOffIdImeiInBooking ? (
                              <div>
                                <div className="text-sm font-semibold text-[var(--rb-text)]">Serial / IMEI (optional)</div>
                                <div className="mt-2">
                                  <Input value={serial} onChange={(e) => setSerial(e.target.value)} placeholder="Serial, IMEI" />
                                </div>
                              </div>
                            ) : null}

                            {config.devicesBrands.enablePinCodeField ? (
                              <div>
                                <div className="text-sm font-semibold text-[var(--rb-text)]">PIN (optional)</div>
                                <div className="mt-2">
                                  <Input value={pin} onChange={(e) => setPin(e.target.value)} placeholder="Device PIN" />
                                </div>
                              </div>
                            ) : null}
                          </div>

                          <div>
                            <div className="text-sm font-semibold text-[var(--rb-text)]">Device notes (optional)</div>
                            <div className="mt-2">
                              <textarea
                                value={deviceNotes}
                                onChange={(e) => setDeviceNotes(e.target.value)}
                                placeholder="Condition, accessories included, passcode notes, etc."
                                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm text-[var(--rb-text)] shadow-sm outline-none transition placeholder:text-zinc-400 focus-visible:ring-2 focus-visible:ring-[color:color-mix(in_srgb,var(--rb-orange),white_65%)] focus-visible:ring-offset-2 focus-visible:ring-offset-white"
                                rows={4}
                              />
                            </div>
                          </div>

                          {extraFields.length > 0 ? (
                            <div>
                              <div className="text-sm font-semibold text-[var(--rb-text)]">Additional device info (optional)</div>
                              <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                {extraFields.map((f, idx) => (
                                  <label key={f.key} className="grid gap-1 text-sm">
                                    <span className="font-semibold text-[var(--rb-text)]">{f.label}</span>
                                    <Input
                                      value={f.value_text}
                                      onChange={(e) => {
                                        setExtraFields((prev) => {
                                          const next = prev.slice();
                                          next[idx] = { ...next[idx], value_text: e.target.value };
                                          return next;
                                        });
                                      }}
                                    />
                                  </label>
                                ))}
                              </div>
                            </div>
                          ) : null}
                        </div>
                      )}
                    </div>
                  ) : null}

                  {step === 2 ? (
                    <div className="space-y-4">
                      <div className="grid gap-4">
                        <div>
                          <div className="text-sm font-semibold text-[var(--rb-text)]">Search</div>
                          <div className="mt-2">
                            <Input value={serviceQuery} onChange={(e) => setServiceQuery(e.target.value)} placeholder="Search services" />
                          </div>
                        </div>

                        <div className="space-y-3">
                          {serviceGroups.map((g) => (
                            <div key={g.service_type?.id ?? "none"} className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-3">
                              <div className="text-sm font-semibold text-[var(--rb-text)]">{g.service_type?.name ?? "Services"}</div>
                              <div className={cn("mt-2", uiStyle === "images" ? "grid gap-2 sm:grid-cols-2" : "grid gap-2")}>
                                {g.services
                                  .filter((s) => s.is_active)
                                  .filter((s) => {
                                    const q = serviceQuery.trim().toLowerCase();
                                    if (!q) return true;
                                    return (s.name ?? "").toLowerCase().includes(q) || (s.description ?? "").toLowerCase().includes(q);
                                  })
                                  .map((s) => {
                                    const isSelected = serviceId === String(s.id);
                                    return (
                                      <button
                                        key={s.id}
                                        type="button"
                                        onClick={() => {
                                          setFlowError(null);
                                          setServiceId(String(s.id));
                                          setOtherService("");
                                        }}
                                        className={cn(
                                          "w-full rounded-[var(--rb-radius-sm)] border p-3 text-left transition",
                                          isSelected
                                            ? "border-[color:color-mix(in_srgb,var(--rb-blue),white_65%)] bg-[color:color-mix(in_srgb,var(--rb-blue),white_92%)]"
                                            : "border-zinc-200 bg-white hover:bg-[var(--rb-surface-muted)]",
                                        )}
                                      >
                                        <div className="flex items-start justify-between gap-3">
                                          <div className="min-w-0 flex-1">
                                            <div className="text-sm font-semibold text-[var(--rb-text)]">{s.name}</div>
                                            {s.description ? <div className="mt-1 text-sm text-zinc-600">{s.description}</div> : null}
                                          </div>
                                          {!config.booking.turnOffServicePrice ? (
                                            <div className="shrink-0 text-sm font-semibold text-[var(--rb-text)]">
                                              {formatMoney({ amountCents: s.price?.amount_cents, currency: s.price?.currency })}
                                            </div>
                                          ) : null}
                                        </div>
                                      </button>
                                    );
                                  })}
                              </div>
                            </div>
                          ))}

                          {!allowOtherService ? null : (
                            <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-3">
                              <div className="text-sm font-semibold text-[var(--rb-text)]">Other service</div>
                              <div className="mt-2">
                                <Input
                                  value={otherService}
                                  onChange={(e) => {
                                    setFlowError(null);
                                    setOtherService(e.target.value);
                                    if (e.target.value.trim().length > 0) setServiceId("");
                                  }}
                                  placeholder="Describe the service you need"
                                />
                              </div>
                            </div>
                          )}

                          <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                              <div className="text-sm font-semibold text-[var(--rb-text)]">Quantity</div>
                              <div className="mt-2">
                                <Input
                                  type="number"
                                  min={1}
                                  max={9999}
                                  value={String(serviceQty)}
                                  onChange={(e) => setServiceQty(Math.max(1, Math.min(9999, Number(e.target.value) || 1)))}
                                />
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  ) : null}

                  {step === 3 ? (
                    <div className="space-y-4">
                      {config.booking.publicBookingMode === "warranty" ? (
                        <div>
                          <div className="text-sm font-semibold text-[var(--rb-text)]">Warranty: date of purchase</div>
                          <div className="mt-2">
                            <Input type="date" value={dateOfPurchase} onChange={(e) => setDateOfPurchase(e.target.value)} />
                          </div>
                        </div>
                      ) : null}

                      <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                          <div className="text-sm font-semibold text-[var(--rb-text)]">First name</div>
                          <div className="mt-2">
                            <Input value={customerFirstName} onChange={(e) => setCustomerFirstName(e.target.value)} autoComplete="given-name" />
                          </div>
                        </div>
                        <div>
                          <div className="text-sm font-semibold text-[var(--rb-text)]">Last name</div>
                          <div className="mt-2">
                            <Input value={customerLastName} onChange={(e) => setCustomerLastName(e.target.value)} autoComplete="family-name" />
                          </div>
                        </div>
                        <div>
                          <div className="text-sm font-semibold text-[var(--rb-text)]">Email</div>
                          <div className="mt-2">
                            <Input type="email" value={customerEmail} onChange={(e) => setCustomerEmail(e.target.value)} placeholder="you@example.com" autoComplete="email" />
                          </div>
                        </div>
                        <div>
                          <div className="text-sm font-semibold text-[var(--rb-text)]">Phone (optional)</div>
                          <div className="mt-2">
                            <Input type="tel" value={customerPhone} onChange={(e) => setCustomerPhone(e.target.value)} autoComplete="tel" />
                          </div>
                        </div>
                        <div>
                          <div className="text-sm font-semibold text-[var(--rb-text)]">Company (optional)</div>
                          <div className="mt-2">
                            <Input value={customerCompany} onChange={(e) => setCustomerCompany(e.target.value)} autoComplete="organization" />
                          </div>
                        </div>
                        <div>
                          <div className="text-sm font-semibold text-[var(--rb-text)]">Tax ID (optional)</div>
                          <div className="mt-2">
                            <Input value={customerTaxId} onChange={(e) => setCustomerTaxId(e.target.value)} />
                          </div>
                        </div>
                        <div className="sm:col-span-2">
                          <div className="text-sm font-semibold text-[var(--rb-text)]">Address line 1 (optional)</div>
                          <div className="mt-2">
                            <Input value={addressLine1} onChange={(e) => setAddressLine1(e.target.value)} autoComplete="address-line1" />
                          </div>
                        </div>
                        <div className="sm:col-span-2">
                          <div className="text-sm font-semibold text-[var(--rb-text)]">Address line 2 (optional)</div>
                          <div className="mt-2">
                            <Input value={addressLine2} onChange={(e) => setAddressLine2(e.target.value)} autoComplete="address-line2" />
                          </div>
                        </div>
                        <div>
                          <div className="text-sm font-semibold text-[var(--rb-text)]">City (optional)</div>
                          <div className="mt-2">
                            <Input value={addressCity} onChange={(e) => setAddressCity(e.target.value)} autoComplete="address-level2" />
                          </div>
                        </div>
                        <div>
                          <div className="text-sm font-semibold text-[var(--rb-text)]">State (optional)</div>
                          <div className="mt-2">
                            <Input value={addressState} onChange={(e) => setAddressState(e.target.value)} autoComplete="address-level1" />
                          </div>
                        </div>
                        <div>
                          <div className="text-sm font-semibold text-[var(--rb-text)]">Postal code (optional)</div>
                          <div className="mt-2">
                            <Input value={addressPostalCode} onChange={(e) => setAddressPostalCode(e.target.value)} autoComplete="postal-code" />
                          </div>
                        </div>
                        <div>
                          <div className="text-sm font-semibold text-[var(--rb-text)]">Country (optional)</div>
                          <div className="mt-2">
                            <Input value={addressCountry} onChange={(e) => setAddressCountry(e.target.value)} placeholder="US" autoComplete="country" />
                          </div>
                        </div>
                        <div className="sm:col-span-2">
                          <div className="text-sm font-semibold text-[var(--rb-text)]">Job details</div>
                          <div className="mt-2">
                            <textarea
                              value={jobDetails}
                              onChange={(e) => setJobDetails(e.target.value)}
                              placeholder="Describe the issue, symptoms, error messages, and any constraints."
                              className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm text-[var(--rb-text)] shadow-sm outline-none transition placeholder:text-zinc-400 focus-visible:ring-2 focus-visible:ring-[color:color-mix(in_srgb,var(--rb-orange),white_65%)] focus-visible:ring-offset-2 focus-visible:ring-offset-white"
                              rows={6}
                            />
                          </div>
                        </div>
                        <div className="sm:col-span-2">
                          <div className="text-sm font-semibold text-[var(--rb-text)]">Attachments (optional)</div>
                          <div className="mt-2 space-y-2">
                            <input
                              type="file"
                              multiple
                              onChange={(e) => {
                                const files = Array.from(e.target.files ?? []);
                                setAttachments(files.slice(0, 5));
                              }}
                              className="block w-full text-sm"
                            />
                            <div className="text-xs text-zinc-500">Up to 5 files.</div>
                            {attachments.length > 0 ? (
                              <div className="grid gap-2">
                                {attachments.map((f, idx) => (
                                  <div key={idx} className="flex items-center justify-between gap-3 rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white px-3 py-2">
                                    <div className="min-w-0">
                                      <div className="truncate text-sm font-medium text-[var(--rb-text)]">{f.name}</div>
                                      <div className="text-xs text-zinc-500">{Math.max(1, Math.round(f.size / 1024))} KB</div>
                                    </div>
                                    <Button
                                      variant="ghost"
                                      size="sm"
                                      type="button"
                                      onClick={() => {
                                        setAttachments((prev) => prev.filter((_, i) => i !== idx));
                                      }}
                                    >
                                      Remove
                                    </Button>
                                  </div>
                                ))}
                              </div>
                            ) : null}
                          </div>
                        </div>
                      </div>

                      {needsGdpr ? (
                        <label className="flex items-start gap-3 rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-3 text-sm">
                          <input type="checkbox" checked={gdprAccepted} onChange={(e) => setGdprAccepted(e.target.checked)} />
                          <div className="space-y-1">
                            <div className="font-semibold text-[var(--rb-text)]">GDPR</div>
                            <div className="text-zinc-600">
                              {gdprText}
                              {config.general.gdprLinkUrl && config.general.gdprLinkLabel ? (
                                <span>
                                  {" "}
                                  <a href={config.general.gdprLinkUrl} target="_blank" rel="noreferrer" className="font-semibold text-[var(--rb-blue)] hover:underline">
                                    {config.general.gdprLinkLabel}
                                  </a>
                                </span>
                              ) : null}
                            </div>
                          </div>
                        </label>
                      ) : null}
                    </div>
                  ) : null}

                  {step === 4 ? (
                    <div className="space-y-4">
                      <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-4">
                        <div className="text-sm font-semibold text-[var(--rb-text)]">Review</div>
                        <div className="mt-3 grid gap-2 text-sm text-zinc-700">
                          <div>
                            <span className="font-semibold">Device:</span> {safeInt(deviceId) ? (devices.find((d) => String(d.id) === deviceId)?.model ?? "") : otherDeviceLabel.trim()}
                          </div>
                          <div>
                            <span className="font-semibold">Service:</span> {selectedService ? selectedService.name : otherService.trim()}
                          </div>
                          <div>
                            <span className="font-semibold">Qty:</span> {serviceQty}
                          </div>
                          {!config.booking.turnOffServicePrice && selectedService?.price ? (
                            <div>
                              <span className="font-semibold">Starting price:</span> {formatMoney({ amountCents: selectedService.price.amount_cents, currency: selectedService.price.currency })}
                            </div>
                          ) : null}
                          <div>
                            <span className="font-semibold">Name:</span> {customerFirstName.trim()} {customerLastName.trim()}
                          </div>
                          <div>
                            <span className="font-semibold">Email:</span> {customerEmail.trim()}
                          </div>
                          {customerPhone.trim().length > 0 ? (
                            <div>
                              <span className="font-semibold">Phone:</span> {customerPhone.trim()}
                            </div>
                          ) : null}
                          {config.booking.publicBookingMode === "warranty" ? (
                            <div>
                              <span className="font-semibold">Date of purchase:</span> {dateOfPurchase.trim()}
                            </div>
                          ) : null}
                        </div>
                      </div>

                      <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-4">
                        <div className="text-sm font-semibold text-[var(--rb-text)]">Notes</div>
                        <div className="mt-2 whitespace-pre-wrap text-sm text-zinc-700">{jobDetails.trim()}</div>
                      </div>
                    </div>
                  ) : null}
                </WizardShell>
              ) : null}
            </div>
          </CardContent>
        </Card>
      </div>
    </PublicPageShell>
  );
}
