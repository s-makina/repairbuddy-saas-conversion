export type TenantStatus = "trial" | "active" | "past_due" | "suspended" | "closed";

export type UserRole = "owner" | "member" | string;

export type UserStatus = "pending" | "active" | "inactive" | "suspended";

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
