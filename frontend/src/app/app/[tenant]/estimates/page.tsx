"use client";

import React from "react";
import { useParams, useRouter } from "next/navigation";
import { useAuth } from "@/lib/auth";
import { Ban, CheckCircle2, Eye, Mail, MoreHorizontal, Pencil, Trash2, XCircle } from "lucide-react";
import { Button } from "@/components/ui/Button";
import { Badge } from "@/components/ui/Badge";
import { Card, CardContent } from "@/components/ui/Card";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { DropdownMenu, DropdownMenuItem } from "@/components/ui/DropdownMenu";
import { ListPageShell } from "@/components/shells/ListPageShell";
import { apiFetch, ApiError } from "@/lib/api";
import { formatMoney } from "@/lib/money";
import { notify } from "@/lib/notify";

type ApiEstimate = {
  id: number;
  case_number: string;
  title: string;
  status: string;
  sent_at?: string | null;
  converted_job_id?: number | null;
  customer: null | {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    company: string | null;
  };
  totals: {
    currency: string;
    subtotal_cents: number;
    tax_cents: number;
    total_cents: number;
  };
  updated_at: string;
};

function estimateBadgeVariant(status: string): "default" | "success" | "warning" | "danger" {
  if (status === "approved") return "success";
  if (status === "rejected") return "danger";
  return "warning";
}

export default function TenantEstimatesPage() {
  const auth = useAuth();
  const router = useRouter();
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [estimates, setEstimates] = React.useState<ApiEstimate[]>([]);
  const [q, setQ] = React.useState<string>("");

  const [sort, setSort] = React.useState<{ id: string; dir: "asc" | "desc" } | null>(null);

  const [actionBusyId, setActionBusyId] = React.useState<number | null>(null);
  const [refreshKey, setRefreshKey] = React.useState(0);

  const [pageIndex, setPageIndex] = React.useState(0);
  const [pageSize, setPageSize] = React.useState(10);

  React.useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);
        if (typeof tenantSlug !== "string" || tenantSlug.trim().length === 0) {
          setEstimates([]);
          return;
        }

        const res = await apiFetch<{ estimates: ApiEstimate[] }>(
          `/api/${tenantSlug}/app/repairbuddy/estimates?limit=200&q=${encodeURIComponent(q.trim())}`,
          {
            method: "GET",
          },
        );

        if (!alive) return;
        setEstimates(Array.isArray(res?.estimates) ? res.estimates : []);
      } catch (e) {
        if (!alive) return;

        if (e instanceof ApiError && e.status === 428 && typeof tenantSlug === "string" && tenantSlug.trim().length > 0) {
          const next = `/app/${tenantSlug}/estimates`;
          router.replace(`/app/${tenantSlug}/branches/select?next=${encodeURIComponent(next)}`);
          return;
        }

        setError(e instanceof Error ? e.message : "Failed to load estimates.");
      } finally {
        if (!alive) return;
        setLoading(false);
      }
    }

    void load();

    return () => {
      alive = false;
    };
  }, [q, refreshKey, tenantSlug]);

  const stats = React.useMemo(() => {
    const byStatus: Record<string, number> = { pending: 0, approved: 0, rejected: 0 };
    for (const e of estimates) {
      const s = typeof e?.status === "string" ? e.status : "";
      if (s in byStatus) byStatus[s] += 1;
    }
    return {
      total: estimates.length,
      pending: byStatus.pending,
      approved: byStatus.approved,
      rejected: byStatus.rejected,
    };
  }, [estimates]);

  const sortedEstimates = React.useMemo(() => {
    const rows = [...estimates];
    if (!sort?.id || !sort?.dir) return rows;

    const dir = sort.dir === "asc" ? 1 : -1;

    rows.sort((a, b) => {
      if (sort.id === "id") return (a.id - b.id) * dir;
      if (sort.id === "case_number") return a.case_number.localeCompare(b.case_number) * dir;
      if (sort.id === "status") return String(a.status).localeCompare(String(b.status)) * dir;
      if (sort.id === "updated_at") return (new Date(a.updated_at).getTime() - new Date(b.updated_at).getTime()) * dir;
      if (sort.id === "total") {
        const at = typeof a.totals?.total_cents === "number" ? a.totals.total_cents : 0;
        const bt = typeof b.totals?.total_cents === "number" ? b.totals.total_cents : 0;
        return (at - bt) * dir;
      }
      return 0;
    });

    return rows;
  }, [estimates, sort]);

  async function sendEstimate(estimateId: number) {
    if (typeof tenantSlug !== "string" || tenantSlug.trim().length === 0) return;
    if (!auth.can("estimates.manage")) return;

    setActionBusyId(estimateId);
    try {
      await apiFetch<{ message: string }>(`/api/${tenantSlug}/app/repairbuddy/estimates/${estimateId}/send`, {
        method: "POST",
      });
      notify.success("Estimate sent.");
      setRefreshKey((x) => x + 1);
    } catch (e) {
      if (e instanceof ApiError && e.status === 428) {
        const next = `/app/${tenantSlug}/estimates`;
        router.replace(`/app/${tenantSlug}/branches/select?next=${encodeURIComponent(next)}`);
        return;
      }
      notify.error(e instanceof Error ? e.message : "Failed to send estimate.");
    } finally {
      setActionBusyId(null);
    }
  }

  async function setEstimateStatus(estimateId: number, status: "approved" | "rejected") {
    if (typeof tenantSlug !== "string" || tenantSlug.trim().length === 0) return;
    if (!auth.can("estimates.manage")) return;

    setActionBusyId(estimateId);
    try {
      await apiFetch<{ estimate: ApiEstimate }>(`/api/${tenantSlug}/app/repairbuddy/estimates/${estimateId}`, {
        method: "PATCH",
        body: { status },
      });
      setRefreshKey((x) => x + 1);
    } catch (e) {
      if (e instanceof ApiError && e.status === 428) {
        const next = `/app/${tenantSlug}/estimates`;
        router.replace(`/app/${tenantSlug}/branches/select?next=${encodeURIComponent(next)}`);
        return;
      }
      notify.error(e instanceof Error ? e.message : "Failed to update estimate.");
    } finally {
      setActionBusyId(null);
    }
  }

  const totalRows = sortedEstimates.length;
  const pageRows = React.useMemo(() => {
    const start = pageIndex * pageSize;
    const end = start + pageSize;
    return sortedEstimates.slice(start, end);
  }, [pageIndex, pageSize, sortedEstimates]);

  const columns = React.useMemo<Array<DataTableColumn<ApiEstimate>>>(
    () => [
      {
        id: "id",
        header: "ID",
        sortId: "id",
        cell: (row) => <div className="text-sm font-semibold text-[var(--rb-text)]">{row.id}</div>,
        className: "whitespace-nowrap",
      },
      {
        id: "case",
        header: "Estimate",
        sortId: "case_number",
        cell: (row) => (
          <div className="min-w-0">
            <div className="truncate font-semibold text-[var(--rb-text)]">{row.case_number}</div>
            <div className="truncate text-xs text-zinc-600">{row.title}</div>
          </div>
        ),
        className: "min-w-[220px] max-w-[280px]",
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
        id: "status",
        header: "Status",
        sortId: "status",
        cell: (row) => (
          <Badge variant={estimateBadgeVariant(row.status)}>
            {typeof row.status === "string" ? row.status.replace(/_/g, " ") : "—"}
          </Badge>
        ),
        className: "whitespace-nowrap",
      },
      {
        id: "total",
        header: "Total",
        sortId: "total",
        cell: (row) => (
          <div className="text-sm text-zinc-700">
            {formatMoney({ amountCents: row.totals?.total_cents, currency: row.totals?.currency })}
          </div>
        ),
        className: "whitespace-nowrap",
      },
      {
        id: "updated",
        header: "Updated",
        sortId: "updated_at",
        cell: (row) => <div className="text-sm text-zinc-600">{new Date(row.updated_at).toLocaleString()}</div>,
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
                  className="px-2"
                  aria-label="Actions"
                  disabled={actionBusyId === row.id}
                  onClick={(e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    toggle();
                  }}
                >
                  <MoreHorizontal className="h-4 w-4" />
                </Button>
              )}
            >
              {({ close }) => (
                <>
                  <DropdownMenuItem
                    onSelect={() => {
                      if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
                      close();
                      router.push(`/app/${tenantSlug}/estimates/${row.id}`);
                    }}
                  >
                    <span className="flex items-center gap-2">
                      <Eye className="h-4 w-4" />
                      <span>View</span>
                    </span>
                  </DropdownMenuItem>

                  <DropdownMenuItem
                    disabled={
                      actionBusyId === row.id ||
                      !auth.can("estimates.manage") ||
                      !row.customer ||
                      !row.customer.email ||
                      row.customer.email.trim().length === 0
                    }
                    onSelect={() => {
                      close();
                      void sendEstimate(row.id);
                    }}
                  >
                    <span className="flex items-center gap-2">
                      <Mail className="h-4 w-4" />
                      <span>Send</span>
                    </span>
                  </DropdownMenuItem>

                  <DropdownMenuItem
                    disabled={actionBusyId === row.id || !auth.can("estimates.manage") || row.status !== "pending"}
                    onSelect={() => {
                      close();
                      if (!window.confirm("Approve this estimate?")) return;
                      void setEstimateStatus(row.id, "approved");
                    }}
                  >
                    <span className="flex items-center gap-2">
                      <CheckCircle2 className="h-4 w-4" />
                      <span>Approve</span>
                    </span>
                  </DropdownMenuItem>

                  <DropdownMenuItem
                    disabled={actionBusyId === row.id || !auth.can("estimates.manage") || row.status !== "pending"}
                    onSelect={() => {
                      close();
                      if (!window.confirm("Reject this estimate?")) return;
                      void setEstimateStatus(row.id, "rejected");
                    }}
                  >
                    <span className="flex items-center gap-2">
                      <XCircle className="h-4 w-4" />
                      <span>Reject</span>
                    </span>
                  </DropdownMenuItem>

                  <DropdownMenuItem disabled onSelect={() => {}}>
                    <span className="flex items-center gap-2">
                      <Pencil className="h-4 w-4" />
                      <span>Edit</span>
                    </span>
                  </DropdownMenuItem>

                  <DropdownMenuItem disabled onSelect={() => {}}>
                    <span className="flex items-center gap-2">
                      <Ban className="h-4 w-4" />
                      <span>Cancel</span>
                    </span>
                  </DropdownMenuItem>

                  <DropdownMenuItem disabled onSelect={() => {}}>
                    <span className="flex items-center gap-2">
                      <Trash2 className="h-4 w-4" />
                      <span>Delete</span>
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
    [actionBusyId, auth, router, tenantSlug],
  );

  return (
    <ListPageShell
      title="Estimates"
      description="Create, send, and track customer approvals."
      actions={
        <Button
          variant="outline"
          size="sm"
          onClick={() => {
            if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
            if (!auth.can("estimates.manage")) return;
            router.push(`/app/${tenantSlug}/estimates/new`);
          }}
          disabled={typeof tenantSlug !== "string" || tenantSlug.length === 0 || !auth.can("estimates.manage")}
        >
          New estimate
        </Button>
      }
      loading={loading}
      error={error}
      empty={!loading && !error && estimates.length === 0}
      emptyTitle="No estimates found"
      emptyDescription="Try adjusting your search."
    >
      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        {[{
          id: "pending",
          label: "Pending",
          value: stats.pending,
          dot: "bg-amber-500",
        },
        {
          id: "approved",
          label: "Approved",
          value: stats.approved,
          dot: "bg-emerald-500",
        },
        {
          id: "rejected",
          label: "Rejected",
          value: stats.rejected,
          dot: "bg-rose-500",
        },
        {
          id: "total",
          label: "Total",
          value: stats.total,
          dot: "bg-sky-500",
        }].map((c) => (
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
            <div className="mt-1 text-2xl font-semibold text-[var(--rb-text)]">{loading ? "—" : String(c.value)}</div>
          </div>
        ))}
      </div>

      <Card className="shadow-none">
        <CardContent className="pt-5">
          <DataTable
            title={typeof tenantSlug === "string" ? `Estimates · ${tenantSlug}` : "Estimates"}
            data={pageRows}
            loading={loading}
            emptyMessage="No estimates."
            columns={columns}
            getRowId={(row) => row.id}
            search={{
              placeholder: "Search by case number, title, customer, or ID...",
            }}
            columnVisibilityKey={typeof tenantSlug === "string" ? `rb:datatable:${tenantSlug}:estimates` : "rb:datatable:estimates"}
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
              totalRows,
              sort,
              onSortChange: (next) => {
                setSort(next);
                setPageIndex(0);
              },
            }}
            onRowClick={(row) => {
              if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
              router.push(`/app/${tenantSlug}/estimates/${row.id}`);
            }}
          />
        </CardContent>
      </Card>
    </ListPageShell>
  );
}
