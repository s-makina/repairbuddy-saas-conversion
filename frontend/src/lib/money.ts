export function formatMoney(args: {
  amountCents: number | null | undefined;
  currency: string | null | undefined;
  fallback?: string;
}): string {
  const amountCents = args.amountCents;
  const currency = args.currency?.toUpperCase() ?? null;

  if (typeof amountCents !== "number" || !Number.isFinite(amountCents)) {
    return args.fallback ?? "—";
  }

  const amount = amountCents / 100;

  if (!currency || currency.length !== 3) {
    return `${amount.toFixed(2)}`;
  }

  try {
    return new Intl.NumberFormat(undefined, {
      style: "currency",
      currency,
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(amount);
  } catch {
    return `${currency} ${amount.toFixed(2)}`;
  }
}
