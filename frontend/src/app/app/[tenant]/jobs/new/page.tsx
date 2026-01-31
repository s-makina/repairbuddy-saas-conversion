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
import { getRepairBuddySettings } from "@/lib/repairbuddy-settings";

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

type ApiTechnician = {
  id: number;
  name: string;
  email: string;
};

type ApiCustomerDevice = {
  id: number;
  customer_id: number;
  label: string;
  serial: string | null;
};

type ApiJob = {
  id: number;
};

export default function NewJobPage() {
  const router = useRouter();
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loadingLookups, setLoadingLookups] = useState(true);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const [title, setTitle] = useState("");
  const [statusSlug, setStatusSlug] = useState<string>("");
  const [paymentStatusSlug, setPaymentStatusSlug] = useState<string>("");
  const [priority, setPriority] = useState<string>("");
  const [customerId, setCustomerId] = useState<number | null>(null);

  const [caseNumber, setCaseNumber] = useState<string>("");
  const [pickupDate, setPickupDate] = useState<string>("");
  const [deliveryDate, setDeliveryDate] = useState<string>("");
  const [nextServiceDate, setNextServiceDate] = useState<string>("");
  const [caseDetail, setCaseDetail] = useState<string>("");
  const [assignedTechnicianId, setAssignedTechnicianId] = useState<number | null>(null);
  const [attachCustomerDeviceId, setAttachCustomerDeviceId] = useState<number | null>(null);

  const [statuses, setStatuses] = useState<ApiJobStatus[]>([]);
  const [paymentStatuses, setPaymentStatuses] = useState<ApiPaymentStatus[]>([]);
  const [clients, setClients] = useState<ApiClient[]>([]);

  const [technicians, setTechnicians] = useState<ApiTechnician[]>([]);
  const [customerDevices, setCustomerDevices] = useState<ApiCustomerDevice[]>([]);
  const [nextServiceEnabled, setNextServiceEnabled] = useState(false);

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
          const techniciansRes = await apiFetch<{ users: ApiTechnician[] }>(`/api/${tenantSlug}/app/technicians?per_page=200`);
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

        setStatusSlug(nextStatuses.length > 0 ? nextStatuses[0].slug : "");
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

  const sortedClients = useMemo(() => {
    return clients.slice().sort((a, b) => `${a.name}`.localeCompare(`${b.name}`));
  }, [clients]);

  const sortedTechnicians = useMemo(() => {
    return technicians.slice().sort((a, b) => `${a.name}`.localeCompare(`${b.name}`));
  }, [technicians]);

  useEffect(() => {
    let alive = true;

    async function loadCustomerDevices() {
      if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
      if (typeof customerId !== "number") {
        setCustomerDevices([]);
        setAttachCustomerDeviceId(null);
        return;
      }

      try {
        const res = await apiFetch<{ customer_devices: ApiCustomerDevice[] }>(
          `/api/${tenantSlug}/app/repairbuddy/customer-devices?customer_id=${encodeURIComponent(String(customerId))}&limit=200`,
        );
        if (!alive) return;
        setCustomerDevices(Array.isArray(res.customer_devices) ? res.customer_devices : []);
      } catch {
        if (!alive) return;
        setCustomerDevices([]);
      }
    }

    void loadCustomerDevices();

    return () => {
      alive = false;
    };
  }, [customerId, tenantSlug]);

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
    if (busy) return;

    setBusy(true);
    setError(null);

    try {
      const trimmedTitle = title.trim();
      if (trimmedTitle.length === 0) {
        setError("Title is required.");
        return;
      }

      const res = await apiFetch<{ job: ApiJob }>(`/api/${tenantSlug}/app/repairbuddy/jobs`, {
        method: "POST",
        body: {
          case_number: caseNumber.trim() !== "" ? caseNumber.trim() : null,
          title: trimmedTitle,
          status_slug: statusSlug.trim() !== "" ? statusSlug : null,
          payment_status_slug: paymentStatusSlug.trim() !== "" ? paymentStatusSlug : null,
          priority: priority.trim() !== "" ? priority.trim() : null,
          customer_id: typeof customerId === "number" ? customerId : null,
          pickup_date: pickupDate.trim() !== "" ? pickupDate : null,
          delivery_date: deliveryDate.trim() !== "" ? deliveryDate : null,
          next_service_date: nextServiceEnabled && nextServiceDate.trim() !== "" ? nextServiceDate : null,
          case_detail: caseDetail.trim() !== "" ? caseDetail.trim() : null,
          assigned_technician_id: typeof assignedTechnicianId === "number" ? assignedTechnicianId : null,
        },
      });

      notify.success("Job created.");

      const nextId = res.job?.id;
      if (typeof nextId === "number") {
        if (typeof attachCustomerDeviceId === "number" && typeof customerId === "number") {
          try {
            await apiFetch(`/api/${tenantSlug}/app/repairbuddy/jobs/${nextId}/devices`, {
              method: "POST",
              body: { customer_device_id: attachCustomerDeviceId },
            });
          } catch {
            // Best-effort: job is created, device attach can be done on the job detail page.
          }
        }
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
              <Button variant="primary" size="sm" type="submit" form="rb_job_new_form" disabled={disabled}>
                {busy ? "Saving..." : "Save"}
              </Button>
            </>
          }
        />

        {error ? <div className="text-sm text-red-600">{error}</div> : null}

        <Card className="shadow-none">
          <CardContent className="pt-5">
            <form id="rb_job_new_form" className="space-y-4" onSubmit={onSubmit}>
              <FormRow label="Title" fieldId="job_title" required>
                <Input id="job_title" value={title} onChange={(e) => setTitle(e.target.value)} disabled={disabled} />
              </FormRow>

              <FormRow label="Case number" fieldId="job_case_number" description="Leave blank to auto-generate.">
                <Input
                  id="job_case_number"
                  value={caseNumber}
                  onChange={(e) => setCaseNumber(e.target.value)}
                  disabled={disabled}
                  placeholder="(auto)"
                />
              </FormRow>

              <FormRow label="Customer" fieldId="job_customer">
                <select
                  id="job_customer"
                  className="w-full rounded-[var(--rb-radius-sm)] border border-zinc-300 bg-white px-3 py-2 text-sm text-[var(--rb-text)]"
                  value={typeof customerId === "number" ? String(customerId) : ""}
                  onChange={(e) => {
                    const raw = e.target.value;
                    if (!raw) {
                      setCustomerId(null);
                      setAttachCustomerDeviceId(null);
                      return;
                    }
                    const n = Number(raw);
                    setCustomerId(Number.isFinite(n) ? n : null);
                    setAttachCustomerDeviceId(null);
                  }}
                  disabled={disabled || sortedClients.length === 0}
                >
                  <option value="">{sortedClients.length > 0 ? "—" : "No clients available"}</option>
                  {sortedClients.map((c) => (
                    <option key={c.id} value={String(c.id)}>
                      {c.name} {c.email ? `(${c.email})` : ""}
                    </option>
                  ))}
                </select>
              </FormRow>

              <FormRow label="Assigned technician" fieldId="job_technician">
                <select
                  id="job_technician"
                  className="w-full rounded-[var(--rb-radius-sm)] border border-zinc-300 bg-white px-3 py-2 text-sm text-[var(--rb-text)]"
                  value={typeof assignedTechnicianId === "number" ? String(assignedTechnicianId) : ""}
                  onChange={(e) => {
                    const raw = e.target.value;
                    if (!raw) {
                      setAssignedTechnicianId(null);
                      return;
                    }
                    const n = Number(raw);
                    setAssignedTechnicianId(Number.isFinite(n) ? n : null);
                  }}
                  disabled={disabled || sortedTechnicians.length === 0}
                >
                  <option value="">{sortedTechnicians.length > 0 ? "—" : "No technicians available"}</option>
                  {sortedTechnicians.map((t) => (
                    <option key={t.id} value={String(t.id)}>
                      {t.name} {t.email ? `(${t.email})` : ""}
                    </option>
                  ))}
                </select>
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

              <FormRow label="Pickup date" fieldId="job_pickup_date">
                <Input id="job_pickup_date" type="date" value={pickupDate} onChange={(e) => setPickupDate(e.target.value)} disabled={disabled} />
              </FormRow>

              <FormRow label="Delivery date" fieldId="job_delivery_date">
                <Input id="job_delivery_date" type="date" value={deliveryDate} onChange={(e) => setDeliveryDate(e.target.value)} disabled={disabled} />
              </FormRow>

              {nextServiceEnabled ? (
                <FormRow label="Next service date" fieldId="job_next_service_date">
                  <Input
                    id="job_next_service_date"
                    type="date"
                    value={nextServiceDate}
                    onChange={(e) => setNextServiceDate(e.target.value)}
                    disabled={disabled}
                  />
                </FormRow>
              ) : null}

              <FormRow label="Job details" fieldId="job_case_detail">
                <textarea
                  id="job_case_detail"
                  value={caseDetail}
                  onChange={(e) => setCaseDetail(e.target.value)}
                  disabled={disabled}
                  rows={4}
                  placeholder="Enter details about job."
                  className="w-full rounded-[var(--rb-radius-sm)] border border-zinc-300 bg-white px-3 py-2 text-sm text-[var(--rb-text)]"
                />
              </FormRow>

              <FormRow label="Attach customer device" fieldId="job_attach_device" description="Optional. Requires selecting a customer.">
                <select
                  id="job_attach_device"
                  className="w-full rounded-[var(--rb-radius-sm)] border border-zinc-300 bg-white px-3 py-2 text-sm text-[var(--rb-text)]"
                  value={typeof attachCustomerDeviceId === "number" ? String(attachCustomerDeviceId) : ""}
                  onChange={(e) => {
                    const raw = e.target.value;
                    if (!raw) {
                      setAttachCustomerDeviceId(null);
                      return;
                    }
                    const n = Number(raw);
                    setAttachCustomerDeviceId(Number.isFinite(n) ? n : null);
                  }}
                  disabled={disabled || typeof customerId !== "number" || customerDevices.length === 0}
                >
                  <option value="">{typeof customerId !== "number" ? "Select a customer first" : customerDevices.length > 0 ? "—" : "No devices available"}</option>
                  {customerDevices
                    .slice()
                    .sort((a, b) => `${a.label}`.localeCompare(`${b.label}`))
                    .map((d) => (
                      <option key={d.id} value={String(d.id)}>
                        {d.label} {d.serial ? `(Serial: ${d.serial})` : ""}
                      </option>
                    ))}
                </select>
              </FormRow>
            </form>
          </CardContent>
        </Card>
      </div>
    </RequireAuth>
  );
}
