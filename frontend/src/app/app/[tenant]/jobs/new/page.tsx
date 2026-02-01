"use client";

import React, { useEffect, useMemo, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import Select from "react-select";
import AsyncSelect from "react-select/async";
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

  const [step, setStep] = useState<1 | 2>(1);

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

  const [jobDevicesAdmin, setJobDevicesAdmin] = useState<NewJobDeviceDraft[]>([]);

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
            `/api/${tenantSlug}/app/repairbuddy/devices?limit=5000&for_booking=true`,
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
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load form data.");
        setStatuses([]);
        setPaymentStatuses([]);
        setClients([]);
        setTechnicians([]);
        setNextServiceEnabled(false);
        setDevices([]);
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
      qs.set("limit", "50");
      qs.set("for_booking", "true");
      const q = inputValue.trim();
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

      const payload = {
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
        customer_create:
          shouldCreateCustomer
            ? {
                name: customerCreateName.trim(),
                email: customerCreateEmail.trim(),
                phone: customerCreatePhone.trim() !== "" ? customerCreatePhone.trim() : null,
                company: customerCreateCompany.trim() !== "" ? customerCreateCompany.trim() : null,
                address_line1: customerCreateAddressLine1.trim() !== "" ? customerCreateAddressLine1.trim() : null,
                address_line2: customerCreateAddressLine2.trim() !== "" ? customerCreateAddressLine2.trim() : null,
                address_city: customerCreateAddressCity.trim() !== "" ? customerCreateAddressCity.trim() : null,
                address_state: customerCreateAddressState.trim() !== "" ? customerCreateAddressState.trim() : null,
                address_postal_code:
                  customerCreateAddressPostalCode.trim() !== "" ? customerCreateAddressPostalCode.trim() : null,
                address_country: customerCreateAddressCountry.trim() !== "" ? customerCreateAddressCountry.trim() : null,
              }
            : null,
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

      const form = new FormData();
      form.append("payload_json", JSON.stringify(payload));
      if (jobFile) {
        form.append("job_file", jobFile);
      }

      const res = await apiFetch<{ job: ApiJob }>(`/api/${tenantSlug}/app/repairbuddy/jobs`, {
        method: "POST",
        body: form,
      });

      notify.success("Job created.");

      const nextId = res.job?.id;
      if (typeof nextId === "number") {
        router.replace(`/app/${tenantSlug}/jobs/${nextId}`);
      } else {
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
  const stepIndex = step === 1 ? 0 : 1;
  const progress = stepIndex;

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
                  {[1, 2].map((s, idx) => {
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
                          setStep(s as 1 | 2);
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
                              {s === 1 ? "Customer & devices" : "Job details"}
                            </div>
                            <div className="mt-0.5 line-clamp-2 text-xs text-zinc-600">
                              {s === 1
                                ? "Case, dates, customer, technician, description, devices."
                                : "Status, payment, priority, attachments, customer devices."}
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
                    <CardTitle className="text-base">{isStep1 ? "Customer & devices" : "Job details"}</CardTitle>
                    <CardDescription>
                      {isStep1
                        ? "Enter the customer, technician, description and devices."
                        : "Finalize status, priority, attachments and other details."}
                    </CardDescription>
                  </div>
                  <div className="text-right">
                    <div className="text-xs text-zinc-500">Step {stepIndex + 1} of 2</div>
                    <div className="mt-2 flex items-center justify-end gap-2">
                      <div className="flex items-center gap-1" aria-label="Progress">
                        {[0, 1].map((i) => {
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
                              <div key={idx} className="grid grid-cols-1 gap-3 md:grid-cols-[1fr_220px_1fr_auto] md:items-end">
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
                                    placeholder="Search devices..."
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
                                  <Button
                                    type="button"
                                    variant="outline"
                                    size="md"
                                    className="h-10 w-10 px-0"
                                    disabled={disabled}
                                    onClick={() => setJobDevicesAdmin((prev) => prev.filter((_, i) => i !== idx))}
                                    aria-label="Remove device"
                                    title="Remove device"
                                  >
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
                                      <path d="M3 6h18" />
                                      <path d="M8 6V4h8v2" />
                                      <path d="M6 6l1 16h10l1-16" />
                                      <path d="M10 11v6" />
                                      <path d="M14 11v6" />
                                    </svg>
                                  </Button>
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
                ) : (
                  <div className="space-y-5">
                    <FormRow label="Title" fieldId="job_title" description="Optional. Leave blank to auto-fill.">
                      <Input id="job_title" value={title} onChange={(e) => setTitle(e.target.value)} disabled={disabled} />
                    </FormRow>

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

                    <FormRow label="Order note" fieldId="job_order_note" description="Visible to customer.">
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

                    <FormRow label="File attachment" fieldId="job_file" description="Optional. Upload a single file.">
                      <Input
                        id="job_file"
                        type="file"
                        disabled={disabled}
                        onChange={(e) => {
                          const next = e.target.files?.[0] ?? null;
                          setJobFile(next);
                        }}
                      />
                    </FormRow>

                    <FormRow label="Customer devices" fieldId="job_devices" description="Optional. Requires selecting a customer.">
                      {customerDevicesError ? <div className="mb-2 text-sm text-red-600">{customerDevicesError}</div> : null}
                      <AsyncSelect
                        inputId="job_devices"
                        instanceId="job_devices"
                        cacheOptions
                        defaultOptions={customerDeviceOptions}
                        loadOptions={loadCustomerDeviceOptions}
                        isClearable
                        isSearchable
                        isMulti
                        value={jobDevices.map((d) => d.option)}
                        onChange={(opts) => {
                          const nextOptions = (Array.isArray(opts) ? (opts as CustomerDeviceOption[]) : [])
                            .filter((o) => o && typeof o.value === "number")
                            .map((o) => ({ value: o.value, label: o.label }));

                          setJobDevices((prev) => {
                            const prevById = new Map(prev.map((d) => [d.customer_device_id, d] as const));
                            return nextOptions.map((o) => {
                              const existing = prevById.get(o.value);
                              if (existing) {
                                return { ...existing, option: o };
                              }
                              return {
                                customer_device_id: o.value,
                                option: o,
                                serial: "",
                                notes: "",
                              };
                            });
                          });
                        }}
                        isDisabled={disabled || typeof customerId !== "number"}
                        placeholder={typeof customerId !== "number" ? "Select a customer first" : "Search devices..."}
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

                      {jobDevices.length > 0 ? (
                        <div className="mt-3 space-y-3">
                          {jobDevices.map((d) => (
                            <div key={d.customer_device_id} className="rounded-[var(--rb-radius-sm)] border border-zinc-200 bg-white p-3">
                              <div className="text-sm font-medium text-[var(--rb-text)]">{d.option.label}</div>

                              <div className="mt-2 grid grid-cols-1 gap-3 md:grid-cols-2">
                                <div>
                                  <div className="mb-1 text-xs text-zinc-600">Device ID / IMEI</div>
                                  <Input
                                    value={d.serial}
                                    onChange={(e) => {
                                      const v = e.target.value;
                                      setJobDevices((prev) =>
                                        prev.map((x) =>
                                          x.customer_device_id === d.customer_device_id ? { ...x, serial: v } : x,
                                        ),
                                      );
                                    }}
                                    disabled={disabled}
                                    placeholder="Enter device ID / IMEI"
                                  />
                                </div>

                                <div>
                                  <div className="mb-1 text-xs text-zinc-600">Note</div>
                                  <textarea
                                    value={d.notes}
                                    onChange={(e) => {
                                      const v = e.target.value;
                                      setJobDevices((prev) =>
                                        prev.map((x) =>
                                          x.customer_device_id === d.customer_device_id ? { ...x, notes: v } : x,
                                        ),
                                      );
                                    }}
                                    disabled={disabled}
                                    rows={2}
                                    placeholder="Add a note for this device"
                                    className="w-full rounded-[var(--rb-radius-sm)] border border-zinc-300 bg-white px-3 py-2 text-sm text-[var(--rb-text)]"
                                  />
                                </div>
                              </div>
                            </div>
                          ))}
                        </div>
                      ) : null}
                    </FormRow>
                  </div>
                )}
              </CardContent>

              <div className="sticky bottom-0 border-t border-[var(--rb-border)] bg-white/90 px-5 py-4 backdrop-blur">
                <div className="flex items-center justify-between gap-4">
                  <Button
                    variant="ghost"
                    disabled={disabled || isStep1}
                    onClick={() => setStep(1)}
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
                      {isStep1 ? "Customer & devices" : "Job details"}
                      <span className="mx-2">•</span>
                      {Math.round(progress * 100)}%
                    </div>

                    {isStep1 ? (
                      <Button variant="primary" disabled={disabled} onClick={() => setStep(2)} type="button">
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
