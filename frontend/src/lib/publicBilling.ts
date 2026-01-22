import { apiFetch } from "@/lib/api";
import type { BillingPlan } from "@/lib/types";

export type PublicBillingPlansPayload = {
  billing_plans: BillingPlan[];
};

export async function getPublicBillingPlans(): Promise<PublicBillingPlansPayload> {
  return apiFetch<PublicBillingPlansPayload>("/api/public/billing/plans", {
    token: null,
    impersonationSessionId: null,
  });
}
