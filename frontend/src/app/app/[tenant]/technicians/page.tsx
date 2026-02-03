"use client";

import React from "react";
import { useParams } from "next/navigation";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { DataTable, type DataTableColumn } from "@/components/ui/DataTable";
import { ListPageShell } from "@/components/shells/ListPageShell";
import { RequireAuth } from "@/components/RequireAuth";
import { apiFetch, ApiError } from "@/lib/api";
import { Modal } from "@/components/ui/Modal";
import { DropdownMenu, DropdownMenuItem } from "@/components/ui/DropdownMenu";
import { useAuth } from "@/lib/auth";
import { notify } from "@/lib/notify";
import { formatMoney } from "@/lib/money";
import { DollarSign, MoreHorizontal } from "lucide-react";
import type { Branch, User, UserStatus } from "@/lib/types";

type TechRow = {
  id: number;
  name: string;
  email: string;
  phone?: string;
  status: UserStatus;
  tech_hourly_rate_cents?: number | null;
  client_hourly_rate_cents?: number | null;
};

export default function TenantTechniciansPage() {
  const auth = useAuth();
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const canManageUsers = auth.can("users.manage");

  const [query, setQuery] = React.useState("");
  const [pageIndex, setPageIndex] = React.useState(0);
  const [pageSize, setPageSize] = React.useState(10);
  const [sort, setSort] = React.useState<{ id: string; dir: "asc" | "desc" } | null>(null);
  const [statusFilter, setStatusFilter] = React.useState<string>("all");

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [rows, setRows] = React.useState<TechRow[]>([]);
  const [totalRows, setTotalRows] = React.useState(0);

  const [reloadKey, setReloadKey] = React.useState(0);

  const [createOpen, setCreateOpen] = React.useState(false);
  const [createBusy, setCreateBusy] = React.useState(false);
  const [newName, setNewName] = React.useState("");
  const [newEmail, setNewEmail] = React.useState("");
  const [branches, setBranches] = React.useState<Branch[]>([]);
  const [newShopQuery, setNewShopQuery] = React.useState<string>("");
  const [newShopSelected, setNewShopSelected] = React.useState<Record<number, boolean>>({});

  const [ratesOpen, setRatesOpen] = React.useState(false);
  const [ratesBusy, setRatesBusy] = React.useState(false);
  const [ratesUser, setRatesUser] = React.useState<TechRow | null>(null);
  const [ratesTech, setRatesTech] = React.useState<string>("");
  const [ratesClient, setRatesClient] = React.useState<string>("");

  const branchOptions = React.useMemo(() => {
    const list = branches.slice().sort((a, b) => `${a.code} ${a.name}`.localeCompare(`${b.code} ${b.name}`));
    return list.map((b) => ({
      id: b.id,
      label: `${b.code} - ${b.name}${b.is_active ? "" : " (inactive)"}`,
      isActive: b.is_active,
    }));
  }, [branches]);

  const activeBranchOptions = React.useMemo(() => branchOptions.filter((b) => b.isActive), [branchOptions]);

  const filteredNewShopOptions = React.useMemo(() => {
    const q = newShopQuery.trim().toLowerCase();
    if (!q) return activeBranchOptions;
    return activeBranchOptions.filter((b) => b.label.toLowerCase().includes(q));
  }, [activeBranchOptions, newShopQuery]);

  const newShopAllChecked = React.useMemo(() => {
    if (filteredNewShopOptions.length === 0) return false;
    return filteredNewShopOptions.every((b) => Boolean(newShopSelected[b.id]));
  }, [filteredNewShopOptions, newShopSelected]);

  const newShopSomeChecked = React.useMemo(() => {
    return filteredNewShopOptions.some((b) => Boolean(newShopSelected[b.id]));
  }, [filteredNewShopOptions, newShopSelected]);

  const newShopCheckAllRef = React.useRef<HTMLInputElement | null>(null);
  React.useEffect(() => {
    if (!newShopCheckAllRef.current) return;
    newShopCheckAllRef.current.indeterminate = newShopSomeChecked && !newShopAllChecked;
  }, [newShopAllChecked, newShopSomeChecked]);

  const statusOptions = React.useMemo(() => {
    return [
      { label: "All statuses", value: "all" },
      { label: "Pending", value: "pending" },
      { label: "Active", value: "active" },
      { label: "Inactive", value: "inactive" },
      { label: "Suspended", value: "suspended" },
    ];
  }, []);

  const statusBadgeVariant = React.useMemo(() => {
    return new Map<UserStatus, "default" | "info" | "success" | "warning" | "danger">([
      ["pending", "warning"],
      ["active", "success"],
      ["inactive", "default"],
      ["suspended", "danger"],
    ]);
  }, []);

  React.useEffect(() => {
    let alive = true;

    async function loadBranches() {
      if (!canManageUsers) return;
      if (!createOpen) return;
      if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
      if (branches.length > 0) return;

      try {
        const res = await apiFetch<{ branches: Branch[] }>(`/api/${tenantSlug}/app/branches`);
        if (!alive) return;
        const list = Array.isArray(res.branches) ? res.branches : [];
        setBranches(list);

        const defaultBranchId = auth.tenant?.default_branch_id ?? null;
        const activeBranchIds = list.filter((b) => b.is_active).map((b) => b.id);
        const seedIds =
          (defaultBranchId && activeBranchIds.includes(defaultBranchId) ? [defaultBranchId] : [])
            .concat(activeBranchIds.length > 0 ? [activeBranchIds[0]] : [])
            .filter((v, idx, arr) => arr.indexOf(v) === idx);

        setNewShopSelected((prev) => {
          if (Object.keys(prev).length > 0) return prev;
          const next: Record<number, boolean> = {};
          for (const id of seedIds) next[id] = true;
          return next;
        });
      } catch (err) {
        if (!alive) return;
        notify.error(err instanceof Error ? err.message : "Failed to load shops.");
      }
    }

    void loadBranches();

    return () => {
      alive = false;
    };
  }, [auth.tenant?.default_branch_id, branches.length, canManageUsers, createOpen, tenantSlug]);

  const currency = auth.tenant?.currency ?? "USD";

  const openRatesModal = React.useCallback(
    (row: TechRow) => {
      if (!canManageUsers) return;
      setRatesUser(row);
      setRatesTech(
        typeof row.tech_hourly_rate_cents === "number" && Number.isFinite(row.tech_hourly_rate_cents)
          ? (row.tech_hourly_rate_cents / 100).toFixed(2)
          : "",
      );
      setRatesClient(
        typeof row.client_hourly_rate_cents === "number" && Number.isFinite(row.client_hourly_rate_cents)
          ? (row.client_hourly_rate_cents / 100).toFixed(2)
          : "",
      );
      setRatesOpen(true);
    },
    [canManageUsers],
  );

  const onSaveRates = React.useCallback(async () => {
    if (!canManageUsers) return;
    if (!ratesUser) return;
    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
    if (ratesBusy) return;

    const parseToCents = (s: string): { cents: number | null; valid: boolean } => {
      const trimmed = s.trim();
      if (trimmed.length === 0) return { cents: null, valid: true };
      const num = Number(trimmed);
      if (!Number.isFinite(num)) return { cents: null, valid: false };
      return { cents: Math.round(num * 100), valid: true };
    };

    const techParsed = parseToCents(ratesTech);
    const clientParsed = parseToCents(ratesClient);

    if (!techParsed.valid || !clientParsed.valid) {
      notify.error("Enter valid numbers for hourly rates.");
      return;
    }

    const techCents = techParsed.cents;
    const clientCents = clientParsed.cents;

    if (typeof techCents === "number" && techCents < 0) {
      notify.error("Tech hourly rate must be 0 or greater.");
      return;
    }
    if (typeof clientCents === "number" && clientCents < 0) {
      notify.error("Client hourly rate must be 0 or greater.");
      return;
    }

    setRatesBusy(true);

    try {
      await apiFetch<{ user: User }>(`/api/${tenantSlug}/app/technicians/${ratesUser.id}/rates`, {
        method: "PATCH",
        body: {
          tech_hourly_rate_cents: techCents,
          client_hourly_rate_cents: clientCents,
        },
      });

      notify.success("Hourly rates updated.");
      setRatesOpen(false);
      setRatesUser(null);
      setReloadKey((v) => v + 1);
    } catch (err) {
      if (err instanceof ApiError) {
        notify.error(err.message);
      } else {
        notify.error(err instanceof Error ? err.message : "Failed to update hourly rates.");
      }
    } finally {
      setRatesBusy(false);
    }
  }, [canManageUsers, ratesBusy, ratesClient, ratesTech, ratesUser, tenantSlug]);

  React.useEffect(() => {
    let alive = true;

    async function load() {
      if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;

      setLoading(true);
      setError(null);

      try {
        const qs = new URLSearchParams();
        if (query.trim().length > 0) qs.set("q", query.trim());
        if (statusFilter && statusFilter !== "all") qs.set("status", statusFilter);
        if (sort?.id && sort?.dir) {
          qs.set("sort", sort.id);
          qs.set("dir", sort.dir);
        }
        qs.set("page", String(pageIndex + 1));
        qs.set("per_page", String(pageSize));

        const res = await apiFetch<{ users: User[]; meta?: { total?: number; per_page?: number } }>(
          `/api/${tenantSlug}/app/technicians?${qs.toString()}`,
        );

        if (!alive) return;

        const list = Array.isArray(res.users) ? res.users : [];
        setRows(
          list.map((u) => ({
            id: u.id,
            name: u.name,
            email: u.email,
            phone: u.phone ?? undefined,
            status: (u.status ?? "active") as UserStatus,
            tech_hourly_rate_cents: u.tech_hourly_rate_cents ?? null,
            client_hourly_rate_cents: u.client_hourly_rate_cents ?? null,
          })),
        );

        setTotalRows(typeof res.meta?.total === "number" ? res.meta.total : list.length);
        setPageSize(typeof res.meta?.per_page === "number" ? res.meta.per_page : pageSize);
      } catch (err) {
        if (!alive) return;
        setError(err instanceof Error ? err.message : "Failed to load technicians.");
      } finally {
        if (!alive) return;
        setLoading(false);
      }
    }

    void load();

    return () => {
      alive = false;
    };
  }, [pageIndex, pageSize, query, reloadKey, sort?.dir, sort?.id, statusFilter, tenantSlug]);

  async function onCreateTechnician(e: React.FormEvent) {
    e.preventDefault();
    if (!canManageUsers) return;
    if (typeof tenantSlug !== "string" || tenantSlug.length === 0) return;
    if (activeBranchOptions.length === 0) return;

    const selectedBranchIds = Object.entries(newShopSelected)
      .filter(([, v]) => v)
      .map(([k]) => Number(k))
      .filter((n) => Number.isFinite(n) && n > 0);

    if (selectedBranchIds.length === 0) {
      notify.error("Select at least one shop.");
      return;
    }

    setCreateBusy(true);

    try {
      await apiFetch<{ user: User }>(`/api/${tenantSlug}/app/technicians`, {
        method: "POST",
        body: {
          name: newName,
          email: newEmail,
          branch_ids: selectedBranchIds,
        },
      });

      notify.success("Technician created.");
      setCreateOpen(false);
      setNewName("");
      setNewEmail("");
      setNewShopQuery("");
      setNewShopSelected({});
      setPageIndex(0);
      setReloadKey((v) => v + 1);
    } catch (err) {
      if (err instanceof ApiError) {
        notify.error(err.message);
      } else {
        notify.error(err instanceof Error ? err.message : "Failed to create technician.");
      }
    } finally {
      setCreateBusy(false);
    }
  }

  const columns = React.useMemo<Array<DataTableColumn<TechRow>>>(
    () => [
      {
        id: "name",
        header: "Technician",
        sortId: "name",
        cell: (row) => (
          <div className="min-w-0">
            <div className="truncate font-semibold text-[var(--rb-text)]">{row.name}</div>
            <div className="truncate text-xs text-zinc-600">#{row.id}</div>
          </div>
        ),
        className: "min-w-[260px]",
      },
      {
        id: "email",
        header: "Email",
        sortId: "email",
        cell: (row) => <div className="text-sm text-zinc-700">{row.email}</div>,
        className: "min-w-[260px]",
      },
      {
        id: "phone",
        header: "Phone",
        cell: (row) => <div className="text-sm text-zinc-700">{row.phone ?? "—"}</div>,
        className: "min-w-[160px]",
      },
      {
        id: "status",
        header: "Status",
        sortId: "status",
        cell: (row) => <Badge variant={statusBadgeVariant.get(row.status) ?? "default"}>{row.status}</Badge>,
        className: "whitespace-nowrap",
      },
      {
        id: "tech_hourly_rate",
        header: "Tech hourly",
        cell: (row) => (
          <div className="whitespace-nowrap text-sm font-semibold text-[var(--rb-text)]">
            {formatMoney({ amountCents: row.tech_hourly_rate_cents ?? null, currency, fallback: "—" })} / hr
          </div>
        ),
        className: "whitespace-nowrap",
      },
      {
        id: "client_hourly_rate",
        header: "Client hourly",
        cell: (row) => (
          <div className="whitespace-nowrap text-sm font-semibold text-[var(--rb-text)]">
            {formatMoney({ amountCents: row.client_hourly_rate_cents ?? null, currency, fallback: "—" })} / hr
          </div>
        ),
        className: "whitespace-nowrap",
      },
      {
        id: "actions",
        header: "",
        cell: (row) => (
          <div className="flex justify-end">
            <DropdownMenu
              align="right"
              trigger={({ toggle }) => (
                <Button
                  variant="ghost"
                  size="sm"
                  disabled={!canManageUsers}
                  onClick={(e) => {
                    e.stopPropagation();
                    toggle();
                  }}
                  aria-label="Actions"
                >
                  <MoreHorizontal className="h-4 w-4" />
                </Button>
              )}
            >
              {({ close }) => (
                <>
                  <DropdownMenuItem
                    onSelect={() => {
                      close();
                      openRatesModal(row);
                    }}
                    disabled={!canManageUsers}
                  >
                    <span className="inline-flex items-center gap-2">
                      <DollarSign className="h-4 w-4" />
                      <span>Update hourly rates</span>
                    </span>
                  </DropdownMenuItem>
                </>
              )}
            </DropdownMenu>
          </div>
        ),
        className: "w-[1%] whitespace-nowrap text-right",
        headerClassName: "w-[1%]",
      },
    ],
    [canManageUsers, currency, openRatesModal, statusBadgeVariant],
  );

  return (
    <RequireAuth requiredPermission="technicians.view">
      <ListPageShell
        title="Technicians"
        description="Technician roster."
        actions={
          <Button
            variant="outline"
            size="sm"
            disabled={!canManageUsers}
            onClick={() => {
              if (!canManageUsers) return;
              setCreateOpen(true);
            }}
          >
            Add technician
          </Button>
        }
        loading={false}
        error={error}
        empty={false}
      >
        <Modal
          open={ratesOpen}
          title={ratesUser ? `Update rates · ${ratesUser.name}` : "Update rates"}
          onClose={() => {
            if (ratesBusy) return;
            setRatesOpen(false);
            setRatesUser(null);
          }}
          footer={
            <div className="flex items-center justify-end gap-2">
              <Button
                variant="outline"
                disabled={ratesBusy}
                onClick={() => {
                  if (ratesBusy) return;
                  setRatesOpen(false);
                  setRatesUser(null);
                }}
              >
                Cancel
              </Button>
              <Button
                disabled={ratesBusy || !canManageUsers || !ratesUser}
                onClick={() => {
                  void onSaveRates();
                }}
              >
                {ratesBusy ? "Saving..." : "Save"}
              </Button>
            </div>
          }
        >
          <div className="space-y-4">
            <div className="space-y-1">
              <label className="text-sm font-medium" htmlFor="tech_hourly_rate">
                Tech hourly rate ({currency})
              </label>
              <input
                id="tech_hourly_rate"
                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={ratesTech}
                onChange={(e) => setRatesTech(e.target.value)}
                placeholder="e.g. 45.00"
                disabled={ratesBusy}
                inputMode="decimal"
              />
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium" htmlFor="client_hourly_rate">
                Client hourly rate ({currency})
              </label>
              <input
                id="client_hourly_rate"
                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={ratesClient}
                onChange={(e) => setRatesClient(e.target.value)}
                placeholder="e.g. 60.00"
                disabled={ratesBusy}
                inputMode="decimal"
              />
            </div>

            <div className="text-xs text-zinc-600">Leave blank to clear a rate.</div>
          </div>
        </Modal>

        <Modal
          open={createOpen}
          title="Add technician"
          onClose={() => {
            if (createBusy) return;
            setCreateOpen(false);
          }}
          className="max-w-3xl"
          footer={
            <div className="flex items-center justify-end gap-2">
              <Button
                variant="outline"
                disabled={createBusy}
                onClick={() => {
                  if (createBusy) return;
                  setCreateOpen(false);
                }}
              >
                Cancel
              </Button>
              <Button
                disabled={
                  createBusy ||
                  !canManageUsers ||
                  activeBranchOptions.length === 0 ||
                  Object.values(newShopSelected).filter(Boolean).length === 0
                }
                onClick={() => {
                  const form = document.getElementById("create_technician_form") as HTMLFormElement | null;
                  form?.requestSubmit();
                }}
              >
                {createBusy ? "Saving..." : "Create"}
              </Button>
            </div>
          }
        >
          <form
            id="create_technician_form"
            onSubmit={onCreateTechnician}
            className="max-h-[70vh] space-y-4 overflow-y-auto pr-1"
          >
            <div className="space-y-1">
              <label className="text-sm font-medium" htmlFor="tech_name">
                Name
              </label>
              <input
                id="tech_name"
                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={newName}
                onChange={(e) => setNewName(e.target.value)}
                required
                disabled={createBusy}
              />
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium" htmlFor="tech_email">
                Email
              </label>
              <input
                id="tech_email"
                className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                value={newEmail}
                onChange={(e) => setNewEmail(e.target.value)}
                type="email"
                required
                disabled={createBusy}
              />
            </div>

            <div className="space-y-1">
              <div className="text-sm text-zinc-600">
                A one-time password will be generated and emailed to the technician.
              </div>
            </div>

            <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-[var(--rb-surface-muted)] p-4">
              <div className="flex items-start justify-between gap-3">
                <div>
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Shops</div>
                  <div className="mt-1 text-xs text-zinc-600">Select one or more shops this technician can access.</div>
                </div>
                <div className="text-xs text-zinc-600">Selected: {Object.values(newShopSelected).filter(Boolean).length}</div>
              </div>

              <div className="mt-3 space-y-1">
                <label className="text-sm font-medium" htmlFor="new_tech_shop_search">
                  Search
                </label>
                <input
                  id="new_tech_shop_search"
                  className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                  value={newShopQuery}
                  onChange={(e) => setNewShopQuery(e.target.value)}
                  placeholder="Search shops..."
                  disabled={createBusy}
                />
              </div>

              <div className="mt-3 max-h-[260px] overflow-y-auto pr-1">
                <div className="grid gap-2">
                  <label className="flex items-center justify-between gap-3 rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2">
                    <div className="min-w-0">
                      <div className="truncate text-sm font-medium text-[var(--rb-text)]">Check all</div>
                    </div>
                    <input
                      ref={newShopCheckAllRef}
                      type="checkbox"
                      checked={newShopAllChecked}
                      onChange={(e) => {
                        const next = e.target.checked;
                        setNewShopSelected((prev) => {
                          const copy = { ...prev };
                          for (const b of filteredNewShopOptions) {
                            copy[b.id] = next;
                          }
                          return copy;
                        });
                      }}
                      disabled={createBusy}
                    />
                  </label>

                  {filteredNewShopOptions.map((b) => {
                    const checked = Boolean(newShopSelected[b.id]);
                    return (
                      <label
                        key={b.id}
                        className="flex items-center justify-between gap-3 rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2"
                      >
                        <div className="min-w-0">
                          <div className="truncate text-sm font-medium text-[var(--rb-text)]">{b.label}</div>
                        </div>
                        <input
                          type="checkbox"
                          checked={checked}
                          onChange={(e) =>
                            setNewShopSelected((prev) => ({
                              ...prev,
                              [b.id]: e.target.checked,
                            }))
                          }
                          disabled={createBusy}
                        />
                      </label>
                    );
                  })}
                </div>
              </div>

              {activeBranchOptions.length === 0 ? (
                <div className="mt-2 text-sm text-zinc-600">Create a shop first before adding technicians.</div>
              ) : null}
            </div>
          </form>
        </Modal>

        <Card className="shadow-none">
          <CardContent className="pt-5">
            <DataTable
              // title={typeof tenantSlug === "string" ? `Technicians` : "Technicians"}
              data={rows}
              loading={loading}
              columns={columns}
              getRowId={(row) => String(row.id)}
              emptyMessage="No technicians."
              search={{
                placeholder: "Search technicians...",
              }}
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
                sort,
                onSortChange: (next) => {
                  setSort(next);
                  setPageIndex(0);
                },
              }}
              filters={[
                {
                  id: "status",
                  label: "Status",
                  value: statusFilter,
                  options: statusOptions,
                  onChange: (value) => {
                    setStatusFilter(String(value));
                    setPageIndex(0);
                  },
                },
              ]}
            />
          </CardContent>
        </Card>
      </ListPageShell>
    </RequireAuth>
  );
}
