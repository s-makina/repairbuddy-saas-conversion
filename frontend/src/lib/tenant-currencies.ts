import { apiFetch } from "@/lib/api";

export type TenantCurrency = {
  code: string;
  symbol: string | null;
  name: string;
  is_active: boolean;
  is_default: boolean;
};

export type TenantCurrenciesPayload = {
  currencies: TenantCurrency[];
  active_currency: string | null;
};

export async function getTenantCurrencies(business: string): Promise<TenantCurrenciesPayload> {
  return apiFetch<TenantCurrenciesPayload>(`/api/${business}/app/currencies`);
}

export async function updateTenantCurrencies(
  business: string,
  currencies: Array<{ code: string; symbol: string | null; name: string; is_active: boolean; is_default: boolean }>,
): Promise<TenantCurrenciesPayload> {
  return apiFetch<TenantCurrenciesPayload>(`/api/${business}/app/currencies`, {
    method: "PATCH",
    body: {
      currencies,
    },
  });
}
