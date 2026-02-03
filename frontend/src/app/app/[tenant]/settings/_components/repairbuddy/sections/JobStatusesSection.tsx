"use client";

import React from "react";
import { useRouter } from "next/navigation";
import { Badge, type BadgeVariant } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { Input } from "@/components/ui/Input";
import { Modal } from "@/components/ui/Modal";
import { Select } from "@/components/ui/Select";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/Tabs";
import { ApiError, apiFetch } from "@/lib/api";
import { useAuth } from "@/lib/auth";
import { SectionShell } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/SectionShell";

type ApiJobStatus = {
  id: number;
  slug: string;
  label: string;
  invoice_label?: string | null;
  email_enabled?: boolean;
  sms_enabled?: boolean;
  is_active?: boolean;
  color?: string | null;
  sort_order?: number | null;
};

type ApiPaymentStatus = {
  id: number;
  slug: string;
  label: string;
  is_active?: boolean;
  color?: string | null;
  sort_order?: number | null;
};

type Domain = "job" | "payment";

const badgeOptions: Array<{ value: "" | BadgeVariant; label: string }> = [
  { value: "", label: "Default" },
  { value: "info", label: "Info" },
  { value: "success", label: "Success" },
  { value: "warning", label: "Warning" },
  { value: "danger", label: "Danger" },
];

function badgeVariantFromValue(value: unknown): BadgeVariant {
  if (value === "info" || value === "success" || value === "warning" || value === "danger" || value === "default") {
    return value;
  }
  return "default";
}

function normalizeNullableString(value: unknown): string | null {
  if (typeof value !== "string") return null;
  const trimmed = value.trim();
  return trimmed.length > 0 ? trimmed : null;
}

function normalizeNullableInt(value: unknown): number | null {
  if (typeof value !== "string" && typeof value !== "number") return null;
  const raw = String(value).trim();
  if (raw.length === 0) return null;
  const n = Number.parseInt(raw, 10);
  if (!Number.isFinite(n)) return null;
  return Math.max(0, Math.min(100000, n));
}

export function JobStatusesSection({ tenantSlug }: { tenantSlug: string }) {
  const auth = useAuth();
  const router = useRouter();

  const [loading, setLoading] = React.useState(true);
  const [busy, setBusy] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);
  const [status, setStatus] = React.useState<string | null>(null);

  const [activeTab, setActiveTab] = React.useState<Domain>("job");

  const [jobStatuses, setJobStatuses] = React.useState<ApiJobStatus[]>([]);
  const [paymentStatuses, setPaymentStatuses] = React.useState<ApiPaymentStatus[]>([]);

  const [query, setQuery] = React.useState("");
  const [pageIndex, setPageIndex] = React.useState(0);
  const [pageSize, setPageSize] = React.useState(10);

  const [editOpen, setEditOpen] = React.useState(false);
  const [editDomain, setEditDomain] = React.useState<Domain>("job");
  const [editSlug, setEditSlug] = React.useState<string>("");
  const [editLabel, setEditLabel] = React.useState<string>("");
  const [editColor, setEditColor] = React.useState<string>("");
  const [editSortOrder, setEditSortOrder] = React.useState<string>("");

  const openEdit = React.useCallback(
    (domain: Domain, row: { slug: string; label: string; color?: string | null; sort_order?: number | null }) => {
      setEditDomain(domain);
      setEditSlug(row.slug);
      setEditLabel(row.label ?? "");
      setEditColor(typeof row.color === "string" ? row.color : "");
      setEditSortOrder(typeof row.sort_order === "number" ? String(row.sort_order) : "");
      setEditOpen(true);
    },
    []
  );

  const loadAll = React.useCallback(async () => {
    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) {
      setError("Business is missing.");
      return;
    }

    try {
      setLoading(true);
      setError(null);
      setStatus(null);

      const [jobsRes, paymentsRes] = await Promise.all([
        apiFetch<{ job_statuses: ApiJobStatus[] }>(`/api/${tenantSlug}/app/repairbuddy/job-statuses`),
        apiFetch<{ payment_statuses: ApiPaymentStatus[] }>(`/api/${tenantSlug}/app/repairbuddy/payment-statuses`),
      ]);

      setJobStatuses(Array.isArray(jobsRes.job_statuses) ? jobsRes.job_statuses : []);
      setPaymentStatuses(Array.isArray(paymentsRes.payment_statuses) ? paymentsRes.payment_statuses : []);
    } catch (e) {
      if (e instanceof ApiError && e.status === 428 && typeof tenantSlug === "string" && tenantSlug.length > 0) {
        const next = `/app/${tenantSlug}/business-settings?section=job-statuses`;
        router.replace(`/app/${tenantSlug}/branches/select?next=${encodeURIComponent(next)}`);
        return;
      }
      setError(e instanceof Error ? e.message : "Failed to load statuses.");
    } finally {
      setLoading(false);
    }
  }, [router, tenantSlug]);

  React.useEffect(() => {
    if (auth.loading) return;
    if (!auth.isAuthenticated) return;
    if (!auth.can("jobs.view")) {
      router.replace("/app");
      return;
    }
    void loadAll();
  }, [auth, loadAll, router]);

  const activeRows = React.useMemo(() => {
    const rows = activeTab === "job" ? jobStatuses : paymentStatuses;
    const needle = query.trim().toLowerCase();
    if (!needle) return rows;
    return rows.filter((s) => `${s.slug} ${s.label} ${s.color ?? ""} ${s.sort_order ?? ""}`.toLowerCase().includes(needle));
  }, [activeTab, jobStatuses, paymentStatuses, query]);

  const totalRows = activeRows.length;
  const pageRows = React.useMemo(() => {
    const start = pageIndex * pageSize;
    const end = start + pageSize;
    return activeRows.slice(start, end);
  }, [activeRows, pageIndex, pageSize]);

  const jobColumns = React.useMemo<Array<DataTableColumn<ApiJobStatus>>>(
    () => [
      {
        id: "label",
        header: "Label",
        cell: (row) => (
          <div className="min-w-0">
            <div className="flex items-center gap-2">
              <Badge variant={badgeVariantFromValue(row.color)}>{row.label}</Badge>
              {typeof row.color === "string" && row.color.length > 0 ? <span className="text-xs text-zinc-500">({row.color})</span> : null}
            </div>
            <div className="mt-1 truncate text-xs text-zinc-600">{row.slug}</div>
          </div>
        ),
        className: "min-w-[280px]",
      },
      {
        id: "invoice",
        header: "Invoice label",
        cell: (row) => <div className="text-sm text-zinc-700">{row.invoice_label ?? "—"}</div>,
        className: "min-w-[200px]",
      },
      {
        id: "order",
        header: "Order",
        cell: (row) => <div className="text-sm text-zinc-700">{typeof row.sort_order === "number" ? row.sort_order : "—"}</div>,
        className: "whitespace-nowrap",
      },
      {
        id: "active",
        header: "Active",
        cell: (row) => <div className="text-sm text-zinc-700">{row.is_active === false ? "No" : "Yes"}</div>,
        className: "whitespace-nowrap",
      },
      {
        id: "actions",
        header: "Actions",
        cell: (row) => (
          <div className="whitespace-nowrap">
            <Button variant="outline" size="sm" onClick={() => openEdit("job", row)}>
              Edit
            </Button>
          </div>
        ),
        className: "whitespace-nowrap",
      },
    ],
    [openEdit]
  );

  const paymentColumns = React.useMemo<Array<DataTableColumn<ApiPaymentStatus>>>(
    () => [
      {
        id: "label",
        header: "Label",
        cell: (row) => (
          <div className="min-w-0">
            <div className="flex items-center gap-2">
              <Badge variant={badgeVariantFromValue(row.color)}>{row.label}</Badge>
              {typeof row.color === "string" && row.color.length > 0 ? <span className="text-xs text-zinc-500">({row.color})</span> : null}
            </div>
            <div className="mt-1 truncate text-xs text-zinc-600">{row.slug}</div>
          </div>
        ),
        className: "min-w-[280px]",
      },
      {
        id: "order",
        header: "Order",
        cell: (row) => <div className="text-sm text-zinc-700">{typeof row.sort_order === "number" ? row.sort_order : "—"}</div>,
        className: "whitespace-nowrap",
      },
      {
        id: "active",
        header: "Active",
        cell: (row) => <div className="text-sm text-zinc-700">{row.is_active === false ? "No" : "Yes"}</div>,
        className: "whitespace-nowrap",
      },
      {
        id: "actions",
        header: "Actions",
        cell: (row) => (
          <div className="whitespace-nowrap">
            <Button variant="outline" size="sm" onClick={() => openEdit("payment", row)}>
              Edit
            </Button>
          </div>
        ),
        className: "whitespace-nowrap",
      },
    ],
    [openEdit]
  );

  async function saveOverride(mode: "save" | "reset") {
    if (busy) return;
    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) {
      setError("Business is missing.");
      return;
    }

    const endpoint =
      editDomain === "job"
        ? `/api/${tenantSlug}/app/repairbuddy/job-statuses/${encodeURIComponent(editSlug)}`
        : `/api/${tenantSlug}/app/repairbuddy/payment-statuses/${encodeURIComponent(editSlug)}`;

    const payload =
      mode === "reset"
        ? { label: null, color: null, sort_order: null }
        : {
            label: normalizeNullableString(editLabel),
            color: normalizeNullableString(editColor),
            sort_order: normalizeNullableInt(editSortOrder),
          };

    try {
      setBusy(true);
      setError(null);
      setStatus(null);

      await apiFetch(endpoint, {
        method: "PATCH",
        body: payload,
      });

      setEditOpen(false);
      setStatus(mode === "reset" ? "Status reset to defaults." : "Status saved.");
      await loadAll();
    } catch (e) {
      if (e instanceof ApiError && e.status === 428 && typeof tenantSlug === "string" && tenantSlug.length > 0) {
        const next = `/app/${tenantSlug}/business-settings?section=job-statuses`;
        router.replace(`/app/${tenantSlug}/branches/select?next=${encodeURIComponent(next)}`);
        return;
      }

      setError(e instanceof Error ? e.message : "Failed to save status.");
    } finally {
      setBusy(false);
    }
  }

  return (
    <SectionShell title="Statuses" description="Customize display labels, colors, and ordering for job and payment statuses.">
      <div className="flex flex-wrap items-center justify-between gap-3">
        {status ? <div className="text-sm text-zinc-600">{status}</div> : <div />}
        <Button variant="outline" size="sm" onClick={() => void loadAll()} disabled={loading || busy}>
          Refresh
        </Button>
      </div>

      {error ? <div className="text-sm text-red-600">{error}</div> : null}

      <Tabs
        value={activeTab}
        onValueChange={(next) => {
          if (next === "job" || next === "payment") {
            setActiveTab(next);
            setQuery("");
            setPageIndex(0);
          }
        }}
      >
        <TabsList>
          <TabsTrigger value="job">Job statuses</TabsTrigger>
          <TabsTrigger value="payment">Payment statuses</TabsTrigger>
        </TabsList>

        <TabsContent value="job">
          <Card className="shadow-none">
            <CardContent className="pt-5">
              <DataTable
                title={`Job statuses · ${tenantSlug}`}
                data={pageRows as ApiJobStatus[]}
                columns={jobColumns}
                getRowId={(row) => row.id}
                emptyMessage="No job statuses."
                server={{
                  query,
                  onQueryChange: (value) => {
                    setQuery(value);
                    setPageIndex(0);
                  },
                  pageIndex,
                  onPageIndexChange: setPageIndex,
                  pageSize,
                  onPageSizeChange: (value) => {
                    setPageSize(value);
                    setPageIndex(0);
                  },
                  totalRows,
                }}
              />
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="payment">
          <Card className="shadow-none">
            <CardContent className="pt-5">
              <DataTable
                title={`Payment statuses · ${tenantSlug}`}
                data={pageRows as ApiPaymentStatus[]}
                columns={paymentColumns}
                getRowId={(row) => row.id}
                emptyMessage="No payment statuses."
                server={{
                  query,
                  onQueryChange: (value) => {
                    setQuery(value);
                    setPageIndex(0);
                  },
                  pageIndex,
                  onPageIndexChange: setPageIndex,
                  pageSize,
                  onPageSizeChange: (value) => {
                    setPageSize(value);
                    setPageIndex(0);
                  },
                  totalRows,
                }}
              />
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>

      <Modal
        open={editOpen}
        onClose={() => {
          if (busy) return;
          setEditOpen(false);
        }}
        title={editDomain === "job" ? `Edit job status` : `Edit payment status`}
        footer={
          <div className="flex flex-wrap items-center justify-between gap-2">
            <Button variant="outline" disabled={busy} onClick={() => void saveOverride("reset")}>
              Reset to defaults
            </Button>
            <div className="flex items-center gap-2">
              <Button variant="outline" disabled={busy} onClick={() => setEditOpen(false)}>
                Cancel
              </Button>
              <Button disabled={busy} onClick={() => void saveOverride("save")}>
                {busy ? "Saving..." : "Save"}
              </Button>
            </div>
          </div>
        }
      >
        <div className="space-y-4">
          <div className="space-y-1">
            <label className="text-sm font-medium">Label</label>
            <Input value={editLabel} onChange={(e) => setEditLabel(e.target.value)} placeholder="e.g. In Service" maxLength={255} />
            <div className="text-xs text-zinc-500">Leave blank to fall back to the default label.</div>
          </div>

          <div className="space-y-1">
            <label className="text-sm font-medium">Color</label>
            <Select value={editColor} onChange={(e) => setEditColor(e.target.value)}>
              {badgeOptions.map((opt) => (
                <option key={opt.label + opt.value} value={opt.value}>
                  {opt.label}
                </option>
              ))}
            </Select>
            <div className="text-xs text-zinc-500">Used to style badges in the UI.</div>
          </div>

          <div className="space-y-1">
            <label className="text-sm font-medium">Sort order</label>
            <Input value={editSortOrder} onChange={(e) => setEditSortOrder(e.target.value)} placeholder="e.g. 10" inputMode="numeric" />
            <div className="text-xs text-zinc-500">Lower numbers appear first. Leave blank to use the default order.</div>
          </div>
        </div>
      </Modal>
    </SectionShell>
  );
}
