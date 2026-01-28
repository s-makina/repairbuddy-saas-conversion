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
import { mockApi } from "@/mock/mockApi";
import type { Appointment } from "@/mock/types";

export default function PublicBookingPage() {
  const params = useParams() as { tenant?: string };
  const tenantSlug = typeof params.tenant === "string" ? params.tenant : "";

  const [scheduledAt, setScheduledAt] = React.useState<string>("");
  const [clientName, setClientName] = React.useState<string>("");
  const [clientEmail, setClientEmail] = React.useState<string>("");
  const [clientPhone, setClientPhone] = React.useState<string>("");
  const [notes, setNotes] = React.useState<string>("");

  const [busy, setBusy] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);
  const [success, setSuccess] = React.useState<Appointment | null>(null);

  const [recent, setRecent] = React.useState<Appointment[]>([]);

  React.useEffect(() => {
    let alive = true;

    async function loadRecent() {
      try {
        const appts = await mockApi.listAppointments();
        if (!alive) return;
        setRecent(Array.isArray(appts) ? appts.slice(0, 5) : []);
      } catch {
        if (!alive) return;
        setRecent([]);
      }
    }

    void loadRecent();

    return () => {
      alive = false;
    };
  }, [success?.id]);

  async function submit() {
    setError(null);
    setSuccess(null);

    if (!tenantSlug) {
      setError("Tenant slug is required in the URL.");
      return;
    }

    setBusy(true);
    try {
      const created = await mockApi.createAppointment({
        scheduled_at: scheduledAt,
        client_name: clientName,
        client_email: clientEmail.trim().length > 0 ? clientEmail.trim() : null,
        client_phone: clientPhone.trim().length > 0 ? clientPhone.trim() : null,
        notes: notes.trim().length > 0 ? notes.trim() : null,
      });

      setSuccess(created);
      setScheduledAt("");
      setClientName("");
      setClientEmail("");
      setClientPhone("");
      setNotes("");
    } catch (e) {
      setError(e instanceof Error ? e.message : "Could not request an appointment.");
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
                <div className="text-xl font-semibold text-[var(--rb-text)]">Book an appointment</div>
                <div className="mt-1 text-sm text-zinc-600">Request a time and we’ll confirm your appointment.</div>
              </div>

              {!tenantSlug ? (
                <Alert variant="warning" title="Missing tenant">
                  Tenant slug is required in the URL.
                </Alert>
              ) : null}

              {error ? (
                <Alert variant="danger" title="Booking failed">
                  {error}
                </Alert>
              ) : null}

              {success ? (
                <Alert variant="success" title="Appointment requested">
                  We received your request (ID: {success.id}) for {new Date(success.scheduled_at).toLocaleString()}.
                </Alert>
              ) : null}

              <div className="grid gap-4 sm:grid-cols-2">
                <div className="sm:col-span-2">
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Preferred time</div>
                  <div className="mt-2">
                    <Input type="datetime-local" value={scheduledAt} onChange={(e) => setScheduledAt(e.target.value)} disabled={busy} />
                  </div>
                </div>

                <div className="sm:col-span-2">
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Name</div>
                  <div className="mt-2">
                    <Input value={clientName} onChange={(e) => setClientName(e.target.value)} disabled={busy} placeholder="Your name" />
                  </div>
                </div>

                <div>
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Email (optional)</div>
                  <div className="mt-2">
                    <Input value={clientEmail} onChange={(e) => setClientEmail(e.target.value)} disabled={busy} placeholder="you@example.com" />
                  </div>
                </div>

                <div>
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Phone (optional)</div>
                  <div className="mt-2">
                    <Input value={clientPhone} onChange={(e) => setClientPhone(e.target.value)} disabled={busy} placeholder="(555) 555-5555" />
                  </div>
                </div>

                <div className="sm:col-span-2">
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Notes (optional)</div>
                  <div className="mt-2">
                    <textarea
                      value={notes}
                      onChange={(e) => setNotes(e.target.value)}
                      placeholder="Describe the issue, device model, any constraints..."
                      className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm text-[var(--rb-text)] shadow-sm outline-none transition placeholder:text-zinc-400 focus-visible:ring-2 focus-visible:ring-[color:color-mix(in_srgb,var(--rb-orange),white_65%)] focus-visible:ring-offset-2 focus-visible:ring-offset-white disabled:cursor-not-allowed disabled:bg-[var(--rb-surface-muted)] disabled:opacity-70"
                      rows={5}
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
                  {busy ? "Submitting..." : "Request appointment"}
                </Button>
              </div>

              <div className="border-t border-[var(--rb-border)] pt-5">
                <div className="text-sm font-semibold text-[var(--rb-text)]">Recent requests (mock)</div>
                <div className="mt-2 text-sm text-zinc-600">These are stored in your browser for demo purposes.</div>

                <div className="mt-4 space-y-2">
                  {recent.length === 0 ? <div className="text-sm text-zinc-600">No appointment requests yet.</div> : null}
                  {recent.map((a) => (
                    <div key={a.id} className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white p-3">
                      <div className="flex flex-wrap items-start justify-between gap-3">
                        <div className="min-w-0">
                          <div className="truncate text-sm font-semibold text-[var(--rb-text)]">{a.client_name}</div>
                          <div className="mt-1 text-xs text-zinc-500">{new Date(a.scheduled_at).toLocaleString()}</div>
                        </div>
                        <Badge variant={a.status === "requested" ? "warning" : "default"}>{a.status}</Badge>
                      </div>
                      {a.notes ? <div className="mt-2 text-sm text-zinc-700">{a.notes}</div> : null}
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
