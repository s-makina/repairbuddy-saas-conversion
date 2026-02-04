"use client";

import React from "react";
import { useRouter } from "next/navigation";
import FullCalendar from "@fullcalendar/react";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import listPlugin from "@fullcalendar/list";
import interactionPlugin from "@fullcalendar/interaction";
import type { EventContentArg, EventInput, EventMountArg, EventSourceFuncArg } from "@fullcalendar/core";
import { PageHeader } from "@/components/ui/PageHeader";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { Badge } from "@/components/ui/Badge";
import { Select } from "@/components/ui/Select";
import { apiFetch, ApiError } from "@/lib/api";
import { useAuth } from "@/lib/auth";

type DateField = "pickup_date" | "delivery_date" | "next_service_date" | "post_date";

type CalendarFilter = "all" | "jobs" | "estimates" | "my_assignments";

type ApiJob = {
  id: number;
  case_number: string;
  title: string;
  status: string;
  customer?: { id: number; name: string } | null;
  assigned_technicians?: Array<{ id: number; name: string; email: string }>;
  pickup_date?: string | null;
  delivery_date?: string | null;
  next_service_date?: string | null;
  created_at?: string;
};

type ApiEstimate = {
  id: number;
  case_number: string;
  title: string;
  status: string;
  customer?: { id: number; name: string } | null;
  pickup_date?: string | null;
  delivery_date?: string | null;
  assigned_technician_id?: number | null;
  created_at?: string;
};

type CalendarItem = {
  id: string;
  type: "job" | "estimate";
  numericId: number;
  caseNumber: string;
  customerName: string;
  status: string;
  assignedToMe: boolean;
  pickup_date: string | null;
  delivery_date: string | null;
  next_service_date: string | null;
  post_date: string | null;
};

type CalendarExtendedProps = {
  type?: string;
  status?: string;
  tooltip?: string;
  href?: string;
};

function DateFieldButton({
  field,
  label,
  active,
  onSelect,
}: {
  field: DateField;
  label: string;
  active: boolean;
  onSelect: (field: DateField) => void;
}) {
  return (
    <Button
      size="sm"
      variant={active ? "primary" : "outline"}
      className="rounded-none border-0"
      onClick={() => onSelect(field)}
      type="button"
    >
      {label}
    </Button>
  );
}

function normalizeStatusClass(status: string) {
  return `status-${status.toLowerCase().replace(/\s+/g, "").replace(/\//g, "")}`;
}

function normalizeIsoDate(value: unknown): string | null {
  if (typeof value !== "string") return null;
  const trimmed = value.trim();
  if (trimmed === "") return null;
  const d = new Date(trimmed);
  if (Number.isNaN(d.getTime())) return null;
  return d.toISOString().slice(0, 10);
}

function withinRange(date: string, start: string, end: string): boolean {
  return date >= start && date < end;
}

export default function GetUpAndGloCalendarPage() {
  const auth = useAuth();
  const router = useRouter();
  const tenantSlug = "get-up-and-glo";

  const enableNextServiceDate = false;
  const showMyAssignments = auth.isAdmin;

  const [dateField, setDateField] = React.useState<DateField>("pickup_date");
  const [filter, setFilter] = React.useState<CalendarFilter>("all");

  const [itemsLoading, setItemsLoading] = React.useState(true);
  const [itemsError, setItemsError] = React.useState<string | null>(null);
  const [items, setItems] = React.useState<CalendarItem[]>([]);
  const [activeRange, setActiveRange] = React.useState<{ start: string; end: string } | null>(null);

  const [busyRefresh, setBusyRefresh] = React.useState(false);
  const [calendarLoading, setCalendarLoading] = React.useState(false);
  const calendarRef = React.useRef<FullCalendar | null>(null);
  const lastLoadedAtRef = React.useRef<number>(0);

  React.useEffect(() => {
    if (filter === "my_assignments" && !showMyAssignments) {
      setFilter("all");
    }
  }, [filter, showMyAssignments]);

  const userId = auth.user?.id ?? null;

  const loadAllItems = React.useCallback(async () => {
    setItemsLoading(true);
    setItemsError(null);

    try {
      const qsJobs = new URLSearchParams();
      qsJobs.set("page", "1");
      qsJobs.set("per_page", "100");

      const [jobsRes, estimatesRes] = await Promise.all([
        apiFetch<{ jobs: ApiJob[]; meta?: unknown }>(`/api/${tenantSlug}/app/repairbuddy/jobs?${qsJobs.toString()}`),
        apiFetch<{ estimates: ApiEstimate[] }>(`/api/${tenantSlug}/app/repairbuddy/estimates?limit=200`),
      ]);

      const jobsRaw = Array.isArray(jobsRes?.jobs) ? jobsRes.jobs : [];
      const estimatesRaw = Array.isArray(estimatesRes?.estimates) ? estimatesRes.estimates : [];

      const nextItems: CalendarItem[] = [];

      for (const j of jobsRaw) {
        const numericId = typeof j?.id === "number" ? j.id : NaN;
        if (!Number.isFinite(numericId)) continue;

        const assigned = Array.isArray(j?.assigned_technicians) ? j.assigned_technicians : [];
        const assignedToMe = typeof userId === "number" ? assigned.some((t) => t?.id === userId) : false;
        const customerName = typeof j?.customer?.name === "string" ? j.customer.name : "Unknown";

        nextItems.push({
          id: `job-${numericId}`,
          type: "job",
          numericId,
          caseNumber: typeof j?.case_number === "string" && j.case_number.trim() !== "" ? j.case_number : String(numericId),
          customerName,
          status: typeof j?.status === "string" && j.status.trim() !== "" ? j.status : "Job",
          assignedToMe,
          pickup_date: normalizeIsoDate(j?.pickup_date),
          delivery_date: normalizeIsoDate(j?.delivery_date),
          next_service_date: normalizeIsoDate(j?.next_service_date),
          post_date: normalizeIsoDate(j?.created_at),
        });
      }

      for (const e of estimatesRaw) {
        const numericId = typeof e?.id === "number" ? e.id : NaN;
        if (!Number.isFinite(numericId)) continue;

        const assignedToMe =
          typeof userId === "number" && typeof e?.assigned_technician_id === "number" ? e.assigned_technician_id === userId : false;
        const customerName = typeof e?.customer?.name === "string" ? e.customer.name : "Unknown";

        nextItems.push({
          id: `estimate-${numericId}`,
          type: "estimate",
          numericId,
          caseNumber: typeof e?.case_number === "string" && e.case_number.trim() !== "" ? e.case_number : String(numericId),
          customerName,
          status: typeof e?.status === "string" && e.status.trim() !== "" ? e.status : "Estimate",
          assignedToMe,
          pickup_date: normalizeIsoDate(e?.pickup_date),
          delivery_date: normalizeIsoDate(e?.delivery_date),
          next_service_date: null,
          post_date: normalizeIsoDate(e?.created_at),
        });
      }

      setItems(nextItems);
      lastLoadedAtRef.current = Date.now();
    } catch (e) {
      if (e instanceof ApiError && e.status === 428) {
        const next = `/app/${tenantSlug}/calendar`;
        router.replace(`/app/${tenantSlug}/branches/select?next=${encodeURIComponent(next)}`);
        return;
      }

      setItemsError(e instanceof Error ? e.message : "Failed to load calendar data.");
      setItems([]);
    } finally {
      setItemsLoading(false);
    }
  }, [router, tenantSlug, userId]);

  React.useEffect(() => {
    void loadAllItems();
  }, [loadAllItems]);

  const visibleItems = React.useMemo(() => {
    if (filter === "jobs") return items.filter((x) => x.type === "job");
    if (filter === "estimates") return items.filter((x) => x.type === "estimate");
    if (filter === "my_assignments") return items.filter((x) => x.assignedToMe);
    return items;
  }, [filter, items]);

  const resolveItemDate = React.useCallback(
    (item: CalendarItem) => {
      if (dateField === "pickup_date") return item.pickup_date;
      if (dateField === "delivery_date") return item.delivery_date;
      if (dateField === "next_service_date") return item.next_service_date ?? item.pickup_date;
      return item.post_date;
    },
    [dateField],
  );

  const visibleItemsInRange = React.useMemo(() => {
    if (!activeRange) return visibleItems;
    const start = activeRange.start;
    const end = activeRange.end;

    return visibleItems.filter((item) => {
      const date = resolveItemDate(item);
      if (!date) return false;
      return withinRange(date, start, end);
    });
  }, [activeRange, resolveItemDate, visibleItems]);

  const totals = React.useMemo(() => {
    return {
      jobs: visibleItemsInRange.filter((e) => e.type === "job").length,
      estimates: visibleItemsInRange.filter((e) => e.type === "estimate").length,
    };
  }, [visibleItemsInRange]);

  const loadEvents = React.useCallback(
    (info: EventSourceFuncArg, successCallback: (events: EventInput[]) => void, failureCallback: (error: Error) => void) => {
      const start = info.startStr.split("T")[0] ?? info.startStr;
      const end = info.endStr.split("T")[0] ?? info.endStr;

      try {
        const events: EventInput[] = [];

        for (const item of visibleItems) {
          const date = resolveItemDate(item);
          if (!date) continue;
          if (!withinRange(date, start, end)) continue;

          const titlePrefix = item.type === "estimate" ? "Estimate" : "Job";
          const href = item.type === "estimate" ? `/app/${tenantSlug}/estimates/${item.numericId}` : `/app/${tenantSlug}/jobs/${item.numericId}`;

          events.push({
            id: item.id,
            title: `${titlePrefix} ${item.caseNumber}`,
            start: date,
            allDay: true,
            classNames: [item.type === "estimate" ? "estimate-event" : "job-event", normalizeStatusClass(item.status)],
            extendedProps: {
              type: item.type,
              status: item.status,
              tooltip: `Case: ${item.caseNumber} | Customer: ${item.customerName} | Status: ${item.status}`,
              customerName: item.customerName,
              caseNumber: item.caseNumber,
              href,
            } as CalendarExtendedProps,
          });
        }

        successCallback(events);
      } catch (err) {
        failureCallback(err instanceof Error ? err : new Error("Failed to build calendar events."));
      }
    },
    [resolveItemDate, tenantSlug, visibleItems],
  );

  async function refreshAll() {
    if (busyRefresh) return;
    setBusyRefresh(true);

    try {
      await loadAllItems();
      calendarRef.current?.getApi().refetchEvents();
    } finally {
      setBusyRefresh(false);
    }
  }

  React.useEffect(() => {
    calendarRef.current?.getApi().refetchEvents();
  }, [dateField, filter]);

  const onSelectDateField = React.useCallback((field: DateField) => setDateField(field), []);

  const emptyHint =
    visibleItems.length === 0
      ? itemsLoading
        ? "Loading…"
        : itemsError
          ? "Calendar data could not be loaded."
          : "No items match the current filter."
      : null;

  return (
    <div className="space-y-6">
      <PageHeader
        title="Service Calendar"
        description="View and manage all your service appointments and estimates"
        actions={
          <div className="flex flex-wrap items-center gap-2">
            <div className="inline-flex overflow-hidden rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white">
              <DateFieldButton field="pickup_date" label="Pickup Date" active={dateField === "pickup_date"} onSelect={onSelectDateField} />
              <div className="h-8 w-px bg-[var(--rb-border)]" />
              <DateFieldButton field="delivery_date" label="Delivery Date" active={dateField === "delivery_date"} onSelect={onSelectDateField} />
              {enableNextServiceDate ? (
                <>
                  <div className="h-8 w-px bg-[var(--rb-border)]" />
                  <DateFieldButton field="next_service_date" label="Next Service" active={dateField === "next_service_date"} onSelect={onSelectDateField} />
                </>
              ) : null}
              <div className="h-8 w-px bg-[var(--rb-border)]" />
              <DateFieldButton field="post_date" label="Creation" active={dateField === "post_date"} onSelect={onSelectDateField} />
            </div>
          </div>
        }
      />

      <Card className="shadow-none">
        <CardContent className="pt-5">
          <div className="rounded-[var(--rb-radius-md)] bg-[var(--rb-surface-muted)] p-4">
            <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
              <div className="flex items-center gap-3">
                <div className="text-sm font-semibold text-[var(--rb-text)]">Filter:</div>
                <Select
                  value={filter}
                  onChange={(e) => setFilter(e.target.value as CalendarFilter)}
                  className="w-auto min-w-[180px]"
                  disabled={itemsLoading || busyRefresh || calendarLoading}
                >
                  <option value="all">All Items</option>
                  <option value="jobs">Jobs Only</option>
                  <option value="estimates">Estimates Only</option>
                  {showMyAssignments ? <option value="my_assignments">My Assignments</option> : null}
                </Select>
                <input type="hidden" value={dateField} readOnly />
              </div>

              <div className="flex items-center gap-2">
                <Button size="sm" variant="outline" onClick={() => void loadAllItems()} disabled={itemsLoading || busyRefresh}>
                  Reload data
                </Button>
                <Button size="sm" variant="primary" onClick={() => void refreshAll()} disabled={itemsLoading || busyRefresh}>
                  {busyRefresh ? "Loading..." : "Refresh"}
                </Button>
              </div>
            </div>

            {itemsError ? (
              <div className="mt-3 rounded-[var(--rb-radius-sm)] border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                {itemsError}
              </div>
            ) : null}

            {!itemsError && typeof lastLoadedAtRef.current === "number" && lastLoadedAtRef.current > 0 ? (
              <div className="mt-3 text-xs text-zinc-500">Last updated: {new Date(lastLoadedAtRef.current).toLocaleString()}</div>
            ) : null}
          </div>

          <div className="relative mt-4">
            <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white" style={{ minHeight: 500 }}>
              <FullCalendar
                ref={calendarRef}
                plugins={[dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin]}
                initialView="dayGridMonth"
                headerToolbar={{
                  left: "prevYear,prev,next,nextYear today",
                  center: "title",
                  right: "dayGridMonth,timeGridWeek,timeGridDay,listMonth",
                }}
                buttonText={{
                  today: "Today",
                  month: "Month",
                  week: "Week",
                  day: "Day",
                  list: "List",
                }}
                height="auto"
                expandRows
                navLinks
                nowIndicator
                editable={false}
                selectable
                dayMaxEvents={10}
                events={loadEvents}
                datesSet={(arg) => {
                  const start = arg.start;
                  const end = arg.end;
                  const startIso = start instanceof Date && !Number.isNaN(start.getTime()) ? start.toISOString().slice(0, 10) : null;
                  const endIso = end instanceof Date && !Number.isNaN(end.getTime()) ? end.toISOString().slice(0, 10) : null;
                  if (startIso && endIso) {
                    setActiveRange({ start: startIso, end: endIso });
                  }
                }}
                eventDidMount={(info: EventMountArg) => {
                  const tip = (info.event.extendedProps as CalendarExtendedProps | undefined)?.tooltip;
                  if (typeof tip === "string" && tip.length > 0) {
                    info.el.setAttribute("title", tip);
                  }
                }}
                eventClick={(info) => {
                  const href = (info.event.extendedProps as CalendarExtendedProps | undefined)?.href;
                  if (typeof href === "string" && href.trim() !== "") {
                    info.jsEvent.preventDefault();
                    router.push(href);
                  }
                }}
                eventContent={(arg: EventContentArg) => {
                  const props = arg.event.extendedProps as (CalendarExtendedProps & { customerName?: string; caseNumber?: string }) | undefined;
                  const status = props?.status;
                  const customerName = typeof props?.customerName === "string" ? props.customerName : "";
                  return (
                    <div className="flex min-w-0 flex-col">
                      <div className="truncate text-[0.8125rem] font-semibold leading-[1.1]">{arg.event.title}</div>
                      {customerName ? <div className="truncate text-[0.8125rem] leading-[1.1]">{customerName}</div> : null}
                      {status ? <div className="truncate text-[0.8125rem] opacity-80">{status}</div> : null}
                    </div>
                  );
                }}
                loading={(isLoading: boolean) => {
                  setCalendarLoading(isLoading);
                }}
              />
            </div>

            {calendarLoading || itemsLoading ? (
              <div className="absolute left-1/2 top-1/2 z-10 w-[280px] -translate-x-1/2 -translate-y-1/2 rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-4 shadow">
                <div className="flex flex-col items-center gap-2">
                  <div className="h-7 w-7 animate-spin rounded-full border-2 border-[var(--rb-border)] border-t-[var(--rb-blue)]" />
                  <div className="text-sm text-zinc-600">Loading calendar events...</div>
                </div>
              </div>
            ) : null}

            {!itemsLoading && !itemsError && emptyHint ? (
              <div className="mt-3 rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-[var(--rb-surface-muted)] p-4 text-sm text-zinc-600">
                {emptyHint}
              </div>
            ) : null}
          </div>

          <div className="mt-5 border-t border-[var(--rb-border)] pt-4">
            <div className="flex flex-wrap items-center gap-2">
              <span className="text-sm text-zinc-500">Legend:</span>
              <Badge className="bg-[var(--rb-blue)] text-white border-transparent">Job</Badge>
              <Badge className="bg-[var(--rb-orange)] text-white border-transparent">Estimate</Badge>
              <Badge className="bg-[#16a34a] text-white border-transparent">New/Quote</Badge>
              <Badge className="bg-[#0ea5e9] text-white border-transparent">In Process</Badge>
              <Badge className="bg-[#dc2626] text-white border-transparent">Cancelled</Badge>
              <Badge className="bg-[#fd7e14] text-white border-transparent">Ready</Badge>
              <Badge className="bg-[#6f42c1] text-white border-transparent">Completed</Badge>
              <Badge className="bg-[#e83e8c] text-white border-transparent">Delivered</Badge>
            </div>
          </div>
        </CardContent>
      </Card>

      <div className="grid gap-4 md:grid-cols-2">
        <Card className="relative overflow-hidden border-0 bg-gradient-to-br from-[var(--rb-blue)] via-[#1d4ed8] to-[#0ea5e9] text-white shadow-sm">
          <div className="pointer-events-none absolute -right-12 -top-12 h-40 w-40 rounded-full bg-white/10" />
          <div className="pointer-events-none absolute -bottom-16 -left-16 h-56 w-56 rounded-full bg-black/10" />
          <CardContent className="relative pt-5">
            <div className="text-sm font-semibold opacity-90">Total Jobs</div>
            <div className="mt-2 text-4xl font-bold">{totals.jobs}</div>
          </CardContent>
        </Card>

        <Card className="relative overflow-hidden border-0 bg-gradient-to-br from-[#16a34a] via-[#22c55e] to-[#10b981] text-white shadow-sm">
          <div className="pointer-events-none absolute -right-12 -top-12 h-40 w-40 rounded-full bg-white/10" />
          <div className="pointer-events-none absolute -bottom-16 -left-16 h-56 w-56 rounded-full bg-black/10" />
          <CardContent className="relative pt-5">
            <div className="text-sm font-semibold opacity-90">Total Estimates</div>
            <div className="mt-2 text-4xl font-bold">{totals.estimates}</div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
