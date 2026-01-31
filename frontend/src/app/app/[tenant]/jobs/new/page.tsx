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

  const [statuses, setStatuses] = useState<ApiJobStatus[]>([]);
  const [paymentStatuses, setPaymentStatuses] = useState<ApiPaymentStatus[]>([]);
  const [clients, setClients] = useState<ApiClient[]>([]);

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
          title: trimmedTitle,
          status_slug: statusSlug.trim() !== "" ? statusSlug : null,
          payment_status_slug: paymentStatusSlug.trim() !== "" ? paymentStatusSlug : null,
          priority: priority.trim() !== "" ? priority.trim() : null,
          customer_id: typeof customerId === "number" ? customerId : null,
        },
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

              <FormRow label="Customer" fieldId="job_customer">
                <select
                  id="job_customer"
                  className="w-full rounded-[var(--rb-radius-sm)] border border-zinc-300 bg-white px-3 py-2 text-sm text-[var(--rb-text)]"
                  value={typeof customerId === "number" ? String(customerId) : ""}
                  onChange={(e) => {
                    const raw = e.target.value;
                    if (!raw) {
                      setCustomerId(null);
                      return;
                    }
                    const n = Number(raw);
                    setCustomerId(Number.isFinite(n) ? n : null);
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
            </form>
          </CardContent>
        </Card>
      </div>
    </RequireAuth>
  );
}
