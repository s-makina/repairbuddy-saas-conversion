import type {
  Appointment,
  Client,
  CustomerDevice,
  Device,
  DeviceBrand,
  DeviceType,
  Estimate,
  Expense,
  ExpenseCategory,
  Job,
  JobAttachment,
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

function iso(d: string) {
  return new Date(d).toISOString();
}

const statuses: JobStatus[] = [
  { key: "new_quote", label: "New/Quote" },
  { key: "in_process", label: "In Process" },
  { key: "ready", label: "Ready" },
  { key: "completed", label: "Completed" },
  { key: "delivered", label: "Delivered" },
  { key: "cancelled", label: "Cancelled" },
];

const clients: Client[] = [
  { id: "client_001", name: "Alex Johnson", email: "alex@example.com", phone: "+1 555 0101", created_at: iso("2026-01-05T10:00:00Z") },
  { id: "client_002", name: "Sam Rivera", email: "sam@example.com", phone: "+1 555 0102", created_at: iso("2026-01-06T09:00:00Z") },
  { id: "client_003", name: "Taylor Chen", email: "taylor@example.com", phone: "+1 555 0103", created_at: iso("2026-01-08T15:30:00Z") },
  { id: "client_004", name: "Jordan Patel", email: "jordan@example.com", phone: "+1 555 0104", created_at: iso("2026-01-10T11:45:00Z") },
  { id: "client_005", name: "Morgan Lee", email: "morgan@example.com", phone: "+1 555 0105", created_at: iso("2026-01-12T08:20:00Z") },
];

const device_brands: DeviceBrand[] = [
  { id: "brand_001", name: "Apple" },
  { id: "brand_002", name: "Dell" },
  { id: "brand_003", name: "HP" },
  { id: "brand_004", name: "Lenovo" },
  { id: "brand_005", name: "Samsung" },
];

const device_types: DeviceType[] = [
  { id: "type_001", name: "Laptop" },
  { id: "type_002", name: "Desktop" },
  { id: "type_003", name: "Phone" },
  { id: "type_004", name: "Tablet" },
  { id: "type_005", name: "Game Console" },
];

const devices: Device[] = [
  { id: "device_001", brand_id: "brand_002", type_id: "type_001", model: "XPS 13" },
  { id: "device_002", brand_id: "brand_001", type_id: "type_004", model: "iPad Pro" },
  { id: "device_003", brand_id: "brand_005", type_id: "type_003", model: "Galaxy S23" },
  { id: "device_004", brand_id: "brand_004", type_id: "type_001", model: "ThinkPad T14" },
  { id: "device_005", brand_id: "brand_003", type_id: "type_002", model: "Pavilion Desktop" },
];

const customer_devices: CustomerDevice[] = [
  {
    id: "custdev_001",
    client_id: "client_001",
    device_id: "device_001",
    serial_number: "SN-XPS13-0001",
    notes: "Battery drains quickly.",
  },
  {
    id: "custdev_002",
    client_id: "client_002",
    device_id: "device_004",
    serial_number: "SN-TP-0012",
    notes: "Intermittent boot issues.",
  },
  {
    id: "custdev_003",
    client_id: "client_003",
    device_id: "device_003",
    serial_number: "SN-GS23-031",
    notes: "Screen flickers at low brightness.",
  },
  {
    id: "custdev_004",
    client_id: "client_004",
    device_id: "device_002",
    serial_number: "SN-IPAD-888",
    notes: "Charging port is loose.",
  },
  {
    id: "custdev_005",
    client_id: "client_005",
    device_id: "device_005",
    serial_number: "SN-HPD-555",
    notes: "Runs very hot under load.",
  },
];

const payments: Payment[] = [
  {
    id: "pay_001",
    job_id: "job_001",
    status: "paid",
    method: "card",
    amount: { currency: "USD", amount_cents: 7500 },
    created_at: iso("2026-01-26T14:12:00Z"),
  },
  {
    id: "pay_002",
    job_id: "job_002",
    status: "pending",
    method: "cash",
    amount: { currency: "USD", amount_cents: 4500 },
    created_at: iso("2026-01-27T10:20:00Z"),
  },
  {
    id: "pay_003",
    job_id: "job_004",
    status: "paid",
    method: "bank_transfer",
    amount: { currency: "USD", amount_cents: 12000 },
    created_at: iso("2026-01-22T16:40:00Z"),
  },
];

const expense_categories: ExpenseCategory[] = [
  { id: "expcat_001", name: "Parts" },
  { id: "expcat_002", name: "Shipping" },
  { id: "expcat_003", name: "Tools" },
];

const expenses: Expense[] = [
  {
    id: "exp_001",
    job_id: "job_001",
    category_id: "expcat_001",
    label: "Replacement battery",
    amount: { currency: "USD", amount_cents: 3200 },
    created_at: iso("2026-01-26T09:05:00Z"),
  },
  {
    id: "exp_002",
    job_id: "job_002",
    category_id: "expcat_002",
    label: "Courier",
    amount: { currency: "USD", amount_cents: 1500 },
    created_at: iso("2026-01-27T11:10:00Z"),
  },
];

const time_logs: TimeLog[] = [
  {
    id: "timelog_001",
    job_id: "job_001",
    user_label: "Technician A",
    minutes: 90,
    rate: { currency: "USD", amount_cents: 9000 },
    created_at: iso("2026-01-26T12:10:00Z"),
  },
  {
    id: "timelog_002",
    job_id: "job_002",
    user_label: "Technician B",
    minutes: 45,
    rate: { currency: "USD", amount_cents: 8000 },
    created_at: iso("2026-01-27T12:30:00Z"),
  },
];

const attachments: JobAttachment[] = [
  {
    id: "att_001",
    job_id: "job_001",
    filename: "intake-photo.jpg",
    mime_type: "image/jpeg",
    size_bytes: 245_312,
    url: "/mock/uploads/intake-photo.jpg",
    created_at: iso("2026-01-26T08:58:00Z"),
  },
  {
    id: "att_002",
    job_id: "job_003",
    filename: "screen-video.mp4",
    mime_type: "video/mp4",
    size_bytes: 1_245_000,
    url: "/mock/uploads/screen-video.mp4",
    created_at: iso("2026-01-23T09:15:00Z"),
  },
];

const messages: JobMessage[] = [
  {
    id: "msg_001",
    job_id: "job_001",
    author: "customer",
    body: "Hi, I dropped it off today. Please let me know the ETA.",
    created_at: iso("2026-01-26T09:00:00Z"),
    attachments: [attachments[0]],
  },
  {
    id: "msg_002",
    job_id: "job_001",
    author: "staff",
    body: "Thanks! We are running diagnostics now.",
    created_at: iso("2026-01-26T09:20:00Z"),
  },
  {
    id: "msg_003",
    job_id: "job_003",
    author: "customer",
    body: "The screen flickers randomly. It’s worse at night.",
    created_at: iso("2026-01-23T09:10:00Z"),
    attachments: [attachments[1]],
  },
];

const makeTimeline = (jobId: Job["id"], items: Array<Omit<JobTimelineEvent, "job_id">>): JobTimelineEvent[] => {
  return items.map((x) => ({ ...x, job_id: jobId }));
};

const estimates: Estimate[] = [
  {
    id: "estimate_001",
    job_id: "job_001",
    client_id: "client_001",
    status: "approved",
    lines: [
      { id: "estline_001", label: "Battery replacement", qty: 1, unit_price: { currency: "USD", amount_cents: 7500 } },
    ],
    created_at: iso("2026-01-26T10:00:00Z"),
    updated_at: iso("2026-01-26T10:30:00Z"),
  },
  {
    id: "estimate_002",
    job_id: "job_003",
    client_id: "client_003",
    status: "pending",
    lines: [
      { id: "estline_002", label: "Display diagnostics", qty: 1, unit_price: { currency: "USD", amount_cents: 2500 } },
      { id: "estline_003", label: "Potential screen replacement", qty: 1, unit_price: { currency: "USD", amount_cents: 19900 } },
    ],
    created_at: iso("2026-01-23T10:20:00Z"),
    updated_at: iso("2026-01-23T10:20:00Z"),
  },
  {
    id: "estimate_003",
    job_id: "job_005",
    client_id: "client_005",
    status: "rejected",
    lines: [
      { id: "estline_004", label: "Thermal paste + cleaning", qty: 1, unit_price: { currency: "USD", amount_cents: 6900 } },
    ],
    created_at: iso("2026-01-19T12:00:00Z"),
    updated_at: iso("2026-01-20T09:00:00Z"),
  },
];

const jobs: Job[] = [
  {
    id: "job_001",
    case_number: "RB-10421",
    title: "Dell XPS 13 battery replacement",
    status: "in_process",
    client_id: "client_001",
    customer_device_ids: ["custdev_001"],
    estimate_id: "estimate_001",
    payment_ids: ["pay_001"],
    created_at: iso("2026-01-26T08:50:00Z"),
    updated_at: iso("2026-01-26T14:15:00Z"),
    timeline: makeTimeline("job_001", [
      { id: "tl_001", type: "status_changed", title: "Status set to New/Quote", created_at: iso("2026-01-26T08:50:00Z"), meta: { status: "new_quote" } },
      { id: "tl_002", type: "estimate", title: "Estimate created", created_at: iso("2026-01-26T10:00:00Z"), meta: { estimate_id: "estimate_001" } },
      { id: "tl_003", type: "status_changed", title: "Status set to In Process", created_at: iso("2026-01-26T10:45:00Z"), meta: { status: "in_process" } },
      { id: "tl_004", type: "payment", title: "Payment received", created_at: iso("2026-01-26T14:12:00Z"), meta: { payment_id: "pay_001" } },
    ]),
    messages: messages.filter((m) => m.job_id === "job_001"),
    attachments: attachments.filter((a) => a.job_id === "job_001"),
  },
  {
    id: "job_002",
    case_number: "RB-10418",
    title: "Lenovo ThinkPad intermittent boot",
    status: "new_quote",
    client_id: "client_002",
    customer_device_ids: ["custdev_002"],
    estimate_id: null,
    payment_ids: ["pay_002"],
    created_at: iso("2026-01-25T11:30:00Z"),
    updated_at: iso("2026-01-27T10:22:00Z"),
    timeline: makeTimeline("job_002", [
      { id: "tl_005", type: "status_changed", title: "Status set to New/Quote", created_at: iso("2026-01-25T11:30:00Z"), meta: { status: "new_quote" } },
    ]),
    messages: [],
    attachments: [],
  },
  {
    id: "job_003",
    case_number: "RB-10412",
    title: "Galaxy S23 screen flicker",
    status: "new_quote",
    client_id: "client_003",
    customer_device_ids: ["custdev_003"],
    estimate_id: "estimate_002",
    payment_ids: [],
    created_at: iso("2026-01-23T09:00:00Z"),
    updated_at: iso("2026-01-23T10:20:00Z"),
    timeline: makeTimeline("job_003", [
      { id: "tl_006", type: "status_changed", title: "Status set to New/Quote", created_at: iso("2026-01-23T09:00:00Z"), meta: { status: "new_quote" } },
      { id: "tl_007", type: "estimate", title: "Estimate created", created_at: iso("2026-01-23T10:20:00Z"), meta: { estimate_id: "estimate_002" } },
    ]),
    messages: messages.filter((m) => m.job_id === "job_003"),
    attachments: attachments.filter((a) => a.job_id === "job_003"),
  },
  {
    id: "job_004",
    case_number: "RB-10399",
    title: "iPad charging port repair",
    status: "delivered",
    client_id: "client_004",
    customer_device_ids: ["custdev_004"],
    estimate_id: null,
    payment_ids: ["pay_003"],
    created_at: iso("2026-01-18T10:00:00Z"),
    updated_at: iso("2026-01-22T16:45:00Z"),
    timeline: makeTimeline("job_004", [
      { id: "tl_008", type: "status_changed", title: "Status set to Completed", created_at: iso("2026-01-21T14:00:00Z"), meta: { status: "completed" } },
      { id: "tl_009", type: "status_changed", title: "Status set to Delivered", created_at: iso("2026-01-22T16:45:00Z"), meta: { status: "delivered" } },
    ]),
    messages: [],
    attachments: [],
  },
  {
    id: "job_005",
    case_number: "RB-10380",
    title: "HP desktop overheating",
    status: "cancelled",
    client_id: "client_005",
    customer_device_ids: ["custdev_005"],
    estimate_id: "estimate_003",
    payment_ids: [],
    created_at: iso("2026-01-19T11:40:00Z"),
    updated_at: iso("2026-01-20T09:05:00Z"),
    timeline: makeTimeline("job_005", [
      { id: "tl_010", type: "status_changed", title: "Status set to Cancelled", created_at: iso("2026-01-20T09:05:00Z"), meta: { status: "cancelled" } },
    ]),
    messages: [],
    attachments: [],
  },
];

const reviews: Review[] = [
  {
    id: "review_001",
    job_id: "job_004",
    client_id: "client_004",
    rating: 5,
    comment: "Fast service and great communication.",
    created_at: iso("2026-01-23T08:00:00Z"),
  },
  {
    id: "review_002",
    job_id: "job_001",
    client_id: "client_001",
    rating: 4,
    comment: "Good work. Slight delay but handled well.",
    created_at: iso("2026-01-27T18:00:00Z"),
  },
];

const appointments: Appointment[] = [
  {
    id: "appt_001",
    scheduled_at: iso("2026-01-29T10:00:00Z"),
    status: "confirmed",
    client_name: "Alex Johnson",
    client_email: "alex@example.com",
    client_phone: "+1 555 0101",
    notes: "Drop off in the morning.",
    created_at: iso("2026-01-26T16:00:00Z"),
  },
  {
    id: "appt_002",
    scheduled_at: iso("2026-02-01T13:30:00Z"),
    status: "requested",
    client_name: "Sam Rivera",
    client_email: "sam@example.com",
    client_phone: "+1 555 0102",
    notes: "Prefer weekend.",
    created_at: iso("2026-01-27T09:45:00Z"),
  },
];

const services: Service[] = [
  {
    id: "service_001",
    name: "Diagnostics",
    description: "Hardware and software diagnostics.",
    base_price: { currency: "USD", amount_cents: 2500 },
  },
  {
    id: "service_002",
    name: "Battery replacement",
    description: "Laptop battery replacement service.",
    base_price: { currency: "USD", amount_cents: 7500 },
  },
  {
    id: "service_003",
    name: "Screen replacement",
    description: "Phone/tablet screen replacement (parts excluded).",
    base_price: { currency: "USD", amount_cents: 19900 },
  },
];

const parts: Part[] = [
  {
    id: "part_001",
    name: "Laptop battery (generic)",
    sku: "BAT-GEN-001",
    price: { currency: "USD", amount_cents: 3200 },
    stock: 12,
  },
  {
    id: "part_002",
    name: "USB-C port module",
    sku: "USB-C-MOD-020",
    price: { currency: "USD", amount_cents: 1800 },
    stock: 7,
  },
  {
    id: "part_003",
    name: "Thermal paste",
    sku: "THERM-PASTE-010",
    price: { currency: "USD", amount_cents: 900 },
    stock: 30,
  },
];

export const mockFixtures: MockDataBundle = {
  statuses,
  clients,
  device_brands,
  device_types,
  devices,
  customer_devices,
  jobs,
  estimates,
  appointments,
  payments,
  expenses,
  expense_categories,
  time_logs,
  reviews,
  services,
  parts,
};
