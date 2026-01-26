"use client";

import React, { useCallback, useMemo, useState } from "react";
import { defaultRepairBuddyDraft } from "@/app/app/[tenant]/settings/_components/repairbuddy/defaults";
import type { RepairBuddySettingsDraft } from "@/app/app/[tenant]/settings/_components/repairbuddy/types";

export const savingDisabledReason = "Backend not implemented";

export function useRepairBuddyDraft() {
  const [draft, setDraft] = useState<RepairBuddySettingsDraft>(defaultRepairBuddyDraft);

  const updateSection = useCallback(<K extends keyof RepairBuddySettingsDraft>(key: K, patch: Partial<RepairBuddySettingsDraft[K]>) => {
    setDraft((prev) => ({
      ...prev,
      [key]: {
        ...prev[key],
        ...patch,
      },
    }));
  }, []);

  const reset = useCallback(() => {
    setDraft(defaultRepairBuddyDraft);
  }, []);

  const save = useCallback(async () => {
    throw new Error(savingDisabledReason);
  }, []);

  const isMock = true;

  return useMemo(
    () => ({
      draft,
      setDraft,
      updateSection,
      reset,
      save,
      isMock,
      savingDisabledReason,
    }),
    [draft, reset, save, updateSection],
  );
}
