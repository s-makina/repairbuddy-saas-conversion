"use client";

import React from "react";
import { useParams } from "next/navigation";
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
import { Skeleton } from "@/components/ui/Skeleton";
import { useAuth } from "@/lib/auth";

type DateField = "pickup_date" | "delivery_date" | "next_service_date" | "post_date";

type CalendarFilter = "all" | "jobs" | "estimates" | "my_assignments";

type MockCalendarItem = {
  id: string;
  type: "job" | "estimate";
  customer: string;
  status: string;
  assigned_to_me: boolean;
  pickup_date: string;
  delivery_date: string;
  next_service_date?: string;
  post_date: string;
};

type CalendarExtendedProps = {
  type?: string;
  status?: string;
  tooltip?: string;
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
    >
      {label}
    </Button>
  );
}

function normalizeStatusClass(status: string) {
  return `status-${status.toLowerCase().replace(/\s+/g, "").replace(/\//g, "")}`;
}

export default function TenantCalendarPage() {
  const auth = useAuth();
  const params = useParams() as { tenant?: string; business?: string };
  const tenantSlug = params.business ?? params.tenant;

  const enableNextServiceDate = false;
  const showMyAssignments = auth.isAdmin;

  const [dateField, setDateField] = React.useState<DateField>("pickup_date");
  const [filter, setFilter] = React.useState<CalendarFilter>("all");
  const [loading, setLoading] = React.useState(false);
  const calendarRef = React.useRef<FullCalendar | null>(null);
  const fetchSeq = React.useRef(0);

  const items = React.useMemo<MockCalendarItem[]>(() => {
    return [
      {
        id: "RB-10421",
        type: "job",
        customer: "Alex Johnson",
        status: "In Process",
        assigned_to_me: true,
        pickup_date: "2026-01-28",
        delivery_date: "2026-01-30",
        post_date: "2026-01-26",
      },
      {
        id: "RB-10418",
        type: "job",
        customer: "Sam Rivera",
        status: "New/Quote",
        assigned_to_me: false,
        pickup_date: "2026-01-29",
        delivery_date: "2026-02-02",
        post_date: "2026-01-25",
      },
      {
        id: "E-883",
        type: "estimate",
        customer: "Taylor Chen",
        status: "New/Quote",
        assigned_to_me: true,
        pickup_date: "2026-01-27",
        delivery_date: "2026-01-31",
        post_date: "2026-01-23",
      },
      {
        id: "RB-10412",
        type: "job",
        customer: "Jordan Patel",
        status: "Delivered",
        assigned_to_me: false,
        pickup_date: "2026-01-20",
        delivery_date: "2026-01-22",
        post_date: "2026-01-18",
      },
      {
        id: "E-879",
        type: "estimate",
        customer: "Morgan Lee",
        status: "Cancelled",
        assigned_to_me: false,
        pickup_date: "2026-01-21",
        delivery_date: "2026-01-24",
        post_date: "2026-01-19",
      },
    ];
  }, []);

  React.useEffect(() => {
    if (filter === "my_assignments" && !showMyAssignments) {
      setFilter("all");
    }
  }, [filter, showMyAssignments]);

  const visibleItems = React.useMemo(() => {
    if (filter === "jobs") return items.filter((x) => x.type === "job");
    if (filter === "estimates") return items.filter((x) => x.type === "estimate");
    if (filter === "my_assignments") return items.filter((x) => x.assigned_to_me);
    return items;
  }, [filter, items]);

  const totals = React.useMemo(() => {
    return {
      jobs: visibleItems.filter((e) => e.type === "job").length,
      estimates: visibleItems.filter((e) => e.type === "estimate").length,
    };
  }, [visibleItems]);

  const resolveItemDate = React.useCallback(
    (item: MockCalendarItem) => {
      if (dateField === "pickup_date") return item.pickup_date;
      if (dateField === "delivery_date") return item.delivery_date;
      if (dateField === "next_service_date") return item.next_service_date ?? item.pickup_date;
      return item.post_date;
    },
    [dateField],
  );

  const loadEvents = React.useCallback(
    (info: EventSourceFuncArg, successCallback: (events: EventInput[]) => void, failureCallback: (error: Error) => void) => {
      const seq = ++fetchSeq.current;
      setLoading(true);

      window.setTimeout(() => {
        if (seq !== fetchSeq.current) return;

        const start = info.startStr.split("T")[0] ?? info.startStr;
        const end = info.endStr.split("T")[0] ?? info.endStr;

        const events: EventInput[] = visibleItems
          .map((item) => {
            const date = resolveItemDate(item);
            return {
              id: item.id,
              title: `${item.type === "estimate" ? "Estimate" : "Job"} ${item.id} - ${item.customer}`,
              start: date,
              allDay: true,
              classNames: [
                item.type === "estimate" ? "estimate-event" : "job-event",
                normalizeStatusClass(item.status),
              ],
              extendedProps: {
                type: item.type,
                status: item.status,
                tooltip: `Case: ${item.id} | Customer: ${item.customer} | Status: ${item.status}`,
              },
            };
          })
          .filter((ev) => {
            const d = typeof ev.start === "string" ? ev.start : "";
            return d >= start && d < end;
          });

        try {
          successCallback(events);
        } catch (err) {
          failureCallback(err instanceof Error ? err : new Error("Failed to load calendar events."));
        }
        setLoading(false);
      }, 450);
    },
    [resolveItemDate, visibleItems],
  );

  function refresh() {
    calendarRef.current?.getApi().refetchEvents();
  }

  React.useEffect(() => {
    calendarRef.current?.getApi().refetchEvents();
  }, [dateField, filter]);

  const onSelectDateField = React.useCallback((field: DateField) => setDateField(field), []);

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
                  disabled={loading}
                >
                  <option value="all">All Items</option>
                  <option value="jobs">Jobs Only</option>
                  <option value="estimates">Estimates Only</option>
                  {showMyAssignments ? <option value="my_assignments">My Assignments</option> : null}
                </Select>
                <input type="hidden" value={dateField} readOnly />
              </div>

              <div>
                <Button size="sm" variant="primary" onClick={refresh} disabled={loading}>
                  {loading ? "Loading..." : "Refresh"}
                </Button>
              </div>
            </div>
          </div>

          <div className="relative mt-4">
            <div
              className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white"
              style={{ minHeight: 500 }}
            >
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
                eventDidMount={(info: EventMountArg) => {
                  const tip = (info.event.extendedProps as CalendarExtendedProps | undefined)?.tooltip;
                  if (typeof tip === "string" && tip.length > 0) {
                    info.el.setAttribute("title", tip);
                  }
                }}
                eventContent={(arg: EventContentArg) => {
                  const status = (arg.event.extendedProps as CalendarExtendedProps | undefined)?.status;
                  return (
                    <div className="flex flex-col">
                      <div className="text-[0.875rem] font-medium leading-[1.1]">{arg.event.title}</div>
                      {status ? <div className="text-[0.8125rem] opacity-80">{status}</div> : null}
                    </div>
                  );
                }}
                loading={(isLoading: boolean) => {
                  setLoading(isLoading);
                }}
              />
            </div>

            {loading ? (
              <div className="absolute inset-0 z-10 rounded-[var(--rb-radius-md)] bg-white/70 p-4 backdrop-blur-[1px]">
                <div className="mx-auto mt-20 w-full max-w-[640px] rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-5 shadow">
                  <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0">
                      <Skeleton className="h-4 w-44 rounded-[var(--rb-radius-sm)]" />
                      <Skeleton className="mt-2 h-4 w-72 rounded-[var(--rb-radius-sm)]" />
                    </div>
                    <Skeleton className="h-9 w-24 rounded-[var(--rb-radius-sm)]" />
                  </div>
                  <div className="mt-4 grid gap-3 sm:grid-cols-2">
                    {Array.from({ length: 6 }).map((_, idx) => (
                      <div key={idx} className="rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-[var(--rb-surface-muted)] p-3">
                        <Skeleton className="h-3 w-24 rounded-[var(--rb-radius-sm)]" />
                        <Skeleton className="mt-2 h-4 w-full rounded-[var(--rb-radius-sm)]" />
                        <Skeleton className="mt-2 h-3 w-20 rounded-[var(--rb-radius-sm)]" />
                      </div>
                    ))}
                  </div>
                </div>
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
        <Card className="border-0 bg-[var(--rb-blue)] text-white shadow-none">
          <CardContent className="pt-5">
            <div className="text-sm font-semibold opacity-90">Total Jobs</div>
            <div className="mt-2 text-4xl font-bold">{totals.jobs}</div>
          </CardContent>
        </Card>

        <Card className="border-0 bg-[#16a34a] text-white shadow-none">
          <CardContent className="pt-5">
            <div className="text-sm font-semibold opacity-90">Total Estimates</div>
            <div className="mt-2 text-4xl font-bold">{totals.estimates}</div>
          </CardContent>
        </Card>
      </div>

      <div className="text-xs text-zinc-500">Tenant: {tenantSlug}</div>
    </div>
  );
}
