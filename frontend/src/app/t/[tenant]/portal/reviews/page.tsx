"use client";

import React from "react";
import { useParams } from "next/navigation";
import { PortalShell } from "@/components/shells/PortalShell";
import { Alert } from "@/components/ui/Alert";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Card, CardContent } from "@/components/ui/Card";
import { mockApi } from "@/mock/mockApi";
import type { Job, JobId, Review } from "@/mock/types";

type PortalSession = {
  jobId: string;
  caseNumber: string;
};

type LocalReview = {
  id: string;
  created_at: string;
  rating: 1 | 2 | 3 | 4 | 5;
  comment: string;
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

function localReviewsKey(tenantSlug: string, jobId: string) {
  return `rb.portal.reviews.local:v1:${tenantSlug}:${jobId}`;
}

function safeJsonParse(raw: string | null): unknown {
  if (!raw) return null;
  try {
    return JSON.parse(raw);
  } catch {
    return null;
  }
}

function loadLocalReviews(tenantSlug: string, jobId: string): LocalReview[] {
  if (typeof window === "undefined") return [];
  const parsed = safeJsonParse(window.localStorage.getItem(localReviewsKey(tenantSlug, jobId)));
  if (!Array.isArray(parsed)) return [];
  return parsed as LocalReview[];
}

function saveLocalReviews(tenantSlug: string, jobId: string, next: LocalReview[]) {
  if (typeof window === "undefined") return;
  try {
    window.localStorage.setItem(localReviewsKey(tenantSlug, jobId), JSON.stringify(next));
  } catch {
    // ignore
  }
}

function nextLocalReviewId(tenantSlug: string, jobId: string, existing: LocalReview[]) {
  const n = existing.length + 1;
  return `local_review_${tenantSlug}_${jobId}_${String(n).padStart(3, "0")}`;
}

export default function PortalReviewsPage() {
  const params = useParams() as { tenant?: string };
  const tenantSlug = typeof params.tenant === "string" ? params.tenant : "";

  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [session, setSession] = React.useState<PortalSession | null>(null);

  const [job, setJob] = React.useState<Job | null>(null);
  const [existing, setExisting] = React.useState<Review[]>([]);
  const [local, setLocal] = React.useState<LocalReview[]>([]);

  const [rating, setRating] = React.useState<LocalReview["rating"]>(5);
  const [comment, setComment] = React.useState("");
  const [submitError, setSubmitError] = React.useState<string | null>(null);

  React.useEffect(() => {
    let alive = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);

        if (!tenantSlug) {
          setSession(null);
          setJob(null);
          setExisting([]);
          setLocal([]);
          return;
        }

        const s = loadPortalSession(tenantSlug);
        setSession(s);

        if (!s) {
          setJob(null);
          setExisting([]);
          setLocal([]);
          return;
        }

        const [j, reviews] = await Promise.all([mockApi.getJob(s.jobId as JobId), mockApi.listReviews()]);
        if (!alive) return;

        setJob(j);

        const filtered = (Array.isArray(reviews) ? reviews : []).filter((r) => r.job_id === (s.jobId as JobId));
        setExisting(filtered);

        setLocal(loadLocalReviews(tenantSlug, s.jobId));
      } catch (e) {
        if (!alive) return;
        setError(e instanceof Error ? e.message : "Failed to load reviews.");
        setExisting([]);
        setLocal([]);
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
    <PortalShell tenantSlug={tenantSlug} title="Reviews" subtitle="Your feedback helps the shop improve.">
      <div className="space-y-4">
        {loading ? <div className="text-sm text-zinc-500">Loading reviews...</div> : null}

        {error ? (
          <Alert variant="danger" title="Could not load reviews">
            {error}
          </Alert>
        ) : null}

        {!loading && !error && !session ? (
          <Alert variant="warning" title="Portal locked">
            Enter your case number to view reviews.
          </Alert>
        ) : null}

        {session ? (
          <Card className="shadow-none">
            <CardContent className="pt-5">
              <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Leave a review</div>
                  <div className="mt-1 text-sm text-zinc-600">Case {job?.case_number ?? session.caseNumber}</div>
                </div>
                <Badge variant="info">{session.jobId}</Badge>
              </div>

              {submitError ? (
                <div className="mt-3">
                  <Alert variant="danger" title="Could not submit review">
                    {submitError}
                  </Alert>
                </div>
              ) : null}

              <div className="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Rating</div>
                  <div className="mt-2">
                    <select
                      value={rating}
                      onChange={(e) => setRating((Number(e.target.value) as LocalReview["rating"]) ?? 5)}
                      className="h-10 w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 text-sm"
                    >
                      <option value={5}>5 - Excellent</option>
                      <option value={4}>4 - Good</option>
                      <option value={3}>3 - Okay</option>
                      <option value={2}>2 - Poor</option>
                      <option value={1}>1 - Terrible</option>
                    </select>
                  </div>
                </div>
                <div className="sm:col-span-2">
                  <div className="text-sm font-semibold text-[var(--rb-text)]">Comment</div>
                  <div className="mt-2">
                    <textarea
                      value={comment}
                      onChange={(e) => setComment(e.target.value)}
                      placeholder="Tell us about your experience..."
                      className="w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
                      rows={4}
                    />
                  </div>
                </div>
              </div>

              <div className="mt-4 flex items-center justify-end">
                <Button
                  onClick={() => {
                    if (!session) return;
                    setSubmitError(null);
                    const body = comment.trim();
                    if (!body) {
                      setSubmitError("Comment is required.");
                      return;
                    }
                    const next: LocalReview = {
                      id: nextLocalReviewId(tenantSlug, session.jobId, local),
                      created_at: new Date().toISOString(),
                      rating,
                      comment: body,
                    };
                    const merged = [next, ...local];
                    setLocal(merged);
                    saveLocalReviews(tenantSlug, session.jobId, merged);
                    setComment("");
                    setRating(5);
                  }}
                >
                  Submit
                </Button>
              </div>
            </CardContent>
          </Card>
        ) : null}

        {session ? (
          <div className="space-y-3">
            <div className="text-sm font-semibold text-[var(--rb-text)]">Your reviews (mock)</div>

            {local.length === 0 ? <div className="text-sm text-zinc-600">No reviews submitted yet.</div> : null}

            {local.map((r) => (
              <Card key={r.id} className="shadow-none">
                <CardContent className="pt-5">
                  <div className="flex items-start justify-between gap-3">
                    <div>
                      <div className="text-sm font-semibold text-[var(--rb-text)]">Rating: {r.rating}/5</div>
                      <div className="mt-1 text-xs text-zinc-500">{new Date(r.created_at).toLocaleString()}</div>
                    </div>
                    <Badge variant="default">local</Badge>
                  </div>
                  <div className="mt-3 whitespace-pre-wrap text-sm text-zinc-700">{r.comment}</div>
                </CardContent>
              </Card>
            ))}

            {existing.length > 0 ? (
              <div className="pt-4">
                <div className="text-sm font-semibold text-[var(--rb-text)]">Existing reviews (fixture)</div>
                <div className="mt-3 space-y-3">
                  {existing.map((r) => (
                    <Card key={r.id} className="shadow-none">
                      <CardContent className="pt-5">
                        <div className="flex items-start justify-between gap-3">
                          <div>
                            <div className="text-sm font-semibold text-[var(--rb-text)]">Rating: {r.rating}/5</div>
                            <div className="mt-1 text-xs text-zinc-500">{new Date(r.created_at).toLocaleString()}</div>
                          </div>
                          <Badge variant="info">{r.id}</Badge>
                        </div>
                        <div className="mt-3 whitespace-pre-wrap text-sm text-zinc-700">{r.comment}</div>
                      </CardContent>
                    </Card>
                  ))}
                </div>
              </div>
            ) : null}
          </div>
        ) : null}
      </div>
    </PortalShell>
  );
}
