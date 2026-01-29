"use client";

import React from "react";
import { useParams } from "next/navigation";
import { PortalShell } from "@/components/shells/PortalShell";
import { Alert } from "@/components/ui/Alert";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { Input } from "@/components/ui/Input";
import { mockApi } from "@/mock/mockApi";
import type { Client, ClientId, Job, JobId } from "@/mock/types";

type PortalSession = {
  jobId: string;
  caseNumber: string;
};

type ClientDraft = {
  name: string;
  email: string;
  phone: string;
};

function portalSessionKey(tenantSlug: string) {
  return `rb.portal.session:v1:${tenantSlug}`;
}

function loadPortalSession(tenantSlug: string): PortalSession | null {
  if (typeof window === "undefined") return null;
  try {
    const raw = window.localStorage.getItem(portalSessionKey(tenantSlug));
    if (!raw) return null;
    const parsed = JSON.parse(raw) as { job_id?: unknown; case_number?: unknown };
    const jobId = typeof parsed.job_id === "string" ? parsed.job_id : "";
    const caseNumber = typeof parsed.case_number === "string" ? parsed.case_number : "";
    if (!jobId) return null;
    return { jobId, caseNumber };
  } catch {
    return null;
  }
}

function profileDraftKey(tenantSlug: string, clientId: string) {
  return `rb.portal.profileDraft:v1:${tenantSlug}:${clientId}`;
}

function safeJsonParse(raw: string | null): unknown {
  if (!raw) return null;
  try {
    return JSON.parse(raw);
  } catch {
    return null;
  }
}

function loadDraft(tenantSlug: string, clientId: string): ClientDraft | null {
  if (typeof window === "undefined") return null;
  const parsed = safeJsonParse(window.localStorage.getItem(profileDraftKey(tenantSlug, clientId)));
  if (!parsed || typeof parsed !== "object") return null;
  const obj = parsed as Partial<ClientDraft>;
  return {
    name: typeof obj.name === "string" ? obj.name : "",
    email: typeof obj.email === "string" ? obj.email : "",
    phone: typeof obj.phone === "string" ? obj.phone : "",
  };
}

function saveDraft(tenantSlug: string, clientId: string, draft: ClientDraft) {
  if (typeof window === "undefined") return;
  try {
    window.localStorage.setItem(profileDraftKey(tenantSlug, clientId), JSON.stringify(draft));
  } catch {
    // ignore
  }
}

export default function PortalProfilePage() {
  const params = useParams() as { tenant?: string };
  const tenantSlug = typeof params.tenant === "string" ? params.tenant : "";

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [session, setSession] = React.useState<PortalSession | null>(null);

  const [job, setJob] = React.useState<Job | null>(null);
  const [client, setClient] = React.useState<Client | null>(null);

  const [draft, setDraft] = React.useState<ClientDraft>({ name: "", email: "", phone: "" });
  const [savedNotice, setSavedNotice] = React.useState<string | null>(null);

  React.useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);

        if (!tenantSlug) {
          setSession(null);
          setJob(null);
          setClient(null);
          return;
        }

        const s = loadPortalSession(tenantSlug);
        setSession(s);

        if (!s) {
          setJob(null);
          setClient(null);
          return;
        }

        const j = await mockApi.getJob(s.jobId as JobId);
        if (!alive) return;
        setJob(j);

        if (!j) {
          setClient(null);
          return;
        }

        const c = await mockApi.getClient(j.client_id as ClientId);
        if (!alive) return;
        setClient(c);

        if (c) {
          const persisted = loadDraft(tenantSlug, c.id);
          setDraft(
            persisted ?? {
              name: c.name,
              email: c.email ?? "",
              phone: c.phone ?? "",
            },
          );
        }
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load profile.");
        setJob(null);
        setClient(null);
      } finally {
        if (!alive) return;
        setLoading(false);
      }
    }

    void load();

    return () => {
      alive = false;
    };
  }, [tenantSlug]);

  return (
    <PortalShell tenantSlug={tenantSlug} title="Profile" subtitle="Your contact details (mock).">
      <div className="space-y-4">
        {loading ? <div className="text-sm text-zinc-500">Loading profile...</div> : null}

        {error ? (
          <Alert variant="danger" title="Could not load profile">
            {error}
          </Alert>
        ) : null}

        {!loading && !error && !session ? (
          <Alert variant="warning" title="Portal locked">
            Enter your case number to view your profile.
          </Alert>
        ) : null}

        {!loading && !error && session && !client ? (
          <Alert variant="warning" title="Client not found">
            We couldn’t load your client profile for this ticket.
          </Alert>
        ) : null}

        {client ? (
          <Card className="shadow-none">
            <CardContent className="pt-5">
              <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Profile</div>
                  <div className="mt-1 text-xs text-zinc-500">Case: {job?.case_number ?? "—"}</div>
                </div>
                <Badge variant="info">{client.id}</Badge>
              </div>

              {savedNotice ? (
                <div className="mt-4">
                  <Alert variant="success" title="Saved">
                    {savedNotice}
                  </Alert>
                </div>
              ) : null}

              <div className="mt-4 grid gap-4 sm:grid-cols-2">
                <div className="sm:col-span-2">
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Name</div>
                  <div className="mt-2">
                    <Input value={draft.name} onChange={(e) => setDraft((d) => ({ ...d, name: e.target.value }))} placeholder="Your name" />
                  </div>
                </div>

                <div>
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Email</div>
                  <div className="mt-2">
                    <Input value={draft.email} onChange={(e) => setDraft((d) => ({ ...d, email: e.target.value }))} placeholder="you@example.com" />
                  </div>
                </div>

                <div>
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Phone</div>
                  <div className="mt-2">
                    <Input value={draft.phone} onChange={(e) => setDraft((d) => ({ ...d, phone: e.target.value }))} placeholder="(555) 555-5555" />
                  </div>
                </div>
              </div>

              <div className="mt-4 flex items-center justify-end gap-2">
                <Button
                  variant="outline"
                  onClick={() => {
                    setSavedNotice(null);
                    setDraft({
                      name: client.name,
                      email: client.email ?? "",
                      phone: client.phone ?? "",
                    });
                  }}
                >
                  Reset
                </Button>
                <Button
                  onClick={() => {
                    saveDraft(tenantSlug, client.id, draft);
                    setSavedNotice("Saved to your browser for demo purposes.");
                    setTimeout(() => setSavedNotice(null), 2500);
                  }}
                >
                  Save
                </Button>
              </div>
            </CardContent>
          </Card>
        ) : null}
      </div>
    </PortalShell>
  );
}
