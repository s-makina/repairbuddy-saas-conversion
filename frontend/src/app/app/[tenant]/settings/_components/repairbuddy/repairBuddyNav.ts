export type RepairBuddyNavItem = {
  key: string;
  label: string;
  description?: string;
};

export const repairBuddyNav: RepairBuddyNavItem[] = [
  { key: "general", label: "General" },
  { key: "currency", label: "Currency" },
  { key: "invoices-reports", label: "Invoices & Reports" },
  { key: "job-statuses", label: "Statuses" },
  { key: "payments", label: "Payments" },
  { key: "reviews", label: "Reviews" },
  { key: "estimates", label: "Estimates" },
  { key: "my-account", label: "My Account" },
  { key: "devices-brands", label: "Devices & Brands" },
  { key: "pages-setup", label: "Pages Setup" },
  { key: "sms", label: "SMS" },
  { key: "taxes", label: "Taxes" },
  { key: "service-settings", label: "Service Settings" },
  { key: "time-logs", label: "Time Logs" },
  { key: "maintenance-reminders", label: "Maintenance Reminders" },
  { key: "styling-labels", label: "Styling & Labels" },
  { key: "signature-workflow", label: "Signature Workflow" },
  { key: "booking", label: "Booking" },
];
