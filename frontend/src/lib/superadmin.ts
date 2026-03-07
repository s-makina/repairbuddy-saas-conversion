/**
 * superadmin.ts — Unified superadmin API service
 *
 * All API calls needed by the V2 superadmin React pages in one place.
 * Billing admin functions are re-exported from billing.ts to keep a single
 * source of truth, while new endpoints (users, audit, analytics, currencies,
 * impersonation log) are defined here.
 *
 * Usage:
 *   import { getDashboardKpis, listAdminBusinesses, ... } from "@/lib/superadmin";
 */

import { apiFetch, apiDownload } from "@/lib/api";
import type {
  AdminDashboardKpis,
  AdminSalesResponse,
  AdminAnalyticsData,
  AdminUser,
  ImpersonationSession,
  PlatformCurrency,
  PlatformSettings,
  PaginatedResponse,
  Tenant,
} from "@/lib/types";

// ─────────────────────────────────────────────────────────────────────────────
// Re-exports from billing.ts (admin-side billing management)
// ─────────────────────────────────────────────────────────────────────────────
export {
  getBillingCatalog,
  listBillingIntervals,
  createBillingInterval,
  updateBillingInterval,
  setBillingIntervalActive,
  createBillingPlan,
  updateBillingPlan,
  createDraftBillingPlanVersionFromActive,
  validateBillingPlanVersionDraft,
  syncBillingPlanVersionEntitlements,
  createBillingPrice,
  updateBillingPrice,
  deleteBillingPrice,
  activateBillingPlanVersion,
  retireBillingPlanVersion,
  createEntitlementDefinition,
  updateEntitlementDefinition,
  deleteEntitlementDefinition,
  getTenantSubscriptions,
  assignTenantSubscription,
  cancelTenantSubscription,
  getTenantInvoices,
  getTenantInvoice,
  createInvoiceFromSubscription,
  issueInvoice,
  markInvoicePaid,
  downloadInvoicePdf,
} from "@/lib/billing";

export type {
  BillingCatalogPayload,
  TenantSubscriptionsPayload,
  TenantInvoicesPayload,
} from "@/lib/billing";

// ─────────────────────────────────────────────────────────────────────────────
// Dashboard
// ─────────────────────────────────────────────────────────────────────────────

export async function getDashboardKpis(): Promise<AdminDashboardKpis> {
  return apiFetch<AdminDashboardKpis>("/api/admin/dashboard/kpis");
}

export async function getDashboardSales(): Promise<AdminSalesResponse> {
  return apiFetch<AdminSalesResponse>("/api/admin/dashboard/sales-last-12-months");
}

// ─────────────────────────────────────────────────────────────────────────────
// Platform Settings
// ─────────────────────────────────────────────────────────────────────────────

export async function getPlatformSettings(): Promise<PlatformSettings> {
  return apiFetch<PlatformSettings>("/api/admin/settings");
}

export async function updatePlatformSettings(
  data: Partial<Pick<PlatformSettings, "app" | "billing" | "mail">>
): Promise<PlatformSettings> {
  return apiFetch<PlatformSettings>("/api/admin/settings", {
    method: "PATCH",
    body: data,
  });
}

// ─────────────────────────────────────────────────────────────────────────────
// Business (Tenant) Management
// ─────────────────────────────────────────────────────────────────────────────

export type ListBusinessesParams = {
  q?: string;
  status?: string;
  sort?: string;
  dir?: "asc" | "desc";
  page?: number;
  per_page?: number;
};

export type AdminBusinessesPayload = {
  data: Tenant[];
  meta: { total: number; per_page: number; current_page: number; last_page: number };
};

export async function listAdminBusinesses(
  params: ListBusinessesParams = {}
): Promise<AdminBusinessesPayload> {
  const qs = new URLSearchParams();
  if (params.q) qs.set("q", params.q);
  if (params.status) qs.set("status", params.status);
  if (params.sort) qs.set("sort", params.sort);
  if (params.dir) qs.set("dir", params.dir);
  if (params.page) qs.set("page", String(params.page));
  if (params.per_page) qs.set("per_page", String(params.per_page));

  return apiFetch<AdminBusinessesPayload>(
    `/api/admin/businesses${qs.toString() ? `?${qs.toString()}` : ""}`
  );
}

export async function getAdminBusinessStats(
  params: Pick<ListBusinessesParams, "q" | "status"> = {}
): Promise<{ total: number; by_status: Record<string, number> }> {
  const qs = new URLSearchParams();
  if (params.q) qs.set("q", params.q);
  if (params.status) qs.set("status", params.status);

  return apiFetch<{ total: number; by_status: Record<string, number> }>(
    `/api/admin/businesses/stats${qs.toString() ? `?${qs.toString()}` : ""}`
  );
}

export async function getAdminBusiness(
  tenantId: number
): Promise<{ tenant: Tenant }> {
  return apiFetch<{ tenant: Tenant }>(`/api/admin/businesses/${tenantId}`);
}

export async function getAdminBusinessEntitlements(
  tenantId: number
): Promise<{ tenant: Tenant; entitlements: Record<string, unknown> }> {
  return apiFetch<{ tenant: Tenant; entitlements: Record<string, unknown> }>(
    `/api/admin/businesses/${tenantId}/entitlements`
  );
}

export async function getAdminBusinessAudit(
  tenantId: number,
  params: { page?: number; per_page?: number } = {}
): Promise<unknown> {
  const qs = new URLSearchParams();
  if (params.page) qs.set("page", String(params.page));
  if (params.per_page) qs.set("per_page", String(params.per_page));

  return apiFetch<unknown>(
    `/api/admin/businesses/${tenantId}/audit${qs.toString() ? `?${qs.toString()}` : ""}`
  );
}

export async function createBusiness(args: {
  name: string;
  slug: string;
  contactEmail?: string;
  contactPhone?: string;
  currency?: string;
  billingCountry?: string;
  timezone?: string;
  language?: string;
  status?: "trial" | "active";
  ownerName: string;
  ownerEmail: string;
  ownerPassword: string;
  ownerPhone?: string;
  skipEmailVerification?: boolean;
  mustChangePassword?: boolean;
  reason?: string;
}): Promise<{ tenant: Tenant; owner: import("@/lib/types").User }> {
  return apiFetch<{ tenant: Tenant; owner: import("@/lib/types").User }>("/api/admin/businesses", {
    method: "POST",
    body: {
      name: args.name,
      slug: args.slug,
      contact_email: args.contactEmail,
      contact_phone: args.contactPhone,
      currency: args.currency,
      billing_country: args.billingCountry,
      timezone: args.timezone,
      language: args.language,
      status: args.status,
      owner_name: args.ownerName,
      owner_email: args.ownerEmail,
      owner_password: args.ownerPassword,
      owner_phone: args.ownerPhone,
      skip_email_verification: args.skipEmailVerification,
      must_change_password: args.mustChangePassword,
      reason: args.reason,
    },
  });
}

export async function suspendBusiness(args: {
  tenantId: number;
  reason: string;
}): Promise<{ tenant: Tenant }> {
  return apiFetch<{ tenant: Tenant }>(`/api/admin/businesses/${args.tenantId}/suspend`, {
    method: "PATCH",
    body: { reason: args.reason },
  });
}

export async function unsuspendBusiness(args: {
  tenantId: number;
  reason?: string;
}): Promise<{ tenant: Tenant }> {
  return apiFetch<{ tenant: Tenant }>(`/api/admin/businesses/${args.tenantId}/unsuspend`, {
    method: "PATCH",
    body: { reason: args.reason },
  });
}

export async function closeBusiness(args: {
  tenantId: number;
  reason: string;
}): Promise<{ tenant: Tenant }> {
  return apiFetch<{ tenant: Tenant }>(`/api/admin/businesses/${args.tenantId}/close`, {
    method: "PATCH",
    body: { reason: args.reason },
  });
}

export async function resetBusinessOwnerPassword(args: {
  tenantId: number;
  reason?: string;
}): Promise<{ message: string }> {
  return apiFetch<{ message: string }>(
    `/api/admin/businesses/${args.tenantId}/owner/reset-password`,
    { method: "POST", body: { reason: args.reason } }
  );
}

export async function setBusinessPlan(args: {
  tenantId: number;
  planId: number;
  reason?: string;
}): Promise<{ tenant: Tenant }> {
  return apiFetch<{ tenant: Tenant }>(`/api/admin/businesses/${args.tenantId}/plan`, {
    method: "PUT",
    body: { plan_id: args.planId, reason: args.reason },
  });
}

export async function setBusinessEntitlementOverrides(args: {
  tenantId: number;
  overrides: Record<string, unknown>;
  reason?: string;
}): Promise<{ tenant: Tenant }> {
  return apiFetch<{ tenant: Tenant }>(
    `/api/admin/businesses/${args.tenantId}/entitlements`,
    { method: "PUT", body: { overrides: args.overrides, reason: args.reason } }
  );
}

export async function exportBusinesses(
  params: Omit<ListBusinessesParams, "page" | "per_page"> = {}
): Promise<void> {
  const qs = new URLSearchParams();
  if (params.q) qs.set("q", params.q);
  if (params.status) qs.set("status", params.status);
  if (params.sort) qs.set("sort", params.sort);
  if (params.dir) qs.set("dir", params.dir);

  await apiDownload(
    `/api/admin/businesses/export${qs.toString() ? `?${qs.toString()}` : ""}`,
    { filename: "businesses_export.xlsx" }
  );
}

export async function getAdminBusinessDiagnostics(
  tenantId: number
): Promise<unknown> {
  return apiFetch<unknown>(`/api/admin/businesses/${tenantId}/diagnostics`);
}

// ─────────────────────────────────────────────────────────────────────────────
// Users Directory (cross-tenant)
// ─────────────────────────────────────────────────────────────────────────────

export type ListAdminUsersParams = {
  q?: string;
  status?: string;
  role?: string;
  tenant_id?: number;
  sort?: string;
  dir?: "asc" | "desc";
  page?: number;
  per_page?: number;
};

export async function listAdminUsers(
  params: ListAdminUsersParams = {}
): Promise<PaginatedResponse<AdminUser>> {
  const qs = new URLSearchParams();
  if (params.q) qs.set("q", params.q);
  if (params.status) qs.set("status", params.status);
  if (params.role) qs.set("role", params.role);
  if (params.tenant_id) qs.set("tenant_id", String(params.tenant_id));
  if (params.sort) qs.set("sort", params.sort);
  if (params.dir) qs.set("dir", params.dir);
  if (params.page) qs.set("page", String(params.page));
  if (params.per_page) qs.set("per_page", String(params.per_page));

  return apiFetch<PaginatedResponse<AdminUser>>(
    `/api/admin/users${qs.toString() ? `?${qs.toString()}` : ""}`
  );
}

// ─────────────────────────────────────────────────────────────────────────────
// Impersonation
// ─────────────────────────────────────────────────────────────────────────────

export type ListImpersonationParams = {
  q?: string;
  status?: "active" | "completed" | "terminated" | "all";
  from?: string;
  to?: string;
  sort?: string;
  dir?: "asc" | "desc";
  page?: number;
  per_page?: number;
};

export async function listImpersonationSessions(
  params: ListImpersonationParams = {}
): Promise<PaginatedResponse<ImpersonationSession>> {
  const qs = new URLSearchParams();
  if (params.q) qs.set("q", params.q);
  if (params.status) qs.set("status", params.status);
  if (params.from) qs.set("from", params.from);
  if (params.to) qs.set("to", params.to);
  if (params.sort) qs.set("sort", params.sort);
  if (params.dir) qs.set("dir", params.dir);
  if (params.page) qs.set("page", String(params.page));
  if (params.per_page) qs.set("per_page", String(params.per_page));

  return apiFetch<PaginatedResponse<ImpersonationSession>>(
    `/api/admin/impersonation${qs.toString() ? `?${qs.toString()}` : ""}`
  );
}

export async function startImpersonation(args: {
  tenantId: number;
  targetUserId: number;
  reason: string;
  referenceId: string;
  durationMinutes?: number;
}): Promise<{ session: ImpersonationSession }> {
  return apiFetch<{ session: ImpersonationSession }>("/api/admin/impersonation", {
    method: "POST",
    body: {
      tenant_id: args.tenantId,
      target_user_id: args.targetUserId,
      reason: args.reason,
      reference_id: args.referenceId,
      duration_minutes: typeof args.durationMinutes === "number" ? args.durationMinutes : undefined,
    },
  });
}

export async function stopImpersonation(args: {
  sessionId: number;
  reason?: string;
}): Promise<{ session: ImpersonationSession }> {
  return apiFetch<{ session: ImpersonationSession }>(
    `/api/admin/impersonation/${args.sessionId}/stop`,
    { method: "POST", body: { reason: args.reason } }
  );
}

// ─────────────────────────────────────────────────────────────────────────────
// Platform Audit Log
// ─────────────────────────────────────────────────────────────────────────────

export type ListAuditLogsParams = {
  q?: string;
  action?: string;
  tenant_id?: number;
  from?: string;
  to?: string;
  sort?: string;
  dir?: "asc" | "desc";
  page?: number;
  per_page?: number;
};

export async function listAuditLogs(
  params: ListAuditLogsParams = {}
): Promise<PaginatedResponse<import("@/lib/types").PlatformAuditLog>> {
  const qs = new URLSearchParams();
  if (params.q) qs.set("q", params.q);
  if (params.action) qs.set("action", params.action);
  if (params.tenant_id) qs.set("tenant_id", String(params.tenant_id));
  if (params.from) qs.set("from", params.from);
  if (params.to) qs.set("to", params.to);
  if (params.sort) qs.set("sort", params.sort);
  if (params.dir) qs.set("dir", params.dir);
  if (params.page) qs.set("page", String(params.page));
  if (params.per_page) qs.set("per_page", String(params.per_page));

  return apiFetch<PaginatedResponse<import("@/lib/types").PlatformAuditLog>>(
    `/api/admin/audit${qs.toString() ? `?${qs.toString()}` : ""}`
  );
}

// ─────────────────────────────────────────────────────────────────────────────
// Platform Analytics
// ─────────────────────────────────────────────────────────────────────────────

export async function getAnalytics(
  params: { months?: number } = {}
): Promise<AdminAnalyticsData> {
  const qs = new URLSearchParams();
  if (typeof params.months === "number") qs.set("months", String(params.months));

  return apiFetch<AdminAnalyticsData>(
    `/api/admin/analytics${qs.toString() ? `?${qs.toString()}` : ""}`
  );
}

// ─────────────────────────────────────────────────────────────────────────────
// Platform Currencies
// ─────────────────────────────────────────────────────────────────────────────

export async function listCurrencies(): Promise<{ currencies: PlatformCurrency[] }> {
  return apiFetch<{ currencies: PlatformCurrency[] }>("/api/admin/currencies");
}

export async function createCurrency(args: {
  code: string;
  symbol: string;
  name: string;
  isActive?: boolean;
  sortOrder?: number;
}): Promise<{ currency: PlatformCurrency }> {
  return apiFetch<{ currency: PlatformCurrency }>("/api/admin/currencies", {
    method: "POST",
    body: {
      code: args.code.toUpperCase(),
      symbol: args.symbol,
      name: args.name,
      is_active: typeof args.isActive === "boolean" ? args.isActive : undefined,
      sort_order: typeof args.sortOrder === "number" ? args.sortOrder : undefined,
    },
  });
}

export async function updateCurrency(args: {
  id: number;
  symbol: string;
  name: string;
  isActive?: boolean;
  sortOrder?: number;
}): Promise<{ currency: PlatformCurrency }> {
  return apiFetch<{ currency: PlatformCurrency }>(`/api/admin/currencies/${args.id}`, {
    method: "PUT",
    body: {
      symbol: args.symbol,
      name: args.name,
      is_active: typeof args.isActive === "boolean" ? args.isActive : undefined,
      sort_order: typeof args.sortOrder === "number" ? args.sortOrder : undefined,
    },
  });
}

export async function deleteCurrency(id: number): Promise<{ deleted: boolean }> {
  return apiFetch<{ deleted: boolean }>(`/api/admin/currencies/${id}`, {
    method: "DELETE",
  });
}

export async function setCurrencyActive(args: {
  id: number;
  isActive: boolean;
}): Promise<{ currency: PlatformCurrency }> {
  return apiFetch<{ currency: PlatformCurrency }>(
    `/api/admin/currencies/${args.id}/active`,
    { method: "PATCH", body: { is_active: args.isActive } }
  );
}

// ─────────────────────────────────────────────────────────────────────────────
// Billing Plans (admin CRUD beyond what billing.ts provides)
// ─────────────────────────────────────────────────────────────────────────────

export type ListBillingPlansParams = {
  q?: string;
  include_inactive?: boolean;
  page?: number;
  per_page?: number;
};

export async function listBillingPlans(
  params: ListBillingPlansParams = {}
): Promise<PaginatedResponse<import("@/lib/types").BillingPlan>> {
  const qs = new URLSearchParams();
  if (params.q) qs.set("q", params.q);
  if (params.include_inactive) qs.set("include_inactive", "1");
  if (params.page) qs.set("page", String(params.page));
  if (params.per_page) qs.set("per_page", String(params.per_page));

  return apiFetch<PaginatedResponse<import("@/lib/types").BillingPlan>>(
    `/api/admin/billing/plans${qs.toString() ? `?${qs.toString()}` : ""}`
  );
}

export async function getAdminBillingPlan(
  planId: number
): Promise<{ plan: import("@/lib/types").BillingPlan }> {
  return apiFetch<{ plan: import("@/lib/types").BillingPlan }>(
    `/api/admin/billing/plans/${planId}`
  );
}

export async function deleteBillingPlan(args: {
  planId: number;
  reason?: string;
}): Promise<{ status: string }> {
  return apiFetch<{ status: string }>(`/api/admin/billing/plans/${args.planId}`, {
    method: "DELETE",
    body: args.reason ? { reason: args.reason } : undefined,
  });
}

// ─────────────────────────────────────────────────────────────────────────────
// Billing Intervals (delete)
// ─────────────────────────────────────────────────────────────────────────────

export async function deleteBillingInterval(args: {
  intervalId: number;
  reason?: string;
}): Promise<{ status: string }> {
  return apiFetch<{ status: string }>(
    `/api/admin/billing/intervals/${args.intervalId}`,
    {
      method: "DELETE",
      body: args.reason ? { reason: args.reason } : undefined,
    }
  );
}

// ─────────────────────────────────────────────────────────────────────────────
// Entitlement Definitions (list)
// ─────────────────────────────────────────────────────────────────────────────

export async function listEntitlementDefinitions(): Promise<{
  definitions: import("@/lib/types").EntitlementDefinition[];
}> {
  return apiFetch<{ definitions: import("@/lib/types").EntitlementDefinition[] }>(
    "/api/admin/billing/entitlement-definitions"
  );
}
