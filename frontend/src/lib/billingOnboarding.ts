import { apiFetch } from "@/lib/api";
import type { GateSnapshot } from "@/lib/gate";
import type { BillingPlan, Tenant, TenantSubscription } from "@/lib/types";

export type TenantBillingPlansPayload = {
  billing_plans: BillingPlan[];
};

export async function getTenantBillingPlans(business: string): Promise<TenantBillingPlansPayload> {
  return apiFetch<TenantBillingPlansPayload>(`/api/${business}/app/billing/plans`);
}

export type SubscribeInput = {
  billing_price_id: number;
  billing_country: string;
  currency: string;
  billing_vat_number?: string | null;
};

export type SubscribePayload = {
  tenant: Tenant;
  subscription: TenantSubscription;
  gate: GateSnapshot;
};

export async function subscribeToPlan(business: string, input: SubscribeInput): Promise<SubscribePayload> {
  return apiFetch<SubscribePayload>(`/api/${business}/app/billing/subscribe`, {
    method: "POST",
    body: {
      billing_price_id: input.billing_price_id,
      billing_country: input.billing_country,
      currency: input.currency,
      billing_vat_number: input.billing_vat_number ?? undefined,
    },
  });
}

export type CheckoutSnapshotPayload = {
  tenant: Tenant;
  subscription: TenantSubscription | null;
};

export async function getCheckoutSnapshot(business: string): Promise<CheckoutSnapshotPayload> {
  return apiFetch<CheckoutSnapshotPayload>(`/api/${business}/app/billing/checkout`);
}

export type ConfirmCheckoutPayload = {
  tenant: Tenant;
  subscription: TenantSubscription;
  gate: GateSnapshot;
};

export async function confirmCheckout(business: string): Promise<ConfirmCheckoutPayload> {
  return apiFetch<ConfirmCheckoutPayload>(`/api/${business}/app/billing/checkout/confirm`, {
    method: "POST",
  });
}
