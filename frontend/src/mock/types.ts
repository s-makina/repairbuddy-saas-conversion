export type JobId = `job_${string}`;
export type ClientId = `client_${string}`;
export type EstimateId = `estimate_${string}`;
export type AppointmentId = `appt_${string}`;
export type PaymentId = `pay_${string}`;
export type ExpenseId = `exp_${string}`;
export type TimeLogId = `timelog_${string}`;
export type ReviewId = `review_${string}`;
export type DeviceId = `device_${string}`;
export type DeviceBrandId = `brand_${string}`;
export type DeviceTypeId = `type_${string}`;
export type CustomerDeviceId = `custdev_${string}`;
export type ServiceId = `service_${string}`;
export type PartId = `part_${string}`;

export type ISODateTime = string;

export type MoneyAmount = {
  currency: string;
  amount_cents: number;
};

export type JobStatusKey =
  | "new_quote"
  | "in_process"
  | "ready"
  | "completed"
  | "delivered"
  | "cancelled";

export type JobStatus = {
  key: JobStatusKey;
  label: string;
};

export type JobAttachment = {
  id: string;
  job_id: JobId;
  filename: string;
  mime_type: string;
  size_bytes: number;
  url: string;
  created_at: ISODateTime;
};

export type JobMessage = {
  id: string;
  job_id: JobId;
  author: "customer" | "staff";
  body: string;
  created_at: ISODateTime;
  attachments?: JobAttachment[];
};

export type JobTimelineEvent = {
  id: string;
  job_id: JobId;
  type: "status_changed" | "note" | "payment" | "estimate";
  title: string;
  created_at: ISODateTime;
  meta?: Record<string, unknown>;
};

export type Client = {
  id: ClientId;
  name: string;
  email?: string | null;
  phone?: string | null;
  created_at: ISODateTime;
};

export type DeviceBrand = {
  id: DeviceBrandId;
  name: string;
};

export type DeviceType = {
  id: DeviceTypeId;
  name: string;
};

export type Device = {
  id: DeviceId;
  brand_id: DeviceBrandId;
  type_id: DeviceTypeId;
  model: string;
};

export type CustomerDevice = {
  id: CustomerDeviceId;
  client_id: ClientId;
  device_id: DeviceId;
  serial_number?: string | null;
  notes?: string | null;
};

export type EstimateStatus = "pending" | "approved" | "rejected";

export type EstimateLine = {
  id: string;
  label: string;
  qty: number;
  unit_price: MoneyAmount;
};

export type Estimate = {
  id: EstimateId;
  job_id: JobId;
  client_id: ClientId;
  status: EstimateStatus;
  lines: EstimateLine[];
  created_at: ISODateTime;
  updated_at: ISODateTime;
};

export type PaymentStatus = "pending" | "paid" | "refunded" | "failed";

export type PaymentMethod = "cash" | "card" | "bank_transfer" | "other";

export type Payment = {
  id: PaymentId;
  job_id: JobId;
  status: PaymentStatus;
  method: PaymentMethod;
  amount: MoneyAmount;
  created_at: ISODateTime;
};

export type ExpenseCategory = {
  id: string;
  name: string;
};

export type Expense = {
  id: ExpenseId;
  job_id?: JobId | null;
  category_id?: string | null;
  label: string;
  amount: MoneyAmount;
  created_at: ISODateTime;
};

export type TimeLog = {
  id: TimeLogId;
  job_id: JobId;
  user_label: string;
  minutes: number;
  rate: MoneyAmount;
  created_at: ISODateTime;
};

export type Review = {
  id: ReviewId;
  job_id: JobId;
  client_id: ClientId;
  rating: 1 | 2 | 3 | 4 | 5;
  comment: string;
  created_at: ISODateTime;
};

export type AppointmentStatus = "requested" | "confirmed" | "cancelled";

export type Appointment = {
  id: AppointmentId;
  scheduled_at: ISODateTime;
  status: AppointmentStatus;
  client_name: string;
  client_email?: string | null;
  client_phone?: string | null;
  notes?: string | null;
  created_at: ISODateTime;
};

export type Service = {
  id: ServiceId;
  name: string;
  description?: string | null;
  base_price?: MoneyAmount | null;
};

export type Part = {
  id: PartId;
  name: string;
  sku?: string | null;
  price?: MoneyAmount | null;
  stock?: number | null;
};

export type Job = {
  id: JobId;
  case_number: string;
  title: string;
  status: JobStatusKey;
  client_id: ClientId;
  customer_device_ids: CustomerDeviceId[];
  estimate_id?: EstimateId | null;
  payment_ids: PaymentId[];
  created_at: ISODateTime;
  updated_at: ISODateTime;
  timeline: JobTimelineEvent[];
  messages: JobMessage[];
  attachments: JobAttachment[];
};

export type MockDataBundle = {
  statuses: JobStatus[];
  clients: Client[];
  device_brands: DeviceBrand[];
  device_types: DeviceType[];
  devices: Device[];
  customer_devices: CustomerDevice[];
  jobs: Job[];
  estimates: Estimate[];
  appointments: Appointment[];
  payments: Payment[];
  expenses: Expense[];
  expense_categories: ExpenseCategory[];
  time_logs: TimeLog[];
  reviews: Review[];
  services: Service[];
  parts: Part[];
};
