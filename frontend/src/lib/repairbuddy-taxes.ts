import { apiFetch } from "@/lib/api";

export type ApiRepairBuddyTax = {
  id: number;
  name: string;
  rate: string | number;
  is_default: boolean;
  is_active: boolean;
};

export async function getRepairBuddyTaxes(business: string): Promise<{ taxes: ApiRepairBuddyTax[] }> {
  return apiFetch<{ taxes: ApiRepairBuddyTax[] }>(`/api/${business}/app/repairbuddy/taxes`);
}

export async function createRepairBuddyTax(
  business: string,
  input: { name: string; rate: number; is_default?: boolean; is_active?: boolean },
): Promise<{ tax: ApiRepairBuddyTax }> {
  return apiFetch<{ tax: ApiRepairBuddyTax }>(`/api/${business}/app/repairbuddy/taxes`, {
    method: "POST",
    body: input,
  });
}

export async function updateRepairBuddyTax(
  business: string,
  id: number,
  input: { name?: string; rate?: number },
): Promise<{ tax: ApiRepairBuddyTax }> {
  return apiFetch<{ tax: ApiRepairBuddyTax }>(`/api/${business}/app/repairbuddy/taxes/${id}`, {
    method: "PATCH",
    body: input,
  });
}

export async function deleteRepairBuddyTax(business: string, id: number): Promise<{ deleted: boolean }> {
  return apiFetch<{ deleted: boolean }>(`/api/${business}/app/repairbuddy/taxes/${id}`, {
    method: "DELETE",
  });
}

export async function setRepairBuddyTaxDefault(business: string, id: number): Promise<{ tax: ApiRepairBuddyTax }> {
  return apiFetch<{ tax: ApiRepairBuddyTax }>(`/api/${business}/app/repairbuddy/taxes/${id}/default`, {
    method: "PATCH",
  });
}

export async function setRepairBuddyTaxActive(business: string, id: number, is_active: boolean): Promise<{ tax: ApiRepairBuddyTax }> {
  return apiFetch<{ tax: ApiRepairBuddyTax }>(`/api/${business}/app/repairbuddy/taxes/${id}/active`, {
    method: "PATCH",
    body: { is_active },
  });
}
