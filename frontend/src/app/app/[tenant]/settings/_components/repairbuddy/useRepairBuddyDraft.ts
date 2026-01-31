"use client";

import React, { useCallback, useEffect, useMemo, useState } from "react";
import { defaultRepairBuddyDraft } from "@/app/app/[tenant]/settings/_components/repairbuddy/defaults";
import type { RepairBuddySettingsDraft } from "@/app/app/[tenant]/settings/_components/repairbuddy/types";
import { ApiError } from "@/lib/api";
import { getRepairBuddySettings, updateRepairBuddySettings } from "@/lib/repairbuddy-settings";

export const savingDisabledReason = "";

export function useRepairBuddyDraft(tenantSlug?: string) {
  const [draft, setDraft] = useState<RepairBuddySettingsDraft>(defaultRepairBuddyDraft);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

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
        const res = await getRepairBuddySettings(String(tenantSlug));
        if (!alive) return;
        if (res.settings && typeof res.settings === "object") {
          setDraft(res.settings);
        } else {
          setDraft(defaultRepairBuddyDraft);
        }
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
      const res = await updateRepairBuddySettings(String(tenantSlug), draft);
      if (res.settings) {
        setDraft(res.settings);
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
      savingDisabledReason,
    }),
    [draft, error, loading, reset, save, saving, updateSection],
  );
}
