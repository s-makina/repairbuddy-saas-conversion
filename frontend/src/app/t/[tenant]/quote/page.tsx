"use client";

import React from "react";
import Link from "next/link";
import { useParams } from "next/navigation";
import { PublicPageShell } from "@/components/PublicPageShell";
import { Card, CardContent } from "@/components/ui/Card";
import { Alert } from "@/components/ui/Alert";
import { Button } from "@/components/ui/Button";
import { Input } from "@/components/ui/Input";
import { Badge } from "@/components/ui/Badge";

type QuoteRequest = {
  id: string;
  created_at: string;
  name: string;
  email?: string | null;
  phone?: string | null;
  device?: string | null;
  issue: string;
  preferred_contact?: "email" | "phone" | "either";
};

function storageKey(tenantSlug: string) {
  return `rb.public.quoteRequests:v1:${tenantSlug}`;
}

function counterKey(tenantSlug: string) {
  return `rb.public.quoteRequests.counter:v1:${tenantSlug}`;
}

function safeJsonParse(raw: string | null): unknown {
  if (!raw) return null;
  try {
    return JSON.parse(raw);
  } catch {
    return null;
  }
}

function loadRequests(tenantSlug: string): QuoteRequest[] {
  if (typeof window === "undefined") return [];
  const parsed = safeJsonParse(window.localStorage.getItem(storageKey(tenantSlug)));
  if (!Array.isArray(parsed)) return [];
  return parsed as QuoteRequest[];
}

function saveRequests(tenantSlug: string, next: QuoteRequest[]) {
  if (typeof window === "undefined") return;
  try {
    window.localStorage.setItem(storageKey(tenantSlug), JSON.stringify(next));
  } catch {
    // ignore
  }
}

function nextId(tenantSlug: string) {
  if (typeof window === "undefined") return "quote_000";
  const parsed = safeJsonParse(window.localStorage.getItem(counterKey(tenantSlug)));
  const current = typeof parsed === "number" && Number.isFinite(parsed) ? parsed : 0;
  const next = current + 1;
  try {
    window.localStorage.setItem(counterKey(tenantSlug), JSON.stringify(next));
  } catch {
    // ignore
  }
  return `quote_${String(next).padStart(3, "0")}`;
}

export default function PublicQuotePage() {
  const params = useParams() as { tenant?: string };
  const tenantSlug = typeof params.tenant === "string" ? params.tenant : "";

  const [name, setName] = React.useState("");
  const [email, setEmail] = React.useState("");
  const [phone, setPhone] = React.useState("");
  const [device, setDevice] = React.useState("");
  const [issue, setIssue] = React.useState("");
  const [preferred, setPreferred] = React.useState<QuoteRequest["preferred_contact"]>("either");

  const [busy, setBusy] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);
  const [success, setSuccess] = React.useState<QuoteRequest | null>(null);
  const [recent, setRecent] = React.useState<QuoteRequest[]>([]);

  React.useEffect(() => {
    if (!tenantSlug) {
      setRecent([]);
      return;
    }
    setRecent(loadRequests(tenantSlug).slice(0, 5));
  }, [tenantSlug, success?.id]);

  async function submit() {
    setError(null);
    setSuccess(null);

    if (!tenantSlug) {
      setError("Tenant slug is required in the URL.");
      return;
    }

    const n = name.trim();
    const i = issue.trim();

    if (!n) {
      setError("Name is required.");
      return;
    }
    if (!i) {
      setError("Issue description is required.");
      return;
    }

    setBusy(true);
    try {
      const req: QuoteRequest = {
        id: nextId(tenantSlug),
        created_at: new Date().toISOString(),
        name: n,
        email: email.trim().length > 0 ? email.trim() : null,
        phone: phone.trim().length > 0 ? phone.trim() : null,
        device: device.trim().length > 0 ? device.trim() : null,
        issue: i,
        preferred_contact: preferred,
      };

      const existing = loadRequests(tenantSlug);
      saveRequests(tenantSlug, [req, ...existing]);

      setSuccess(req);
      setName("");
      setEmail("");
      setPhone("");
      setDevice("");
      setIssue("");
      setPreferred("either");
    } catch (e) {
      setError(e instanceof Error ? e.message : "Could not submit quote request.");
    } finally {
      setBusy(false);
    }
  }

  return (
    <PublicPageShell
      badge={
        <span className="inline-flex items-center gap-2">
          <span>RepairBuddy</span>
          {tenantSlug ? <Badge variant="info">{tenantSlug}</Badge> : null}
        </span>
      }
      actions={
        tenantSlug ? (
          <div className="flex items-center gap-3">
            <Link href={`/t/${tenantSlug}/status`} className="text-sm font-semibold text-[var(--rb-blue)] hover:underline">
              Status check
            </Link>
            <Link href={`/t/${tenantSlug}/book`} className="text-sm font-semibold text-[var(--rb-blue)] hover:underline">
              Book
            </Link>
            <Link href={`/t/${tenantSlug}/portal`} className="text-sm font-semibold text-[var(--rb-blue)] hover:underline">
              Portal
            </Link>
          </div>
        ) : null
      }
      centerContent
    >
      <div className="mx-auto w-full max-w-3xl px-4">
        <Card className="shadow-none">
          <CardContent className="pt-6">
            <div className="space-y-5">
              <div>
                <div className="text-xl font-semibold text-[var(--rb-text)]">Request a quote</div>
                <div className="mt-1 text-sm text-zinc-600">Tell us what’s wrong and we’ll reply with an estimate.</div>
              </div>

              {!tenantSlug ? (
                <Alert variant="warning" title="Missing tenant">
                  Tenant slug is required in the URL.
                </Alert>
              ) : null}

              {error ? (
                <Alert variant="danger" title="Could not submit quote request">
                  {error}
                </Alert>
              ) : null}

              {success ? (
                <Alert variant="success" title="Quote request sent">
                  We received your request (ID: {success.id}).
                </Alert>
              ) : null}

              <div className="grid gap-4 sm:grid-cols-2">
                <div className="sm:col-span-2">
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Name</div>
                  <div className="mt-2">
                    <Input value={name} onChange={(e) => setName(e.target.value)} disabled={busy} placeholder="Your name" />
                  </div>
                </div>

                <div>
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Email (optional)</div>
                  <div className="mt-2">
                    <Input value={email} onChange={(e) => setEmail(e.target.value)} disabled={busy} placeholder="you@example.com" />
                  </div>
                </div>

                <div>
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Phone (optional)</div>
                  <div className="mt-2">
                    <Input value={phone} onChange={(e) => setPhone(e.target.value)} disabled={busy} placeholder="(555) 555-5555" />
                  </div>
                </div>

                <div className="sm:col-span-2">
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Device (optional)</div>
                  <div className="mt-2">
                    <Input value={device} onChange={(e) => setDevice(e.target.value)} disabled={busy} placeholder="e.g. Dell XPS 13, iPhone 13, Custom PC" />
                  </div>
                </div>

                <div className="sm:col-span-2">
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Preferred contact</div>
                  <div className="mt-2">
                    <select
                      value={preferred}
                      onChange={(e) => setPreferred((e.target.value as QuoteRequest["preferred_contact"]) ?? "either")}
                      disabled={busy}
                      className="h-10 w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 text-sm"
                    >
                      <option value="either">Either</option>
                      <option value="email">Email</option>
                      <option value="phone">Phone</option>
                    </select>
                  </div>
                </div>

                <div className="sm:col-span-2">
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Describe the issue</div>
                  <div className="mt-2">
                    <textarea
                      value={issue}
                      onChange={(e) => setIssue(e.target.value)}
                      placeholder="Symptoms, error messages, what changed recently, etc."
                      className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm text-[var(--rb-text)] shadow-sm outline-none transition placeholder:text-zinc-400 focus-visible:ring-2 focus-visible:ring-[color:color-mix(in_srgb,var(--rb-orange),white_65%)] focus-visible:ring-offset-2 focus-visible:ring-offset-white disabled:cursor-not-allowed disabled:bg-[var(--rb-surface-muted)] disabled:opacity-70"
                      rows={6}
                      disabled={busy}
                    />
                  </div>
                </div>
              </div>

              <div className="flex items-center justify-end gap-2">
                <Button
                  variant="outline"
                  disabled={busy}
                  onClick={() => {
                    setError(null);
                    setSuccess(null);
                  }}
                >
                  Clear
                </Button>
                <Button disabled={busy || !tenantSlug} onClick={() => void submit()}>
                  {busy ? "Submitting..." : "Send request"}
                </Button>
              </div>

              <div className="border-t border-[var(--rb-border)] pt-5">
                <div className="text-sm font-semibold text-[var(--rb-text)]">Recent quote requests (mock)</div>
                <div className="mt-2 text-sm text-zinc-600">These are stored in your browser for demo purposes.</div>

                <div className="mt-4 space-y-2">
                  {recent.length === 0 ? <div className="text-sm text-zinc-600">No quote requests yet.</div> : null}
                  {recent.map((r) => (
                    <div key={r.id} className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-3">
                      <div className="flex flex-wrap items-start justify-between gap-3">
                        <div className="min-w-0">
                          <div className="truncate text-sm font-semibold text-[var(--rb-text)]">{r.name}</div>
                          <div className="mt-1 text-xs text-zinc-500">{new Date(r.created_at).toLocaleString()}</div>
                        </div>
                        <Badge variant="info">{r.id}</Badge>
                      </div>
                      <div className="mt-2 text-sm text-zinc-700 whitespace-pre-wrap">{r.issue}</div>
                      {r.device ? <div className="mt-2 text-xs text-zinc-500">Device: {r.device}</div> : null}
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </PublicPageShell>
  );
}
