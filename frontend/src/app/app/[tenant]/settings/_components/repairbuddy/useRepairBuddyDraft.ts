"use client";

import React, { useCallback, useEffect, useMemo, useState } from "react";
import { defaultRepairBuddyDraft } from "@/app/app/[tenant]/settings/_components/repairbuddy/defaults";
import type { RepairBuddySettingsDraft } from "@/app/app/[tenant]/settings/_components/repairbuddy/types";
import { ApiError } from "@/lib/api";
import { getRepairBuddySettings, updateRepairBuddySettings } from "@/lib/repairbuddy-settings";
import { getSetup } from "@/lib/setup";
import type { Tenant } from "@/lib/types";

export const savingDisabledReason = "";

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
  const logoUrl = typeof tenant.logo_url === "string" ? tenant.logo_url : "";
  const billingCountry = typeof tenant.billing_country === "string" ? tenant.billing_country.trim().toUpperCase() : "";

  return {
    ...draft,
    general: {
      ...draft.general,
      businessName: tenant.name ?? "",
      businessPhone: tenant.contact_phone ?? "",
      email: tenant.contact_email ?? "",
      businessAddress: formatTenantAddress(tenant),
      logoUrl,
      defaultCountry: billingCountry || draft.general.defaultCountry,
    },
  };
}

export function useRepairBuddyDraft(tenantSlug?: string) {
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
        const [settingsRes, setupRes] = await Promise.all([getRepairBuddySettings(String(tenantSlug)), getSetup(String(tenantSlug))]);
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

        setDraft(applyTenantIdentityToDraft(merged, setupRes.tenant));
      } catch (e) {
        if (!alive) return;
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
  }, [tenantSlug]);

  const reset = useCallback(() => {
    setDraft(defaultRepairBuddyDraft);
  }, []);

  const save = useCallback(async () => {
    if (!tenantSlug) return;
    setSaving(true);
    setError(null);
    try {
      const setupRes = await getSetup(String(tenantSlug));
      const nextDraft = applyTenantIdentityToDraft(draft, setupRes.tenant);
      setTenant(setupRes.tenant);

      const res = await updateRepairBuddySettings(String(tenantSlug), nextDraft);
      if (res.settings) {
        setDraft(applyTenantIdentityToDraft(res.settings, setupRes.tenant));
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
