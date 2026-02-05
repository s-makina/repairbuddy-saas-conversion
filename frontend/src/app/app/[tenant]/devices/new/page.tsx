"use client";

import React, { useEffect, useMemo, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { RequireAuth } from "@/components/RequireAuth";
import { PageHeader } from "@/components/ui/PageHeader";
import { Card, CardContent } from "@/components/ui/Card";
import { Button } from "@/components/ui/Button";
import { Input } from "@/components/ui/Input";
import { FormRow } from "@/components/ui/FormRow";
import { apiFetch, ApiError } from "@/lib/api";
import { notify } from "@/lib/notify";

type ApiDeviceType = {
  id: number;
  name: string;
  is_active: boolean;
};

type ApiDeviceBrand = {
  id: number;
  name: string;
  image_path: string | null;
  is_active: boolean;
};

type ApiDevice = {
  id: number;
  model: string;
  device_type_id: number;
  device_brand_id: number;
  parent_device_id: number | null;
  disable_in_booking_form: boolean;
  is_other: boolean;
  is_active: boolean;
};

export default function NewDevicePage() {
  const router = useRouter();
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loadingLookups, setLoadingLookups] = useState(true);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const [devices, setDevices] = useState<ApiDevice[]>([]);
  const [brands, setBrands] = useState<ApiDeviceBrand[]>([]);
  const [types, setTypes] = useState<ApiDeviceType[]>([]);

  const [model, setModel] = useState("");
  const [typeId, setTypeId] = useState<number | null>(null);
  const [brandId, setBrandId] = useState<number | null>(null);
  const [parentId, setParentId] = useState<number | null>(null);
  const [disableInBooking, setDisableInBooking] = useState(false);
  const [isOther, setIsOther] = useState(false);
  const [isActive, setIsActive] = useState(true);

  const [variationRows, setVariationRows] = useState<string[]>([""]);

  useEffect(() => {
    let alive = true;

    async function loadLookups() {
      if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;

      setLoadingLookups(true);
      setError(null);

      try {
        const [devicesRes, brandsRes, typesRes] = await Promise.all([
          apiFetch<{ devices: ApiDevice[] }>(`/api/${tenantSlug}/app/repairbuddy/devices?limit=200`),
          apiFetch<{ device_brands: ApiDeviceBrand[] }>(`/api/${tenantSlug}/app/repairbuddy/device-brands?limit=200`),
          apiFetch<{ device_types: ApiDeviceType[] }>(`/api/${tenantSlug}/app/repairbuddy/device-types?limit=200`),
        ]);

        if (!alive) return;

        const resolvedTypes = Array.isArray(typesRes.device_types) ? typesRes.device_types : [];
        const resolvedBrands = Array.isArray(brandsRes.device_brands) ? brandsRes.device_brands : [];

        setDevices(Array.isArray(devicesRes.devices) ? devicesRes.devices : []);
        setBrands(resolvedBrands);
        setTypes(resolvedTypes);

        setTypeId(resolvedTypes.length > 0 ? resolvedTypes[0].id : null);
        setBrandId(resolvedBrands.length > 0 ? resolvedBrands[0].id : null);
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load form data.");
        setDevices([]);
        setBrands([]);
        setTypes([]);
      } finally {
        if (!alive) return;
        setLoadingLookups(false);
      }
    }

    void loadLookups();

    return () => {
      alive = false;
    };
  }, [tenantSlug]);

  const parentOptions = useMemo(() => {
    return devices.slice().sort((a, b) => a.model.localeCompare(b.model));
  }, [devices]);

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
    if (busy) return;

    setBusy(true);
    setError(null);

    try {
      const trimmedModel = model.trim();
      if (trimmedModel.length === 0) {
        setError("Model is required.");
        return;
      }

      if (typeof typeId !== "number") {
        setError("Device type is required.");
        return;
      }

      if (typeof brandId !== "number") {
        setError("Device brand is required.");
        return;
      }

      const basePayload = {
        model: trimmedModel,
        device_type_id: typeId,
        device_brand_id: brandId,
        parent_device_id: parentId,
        disable_in_booking_form: disableInBooking,
        is_other: isOther,
        is_active: isActive,
      };

      if (typeof parentId === "number") {
        await apiFetch<{ device: ApiDevice }>(`/api/${tenantSlug}/app/repairbuddy/devices`, {
          method: "POST",
          body: basePayload,
        });

        notify.success("Device created.");
        router.replace(`/app/${tenantSlug}/devices`);
        return;
      }

      const baseRes = await apiFetch<{ device: ApiDevice }>(`/api/${tenantSlug}/app/repairbuddy/devices`, {
        method: "POST",
        body: basePayload,
      });

      const baseId = baseRes.device?.id;

      if (typeof baseId !== "number") {
        notify.success("Device created.");
        router.replace(`/app/${tenantSlug}/devices`);
        return;
      }

      const variations = variationRows
        .map((v) => v.trim())
        .filter((v) => v.length > 0);

      const uniqueVariations = Array.from(new Set(variations));

      if (uniqueVariations.length > 0) {
        for (const variation of uniqueVariations) {
          await apiFetch<{ device: ApiDevice }>(`/api/${tenantSlug}/app/repairbuddy/devices`, {
            method: "POST",
            body: {
              ...basePayload,
              model: `${trimmedModel} - ${variation}`,
              parent_device_id: baseId,
            },
          });
        }

        notify.success("Device and variations created.");
      } else {
        notify.success("Device created.");
      }

      router.replace(`/app/${tenantSlug}/devices`);
    } catch (e) {
      if (e instanceof ApiError) {
        setError(e.message);
      } else {
        setError("Failed to create device.");
      }
    } finally {
      setBusy(false);
    }
  }

  const disabled = busy || loadingLookups;

  return (
    <RequireAuth requiredPermission="devices.manage">
      <div className="space-y-6">
        <PageHeader
          title="New device"
          description="Create a new device model (optionally with variations)."
          actions={
            <>
              <Button
                variant="outline"
                size="sm"
                onClick={() => {
                  if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
                  router.push(`/app/${tenantSlug}/devices`);
                }}
                disabled={busy}
              >
                Cancel
              </Button>
              <Button variant="primary" size="sm" type="submit" form="rb_device_new_form" disabled={disabled}>
                {busy ? "Saving..." : "Save"}
              </Button>
            </>
          }
        />

        {error ? <div className="text-sm text-red-600">{error}</div> : null}

        <Card className="shadow-none">
          <CardContent className="pt-5">
            <form id="rb_device_new_form" className="space-y-4" onSubmit={onSubmit}>
              <FormRow label="Model" fieldId="device_model" required>
                <Input id="device_model" value={model} onChange={(e) => setModel(e.target.value)} disabled={disabled} />
              </FormRow>

              <FormRow label="Device type" fieldId="device_type" required>
                <select
                  id="device_type"
                  className="w-full rounded-[var(--rb-radius-sm)] border border-zinc-300 bg-white px-3 py-2 text-sm text-[var(--rb-text)]"
                  value={typeId ?? ""}
                  onChange={(e) => {
                    const raw = e.target.value;
                    if (!raw) {
                      setTypeId(null);
                      return;
                    }
                    const n = Number(raw);
                    setTypeId(Number.isFinite(n) ? n : null);
                  }}
                  disabled={disabled}
                >
                  {types
                    .slice()
                    .sort((a, b) => a.name.localeCompare(b.name))
                    .map((t) => (
                      <option key={t.id} value={t.id}>
                        {t.name}
                      </option>
                    ))}
                </select>
              </FormRow>

              <FormRow label="Device brand" fieldId="device_brand" required>
                <select
                  id="device_brand"
                  className="w-full rounded-[var(--rb-radius-sm)] border border-zinc-300 bg-white px-3 py-2 text-sm text-[var(--rb-text)]"
                  value={brandId ?? ""}
                  onChange={(e) => {
                    const raw = e.target.value;
                    if (!raw) {
                      setBrandId(null);
                      return;
                    }
                    const n = Number(raw);
                    setBrandId(Number.isFinite(n) ? n : null);
                  }}
                  disabled={disabled}
                >
                  {brands
                    .slice()
                    .sort((a, b) => a.name.localeCompare(b.name))
                    .map((b) => (
                      <option key={b.id} value={b.id}>
                        {b.name}
                      </option>
                    ))}
                </select>
              </FormRow>

              <FormRow label="Variation base" fieldId="device_parent" description="Leave empty to create a base device.">
                <select
                  id="device_parent"
                  className="w-full rounded-[var(--rb-radius-sm)] border border-zinc-300 bg-white px-3 py-2 text-sm text-[var(--rb-text)]"
                  value={parentId ?? ""}
                  onChange={(e) => {
                    const raw = e.target.value;
                    if (!raw) {
                      setParentId(null);
                      return;
                    }
                    const n = Number(raw);
                    setParentId(Number.isFinite(n) ? n : null);
                    setVariationRows([""]);
                  }}
                  disabled={disabled}
                >
                  <option value="">(none)</option>
                  {parentOptions.map((d) => (
                    <option key={d.id} value={d.id}>
                      {d.model}
                    </option>
                  ))}
                </select>
              </FormRow>

              {parentId === null ? (
                <FormRow
                  label="Variations"
                  fieldId="device_variations_0"
                  description='Each entry becomes a child device. Example: "Black", "64GB", "Silver".'
                >
                  <div className="space-y-2">
                    {variationRows.map((value, index) => {
                      const id = `device_variations_${index}`;
                      return (
                        <div key={id} className="flex items-center gap-2">
                          <Input
                            id={id}
                            value={value}
                            onChange={(e) => {
                              const next = e.target.value;
                              setVariationRows((prev) => prev.map((v, i) => (i === index ? next : v)));
                            }}
                            disabled={disabled}
                          />
                          {variationRows.length > 1 ? (
                            <Button
                              type="button"
                              variant="outline"
                              size="sm"
                              onClick={() => {
                                setVariationRows((prev) => prev.filter((_, i) => i !== index));
                              }}
                              disabled={disabled}
                            >
                              Remove
                            </Button>
                          ) : null}
                        </div>
                      );
                    })}

                    <div>
                      <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => {
                          setVariationRows((prev) => [...prev, ""]);
                        }}
                        disabled={disabled}
                      >
                        Add variation
                      </Button>
                    </div>
                  </div>
                </FormRow>
              ) : null}

              <div className="grid gap-1 sm:grid-cols-[220px_1fr] sm:gap-4">
                <div className="sm:pt-2">
                  <span className="block text-sm font-medium text-[var(--rb-text)]">Options</span>
                </div>
                <div className="space-y-2">
                  <label className="flex items-center gap-2 text-sm">
                    <input type="checkbox" checked={disableInBooking} onChange={(e) => setDisableInBooking(e.target.checked)} disabled={disabled} />
                    Disable in booking form
                  </label>

                  <label className="flex items-center gap-2 text-sm">
                    <input type="checkbox" checked={isOther} onChange={(e) => setIsOther(e.target.checked)} disabled={disabled} />
                    Is {"\"Other\""} device
                  </label>

                  <label className="flex items-center gap-2 text-sm">
                    <input type="checkbox" checked={isActive} onChange={(e) => setIsActive(e.target.checked)} disabled={disabled} />
                    Active
                  </label>
                </div>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </RequireAuth>
  );
}
