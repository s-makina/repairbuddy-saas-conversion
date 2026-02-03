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

type ApiClient = {
  id: number;
  name: string;
  email: string | null;
  phone: string | null;
  company: string | null;
};

type ApiEstimate = {
  id: number;
  customer: null | { id: number; name: string; email: string };
  items: Array<{ id: number; name: string; qty: number; unit_price: { currency: string; amount_cents: number } }>;
  totals: { currency: string; subtotal_cents: number; tax_cents: number; total_cents: number };
};

type ApiCustomerDevice = {
  id: number;
  customer_id: number;
  label: string;
  serial: string | null;
};

type ApiEstimateDevice = {
  id: number;
  estimate_id: number;
  customer_device_id: number;
  label: string;
  serial: string | null;
  pin: string | null;
  notes: string | null;
};

type ApiService = {
  id: number;
  name: string;
};

type ApiPart = {
  id: number;
  name: string;
};

export default function NewEstimatePage() {
  const router = useRouter();
  const params = useParams() as { tenant?: string; business?: string };
  const tenant = params.business ?? params.tenant;

  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const [step, setStep] = useState<1 | 2 | 3>(1);
  const [estimateId, setEstimateId] = useState<number | null>(null);

  const [clients, setClients] = useState<ApiClient[]>([]);

  const [title, setTitle] = useState("");
  const [caseNumber, setCaseNumber] = useState("");

  const [customerMode, setCustomerMode] = useState<"existing" | "new">("existing");
  const [customerId, setCustomerId] = useState<number | null>(null);
  const [customerCreateName, setCustomerCreateName] = useState("");
  const [customerCreateEmail, setCustomerCreateEmail] = useState("");
  const [customerCreatePhone, setCustomerCreatePhone] = useState("");
  const [customerCreateCompany, setCustomerCreateCompany] = useState("");

  const [estimate, setEstimate] = useState<ApiEstimate | null>(null);
  const [estimateDevices, setEstimateDevices] = useState<ApiEstimateDevice[]>([]);
  const [customerDevices, setCustomerDevices] = useState<ApiCustomerDevice[]>([]);
  const [selectedCustomerDeviceId, setSelectedCustomerDeviceId] = useState<number | null>(null);

  const [services, setServices] = useState<ApiService[]>([]);
  const [parts, setParts] = useState<ApiPart[]>([]);

  const [newItemType, setNewItemType] = useState<"service" | "part" | "fee" | "discount">("service");
  const [newItemRefId, setNewItemRefId] = useState<number | null>(null);
  const [newItemName, setNewItemName] = useState("");
  const [newItemQty, setNewItemQty] = useState("1");
  const [newItemPrice, setNewItemPrice] = useState("");

  const isStep1 = step === 1;
  const isStep2 = step === 2;
  const isStep3 = step === 3;

  const customerEmail = estimate?.customer?.email ?? "";
  const canSendEmail = customerEmail.trim().length > 0;

  const sortedClients = useMemo(() => {
    return clients.slice().sort((a, b) => `${a.name}`.localeCompare(`${b.name}`));
  }, [clients]);

  const sortedCustomerDevices = useMemo(() => {
    return customerDevices.slice().sort((a, b) => `${a.label}`.localeCompare(`${b.label}`));
  }, [customerDevices]);

  const sortedServices = useMemo(() => {
    return services.slice().sort((a, b) => `${a.name}`.localeCompare(`${b.name}`));
  }, [services]);

  const sortedParts = useMemo(() => {
    return parts.slice().sort((a, b) => `${a.name}`.localeCompare(`${b.name}`));
  }, [parts]);

  async function refreshEstimate(nextEstimateId?: number | null) {
    if (typeof tenant !== "string" || tenant.length === 0) return;
    const id = typeof nextEstimateId === "number" ? nextEstimateId : estimateId;
    if (typeof id !== "number") return;

    const res = await apiFetch<{ estimate: ApiEstimate }>(`/api/${tenant}/app/repairbuddy/estimates/${id}`, {
      method: "GET",
    });
    setEstimate(res?.estimate ?? null);
  }

  async function refreshEstimateDevices(nextEstimateId?: number | null) {
    if (typeof tenant !== "string" || tenant.length === 0) return;
    const id = typeof nextEstimateId === "number" ? nextEstimateId : estimateId;
    if (typeof id !== "number") return;

    const res = await apiFetch<{ estimate_devices: ApiEstimateDevice[] }>(`/api/${tenant}/app/repairbuddy/estimates/${id}/devices`, {
      method: "GET",
    });
    setEstimateDevices(Array.isArray(res?.estimate_devices) ? res.estimate_devices : []);
  }

  useEffect(() => {
    let alive = true;
    async function loadClients() {
      if (typeof tenant !== "string" || tenant.length === 0) return;
      try {
        const res = await apiFetch<{ clients: ApiClient[] }>(`/api/${tenant}/app/clients?limit=200`, { method: "GET" });
        if (!alive) return;
        setClients(Array.isArray(res.clients) ? res.clients : []);
      } catch {
        if (!alive) return;
        setClients([]);
      }
    }

    void loadClients();
    return () => {
      alive = false;
    };
  }, [tenant]);

  useEffect(() => {
    let alive = true;
    async function loadCatalogs() {
      if (!isStep2 && !isStep3) return;
      if (typeof tenant !== "string" || tenant.length === 0) return;

      try {
        const [servicesRes, partsRes] = await Promise.all([
          apiFetch<{ services: ApiService[] }>(`/api/${tenant}/app/repairbuddy/services?limit=5000`, { method: "GET" }),
          apiFetch<{ parts: ApiPart[] }>(`/api/${tenant}/app/repairbuddy/parts?limit=5000`, { method: "GET" }),
        ]);
        if (!alive) return;
        setServices(Array.isArray(servicesRes.services) ? servicesRes.services : []);
        setParts(Array.isArray(partsRes.parts) ? partsRes.parts : []);
      } catch {
        if (!alive) return;
        setServices([]);
        setParts([]);
      }
    }

    void loadCatalogs();
    return () => {
      alive = false;
    };
  }, [isStep2, isStep3, tenant]);

  useEffect(() => {
    let alive = true;

    async function loadCustomerDevicesForEstimate() {
      if (!isStep2 && !isStep3) return;
      if (typeof tenant !== "string" || tenant.length === 0) return;
      if (typeof estimateId !== "number") return;

      try {
        await refreshEstimate(estimateId);
        await refreshEstimateDevices(estimateId);

        const current = await apiFetch<{ estimate: ApiEstimate }>(`/api/${tenant}/app/repairbuddy/estimates/${estimateId}`, { method: "GET" });
        if (!alive) return;

        const custId = current?.estimate?.customer?.id ?? null;
        if (typeof custId !== "number") {
          setCustomerDevices([]);
          return;
        }

        const devicesRes = await apiFetch<{ customer_devices: ApiCustomerDevice[] }>(
          `/api/${tenant}/app/repairbuddy/customer-devices?customer_id=${encodeURIComponent(String(custId))}&limit=200`,
          { method: "GET" },
        );
        if (!alive) return;
        setCustomerDevices(Array.isArray(devicesRes.customer_devices) ? devicesRes.customer_devices : []);
      } catch {
        if (!alive) return;
        setCustomerDevices([]);
      }
    }

    void loadCustomerDevicesForEstimate();

    return () => {
      alive = false;
    };
  }, [estimateId, isStep2, isStep3, tenant]);

  async function createEstimate() {
    if (typeof tenant !== "string" || tenant.length === 0) return;
    if (busy) return;

    const trimmedTitle = title.trim();
    if (trimmedTitle === "") {
      setError("Title is required.");
      return;
    }

    if (customerMode === "existing" && typeof customerId !== "number") {
      setError("Select a customer.");
      return;
    }

    if (customerMode === "new") {
      if (customerCreateName.trim() === "") {
        setError("Customer name is required.");
        return;
      }
      if (customerCreateEmail.trim() === "") {
        setError("Customer email is required.");
        return;
      }
    }

    setBusy(true);
    setError(null);

    try {
      const payload: Record<string, unknown> = {
        title: trimmedTitle,
        ...(caseNumber.trim() !== "" ? { case_number: caseNumber.trim() } : {}),
        ...(customerMode === "existing" && typeof customerId === "number" ? { customer_id: customerId } : {}),
        ...(customerMode === "new"
          ? {
              customer_create: {
                name: customerCreateName.trim(),
                email: customerCreateEmail.trim(),
                ...(customerCreatePhone.trim() !== "" ? { phone: customerCreatePhone.trim() } : {}),
                ...(customerCreateCompany.trim() !== "" ? { company: customerCreateCompany.trim() } : {}),
              },
            }
          : {}),
      };

      const res = await apiFetch<{ estimate: ApiEstimate }>(`/api/${tenant}/app/repairbuddy/estimates`, {
        method: "POST",
        body: payload,
      });

      const id = res?.estimate?.id;
      if (typeof id !== "number") {
        throw new Error("Estimate creation failed.");
      }

      setEstimateId(id);
      notify.success("Estimate created.");
      setStep(2);
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

  async function attachCustomerDevice() {
    if (typeof tenant !== "string" || tenant.length === 0) return;
    if (typeof estimateId !== "number") return;
    if (typeof selectedCustomerDeviceId !== "number") return;
    if (busy) return;

    setBusy(true);
    setError(null);
    try {
      await apiFetch(`/api/${tenant}/app/repairbuddy/estimates/${estimateId}/devices`, {
        method: "POST",
        body: { customer_device_id: selectedCustomerDeviceId },
      });
      await refreshEstimateDevices();
      notify.success("Device attached.");
    } catch (e) {
      if (e instanceof ApiError) {
        setError(e.message);
      } else {
        setError(e instanceof Error ? e.message : "Failed to attach device.");
      }
    } finally {
      setBusy(false);
    }
  }

  async function removeEstimateDevice(deviceId: number) {
    if (typeof tenant !== "string" || tenant.length === 0) return;
    if (typeof estimateId !== "number") return;
    if (busy) return;

    setBusy(true);
    setError(null);
    try {
      await apiFetch(`/api/${tenant}/app/repairbuddy/estimates/${estimateId}/devices/${deviceId}`, {
        method: "DELETE",
      });
      await refreshEstimateDevices();
      notify.success("Device removed.");
    } catch (e) {
      if (e instanceof ApiError) {
        setError(e.message);
      } else {
        setError(e instanceof Error ? e.message : "Failed to remove device.");
      }
    } finally {
      setBusy(false);
    }
  }

  async function addItem() {
    if (typeof tenant !== "string" || tenant.length === 0) return;
    if (typeof estimateId !== "number") return;
    if (busy) return;

    const qtyNum = Math.round(Number(newItemQty));
    const qty = Number.isFinite(qtyNum) && qtyNum > 0 ? qtyNum : 1;

    const body: Record<string, unknown> = {
      item_type: newItemType,
      qty,
    };

    if (newItemType === "service" || newItemType === "part") {
      if (typeof newItemRefId !== "number") {
        setError("Select an item.");
        return;
      }
      body.ref_id = newItemRefId;
    } else {
      const n = newItemName.trim();
      if (n === "") {
        setError("Name is required.");
        return;
      }
      body.name = n;
    }

    const rawPrice = newItemPrice.trim();
    const priceNum = rawPrice.length > 0 ? Number(rawPrice) : NaN;
    if (rawPrice.length > 0 && Number.isFinite(priceNum)) {
      body.unit_price_amount_cents = Math.round(priceNum * 100);
    }

    setBusy(true);
    setError(null);
    try {
      await apiFetch(`/api/${tenant}/app/repairbuddy/estimates/${estimateId}/items`, {
        method: "POST",
        body,
      });
      await refreshEstimate();
      notify.success("Item added.");
      setNewItemName("");
      setNewItemQty("1");
      setNewItemPrice("");
    } catch (e) {
      if (e instanceof ApiError) {
        setError(e.message);
      } else {
        setError(e instanceof Error ? e.message : "Failed to add item.");
      }
    } finally {
      setBusy(false);
    }
  }

  async function removeItem(itemId: number) {
    if (typeof tenant !== "string" || tenant.length === 0) return;
    if (typeof estimateId !== "number") return;
    if (busy) return;

    setBusy(true);
    setError(null);
    try {
      await apiFetch(`/api/${tenant}/app/repairbuddy/estimates/${estimateId}/items/${itemId}`, {
        method: "DELETE",
      });
      await refreshEstimate();
      notify.success("Item removed.");
    } catch (e) {
      if (e instanceof ApiError) {
        setError(e.message);
      } else {
        setError(e instanceof Error ? e.message : "Failed to remove item.");
      }
    } finally {
      setBusy(false);
    }
  }

  async function sendNow() {
    if (typeof tenant !== "string" || tenant.length === 0) return;
    if (typeof estimateId !== "number") return;
    if (busy) return;

    setBusy(true);
    setError(null);
    try {
      await apiFetch(`/api/${tenant}/app/repairbuddy/estimates/${estimateId}/send`, {
        method: "POST",
      });
      notify.success("Estimate sent.");
      await refreshEstimate();
    } catch (e) {
      if (e instanceof ApiError) {
        setError(e.message);
      } else {
        setError(e instanceof Error ? e.message : "Failed to send estimate.");
      }
    } finally {
      setBusy(false);
    }
  }

  return (
    <RequireAuth requiredPermission="estimates.manage">
      <div className="space-y-6">
        <PageHeader
          title="New estimate"
          description={step === 1 ? "Create an estimate and assign a customer." : step === 2 ? "Add devices and line items." : "Review and finish."}
          actions={
            <Button
              variant="outline"
              size="sm"
              onClick={() => {
                router.back();
              }}
              disabled={busy}
            >
              Back
            </Button>
          }
        />

        {error ? <div className="text-sm text-red-600">{error}</div> : null}

        {isStep1 ? (
          <Card className="shadow-none">
            <CardContent className="pt-5">
              <div className="grid gap-4">
                <FormRow label="Title" fieldId="estimate_title" required description="Internal label for the estimate.">
                  {({ describedBy, invalid }) => (
                    <Input
                      id="estimate_title"
                      value={title}
                      onChange={(e) => setTitle(e.target.value)}
                      required
                      disabled={busy}
                      maxLength={255}
                      aria-describedby={describedBy}
                      aria-invalid={invalid}
                    />
                  )}
                </FormRow>

                <FormRow label="Case number" fieldId="estimate_case_number" description="Optional. Leave blank to auto-generate.">
                  {({ describedBy, invalid }) => (
                    <Input
                      id="estimate_case_number"
                      value={caseNumber}
                      onChange={(e) => setCaseNumber(e.target.value)}
                      disabled={busy}
                      maxLength={64}
                      aria-describedby={describedBy}
                      aria-invalid={invalid}
                    />
                  )}
                </FormRow>

                <div className="grid gap-2">
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Customer</div>
                  <div className="flex flex-wrap gap-2">
                    <Button
                      type="button"
                      size="sm"
                      variant={customerMode === "existing" ? "primary" : "outline"}
                      onClick={() => setCustomerMode("existing")}
                      disabled={busy}
                    >
                      Existing
                    </Button>
                    <Button
                      type="button"
                      size="sm"
                      variant={customerMode === "new" ? "primary" : "outline"}
                      onClick={() => setCustomerMode("new")}
                      disabled={busy}
                    >
                      New
                    </Button>
                  </div>
                </div>

                {customerMode === "existing" ? (
                  <div className="grid gap-2">
                    <div className="text-xs text-zinc-600">Select customer</div>
                    <select
                      className="h-10 w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 text-sm"
                      value={typeof customerId === "number" ? String(customerId) : ""}
                      onChange={(e) => {
                        const v = e.target.value;
                        setCustomerId(v ? Number(v) : null);
                      }}
                      disabled={busy}
                    >
                      <option value="">Select...</option>
                      {sortedClients.map((c) => (
                        <option key={c.id} value={String(c.id)}>
                          {c.email ? `${c.name} (${c.email})` : c.name}
                        </option>
                      ))}
                    </select>
                  </div>
                ) : (
                  <div className="grid gap-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                      <FormRow label="Name" fieldId="customer_name" required>
                        {({ describedBy, invalid }) => (
                          <Input
                            id="customer_name"
                            value={customerCreateName}
                            onChange={(e) => setCustomerCreateName(e.target.value)}
                            required
                            disabled={busy}
                            maxLength={255}
                            aria-describedby={describedBy}
                            aria-invalid={invalid}
                          />
                        )}
                      </FormRow>
                      <FormRow label="Email" fieldId="customer_email" required>
                        {({ describedBy, invalid }) => (
                          <Input
                            id="customer_email"
                            type="email"
                            value={customerCreateEmail}
                            onChange={(e) => setCustomerCreateEmail(e.target.value)}
                            required
                            disabled={busy}
                            maxLength={255}
                            aria-describedby={describedBy}
                            aria-invalid={invalid}
                          />
                        )}
                      </FormRow>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                      <FormRow label="Phone" fieldId="customer_phone">
                        {({ describedBy, invalid }) => (
                          <Input
                            id="customer_phone"
                            value={customerCreatePhone}
                            onChange={(e) => setCustomerCreatePhone(e.target.value)}
                            disabled={busy}
                            maxLength={64}
                            aria-describedby={describedBy}
                            aria-invalid={invalid}
                          />
                        )}
                      </FormRow>
                      <FormRow label="Company" fieldId="customer_company">
                        {({ describedBy, invalid }) => (
                          <Input
                            id="customer_company"
                            value={customerCreateCompany}
                            onChange={(e) => setCustomerCreateCompany(e.target.value)}
                            disabled={busy}
                            maxLength={255}
                            aria-describedby={describedBy}
                            aria-invalid={invalid}
                          />
                        )}
                      </FormRow>
                    </div>
                  </div>
                )}

                <div className="flex items-center justify-end gap-2 pt-2">
                  <Button
                    variant="outline"
                    onClick={() => {
                      if (typeof tenant === "string" && tenant.length > 0) {
                        router.push(`/app/${tenant}/estimates`);
                      }
                    }}
                    disabled={busy}
                  >
                    Cancel
                  </Button>
                  <Button
                    onClick={() => void createEstimate()}
                    disabled={busy}
                  >
                    {busy ? "Saving..." : "Continue"}
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        ) : null}

        {isStep2 ? (
          <div className="space-y-6">
            <Card className="shadow-none">
              <CardContent className="pt-5">
                <div className="text-sm font-semibold text-[var(--rb-text)]">Attach devices</div>
                <div className="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end">
                  <div className="flex-1">
                    <div className="mb-1 text-xs text-zinc-600">Customer device</div>
                    <select
                      className="h-10 w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 text-sm"
                      value={typeof selectedCustomerDeviceId === "number" ? String(selectedCustomerDeviceId) : ""}
                      onChange={(e) => {
                        const v = e.target.value;
                        setSelectedCustomerDeviceId(v ? Number(v) : null);
                      }}
                      disabled={busy}
                    >
                      <option value="">Select device...</option>
                      {sortedCustomerDevices.map((d) => (
                        <option key={d.id} value={String(d.id)}>
                          {d.serial ? `${d.label} (Serial: ${d.serial})` : d.label}
                        </option>
                      ))}
                    </select>
                  </div>
                  <Button onClick={() => void attachCustomerDevice()} disabled={busy || typeof selectedCustomerDeviceId !== "number"}>
                    Attach
                  </Button>
                </div>

                <div className="mt-4 space-y-2">
                  {estimateDevices.length === 0 ? <div className="text-sm text-zinc-600">No devices attached.</div> : null}
                  {estimateDevices.map((d) => (
                    <div key={d.id} className="flex items-start justify-between gap-3 rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-3">
                      <div className="min-w-0">
                        <div className="truncate text-sm font-semibold text-[var(--rb-text)]">{d.label}</div>
                        <div className="mt-1 text-xs text-zinc-500">{d.serial ? `Serial: ${d.serial}` : ""}</div>
                      </div>
                      <Button variant="outline" size="sm" disabled={busy} onClick={() => void removeEstimateDevice(d.id)}>
                        Remove
                      </Button>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>

            <Card className="shadow-none">
              <CardContent className="pt-5">
                <div className="text-sm font-semibold text-[var(--rb-text)]">Line items</div>

                <div className="mt-3 grid gap-3 sm:grid-cols-2">
                  <div>
                    <div className="mb-1 text-xs text-zinc-600">Type</div>
                    <select
                      className="h-10 w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 text-sm"
                      value={newItemType}
                      onChange={(e) => {
                        const v = e.target.value as "service" | "part" | "fee" | "discount";
                        setNewItemType(v);
                        setNewItemRefId(null);
                        setNewItemName("");
                      }}
                      disabled={busy}
                    >
                      <option value="service">Service</option>
                      <option value="part">Part</option>
                      <option value="fee">Fee</option>
                      <option value="discount">Discount</option>
                    </select>
                  </div>

                  {newItemType === "service" ? (
                    <div>
                      <div className="mb-1 text-xs text-zinc-600">Service</div>
                      <select
                        className="h-10 w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 text-sm"
                        value={typeof newItemRefId === "number" ? String(newItemRefId) : ""}
                        onChange={(e) => {
                          const v = e.target.value;
                          setNewItemRefId(v ? Number(v) : null);
                        }}
                        disabled={busy}
                      >
                        <option value="">Select service...</option>
                        {sortedServices.map((s) => (
                          <option key={s.id} value={String(s.id)}>
                            {s.name}
                          </option>
                        ))}
                      </select>
                    </div>
                  ) : null}

                  {newItemType === "part" ? (
                    <div>
                      <div className="mb-1 text-xs text-zinc-600">Part</div>
                      <select
                        className="h-10 w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 text-sm"
                        value={typeof newItemRefId === "number" ? String(newItemRefId) : ""}
                        onChange={(e) => {
                          const v = e.target.value;
                          setNewItemRefId(v ? Number(v) : null);
                        }}
                        disabled={busy}
                      >
                        <option value="">Select part...</option>
                        {sortedParts.map((p) => (
                          <option key={p.id} value={String(p.id)}>
                            {p.name}
                          </option>
                        ))}
                      </select>
                    </div>
                  ) : null}

                  {newItemType === "fee" || newItemType === "discount" ? (
                    <div>
                      <div className="mb-1 text-xs text-zinc-600">Name</div>
                      <Input value={newItemName} onChange={(e) => setNewItemName(e.target.value)} disabled={busy} maxLength={255} />
                    </div>
                  ) : null}

                  <div>
                    <div className="mb-1 text-xs text-zinc-600">Qty</div>
                    <Input value={newItemQty} onChange={(e) => setNewItemQty(e.target.value)} disabled={busy} />
                  </div>
                  <div>
                    <div className="mb-1 text-xs text-zinc-600">Unit price (optional)</div>
                    <Input value={newItemPrice} onChange={(e) => setNewItemPrice(e.target.value)} disabled={busy} />
                  </div>
                </div>

                <div className="mt-3 flex items-center justify-end">
                  <Button onClick={() => void addItem()} disabled={busy}>
                    Add item
                  </Button>
                </div>

                <div className="mt-4 space-y-2">
                  {(estimate?.items ?? []).length === 0 ? <div className="text-sm text-zinc-600">No items added.</div> : null}
                  {(estimate?.items ?? []).map((line) => (
                    <div key={line.id} className="flex items-start justify-between gap-3 rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-3">
                      <div className="min-w-0">
                        <div className="truncate text-sm font-semibold text-[var(--rb-text)]">{line.name}</div>
                        <div className="mt-1 text-xs text-zinc-500">Qty: {line.qty}</div>
                      </div>
                      <div className="flex items-center gap-2">
                        <div className="text-sm font-semibold text-[var(--rb-text)] whitespace-nowrap">
                          {(line.qty * line.unit_price.amount_cents) / 100} {line.unit_price.currency}
                        </div>
                        <Button variant="outline" size="sm" disabled={busy} onClick={() => void removeItem(line.id)}>
                          Remove
                        </Button>
                      </div>
                    </div>
                  ))}
                </div>

                <div className="mt-4 flex items-center justify-between border-t border-[var(--rb-border)] pt-3">
                  <div className="text-sm text-zinc-700">Total</div>
                  <div className="text-sm font-semibold text-[var(--rb-text)]">
                    {estimate ? `${(estimate.totals.total_cents ?? 0) / 100} ${estimate.totals.currency ?? "USD"}` : "—"}
                  </div>
                </div>
              </CardContent>
            </Card>

            <div className="flex items-center justify-end gap-2">
              <Button variant="outline" disabled={busy} onClick={() => setStep(1)}>
                Back
              </Button>
              <Button disabled={busy || typeof estimateId !== "number"} onClick={() => setStep(3)}>
                Review
              </Button>
            </div>
          </div>
        ) : null}

        {isStep3 ? (
          <Card className="shadow-none">
            <CardContent className="pt-5">
              <div className="space-y-4">
                <div className="text-sm font-semibold text-[var(--rb-text)]">Review</div>

                <div className="grid gap-4 sm:grid-cols-2">
                  <div>
                    <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Customer</div>
                    <div className="mt-1 text-sm text-zinc-700">{estimate?.customer?.name ?? "—"}</div>
                    <div className="mt-1 text-xs text-zinc-500">{estimate?.customer?.email ?? ""}</div>
                  </div>
                  <div>
                    <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Total</div>
                    <div className="mt-1 text-sm font-semibold text-[var(--rb-text)]">
                      {estimate ? `${(estimate.totals.total_cents ?? 0) / 100} ${estimate.totals.currency ?? "USD"}` : "—"}
                    </div>
                  </div>
                </div>

                <div>
                  <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Devices</div>
                  <div className="mt-2 space-y-2">
                    {estimateDevices.length === 0 ? <div className="text-sm text-zinc-600">None</div> : null}
                    {estimateDevices.map((d) => (
                      <div key={d.id} className="text-sm text-zinc-700">
                        {d.label}
                      </div>
                    ))}
                  </div>
                </div>

                <div>
                  <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Items</div>
                  <div className="mt-2 space-y-2">
                    {(estimate?.items ?? []).length === 0 ? <div className="text-sm text-zinc-600">None</div> : null}
                    {(estimate?.items ?? []).map((line) => (
                      <div key={line.id} className="flex items-center justify-between gap-3 text-sm">
                        <div className="min-w-0 truncate text-zinc-700">
                          {line.qty} × {line.name}
                        </div>
                        <div className="whitespace-nowrap font-semibold text-[var(--rb-text)]">
                          {(line.qty * line.unit_price.amount_cents) / 100} {line.unit_price.currency}
                        </div>
                      </div>
                    ))}
                  </div>
                </div>

                <div className="flex items-center justify-end gap-2 pt-2">
                  <Button variant="outline" disabled={busy} onClick={() => setStep(2)}>
                    Back
                  </Button>
                  <Button
                    variant="outline"
                    disabled={busy || !canSendEmail}
                    onClick={() => void sendNow()}
                  >
                    Send
                  </Button>
                  <Button
                    disabled={busy || typeof estimateId !== "number"}
                    onClick={() => {
                      if (typeof tenant !== "string" || tenant.length === 0) return;
                      if (typeof estimateId !== "number") return;
                      router.replace(`/app/${tenant}/estimates/${estimateId}`);
                    }}
                  >
                    Finish
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        ) : null}
      </div>
    </RequireAuth>
  );
}
