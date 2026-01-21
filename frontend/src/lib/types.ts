export type TenantStatus = "trial" | "active" | "past_due" | "suspended" | "closed";

export type UserRole = "owner" | "member" | string;

export type UserStatus = "pending" | "active" | "inactive" | "suspended";

export interface Plan {
  id: number;
  name: string;
  code: string;
  price_display?: string | null;
  billing_interval?: string | null;
  entitlements?: Record<string, unknown> | null;
  created_at?: string;
  updated_at?: string;
}

export interface Permission {
  id: number;
  name: string;
}

export interface Role {
  id: number;
  tenant_id?: number | null;
  name: string;
  permissions?: Permission[];
}

export interface Tenant {
  id: number;
  name: string;
  slug: string;
  status: TenantStatus;
  contact_email?: string | null;
  contact_phone?: string | null;
  plan_id?: number | null;
  plan?: Plan | null;
  entitlement_overrides?: Record<string, unknown> | null;
  currency?: string | null;
  billing_country?: string | null;
  billing_vat_number?: string | null;
  billing_address_json?: Record<string, unknown> | null;
  timezone?: string | null;
  language?: string | null;
  brand_color?: string | null;
  logo_path?: string | null;
  logo_url?: string | null;
  setup_completed_at?: string | null;
  setup_step?: string | null;
  setup_state?: Record<string, unknown> | null;
  activated_at?: string | null;
  suspended_at?: string | null;
  suspension_reason?: string | null;
  closed_at?: string | null;
  closed_reason?: string | null;
  data_retention_days?: number | null;
  billing_snapshot?: TenantBillingSnapshot | null;
  created_at?: string;
  updated_at?: string;
}

export interface TenantBillingSnapshot {
  subscription_status?: string | null;
  subscription_currency?: string | null;
  subscription_current_period_end?: string | null;
  subscription_cancel_at_period_end?: boolean;
  plan_name?: string | null;
  price_amount_cents?: number | null;
  price_interval?: string | null;
  mrr_cents?: number | null;
  outstanding_invoices_count?: number | null;
  outstanding_balance_cents?: number | null;
  last_invoice?: {
    id: number;
    invoice_number?: string | null;
    status?: string | null;
    currency?: string | null;
    total_cents?: number | null;
    issued_at?: string | null;
    paid_at?: string | null;
  } | null;
}

export interface User {
  id: number;
  name: string;
  email: string;
  avatar_url?: string | null;
  email_verified_at?: string | null;
  tenant_id?: number | null;
  role?: UserRole | null;
  role_id?: number | null;
  role_model?: Role | null;
  status?: UserStatus | null;
  is_admin: boolean;
  otp_enabled?: boolean;
  otp_confirmed_at?: string | null;
  created_at?: string;
  updated_at?: string;
}

export interface ImpersonationInfo {
  session_id: number;
  tenant_id: number;
  target_user_id: number;
  started_at?: string | null;
  expires_at?: string | null;
  reference_id?: string | null;
}

export interface PlatformAuditLog {
  id: number;
  actor_user_id?: number | null;
  tenant_id?: number | null;
  action: string;
  reason?: string | null;
  metadata?: Record<string, unknown> | null;
  ip?: string | null;
  user_agent?: string | null;
  created_at?: string;
  updated_at?: string;
  actor?: User | null;
}

export interface AuthEvent {
  id: number;
  tenant_id?: number | null;
  user_id?: number | null;
  email?: string | null;
  event_type: string;
  ip?: string | null;
  user_agent?: string | null;
  metadata?: Record<string, unknown> | null;
  created_at?: string;
  updated_at?: string;
}

export interface BillingPlan {
  id: number;
  code: string;
  name: string;
  description?: string | null;
  is_active: boolean;
  created_at?: string;
  updated_at?: string;
  versions?: BillingPlanVersion[];
}

export interface BillingPlanVersion {
  id: number;
  billing_plan_id: number;
  version: number;
  status: string;
  locked_at?: string | null;
  activated_at?: string | null;
  retired_at?: string | null;
  created_at?: string;
  updated_at?: string;
  plan?: BillingPlan;
  prices?: BillingPrice[];
  entitlements?: PlanEntitlement[];
}

export interface BillingInterval {
  id: number;
  code: string;
  name: string;
  months: number;
  is_active: boolean;
  created_at?: string;
  updated_at?: string;
}

export interface BillingPrice {
  id: number;
  billing_plan_version_id: number;
  currency: string;
  interval: string;
  billing_interval_id?: number | null;
  interval_model?: BillingInterval | null;
  amount_cents: number;
  trial_days?: number | null;
  is_default: boolean;
  default_for_currency_interval?: string | null;
  created_at?: string;
  updated_at?: string;
}

export interface EntitlementDefinition {
  id: number;
  code: string;
  name: string;
  value_type: string;
  description?: string | null;
  is_premium?: boolean;
  created_at?: string;
  updated_at?: string;
}

export interface PlanEntitlement {
  id: number;
  billing_plan_version_id: number;
  entitlement_definition_id: number;
  value_json: unknown;
  definition?: EntitlementDefinition;
}

export interface TenantSubscription {
  id: number;
  tenant_id: number;
  billing_plan_version_id: number;
  billing_price_id: number;
  currency: string;
  status: string;
  started_at?: string | null;
  current_period_start?: string | null;
  current_period_end?: string | null;
  cancel_at_period_end?: boolean;
  canceled_at?: string | null;
  plan_version?: BillingPlanVersion;
  price?: BillingPrice;
}

export interface InvoiceLine {
  id: number;
  invoice_id: number;
  description: string;
  quantity: number;
  unit_amount_cents: number;
  subtotal_cents: number;
  tax_rate_percent?: string | number | null;
  tax_cents: number;
  total_cents: number;
  tax_meta_json?: Record<string, unknown> | null;
  created_at?: string;
  updated_at?: string;
}

export interface Invoice {
  id: number;
  tenant_id: number;
  tenant_subscription_id?: number | null;
  invoice_number: string;
  status: string;
  currency: string;
  subtotal_cents: number;
  tax_cents: number;
  total_cents: number;
  seller_country: string;
  billing_country?: string | null;
  billing_vat_number?: string | null;
  billing_address_json?: Record<string, unknown> | null;
  tax_details_json?: Record<string, unknown> | null;
  issued_at?: string | null;
  paid_at?: string | null;
  paid_method?: string | null;
  paid_note?: string | null;
  created_at?: string;
  updated_at?: string;
  lines?: InvoiceLine[];
  subscription?: TenantSubscription;
}
