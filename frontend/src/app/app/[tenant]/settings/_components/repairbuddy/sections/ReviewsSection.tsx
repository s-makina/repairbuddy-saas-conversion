"use client";

import React, { useMemo } from "react";
import { Input } from "@/components/ui/Input";
import { Select } from "@/components/ui/Select";
import { SectionShell } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/SectionShell";
import type { RepairBuddySettingsDraft } from "@/app/app/[tenant]/settings/_components/repairbuddy/types";

export function ReviewsSection({
  draft,
  updateReviews,
}: {
  draft: RepairBuddySettingsDraft;
  updateReviews: (patch: Partial<RepairBuddySettingsDraft["reviews"]>) => void;
}) {
  const r = draft.reviews;
  const statusOptions = useMemo(() => draft.jobStatuses.statuses, [draft.jobStatuses.statuses]);

  return (
    <SectionShell title="Reviews" description="Customer feedback request rules and message templates.">
      <div className="grid gap-4 sm:grid-cols-2">
        <label className="sm:col-span-2 flex items-center gap-2 text-sm">
          <input type="checkbox" checked={r.requestFeedbackBySms} onChange={(e) => updateReviews({ requestFeedbackBySms: e.target.checked })} />
          Request feedback by SMS
        </label>
        <label className="sm:col-span-2 flex items-center gap-2 text-sm">
          <input type="checkbox" checked={r.requestFeedbackByEmail} onChange={(e) => updateReviews({ requestFeedbackByEmail: e.target.checked })} />
          Request feedback by Email
        </label>

        <div className="space-y-1">
          <label className="text-sm font-medium">Feedback page selection</label>
          <Select value={r.feedbackPage} onChange={(e) => updateReviews({ feedbackPage: e.target.value })}>
            <option value="">Select page (mock)</option>
            <option value="feedback">Feedback</option>
            <option value="reviews">Reviews</option>
          </Select>
        </div>

        <div className="space-y-1">
          <label className="text-sm font-medium">Send review request if job status is</label>
          <Select value={r.sendReviewRequestIfJobStatusId} onChange={(e) => updateReviews({ sendReviewRequestIfJobStatusId: e.target.value })}>
            {statusOptions.map((s) => (
              <option key={s.id} value={s.id}>
                {s.name}
              </option>
            ))}
          </Select>
        </div>

        <div className="space-y-1">
          <label className="text-sm font-medium">Auto feedback request interval (days)</label>
          <Input
            type="number"
            min={0}
            value={String(r.autoFeedbackRequestIntervalDays)}
            onChange={(e) => updateReviews({ autoFeedbackRequestIntervalDays: Number(e.target.value || 0) })}
          />
        </div>

        <div className="sm:col-span-2 space-y-1">
          <label className="text-sm font-medium">Email subject</label>
          <Input value={r.emailSubject} onChange={(e) => updateReviews({ emailSubject: e.target.value })} />
        </div>

        <div className="sm:col-span-2 space-y-1">
          <label className="text-sm font-medium">Email message template</label>
          <textarea
            className="min-h-[120px] w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
            value={r.emailMessageTemplate}
            onChange={(e) => updateReviews({ emailMessageTemplate: e.target.value })}
          />
        </div>

        <div className="sm:col-span-2 space-y-1">
          <label className="text-sm font-medium">SMS message template</label>
          <textarea
            className="min-h-[90px] w-full rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2 text-sm"
            value={r.smsMessageTemplate}
            onChange={(e) => updateReviews({ smsMessageTemplate: e.target.value })}
          />
        </div>
      </div>
    </SectionShell>
  );
}
