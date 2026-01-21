import { apiFetch } from "@/lib/api";
import type { Tenant } from "@/lib/types";

export type SetupPayload = {
  tenant: Tenant;
  setup: {
    completed_at?: string | null;
    step?: string | null;
    state?: Record<string, unknown> | null;
  };
};

export type SetupUpdateInput = {
  name?: string;
  contact_email?: string | null;
  contact_phone?: string | null;

  billing_country?: string | null;
  billing_vat_number?: string | null;
  billing_address_json?: Record<string, unknown> | null;
  currency?: string | null;

  timezone?: string | null;
  language?: string | null;

  brand_color?: string | null;
  logo?: File | null;

  setup_step?: string | null;
  setup_state?: Record<string, unknown> | null;
};

export async function getSetup(business: string): Promise<SetupPayload> {
  return apiFetch<SetupPayload>(`/api/${business}/app/setup`);
}

export async function updateSetup(business: string, input: SetupUpdateInput): Promise<{ tenant: Tenant }> {
  const logo = Object.prototype.hasOwnProperty.call(input, "logo") ? input.logo : undefined;
  const shouldUseFormData = logo instanceof File;

  if (shouldUseFormData) {
    const fd = new FormData();

    for (const [key, value] of Object.entries(input)) {
      if (key === "logo") {
        fd.append("logo", value as File);
        continue;
      }

      if (value === undefined) continue;
      if (value === null) {
        fd.append(key, "");
        continue;
      }

      if (typeof value === "object") {
        fd.append(key, JSON.stringify(value));
      } else {
        fd.append(key, String(value));
      }
    }

    return apiFetch<{ tenant: Tenant }>(`/api/${business}/app/setup`, {
      method: "PATCH",
      body: fd,
    });
  }

  return apiFetch<{ tenant: Tenant }>(`/api/${business}/app/setup`, {
    method: "PATCH",
    body: input,
  });
}

export async function completeSetup(business: string, input: {
  name: string;
  billing_country: string;
  timezone: string;
  language: string;
}): Promise<{ tenant: Tenant }> {
  return apiFetch<{ tenant: Tenant }>(`/api/${business}/app/setup/complete`, {
    method: "POST",
    body: input,
  });
}
