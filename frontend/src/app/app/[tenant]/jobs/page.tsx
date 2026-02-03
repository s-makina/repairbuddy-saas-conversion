"use client";

import React from "react";
import { useParams, useRouter } from "next/navigation";
import { CreditCard, Download, Eye, Mail, MessageSquare, PiggyBank, Printer, Repeat2 } from "lucide-react";
import { Button } from "@/components/ui/Button";
import { Badge } from "@/components/ui/Badge";
import { Card, CardContent } from "@/components/ui/Card";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { DropdownMenu, DropdownMenuItem, DropdownMenuSeparator } from "@/components/ui/DropdownMenu";
import { ListPageShell } from "@/components/shells/ListPageShell";
import { apiFetch, ApiError } from "@/lib/api";
import { formatMoney } from "@/lib/money";

type JobStatusKey = string;

type ApiJob = {
  id: number;
  case_number: string;
  title: string;
  status: JobStatusKey;
  payment_status?: string | null;
  priority?: string | null;
  customer?: { id: number; name: string; email: string; phone: string | null; company: string | null } | null;
  assigned_technicians?: Array<{ id: number; name: string; email: string }>;
  job_devices?: Array<{ id: number; customer_device_id: number; label: string | null; serial: string | null }>;
  pickup_date?: string | null;
  delivery_date?: string | null;
  next_service_date?: string | null;
  totals?: { currency: string; subtotal_cents: number; tax_cents: number; total_cents: number } | null;
  updated_at: string;
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

type JobsPayload = {
  jobs: ApiJob[];
  meta?: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
};

type JobsStatsPayload = {
  total: number;
  open?: number;
  urgent?: number;
  overdue?: number;
  payment_due?: number;
  by_status: Record<string, number>;
};

function statusBadgeVariant(status: JobStatusKey): "default" | "info" | "success" | "warning" | "danger" {
  if (status === "delivered" || status === "completed") return "success";
  if (status === "ready") return "warning";
  if (status === "cancelled") return "danger";
  if (status === "in_process") return "info";
  return "default";
}

function paymentBadgeVariant(status: string | null | undefined): "default" | "info" | "success" | "warning" | "danger" {
  if (!status) return "default";
  if (status === "paid") return "success";
  if (status === "partial") return "warning";
  if (status === "unpaid" || status === "due") return "danger";
  return "default";
}

function formatShortDate(value: string | null | undefined): string {
  if (!value) return "";
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return "";
  return d.toLocaleDateString();
}

export default function TenantJobsPage() {
  const router = useRouter();
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [jobs, setJobs] = React.useState<ApiJob[]>([]);
  const [q, setQ] = React.useState<string>("");
  const [statusLabels, setStatusLabels] = React.useState<Record<string, string>>({});
  const [paymentStatusLabels, setPaymentStatusLabels] = React.useState<Record<string, string>>({});

  const [statusFilter, setStatusFilter] = React.useState<string>("all");
  const [paymentFilter, setPaymentFilter] = React.useState<string>("all");
  const [priorityFilter, setPriorityFilter] = React.useState<string>("all");
  const [overdueFilter, setOverdueFilter] = React.useState<boolean>(false);

  const [sort, setSort] = React.useState<{ id: string; dir: "asc" | "desc" } | null>(null);
  const [totalJobs, setTotalJobs] = React.useState<number>(0);

  const [statsLoading, setStatsLoading] = React.useState<boolean>(true);
  const [statsError, setStatsError] = React.useState<string | null>(null);
  const [statsOpen, setStatsOpen] = React.useState<number>(0);
  const [statsUrgent, setStatsUrgent] = React.useState<number>(0);
  const [statsOverdue, setStatsOverdue] = React.useState<number>(0);
  const [statsPaymentDue, setStatsPaymentDue] = React.useState<number>(0);

  const [pageIndex, setPageIndex] = React.useState(0);
  const [pageSize, setPageSize] = React.useState(10);

  React.useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);

        if (typeof tenantSlug !== "string" || tenantSlug.length === 0) {
          throw new Error("Business is missing.");
        }

        const qs = new URLSearchParams();
        const needle = q.trim();
        if (needle !== "") qs.set("q", needle);

        if (statusFilter !== "all") qs.set("status", statusFilter);
        if (paymentFilter !== "all") qs.set("payment_status", paymentFilter);
        if (priorityFilter !== "all") qs.set("priority", priorityFilter);
        if (overdueFilter) qs.set("overdue", "1");

        qs.set("page", String(pageIndex + 1));
        qs.set("per_page", String(pageSize));

        if (sort?.id && sort?.dir) {
          qs.set("sort", sort.id);
          qs.set("dir", sort.dir);
        }

        const [jobsRes, statusesRes, paymentStatusesRes] = await Promise.all([
          apiFetch<JobsPayload>(`/api/${tenantSlug}/app/repairbuddy/jobs?${qs.toString()}`),
          apiFetch<{ job_statuses: ApiJobStatus[] }>(`/api/${tenantSlug}/app/repairbuddy/job-statuses`),
          apiFetch<{ payment_statuses: ApiPaymentStatus[] }>(`/api/${tenantSlug}/app/repairbuddy/payment-statuses`),
        ]);
        if (!alive) return;

        setJobs(Array.isArray(jobsRes.jobs) ? jobsRes.jobs : []);
        setTotalJobs(typeof jobsRes.meta?.total === "number" ? jobsRes.meta.total : 0);

        const next: Record<string, string> = {};
        for (const s of Array.isArray(statusesRes.job_statuses) ? statusesRes.job_statuses : []) {
          next[s.slug] = s.label;
        }
        setStatusLabels(next);

        const nextPayment: Record<string, string> = {};
        for (const s of Array.isArray(paymentStatusesRes.payment_statuses) ? paymentStatusesRes.payment_statuses : []) {
          nextPayment[s.slug] = s.label;
        }
        setPaymentStatusLabels(nextPayment);
      } catch (e) {
        if (!alive) return;
        if (e instanceof ApiError && e.status === 428 && typeof tenantSlug === "string" && tenantSlug.length > 0) {
          const next = `/app/${tenantSlug}/jobs`;
          router.replace(`/app/${tenantSlug}/branches/select?next=${encodeURIComponent(next)}`);
          return;
        }

        setError(e instanceof Error ? e.message : "Failed to load jobs.");
      } finally {
        if (!alive) return;
        setLoading(false);
      }
    }

    void load();

    return () => {
      alive = false;
    };
  }, [router, tenantSlug, q, pageIndex, pageSize, sort, statusFilter, paymentFilter, priorityFilter, overdueFilter]);

  React.useEffect(() => {
    let alive = true;

    async function loadStats() {
      try {
        setStatsLoading(true);
        setStatsError(null);

        if (typeof tenantSlug !== "string" || tenantSlug.length === 0) {
          throw new Error("Business is missing.");
        }

        const qs = new URLSearchParams();
        const needle = q.trim();
        if (needle !== "") qs.set("q", needle);
        if (paymentFilter !== "all") qs.set("payment_status", paymentFilter);
        if (priorityFilter !== "all") qs.set("priority", priorityFilter);

        const res = await apiFetch<JobsStatsPayload>(`/api/${tenantSlug}/app/repairbuddy/jobs/stats?${qs.toString()}`);
        if (!alive) return;

        setStatsOpen(typeof res.open === "number" ? res.open : 0);
        setStatsUrgent(typeof res.urgent === "number" ? res.urgent : 0);
        setStatsOverdue(typeof res.overdue === "number" ? res.overdue : 0);
        setStatsPaymentDue(typeof res.payment_due === "number" ? res.payment_due : 0);
      } catch (e) {
        if (!alive) return;
        setStatsError(e instanceof Error ? e.message : "Failed to load stats.");
        setStatsOpen(0);
        setStatsUrgent(0);
        setStatsOverdue(0);
        setStatsPaymentDue(0);
      } finally {
        if (!alive) return;
        setStatsLoading(false);
      }
    }

    void loadStats();

    return () => {
      alive = false;
    };
  }, [tenantSlug, q, paymentFilter, priorityFilter]);

  const columns = React.useMemo<Array<DataTableColumn<ApiJob>>>(
    () => [
      {
        id: "id",
        header: "ID",
        sortId: "id",
        cell: (row) => <div className="text-sm font-semibold text-[var(--rb-text)]">{row.id}</div>,
        className: "whitespace-nowrap",
      },
      {
        id: "case_tech",
        header: "Case/Tech",
        sortId: "case_number",
        cell: (row) => {
          const techNames = Array.isArray(row.assigned_technicians)
            ? row.assigned_technicians
                .map((t) => (typeof t?.name === "string" ? t.name : ""))
                .map((v) => v.trim())
                .filter((v) => v.length > 0)
            : [];

          return (
            <div className="min-w-0">
              <div className="truncate font-semibold text-[var(--rb-text)]">{row.case_number}</div>
              <div className="truncate text-xs text-zinc-600">{techNames.length > 0 ? `Tech: ${techNames.join(", ")}` : "—"}</div>
            </div>
          );
        },
        className: "min-w-[200px] max-w-[260px]",
      },
      {
        id: "customer",
        header: "Customer",
        cell: (row) => (
          <div className="min-w-0">
            <div className="truncate text-sm font-semibold text-[var(--rb-text)]">{row.customer?.name ?? "—"}</div>
            <div className="truncate text-xs text-zinc-600">{row.customer?.email ?? ""}</div>
          </div>
        ),
        className: "min-w-[220px] max-w-[320px]",
      },
      {
        id: "devices",
        header: "Devices",
        cell: (row) => {
          const devices = Array.isArray(row.job_devices) ? row.job_devices : [];
          if (devices.length === 0) return <div className="text-sm text-zinc-600">—</div>;

          const labels = devices
            .map((d) => {
              const label = typeof d?.label === "string" ? d.label.trim() : "";
              const serial = typeof d?.serial === "string" ? d.serial.trim() : "";
              if (label && serial) return `${label} (${serial})`;
              return label || serial;
            })
            .filter((v) => v.length > 0);

          const preview = labels.slice(0, 2);
          const more = labels.length - preview.length;

          return (
            <div className="min-w-0">
              <div className="truncate text-sm text-zinc-700">{preview.join(", ") || "—"}</div>
              {more > 0 ? <div className="truncate text-xs text-zinc-600">+{more} more</div> : null}
            </div>
          );
        },
        className: "min-w-[220px] max-w-[360px]",
      },
      {
        id: "dates",
        header: "Dates",
        cell: (row) => {
          const p = formatShortDate(row.pickup_date);
          const d = formatShortDate(row.delivery_date);
          const n = formatShortDate(row.next_service_date);

          if (!p && !d && !n) return <div className="text-sm text-zinc-600">—</div>;

          return (
            <div className="text-xs text-zinc-700">
              {p ? <div className="truncate"><span className="font-semibold">P</span>: {p}</div> : null}
              {d ? <div className="truncate"><span className="font-semibold">D</span>: {d}</div> : null}
              {n ? <div className="truncate"><span className="font-semibold">N</span>: {n}</div> : null}
            </div>
          );
        },
        className: "whitespace-nowrap min-w-[140px]",
      },
      {
        id: "total",
        header: "Total",
        cell: (row) => (
          <div className="text-sm text-zinc-700">
            {formatMoney({ amountCents: row.totals?.total_cents, currency: row.totals?.currency })}
          </div>
        ),
        className: "whitespace-nowrap",
      },
      {
        id: "balance",
        header: "Balance",
        cell: (row) => {
          const total = typeof row.totals?.total_cents === "number" ? row.totals.total_cents : null;
          const currency = row.totals?.currency;
          if (total === null || !currency) return <div className="text-sm text-zinc-600">—</div>;
          const balance = row.payment_status === "paid" ? 0 : total;
          return <div className="text-sm text-zinc-700">{formatMoney({ amountCents: balance, currency })}</div>;
        },
        className: "whitespace-nowrap",
      },
      {
        id: "payment",
        header: "Payment",
        sortId: "payment_status",
        cell: (row) => (
          <Badge variant={paymentBadgeVariant(row.payment_status)}>
            {row.payment_status ? paymentStatusLabels[row.payment_status] ?? row.payment_status.replace(/_/g, " ") : "—"}
          </Badge>
        ),
        className: "whitespace-nowrap",
      },
      {
        id: "status",
        header: "Status",
        sortId: "status",
        cell: (row) => (
          <Badge variant={statusBadgeVariant(row.status)}>
            {statusLabels[row.status] ?? row.status.replace(/_/g, " ")}
          </Badge>
        ),
        className: "whitespace-nowrap",
      },
      {
        id: "priority",
        header: "Priority",
        sortId: "priority",
        cell: (row) => <div className="text-sm text-zinc-600">{row.priority ? row.priority.replace(/_/g, " ") : "—"}</div>,
        className: "whitespace-nowrap",
      },
      {
        id: "actions",
        header: <div className="text-right">Actions</div>,
        cell: (row) => (
          <div className="flex justify-end">
            <DropdownMenu
              align="right"
              trigger={({ toggle }) => (
                <Button
                  variant="outline"
                  size="sm"
                  onClick={(e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    toggle();
                  }}
                >
                  Actions
                </Button>
              )}
            >
              {({ close }) => (
                <>
                  <DropdownMenuItem
                    onSelect={() => {
                      if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
                      close();
                      router.push(`/app/${tenantSlug}/jobs/${row.id}`);
                    }}
                  >
                    <span className="flex items-center gap-2">
                      <Eye className="h-4 w-4" />
                      <span>View</span>
                    </span>
                  </DropdownMenuItem>
                  <DropdownMenuItem
                    onSelect={() => {
                      if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
                      close();
                      router.push(`/app/${tenantSlug}/jobs/${row.id}?tab=financial`);
                    }}
                  >
                    <span className="flex items-center gap-2">
                      <PiggyBank className="h-4 w-4" />
                      <span>Financial</span>
                    </span>
                  </DropdownMenuItem>
                  <DropdownMenuItem
                    onSelect={() => {
                      if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
                      close();
                      router.push(`/app/${tenantSlug}/jobs/${row.id}?tab=messages`);
                    }}
                  >
                    <span className="flex items-center gap-2">
                      <MessageSquare className="h-4 w-4" />
                      <span>Messages</span>
                    </span>
                  </DropdownMenuItem>

                  <DropdownMenuSeparator />

                  <DropdownMenuItem onSelect={() => {}} disabled>
                    <span className="flex items-center gap-2">
                      <Printer className="h-4 w-4" />
                      <span>Print invoice</span>
                    </span>
                  </DropdownMenuItem>
                  <DropdownMenuItem onSelect={() => {}} disabled>
                    <span className="flex items-center gap-2">
                      <Download className="h-4 w-4" />
                      <span>Download PDF</span>
                    </span>
                  </DropdownMenuItem>
                  <DropdownMenuItem onSelect={() => {}} disabled>
                    <span className="flex items-center gap-2">
                      <Mail className="h-4 w-4" />
                      <span>Email customer</span>
                    </span>
                  </DropdownMenuItem>

                  <DropdownMenuSeparator />

                  <DropdownMenuItem onSelect={() => {}} disabled>
                    <span className="flex items-center gap-2">
                      <Repeat2 className="h-4 w-4" />
                      <span>Duplicate job</span>
                    </span>
                  </DropdownMenuItem>
                  <DropdownMenuItem onSelect={() => {}} disabled>
                    <span className="flex items-center gap-2">
                      <CreditCard className="h-4 w-4" />
                      <span>Take payment</span>
                    </span>
                  </DropdownMenuItem>
                </>
              )}
            </DropdownMenu>
          </div>
        ),
        className: "whitespace-nowrap",
      },
    ],
    [paymentStatusLabels, router, statusLabels, tenantSlug],
  );

  const statusOptions = React.useMemo(() => {
    const opts = [
      { label: "All", value: "all" },
      { label: "Open", value: "open" },
    ];
    const entries = Object.entries(statusLabels);
    entries.sort((a, b) => a[1].localeCompare(b[1]));
    for (const [slug, label] of entries) {
      opts.push({ label, value: slug });
    }
    return opts;
  }, [statusLabels]);

  const statsCards = React.useMemo(() => {
    return [
      {
        id: "open" as const,
        label: "Open",
        value: statsOpen,
        dot: "bg-sky-500",
      },
      {
        id: "urgent" as const,
        label: "Urgent",
        value: statsUrgent,
        dot: "bg-rose-500",
      },
      {
        id: "overdue" as const,
        label: "Overdue",
        value: statsOverdue,
        dot: "bg-amber-500",
      },
      {
        id: "payment_due" as const,
        label: "Needs payment",
        value: statsPaymentDue,
        dot: "bg-violet-500",
      },
    ];
  }, [statsOpen, statsOverdue, statsPaymentDue, statsUrgent]);

  const paymentOptions = React.useMemo(() => {
    const opts = [{ label: "All", value: "all" }, { label: "Needs payment", value: "needs_payment" }];
    const entries = Object.entries(paymentStatusLabels);
    entries.sort((a, b) => a[1].localeCompare(b[1]));
    for (const [slug, label] of entries) {
      opts.push({ label, value: slug });
    }
    return opts;
  }, [paymentStatusLabels]);

  const priorityOptions = React.useMemo(
    () => [
      { label: "All", value: "all" },
      { label: "Low", value: "low" },
      { label: "Normal", value: "normal" },
      { label: "High", value: "high" },
      { label: "Urgent", value: "urgent" },
    ],
    [],
  );

  const overdueOptions = React.useMemo(
    () => [
      { label: "All", value: "all" },
      { label: "Overdue", value: "overdue" },
    ],
    [],
  );

  return (
    <ListPageShell
      title="Jobs"
      description="Track, update, and communicate on repair jobs."
      actions={
        <Button
          variant="outline"
          size="sm"
          onClick={() => {
            if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
            router.push(`/app/${tenantSlug}/jobs/new`);
          }}
        >
          New job
        </Button>
      }
      loading={loading}
      error={error}
      empty={!loading && !error && jobs.length === 0}
      emptyTitle="No jobs found"
      emptyDescription="Try adjusting your search."
    >
      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        {statsCards.map((c) => (
          <div
            key={c.id}
            className={
              "rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-4 text-left shadow-none"
            }
          >
            <div className="flex items-start justify-between gap-3">
              <div className="min-w-0">
                <div className="text-xs font-medium text-zinc-600">{c.label}</div>
              </div>
              <div className={`mt-1 h-2.5 w-2.5 shrink-0 rounded-full ${c.dot}`} aria-hidden="true" />
            </div>
            <div className="mt-1 text-2xl font-semibold text-[var(--rb-text)]">{statsLoading ? "—" : String(c.value)}</div>
          </div>
        ))}
      </div>

      {statsError ? <div className="mt-2 text-sm text-red-600">{statsError}</div> : null}

      <Card className="shadow-none">
        <CardContent className="pt-5">
          <DataTable
            title={typeof tenantSlug === "string" ? `Jobs · ${tenantSlug}` : "Jobs"}
            data={jobs}
            loading={loading}
            emptyMessage="No jobs."
            columns={columns}
            getRowId={(row) => row.id}
            search={{
              placeholder: "Search by case number, title, device, or ID...",
            }}
            columnVisibilityKey={typeof tenantSlug === "string" ? `rb:datatable:${tenantSlug}:jobs` : "rb:datatable:jobs"}
            filters={[
              {
                id: "status",
                label: "Status",
                value: statusFilter,
                options: statusOptions,
                onChange: (value) => {
                  setStatusFilter(String(value));
                  setOverdueFilter(false);
                  setPageIndex(0);
                },
              },
              {
                id: "overdue",
                label: "Due",
                value: overdueFilter ? "overdue" : "all",
                options: overdueOptions,
                onChange: (value) => {
                  setOverdueFilter(String(value) === "overdue");
                  setPageIndex(0);
                },
              },
              {
                id: "payment_status",
                label: "Payment",
                value: paymentFilter,
                options: paymentOptions,
                onChange: (value) => {
                  setPaymentFilter(String(value));
                  setOverdueFilter(false);
                  setPageIndex(0);
                },
              },
              {
                id: "priority",
                label: "Priority",
                value: priorityFilter,
                options: priorityOptions,
                onChange: (value) => {
                  setPriorityFilter(String(value));
                  setOverdueFilter(false);
                  setPageIndex(0);
                },
              },
            ]}
            server={{
              query: q,
              onQueryChange: (value) => {
                setQ(value);
                setPageIndex(0);
              },
              pageIndex,
              onPageIndexChange: setPageIndex,
              pageSize,
              onPageSizeChange: (value) => {
                setPageSize(value);
                setPageIndex(0);
              },
              totalRows: totalJobs,
              sort,
              onSortChange: (next) => {
                setSort(next);
                setPageIndex(0);
              },
            }}
            onRowClick={(row) => {
              if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
              router.push(`/app/${tenantSlug}/jobs/${row.id}`);
            }}
          />
        </CardContent>
      </Card>
    </ListPageShell>
  );
}
