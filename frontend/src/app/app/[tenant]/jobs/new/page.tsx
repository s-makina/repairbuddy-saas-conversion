"use client";

import React, { useEffect, useMemo, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import Select from "react-select";
import AsyncSelect from "react-select/async";
import { RequireAuth } from "@/components/RequireAuth";
import { useAuth } from "@/lib/auth";
import { PageHeader } from "@/components/ui/PageHeader";
import { Button } from "@/components/ui/Button";
import { Input } from "@/components/ui/Input";
import { FormRow } from "@/components/ui/FormRow";
import { Modal } from "@/components/ui/Modal";
import { apiFetch, ApiError } from "@/lib/api";
import { notify } from "@/lib/notify";
import { getRepairBuddySettings } from "@/lib/repairbuddy-settings";
import { WizardShell } from "@/components/repairbuddy/wizard/WizardShell";
import { DevicesAdminEditor, type WizardAdditionalDeviceField } from "@/components/repairbuddy/wizard/DevicesAdminEditor";
import { CustomerCreateModal } from "@/components/repairbuddy/wizard/CustomerCreateModal";
import { ItemsStep } from "@/components/repairbuddy/wizard/ItemsStep";

type ApiDevice = {
  id: number;
  model: string;
};

type ApiServiceType = {
  id: number;
  name: string;
};

type ApiPartType = {
  id: number;
  name: string;
};

type ApiPartBrand = {
  id: number;
  name: string;
};

type ApiBranch = {
  id: number;
  name: string;
  code?: string;
  is_active?: boolean;
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
  extra_fields: Array<{ key: string; label: string; value_text: string }>;
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
      extra_fields: [],
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
  const [serviceTypesCatalog, setServiceTypesCatalog] = useState<ApiServiceType[]>([]);
  const [partTypesCatalog, setPartTypesCatalog] = useState<ApiPartType[]>([]);
  const [partBrandsCatalog, setPartBrandsCatalog] = useState<ApiPartBrand[]>([]);
  const [createPartModalOpen, setCreatePartModalOpen] = useState(false);
  const [createPartError, setCreatePartError] = useState<string | null>(null);
  const [createPartName, setCreatePartName] = useState("");
  const [createPartPrice, setCreatePartPrice] = useState("");
  const [createPartBrandId, setCreatePartBrandId] = useState<string>("");
  const [createPartTypeId, setCreatePartTypeId] = useState<string>("");
  const [createPartManufacturingCode, setCreatePartManufacturingCode] = useState("");
  const [createPartStockCode, setCreatePartStockCode] = useState("");

  const [createServiceModalOpen, setCreateServiceModalOpen] = useState(false);
  const [createServiceError, setCreateServiceError] = useState<string | null>(null);
  const [createServiceName, setCreateServiceName] = useState("");
  const [createServiceTypeId, setCreateServiceTypeId] = useState<string>("");
  const [createServiceCode, setCreateServiceCode] = useState("");
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
  const [additionalDeviceFields, setAdditionalDeviceFields] = useState<WizardAdditionalDeviceField[]>([]);
  const [pinEnabled, setPinEnabled] = useState(false);
  const [pinLabel, setPinLabel] = useState("Pin");
  const [devices, setDevices] = useState<ApiDevice[]>([]);

  const [technicianCreateOpen, setTechnicianCreateOpen] = useState(false);
  const [technicianCreateError, setTechnicianCreateError] = useState<string | null>(null);
  const [technicianCreateName, setTechnicianCreateName] = useState("");
  const [technicianCreateEmail, setTechnicianCreateEmail] = useState("");
  const [technicianCreateBranchIds, setTechnicianCreateBranchIds] = useState<number[]>([]);
  const [technicianOption, setTechnicianOption] = useState<TechnicianOption | null>(null);
  const [technicianMode, setTechnicianMode] = useState<"existing" | "new">("existing");

  const [branchesCatalog, setBranchesCatalog] = useState<ApiBranch[]>([]);

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
          const branchesRes = await apiFetch<{ branches: ApiBranch[] }>(`/api/${tenantSlug}/app/branches`);
          if (!alive) return;
          setBranchesCatalog(Array.isArray(branchesRes.branches) ? branchesRes.branches : []);
        } catch {
          if (!alive) return;
          setBranchesCatalog([]);
        }

        try {
          const settingsRes = await getRepairBuddySettings(String(tenantSlug));
          if (!alive) return;
          setNextServiceEnabled(Boolean(settingsRes.settings?.general?.nextServiceDateEnabled));

          setPinEnabled(Boolean(settingsRes.settings?.devicesBrands?.enablePinCodeField));
          const rawLabels = settingsRes.settings?.devicesBrands?.labels;
          const nextPinLabel =
            rawLabels && typeof rawLabels === "object" && typeof (rawLabels as { pin?: unknown }).pin === "string"
              ? String((rawLabels as { pin?: unknown }).pin).trim()
              : "";
          setPinLabel(nextPinLabel !== "" ? nextPinLabel : "Pin");

          const rawFields = settingsRes.settings?.devicesBrands?.additionalDeviceFields;
          const mapped: WizardAdditionalDeviceField[] = (Array.isArray(rawFields) ? rawFields : [])
            .map((f) => {
              const key = typeof (f as { id?: unknown }).id === "string" ? String((f as { id?: unknown }).id) : "";
              const label = typeof (f as { label?: unknown }).label === "string" ? String((f as { label?: unknown }).label) : "";
              if (!key || !label) return null;
              return { key, label };
            })
            .filter((x): x is WizardAdditionalDeviceField => Boolean(x));
          setAdditionalDeviceFields(mapped);
        } catch {
          if (!alive) return;
          setNextServiceEnabled(false);
          setAdditionalDeviceFields([]);
          setPinEnabled(false);
          setPinLabel("Pin");
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
          const typesRes = await apiFetch<{ part_types: ApiPartType[] }>(`/api/${tenantSlug}/app/repairbuddy/part-types?limit=200`);
          if (!alive) return;
          setPartTypesCatalog(Array.isArray(typesRes.part_types) ? typesRes.part_types : []);
        } catch {
          if (!alive) return;
          setPartTypesCatalog([]);
        }

        try {
          const brandsRes = await apiFetch<{ part_brands: ApiPartBrand[] }>(`/api/${tenantSlug}/app/repairbuddy/part-brands?limit=200`);
          if (!alive) return;
          setPartBrandsCatalog(Array.isArray(brandsRes.part_brands) ? brandsRes.part_brands : []);
        } catch {
          if (!alive) return;
          setPartBrandsCatalog([]);
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

        try {
          const typesRes = await apiFetch<{ service_types: ApiServiceType[] }>(`/api/${tenantSlug}/app/repairbuddy/service-types?limit=200`);
          if (!alive) return;
          setServiceTypesCatalog(Array.isArray(typesRes.service_types) ? typesRes.service_types : []);
        } catch {
          if (!alive) return;
          setServiceTypesCatalog([]);
        }
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load form data.");
        setStatuses([]);
        setPaymentStatuses([]);
        setClients([]);
        setTechnicians([]);
        setBranchesCatalog([]);
        setNextServiceEnabled(false);
        setDevices([]);
        setPartsCatalog([]);
        setServicesCatalog([]);
        setServiceTypesCatalog([]);
        setPartTypesCatalog([]);
        setPartBrandsCatalog([]);
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

    const native = e.nativeEvent as SubmitEvent;
    const submitter = native?.submitter as HTMLElement | null | undefined;
    const submitterId = submitter?.getAttribute?.("id") ?? "";

    if (step !== 3) {
      return;
    }

    if (submitterId !== "rb_job_save") {
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
                extra_fields:
                  Array.isArray(d.extra_fields) && d.extra_fields.length > 0
                    ? d.extra_fields
                        .map((x) => ({
                          key: typeof x?.key === "string" ? x.key : "",
                          label: typeof x?.label === "string" ? x.label : "",
                          value_text: typeof x?.value_text === "string" ? x.value_text : "",
                        }))
                        .filter((x) => x.key && x.label)
                    : [],
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

        <CustomerCreateModal
          open={customerCreateOpen}
          title="Add customer"
          className="max-w-3xl"
          disabled={disabled}
          error={customerCreateError}
          setError={setCustomerCreateError}
          name={customerCreateName}
          setName={setCustomerCreateName}
          email={customerCreateEmail}
          setEmail={setCustomerCreateEmail}
          phone={customerCreatePhone}
          setPhone={setCustomerCreatePhone}
          company={customerCreateCompany}
          setCompany={setCustomerCreateCompany}
          addressLine1={customerCreateAddressLine1}
          setAddressLine1={setCustomerCreateAddressLine1}
          addressLine2={customerCreateAddressLine2}
          setAddressLine2={setCustomerCreateAddressLine2}
          addressCity={customerCreateAddressCity}
          setAddressCity={setCustomerCreateAddressCity}
          addressState={customerCreateAddressState}
          setAddressState={setCustomerCreateAddressState}
          addressPostalCode={customerCreateAddressPostalCode}
          setAddressPostalCode={setCustomerCreateAddressPostalCode}
          addressCountry={customerCreateAddressCountry}
          setAddressCountry={setCustomerCreateAddressCountry}
          onClose={() => setCustomerCreateOpen(false)}
          onSave={({ name, email, phone, company, address_line1, address_line2, address_city, address_state, address_postal_code, address_country }) => {
            setCustomerMode("new");
            setCustomerId(null);
            setCustomerOption({ value: -1, label: `${name} (${email})` });
            setJobDevices([]);

            setCustomerCreatePhone(phone);
            setCustomerCreateCompany(company);
            setCustomerCreateAddressLine1(address_line1 ?? "");
            setCustomerCreateAddressLine2(address_line2 ?? "");
            setCustomerCreateAddressCity(address_city ?? "");
            setCustomerCreateAddressState(address_state ?? "");
            setCustomerCreateAddressPostalCode(address_postal_code ?? "");
            setCustomerCreateAddressCountry(address_country ?? "");

            setCustomerCreateOpen(false);
          }}
        />

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

                const crypto = globalThis.crypto as unknown as { randomUUID?: () => string };
                const id =
                  typeof crypto?.randomUUID === "function"
                    ? crypto.randomUUID()
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

                const crypto = globalThis.crypto as unknown as { randomUUID?: () => string };
                const id =
                  typeof crypto?.randomUUID === "function"
                    ? crypto.randomUUID()
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

                const typeIdTrimmed = createServiceTypeId.trim();
                const typeIdNum = typeIdTrimmed !== "" ? Number(typeIdTrimmed) : NaN;
                const serviceTypeId = Number.isFinite(typeIdNum) && typeIdNum > 0 ? Math.trunc(typeIdNum) : null;
                if (serviceTypesCatalog.length > 0 && serviceTypeId === null) {
                  setCreateServiceError("Service type is required.");
                  return;
                }

                const code = createServiceCode.trim();

                setBusy(true);
                setCreateServiceError(null);

                try {
                  const res = await apiFetch<{ service: { id: number; name: string } }>(`/api/${tenantSlug}/app/repairbuddy/services`, {
                    method: "POST",
                    body: {
                      name,
                      ...(typeof serviceTypeId === "number" ? { service_type_id: serviceTypeId } : {}),
                      ...(code !== "" ? { service_code: code } : {}),
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
                  setCreateServiceTypeId("");
                  setCreateServiceCode("");
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
              <div className="mb-1 text-xs text-zinc-600">Service name</div>
              <Input value={createServiceName} onChange={(e) => setCreateServiceName(e.target.value)} disabled={disabled} />
            </div>

            <div>
              <div className="mb-1 text-xs text-zinc-600">Select type</div>
              <select
                className="w-full rounded-[var(--rb-radius-sm)] border border-zinc-300 bg-white px-3 py-2 text-sm text-[var(--rb-text)]"
                value={createServiceTypeId}
                onChange={(e) => {
                  setCreateServiceError(null);
                  setCreateServiceTypeId(e.target.value);
                }}
                disabled={disabled || serviceTypesCatalog.length === 0}
              >
                <option value="">{serviceTypesCatalog.length > 0 ? "Select type..." : "No types available"}</option>
                {serviceTypesCatalog.map((t) => (
                  <option key={t.id} value={String(t.id)}>
                    {t.name}
                  </option>
                ))}
              </select>
            </div>

            <div>
              <div className="mb-1 text-xs text-zinc-600">Service code</div>
              <Input
                value={createServiceCode}
                onChange={(e) => {
                  setCreateServiceError(null);
                  setCreateServiceCode(e.target.value);
                }}
                disabled={disabled}
              />
            </div>

            <div>
              <div className="mb-1 text-xs text-zinc-600">Service price</div>
              <Input
                type="number"
                min={0}
                step="0.01"
                value={createServiceBasePrice}
                onChange={(e) => {
                  setCreateServiceError(null);
                  setCreateServiceBasePrice(e.target.value);
                }}
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

                const rawPrice = createPartPrice.trim();
                if (rawPrice.length === 0) {
                  setCreatePartError("Part price is required.");
                  return;
                }

                const priceNum = Number(rawPrice);
                const priceAmountCents = Number.isFinite(priceNum) ? Math.round(priceNum * 100) : null;
                if (priceAmountCents === null || priceAmountCents < 0) {
                  setCreatePartError("Part price is invalid.");
                  return;
                }

                const typeIdTrimmed = createPartTypeId.trim();
                const typeIdNum = typeIdTrimmed !== "" ? Number(typeIdTrimmed) : NaN;
                const partTypeId = Number.isFinite(typeIdNum) && typeIdNum > 0 ? Math.trunc(typeIdNum) : null;
                if (partTypesCatalog.length > 0 && partTypeId === null) {
                  setCreatePartError("Part type is required.");
                  return;
                }

                const brandIdTrimmed = createPartBrandId.trim();
                const brandIdNum = brandIdTrimmed !== "" ? Number(brandIdTrimmed) : NaN;
                const partBrandId = Number.isFinite(brandIdNum) && brandIdNum > 0 ? Math.trunc(brandIdNum) : null;
                if (partBrandsCatalog.length > 0 && partBrandId === null) {
                  setCreatePartError("Part brand is required.");
                  return;
                }

                const manufacturingCode = createPartManufacturingCode.trim();
                const stockCode = createPartStockCode.trim();

                setBusy(true);
                setCreatePartError(null);

                try {
                  const res = await apiFetch<{
                    part: {
                      id: number;
                      name: string;
                      manufacturing_code: string | null;
                      stock_code: string | null;
                      capacity: string | null;
                    };
                  }>(
                    `/api/${tenantSlug}/app/repairbuddy/parts`,
                    {
                      method: "POST",
                      body: {
                        name,
                        price_amount_cents: priceAmountCents,
                        ...(typeof partTypeId === "number" ? { part_type_id: partTypeId } : {}),
                        ...(typeof partBrandId === "number" ? { part_brand_id: partBrandId } : {}),
                        manufacturing_code: manufacturingCode !== "" ? manufacturingCode : null,
                        stock_code: stockCode !== "" ? stockCode : null,
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
                  setCreatePartPrice("");
                  setCreatePartBrandId("");
                  setCreatePartTypeId("");
                  setCreatePartManufacturingCode("");
                  setCreatePartStockCode("");
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
              <div className="mb-1 text-xs text-zinc-600">Part name</div>
              <Input
                value={createPartName}
                onChange={(e) => {
                  setCreatePartError(null);
                  setCreatePartName(e.target.value);
                }}
                disabled={disabled}
              />
            </div>

            <div>
              <div className="mb-1 text-xs text-zinc-600">Part price</div>
              <Input
                type="number"
                min={0}
                step="0.01"
                value={createPartPrice}
                onChange={(e) => {
                  setCreatePartError(null);
                  setCreatePartPrice(e.target.value);
                }}
                disabled={disabled}
              />
            </div>

            <div>
              <div className="mb-1 text-xs text-zinc-600">Select brand</div>
              <select
                className="w-full rounded-[var(--rb-radius-sm)] border border-zinc-300 bg-white px-3 py-2 text-sm text-[var(--rb-text)]"
                value={createPartBrandId}
                onChange={(e) => {
                  setCreatePartError(null);
                  setCreatePartBrandId(e.target.value);
                }}
                disabled={disabled || partBrandsCatalog.length === 0}
              >
                <option value="">{partBrandsCatalog.length > 0 ? "Select brand..." : "No brands available"}</option>
                {partBrandsCatalog.map((b) => (
                  <option key={b.id} value={String(b.id)}>
                    {b.name}
                  </option>
                ))}
              </select>
            </div>

            <div>
              <div className="mb-1 text-xs text-zinc-600">Select type</div>
              <select
                className="w-full rounded-[var(--rb-radius-sm)] border border-zinc-300 bg-white px-3 py-2 text-sm text-[var(--rb-text)]"
                value={createPartTypeId}
                onChange={(e) => {
                  setCreatePartError(null);
                  setCreatePartTypeId(e.target.value);
                }}
                disabled={disabled || partTypesCatalog.length === 0}
              >
                <option value="">{partTypesCatalog.length > 0 ? "Select type..." : "No types available"}</option>
                {partTypesCatalog.map((t) => (
                  <option key={t.id} value={String(t.id)}>
                    {t.name}
                  </option>
                ))}
              </select>
            </div>

            <div>
              <div className="mb-1 text-xs text-zinc-600">Manufacturing code</div>
              <Input
                value={createPartManufacturingCode}
                onChange={(e) => {
                  setCreatePartError(null);
                  setCreatePartManufacturingCode(e.target.value);
                }}
                disabled={disabled}
              />
            </div>

            <div>
              <div className="mb-1 text-xs text-zinc-600">Stock code</div>
              <Input
                value={createPartStockCode}
                onChange={(e) => {
                  setCreatePartError(null);
                  setCreatePartStockCode(e.target.value);
                }}
                disabled={disabled}
              />
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
              type="button"
              variant="outline"
              onClick={() => {
                setExtraFieldError(null);
                setExtraFieldModalOpen(false);
              }}
            >
              Cancel
            </Button>
            <Button
              type="button"
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
                type="button"
                variant="outline"
                onClick={() => {
                  setTechnicianCreateError(null);
                  setTechnicianCreateOpen(false);
                }}
              >
                Cancel
              </Button>
              <Button
                type="button"
                disabled={disabled}
                onClick={async () => {
                  if (busy) return;
                  if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;

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

                  const branchIds = technicianCreateBranchIds.filter((x) => typeof x === "number" && x > 0);
                  if (branchIds.length === 0) {
                    setTechnicianCreateError("Please select at least one shop.");
                    return;
                  }

                  setBusy(true);
                  setTechnicianCreateError(null);

                  try {
                    const res = await apiFetch<{ user: ApiTechnician }>(`/api/${tenantSlug}/app/technicians`, {
                      method: "POST",
                      body: {
                        name,
                        email,
                        branch_ids: branchIds,
                      },
                    });

                    const user = res.user;
                    if (!user || typeof user.id !== "number") {
                      setTechnicianCreateError("Failed to create technician.");
                      return;
                    }

                    setTechnicians((prev) => [user, ...prev.filter((t) => t.id !== user.id)]);

                    setTechnicianMode("existing");
                    setTechnicianOption(null);
                    setAssignedTechnicianIds([user.id]);

                    setTechnicianCreateName("");
                    setTechnicianCreateEmail("");
                    setTechnicianCreateBranchIds([]);
                    setTechnicianCreateOpen(false);
                    notify.success("Technician created.");
                  } catch (err) {
                    if (err instanceof ApiError) {
                      setTechnicianCreateError(err.message);
                    } else {
                      setTechnicianCreateError("Failed to create technician.");
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
            {technicianCreateError ? <div className="text-sm text-red-600">{technicianCreateError}</div> : null}
            <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
              <div>
                <div className="mb-1 text-xs text-zinc-600">Name</div>
                <Input
                  value={technicianCreateName}
                  onChange={(e) => {
                    setTechnicianCreateError(null);
                    setTechnicianCreateName(e.target.value);
                  }}
                  disabled={disabled}
                />
              </div>
              <div>
                <div className="mb-1 text-xs text-zinc-600">Email</div>
                <Input
                  value={technicianCreateEmail}
                  onChange={(e) => {
                    setTechnicianCreateError(null);
                    setTechnicianCreateEmail(e.target.value);
                  }}
                  disabled={disabled}
                />
              </div>

              <div className="md:col-span-2">
                <div className="mb-1 text-xs text-zinc-600">Shops</div>
                <select
                  multiple
                  className="w-full rounded-[var(--rb-radius-sm)] border border-zinc-300 bg-white px-3 py-2 text-sm text-[var(--rb-text)]"
                  value={technicianCreateBranchIds.map(String)}
                  onChange={(e) => {
                    setTechnicianCreateError(null);
                    const values = Array.from(e.target.selectedOptions)
                      .map((o) => Number(o.value))
                      .filter((n) => Number.isFinite(n) && n > 0)
                      .map((n) => Math.trunc(n));
                    setTechnicianCreateBranchIds(values);
                  }}
                  disabled={disabled || branchesCatalog.length === 0}
                >
                  {branchesCatalog.length === 0 ? <option value="">No shops available</option> : null}
                  {branchesCatalog.map((b) => (
                    <option key={b.id} value={String(b.id)}>
                      {b.name}
                    </option>
                  ))}
                </select>
                <div className="mt-1 text-xs text-zinc-500">Hold Ctrl / Cmd to select multiple shops.</div>
              </div>
            </div>
          </div>
        </Modal>

        <form id="rb_job_new_form" className="space-y-6" onSubmit={onSubmit}>
          <WizardShell
            steps={[
              {
                id: 1,
                navTitle: "Customer & devices",
                navDescription: "Case, dates, customer, technician, description, devices.",
                pageTitle: "Customer & devices",
                pageDescription: "Enter the customer, technician, description and devices.",
                footerTitle: "Customer & devices",
              },
              {
                id: 2,
                navTitle: "Job Items and services",
                navDescription: "Attach extra fields and files for this job.",
                pageTitle: "Job Items and services",
                pageDescription: "Attach extra fields and files for the job.",
                footerTitle: "Job Items and services",
              },
              {
                id: 3,
                navTitle: "Order information",
                navDescription: "Status, payment, priority, notes.",
                pageTitle: "Order information",
                pageDescription: "Finalize status, payment, priority and notes.",
                footerTitle: "Order information",
              },
            ]}
            step={step}
            disabled={disabled}
            sidebarTitle="Job steps"
            sidebarDescription="Complete the steps to create the job."
            sidebarAriaLabel="Job steps"
            onStepChange={(next) => {
              setError(null);
              setStep(next as 1 | 2 | 3);
            }}
            footerRight={
              <Button id="rb_job_save" variant="primary" disabled={disabled} type="submit">
                {busy ? "Saving..." : "Save"}
              </Button>
            }
          >
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
                      <DevicesAdminEditor
                        value={jobDevicesAdmin}
                        onChange={(next) => setJobDevicesAdmin(next)}
                        deviceOptions={deviceOptions}
                        loadDeviceOptions={loadDeviceOptions}
                        disabled={disabled}
                        idPrefix="job"
                        showPin={pinEnabled}
                        serialLabel="Device ID / IMEI"
                        pinLabel={pinLabel}
                        notesLabel="Device note"
                        addButtonLabel="Add device"
                        additionalFields={additionalDeviceFields}
                        createEmptyRow={() => ({
                          device_id: null,
                          option: null,
                          serial: "",
                          pin: "",
                          notes: "",
                          extra_fields: additionalDeviceFields.map((f) => ({ key: f.key, label: f.label, value_text: "" })),
                        })}
                      />
                    </FormRow>
                  </div>
                ) : isStep2 ? (
                  <div className="space-y-5">
                    <ItemsStep
                      deviceContextOptions={selectedJobDeviceOptions.map((d) => ({ value: d.value, label: d.label }))}
                      disabled={disabled}
                      services={jobServices}
                      setServices={setJobServices}
                      parts={jobParts}
                      setParts={setJobParts}
                      otherItems={jobOtherItems}
                      setOtherItems={setJobOtherItems}
                      onAddService={() => {
                        setServicePickerError(null);
                        if (!servicePickerDevice && selectedJobDeviceOptions.length > 0) {
                          setServicePickerDevice(selectedJobDeviceOptions[0]);
                        }
                        setServicePickerModalOpen(true);
                      }}
                      serviceAddLabel="Add service"
                      onCreateService={() => {
                        setCreateServiceError(null);
                        setCreateServiceModalOpen(true);
                      }}
                      serviceCreateLabel="Create new service"
                      onAddPart={() => {
                        setPartPickerError(null);
                        if (!partPickerDevice && selectedJobDeviceOptions.length > 0) {
                          setPartPickerDevice(selectedJobDeviceOptions[0]);
                        }
                        setPartPickerModalOpen(true);
                      }}
                      partAddLabel="Add part"
                      onCreatePart={() => {
                        setCreatePartError(null);
                        setCreatePartModalOpen(true);
                      }}
                      partCreateLabel="Create new part"
                      otherItemsTitle="Other items"
                      otherItemsDescription="Add custom line items like rent, used cable, etc."
                      otherItemsAddLabel="Add other item"
                      createOtherItem={() => ({
                        id:
                          typeof crypto !== "undefined" && "randomUUID" in crypto
                            ? crypto.randomUUID()
                            : `oi-${Date.now()}-${Math.random().toString(16).slice(2)}`,
                        name: "",
                        qty: "1",
                        price: "",
                      })}
                      otherItemNamePlaceholder="e.g. Rent, Used cable"
                      otherItemPricePlaceholder="e.g. 10.00 (use -10.00 for discount)"
                    />

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
          </WizardShell>
        </form>
      </div>
    </RequireAuth>
  );
}
