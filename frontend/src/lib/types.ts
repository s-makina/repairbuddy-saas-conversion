export type TenantStatus = "active" | "inactive";

export type UserRole = "owner" | "member" | string;

export interface Tenant {
  id: number;
  name: string;
  slug: string;
  status: TenantStatus;
  contact_email?: string | null;
  created_at?: string;
  updated_at?: string;
}

export interface User {
  id: number;
  name: string;
  email: string;
  email_verified_at?: string | null;
  tenant_id?: number | null;
  role?: UserRole | null;
  is_admin: boolean;
  otp_enabled?: boolean;
  otp_confirmed_at?: string | null;
  created_at?: string;
  updated_at?: string;
}
