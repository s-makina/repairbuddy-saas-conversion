export const API_BASE_URL =
  process.env.NEXT_PUBLIC_API_BASE_URL?.replace(/\/+$/, "") || "http://localhost:8000";

export const APP_DOMAIN = (process.env.NEXT_PUBLIC_APP_DOMAIN ?? "")
  .trim()
  .replace(/^https?:\/\//i, "")
  .split("/")[0]
  .replace(/^\.+|\.+$/g, "")
  .toLowerCase();

function inferAppProtocol(domain: string): "http" | "https" {
  if (domain.includes("localhost") || domain.startsWith("127.0.0.1")) {
    return "http";
  }

  return "https";
}

export function getTenantLoginUrl(tenantSlug: string): string | null {
  const slug = tenantSlug.trim().toLowerCase();
  if (!slug || !APP_DOMAIN) return null;

  const protocol = inferAppProtocol(APP_DOMAIN);
  return `${protocol}://${slug}.${APP_DOMAIN}/login`;
}

// export const API_BASE_URL =
//   process.env.NEXT_PUBLIC_API_BASE_URL?.replace(/\/+$/, "") || "https://api.99smartx.com";