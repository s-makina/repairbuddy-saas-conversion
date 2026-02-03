"use client";

import React, { useEffect, useMemo, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import Select from "react-select";
import AsyncSelect from "react-select/async";
import { Trash2 } from "lucide-react";
import { RequireAuth } from "@/components/RequireAuth";
import { useAuth } from "@/lib/auth";
import { PageHeader } from "@/components/ui/PageHeader";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/Card";
import { Button } from "@/components/ui/Button";
import { Input } from "@/components/ui/Input";
import { FormRow } from "@/components/ui/FormRow";
import { Modal } from "@/components/ui/Modal";
import { apiFetch, ApiError } from "@/lib/api";
import { notify } from "@/lib/notify";
import { getRepairBuddySettings } from "@/lib/repairbuddy-settings";

type ApiDevice = {
  id: number;
  model: string;
};

type ApiJobStatus = {
  id: number;
  slug: string;
  label: string;
};

type ApiPaymentStatus = {
  id: number;
  slug: string;
  label: string;
};

type ApiClient = {
  id: number;
  name: string;
  email: string;
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

type NewJobDeviceDraft = {
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

type ApiCustomerDevice = {
  id: number;
  customer_id: number;
  label: string;
  serial: string | null;
};

type CustomerDeviceOption = {
  value: number;
  label: string;
};

type JobDeviceDraft = {
  customer_device_id: number;
  option: CustomerDeviceOption;
  serial: string;
  notes: string;
};

type ApiJob = {
  id: number;
};

type ExtraJobFieldVisibility = "internal" | "customer";

type ExtraJobFieldDraft = {
  id: string;
  datetime: string;
  label: string;
  data: string;
  description: string;
  visibility: ExtraJobFieldVisibility;
  file: File | null;
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

type JobPartLineDraft = {
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

type JobServiceLineDraft = {
  id: string;
  service_id: number;
  service: ServiceDraft;
  device_id: number | null;
  qty: string;
  price: string;
};

type JobOtherItemLineDraft = {
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

export default function NewJobPage() {
  const auth = useAuth();
  const router = useRouter();
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

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
  const [statusSlug, setStatusSlug] = useState<string>("");
  const [paymentStatusSlug, setPaymentStatusSlug] = useState<string>("");
  const [priority, setPriority] = useState<string>("");
  const [customerId, setCustomerId] = useState<number | null>(null);
  const [customerOption, setCustomerOption] = useState<CustomerOption | null>(null);

  const [customerMode, setCustomerMode] = useState<"existing" | "new">("existing");
  const [customerCreateName, setCustomerCreateName] = useState("");
  const [customerCreateEmail, setCustomerCreateEmail] = useState("");
  const [customerCreatePhone, setCustomerCreatePhone] = useState("");
  const [customerCreateCompany, setCustomerCreateCompany] = useState("");
  const [customerCreateAddressLine1, setCustomerCreateAddressLine1] = useState("");
  const [customerCreateAddressLine2, setCustomerCreateAddressLine2] = useState("");
  const [customerCreateAddressCity, setCustomerCreateAddressCity] = useState("");
  const [customerCreateAddressState, setCustomerCreateAddressState] = useState("");
  const [customerCreateAddressPostalCode, setCustomerCreateAddressPostalCode] = useState("");
  const [customerCreateAddressCountry, setCustomerCreateAddressCountry] = useState("");

  const [customerCreateOpen, setCustomerCreateOpen] = useState(false);
  const [customerCreateError, setCustomerCreateError] = useState<string | null>(null);

  const [jobDevicesAdmin, setJobDevicesAdmin] = useState<NewJobDeviceDraft[]>([
    {
      device_id: null,
      option: null,
      serial: "",
      pin: "",
      notes: "",
    },
  ]);

  const [caseNumber, setCaseNumber] = useState<string>("");
  const [pickupDate, setPickupDate] = useState<string>("");
  const [deliveryDate, setDeliveryDate] = useState<string>("");
  const [nextServiceDate, setNextServiceDate] = useState<string>("");
  const [caseDetail, setCaseDetail] = useState<string>("");

  const [orderNote, setOrderNote] = useState("");
  const [jobFile, setJobFile] = useState<File | null>(null);

  const [assignedTechnicianIds, setAssignedTechnicianIds] = useState<number[]>([]);
  const [jobDevices, setJobDevices] = useState<JobDeviceDraft[]>([]);
  const [customerDevicesError, setCustomerDevicesError] = useState<string | null>(null);

  const [extraFields, setExtraFields] = useState<ExtraJobFieldDraft[]>([]);
  const [extraFieldModalOpen, setExtraFieldModalOpen] = useState(false);
  const [extraFieldError, setExtraFieldError] = useState<string | null>(null);
  const [extraFieldDatetime, setExtraFieldDatetime] = useState<string>("");
  const [extraFieldLabel, setExtraFieldLabel] = useState<string>("");
  const [extraFieldData, setExtraFieldData] = useState<string>("");
  const [extraFieldDescription, setExtraFieldDescription] = useState<string>("");
  const [extraFieldVisibility, setExtraFieldVisibility] = useState<ExtraJobFieldVisibility>("internal");
  const [extraFieldFile, setExtraFieldFile] = useState<File | null>(null);

  const [partsCatalog, setPartsCatalog] = useState<PartDraft[]>([]);
  const [servicesCatalog, setServicesCatalog] = useState<ServiceDraft[]>([]);
  const [createPartModalOpen, setCreatePartModalOpen] = useState(false);
  const [createPartError, setCreatePartError] = useState<string | null>(null);
  const [createPartName, setCreatePartName] = useState("");
  const [createPartCode, setCreatePartCode] = useState("");
  const [createPartCapacity, setCreatePartCapacity] = useState("");

  const [createServiceModalOpen, setCreateServiceModalOpen] = useState(false);
  const [createServiceError, setCreateServiceError] = useState<string | null>(null);
  const [createServiceName, setCreateServiceName] = useState("");
  const [createServiceBasePrice, setCreateServiceBasePrice] = useState("");

  const [partPickerModalOpen, setPartPickerModalOpen] = useState(false);

  const [servicePickerModalOpen, setServicePickerModalOpen] = useState(false);

  const [partPickerPart, setPartPickerPart] = useState<PartOption | null>(null);
  const [partPickerDevice, setPartPickerDevice] = useState<DeviceOption | null>(null);
  const [partPickerQty, setPartPickerQty] = useState<string>("1");
  const [partPickerPrice, setPartPickerPrice] = useState<string>("");
  const [partPickerError, setPartPickerError] = useState<string | null>(null);

  const [servicePickerService, setServicePickerService] = useState<ServiceOption | null>(null);
  const [servicePickerDevice, setServicePickerDevice] = useState<DeviceOption | null>(null);
  const [servicePickerQty, setServicePickerQty] = useState<string>("1");
  const [servicePickerPrice, setServicePickerPrice] = useState<string>("");
  const [servicePickerError, setServicePickerError] = useState<string | null>(null);

  const [jobParts, setJobParts] = useState<JobPartLineDraft[]>([]);
  const [jobServices, setJobServices] = useState<JobServiceLineDraft[]>([]);
  const [jobOtherItems, setJobOtherItems] = useState<JobOtherItemLineDraft[]>([]);

  const [statuses, setStatuses] = useState<ApiJobStatus[]>([]);
  const [paymentStatuses, setPaymentStatuses] = useState<ApiPaymentStatus[]>([]);
  const [clients, setClients] = useState<ApiClient[]>([]);

  const [technicians, setTechnicians] = useState<ApiTechnician[]>([]);
  const [customerDevices, setCustomerDevices] = useState<ApiCustomerDevice[]>([]);
  const [nextServiceEnabled, setNextServiceEnabled] = useState(false);
  const [devices, setDevices] = useState<ApiDevice[]>([]);

  const [technicianCreateOpen, setTechnicianCreateOpen] = useState(false);
  const [technicianCreateError, setTechnicianCreateError] = useState<string | null>(null);
  const [technicianCreateName, setTechnicianCreateName] = useState("");
  const [technicianCreateEmail, setTechnicianCreateEmail] = useState("");
  const [technicianOption, setTechnicianOption] = useState<TechnicianOption | null>(null);
  const [technicianMode, setTechnicianMode] = useState<"existing" | "new">("existing");

  useEffect(() => {
    let alive = true;

    async function loadLookups() {
      if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;

      setLoadingLookups(true);
      setError(null);

      try {
        const [statusesRes, paymentStatusesRes] = await Promise.all([
          apiFetch<{ job_statuses: ApiJobStatus[] }>(`/api/${tenantSlug}/app/repairbuddy/job-statuses`),
          apiFetch<{ payment_statuses: ApiPaymentStatus[] }>(`/api/${tenantSlug}/app/repairbuddy/payment-statuses`),
        ]);

        if (!alive) return;

        const nextStatuses = Array.isArray(statusesRes.job_statuses) ? statusesRes.job_statuses : [];
        const nextPayment = Array.isArray(paymentStatusesRes.payment_statuses) ? paymentStatusesRes.payment_statuses : [];

        setStatuses(nextStatuses);
        setPaymentStatuses(nextPayment);

        try {
          const devicesRes = await apiFetch<{ devices: ApiDevice[] }>(
            `/api/${tenantSlug}/app/repairbuddy/devices?limit=10`,
          );
          if (!alive) return;
          setDevices(Array.isArray(devicesRes.devices) ? devicesRes.devices : []);
        } catch {
          if (!alive) return;
          setDevices([]);
        }

        try {
          const techniciansRes = await apiFetch<{ users: ApiTechnician[] }>(
            `/api/${tenantSlug}/app/technicians?per_page=100&sort=name&dir=asc`,
          );
          if (!alive) return;
          setTechnicians(Array.isArray(techniciansRes.users) ? techniciansRes.users : []);
        } catch {
          if (!alive) return;
          setTechnicians([]);
        }

        try {
          const settingsRes = await getRepairBuddySettings(String(tenantSlug));
          if (!alive) return;
          setNextServiceEnabled(Boolean((settingsRes.settings as any)?.general?.nextServiceDateEnabled));
        } catch {
          if (!alive) return;
          setNextServiceEnabled(false);
        }

        const preferredStatus =
          nextStatuses.find((s) => s.slug === "new")?.slug ?? nextStatuses.find((s) => s.slug === "neworder")?.slug;
        setStatusSlug(preferredStatus ?? (nextStatuses.length > 0 ? nextStatuses[0].slug : ""));
        setPaymentStatusSlug("");

        try {
          const clientsRes = await apiFetch<{ clients: ApiClient[] }>(`/api/${tenantSlug}/app/clients?limit=200`);
          if (!alive) return;
          setClients(Array.isArray(clientsRes.clients) ? clientsRes.clients : []);
        } catch {
          if (!alive) return;
          setClients([]);
        }

        try {
          const partsRes = await apiFetch<{ parts: ApiPart[] }>(`/api/${tenantSlug}/app/repairbuddy/parts?limit=200`);
          if (!alive) return;

          const list = Array.isArray(partsRes.parts) ? partsRes.parts : [];
          setPartsCatalog(
            list.map((p) => ({
              id: p.id,
              name: p.name,
              code: p.manufacturing_code ?? "",
              capacity: p.capacity ?? "",
            })),
          );
        } catch {
          if (!alive) return;
          setPartsCatalog([]);
        }

        try {
          const servicesRes = await apiFetch<{ services: ApiService[] }>(`/api/${tenantSlug}/app/repairbuddy/services?limit=200`);
          if (!alive) return;
          const list = Array.isArray(servicesRes.services) ? servicesRes.services : [];
          setServicesCatalog(
            list.map((s) => ({
              id: s.id,
              name: s.name,
            })),
          );
        } catch {
          if (!alive) return;
          setServicesCatalog([]);
        }
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load form data.");
        setStatuses([]);
        setPaymentStatuses([]);
        setClients([]);
        setTechnicians([]);
        setNextServiceEnabled(false);
        setDevices([]);
        setPartsCatalog([]);
        setServicesCatalog([]);
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

  useEffect(() => {
    setCaseNumber((prev) => (prev.trim() === "" ? defaultCaseNumber : prev));
  }, [defaultCaseNumber]);

  const loadDeviceOptions = async (inputValue: string): Promise<DeviceOption[]> => {
    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return [];

    try {
      const qs = new URLSearchParams();
      const q = inputValue.trim();
      qs.set("limit", q ? "50" : "10");
      if (q) qs.set("q", q);

      const res = await apiFetch<{ devices: ApiDevice[] }>(
        `/api/${tenantSlug}/app/repairbuddy/devices?${qs.toString()}`,
      );
      const list = Array.isArray(res.devices) ? res.devices : [];
      return list.map((d) => ({
        value: d.id,
        label: d.model,
      }));
    } catch {
      return [];
    }
  };

  const sortedClients = useMemo(() => {
    return clients.slice().sort((a, b) => `${a.name}`.localeCompare(`${b.name}`));
  }, [clients]);

  const clientOptions = useMemo<CustomerOption[]>(() => {
    return sortedClients.map((c) => ({
      value: c.id,
      label: c.email ? `${c.name} (${c.email})` : c.name,
    }));
  }, [sortedClients]);

  const deviceOptions = useMemo<DeviceOption[]>(() => {
    return devices
      .slice()
      .sort((a, b) => `${a.model}`.localeCompare(`${b.model}`))
      .map((d) => ({
        value: d.id,
        label: d.model,
      }));
  }, [devices]);

  const selectedJobDeviceOptions = useMemo(() => {
    const opts = jobDevicesAdmin
      .map((d) => d.option)
      .filter((x): x is DeviceOption => Boolean(x) && typeof x?.value === "number");

    const uniq = new Map<number, DeviceOption>();
    for (const o of opts) uniq.set(o.value, o);
    return Array.from(uniq.values());
  }, [jobDevicesAdmin]);

  const partOptions = useMemo<PartOption[]>(() => {
    return partsCatalog
      .slice()
      .sort((a, b) => `${a.name}`.localeCompare(`${b.name}`))
      .map((p) => ({
        value: p.id,
        label: p.name,
        code: p.code,
        capacity: p.capacity,
      }));
  }, [partsCatalog]);

  const serviceOptions = useMemo<ServiceOption[]>(() => {
    return servicesCatalog
      .slice()
      .sort((a, b) => `${a.name}`.localeCompare(`${b.name}`))
      .map((s) => ({
        value: s.id,
        label: s.name,
      }));
  }, [servicesCatalog]);

  const sortedTechnicians = useMemo(() => {
    return technicians.slice().sort((a, b) => `${a.name}`.localeCompare(`${b.name}`));
  }, [technicians]);

  const technicianOptions = useMemo<TechnicianOption[]>(() => {
    return sortedTechnicians.map((t) => ({
      value: t.id,
      label: t.email ? `${t.name} (${t.email})` : t.name,
    }));
  }, [sortedTechnicians]);

  const selectedTechnicianOptions = useMemo<TechnicianOption[]>(() => {
    if (assignedTechnicianIds.length === 0) return [];
    const set = new Set(assignedTechnicianIds);
    return technicianOptions.filter((o) => set.has(o.value));
  }, [assignedTechnicianIds, technicianOptions]);

  const selectedTechnicianOption = useMemo<TechnicianOption | null>(() => {
    if (technicianMode === "new") return technicianOption;
    return selectedTechnicianOptions.length > 0 ? selectedTechnicianOptions[0] : null;
  }, [selectedTechnicianOptions, technicianMode, technicianOption]);

  const customerDeviceOptions = useMemo<CustomerDeviceOption[]>(() => {
    return customerDevices
      .slice()
      .sort((a, b) => `${a.label}`.localeCompare(`${b.label}`))
      .map((d) => ({
        value: d.id,
        label: d.serial ? `${d.label} (Serial: ${d.serial})` : d.label,
      }));
  }, [customerDevices]);

  useEffect(() => {
    let alive = true;

    async function loadCustomerDevices() {
      if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
      if (typeof customerId !== "number") {
        setCustomerDevices([]);
        setJobDevices([]);
        setCustomerDevicesError(null);
        return;
      }

      if (!auth.can("customer_devices.view")) {
        setCustomerDevices([]);
        setJobDevices([]);
        setCustomerDevicesError("You do not have permission to view customer devices.");
        return;
      }

      try {
        const res = await apiFetch<{ customer_devices: ApiCustomerDevice[] }>(
          `/api/${tenantSlug}/app/repairbuddy/customer-devices?customer_id=${encodeURIComponent(String(customerId))}&limit=200`,
        );
        if (!alive) return;
        setCustomerDevices(Array.isArray(res.customer_devices) ? res.customer_devices : []);
        setCustomerDevicesError(null);
      } catch {
        if (!alive) return;
        setCustomerDevices([]);
        setCustomerDevicesError("Failed to load customer devices.");
      }
    }

    void loadCustomerDevices();
    return () => {
      alive = false;
    };
  }, [auth, customerId, tenantSlug]);

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

  const loadCustomerDeviceOptions = async (inputValue: string): Promise<CustomerDeviceOption[]> => {
    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return [];
    if (typeof customerId !== "number") return [];
    if (!auth.can("customer_devices.view")) return [];

    try {
      const qs = new URLSearchParams();
      qs.set("customer_id", String(customerId));
      qs.set("limit", "50");
      const q = inputValue.trim();
      if (q) qs.set("q", q);

      const res = await apiFetch<{ customer_devices: ApiCustomerDevice[] }>(
        `/api/${tenantSlug}/app/repairbuddy/customer-devices?${qs.toString()}`,
      );

      const list = Array.isArray(res.customer_devices) ? res.customer_devices : [];
      return list
        .slice()
        .sort((a, b) => `${a.label}`.localeCompare(`${b.label}`))
        .map((d) => ({
          value: d.id,
          label: d.serial ? `${d.label} (Serial: ${d.serial})` : d.label,
        }));
    } catch {
      return [];
    }
  };

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
    if (busy) return;

    if (step === 1) {
      setStep(2);
      return;
    }

    if (step === 2) {
      setStep(3);
      return;
    }

    setError(null);
    setBusy(true);

    try {
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

      const trimmedTitle = title.trim();

      const payload: Record<string, unknown> = {
        devices:
          jobDevicesAdmin.length > 0
            ? jobDevicesAdmin
                .filter((d) => typeof d.device_id === "number")
                .map((d) => ({
                device_id: d.device_id as number,
                serial: d.serial.trim() !== "" ? d.serial.trim() : null,
                pin: d.pin.trim() !== "" ? d.pin.trim() : null,
                notes: d.notes.trim() !== "" ? d.notes.trim() : null,
              }))
            : [],
        case_number: caseNumber.trim() !== "" ? caseNumber.trim() : null,
        title: trimmedTitle !== "" ? trimmedTitle : null,
        status_slug: statusSlug.trim() !== "" ? statusSlug : null,
        payment_status_slug: paymentStatusSlug.trim() !== "" ? paymentStatusSlug : null,
        priority: priority.trim() !== "" ? priority.trim() : null,
        customer_id: !shouldCreateCustomer && typeof customerId === "number" ? customerId : null,
        pickup_date: pickupDate.trim() !== "" ? pickupDate : null,
        delivery_date: deliveryDate.trim() !== "" ? deliveryDate : null,
        next_service_date: nextServiceEnabled && nextServiceDate.trim() !== "" ? nextServiceDate : null,
        case_detail: caseDetail.trim() !== "" ? caseDetail.trim() : null,
        assigned_technician_id: assignedTechnicianIds.length > 0 ? assignedTechnicianIds[0] : null,
        assigned_technician_ids: assignedTechnicianIds,
        job_devices:
          jobDevices.length > 0
            ? jobDevices.map((d) => ({
                customer_device_id: d.customer_device_id,
                serial: d.serial.trim() !== "" ? d.serial.trim() : null,
                notes: d.notes.trim() !== "" ? d.notes.trim() : null,
              }))
            : [],

        wc_order_note: orderNote.trim() !== "" ? orderNote.trim() : null,
      };

      if (shouldCreateCustomer) {
        payload.customer_create = {
          name: customerCreateName.trim(),
          email: customerCreateEmail.trim(),
          phone: customerCreatePhone.trim() !== "" ? customerCreatePhone.trim() : null,
          company: customerCreateCompany.trim() !== "" ? customerCreateCompany.trim() : null,
          address_line1: customerCreateAddressLine1.trim() !== "" ? customerCreateAddressLine1.trim() : null,
          address_line2: customerCreateAddressLine2.trim() !== "" ? customerCreateAddressLine2.trim() : null,
          address_city: customerCreateAddressCity.trim() !== "" ? customerCreateAddressCity.trim() : null,
          address_state: customerCreateAddressState.trim() !== "" ? customerCreateAddressState.trim() : null,
          address_postal_code: customerCreateAddressPostalCode.trim() !== "" ? customerCreateAddressPostalCode.trim() : null,
          address_country: customerCreateAddressCountry.trim() !== "" ? customerCreateAddressCountry.trim() : null,
        };
      }

      const form = new FormData();
      form.append("payload_json", JSON.stringify(payload));
      if (jobFile) {
        form.append("job_file", jobFile);
      }

      const res = await apiFetch<{ job: ApiJob }>(`/api/${tenantSlug}/app/repairbuddy/jobs`, {
        method: "POST",
        body: form,
      });

      const nextId = res.job?.id;
      if (typeof nextId === "number") {
        try {
          for (const line of jobServices) {
            const qtyNum = Math.round(Number(line.qty));
            const qty = Number.isFinite(qtyNum) && qtyNum > 0 ? qtyNum : 1;

            const rawPrice = line.price.trim();
            const priceNum = rawPrice.length > 0 ? Number(rawPrice) : NaN;
            const unitPriceAmountCents = Number.isFinite(priceNum) ? Math.round(priceNum * 100) : undefined;

            await apiFetch(`/api/${tenantSlug}/app/repairbuddy/jobs/${nextId}/items`, {
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

          for (const line of jobOtherItems) {
            const name = line.name.trim();
            if (name === "") continue;

            const qtyNum = Math.round(Number(line.qty));
            const qty = Number.isFinite(qtyNum) && qtyNum > 0 ? qtyNum : 1;

            const rawPrice = line.price.trim();
            const priceNum = rawPrice.length > 0 ? Number(rawPrice) : NaN;
            const unitPriceAmountCents = Number.isFinite(priceNum) ? Math.round(priceNum * 100) : undefined;
            if (typeof unitPriceAmountCents !== "number") continue;

            await apiFetch(`/api/${tenantSlug}/app/repairbuddy/jobs/${nextId}/items`, {
              method: "POST",
              body: {
                item_type: unitPriceAmountCents < 0 ? "discount" : "fee",
                name,
                qty,
                unit_price_amount_cents: unitPriceAmountCents,
              },
            });
          }

          for (const line of jobParts) {
            const qtyNum = Math.round(Number(line.qty));
            const qty = Number.isFinite(qtyNum) && qtyNum > 0 ? qtyNum : 1;

            const rawPrice = line.price.trim();
            const priceNum = rawPrice.length > 0 ? Number(rawPrice) : NaN;
            const unitPriceAmountCents = Number.isFinite(priceNum) ? Math.round(priceNum * 100) : undefined;

            await apiFetch(`/api/${tenantSlug}/app/repairbuddy/jobs/${nextId}/items`, {
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
          notify.error(err instanceof ApiError ? err.message : "Job created, but failed to attach items.");
        }

        notify.success("Job created.");
        router.replace(`/app/${tenantSlug}/jobs/${nextId}`);
      } else {
        notify.success("Job created.");
        router.replace(`/app/${tenantSlug}/jobs`);
      }
    } catch (e) {
      if (e instanceof ApiError) {
        setError(e.message);
      } else {
        setError("Failed to create job.");
      }
    } finally {
      setBusy(false);
    }
  }

  const disabled = busy || loadingLookups;
  const isStep1 = step === 1;
  const isStep2 = step === 2;
  const isStep3 = step === 3;
  const stepIndex = step === 1 ? 0 : step === 2 ? 1 : 2;
  const progress = stepIndex / 2;

  return (
    <RequireAuth requiredPermission="jobs.view">
      <div className="space-y-6">
        <PageHeader
          title="New job"
          description="Create a new repair job."
          actions={
            <>
              <Button
                variant="outline"
                size="sm"
                onClick={() => {
                  if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
                  router.push(`/app/${tenantSlug}/jobs`);
                }}
                disabled={busy}
              >
                Cancel
              </Button>
            </>
          }
        />

        {error ? <div className="text-sm text-red-600">{error}</div> : null}

        <Modal
          open={customerCreateOpen}
          title="Add customer"
          onClose={() => {
            setCustomerCreateError(null);
            setCustomerCreateOpen(false);
          }}
          className="max-w-3xl"
          footer={
            <div className="flex items-center justify-end gap-2">
              <Button
                variant="outline"
                onClick={() => {
                  setCustomerCreateError(null);
                  setCustomerCreateOpen(false);
                }}
              >
                Cancel
              </Button>
              <Button
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
                  setJobDevices([]);

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
                <Input value={customerCreateEmail} onChange={(e) => setCustomerCreateEmail(e.target.value)} />
              </div>
              <div>
                <div className="mb-1 text-xs text-zinc-600">Phone</div>
                <Input value={customerCreatePhone} onChange={(e) => setCustomerCreatePhone(e.target.value)} />
              </div>
              <div>
                <div className="mb-1 text-xs text-zinc-600">Company</div>
                <Input value={customerCreateCompany} onChange={(e) => setCustomerCreateCompany(e.target.value)} />
              </div>

              <div className="md:col-span-2">
                <div className="mb-1 text-xs text-zinc-600">Address line 1</div>
                <Input value={customerCreateAddressLine1} onChange={(e) => setCustomerCreateAddressLine1(e.target.value)} />
              </div>
              <div className="md:col-span-2">
                <div className="mb-1 text-xs text-zinc-600">Address line 2</div>
                <Input value={customerCreateAddressLine2} onChange={(e) => setCustomerCreateAddressLine2(e.target.value)} />
              </div>
              <div>
                <div className="mb-1 text-xs text-zinc-600">City</div>
                <Input value={customerCreateAddressCity} onChange={(e) => setCustomerCreateAddressCity(e.target.value)} />
              </div>
              <div>
                <div className="mb-1 text-xs text-zinc-600">State</div>
                <Input value={customerCreateAddressState} onChange={(e) => setCustomerCreateAddressState(e.target.value)} />
              </div>
              <div>
                <div className="mb-1 text-xs text-zinc-600">Postal code</div>
                <Input
                  value={customerCreateAddressPostalCode}
                  onChange={(e) => setCustomerCreateAddressPostalCode(e.target.value)}
                />
              </div>
              <div>
                <div className="mb-1 text-xs text-zinc-600">Country (2-letter)</div>
                <Input value={customerCreateAddressCountry} onChange={(e) => setCustomerCreateAddressCountry(e.target.value)} placeholder="US" />
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
        className="max-w-3xl"
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
              variant="primary"
              disabled={disabled}
              onClick={() => {
                if (!servicePickerService) {
                  setServicePickerError("Please select a service.");
                  return;
                }
                if (!servicePickerDevice) {
                  setServicePickerError("Please select a device.");
                  return;
                }

                const service = servicesCatalog.find((s) => s.id === servicePickerService.value);
                if (!service) {
                  setServicePickerError("Selected service not found.");
                  return;
                }

                const anyCrypto = globalThis.crypto as any;
                const id =
                  typeof anyCrypto?.randomUUID === "function"
                    ? anyCrypto.randomUUID()
                    : `js-${Date.now()}-${Math.random().toString(16).slice(2)}`;

                setJobServices((prev) => [
                  {
                    id,
                    service_id: service.id,
                    service,
                    device_id: servicePickerDevice.value,
                    qty: servicePickerQty,
                    price: servicePickerPrice,
                  },
                  ...prev,
                ]);

                setServicePickerError(null);
                setServicePickerQty("1");
                setServicePickerPrice("");
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
                instanceId="job_service_select"
                inputId="job_service_select"
                isSearchable
                isClearable
                options={serviceOptions}
                value={servicePickerService}
                onChange={(opt) => {
                  setServicePickerError(null);
                  setServicePickerService((opt as ServiceOption | null) ?? null);
                }}
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

            <div>
              <div className="mb-1 text-xs text-zinc-600">Device</div>
              <Select
                instanceId="job_service_device_select"
                inputId="job_service_device_select"
                isSearchable
                isClearable
                options={selectedJobDeviceOptions}
                value={servicePickerDevice}
                onChange={(opt) => {
                  setServicePickerError(null);
                  setServicePickerDevice((opt as DeviceOption | null) ?? null);
                }}
                isDisabled={disabled}
                placeholder={selectedJobDeviceOptions.length > 0 ? "Select device..." : "No devices selected"}
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
              <Input
                type="number"
                min={0}
                value={servicePickerQty}
                disabled={disabled}
                onChange={(e) => {
                  setServicePickerError(null);
                  setServicePickerQty(e.target.value);
                }}
              />
            </div>

            <div className="md:col-span-2">
              <div className="mb-1 text-xs text-zinc-600">Price (optional override)</div>
              <Input
                type="number"
                min={0}
                step="0.01"
                value={servicePickerPrice}
                disabled={disabled}
                onChange={(e) => {
                  setServicePickerError(null);
                  setServicePickerPrice(e.target.value);
                }}
              />
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
        className="max-w-3xl"
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
              variant="primary"
              disabled={disabled}
              onClick={() => {
                if (!partPickerPart) {
                  setPartPickerError("Please select a part.");
                  return;
                }
                if (!partPickerDevice) {
                  setPartPickerError("Please select a device.");
                  return;
                }

                const part = partsCatalog.find((p) => p.id === partPickerPart.value);
                if (!part) {
                  setPartPickerError("Selected part not found.");
                  return;
                }

                const anyCrypto = globalThis.crypto as any;
                const id =
                  typeof anyCrypto?.randomUUID === "function"
                    ? anyCrypto.randomUUID()
                    : `jp-${Date.now()}-${Math.random().toString(16).slice(2)}`;

                setJobParts((prev) => [
                  {
                    id,
                    part_id: part.id,
                    part,
                    device_id: partPickerDevice.value,
                    qty: partPickerQty,
                    price: partPickerPrice,
                  },
                  ...prev,
                ]);

                setPartPickerError(null);
                setPartPickerQty("1");
                setPartPickerPrice("");
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
                instanceId="job_part_select"
                isSearchable
                isClearable
                options={partOptions}
                value={partPickerPart}
                onChange={(opt) => {
                  setPartPickerError(null);
                  setPartPickerPart((opt as PartOption | null) ?? null);
                }}
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
                instanceId="job_part_device"
                isSearchable
                isClearable
                options={selectedJobDeviceOptions}
                value={partPickerDevice}
                onChange={(opt) => {
                  setPartPickerError(null);
                  setPartPickerDevice((opt as DeviceOption | null) ?? null);
                }}
                isDisabled={disabled}
                placeholder={selectedJobDeviceOptions.length > 0 ? "Select device..." : "No devices selected"}
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
              <Input
                type="number"
                min={0}
                value={partPickerQty}
                disabled={disabled}
                onChange={(e) => {
                  setPartPickerError(null);
                  setPartPickerQty(e.target.value);
                }}
              />
            </div>

            <div>
              <div className="mb-1 text-xs text-zinc-600">Price</div>
              <Input
                type="number"
                min={0}
                step="0.01"
                value={partPickerPrice}
                disabled={disabled}
                onChange={(e) => {
                  setPartPickerError(null);
                  setPartPickerPrice(e.target.value);
                }}
              />
            </div>
          </div>
        </div>
      </Modal>

      <Modal
        open={createServiceModalOpen}
        title="Create new service"
        onClose={() => {
          setCreateServiceError(null);
          setCreateServiceModalOpen(false);
        }}
        className="max-w-2xl"
        footer={
          <div className="flex items-center justify-end gap-2">
            <Button
              type="button"
              variant="outline"
              onClick={() => {
                setCreateServiceError(null);
                setCreateServiceModalOpen(false);
              }}
            >
              Cancel
            </Button>
            <Button
              type="button"
              variant="primary"
              disabled={disabled}
              onClick={async () => {
                if (busy) return;
                if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;

                const name = createServiceName.trim();
                if (name.length === 0) {
                  setCreateServiceError("Service name is required.");
                  return;
                }

                const rawPrice = createServiceBasePrice.trim();
                const priceNum = rawPrice.length > 0 ? Number(rawPrice) : NaN;
                const basePriceAmountCents = Number.isFinite(priceNum) ? Math.round(priceNum * 100) : null;
                if (rawPrice.length > 0 && basePriceAmountCents === null) {
                  setCreateServiceError("Base price is invalid.");
                  return;
                }

                setBusy(true);
                setCreateServiceError(null);

                try {
                  const res = await apiFetch<{ service: { id: number; name: string } }>(`/api/${tenantSlug}/app/repairbuddy/services`, {
                    method: "POST",
                    body: {
                      name,
                      ...(typeof basePriceAmountCents === "number" ? { base_price_amount_cents: basePriceAmountCents } : {}),
                    },
                  });

                  const service = res.service;
                  if (!service || typeof service.id !== "number") {
                    setCreateServiceError("Failed to create service.");
                    return;
                  }

                  const draft: ServiceDraft = { id: service.id, name: service.name };
                  setServicesCatalog((prev) => [draft, ...prev.filter((s) => s.id !== draft.id)]);
                  setServicePickerService({ value: draft.id, label: draft.name });

                  setCreateServiceName("");
                  setCreateServiceBasePrice("");
                  setCreateServiceModalOpen(false);
                  notify.success("Service created.");
                } catch (err) {
                  if (err instanceof ApiError) {
                    setCreateServiceError(err.message);
                  } else {
                    setCreateServiceError("Failed to create service.");
                  }
                } finally {
                  setBusy(false);
                }
              }}
            >
              Save
            </Button>
          </div>
        }
      >
        <div className="space-y-4">
          {createServiceError ? <div className="text-sm text-red-600">{createServiceError}</div> : null}

          <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
            <div className="md:col-span-2">
              <div className="mb-1 text-xs text-zinc-600">Name</div>
              <Input value={createServiceName} onChange={(e) => setCreateServiceName(e.target.value)} disabled={disabled} />
            </div>
            <div>
              <div className="mb-1 text-xs text-zinc-600">Base price</div>
              <Input
                type="number"
                min={0}
                step="0.01"
                value={createServiceBasePrice}
                onChange={(e) => setCreateServiceBasePrice(e.target.value)}
                disabled={disabled}
              />
            </div>
          </div>
        </div>
      </Modal>

      <Modal
        open={createPartModalOpen}
        title="Create new part"
        onClose={() => {
          setCreatePartError(null);
          setCreatePartModalOpen(false);
        }}
        className="max-w-2xl"
        footer={
          <div className="flex items-center justify-end gap-2">
            <Button
              type="button"
              variant="outline"
              onClick={() => {
                setCreatePartError(null);
                setCreatePartModalOpen(false);
              }}
            >
              Cancel
            </Button>
            <Button
              type="button"
              variant="primary"
              disabled={disabled}
              onClick={async () => {
                if (busy) return;
                if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;

                const name = createPartName.trim();
                if (name.length === 0) {
                  setCreatePartError("Part name is required.");
                  return;
                }

                setBusy(true);
                setCreatePartError(null);

                try {
                  const res = await apiFetch<{ part: { id: number; name: string; manufacturing_code: string | null; capacity: string | null } }>(
                    `/api/${tenantSlug}/app/repairbuddy/parts`,
                    {
                      method: "POST",
                      body: {
                        name,
                        manufacturing_code: createPartCode.trim() !== "" ? createPartCode.trim() : null,
                        capacity: createPartCapacity.trim() !== "" ? createPartCapacity.trim() : null,
                      },
                    },
                  );

                  const part = res.part;
                  if (!part || typeof part.id !== "number") {
                    setCreatePartError("Failed to create part.");
                    return;
                  }

                  const draft: PartDraft = {
                    id: part.id,
                    name: part.name,
                    code: part.manufacturing_code ?? "",
                    capacity: part.capacity ?? "",
                  };

                  setPartsCatalog((prev) => [draft, ...prev.filter((p) => p.id !== draft.id)]);
                  setPartPickerPart({ value: draft.id, label: draft.name, code: draft.code, capacity: draft.capacity });

                  setCreatePartName("");
                  setCreatePartCode("");
                  setCreatePartCapacity("");
                  setCreatePartModalOpen(false);
                  notify.success("Part created.");
                } catch (err) {
                  if (err instanceof ApiError) {
                    setCreatePartError(err.message);
                  } else {
                    setCreatePartError("Failed to create part.");
                  }
                } finally {
                  setBusy(false);
                }
              }}
            >
              Save
            </Button>
          </div>
        }
      >
        <div className="space-y-4">
          {createPartError ? <div className="text-sm text-red-600">{createPartError}</div> : null}

          <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
            <div className="md:col-span-2">
              <div className="mb-1 text-xs text-zinc-600">Name</div>
              <Input value={createPartName} onChange={(e) => setCreatePartName(e.target.value)} />
            </div>
            <div>
              <div className="mb-1 text-xs text-zinc-600">Code</div>
              <Input value={createPartCode} onChange={(e) => setCreatePartCode(e.target.value)} />
            </div>
            <div>
              <div className="mb-1 text-xs text-zinc-600">Capacity</div>
              <Input value={createPartCapacity} onChange={(e) => setCreatePartCapacity(e.target.value)} />
            </div>
          </div>
        </div>
      </Modal>

      <Modal
        open={extraFieldModalOpen}
        title="Add extra field"
        onClose={() => {
          setExtraFieldError(null);
          setExtraFieldModalOpen(false);
        }}
        className="max-w-3xl"
        footer={
          <div className="flex items-center justify-end gap-2">
            <Button
              variant="outline"
              onClick={() => {
                setExtraFieldError(null);
                setExtraFieldModalOpen(false);
              }}
            >
              Cancel
            </Button>
            <Button
              onClick={() => {
                const label = extraFieldLabel.trim();
                const data = extraFieldData.trim();
                if (label === "") {
                  setExtraFieldError("Field label is required.");
                  return;
                }
                if (data === "") {
                  setExtraFieldError("Field data is required.");
                  return;
                }

                setExtraFields((prev) => [
                  ...prev,
                  {
                    id: String(Date.now()),
                    datetime: extraFieldDatetime,
                    label,
                    data,
                    description: extraFieldDescription.trim(),
                    visibility: extraFieldVisibility,
                    file: extraFieldFile,
                  },
                ]);

                setExtraFieldError(null);
                setExtraFieldDatetime("");
                setExtraFieldLabel("");
                setExtraFieldData("");
                setExtraFieldDescription("");
                setExtraFieldVisibility("internal");
                setExtraFieldFile(null);
                setExtraFieldModalOpen(false);
              }}
            >
              Save
            </Button>
          </div>
        }
      >
        <div className="space-y-4">
          {extraFieldError ? <div className="text-sm text-red-600">{extraFieldError}</div> : null}

          <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
            <div>
              <div className="mb-1 text-xs text-zinc-600">Date & time</div>
              <Input type="datetime-local" value={extraFieldDatetime} onChange={(e) => setExtraFieldDatetime(e.target.value)} />
            </div>

            <div>
              <div className="mb-1 text-xs text-zinc-600">Visibility</div>
              <select
                className="w-full rounded-[var(--rb-radius-sm)] border border-zinc-300 bg-white px-3 py-2 text-sm text-[var(--rb-text)]"
                value={extraFieldVisibility}
                onChange={(e) => setExtraFieldVisibility(e.target.value as ExtraJobFieldVisibility)}
              >
                <option value="internal">Internal</option>
                <option value="customer">Customer</option>
              </select>
            </div>

            <div className="md:col-span-2">
              <div className="mb-1 text-xs text-zinc-600">Field label</div>
              <Input value={extraFieldLabel} onChange={(e) => setExtraFieldLabel(e.target.value)} />
            </div>

            <div className="md:col-span-2">
              <div className="mb-1 text-xs text-zinc-600">Field data</div>
              <textarea
                value={extraFieldData}
                onChange={(e) => setExtraFieldData(e.target.value)}
                rows={3}
                className="w-full rounded-[var(--rb-radius-sm)] border border-zinc-300 bg-white px-3 py-2 text-sm text-[var(--rb-text)]"
              />
            </div>

            <div className="md:col-span-2">
              <div className="mb-1 text-xs text-zinc-600">Field description</div>
              <textarea
                value={extraFieldDescription}
                onChange={(e) => setExtraFieldDescription(e.target.value)}
                rows={2}
                className="w-full rounded-[var(--rb-radius-sm)] border border-zinc-300 bg-white px-3 py-2 text-sm text-[var(--rb-text)]"
              />
            </div>

            <div className="md:col-span-2">
              <div className="mb-1 text-xs text-zinc-600">File</div>
              <Input
                type="file"
                onChange={(e) => {
                  const next = e.target.files?.[0] ?? null;
                  setExtraFieldFile(next);
                }}
              />
            </div>
          </div>
        </div>
      </Modal>

        <Modal
          open={technicianCreateOpen}
          title="Add technician"
          onClose={() => {
            setTechnicianCreateError(null);
            setTechnicianCreateOpen(false);
          }}
          footer={
            <div className="flex items-center justify-end gap-2">
              <Button
                variant="outline"
                onClick={() => {
                  setTechnicianCreateError(null);
                  setTechnicianCreateOpen(false);
                }}
              >
                Cancel
              </Button>
              <Button
                onClick={() => {
                  const name = technicianCreateName.trim();
                  const email = technicianCreateEmail.trim();
                  if (name === "") {
                    setTechnicianCreateError("Technician name is required.");
                    return;
                  }
                  if (email === "") {
                    setTechnicianCreateError("Technician email is required.");
                    return;
                  }

                  setTechnicianCreateError(null);
                  setTechnicianMode("new");
                  setTechnicianOption({ value: -1, label: `${name} (${email})` });
                  setAssignedTechnicianIds([]);
                  setTechnicianCreateOpen(false);
                }}
              >
                Save
              </Button>
            </div>
          }
        >
          <div className="space-y-4">
            {technicianCreateError ? <div className="text-sm text-red-600">{technicianCreateError}</div> : null}
            <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
              <div>
                <div className="mb-1 text-xs text-zinc-600">Name</div>
                <Input value={technicianCreateName} onChange={(e) => setTechnicianCreateName(e.target.value)} />
              </div>
              <div>
                <div className="mb-1 text-xs text-zinc-600">Email</div>
                <Input value={technicianCreateEmail} onChange={(e) => setTechnicianCreateEmail(e.target.value)} />
              </div>
            </div>
          </div>
        </Modal>

        <form id="rb_job_new_form" className="space-y-6" onSubmit={onSubmit}>
          <div className="grid gap-6 lg:grid-cols-[260px_1fr]">
            <Card className="shadow-none lg:sticky lg:top-6 lg:self-start">
              <CardHeader className="pb-3">
                <CardTitle className="text-base">Job steps</CardTitle>
                <CardDescription>Complete the steps to create the job.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="h-2 w-full overflow-hidden rounded-full bg-[var(--rb-border)]">
                  <div
                    className="h-full bg-[linear-gradient(90deg,var(--rb-blue),var(--rb-orange))]"
                    style={{ width: `${Math.round(progress * 100)}%` }}
                  />
                </div>

                <nav aria-label="Job steps" className="space-y-1">
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
                              {s === 1 ? "Customer & devices" : s === 2 ? "Job Items and services" : "Order information"}
                            </div>
                            <div className="mt-0.5 line-clamp-2 text-xs text-zinc-600">
                              {s === 1
                                ? "Case, dates, customer, technician, description, devices."
                                : s === 2
                                  ? "Attach extra fields and files for this job."
                                  : "Status, payment, priority, notes."}
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
                    <CardTitle className="text-base">
                      {isStep1 ? "Customer & devices" : isStep2 ? "Job Items and services" : "Order information"}
                    </CardTitle>
                    <CardDescription>
                      {isStep1
                        ? "Enter the customer, technician, description and devices."
                        : isStep2
                          ? "Attach extra fields and files for the job."
                          : "Finalize status, payment, priority and notes."}
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
                    <FormRow label="Case number" fieldId="job_case_number">
                      <Input
                        id="job_case_number"
                        value={caseNumber}
                        onChange={(e) => setCaseNumber(e.target.value)}
                        disabled={disabled}
                      />
                    </FormRow>

                    <FormRow label="Dates" fieldId="job_dates">
                      <div
                        className={
                          nextServiceEnabled
                            ? "grid grid-cols-1 gap-3 md:grid-cols-3"
                            : "grid grid-cols-1 gap-3 md:grid-cols-2"
                        }
                      >
                        <div>
                          <div className="mb-1 text-xs text-zinc-600">Pickup date</div>
                          <Input
                            id="job_pickup_date"
                            type="date"
                            value={pickupDate}
                            onChange={(e) => setPickupDate(e.target.value)}
                            disabled={disabled}
                          />
                        </div>

                        <div>
                          <div className="mb-1 text-xs text-zinc-600">Delivery date</div>
                          <Input
                            id="job_delivery_date"
                            type="date"
                            value={deliveryDate}
                            onChange={(e) => setDeliveryDate(e.target.value)}
                            disabled={disabled}
                          />
                        </div>

                        {nextServiceEnabled ? (
                          <div>
                            <div className="mb-1 text-xs text-zinc-600">Next service date</div>
                            <Input
                              id="job_next_service_date"
                              type="date"
                              value={nextServiceDate}
                              onChange={(e) => setNextServiceDate(e.target.value)}
                              disabled={disabled}
                            />
                          </div>
                        ) : null}
                      </div>
                    </FormRow>

                    <FormRow label="Customer" fieldId="job_customer">
                      <div className="space-y-2">
                        <div className="flex items-start gap-2">
                          <div className="min-w-0 flex-1">
                            <AsyncSelect
                              inputId="job_customer"
                              instanceId="job_customer"
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
                                setJobDevices([]);
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

                        {customerMode === "new" ? (
                          <div className="text-xs text-zinc-600">This job will create a new customer.</div>
                        ) : null}
                      </div>
                    </FormRow>

                    <FormRow label="Technician" fieldId="job_technician">
                      <div className="space-y-2">
                        <div className="flex items-start gap-2">
                          <div className="min-w-0 flex-1">
                            <Select
                              inputId="job_technician"
                              instanceId="job_technician"
                              isSearchable
                              isClearable
                              options={technicianOptions}
                              value={selectedTechnicianOption}
                              onChange={(opt) => {
                                const next = (opt as TechnicianOption | null) ?? null;
                                setTechnicianMode("existing");
                                setTechnicianOption(null);
                                setAssignedTechnicianIds(typeof next?.value === "number" && next.value > 0 ? [next.value] : []);
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
                          </div>

                          <Button
                            type="button"
                            variant="primary"
                            size="md"
                            className="w-10 px-0"
                            disabled={disabled}
                            onClick={() => {
                              setTechnicianCreateError(null);
                              setTechnicianCreateOpen(true);
                            }}
                          >
                            +
                          </Button>
                        </div>

                        {technicianMode === "new" ? (
                          <div className="text-xs text-zinc-600">This job will create a new technician.</div>
                        ) : null}
                      </div>
                    </FormRow>

                    <FormRow label="Job description" fieldId="job_case_detail">
                      <textarea
                        id="job_case_detail"
                        value={caseDetail}
                        onChange={(e) => setCaseDetail(e.target.value)}
                        disabled={disabled}
                        rows={4}
                        placeholder="Enter job description."
                        className="w-full rounded-[var(--rb-radius-sm)] border border-zinc-300 bg-white px-3 py-2 text-sm text-[var(--rb-text)]"
                      />
                    </FormRow>

                    <FormRow label="Add devices to this job" fieldId="job_devices_admin">
                      <div className="space-y-4">
                        <div className="rounded-[var(--rb-radius-sm)] border border-dashed border-zinc-300 bg-white p-3">
                          <div className="grid grid-cols-1 gap-3">
                            {jobDevicesAdmin.map((d, idx) => (
                              <div key={idx} className="grid grid-cols-1 gap-3 md:grid-cols-[1fr_1fr_1fr_auto] md:items-end">
                                <div>
                                  <div className="mb-1 text-xs text-zinc-600">Device</div>
                                  <AsyncSelect
                                    inputId={`job_device_${idx}`}
                                    instanceId={`job_device_${idx}`}
                                    cacheOptions
                                    defaultOptions={deviceOptions}
                                    loadOptions={loadDeviceOptions}
                                    isClearable
                                    isSearchable
                                    value={d.option}
                                    onChange={(opt) => {
                                      const next = (opt as DeviceOption | null) ?? null;
                                      setJobDevicesAdmin((prev) =>
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
                                  <div className="mb-1 text-xs text-zinc-600">Device ID / IMEI</div>
                                  <Input
                                    value={d.serial}
                                    onChange={(e) => {
                                      const v = e.target.value;
                                      setJobDevicesAdmin((prev) => prev.map((x, i) => (i === idx ? { ...x, serial: v } : x)));
                                    }}
                                    disabled={disabled}
                                  />
                                </div>

                                <div>
                                  <div className="mb-1 text-xs text-zinc-600">Device note</div>
                                  <Input
                                    value={d.notes}
                                    onChange={(e) => {
                                      const v = e.target.value;
                                      setJobDevicesAdmin((prev) => prev.map((x, i) => (i === idx ? { ...x, notes: v } : x)));
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
                                      setJobDevicesAdmin((prev) => prev.filter((_, i) => i !== idx));
                                    }}
                                    onKeyDown={(e) => {
                                      if (disabled) return;
                                      if (e.key === "Enter" || e.key === " ") {
                                        e.preventDefault();
                                        setJobDevicesAdmin((prev) => prev.filter((_, i) => i !== idx));
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
                              setJobDevicesAdmin((prev) => [
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
                            disabled={disabled}
                            onClick={() => {
                              setServicePickerError(null);
                              if (!servicePickerDevice && selectedJobDeviceOptions.length > 0) {
                                setServicePickerDevice(selectedJobDeviceOptions[0]);
                              }
                              setServicePickerModalOpen(true);
                            }}
                          >
                            Add service
                          </Button>
                          <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled={disabled}
                            onClick={() => {
                              setCreateServiceError(null);
                              setCreateServiceModalOpen(true);
                            }}
                          >
                            Create new service
                          </Button>
                        </div>
                      </div>
                    </div>

                    {jobServices.length > 0 ? (
                      <div className="space-y-2">
                        {jobServices.map((line) => {
                          const deviceLabel =
                            typeof line.device_id === "number"
                              ? selectedJobDeviceOptions.find((o) => o.value === line.device_id)?.label ?? `Device #${line.device_id}`
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
                                <Button
                                  type="button"
                                  variant="outline"
                                  size="sm"
                                  disabled={disabled}
                                  onClick={() => setJobServices((prev) => prev.filter((x) => x.id !== line.id))}
                                >
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
                            disabled={disabled}
                            onClick={() => {
                              setPartPickerError(null);
                              if (!partPickerDevice && selectedJobDeviceOptions.length > 0) {
                                setPartPickerDevice(selectedJobDeviceOptions[0]);
                              }
                              setPartPickerModalOpen(true);
                            }}
                          >
                            Add part
                          </Button>
                          <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled={disabled}
                            onClick={() => {
                              setCreatePartError(null);
                              setCreatePartModalOpen(true);
                            }}
                          >
                            Create new part
                          </Button>
                        </div>
                      </div>
                    </div>

                    {jobParts.length > 0 ? (
                      <div className="space-y-2">
                        {jobParts.map((line) => {
                          const deviceLabel =
                            typeof line.device_id === "number"
                              ? selectedJobDeviceOptions.find((o) => o.value === line.device_id)?.label ?? `Device #${line.device_id}`
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
                                <Button
                                  type="button"
                                  variant="outline"
                                  size="sm"
                                  disabled={disabled}
                                  onClick={() => setJobParts((prev) => prev.filter((x) => x.id !== line.id))}
                                >
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
                          <div className="mt-1 text-xs text-zinc-600">Add custom line items like rent, used cable, etc.</div>
                        </div>
                        <div>
                          <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled={disabled}
                            onClick={() => {
                              const id = typeof crypto !== "undefined" && "randomUUID" in crypto ? crypto.randomUUID() : `oi-${Date.now()}-${Math.random().toString(16).slice(2)}`;
                              setJobOtherItems((prev) => [
                                ...prev,
                                {
                                  id,
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

                    {jobOtherItems.length > 0 ? (
                      <div className="space-y-2">
                        {jobOtherItems.map((line) => {
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
                                      setJobOtherItems((prev) => prev.map((x) => (x.id === line.id ? { ...x, name: v } : x)));
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
                                      setJobOtherItems((prev) => prev.map((x) => (x.id === line.id ? { ...x, qty: v } : x)));
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
                                      setJobOtherItems((prev) => prev.map((x) => (x.id === line.id ? { ...x, price: v } : x)));
                                    }}
                                    disabled={disabled}
                                    placeholder="e.g. 10.00 (use -10.00 for discount)"
                                  />
                                </div>
                              </div>

                              <div className="flex items-center justify-between gap-2">
                                <div className="text-xs text-zinc-600">Total: {Number.isFinite(total) ? total.toFixed(2) : "0.00"}</div>
                                <Button
                                  type="button"
                                  variant="outline"
                                  size="sm"
                                  disabled={disabled}
                                  onClick={() => setJobOtherItems((prev) => prev.filter((x) => x.id !== line.id))}
                                >
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

                    <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white px-4 py-3">
                      <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                          <div className="text-sm font-semibold text-[var(--rb-text)]">Attach Fields & Files</div>
                          <div className="mt-1 text-xs text-zinc-600">Add extra fields and optionally attach a file.</div>
                        </div>
                        <div>
                          <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled={disabled}
                            onClick={() => {
                              setExtraFieldError(null);
                              setExtraFieldModalOpen(true);
                            }}
                          >
                            Add extra field
                          </Button>
                        </div>
                      </div>
                    </div>

                    {extraFields.length > 0 ? (
                      <div className="space-y-2">
                        {extraFields.map((f) => (
                          <div
                            key={f.id}
                            className="flex flex-col gap-2 rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                          >
                            <div className="min-w-0">
                              <div className="truncate text-sm font-semibold text-[var(--rb-text)]">{f.label}</div>
                              <div className="mt-1 text-xs text-zinc-600">
                                {f.datetime ? f.datetime : "—"}
                                <span className="mx-2">•</span>
                                {f.visibility}
                                {f.file ? (
                                  <>
                                    <span className="mx-2">•</span>
                                    {f.file.name}
                                  </>
                                ) : null}
                              </div>
                              {f.description ? <div className="mt-1 text-xs text-zinc-500">{f.description}</div> : null}
                            </div>

                            <div className="flex items-center gap-2">
                              <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                disabled={disabled}
                                onClick={() => {
                                  setExtraFields((prev) => prev.filter((x) => x.id !== f.id));
                                }}
                              >
                                Remove
                              </Button>
                            </div>
                          </div>
                        ))}
                      </div>
                    ) : (
                      <div className="text-sm text-zinc-600">No extra fields added yet.</div>
                    )}
                  </div>
                ) : (
                  <div className="space-y-5">
                    <FormRow label="Status" fieldId="job_status" required>
                      <select
                        id="job_status"
                        className="w-full rounded-[var(--rb-radius-sm)] border border-zinc-300 bg-white px-3 py-2 text-sm text-[var(--rb-text)]"
                        value={statusSlug}
                        onChange={(e) => setStatusSlug(e.target.value)}
                        disabled={disabled || statuses.length === 0}
                      >
                        {statuses.map((s) => (
                          <option key={s.slug} value={s.slug}>
                            {s.label}
                          </option>
                        ))}
                      </select>
                    </FormRow>

                    <FormRow label="Payment status" fieldId="job_payment">
                      <select
                        id="job_payment"
                        className="w-full rounded-[var(--rb-radius-sm)] border border-zinc-300 bg-white px-3 py-2 text-sm text-[var(--rb-text)]"
                        value={paymentStatusSlug}
                        onChange={(e) => setPaymentStatusSlug(e.target.value)}
                        disabled={disabled || paymentStatuses.length === 0}
                      >
                        <option value="">—</option>
                        {paymentStatuses.map((s) => (
                          <option key={s.slug} value={s.slug}>
                            {s.label}
                          </option>
                        ))}
                      </select>
                    </FormRow>

                    <FormRow label="Priority" fieldId="job_priority">
                      <select
                        id="job_priority"
                        className="w-full rounded-[var(--rb-radius-sm)] border border-zinc-300 bg-white px-3 py-2 text-sm text-[var(--rb-text)]"
                        value={priority}
                        onChange={(e) => setPriority(e.target.value)}
                        disabled={disabled}
                      >
                        <option value="">—</option>
                        <option value="low">Low</option>
                        <option value="normal">Normal</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                      </select>
                    </FormRow>

                    <FormRow label="Order notes" fieldId="job_order_note" description="Visible to customer.">
                      <textarea
                        id="job_order_note"
                        value={orderNote}
                        onChange={(e) => setOrderNote(e.target.value)}
                        disabled={disabled}
                        rows={3}
                        placeholder="Add a note for the customer..."
                        className="w-full rounded-[var(--rb-radius-sm)] border border-zinc-300 bg-white px-3 py-2 text-sm text-[var(--rb-text)]"
                      />
                    </FormRow>
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
                      {isStep1 ? "Customer & devices" : isStep2 ? "Job Items and services" : "Order information"}
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
                      <Button variant="primary" disabled={disabled} type="submit">
                        {busy ? "Saving..." : "Save"}
                      </Button>
                    )}
                  </div>
                </div>
              </div>
            </Card>
          </div>
        </form>
      </div>
    </RequireAuth>
  );
}
