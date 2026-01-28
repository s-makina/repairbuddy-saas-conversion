"use client";

import React from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { cn } from "@/lib/cn";
import { Button } from "@/components/ui/Button";
import { Input } from "@/components/ui/Input";
import { Card, CardContent } from "@/components/ui/Card";
import { Alert } from "@/components/ui/Alert";
import { mockApi } from "@/mock/mockApi";

type PortalNavItem = {
  label: string;
  href: (tenantSlug: string) => string;
};

const portalNav: PortalNavItem[] = [
  { label: "Dashboard", href: (t) => `/t/${t}/portal` },
  { label: "Tickets/Jobs", href: (t) => `/t/${t}/portal/tickets` },
  { label: "Estimates", href: (t) => `/t/${t}/portal/estimates` },
  { label: "Reviews", href: (t) => `/t/${t}/portal/reviews` },
  { label: "My Devices", href: (t) => `/t/${t}/portal/devices` },
  { label: "Booking", href: (t) => `/t/${t}/portal/booking` },
  { label: "Profile", href: (t) => `/t/${t}/portal/profile` },
];

type PortalSession = {
  tenant: string;
  case_number: string;
  job_id: string;
  unlocked_at: string;
};

function portalSessionKey(tenantSlug: string) {
  return `rb.portal.session:v1:${tenantSlug}`;
}

function loadPortalSession(tenantSlug: string): PortalSession | null {
  if (typeof window === "undefined") return null;
  try {
    const raw = window.localStorage.getItem(portalSessionKey(tenantSlug));
    if (!raw) return null;
    const parsed = JSON.parse(raw) as Partial<PortalSession>;
    if (!parsed || typeof parsed !== "object") return null;
    if (typeof parsed.case_number !== "string" || parsed.case_number.trim().length === 0) return null;
    if (typeof parsed.job_id !== "string" || parsed.job_id.trim().length === 0) return null;
    return {
      tenant: tenantSlug,
      case_number: parsed.case_number,
      job_id: parsed.job_id,
      unlocked_at: typeof parsed.unlocked_at === "string" ? parsed.unlocked_at : new Date().toISOString(),
    };
  } catch {
    return null;
  }
}

function savePortalSession(tenantSlug: string, session: PortalSession) {
  if (typeof window === "undefined") return;
  try {
    window.localStorage.setItem(portalSessionKey(tenantSlug), JSON.stringify(session));
  } catch {
    // ignore
  }
}

function clearPortalSession(tenantSlug: string) {
  if (typeof window === "undefined") return;
  try {
    window.localStorage.removeItem(portalSessionKey(tenantSlug));
  } catch {
    // ignore
  }
}

export function PortalShell({ tenantSlug, title, subtitle, actions, children }: { tenantSlug: string; title?: string; subtitle?: string; actions?: React.ReactNode; children: React.ReactNode }) {
  const pathname = usePathname();

  const [checkingSession, setCheckingSession] = React.useState(true);
  const [session, setSession] = React.useState<PortalSession | null>(null);
  const [caseNumberInput, setCaseNumberInput] = React.useState<string>("");
  const [unlockBusy, setUnlockBusy] = React.useState(false);
  const [unlockError, setUnlockError] = React.useState<string | null>(null);

  React.useEffect(() => {
    let alive = true;

    async function validateExistingSession() {
      try {
        setCheckingSession(true);
        setUnlockError(null);

        if (!tenantSlug || tenantSlug.trim().length === 0) {
          setSession(null);
          return;
        }

        const existing = loadPortalSession(tenantSlug);
        if (!existing) {
          setSession(null);
          return;
        }

        const job = await mockApi.getJobByCaseNumber(existing.case_number);
        if (!alive) return;
        if (!job) {
          clearPortalSession(tenantSlug);
          setSession(null);
          return;
        }

        setSession({
          tenant: tenantSlug,
          case_number: job.case_number,
          job_id: job.id,
          unlocked_at: existing.unlocked_at,
        });
      } catch {
        if (!alive) return;
        setSession(null);
      } finally {
        if (!alive) return;
        setCheckingSession(false);
      }
    }

    void validateExistingSession();

    return () => {
      alive = false;
    };
  }, [tenantSlug]);

  const isUnlocked = !!session && !!session.job_id;

  async function unlock() {
    if (!tenantSlug || tenantSlug.trim().length === 0) return;
    const normalized = caseNumberInput.trim();
    if (!normalized) {
      setUnlockError("Case number is required.");
      return;
    }

    setUnlockBusy(true);
    setUnlockError(null);
    try {
      const job = await mockApi.getJobByCaseNumber(normalized);
      if (!job) {
        setUnlockError("Case number not found.");
        return;
      }
      const next: PortalSession = {
        tenant: tenantSlug,
        case_number: job.case_number,
        job_id: job.id,
        unlocked_at: new Date().toISOString(),
      };
      savePortalSession(tenantSlug, next);
      setSession(next);
      setCaseNumberInput("");
    } catch (e) {
      setUnlockError(e instanceof Error ? e.message : "Could not unlock portal.");
    } finally {
      setUnlockBusy(false);
      setCheckingSession(false);
    }
  }

  const activeHref = React.useMemo(() => {
    const all = portalNav.map((x) => x.href(tenantSlug));
    const matches = all
      .map((href) => {
        const isMatch = pathname === href || pathname.startsWith(`${href}/`);
        return isMatch ? { href, score: href.length } : { href, score: -1 };
      })
      .filter((m) => m.score >= 0)
      .sort((a, b) => b.score - a.score);
    return matches[0]?.href ?? null;
  }, [pathname, tenantSlug]);

  return (
    <div className="min-h-screen bg-[var(--rb-surface-muted)] text-[var(--rb-text)]">
      <header className="border-b border-[var(--rb-border)] bg-white">
        <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-4">
          <div className="min-w-0">
            <div className="flex items-center gap-3">
              <Link href={`/t/${tenantSlug}/status`} className="text-sm font-semibold text-[var(--rb-text)]">
                RepairBuddy
              </Link>
              <div className="text-xs text-zinc-500">Business: {tenantSlug}</div>
              {isUnlocked ? <div className="text-xs text-zinc-500">Case: {session.case_number}</div> : null}
            </div>
            {title ? <div className="mt-1 truncate text-lg font-semibold text-[var(--rb-text)]">{title}</div> : null}
            {subtitle ? <div className="mt-1 text-sm text-zinc-600">{subtitle}</div> : null}
          </div>
          <div className="flex items-center gap-2">
            {actions ? actions : null}
            {isUnlocked ? (
              <Button
                variant="outline"
                size="sm"
                onClick={() => {
                  clearPortalSession(tenantSlug);
                  setSession(null);
                }}
              >
                Switch case
              </Button>
            ) : null}
          </div>
        </div>
      </header>

      <div className="mx-auto w-full max-w-6xl px-4 py-6">
        <div className="grid grid-cols-1 gap-6 lg:grid-cols-[260px_1fr]">
          <aside className="rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)] bg-white shadow-[var(--rb-shadow)]">
            <div className="border-b border-[var(--rb-border)] px-5 py-4">
              <div className="text-sm font-semibold text-[var(--rb-text)]">My Account</div>
            </div>
            <nav className="space-y-1 p-2">
              {isUnlocked
                ? portalNav.map((item) => {
                    const href = item.href(tenantSlug);
                    const isActive = !!activeHref && href === activeHref;
                    return (
                      <Link
                        key={href}
                        href={href}
                        className={cn(
                          "block rounded-[var(--rb-radius-sm)] px-3 py-2 text-sm transition-colors",
                          isActive
                            ? "bg-[var(--rb-surface-muted)] font-medium text-[var(--rb-text)]"
                            : "text-zinc-700 hover:bg-[var(--rb-surface-muted)]",
                        )}
                      >
                        {item.label}
                      </Link>
                    );
                  })
                : null}
            </nav>
          </aside>

          <main className="min-w-0">
            {checkingSession ? (
              <div className="text-sm text-zinc-500">Loading portal...</div>
            ) : isUnlocked ? (
              children
            ) : (
              <Card className="shadow-none">
                <CardContent className="pt-5">
                  <div className="space-y-4">
                    <div>
                      <div className="text-lg font-semibold text-[var(--rb-text)]">Enter your case number</div>
                      <div className="mt-1 text-sm text-zinc-600">This portal is protected. Enter a case number to view your tickets and estimates.</div>
                    </div>

                    {unlockError ? (
                      <Alert variant="danger" title="Could not unlock portal">
                        {unlockError}
                      </Alert>
                    ) : null}

                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                      <Input
                        value={caseNumberInput}
                        onChange={(e) => setCaseNumberInput(e.target.value)}
                        placeholder="e.g. RB-1001"
                        disabled={unlockBusy}
                        onKeyDown={(e) => {
                          if (e.key === "Enter") {
                            e.preventDefault();
                            void unlock();
                          }
                        }}
                      />
                      <Button onClick={() => void unlock()} disabled={unlockBusy || !tenantSlug || tenantSlug.trim().length === 0}>
                        {unlockBusy ? "Unlocking..." : "Unlock"}
                      </Button>
                    </div>

                    <div className="text-xs text-zinc-500">
                      Tip: You can also use the public status page at{" "}
                      <Link href={`/t/${tenantSlug}/status`} className="text-[var(--rb-blue)] hover:underline">
                        /t/{tenantSlug}/status
                      </Link>
                      .
                    </div>
                  </div>
                </CardContent>
              </Card>
            )}
          </main>
        </div>
      </div>
    </div>
  );
}
