import { apiFetch } from "@/lib/api";
import type { Tenant } from "@/lib/types";

export type SubscriptionGateStatus = "none" | "pending_checkout" | "trialing" | "active" | "past_due" | "suspended";

export type GateSnapshot = {
  tenant_status: string;
  subscription_status: SubscriptionGateStatus;
  setup_completed_at: string | null;
  setup_step: string | null;
};

export type GatePayload = {
  tenant: Tenant;
  gate: GateSnapshot;
};

const inFlight: Record<string, Promise<GatePayload> | undefined> = {};

export async function getGate(business: string): Promise<GatePayload> {
  const key = business;
  const existing = inFlight[key];
  if (existing) return existing;

  const p = apiFetch<GatePayload>(`/api/${business}/app/gate`).finally(() => {
    delete inFlight[key];
  });
  inFlight[key] = p;
  return p;
}

export function computeGateRedirect(business: string, gate: GateSnapshot): string {
  const setupCompleted = Boolean(gate.setup_completed_at);

  if (gate.tenant_status === "suspended" || gate.subscription_status === "suspended") {
    return `/${business}/suspended`;
  }

  if (gate.subscription_status === "none") {
    return `/${business}/plans`;
  }

  if (gate.subscription_status === "pending_checkout") {
    return `/${business}/checkout`;
  }

  if (["trialing", "active", "past_due"].includes(gate.subscription_status) && !setupCompleted) {
    return `/${business}/setup`;
  }

  return `/app/${business}`;
}
