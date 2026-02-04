"use client";

import React, { useCallback, useEffect, useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import { defaultRepairBuddyDraft } from "@/app/app/[tenant]/settings/_components/repairbuddy/defaults";
import type { RepairBuddySettingsDraft } from "@/app/app/[tenant]/settings/_components/repairbuddy/types";
import { ApiError, apiFetch } from "@/lib/api";
import { getRepairBuddySettings, updateRepairBuddySettings } from "@/lib/repairbuddy-settings";
import { getSetup, updateSetup } from "@/lib/setup";
import type { Tenant } from "@/lib/types";

export const savingDisabledReason = "";

function normalizeAdditionalDeviceFields(draft: RepairBuddySettingsDraft): RepairBuddySettingsDraft {
  const db = draft.devicesBrands;
  if (!db || !Array.isArray(db.additionalDeviceFields)) return draft;

  const nextFields = db.additionalDeviceFields
    .map((raw) => {
      if (!raw || typeof raw !== "object") return null;
      const r = raw as Record<string, unknown>;
      const id = typeof r.id === "string" && r.id.trim() ? r.id.trim() : `device_field_${Date.now()}_${Math.random().toString(16).slice(2)}`;
      const label = typeof r.label === "string" ? r.label : "";
      if (!label.trim()) return null;

      const type = "text" as const;
      const displayInBookingForm = typeof r.displayInBookingForm === "boolean" ? r.displayInBookingForm : true;
      const displayInInvoice = typeof r.displayInInvoice === "boolean" ? r.displayInInvoice : true;
      const displayForCustomer = typeof r.displayForCustomer === "boolean" ? r.displayForCustomer : true;

      return {
        id,
        label: label.trim(),
        type,
        displayInBookingForm,
        displayInInvoice,
        displayForCustomer,
      };
    })
    .filter((x): x is NonNullable<typeof x> => Boolean(x));

  return {
    ...draft,
    devicesBrands: {
      ...db,
      additionalDeviceFields: nextFields,
    },
  };
}

function formatTenantAddress(tenant: Tenant): string {
  const addr = (tenant.billing_address_json ?? {}) as Record<string, unknown>;
  const parts: string[] = [];
  if (typeof addr.line1 === "string" && addr.line1.trim()) parts.push(addr.line1.trim());
  if (typeof addr.line2 === "string" && addr.line2.trim()) parts.push(addr.line2.trim());
  if (typeof addr.city === "string" && addr.city.trim()) parts.push(addr.city.trim());
  if (typeof addr.state === "string" && addr.state.trim()) parts.push(addr.state.trim());
  if (typeof addr.postal_code === "string" && addr.postal_code.trim()) parts.push(addr.postal_code.trim());
  const country = typeof tenant.billing_country === "string" ? tenant.billing_country.trim().toUpperCase() : "";
  if (country) parts.push(country);
  return parts.join(", ");
}

function applyTenantIdentityToDraft(draft: RepairBuddySettingsDraft, tenant: Tenant): RepairBuddySettingsDraft {
  const setupState = (tenant.setup_state ?? {}) as Record<string, unknown>;
  const logoUrlFromState = (setupState.identity as Record<string, unknown> | undefined)?.logo_url;
  const identityFromState = (setupState.identity ?? {}) as Record<string, unknown>;
  const logoUrl =
    typeof tenant.logo_url === "string"
      ? tenant.logo_url
      : typeof logoUrlFromState === "string"
        ? logoUrlFromState
        : "";
  const billingCountry = typeof tenant.billing_country === "string" ? tenant.billing_country.trim().toUpperCase() : "";

  return {
    ...draft,
    general: {
      ...draft.general,
      businessName: tenant.name ?? "",
      businessPhone: tenant.contact_phone ?? "",
      email: tenant.contact_email ?? "",
      displayName: typeof identityFromState.display_name === "string" ? identityFromState.display_name : draft.general.displayName,
      primaryContactName:
        typeof identityFromState.primary_contact_name === "string" ? identityFromState.primary_contact_name : draft.general.primaryContactName,
      registrationNumber:
        typeof identityFromState.registration_number === "string" ? identityFromState.registration_number : draft.general.registrationNumber,
      businessAddress: formatTenantAddress(tenant),
      logoUrl,
      defaultCountry: billingCountry || draft.general.defaultCountry,
    },
  };
}

export function useRepairBuddyDraft(tenantSlug?: string) {
  const router = useRouter();
  const [draft, setDraft] = useState<RepairBuddySettingsDraft>(defaultRepairBuddyDraft);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [tenant, setTenant] = useState<Tenant | null>(null);

  const updateSection = useCallback(<K extends keyof RepairBuddySettingsDraft>(key: K, patch: Partial<RepairBuddySettingsDraft[K]>) => {
    setDraft((prev) => ({
      ...prev,
      [key]: {
        ...prev[key],
        ...patch,
      },
    }));
  }, []);

  useEffect(() => {
    let alive = true;

    async function load() {
      if (!tenantSlug) return;
      setLoading(true);
      setError(null);
      try {
        const [settingsRes, setupRes, jobStatusesRes] = await Promise.all([
          getRepairBuddySettings(String(tenantSlug)),
          getSetup(String(tenantSlug)),
          apiFetch<{ job_statuses: Array<{ id: number; slug: string; label: string; invoice_label?: string | null; is_active?: boolean }> }>(
            `/api/${String(tenantSlug)}/app/repairbuddy/job-statuses`
          ),
        ]);
        if (!alive) return;

        setTenant(setupRes.tenant);

        const settingsFromApi = settingsRes.settings && typeof settingsRes.settings === "object" ? settingsRes.settings : null;
        const merged: RepairBuddySettingsDraft = {
          ...defaultRepairBuddyDraft,
          ...(settingsFromApi ?? {}),
          general: {
            ...defaultRepairBuddyDraft.general,
            ...((settingsFromApi?.general ?? {}) as RepairBuddySettingsDraft["general"]),
          },
        };

        const apiJobStatuses = Array.isArray(jobStatusesRes?.job_statuses) ? jobStatusesRes.job_statuses : [];
        if (apiJobStatuses.length > 0) {
          merged.jobStatuses = {
            ...(merged.jobStatuses ?? defaultRepairBuddyDraft.jobStatuses),
            statuses: apiJobStatuses.map((s) => {
              const slug = typeof s.slug === "string" ? s.slug : "";
              const label = typeof s.label === "string" ? s.label : slug;
              const invoiceLabel = typeof s.invoice_label === "string" ? s.invoice_label : label;

              return {
                id: `status_${slug}`,
                name: label,
                slug,
                description: "",
                invoiceLabel,
                manageWooStock: false,
                status: s.is_active === false ? "inactive" : "active",
              };
            }),
            completedStatusId: merged.jobStatuses?.completedStatusId ?? defaultRepairBuddyDraft.jobStatuses.completedStatusId,
            cancelledStatusId: merged.jobStatuses?.cancelledStatusId ?? defaultRepairBuddyDraft.jobStatuses.cancelledStatusId,
          };
        }

        const shouldHydrateTimeLogStatuses =
          !merged.timeLogs ||
          !Array.isArray(merged.timeLogs.enableTimeLogForStatusIds) ||
          merged.timeLogs.enableTimeLogForStatusIds.length === 0;

        const cancelledStatusId = merged.jobStatuses?.cancelledStatusId;
        if (shouldHydrateTimeLogStatuses && merged.jobStatuses && Array.isArray(merged.jobStatuses.statuses)) {
          const enabled = merged.jobStatuses.statuses
            .filter((s) => s.status === "active")
            .map((s) => s.id)
            .filter((id) => (typeof cancelledStatusId === "string" && cancelledStatusId ? id !== cancelledStatusId : true));

          merged.timeLogs = {
            ...merged.timeLogs,
            enableTimeLogForStatusIds: enabled,
          };
        }

        setDraft(applyTenantIdentityToDraft(normalizeAdditionalDeviceFields(merged), setupRes.tenant));
      } catch (e) {
        if (!alive) return;

        if (e instanceof ApiError && e.status === 428 && tenantSlug) {
          const next = `/app/${String(tenantSlug)}/business-settings`;
          router.replace(`/app/${String(tenantSlug)}/branches/select?next=${encodeURIComponent(next)}`);
          return;
        }

        setError(e instanceof Error ? e.message : "Failed to load business settings.");
      } finally {
        if (!alive) return;
        setLoading(false);
      }
    }

    void load();

    return () => {
      alive = false;
    };
  }, [router, tenantSlug]);

  const reset = useCallback(() => {
    setDraft(defaultRepairBuddyDraft);
  }, []);

  const save = useCallback(async () => {
    if (!tenantSlug) return;
    setSaving(true);
    setError(null);
    try {
      const setupRes = await getSetup(String(tenantSlug));

      const nextTenantCurrency = draft.currency.currency.trim().toUpperCase();

      const nextCountry = draft.general.defaultCountry.trim().toUpperCase();
      const addressInput = draft.general.businessAddress.trim();
      const trailingCountry = nextCountry ? new RegExp(`,\\s*${nextCountry}$`, "i") : null;
      const addressLine1 = trailingCountry ? addressInput.replace(trailingCountry, "").trim() : addressInput;

      const existingAddr = (setupRes.tenant.billing_address_json ?? {}) as Record<string, unknown>;
      const nextAddr = addressLine1 ? { ...existingAddr, line1: addressLine1 } : null;

      const state = (setupRes.tenant.setup_state ?? {}) as Record<string, unknown>;
      const identity = (state.identity ?? {}) as Record<string, unknown>;
      const nextState: Record<string, unknown> = {
        ...state,
        identity: {
          ...identity,
          display_name: draft.general.displayName || null,
          primary_contact_name: draft.general.primaryContactName || null,
          registration_number: draft.general.registrationNumber || null,
          logo_url: draft.general.logoUrl,
        },
      };

      const setupUpdateRes = await updateSetup(String(tenantSlug), {
        name: draft.general.businessName,
        contact_email: draft.general.email || null,
        contact_phone: draft.general.businessPhone || null,
        billing_country: nextCountry || null,
        billing_address_json: nextAddr,
        currency: nextTenantCurrency.length === 3 ? nextTenantCurrency : null,
        setup_state: nextState,
      });

      setTenant(setupUpdateRes.tenant);

      const res = await updateRepairBuddySettings(String(tenantSlug), draft);
      if (res.settings) {
        setDraft(applyTenantIdentityToDraft(res.settings, setupUpdateRes.tenant));
      }
    } catch (e) {
      if (e instanceof ApiError) {
        setError(e.message);
      } else {
        setError(e instanceof Error ? e.message : "Failed to save business settings.");
      }
      throw e;
    } finally {
      setSaving(false);
    }
  }, [draft, tenantSlug]);

  const isMock = false;

  return useMemo(
    () => ({
      draft,
      setDraft,
      updateSection,
      reset,
      save,
      isMock,
      loading,
      saving,
      error,
      tenant,
      savingDisabledReason,
    }),
    [draft, error, loading, reset, save, saving, tenant, updateSection],
  );
}
