import { mockFixtures } from "@/mock/fixtures";
import type {
  Appointment,
  AppointmentId,
  Client,
  ClientId,
  CustomerDevice,
  CustomerDeviceId,
  Device,
  DeviceBrand,
  DeviceType,
  Expense,
  ExpenseCategory,
  Estimate,
  EstimateId,
  EstimateStatus,
  Job,
  JobAttachment,
  JobId,
  JobMessage,
  JobStatus,
  JobTimelineEvent,
  MockDataBundle,
  Part,
  Payment,
  Review,
  Service,
  TimeLog,
} from "@/mock/types";

const STORAGE_KEY = "rb.mockData.overrides:v1";
const COUNTERS_KEY = "rb.mockData.counters:v1";

type Overrides = {
  estimatesById?: Record<string, Partial<Estimate>>;
  jobsById?: Record<string, Partial<Job>>;
  jobMessagesByJobId?: Record<string, JobMessage[]>;
  appointments?: Appointment[];
};

type Counters = {
  msg: number;
  att: number;
  appt: number;
};

function sleep(ms: number) {
  return new Promise<void>((resolve) => {
    setTimeout(resolve, ms);
  });
}

async function withLatency<T>(fn: () => T | Promise<T>, ms = 350): Promise<T> {
  await sleep(ms);
  return await fn();
}

function safeJsonParse(raw: string | null): unknown {
  if (!raw) return null;
  try {
    return JSON.parse(raw);
  } catch {
    return null;
  }
}

function loadOverrides(): Overrides {
  if (typeof window === "undefined") return {};
  const parsed = safeJsonParse(window.localStorage.getItem(STORAGE_KEY));
  if (!parsed || typeof parsed !== "object") return {};
  return parsed as Overrides;
}

function saveOverrides(next: Overrides) {
  if (typeof window === "undefined") return;
  try {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(next));
  } catch {
    // ignore
  }
}

function loadCounters(): Counters {
  if (typeof window === "undefined") {
    return { msg: 0, att: 0, appt: 0 };
  }
  const parsed = safeJsonParse(window.localStorage.getItem(COUNTERS_KEY));
  const base: Counters = { msg: 0, att: 0, appt: 0 };
  if (!parsed || typeof parsed !== "object") return base;
  const obj = parsed as Partial<Counters>;
  return {
    msg: typeof obj.msg === "number" && Number.isFinite(obj.msg) ? obj.msg : 0,
    att: typeof obj.att === "number" && Number.isFinite(obj.att) ? obj.att : 0,
    appt: typeof obj.appt === "number" && Number.isFinite(obj.appt) ? obj.appt : 0,
  };
}

function saveCounters(next: Counters) {
  if (typeof window === "undefined") return;
  try {
    window.localStorage.setItem(COUNTERS_KEY, JSON.stringify(next));
  } catch {
    // ignore
  }
}

function nextId(prefix: keyof Counters): string {
  const counters = loadCounters();
  const next = { ...counters, [prefix]: (counters[prefix] ?? 0) + 1 } as Counters;
  saveCounters(next);
  const n = String(next[prefix]).padStart(3, "0");
  return `${prefix}_${n}`;
}

function nowIso() {
  return new Date().toISOString();
}

function mergeBundle(base: MockDataBundle, overrides: Overrides): MockDataBundle {
  const estById = overrides.estimatesById ?? {};
  const jobsById = overrides.jobsById ?? {};
  const msgsByJobId = overrides.jobMessagesByJobId ?? {};
  const appts = overrides.appointments ?? null;

  const estimates = base.estimates.map((e) => {
    const patch = estById[e.id] ?? null;
    return patch ? { ...e, ...patch } : e;
  });

  const jobs = base.jobs.map((j) => {
    const patch = jobsById[j.id] ?? null;
    const msgs = msgsByJobId[j.id] ?? null;
    if (!patch && !msgs) return j;
    return {
      ...j,
      ...(patch ?? {}),
      messages: msgs ? msgs : j.messages,
    };
  });

  return {
    ...base,
    estimates,
    jobs,
    appointments: appts ? appts : base.appointments,
  };
}

function getBundle(): MockDataBundle {
  const overrides = loadOverrides();
  return mergeBundle(mockFixtures, overrides);
}

function findJobByCaseNumber(bundle: MockDataBundle, caseNumber: string): Job | null {
  const normalized = caseNumber.trim().toUpperCase();
  return bundle.jobs.find((j) => j.case_number.toUpperCase() === normalized) ?? null;
}

function computeEstimateTotalCents(est: Estimate): number {
  return est.lines.reduce((sum, line) => sum + line.qty * line.unit_price.amount_cents, 0);
}

function assertIdNonEmpty(value: string, label: string) {
  if (!value || typeof value !== "string" || value.trim().length === 0) {
    throw new Error(`${label} is required.`);
  }
}

export const mockApi = {
  sleep,
  withLatency,

  async getBundle() {
    return await withLatency(() => getBundle());
  },

  async getStatuses(): Promise<JobStatus[]> {
    return await withLatency(() => getBundle().statuses);
  },

  async listJobs(): Promise<Job[]> {
    return await withLatency(() => getBundle().jobs);
  },

  async getJob(jobId: JobId): Promise<Job | null> {
    return await withLatency(() => getBundle().jobs.find((j) => j.id === jobId) ?? null);
  },

  async getJobByCaseNumber(caseNumber: string): Promise<Job | null> {
    return await withLatency(() => {
      const bundle = getBundle();
      return findJobByCaseNumber(bundle, caseNumber);
    });
  },

  async listClients(): Promise<Client[]> {
    return await withLatency(() => getBundle().clients);
  },

  async getClient(clientId: ClientId): Promise<Client | null> {
    return await withLatency(() => getBundle().clients.find((c) => c.id === clientId) ?? null);
  },

  async listCustomerDevices(): Promise<CustomerDevice[]> {
    return await withLatency(() => getBundle().customer_devices);
  },

  async getCustomerDevice(customerDeviceId: CustomerDeviceId): Promise<CustomerDevice | null> {
    return await withLatency(() => getBundle().customer_devices.find((d) => d.id === customerDeviceId) ?? null);
  },

  async listDevices(): Promise<Device[]> {
    return await withLatency(() => getBundle().devices);
  },

  async listDeviceBrands(): Promise<DeviceBrand[]> {
    return await withLatency(() => getBundle().device_brands);
  },

  async listDeviceTypes(): Promise<DeviceType[]> {
    return await withLatency(() => getBundle().device_types);
  },

  async listEstimates(): Promise<Estimate[]> {
    return await withLatency(() => getBundle().estimates);
  },

  async getEstimate(estimateId: EstimateId): Promise<Estimate | null> {
    return await withLatency(() => getBundle().estimates.find((e) => e.id === estimateId) ?? null);
  },

  async setEstimateStatus(args: { estimateId: EstimateId; status: EstimateStatus }): Promise<Estimate> {
    return await withLatency(() => {
      const { estimateId, status } = args;
      const bundle = getBundle();
      const est = bundle.estimates.find((e) => e.id === estimateId) ?? null;
      if (!est) throw new Error("Estimate not found.");

      const overrides = loadOverrides();
      const estimatesById = { ...(overrides.estimatesById ?? {}) };
      estimatesById[estimateId] = {
        ...(estimatesById[estimateId] ?? {}),
        status,
        updated_at: nowIso(),
      };
      saveOverrides({
        ...overrides,
        estimatesById,
      });

      const updated: Estimate = {
        ...est,
        status,
        updated_at: nowIso(),
      };

      return updated;
    });
  },

  async listAppointments(): Promise<Appointment[]> {
    return await withLatency(() => getBundle().appointments);
  },

  async createAppointment(input: {
    scheduled_at: string;
    client_name: string;
    client_email?: string | null;
    client_phone?: string | null;
    notes?: string | null;
  }): Promise<Appointment> {
    return await withLatency(() => {
      assertIdNonEmpty(input.scheduled_at, "scheduled_at");
      assertIdNonEmpty(input.client_name, "client_name");

      const appt: Appointment = {
        id: nextId("appt") as AppointmentId,
        scheduled_at: new Date(input.scheduled_at).toISOString(),
        status: "requested",
        client_name: input.client_name.trim(),
        client_email: input.client_email ?? null,
        client_phone: input.client_phone ?? null,
        notes: input.notes ?? null,
        created_at: nowIso(),
      };

      const overrides = loadOverrides();
      const existing = Array.isArray(overrides.appointments) ? overrides.appointments : getBundle().appointments;
      const appointments = [appt, ...existing];
      saveOverrides({
        ...overrides,
        appointments,
      });

      return appt;
    });
  },

  async listPayments(): Promise<Payment[]> {
    return await withLatency(() => getBundle().payments);
  },

  async listExpenses(): Promise<Expense[]> {
    return await withLatency(() => getBundle().expenses);
  },

  async listExpenseCategories(): Promise<ExpenseCategory[]> {
    return await withLatency(() => getBundle().expense_categories);
  },

  async listTimeLogs(): Promise<TimeLog[]> {
    return await withLatency(() => getBundle().time_logs);
  },

  async listServices(): Promise<Service[]> {
    return await withLatency(() => getBundle().services);
  },

  async listParts(): Promise<Part[]> {
    return await withLatency(() => getBundle().parts);
  },

  async listReviews(): Promise<Review[]> {
    return await withLatency(() => getBundle().reviews);
  },

  async postJobMessage(args: {
    jobId: JobId;
    author: "customer" | "staff";
    body: string;
    attachments?: Array<Pick<JobAttachment, "filename" | "mime_type" | "size_bytes">>;
  }): Promise<JobMessage> {
    return await withLatency(() => {
      const { jobId, author } = args;
      const body = args.body.trim();
      if (!body) throw new Error("Message body is required.");

      const bundle = getBundle();
      const job = bundle.jobs.find((j) => j.id === jobId) ?? null;
      if (!job) throw new Error("Job not found.");

      const msg: JobMessage = {
        id: nextId("msg"),
        job_id: jobId,
        author,
        body,
        created_at: nowIso(),
        attachments:
          args.attachments && args.attachments.length > 0
            ? args.attachments.map((a, idx) => ({
                id: idx === 0 ? nextId("att") : `${nextId("att")}_${idx}`,
                job_id: jobId,
                filename: a.filename,
                mime_type: a.mime_type,
                size_bytes: a.size_bytes,
                url: "/mock/uploads/placeholder",
                created_at: nowIso(),
              }))
            : undefined,
      };

      const overrides = loadOverrides();
      const jobMessagesByJobId = { ...(overrides.jobMessagesByJobId ?? {}) };
      const existing = jobMessagesByJobId[jobId] ?? job.messages;
      jobMessagesByJobId[jobId] = [...existing, msg];

      saveOverrides({
        ...overrides,
        jobMessagesByJobId,
      });

      return msg;
    });
  },

  computeEstimateTotalCents,
};

export type MockApi = typeof mockApi;
export { computeEstimateTotalCents };
