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
  plan_id?: number | null;
  plan?: Plan | null;
  entitlement_overrides?: Record<string, unknown> | null;
  activated_at?: string | null;
  suspended_at?: string | null;
  suspension_reason?: string | null;
  closed_at?: string | null;
  closed_reason?: string | null;
  data_retention_days?: number | null;
  created_at?: string;
  updated_at?: string;
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
