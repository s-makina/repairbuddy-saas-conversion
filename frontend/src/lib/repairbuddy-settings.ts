import { apiFetch } from "@/lib/api";
import type { RepairBuddySettingsDraft } from "@/app/app/[tenant]/settings/_components/repairbuddy/types";

export type RepairBuddySettingsPayload = {
  settings: RepairBuddySettingsDraft | null;
};

export async function getRepairBuddySettings(business: string): Promise<RepairBuddySettingsPayload> {
  return apiFetch<RepairBuddySettingsPayload>(`/api/${business}/app/repairbuddy/settings`);
}

export async function updateRepairBuddySettings(business: string, settings: RepairBuddySettingsDraft): Promise<RepairBuddySettingsPayload> {
  return apiFetch<RepairBuddySettingsPayload>(`/api/${business}/app/repairbuddy/settings`, {
    method: "PATCH",
    body: {
      settings,
    },
  });
}
