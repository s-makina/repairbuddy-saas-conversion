"use client";

import React, { useEffect, useMemo, useState } from "react";
import { Select } from "@/components/ui/Select";
import { SectionShell } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/SectionShell";
import type { RepairBuddySettingsDraft } from "@/app/app/[tenant]/settings/_components/repairbuddy/types";
import { getRepairBuddyTaxes, type ApiRepairBuddyTax } from "@/lib/repairbuddy-taxes";

export function TimeLogsSection({
  tenantSlug,
  draft,
  updateTimeLogs,
  isMock,
}: {
  tenantSlug: string;
  draft: RepairBuddySettingsDraft;
  updateTimeLogs: (patch: Partial<RepairBuddySettingsDraft["timeLogs"]>) => void;
  isMock: boolean;
}) {
  const t = draft.timeLogs;
  const statusOptions = useMemo(() => draft.jobStatuses.statuses, [draft.jobStatuses.statuses]);

  const [taxes, setTaxes] = useState<ApiRepairBuddyTax[]>([]);
  const [loadingTaxes, setLoadingTaxes] = useState(false);

  useEffect(() => {
    let alive = true;

    async function load() {
      if (isMock) return;
      setLoadingTaxes(true);
      try {
        const res = await getRepairBuddyTaxes(String(tenantSlug));
        if (!alive) return;
        setTaxes(Array.isArray(res.taxes) ? res.taxes : []);
      } catch {
        if (!alive) return;
        setTaxes([]);
      } finally {
        if (!alive) return;
        setLoadingTaxes(false);
      }
    }

    void load();

    return () => {
      alive = false;
    };
  }, [isMock, tenantSlug]);

  const activeTaxOptions = useMemo(() => {
    const source = isMock
      ? draft.taxes.taxes.map((x) => ({
          id: Number.isFinite(Number(x.id)) ? Number(x.id) : 0,
          name: x.name,
          rate: x.ratePercent,
          is_default: x.id === draft.taxes.defaultTaxId,
          is_active: x.status === "active",
        }))
      : taxes;

    return source
      .filter((x) => x && x.is_active)
      .map((x) => ({
        id: String(x.id),
        name: x.name,
        ratePercent: Number(x.rate),
        isDefault: Boolean(x.is_default),
      }));
  }, [draft.taxes.defaultTaxId, draft.taxes.taxes, isMock, taxes]);

  const defaultTaxIdFromTenant = useMemo(() => {
    const fromApi = activeTaxOptions.find((x) => x.isDefault)?.id ?? "";
    if (fromApi) return fromApi;
    const fallback = activeTaxOptions[0]?.id ?? "";
    return fallback;
  }, [activeTaxOptions]);

  const defaultTaxSelectValue = useMemo(() => {
    const activeIds = new Set(activeTaxOptions.map((x) => x.id));
    if (t.defaultTaxIdForHours && activeIds.has(String(t.defaultTaxIdForHours))) return String(t.defaultTaxIdForHours);
    if (defaultTaxIdFromTenant && activeIds.has(String(defaultTaxIdFromTenant))) return String(defaultTaxIdFromTenant);
    return "";
  }, [defaultTaxIdFromTenant, activeTaxOptions, t.defaultTaxIdForHours]);

  useEffect(() => {
    if (!defaultTaxSelectValue) return;
    if (t.defaultTaxIdForHours === defaultTaxSelectValue) return;
    updateTimeLogs({ defaultTaxIdForHours: defaultTaxSelectValue });
  }, [defaultTaxSelectValue, t.defaultTaxIdForHours, updateTimeLogs]);

  function toggleStatus(id: string) {
    const set = new Set(t.enableTimeLogForStatusIds);
    if (set.has(id)) set.delete(id);
    else set.add(id);
    updateTimeLogs({ enableTimeLogForStatusIds: Array.from(set) });
  }

  return (
    <SectionShell title="Time Logs" description="Time tracking and hourly tax defaults.">
      <div className="space-y-4">
        <label className="flex items-center gap-2 text-sm">
          <input type="checkbox" checked={t.disableTimeLog} onChange={(e) => updateTimeLogs({ disableTimeLog: e.target.checked })} />
          Disable time log
        </label>

        <div className="grid gap-4 sm:grid-cols-2">
          <div className="space-y-1">
            <label className="text-sm font-medium">Default tax for hours</label>
            <Select
              value={defaultTaxSelectValue}
              disabled={loadingTaxes || activeTaxOptions.length === 0}
              onChange={(e) => updateTimeLogs({ defaultTaxIdForHours: e.target.value })}
            >
              {activeTaxOptions.length === 0 ? (
                <option value="">No active taxes</option>
              ) : (
                activeTaxOptions.map((x) => (
                  <option key={x.id} value={x.id}>
                    {x.name} ({x.ratePercent}%)
                  </option>
                ))
              )}
            </Select>
          </div>
        </div>

        <div>
          <div className="text-sm font-semibold text-[var(--rb-text)]">Enable time log for statuses</div>
          <div className="mt-2 grid gap-2 sm:grid-cols-3">
            {statusOptions.map((s) => (
              <label key={s.id} className="flex items-center gap-2 text-sm">
                <input type="checkbox" checked={t.enableTimeLogForStatusIds.includes(s.id)} onChange={() => toggleStatus(s.id)} />
                {s.name}
              </label>
            ))}
          </div>
        </div>

        <div className="space-y-1">
          <label className="text-sm font-medium">Activities</label>
          <textarea
            className="min-h-[140px] w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
            value={t.activities}
            onChange={(e) => updateTimeLogs({ activities: e.target.value })}
            placeholder="List of activities (one per line)"
          />
        </div>
      </div>
    </SectionShell>
  );
}
