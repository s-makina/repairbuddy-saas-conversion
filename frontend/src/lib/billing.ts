import { ApiError, apiDownload, apiFetch } from "@/lib/api";
import type {
  BillingPlan,
  BillingPlanVersion,
  BillingPrice,
  BillingInterval,
  EntitlementDefinition,
  Invoice,
  Tenant,
  TenantSubscription,
  PlanEntitlement,
} from "@/lib/types";

export type BillingCatalogPayload = {
  billing_plans: BillingPlan[];
  entitlement_definitions: EntitlementDefinition[];
  billing_intervals?: BillingInterval[];
};

export async function getBillingCatalog(args?: {
  includeInactive?: boolean;
}): Promise<BillingCatalogPayload> {
  const qs = new URLSearchParams();
  if (typeof args?.includeInactive === "boolean") {
    qs.set("include_inactive", args.includeInactive ? "1" : "0");
  }

  return apiFetch<BillingCatalogPayload>(`/api/admin/billing/catalog${qs.toString() ? `?${qs.toString()}` : ""}`);
}

export async function listBillingIntervals(args?: { includeInactive?: boolean }): Promise<{ billing_intervals: BillingInterval[] }> {
  const qs = new URLSearchParams();
  if (typeof args?.includeInactive === "boolean") {
    qs.set("include_inactive", args.includeInactive ? "1" : "0");
  }

  return apiFetch<{ billing_intervals: BillingInterval[] }>(`/api/admin/billing/intervals${qs.toString() ? `?${qs.toString()}` : ""}`);
}

export async function createBillingInterval(args: {
  code?: string;
  name: string;
  months: number;
  isActive?: boolean;
}): Promise<{ interval: BillingInterval }> {
  return apiFetch<{ interval: BillingInterval }>("/api/admin/billing/intervals", {
    method: "POST",
    body: {
      code: args.code?.trim() || undefined,
      name: args.name,
      months: args.months,
      is_active: typeof args.isActive === "boolean" ? args.isActive : undefined,
    },
  });
}

export async function updateBillingInterval(args: {
  intervalId: number;
  code: string;
  name: string;
  months: number;
  isActive?: boolean;
  reason?: string;
}): Promise<{ interval: BillingInterval }> {
  return apiFetch<{ interval: BillingInterval }>(`/api/admin/billing/intervals/${args.intervalId}`, {
    method: "PUT",
    body: {
      code: args.code,
      name: args.name,
      months: args.months,
      is_active: typeof args.isActive === "boolean" ? args.isActive : undefined,
      reason: args.reason,
    },
  });
}

export async function setBillingIntervalActive(args: {
  intervalId: number;
  isActive: boolean;
  reason?: string;
}): Promise<{ interval: BillingInterval }> {
  return apiFetch<{ interval: BillingInterval }>(`/api/admin/billing/intervals/${args.intervalId}/active`, {
    method: "PATCH",
    body: {
      is_active: args.isActive,
      reason: args.reason,
    },
  });
}

export async function createBillingPlan(args: {
  name: string;
  code?: string;
  description?: string;
  isActive?: boolean;
}): Promise<{ plan: BillingPlan }> {
  return apiFetch<{ plan: BillingPlan }>("/api/admin/billing/plans", {
    method: "POST",
    body: {
      name: args.name,
      code: args.code?.trim() || undefined,
      description: args.description?.trim() || undefined,
      is_active: typeof args.isActive === "boolean" ? args.isActive : undefined,
    },
  });
}

export async function updateBillingPlan(args: {
  planId: number;
  name: string;
  code: string;
  description?: string;
  isActive?: boolean;
  reason?: string;
}): Promise<{ plan: BillingPlan }> {
  return apiFetch<{ plan: BillingPlan }>(`/api/admin/billing/plans/${args.planId}`, {
    method: "PUT",
    body: {
      name: args.name,
      code: args.code,
      description: args.description?.trim() || undefined,
      is_active: typeof args.isActive === "boolean" ? args.isActive : undefined,
      reason: args.reason,
    },
  });
}

export async function createDraftBillingPlanVersionFromActive(args: {
  planId: number;
  reason?: string;
}): Promise<{ version: BillingPlanVersion }> {
  return apiFetch<{ version: BillingPlanVersion }>(`/api/admin/billing/plans/${args.planId}/versions/draft-from-active`, {
    method: "POST",
    body: {
      reason: args.reason,
    },
  });
}

export async function validateBillingPlanVersionDraft(args: {
  versionId: number;
  reason?: string;
}): Promise<{ status: "ok" } | { message: string; errors?: string[] }> {
  try {
    return await apiFetch<{ status: "ok" } | { message: string; errors?: string[] }>(
      `/api/admin/billing/versions/${args.versionId}/validate`,
      {
        method: "POST",
        body: {
          reason: args.reason,
        },
      },
    );
  } catch (e) {
    if (e instanceof ApiError) {
      const data: unknown = e.data;
      if (data && typeof data === "object") {
        const maybe = data as { status?: unknown; message?: unknown; errors?: unknown };
        if (maybe.status === "ok") {
          return { status: "ok" };
        }
        if (typeof maybe.message === "string") {
          return {
            message: maybe.message,
            errors: Array.isArray(maybe.errors) ? maybe.errors.map(String) : undefined,
          };
        }
      }
    }
    throw e;
  }
}

export async function syncBillingPlanVersionEntitlements(args: {
  versionId: number;
  entitlements: Array<Pick<PlanEntitlement, "entitlement_definition_id" | "value_json">>;
  reason?: string;
}): Promise<{ version: BillingPlanVersion }> {
  return apiFetch<{ version: BillingPlanVersion }>(`/api/admin/billing/versions/${args.versionId}/entitlements/sync`, {
    method: "POST",
    body: {
      entitlements: args.entitlements,
      reason: args.reason,
    },
  });
}

export async function createBillingPrice(args: {
  versionId: number;
  currency: string;
  interval?: string;
  billingIntervalId?: number | null;
  amountCents: number;
  trialDays?: number | null;
  isDefault?: boolean;
  reason?: string;
}): Promise<{ price: BillingPrice; version: BillingPlanVersion }> {
  return apiFetch<{ price: BillingPrice; version: BillingPlanVersion }>(`/api/admin/billing/versions/${args.versionId}/prices`, {
    method: "POST",
    body: {
      currency: args.currency,
      billing_interval_id: typeof args.billingIntervalId === "number" ? args.billingIntervalId : undefined,
      interval: args.interval,
      amount_cents: args.amountCents,
      trial_days: typeof args.trialDays === "number" ? args.trialDays : undefined,
      is_default: typeof args.isDefault === "boolean" ? args.isDefault : undefined,
      reason: args.reason,
    },
  });
}

export async function updateBillingPrice(args: {
  priceId: number;
  amountCents: number;
  trialDays?: number | null;
  isDefault?: boolean;
  reason?: string;
}): Promise<{ price: BillingPrice; version: BillingPlanVersion }> {
  return apiFetch<{ price: BillingPrice; version: BillingPlanVersion }>(`/api/admin/billing/prices/${args.priceId}`, {
    method: "PATCH",
    body: {
      amount_cents: args.amountCents,
      trial_days: typeof args.trialDays === "number" ? args.trialDays : undefined,
      is_default: typeof args.isDefault === "boolean" ? args.isDefault : undefined,
      reason: args.reason,
    },
  });
}

export async function deleteBillingPrice(args: {
  priceId: number;
  reason?: string;
}): Promise<{ status: "ok"; version: BillingPlanVersion }> {
  return apiFetch<{ status: "ok"; version: BillingPlanVersion }>(`/api/admin/billing/prices/${args.priceId}`, {
    method: "DELETE",
    body: {
      reason: args.reason,
    },
  });
}

export async function activateBillingPlanVersion(args: {
  versionId: number;
  confirm: string;
  reason?: string;
}): Promise<{ version: BillingPlanVersion }> {
  return apiFetch<{ version: BillingPlanVersion }>(`/api/admin/billing/versions/${args.versionId}/activate`, {
    method: "POST",
    body: {
      confirm: args.confirm,
      reason: args.reason,
    },
  });
}

export async function retireBillingPlanVersion(args: {
  versionId: number;
  reason?: string;
}): Promise<{ version: BillingPlanVersion }> {
  return apiFetch<{ version: BillingPlanVersion }>(`/api/admin/billing/versions/${args.versionId}/retire`, {
    method: "POST",
    body: {
      reason: args.reason,
    },
  });
}

export async function createEntitlementDefinition(args: {
  code?: string;
  name: string;
  valueType: string;
  description?: string;
  isPremium?: boolean;
}): Promise<{ definition: EntitlementDefinition }> {
  return apiFetch<{ definition: EntitlementDefinition }>("/api/admin/billing/entitlement-definitions", {
    method: "POST",
    body: {
      code: args.code?.trim() || undefined,
      name: args.name,
      value_type: args.valueType,
      description: args.description?.trim() || undefined,
      is_premium: typeof args.isPremium === "boolean" ? args.isPremium : undefined,
    },
  });
}

export async function updateEntitlementDefinition(args: {
  id: number;
  code: string;
  name: string;
  valueType: string;
  description?: string;
  isPremium?: boolean;
  reason?: string;
}): Promise<{ definition: EntitlementDefinition }> {
  return apiFetch<{ definition: EntitlementDefinition }>(`/api/admin/billing/entitlement-definitions/${args.id}`, {
    method: "PUT",
    body: {
      code: args.code,
      name: args.name,
      value_type: args.valueType,
      description: args.description?.trim() || undefined,
      is_premium: typeof args.isPremium === "boolean" ? args.isPremium : undefined,
      reason: args.reason,
    },
  });
}

export async function deleteEntitlementDefinition(args: {
  id: number;
  reason?: string;
}): Promise<{ status: "ok" }> {
  return apiFetch<{ status: "ok" }>(`/api/admin/billing/entitlement-definitions/${args.id}`, {
    method: "DELETE",
    body: {
      reason: args.reason,
    },
  });
}

export type TenantSubscriptionsPayload = {
  tenant: Tenant;
  subscriptions: TenantSubscription[];
};

export async function getTenantSubscriptions(tenantId: number): Promise<TenantSubscriptionsPayload> {
  return apiFetch<TenantSubscriptionsPayload>(`/api/admin/businesses/${tenantId}/subscriptions`);
}

export async function assignTenantSubscription(args: {
  tenantId: number;
  billingPlanVersionId: number;
  billingPriceId: number;
  reason?: string;
}): Promise<{ subscription: TenantSubscription }> {
  return apiFetch<{ subscription: TenantSubscription }>(`/api/admin/businesses/${args.tenantId}/subscriptions`, {
    method: "POST",
    body: {
      billing_plan_version_id: args.billingPlanVersionId,
      billing_price_id: args.billingPriceId,
      reason: args.reason,
    },
  });
}

export async function cancelTenantSubscription(args: {
  tenantId: number;
  subscriptionId: number;
  atPeriodEnd?: boolean;
  reason?: string;
}): Promise<{ subscription: TenantSubscription }> {
  return apiFetch<{ subscription: TenantSubscription }>(
    `/api/admin/businesses/${args.tenantId}/subscriptions/${args.subscriptionId}/cancel`,
    {
      method: "POST",
      body: {
        at_period_end: typeof args.atPeriodEnd === "boolean" ? args.atPeriodEnd : undefined,
        reason: args.reason,
      },
    },
  );
}

export type TenantInvoicesPayload = {
  tenant: Tenant;
  invoices: Invoice[];
};

export async function getTenantInvoices(args: {
  tenantId: number;
  status?: string;
  limit?: number;
}): Promise<TenantInvoicesPayload> {
  const qs = new URLSearchParams();
  if (args.status && args.status.trim().length > 0) qs.set("status", args.status.trim());
  if (typeof args.limit === "number" && Number.isFinite(args.limit)) qs.set("limit", String(args.limit));

  return apiFetch<TenantInvoicesPayload>(`/api/admin/businesses/${args.tenantId}/invoices${qs.toString() ? `?${qs.toString()}` : ""}`);
}

export async function getTenantInvoice(args: {
  tenantId: number;
  invoiceId: number;
}): Promise<{ tenant: Tenant; invoice: Invoice }> {
  return apiFetch<{ tenant: Tenant; invoice: Invoice }>(`/api/admin/businesses/${args.tenantId}/invoices/${args.invoiceId}`);
}

export async function createInvoiceFromSubscription(args: {
  tenantId: number;
  tenantSubscriptionId: number;
  reason?: string;
}): Promise<{ invoice: Invoice }> {
  return apiFetch<{ invoice: Invoice }>(`/api/admin/businesses/${args.tenantId}/invoices`, {
    method: "POST",
    body: {
      tenant_subscription_id: args.tenantSubscriptionId,
      reason: args.reason,
    },
  });
}

export async function issueInvoice(args: {
  tenantId: number;
  invoiceId: number;
  reason?: string;
}): Promise<{ invoice: Invoice }> {
  return apiFetch<{ invoice: Invoice }>(`/api/admin/businesses/${args.tenantId}/invoices/${args.invoiceId}/issue`, {
    method: "POST",
    body: {
      reason: args.reason,
    },
  });
}

export async function markInvoicePaid(args: {
  tenantId: number;
  invoiceId: number;
  paidAt?: string;
  paidMethod?: string;
  paidNote?: string;
  reason?: string;
}): Promise<{ invoice: Invoice }> {
  return apiFetch<{ invoice: Invoice }>(`/api/admin/businesses/${args.tenantId}/invoices/${args.invoiceId}/paid`, {
    method: "POST",
    body: {
      paid_at: args.paidAt,
      paid_method: args.paidMethod,
      paid_note: args.paidNote,
      reason: args.reason,
    },
  });
}

export async function downloadInvoicePdf(args: {
  tenantId: number;
  invoiceId: number;
  invoiceNumber?: string | null;
}): Promise<void> {
  const filename = args.invoiceNumber ? `invoice_${args.invoiceNumber}.pdf` : "invoice.pdf";

  await apiDownload(`/api/admin/businesses/${args.tenantId}/invoices/${args.invoiceId}/pdf`, {
    filename,
  });
}
