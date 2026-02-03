"use client";

import React, { useEffect, useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import Select from "react-select";
import AsyncSelect from "react-select/async";
import { Trash2 } from "lucide-react";
import { useAuth } from "@/lib/auth";
import { PageHeader } from "@/components/ui/PageHeader";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/Card";
import { Button } from "@/components/ui/Button";
import { Input } from "@/components/ui/Input";
import { FormRow } from "@/components/ui/FormRow";
import { Modal } from "@/components/ui/Modal";
import { apiFetch, ApiError } from "@/lib/api";
import { notify } from "@/lib/notify";

type ApiDevice = {
  id: number;
  model: string;
};

type ApiClient = {
  id: number;
  name: string;
  email: string | null;
  phone: string | null;
  company: string | null;
};

type CustomerOption = {
  value: number;
  label: string;
};

type DeviceOption = {
  value: number;
  label: string;
};

type NewEstimateDeviceDraft = {
  device_id: number | null;
  option: DeviceOption | null;
  serial: string;
  pin: string;
  notes: string;
};

type ApiTechnician = {
  id: number;
  name: string;
  email: string;
};

type TechnicianOption = {
  value: number;
  label: string;
};

type PartDraft = {
  id: number;
  name: string;
  code: string;
  capacity: string;
};

type PartOption = {
  value: number;
  label: string;
  code: string;
  capacity: string;
};

type EstimatePartLineDraft = {
  id: string;
  part_id: number;
  part: PartDraft;
  device_id: number | null;
  qty: string;
  price: string;
};

type ApiPart = {
  id: number;
  name: string;
  manufacturing_code: string | null;
  capacity: string | null;
};

type ServiceDraft = {
  id: number;
  name: string;
};

type ServiceOption = {
  value: number;
  label: string;
};

type EstimateServiceLineDraft = {
  id: string;
  service_id: number;
  service: ServiceDraft;
  device_id: number | null;
  qty: string;
  price: string;
};

type EstimateOtherItemLineDraft = {
  id: string;
  name: string;
  qty: string;
  price: string;
};

type ApiService = {
  id: number;
  name: string;
  base_price: { currency: string; amount_cents: number } | null;
};

type DeviceContextOption = {
  value: number;
  label: string;
};

type ApiEstimate = {
  id: number;
};

function makeId(prefix: string) {
  if (typeof crypto !== "undefined" && "randomUUID" in crypto) {
    return `${prefix}-${crypto.randomUUID()}`;
  }
  return `${prefix}-${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

export function EstimateWizard({ tenantSlug }: { tenantSlug: string }) {
  const auth = useAuth();
  const router = useRouter();

  const defaultCaseNumber = useMemo(() => {
    const now = new Date();
    const yyyy = String(now.getFullYear());
    const mm = String(now.getMonth() + 1).padStart(2, "0");
    const dd = String(now.getDate()).padStart(2, "0");
    const hh = String(now.getHours()).padStart(2, "0");
    const min = String(now.getMinutes()).padStart(2, "0");
    const ss = String(now.getSeconds()).padStart(2, "0");
    return `RB-${yyyy}${mm}${dd}-${hh}${min}${ss}`;
  }, []);

  const [loadingLookups, setLoadingLookups] = useState(true);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const [step, setStep] = useState<1 | 2 | 3>(1);

  const [title, setTitle] = useState("");
  const [caseNumber, setCaseNumber] = useState<string>(defaultCaseNumber);
  const [pickupDate, setPickupDate] = useState<string>("");
  const [deliveryDate, setDeliveryDate] = useState<string>("");
  const [caseDetail, setCaseDetail] = useState<string>("");

  const [customerId, setCustomerId] = useState<number | null>(null);
  const [customerOption, setCustomerOption] = useState<CustomerOption | null>(null);

  const [customerMode, setCustomerMode] = useState<"existing" | "new">("existing");
  const [customerCreateName, setCustomerCreateName] = useState("");
  const [customerCreateEmail, setCustomerCreateEmail] = useState("");
  const [customerCreatePhone, setCustomerCreatePhone] = useState("");
  const [customerCreateCompany, setCustomerCreateCompany] = useState("");

  const [customerCreateOpen, setCustomerCreateOpen] = useState(false);
  const [customerCreateError, setCustomerCreateError] = useState<string | null>(null);

  const [assignedTechnicianId, setAssignedTechnicianId] = useState<number | null>(null);

  const [estimateDevicesAdmin, setEstimateDevicesAdmin] = useState<NewEstimateDeviceDraft[]>([
    {
      device_id: null,
      option: null,
      serial: "",
      pin: "",
      notes: "",
    },
  ]);

  const [customers, setCustomers] = useState<ApiClient[]>([]);
  const [devicesCatalog, setDevicesCatalog] = useState<ApiDevice[]>([]);
  const [technicians, setTechnicians] = useState<ApiTechnician[]>([]);

  const [partsCatalog, setPartsCatalog] = useState<PartDraft[]>([]);
  const [servicesCatalog, setServicesCatalog] = useState<ServiceDraft[]>([]);

  const [estimateServices, setEstimateServices] = useState<EstimateServiceLineDraft[]>([]);
  const [estimateParts, setEstimateParts] = useState<EstimatePartLineDraft[]>([]);
  const [estimateOtherItems, setEstimateOtherItems] = useState<EstimateOtherItemLineDraft[]>([]);

  const [servicePickerModalOpen, setServicePickerModalOpen] = useState(false);
  const [servicePickerError, setServicePickerError] = useState<string | null>(null);
  const [servicePickerService, setServicePickerService] = useState<ServiceOption | null>(null);
  const [servicePickerDevice, setServicePickerDevice] = useState<DeviceContextOption | null>(null);
  const [servicePickerQty, setServicePickerQty] = useState("1");
  const [servicePickerPrice, setServicePickerPrice] = useState("");

  const [partPickerModalOpen, setPartPickerModalOpen] = useState(false);
  const [partPickerError, setPartPickerError] = useState<string | null>(null);
  const [partPickerPart, setPartPickerPart] = useState<PartOption | null>(null);
  const [partPickerDevice, setPartPickerDevice] = useState<DeviceContextOption | null>(null);
  const [partPickerQty, setPartPickerQty] = useState("1");
  const [partPickerPrice, setPartPickerPrice] = useState("");

  const isStep1 = step === 1;
  const isStep2 = step === 2;
  const isStep3 = step === 3;
  const stepIndex = step === 1 ? 0 : step === 2 ? 1 : 2;
  const progress = stepIndex / 2;

  const disabled = busy || loadingLookups;

  const clientOptions = useMemo(() => {
    return customers
      .slice()
      .sort((a, b) => `${a.name}`.localeCompare(`${b.name}`))
      .map((c) => ({
        value: c.id,
        label: c.email ? `${c.name} (${c.email})` : c.name,
      }));
  }, [customers]);

  const technicianOptions = useMemo(() => {
    return technicians
      .slice()
      .sort((a, b) => `${a.name}`.localeCompare(`${b.name}`))
      .map((t) => ({ value: t.id, label: `${t.name} (${t.email})` }));
  }, [technicians]);

  const selectedTechnicianOption = useMemo(() => {
    if (typeof assignedTechnicianId !== "number") return null;
    const found = technicianOptions.find((o) => o.value === assignedTechnicianId);
    return found ?? null;
  }, [assignedTechnicianId, technicianOptions]);

  const deviceOptions = useMemo(() => {
    return devicesCatalog
      .slice()
      .sort((a, b) => `${a.model}`.localeCompare(`${b.model}`))
      .map((d) => ({ value: d.id, label: d.model }));
  }, [devicesCatalog]);

  const selectedDeviceOptions = useMemo(() => {
    const ids = estimateDevicesAdmin
      .map((d) => (typeof d.device_id === "number" ? d.device_id : null))
      .filter((x): x is number => typeof x === "number");

    return ids
      .map((id) => deviceOptions.find((o) => o.value === id) ?? { value: id, label: `Device #${id}` })
      .filter(Boolean);
  }, [deviceOptions, estimateDevicesAdmin]);

  const deviceContextOptions = useMemo(() => {
    return selectedDeviceOptions.map((d) => ({ value: d.value, label: d.label }));
  }, [selectedDeviceOptions]);

  const serviceOptions = useMemo(() => {
    return servicesCatalog
      .slice()
      .sort((a, b) => `${a.name}`.localeCompare(`${b.name}`))
      .map((s) => ({ value: s.id, label: s.name }));
  }, [servicesCatalog]);

  const partOptions = useMemo(() => {
    return partsCatalog
      .slice()
      .sort((a, b) => `${a.name}`.localeCompare(`${b.name}`))
      .map((p) => ({ value: p.id, label: p.name, code: p.code, capacity: p.capacity }));
  }, [partsCatalog]);

  const estimatedTotal = useMemo(() => {
    const sumLines = (lines: Array<{ qty: string; price: string }>) => {
      return lines.reduce((acc, l) => {
        const q = Number(l.qty);
        const p = Number(l.price);
        if (!Number.isFinite(q) || !Number.isFinite(p)) return acc;
        return acc + q * p;
      }, 0);
    };

    return sumLines(estimateServices) + sumLines(estimateParts) + sumLines(estimateOtherItems);
  }, [estimateOtherItems, estimateParts, estimateServices]);

  const selectedCustomer = useMemo(() => {
    if (typeof customerId !== "number") return null;
    return customers.find((c) => c.id === customerId) ?? null;
  }, [customers, customerId]);

  const canSendEmail = useMemo(() => {
    if (customerMode === "new") {
      return customerCreateEmail.trim().length > 0;
    }
    const email = selectedCustomer?.email ?? "";
    return email.trim().length > 0;
  }, [customerCreateEmail, customerMode, selectedCustomer?.email]);

  useEffect(() => {
    let alive = true;

    async function loadLookups() {
      if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;

      try {
        setLoadingLookups(true);

        const [clientsRes, devicesRes, techniciansRes, partsRes, servicesRes] = await Promise.all([
          apiFetch<{ clients: ApiClient[] }>(`/api/${tenantSlug}/app/clients?limit=200`),
          apiFetch<{ devices: ApiDevice[] }>(`/api/${tenantSlug}/app/repairbuddy/devices?limit=10`),
          apiFetch<{ users: ApiTechnician[] }>(`/api/${tenantSlug}/app/technicians?per_page=100&sort=name&dir=asc`),
          apiFetch<{ parts: ApiPart[] }>(`/api/${tenantSlug}/app/repairbuddy/parts?limit=200`),
          apiFetch<{ services: ApiService[] }>(`/api/${tenantSlug}/app/repairbuddy/services?limit=200`),
        ]);

        if (!alive) return;

        setCustomers(Array.isArray(clientsRes.clients) ? clientsRes.clients : []);
        setDevicesCatalog(Array.isArray(devicesRes.devices) ? devicesRes.devices : []);
        setTechnicians(Array.isArray(techniciansRes.users) ? techniciansRes.users : []);

        const partsList = Array.isArray(partsRes.parts) ? partsRes.parts : [];
        setPartsCatalog(
          partsList.map((p) => ({
            id: p.id,
            name: p.name,
            code: p.manufacturing_code ?? "",
            capacity: p.capacity ?? "",
          })),
        );

        const servicesList = Array.isArray(servicesRes.services) ? servicesRes.services : [];
        setServicesCatalog(servicesList.map((s) => ({ id: s.id, name: s.name })));

        setError(null);
      } catch (e) {
        if (!alive) return;

        if (e instanceof ApiError && e.status === 428 && typeof tenantSlug === "string" && tenantSlug.trim().length > 0) {
          const next = `/app/${tenantSlug}/estimates/new`;
          router.replace(`/app/${tenantSlug}/branches/select?next=${encodeURIComponent(next)}`);
          return;
        }

        setError(e instanceof ApiError ? e.message : e instanceof Error ? e.message : "Failed to load data.");
      } finally {
        if (!alive) return;
        setLoadingLookups(false);
      }
    }

    void loadLookups();

    return () => {
      alive = false;
    };
  }, [router, tenantSlug]);

  const loadCustomerOptions = async (inputValue: string): Promise<CustomerOption[]> => {
    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return [];

    try {
      const qs = new URLSearchParams();
      qs.set("limit", "50");
      const q = inputValue.trim();
      if (q) qs.set("query", q);

      const res = await apiFetch<{ clients: ApiClient[] }>(`/api/${tenantSlug}/app/clients?${qs.toString()}`);
      const list = Array.isArray(res.clients) ? res.clients : [];
      return list.map((c) => ({
        value: c.id,
        label: c.email ? `${c.name} (${c.email})` : c.name,
      }));
    } catch {
      return [];
    }
  };

  const loadDeviceOptions = async (inputValue: string): Promise<DeviceOption[]> => {
    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return [];

    try {
      const qs = new URLSearchParams();
      qs.set("limit", inputValue.trim() ? "50" : "10");
      const q = inputValue.trim();
      if (q) qs.set("q", q);

      const res = await apiFetch<{ devices: ApiDevice[] }>(`/api/${tenantSlug}/app/repairbuddy/devices?${qs.toString()}`);
      const list = Array.isArray(res.devices) ? res.devices : [];
      return list.map((d) => ({ value: d.id, label: d.model }));
    } catch {
      return [];
    }
  };

  async function submitEstimate(mode: "save" | "save_send") {
    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
    if (busy) return;

    setError(null);
    setBusy(true);

    try {
      const trimmedTitle = title.trim();
      if (trimmedTitle === "") {
        setError("Title is required.");
        return;
      }

      const shouldCreateCustomer = customerMode === "new";
      if (shouldCreateCustomer) {
        if (customerCreateName.trim() === "") {
          setError("Customer name is required.");
          return;
        }
        if (customerCreateEmail.trim() === "") {
          setError("Customer email is required.");
          return;
        }
      }

      if (!shouldCreateCustomer && typeof customerId !== "number") {
        setError("Select a customer.");
        return;
      }

      const payload: Record<string, unknown> = {
        case_number: caseNumber.trim() !== "" ? caseNumber.trim() : null,
        title: trimmedTitle,
        status: "pending",
        customer_id: !shouldCreateCustomer && typeof customerId === "number" ? customerId : null,
        pickup_date: pickupDate.trim() !== "" ? pickupDate.trim() : null,
        delivery_date: deliveryDate.trim() !== "" ? deliveryDate.trim() : null,
        case_detail: caseDetail.trim() !== "" ? caseDetail.trim() : null,
        assigned_technician_id: typeof assignedTechnicianId === "number" ? assignedTechnicianId : null,
        devices:
          estimateDevicesAdmin.length > 0
            ? estimateDevicesAdmin
                .filter((d) => typeof d.device_id === "number")
                .map((d) => ({
                  device_id: d.device_id as number,
                  serial: d.serial.trim() !== "" ? d.serial.trim() : null,
                  pin: d.pin.trim() !== "" ? d.pin.trim() : null,
                  notes: d.notes.trim() !== "" ? d.notes.trim() : null,
                }))
            : [],
      };

      if (shouldCreateCustomer) {
        payload.customer_create = {
          name: customerCreateName.trim(),
          email: customerCreateEmail.trim(),
          ...(customerCreatePhone.trim() !== "" ? { phone: customerCreatePhone.trim() } : {}),
          ...(customerCreateCompany.trim() !== "" ? { company: customerCreateCompany.trim() } : {}),
        };
      }

      const res = await apiFetch<{ estimate: ApiEstimate }>(`/api/${tenantSlug}/app/repairbuddy/estimates`, {
        method: "POST",
        body: payload,
      });

      const estimateId = res?.estimate?.id;
      if (typeof estimateId !== "number") {
        throw new Error("Estimate creation failed.");
      }

      try {
        for (const line of estimateServices) {
          const qtyNum = Math.round(Number(line.qty));
          const qty = Number.isFinite(qtyNum) && qtyNum > 0 ? qtyNum : 1;

          const rawPrice = line.price.trim();
          const priceNum = rawPrice.length > 0 ? Number(rawPrice) : NaN;
          const unitPriceAmountCents = Number.isFinite(priceNum) ? Math.round(priceNum * 100) : undefined;

          await apiFetch(`/api/${tenantSlug}/app/repairbuddy/estimates/${estimateId}/items`, {
            method: "POST",
            body: {
              item_type: "service",
              ref_id: line.service_id,
              qty,
              ...(typeof unitPriceAmountCents === "number" ? { unit_price_amount_cents: unitPriceAmountCents } : {}),
              meta: {
                ...(typeof line.device_id === "number" ? { device_id: line.device_id } : {}),
              },
            },
          });
        }

        for (const line of estimateOtherItems) {
          const name = line.name.trim();
          if (name === "") continue;

          const qtyNum = Math.round(Number(line.qty));
          const qty = Number.isFinite(qtyNum) && qtyNum > 0 ? qtyNum : 1;

          const rawPrice = line.price.trim();
          const priceNum = rawPrice.length > 0 ? Number(rawPrice) : NaN;
          const unitPriceAmountCents = Number.isFinite(priceNum) ? Math.round(priceNum * 100) : undefined;
          if (typeof unitPriceAmountCents !== "number") continue;

          await apiFetch(`/api/${tenantSlug}/app/repairbuddy/estimates/${estimateId}/items`, {
            method: "POST",
            body: {
              item_type: unitPriceAmountCents < 0 ? "discount" : "fee",
              name,
              qty,
              unit_price_amount_cents: unitPriceAmountCents,
            },
          });
        }

        for (const line of estimateParts) {
          const qtyNum = Math.round(Number(line.qty));
          const qty = Number.isFinite(qtyNum) && qtyNum > 0 ? qtyNum : 1;

          const rawPrice = line.price.trim();
          const priceNum = rawPrice.length > 0 ? Number(rawPrice) : NaN;
          const unitPriceAmountCents = Number.isFinite(priceNum) ? Math.round(priceNum * 100) : undefined;

          await apiFetch(`/api/${tenantSlug}/app/repairbuddy/estimates/${estimateId}/items`, {
            method: "POST",
            body: {
              item_type: "part",
              ref_id: line.part_id,
              qty,
              ...(typeof unitPriceAmountCents === "number" ? { unit_price_amount_cents: unitPriceAmountCents } : {}),
              meta: {
                ...(typeof line.device_id === "number" ? { device_id: line.device_id } : {}),
              },
            },
          });
        }
      } catch (err) {
        notify.error(err instanceof ApiError ? err.message : "Estimate created, but failed to attach items.");
      }

      if (mode === "save_send") {
        try {
          await apiFetch(`/api/${tenantSlug}/app/repairbuddy/estimates/${estimateId}/send`, {
            method: "POST",
          });
          notify.success("Estimate sent.");
        } catch (err) {
          notify.error(err instanceof ApiError ? err.message : "Estimate saved, but sending failed.");
        }
      }

      notify.success("Estimate created.");
      router.replace(`/app/${tenantSlug}/estimates/${estimateId}`);
    } catch (e) {
      if (e instanceof ApiError) {
        setError(e.message);
      } else {
        setError(e instanceof Error ? e.message : "Failed to create estimate.");
      }
    } finally {
      setBusy(false);
    }
  }

  return (
    <div className="space-y-6">
      <PageHeader title="New estimate" description="Complete the steps to create the estimate." />

      {error ? <div className="text-sm text-red-600">{error}</div> : null}

      <Modal
        open={customerCreateOpen}
        title="Add customer"
        onClose={() => {
          setCustomerCreateError(null);
          setCustomerCreateOpen(false);
        }}
        footer={
          <div className="flex items-center justify-end gap-2">
            <Button
              type="button"
              variant="outline"
              onClick={() => {
                setCustomerCreateError(null);
                setCustomerCreateOpen(false);
              }}
            >
              Cancel
            </Button>
            <Button
              type="button"
              onClick={() => {
                const name = customerCreateName.trim();
                const email = customerCreateEmail.trim();
                if (name === "") {
                  setCustomerCreateError("Customer name is required.");
                  return;
                }
                if (email === "") {
                  setCustomerCreateError("Customer email is required.");
                  return;
                }

                setCustomerCreateError(null);
                setCustomerMode("new");
                setCustomerId(null);
                setCustomerOption({ value: -1, label: `${name} (${email})` });
                setCustomerCreateOpen(false);
              }}
            >
              Save
            </Button>
          </div>
        }
      >
        <div className="space-y-4">
          {customerCreateError ? <div className="text-sm text-red-600">{customerCreateError}</div> : null}
          <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
            <div>
              <div className="mb-1 text-xs text-zinc-600">Name</div>
              <Input value={customerCreateName} onChange={(e) => setCustomerCreateName(e.target.value)} />
            </div>
            <div>
              <div className="mb-1 text-xs text-zinc-600">Email</div>
              <Input type="email" value={customerCreateEmail} onChange={(e) => setCustomerCreateEmail(e.target.value)} />
            </div>
            <div>
              <div className="mb-1 text-xs text-zinc-600">Phone</div>
              <Input value={customerCreatePhone} onChange={(e) => setCustomerCreatePhone(e.target.value)} />
            </div>
            <div>
              <div className="mb-1 text-xs text-zinc-600">Company</div>
              <Input value={customerCreateCompany} onChange={(e) => setCustomerCreateCompany(e.target.value)} />
            </div>
          </div>
        </div>
      </Modal>

      <Modal
        open={servicePickerModalOpen}
        title="Add service"
        onClose={() => {
          setServicePickerError(null);
          setServicePickerModalOpen(false);
        }}
        footer={
          <div className="flex items-center justify-end gap-2">
            <Button
              type="button"
              variant="outline"
              onClick={() => {
                setServicePickerError(null);
                setServicePickerModalOpen(false);
              }}
            >
              Cancel
            </Button>
            <Button
              type="button"
              onClick={() => {
                if (!servicePickerService) {
                  setServicePickerError("Please select a service.");
                  return;
                }
                if (!servicePickerDevice) {
                  setServicePickerError("Please select a device.");
                  return;
                }

                const qtyNum = Math.round(Number(servicePickerQty));
                const qty = Number.isFinite(qtyNum) && qtyNum > 0 ? String(qtyNum) : "1";

                setEstimateServices((prev) => [
                  ...prev,
                  {
                    id: makeId("svc"),
                    service_id: servicePickerService.value,
                    service: { id: servicePickerService.value, name: servicePickerService.label },
                    device_id: servicePickerDevice.value,
                    qty,
                    price: servicePickerPrice,
                  },
                ]);

                setServicePickerService(null);
                setServicePickerQty("1");
                setServicePickerPrice("");
                setServicePickerError(null);
                setServicePickerModalOpen(false);
              }}
            >
              Add
            </Button>
          </div>
        }
      >
        <div className="space-y-4">
          {servicePickerError ? <div className="text-sm text-red-600">{servicePickerError}</div> : null}

          <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
            <div className="md:col-span-2">
              <div className="mb-1 text-xs text-zinc-600">Service</div>
              <Select
                instanceId="estimate_service_select"
                inputId="estimate_service_select"
                isSearchable
                isClearable
                options={serviceOptions}
                value={servicePickerService}
                onChange={(opt) => setServicePickerService((opt as ServiceOption | null) ?? null)}
                isDisabled={disabled}
                placeholder={serviceOptions.length > 0 ? "Search services..." : "No services yet"}
                classNamePrefix="rb-select"
                styles={{
                  control: (base) => ({
                    ...base,
                    borderRadius: "var(--rb-radius-sm)",
                    borderColor: "#d4d4d8",
                    minHeight: 40,
                    boxShadow: "none",
                  }),
                  menu: (base) => ({
                    ...base,
                    zIndex: 50,
                  }),
                }}
              />
            </div>

            <div className="md:col-span-2">
              <div className="mb-1 text-xs text-zinc-600">Device</div>
              <Select
                instanceId="estimate_service_device_select"
                inputId="estimate_service_device_select"
                isSearchable
                isClearable
                options={deviceContextOptions}
                value={servicePickerDevice}
                onChange={(opt) => setServicePickerDevice((opt as DeviceContextOption | null) ?? null)}
                isDisabled={disabled || deviceContextOptions.length === 0}
                placeholder={deviceContextOptions.length > 0 ? "Select device..." : "No devices selected"}
                classNamePrefix="rb-select"
                styles={{
                  control: (base) => ({
                    ...base,
                    borderRadius: "var(--rb-radius-sm)",
                    borderColor: "#d4d4d8",
                    minHeight: 40,
                    boxShadow: "none",
                  }),
                  menu: (base) => ({
                    ...base,
                    zIndex: 50,
                  }),
                }}
              />
            </div>

            <div>
              <div className="mb-1 text-xs text-zinc-600">Qty</div>
              <Input value={servicePickerQty} onChange={(e) => setServicePickerQty(e.target.value)} disabled={disabled} />
            </div>
            <div>
              <div className="mb-1 text-xs text-zinc-600">Price</div>
              <Input value={servicePickerPrice} onChange={(e) => setServicePickerPrice(e.target.value)} disabled={disabled} placeholder="Optional" />
            </div>
          </div>
        </div>
      </Modal>

      <Modal
        open={partPickerModalOpen}
        title="Add part"
        onClose={() => {
          setPartPickerError(null);
          setPartPickerModalOpen(false);
        }}
        footer={
          <div className="flex items-center justify-end gap-2">
            <Button
              type="button"
              variant="outline"
              onClick={() => {
                setPartPickerError(null);
                setPartPickerModalOpen(false);
              }}
            >
              Cancel
            </Button>
            <Button
              type="button"
              onClick={() => {
                if (!partPickerPart) {
                  setPartPickerError("Please select a part.");
                  return;
                }
                if (!partPickerDevice) {
                  setPartPickerError("Please select a device.");
                  return;
                }

                const qtyNum = Math.round(Number(partPickerQty));
                const qty = Number.isFinite(qtyNum) && qtyNum > 0 ? String(qtyNum) : "1";

                setEstimateParts((prev) => [
                  ...prev,
                  {
                    id: makeId("part"),
                    part_id: partPickerPart.value,
                    part: {
                      id: partPickerPart.value,
                      name: partPickerPart.label,
                      code: partPickerPart.code,
                      capacity: partPickerPart.capacity,
                    },
                    device_id: partPickerDevice.value,
                    qty,
                    price: partPickerPrice,
                  },
                ]);

                setPartPickerPart(null);
                setPartPickerQty("1");
                setPartPickerPrice("");
                setPartPickerError(null);
                setPartPickerModalOpen(false);
              }}
            >
              Add
            </Button>
          </div>
        }
      >
        <div className="space-y-4">
          {partPickerError ? <div className="text-sm text-red-600">{partPickerError}</div> : null}

          <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
            <div className="md:col-span-2">
              <div className="mb-1 text-xs text-zinc-600">Select part</div>
              <Select
                instanceId="estimate_part_select"
                inputId="estimate_part_select"
                isSearchable
                isClearable
                options={partOptions}
                value={partPickerPart}
                onChange={(opt) => setPartPickerPart((opt as PartOption | null) ?? null)}
                isDisabled={disabled}
                placeholder={partOptions.length > 0 ? "Search parts..." : "No parts yet"}
                classNamePrefix="rb-select"
                styles={{
                  control: (base) => ({
                    ...base,
                    borderRadius: "var(--rb-radius-sm)",
                    borderColor: "#d4d4d8",
                    minHeight: 40,
                    boxShadow: "none",
                  }),
                  menu: (base) => ({
                    ...base,
                    zIndex: 50,
                  }),
                }}
              />
            </div>

            <div className="md:col-span-2">
              <div className="mb-1 text-xs text-zinc-600">Device</div>
              <Select
                instanceId="estimate_part_device"
                isSearchable
                isClearable
                options={deviceContextOptions}
                value={partPickerDevice}
                onChange={(opt) => setPartPickerDevice((opt as DeviceContextOption | null) ?? null)}
                isDisabled={disabled || deviceContextOptions.length === 0}
                placeholder={deviceContextOptions.length > 0 ? "Select device..." : "No devices selected"}
                classNamePrefix="rb-select"
                styles={{
                  control: (base) => ({
                    ...base,
                    borderRadius: "var(--rb-radius-sm)",
                    borderColor: "#d4d4d8",
                    minHeight: 40,
                    boxShadow: "none",
                  }),
                  menu: (base) => ({
                    ...base,
                    zIndex: 50,
                  }),
                }}
              />
            </div>

            <div>
              <div className="mb-1 text-xs text-zinc-600">Qty</div>
              <Input value={partPickerQty} onChange={(e) => setPartPickerQty(e.target.value)} disabled={disabled} />
            </div>
            <div>
              <div className="mb-1 text-xs text-zinc-600">Price</div>
              <Input value={partPickerPrice} onChange={(e) => setPartPickerPrice(e.target.value)} disabled={disabled} placeholder="Optional" />
            </div>
          </div>
        </div>
      </Modal>

      <form
        className="space-y-6"
        onSubmit={(e) => {
          e.preventDefault();
        }}
      >
        <div className="grid gap-6 lg:grid-cols-[260px_1fr]">
          <Card className="shadow-none lg:sticky lg:top-6 lg:self-start">
            <CardHeader className="pb-3">
              <CardTitle className="text-base">Estimate steps</CardTitle>
              <CardDescription>Complete the steps to create the estimate.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="h-2 w-full overflow-hidden rounded-full bg-[var(--rb-border)]">
                <div
                  className="h-full bg-[linear-gradient(90deg,var(--rb-blue),var(--rb-orange))]"
                  style={{ width: `${Math.round(progress * 100)}%` }}
                />
              </div>

              <nav aria-label="Estimate steps" className="space-y-1">
                {[1, 2, 3].map((s, idx) => {
                  const isCurrent = s === step;
                  const isCompleted = idx < stepIndex;
                  const isAvailable = idx <= stepIndex;

                  return (
                    <button
                      key={s}
                      type="button"
                      disabled={!isAvailable || disabled}
                      onClick={() => {
                        if (!isAvailable) return;
                        setError(null);
                        setStep(s as 1 | 2 | 3);
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
                            <svg
                              viewBox="0 0 24 24"
                              className="h-4 w-4"
                              fill="none"
                              stroke="currentColor"
                              strokeWidth={2}
                              strokeLinecap="round"
                              strokeLinejoin="round"
                              aria-hidden="true"
                            >
                              <path d="M20 6L9 17l-5-5" />
                            </svg>
                          ) : (
                            idx + 1
                          )}
                        </div>
                        <div className="min-w-0">
                          <div className="text-sm font-semibold text-[var(--rb-text)]">
                            {s === 1 ? "Customer & devices" : s === 2 ? "Items and services" : "Review & send"}
                          </div>
                          <div className="mt-0.5 line-clamp-2 text-xs text-zinc-600">
                            {s === 1
                              ? "Case, dates, customer, technician, description, devices."
                              : s === 2
                                ? "Add services, parts and other items."
                                : "Review and optionally send the estimate."}
                          </div>
                        </div>
                      </div>
                    </button>
                  );
                })}
              </nav>
            </CardContent>
          </Card>

          <Card className="shadow-none flex flex-col">
            <CardHeader>
              <div className="flex items-start justify-between gap-4">
                <div>
                  <CardTitle className="text-base">{isStep1 ? "Customer & devices" : isStep2 ? "Items and services" : "Review & send"}</CardTitle>
                  <CardDescription>
                    {isStep1
                      ? "Enter the customer, technician, description and devices."
                      : isStep2
                        ? "Attach services, parts and other items."
                        : "Review the estimate and optionally send it to the customer."}
                  </CardDescription>
                </div>
                <div className="text-right">
                  <div className="text-xs text-zinc-500">Step {stepIndex + 1} of 3</div>
                  <div className="mt-2 flex items-center justify-end gap-2">
                    <div className="flex items-center gap-1" aria-label="Progress">
                      {[0, 1, 2].map((i) => {
                        const isDone = i < stepIndex;
                        const isNow = i === stepIndex;
                        return (
                          <span
                            key={i}
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
              {isStep1 ? (
                <div className="space-y-5">
                  <FormRow label="Title" fieldId="estimate_title" required>
                    <Input id="estimate_title" value={title} onChange={(e) => setTitle(e.target.value)} disabled={disabled} />
                  </FormRow>

                  <FormRow label="Case number" fieldId="estimate_case_number">
                    <Input id="estimate_case_number" value={caseNumber} onChange={(e) => setCaseNumber(e.target.value)} disabled={disabled} />
                  </FormRow>

                  <FormRow label="Dates" fieldId="estimate_dates">
                    <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                      <div>
                        <div className="mb-1 text-xs text-zinc-600">Pickup date</div>
                        <Input id="estimate_pickup_date" type="date" value={pickupDate} onChange={(e) => setPickupDate(e.target.value)} disabled={disabled} />
                      </div>
                      <div>
                        <div className="mb-1 text-xs text-zinc-600">Delivery date</div>
                        <Input id="estimate_delivery_date" type="date" value={deliveryDate} onChange={(e) => setDeliveryDate(e.target.value)} disabled={disabled} />
                      </div>
                    </div>
                  </FormRow>

                  <FormRow label="Customer" fieldId="estimate_customer">
                    <div className="space-y-2">
                      <div className="flex items-start gap-2">
                        <div className="min-w-0 flex-1">
                          <AsyncSelect
                            inputId="estimate_customer"
                            instanceId="estimate_customer"
                            cacheOptions
                            defaultOptions={clientOptions}
                            loadOptions={loadCustomerOptions}
                            isClearable
                            isSearchable
                            value={customerOption}
                            onChange={(opt) => {
                              const next = (opt as CustomerOption | null) ?? null;
                              setCustomerMode("existing");
                              setCustomerOption(next);
                              setCustomerId(typeof next?.value === "number" && next.value > 0 ? next.value : null);
                            }}
                            isDisabled={disabled}
                            placeholder="Search customers..."
                            classNamePrefix="rb-select"
                            styles={{
                              control: (base) => ({
                                ...base,
                                borderRadius: "var(--rb-radius-sm)",
                                borderColor: "#d4d4d8",
                                minHeight: 40,
                                boxShadow: "none",
                              }),
                              menu: (base) => ({
                                ...base,
                                zIndex: 50,
                              }),
                            }}
                          />
                        </div>

                        <Button
                          type="button"
                          variant="primary"
                          size="md"
                          className="w-10 px-0"
                          disabled={disabled}
                          onClick={() => {
                            setCustomerCreateError(null);
                            setCustomerCreateOpen(true);
                          }}
                        >
                          +
                        </Button>
                      </div>

                      {customerMode === "new" ? <div className="text-xs text-zinc-600">This estimate will create a new customer.</div> : null}
                    </div>
                  </FormRow>

                  <FormRow label="Technician" fieldId="estimate_technician">
                    <Select
                      inputId="estimate_technician"
                      instanceId="estimate_technician"
                      isSearchable
                      isClearable
                      options={technicianOptions}
                      value={selectedTechnicianOption}
                      onChange={(opt) => {
                        const next = (opt as TechnicianOption | null) ?? null;
                        setAssignedTechnicianId(typeof next?.value === "number" && next.value > 0 ? next.value : null);
                      }}
                      isDisabled={disabled || technicianOptions.length === 0}
                      placeholder={technicianOptions.length > 0 ? "Select technician..." : "No technicians available"}
                      classNamePrefix="rb-select"
                      styles={{
                        control: (base) => ({
                          ...base,
                          borderRadius: "var(--rb-radius-sm)",
                          borderColor: "#d4d4d8",
                          minHeight: 40,
                          boxShadow: "none",
                        }),
                        menu: (base) => ({
                          ...base,
                          zIndex: 50,
                        }),
                      }}
                    />
                  </FormRow>

                  <FormRow label="Estimate description" fieldId="estimate_case_detail">
                    <textarea
                      id="estimate_case_detail"
                      value={caseDetail}
                      onChange={(e) => setCaseDetail(e.target.value)}
                      disabled={disabled}
                      rows={4}
                      placeholder="Enter estimate description."
                      className="w-full rounded-[var(--rb-radius-sm)] border border-zinc-300 bg-white px-3 py-2 text-sm text-[var(--rb-text)]"
                    />
                  </FormRow>

                  <FormRow label="Add devices to this estimate" fieldId="estimate_devices_admin">
                    <div className="space-y-4">
                      <div className="rounded-[var(--rb-radius-sm)] border border-dashed border-zinc-300 bg-white p-3">
                        <div className="grid grid-cols-1 gap-3">
                          {estimateDevicesAdmin.map((d, idx) => (
                            <div key={idx} className="grid grid-cols-1 gap-3 md:grid-cols-[1fr_1fr_1fr_1fr_auto] md:items-end">
                              <div>
                                <div className="mb-1 text-xs text-zinc-600">Device</div>
                                <AsyncSelect
                                  inputId={`estimate_device_${idx}`}
                                  instanceId={`estimate_device_${idx}`}
                                  cacheOptions
                                  defaultOptions={deviceOptions}
                                  loadOptions={loadDeviceOptions}
                                  isClearable
                                  isSearchable
                                  value={d.option}
                                  onChange={(opt) => {
                                    const next = (opt as DeviceOption | null) ?? null;
                                    setEstimateDevicesAdmin((prev) =>
                                      prev.map((x, i) =>
                                        i === idx
                                          ? {
                                              ...x,
                                              option: next,
                                              device_id: typeof next?.value === "number" ? next.value : null,
                                            }
                                          : x,
                                      ),
                                    );
                                  }}
                                  isDisabled={disabled}
                                  placeholder="Search..."
                                  classNamePrefix="rb-select"
                                  styles={{
                                    control: (base) => ({
                                      ...base,
                                      borderRadius: "var(--rb-radius-sm)",
                                      borderColor: "#d4d4d8",
                                      minHeight: 40,
                                      boxShadow: "none",
                                    }),
                                    menu: (base) => ({
                                      ...base,
                                      zIndex: 50,
                                    }),
                                  }}
                                />
                              </div>

                              <div>
                                <div className="mb-1 text-xs text-zinc-600">Serial / IMEI</div>
                                <Input
                                  value={d.serial}
                                  onChange={(e) => {
                                    const v = e.target.value;
                                    setEstimateDevicesAdmin((prev) => prev.map((x, i) => (i === idx ? { ...x, serial: v } : x)));
                                  }}
                                  disabled={disabled}
                                />
                              </div>

                              <div>
                                <div className="mb-1 text-xs text-zinc-600">Pin</div>
                                <Input
                                  value={d.pin}
                                  onChange={(e) => {
                                    const v = e.target.value;
                                    setEstimateDevicesAdmin((prev) => prev.map((x, i) => (i === idx ? { ...x, pin: v } : x)));
                                  }}
                                  disabled={disabled}
                                />
                              </div>

                              <div>
                                <div className="mb-1 text-xs text-zinc-600">Notes</div>
                                <Input
                                  value={d.notes}
                                  onChange={(e) => {
                                    const v = e.target.value;
                                    setEstimateDevicesAdmin((prev) => prev.map((x, i) => (i === idx ? { ...x, notes: v } : x)));
                                  }}
                                  disabled={disabled}
                                />
                              </div>

                              <div className="flex md:justify-end">
                                <span
                                  role="button"
                                  tabIndex={disabled ? -1 : 0}
                                  aria-label="Remove device"
                                  title="Remove device"
                                  onClick={() => {
                                    if (disabled) return;
                                    setEstimateDevicesAdmin((prev) => prev.filter((_, i) => i !== idx));
                                  }}
                                  onKeyDown={(e) => {
                                    if (disabled) return;
                                    if (e.key === "Enter" || e.key === " ") {
                                      e.preventDefault();
                                      setEstimateDevicesAdmin((prev) => prev.filter((_, i) => i !== idx));
                                    }
                                  }}
                                  className={
                                    "inline-flex h-10 w-10 items-center justify-center rounded-[var(--rb-radius-sm)] border border-zinc-300 bg-white text-zinc-700 " +
                                    (disabled ? "pointer-events-none opacity-50" : "cursor-pointer hover:bg-zinc-50")
                                  }
                                >
                                  <Trash2 className="h-4 w-4" aria-hidden="true" />
                                </span>
                              </div>
                            </div>
                          ))}
                        </div>
                      </div>

                      <div>
                        <Button
                          type="button"
                          variant="outline"
                          size="sm"
                          disabled={disabled}
                          onClick={() => {
                            setEstimateDevicesAdmin((prev) => [
                              ...prev,
                              {
                                device_id: null,
                                option: null,
                                serial: "",
                                pin: "",
                                notes: "",
                              },
                            ]);
                          }}
                        >
                          Add device
                        </Button>
                      </div>
                    </div>
                  </FormRow>
                </div>
              ) : isStep2 ? (
                <div className="space-y-5">
                  <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white px-4 py-3">
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                      <div>
                        <div className="text-sm font-semibold text-[var(--rb-text)]">Services</div>
                        <div className="mt-1 text-xs text-zinc-600">Add services for each selected device and adjust quantities/prices.</div>
                      </div>
                      <div className="flex items-center gap-2">
                        <Button
                          type="button"
                          variant="outline"
                          size="sm"
                          disabled={disabled || deviceContextOptions.length === 0}
                          onClick={() => {
                            setServicePickerError(null);
                            if (!servicePickerDevice && deviceContextOptions.length > 0) {
                              setServicePickerDevice(deviceContextOptions[0]);
                            }
                            setServicePickerModalOpen(true);
                          }}
                        >
                          Add service
                        </Button>
                      </div>
                    </div>
                  </div>

                  {estimateServices.length > 0 ? (
                    <div className="space-y-2">
                      {estimateServices.map((line) => {
                        const deviceLabel =
                          typeof line.device_id === "number"
                            ? deviceContextOptions.find((o) => o.value === line.device_id)?.label ?? `Device #${line.device_id}`
                            : "—";

                        const qtyNum = Number(line.qty);
                        const priceNum = Number(line.price);
                        const total = (Number.isFinite(qtyNum) ? qtyNum : 0) * (Number.isFinite(priceNum) ? priceNum : 0);

                        return (
                          <div
                            key={line.id}
                            className="flex flex-col gap-2 rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                          >
                            <div className="min-w-0">
                              <div className="truncate text-sm font-semibold text-[var(--rb-text)]">{line.service.name}</div>
                              <div className="mt-1 text-xs text-zinc-600">
                                {deviceLabel}
                                <span className="mx-2">•</span>
                                Qty: {line.qty || "0"}
                                <span className="mx-2">•</span>
                                Price: {line.price || "0"}
                                <span className="mx-2">•</span>
                                Total: {Number.isFinite(total) ? total.toFixed(2) : "0.00"}
                              </div>
                            </div>

                            <div className="flex items-center gap-2">
                              <Button type="button" variant="outline" size="sm" disabled={disabled} onClick={() => setEstimateServices((prev) => prev.filter((x) => x.id !== line.id))}>
                                Remove
                              </Button>
                            </div>
                          </div>
                        );
                      })}
                    </div>
                  ) : (
                    <div className="text-sm text-zinc-600">No services added yet.</div>
                  )}

                  <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white px-4 py-3">
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                      <div>
                        <div className="text-sm font-semibold text-[var(--rb-text)]">Parts</div>
                        <div className="mt-1 text-xs text-zinc-600">Add parts for each selected device and adjust quantities/prices.</div>
                      </div>
                      <div className="flex items-center gap-2">
                        <Button
                          type="button"
                          variant="outline"
                          size="sm"
                          disabled={disabled || deviceContextOptions.length === 0}
                          onClick={() => {
                            setPartPickerError(null);
                            if (!partPickerDevice && deviceContextOptions.length > 0) {
                              setPartPickerDevice(deviceContextOptions[0]);
                            }
                            setPartPickerModalOpen(true);
                          }}
                        >
                          Add part
                        </Button>
                      </div>
                    </div>
                  </div>

                  {estimateParts.length > 0 ? (
                    <div className="space-y-2">
                      {estimateParts.map((line) => {
                        const deviceLabel =
                          typeof line.device_id === "number"
                            ? deviceContextOptions.find((o) => o.value === line.device_id)?.label ?? `Device #${line.device_id}`
                            : "—";

                        const qtyNum = Number(line.qty);
                        const priceNum = Number(line.price);
                        const total = (Number.isFinite(qtyNum) ? qtyNum : 0) * (Number.isFinite(priceNum) ? priceNum : 0);

                        return (
                          <div
                            key={line.id}
                            className="flex flex-col gap-2 rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                          >
                            <div className="min-w-0">
                              <div className="truncate text-sm font-semibold text-[var(--rb-text)]">{line.part.name}</div>
                              <div className="mt-1 text-xs text-zinc-600">
                                {deviceLabel}
                                <span className="mx-2">•</span>
                                Qty: {line.qty || "0"}
                                <span className="mx-2">•</span>
                                Price: {line.price || "0"}
                                <span className="mx-2">•</span>
                                Total: {Number.isFinite(total) ? total.toFixed(2) : "0.00"}
                              </div>
                              {line.part.code || line.part.capacity ? (
                                <div className="mt-1 text-xs text-zinc-500">
                                  {line.part.code ? `Code: ${line.part.code}` : null}
                                  {line.part.code && line.part.capacity ? <span className="mx-2">•</span> : null}
                                  {line.part.capacity ? `Capacity: ${line.part.capacity}` : null}
                                </div>
                              ) : null}
                            </div>

                            <div className="flex items-center gap-2">
                              <Button type="button" variant="outline" size="sm" disabled={disabled} onClick={() => setEstimateParts((prev) => prev.filter((x) => x.id !== line.id))}>
                                Remove
                              </Button>
                            </div>
                          </div>
                        );
                      })}
                    </div>
                  ) : (
                    <div className="text-sm text-zinc-600">No parts added yet.</div>
                  )}

                  <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white px-4 py-3">
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                      <div>
                        <div className="text-sm font-semibold text-[var(--rb-text)]">Other items</div>
                        <div className="mt-1 text-xs text-zinc-600">Add custom line items like fees or discounts.</div>
                      </div>
                      <div>
                        <Button
                          type="button"
                          variant="outline"
                          size="sm"
                          disabled={disabled}
                          onClick={() => {
                            setEstimateOtherItems((prev) => [
                              ...prev,
                              {
                                id: makeId("oi"),
                                name: "",
                                qty: "1",
                                price: "",
                              },
                            ]);
                          }}
                        >
                          Add other item
                        </Button>
                      </div>
                    </div>
                  </div>

                  {estimateOtherItems.length > 0 ? (
                    <div className="space-y-2">
                      {estimateOtherItems.map((line) => {
                        const qtyNum = Number(line.qty);
                        const priceNum = Number(line.price);
                        const total = (Number.isFinite(qtyNum) ? qtyNum : 0) * (Number.isFinite(priceNum) ? priceNum : 0);

                        return (
                          <div
                            key={line.id}
                            className="flex flex-col gap-3 rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white px-4 py-3"
                          >
                            <div className="grid grid-cols-1 gap-3 md:grid-cols-3 md:items-end">
                              <div>
                                <div className="mb-1 text-xs text-zinc-600">Name</div>
                                <Input
                                  value={line.name}
                                  onChange={(e) => {
                                    const v = e.target.value;
                                    setEstimateOtherItems((prev) => prev.map((x) => (x.id === line.id ? { ...x, name: v } : x)));
                                  }}
                                  disabled={disabled}
                                  placeholder="e.g. Rent, Used cable"
                                />
                              </div>

                              <div>
                                <div className="mb-1 text-xs text-zinc-600">Qty</div>
                                <Input
                                  value={line.qty}
                                  onChange={(e) => {
                                    const v = e.target.value;
                                    setEstimateOtherItems((prev) => prev.map((x) => (x.id === line.id ? { ...x, qty: v } : x)));
                                  }}
                                  disabled={disabled}
                                />
                              </div>

                              <div>
                                <div className="mb-1 text-xs text-zinc-600">Price</div>
                                <Input
                                  value={line.price}
                                  onChange={(e) => {
                                    const v = e.target.value;
                                    setEstimateOtherItems((prev) => prev.map((x) => (x.id === line.id ? { ...x, price: v } : x)));
                                  }}
                                  disabled={disabled}
                                  placeholder="e.g. 10.00 (use -10.00 for discount)"
                                />
                              </div>
                            </div>

                            <div className="flex items-center justify-between gap-2">
                              <div className="text-xs text-zinc-600">Total: {Number.isFinite(total) ? total.toFixed(2) : "0.00"}</div>
                              <Button type="button" variant="outline" size="sm" disabled={disabled} onClick={() => setEstimateOtherItems((prev) => prev.filter((x) => x.id !== line.id))}>
                                Remove
                              </Button>
                            </div>
                          </div>
                        );
                      })}
                    </div>
                  ) : (
                    <div className="text-sm text-zinc-600">No other items added yet.</div>
                  )}
                </div>
              ) : (
                <div className="space-y-5">
                  <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white px-4 py-3">
                    <div className="text-sm font-semibold text-[var(--rb-text)]">Review</div>
                    <div className="mt-2 grid gap-4 sm:grid-cols-2">
                      <div>
                        <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Customer</div>
                        <div className="mt-1 text-sm text-zinc-700">
                          {customerMode === "new" ? customerCreateName.trim() || "—" : selectedCustomer?.name ?? customerOption?.label ?? "—"}
                        </div>
                        <div className="mt-1 text-xs text-zinc-500">
                          {customerMode === "new" ? customerCreateEmail.trim() : selectedCustomer?.email ?? ""}
                        </div>
                      </div>
                      <div>
                        <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Estimated total</div>
                        <div className="mt-1 text-sm font-semibold text-[var(--rb-text)]">{Number.isFinite(estimatedTotal) ? estimatedTotal.toFixed(2) : "0.00"}</div>
                      </div>
                    </div>
                  </div>

                  <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white px-4 py-3">
                    <div className="text-sm font-semibold text-[var(--rb-text)]">Devices</div>
                    <div className="mt-2 space-y-1 text-sm text-zinc-700">
                      {selectedDeviceOptions.length === 0 ? <div className="text-sm text-zinc-600">None</div> : null}
                      {selectedDeviceOptions.map((d) => (
                        <div key={d.value}>{d.label}</div>
                      ))}
                    </div>
                  </div>

                  <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white px-4 py-3">
                    <div className="text-sm font-semibold text-[var(--rb-text)]">Items</div>
                    <div className="mt-2 space-y-2">
                      {estimateServices.length === 0 && estimateParts.length === 0 && estimateOtherItems.length === 0 ? (
                        <div className="text-sm text-zinc-600">None</div>
                      ) : null}

                      {estimateServices.map((l) => (
                        <div key={l.id} className="flex items-center justify-between gap-3 text-sm">
                          <div className="min-w-0 truncate text-zinc-700">
                            {l.qty} × {l.service.name}
                          </div>
                          <div className="whitespace-nowrap font-semibold text-[var(--rb-text)]">{l.price || "—"}</div>
                        </div>
                      ))}

                      {estimateParts.map((l) => (
                        <div key={l.id} className="flex items-center justify-between gap-3 text-sm">
                          <div className="min-w-0 truncate text-zinc-700">
                            {l.qty} × {l.part.name}
                          </div>
                          <div className="whitespace-nowrap font-semibold text-[var(--rb-text)]">{l.price || "—"}</div>
                        </div>
                      ))}

                      {estimateOtherItems.map((l) => (
                        <div key={l.id} className="flex items-center justify-between gap-3 text-sm">
                          <div className="min-w-0 truncate text-zinc-700">
                            {l.qty} × {l.name}
                          </div>
                          <div className="whitespace-nowrap font-semibold text-[var(--rb-text)]">{l.price || "—"}</div>
                        </div>
                      ))}
                    </div>
                  </div>
                </div>
              )}
            </CardContent>

            <div className="sticky bottom-0 border-t border-[var(--rb-border)] bg-white/90 px-5 py-4 backdrop-blur">
              <div className="flex items-center justify-between gap-4">
                <Button
                  variant="ghost"
                  disabled={disabled || isStep1}
                  onClick={() => {
                    setStep((prev) => (prev === 3 ? 2 : 1));
                  }}
                  type="button"
                >
                  <span className="inline-flex items-center gap-2">
                    <svg
                      viewBox="0 0 24 24"
                      className="h-4 w-4"
                      fill="none"
                      stroke="currentColor"
                      strokeWidth={2}
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      aria-hidden="true"
                    >
                      <path d="M15 18l-6-6 6-6" />
                    </svg>
                    Back
                  </span>
                </Button>

                <div className="flex items-center gap-3">
                  <div className="hidden sm:block text-xs text-zinc-500">
                    {isStep1 ? "Customer & devices" : isStep2 ? "Items and services" : "Review & send"}
                    <span className="mx-2">•</span>
                    {Math.round(progress * 100)}%
                  </div>

                  {!isStep3 ? (
                    <Button
                      variant="primary"
                      disabled={disabled}
                      onClick={() => {
                        setStep((prev) => (prev === 1 ? 2 : 3));
                      }}
                      type="button"
                    >
                      <span className="inline-flex items-center gap-2">
                        Next
                        <svg
                          viewBox="0 0 24 24"
                          className="h-4 w-4"
                          fill="none"
                          stroke="currentColor"
                          strokeWidth={2}
                          strokeLinecap="round"
                          strokeLinejoin="round"
                          aria-hidden="true"
                        >
                          <path d="M9 18l6-6-6-6" />
                        </svg>
                      </span>
                    </Button>
                  ) : (
                    <div className="flex items-center gap-2">
                      <Button
                        id="rb_estimate_save"
                        variant="outline"
                        disabled={disabled}
                        type="button"
                        onClick={() => void submitEstimate("save")}
                      >
                        {busy ? "Saving..." : "Save"}
                      </Button>
                      <Button
                        id="rb_estimate_save_send"
                        variant="primary"
                        disabled={disabled || !auth.can("estimates.manage") || !canSendEmail}
                        type="button"
                        onClick={() => void submitEstimate("save_send")}
                      >
                        {busy ? "Saving..." : "Save & Send"}
                      </Button>
                    </div>
                  )}
                </div>
              </div>
            </div>
          </Card>
        </div>
      </form>
    </div>
  );
}
