"use client";

import React from "react";
import { Button } from "@/components/ui/Button";
import type { BillingPlan, BillingPlanVersion, BillingPrice, PlanEntitlement } from "@/lib/types";

export function BillingPlanCard({
  plan,
  version,
  selectedPrice,
  currency,
  interval,
  recommended,
  priceLabel,
  intervalLabel,
  visibleEntitlements,
  submitting,
  onSelect,
  actionLabel,
}: {
  plan: BillingPlan;
  version: BillingPlanVersion;
  selectedPrice: BillingPrice | null;
  currency: string;
  interval: string;
  recommended: boolean;
  priceLabel: string;
  intervalLabel: string;
  visibleEntitlements: PlanEntitlement[];
  submitting: boolean;
  onSelect: (() => void) | null;
  actionLabel: string;
}) {
  void version;

  return (
    <div
      className={
        "relative flex h-full min-h-[420px] flex-col overflow-hidden rounded-[var(--rb-radius-xl)] border bg-white p-5 shadow-[var(--rb-shadow)] " +
        (recommended
          ? "border-[color:color-mix(in_srgb,var(--rb-orange),white_45%)] ring-2 ring-[color:color-mix(in_srgb,var(--rb-orange),white_70%)]"
          : "border-[var(--rb-border)]")
      }
    >
      {recommended ? (
        <div className="absolute right-4 top-4 rounded-full bg-[color:color-mix(in_srgb,var(--rb-orange),white_20%)] px-2.5 py-1 text-[11px] font-semibold text-white">
          Recommended
        </div>
      ) : null}

      <div className="text-base font-semibold text-[var(--rb-text)]">{plan.name}</div>
      {plan.description ? <div className="mt-1 text-sm text-zinc-600">{plan.description}</div> : null}

      <div className="mt-4 flex items-end justify-between gap-3">
        <div>
          <div className="text-3xl font-semibold tracking-tight text-[var(--rb-text)]">{priceLabel}</div>
          <div className="mt-1 text-xs text-zinc-600">
            {selectedPrice ? `per ${intervalLabel.toLowerCase()}` : `Unavailable for ${currency} / ${interval}`}
          </div>
        </div>
      </div>

      {visibleEntitlements.length > 0 ? (
        <div className="mt-4 flex min-h-0 flex-1 flex-col">
          <div className="text-xs font-medium text-zinc-600">What’s included</div>
          <div className="mt-2 min-h-0 overflow-y-auto pr-1 [scrollbar-width:thin]">
            <div className="grid gap-2">
              {visibleEntitlements.map((e) => (
                <div key={e.id} className="flex items-start gap-2 text-sm text-[var(--rb-text)]">
                  <svg
                    viewBox="0 0 24 24"
                    className="mt-0.5 h-4 w-4 shrink-0 text-[var(--rb-orange)]"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth={2}
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    aria-hidden="true"
                  >
                    <path d="M20 6L9 17l-5-5" />
                  </svg>
                  <div className="flex min-w-0 flex-1 items-start justify-between gap-3">
                    <div className="min-w-0">
                      <div className="truncate">{e.definition?.name}</div>
                      {e.definition?.description ? <div className="mt-0.5 text-xs text-zinc-600">{e.definition.description}</div> : null}
                    </div>

                    {String(e.definition?.value_type ?? "") === "integer" && typeof e.value_json === "number" ? (
                      <div className="shrink-0 rounded-full border border-[var(--rb-border)] bg-[var(--rb-surface-muted)] px-2 py-0.5 text-xs font-semibold text-[var(--rb-text)]">
                        {Math.trunc(e.value_json)}
                      </div>
                    ) : null}
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      ) : (
        <div className="mt-4 flex-1 text-sm text-zinc-600">Everything you need to get started.</div>
      )}

      <div className="mt-5 pt-2">
        <Button
          variant={recommended ? "secondary" : "primary"}
          className="w-full"
          disabled={!onSelect || submitting || !selectedPrice}
          onClick={() => {
            if (!onSelect) return;
            onSelect();
          }}
        >
          {selectedPrice ? actionLabel : "Unavailable"}
        </Button>
      </div>
    </div>
  );
}
