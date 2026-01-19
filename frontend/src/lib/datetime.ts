export type DateFormatPreference = {
  locale?: string;
  dateStyle?: Intl.DateTimeFormatOptions["dateStyle"];
  timeStyle?: Intl.DateTimeFormatOptions["timeStyle"];
  hour12?: boolean;
};

const DEFAULT_DATE_PREFERENCE: Required<DateFormatPreference> = {
  locale: "en-GB",
  dateStyle: "medium",
  timeStyle: "short",
  hour12: false,
};

function parseDate(value?: string | Date | null): Date | null {
  if (!value) return null;
  if (value instanceof Date) return Number.isFinite(value.getTime()) ? value : null;
  const d = new Date(value);
  return Number.isFinite(d.getTime()) ? d : null;
}

export function formatDate(value?: string | Date | null, pref?: DateFormatPreference): string {
  const d = parseDate(value);
  if (!d) return "—";

  const p = { ...DEFAULT_DATE_PREFERENCE, ...(pref ?? {}) };
  return new Intl.DateTimeFormat(p.locale, { dateStyle: p.dateStyle }).format(d);
}

export function formatDateTime(value?: string | Date | null, pref?: DateFormatPreference): string {
  const d = parseDate(value);
  if (!d) return "—";

  const p = { ...DEFAULT_DATE_PREFERENCE, ...(pref ?? {}) };
  return new Intl.DateTimeFormat(p.locale, { dateStyle: p.dateStyle, timeStyle: p.timeStyle, hour12: p.hour12 }).format(d);
}
