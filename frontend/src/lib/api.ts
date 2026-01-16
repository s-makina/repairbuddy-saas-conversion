import { API_BASE_URL } from "@/lib/config";
import { getToken } from "@/lib/token";

export class ApiError extends Error {
  status: number;
  data: unknown;

  constructor(message: string, status: number, data: unknown) {
    super(message);
    this.status = status;
    this.data = data;
  }
}

type ApiFetchOptions = Omit<RequestInit, "body"> & {
  body?: unknown;
  token?: string | null;
  timeoutMs?: number;
};

export async function apiFetch<T>(path: string, options: ApiFetchOptions = {}): Promise<T> {
  const url = `${API_BASE_URL}${path.startsWith("/") ? "" : "/"}${path}`;

  const headers = new Headers(options.headers);
  headers.set("Accept", "application/json");

  const token = options.token ?? getToken();
  if (token) {
    headers.set("Authorization", `Bearer ${token}`);
  }

  let body: BodyInit | undefined;
  if (options.body !== undefined) {
    headers.set("Content-Type", "application/json");
    body = JSON.stringify(options.body);
  }

  const timeoutMs = typeof options.timeoutMs === "number" ? options.timeoutMs : 15000;
  const controller = options.signal ? null : new AbortController();
  const timeoutId = controller ? setTimeout(() => controller.abort(), timeoutMs) : null;

  let res: Response;

  try {
    res = await fetch(url, {
      ...options,
      headers,
      body,
      signal: controller?.signal ?? options.signal,
    });
  } catch (err) {
    if (controller && timeoutId !== null) {
      clearTimeout(timeoutId);
    }

    if (err instanceof DOMException && err.name === "AbortError") {
      throw new ApiError("Request timed out", 408, null);
    }

    throw err;
  } finally {
    if (controller && timeoutId !== null) {
      clearTimeout(timeoutId);
    }
  }

  const contentType = res.headers.get("content-type") || "";
  const isJson = contentType.includes("application/json");

  const data = isJson ? await res.json().catch(() => null) : await res.text().catch(() => "");

  if (!res.ok) {
    const message =
      (data && typeof data === "object" && "message" in (data as Record<string, unknown>)
        ? String((data as Record<string, unknown>).message)
        : `Request failed with status ${res.status}`) || "Request failed";

    throw new ApiError(message, res.status, data);
  }

  return data as T;
}
