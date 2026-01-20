import { apiDownload, apiFetch } from "@/lib/api";
import type {
  BillingPlan,
  EntitlementDefinition,
  Invoice,
  Tenant,
  TenantSubscription,
} from "@/lib/types";

export type BillingCatalogPayload = {
  billing_plans: BillingPlan[];
  entitlement_definitions: EntitlementDefinition[];
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

export type TenantSubscriptionsPayload = {
  tenant: Tenant;
  subscriptions: TenantSubscription[];
};

export async function getTenantSubscriptions(tenantId: number): Promise<TenantSubscriptionsPayload> {
  return apiFetch<TenantSubscriptionsPayload>(`/api/admin/tenants/${tenantId}/subscriptions`);
}

export async function assignTenantSubscription(args: {
  tenantId: number;
  billingPlanVersionId: number;
  billingPriceId: number;
  reason?: string;
}): Promise<{ subscription: TenantSubscription }> {
  return apiFetch<{ subscription: TenantSubscription }>(`/api/admin/tenants/${args.tenantId}/subscriptions`, {
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
    `/api/admin/tenants/${args.tenantId}/subscriptions/${args.subscriptionId}/cancel`,
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

  return apiFetch<TenantInvoicesPayload>(`/api/admin/tenants/${args.tenantId}/invoices${qs.toString() ? `?${qs.toString()}` : ""}`);
}

export async function getTenantInvoice(args: {
  tenantId: number;
  invoiceId: number;
}): Promise<{ tenant: Tenant; invoice: Invoice }> {
  return apiFetch<{ tenant: Tenant; invoice: Invoice }>(`/api/admin/tenants/${args.tenantId}/invoices/${args.invoiceId}`);
}

export async function createInvoiceFromSubscription(args: {
  tenantId: number;
  tenantSubscriptionId: number;
  reason?: string;
}): Promise<{ invoice: Invoice }> {
  return apiFetch<{ invoice: Invoice }>(`/api/admin/tenants/${args.tenantId}/invoices`, {
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
  return apiFetch<{ invoice: Invoice }>(`/api/admin/tenants/${args.tenantId}/invoices/${args.invoiceId}/issue`, {
    method: "POST",
    body: {
      reason: args.reason,
    },
  });
}

export async function markInvoicePaid(args: {
  tenantId: number;
  invoiceId: number;
  reason?: string;
}): Promise<{ invoice: Invoice }> {
  return apiFetch<{ invoice: Invoice }>(`/api/admin/tenants/${args.tenantId}/invoices/${args.invoiceId}/paid`, {
    method: "POST",
    body: {
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

  await apiDownload(`/api/admin/tenants/${args.tenantId}/invoices/${args.invoiceId}/pdf`, {
    filename,
  });
}
