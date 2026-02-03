"use client";

import React from "react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/Card";
import { Button } from "@/components/ui/Button";

export type WizardStepDefinition<TStep extends number> = {
  id: TStep;
  navTitle: string;
  navDescription: string;
  pageTitle: string;
  pageDescription: string;
  footerTitle?: string;
};

export function WizardShell<TStep extends number>({
  steps,
  step,
  onStepChange,
  disabled,
  sidebarTitle,
  sidebarDescription,
  sidebarAriaLabel,
  footerRight,
  children,
}: {
  steps: Array<WizardStepDefinition<TStep>>;
  step: TStep;
  onStepChange: (step: TStep) => void;
  disabled: boolean;
  sidebarTitle: string;
  sidebarDescription: string;
  sidebarAriaLabel: string;
  footerRight: React.ReactNode;
  children: React.ReactNode;
}) {
  const stepIndex = Math.max(
    0,
    steps.findIndex((s) => s.id === step),
  );
  const denom = Math.max(1, steps.length - 1);
  const progress = stepIndex / denom;

  const currentStep = steps[stepIndex];
  const isFirst = stepIndex === 0;
  const isLast = stepIndex === steps.length - 1;

  return (
    <div className="grid gap-6 lg:grid-cols-[260px_1fr]">
      <Card className="shadow-none lg:sticky lg:top-6 lg:self-start">
        <CardHeader className="pb-3">
          <CardTitle className="text-base">{sidebarTitle}</CardTitle>
          <CardDescription>{sidebarDescription}</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="h-2 w-full overflow-hidden rounded-full bg-[var(--rb-border)]">
            <div
              className="h-full bg-[linear-gradient(90deg,var(--rb-blue),var(--rb-orange))]"
              style={{ width: `${Math.round(progress * 100)}%` }}
            />
          </div>

          <nav aria-label={sidebarAriaLabel} className="space-y-1">
            {steps.map((s, idx) => {
              const isCurrent = s.id === step;
              const isCompleted = idx < stepIndex;
              const isAvailable = idx <= stepIndex;

              return (
                <button
                  key={s.id}
                  type="button"
                  disabled={!isAvailable || disabled}
                  onClick={() => {
                    if (!isAvailable) return;
                    onStepChange(s.id);
                  }}
                  className={
                    "w-full rounded-[var(--rb-radius-md)] border px-3 py-2 text-left transition " +
                    (isCurrent
                      ? "border-[color:color-mix(in_srgb,var(--rb-blue),white_65%)] bg-[color:color-mix(in_srgb,var(--rb-blue),white_92%)]"
                      : isCompleted
                        ? "border-[color:color-mix(in_srgb,var(--rb-blue),white_75%)] bg-white hover:bg-[var(--rb-surface-muted)]"
                        : "border-[var(--rb-border)] bg-white opacity-60")
                  }
                >
                  <div className="flex items-center gap-3">
                    <div
                      className={
                        "flex h-7 w-7 shrink-0 items-center justify-center rounded-full border text-xs font-semibold " +
                        (isCurrent
                          ? "border-[var(--rb-blue)] bg-[var(--rb-blue)] text-white"
                          : isCompleted
                            ? "border-[var(--rb-blue)] bg-[color:color-mix(in_srgb,var(--rb-blue),white_90%)] text-[var(--rb-blue)]"
                            : "border-[var(--rb-border)] bg-white text-zinc-600")
                      }
                    >
                      {isCompleted ? (
                        <svg
                          viewBox="0 0 24 24"
                          className="h-4 w-4"
                          fill="none"
                          stroke="currentColor"
                          strokeWidth={2}
                          strokeLinecap="round"
                          strokeLinejoin="round"
                          aria-hidden="true"
                        >
                          <path d="M20 6L9 17l-5-5" />
                        </svg>
                      ) : (
                        idx + 1
                      )}
                    </div>
                    <div className="min-w-0">
                      <div className="text-sm font-semibold text-[var(--rb-text)]">{s.navTitle}</div>
                      <div className="mt-0.5 line-clamp-2 text-xs text-zinc-600">{s.navDescription}</div>
                    </div>
                  </div>
                </button>
              );
            })}
          </nav>
        </CardContent>
      </Card>

      <Card className="shadow-none flex flex-col">
        <CardHeader>
          <div className="flex items-start justify-between gap-4">
            <div>
              <CardTitle className="text-base">{currentStep?.pageTitle ?? ""}</CardTitle>
              <CardDescription>{currentStep?.pageDescription ?? ""}</CardDescription>
            </div>
            <div className="text-right">
              <div className="text-xs text-zinc-500">
                Step {stepIndex + 1} of {steps.length}
              </div>
              <div className="mt-2 flex items-center justify-end gap-2">
                <div className="flex items-center gap-1" aria-label="Progress">
                  {Array.from({ length: steps.length }, (_, i) => i).map((i) => {
                    const isDone = i < stepIndex;
                    const isNow = i === stepIndex;
                    return (
                      <span
                        key={i}
                        className={
                          "h-1.5 w-5 rounded-full transition " +
                          (isNow
                            ? "bg-[var(--rb-blue)]"
                            : isDone
                              ? "bg-[color:color-mix(in_srgb,var(--rb-blue),white_55%)]"
                              : "bg-[var(--rb-border)]")
                        }
                      />
                    );
                  })}
                </div>
                <div className="rounded-full border border-[var(--rb-border)] bg-white px-2 py-1 text-[11px] font-medium text-zinc-600">
                  {Math.round(progress * 100)}%
                </div>
              </div>
            </div>
          </div>
        </CardHeader>

        <CardContent className="flex-1 space-y-6">{children}</CardContent>

        <div className="sticky bottom-0 border-t border-[var(--rb-border)] bg-white/90 px-5 py-4 backdrop-blur">
          <div className="flex items-center justify-between gap-4">
            <Button
              variant="ghost"
              disabled={disabled || isFirst}
              onClick={() => {
                if (isFirst) return;
                const prev = steps[stepIndex - 1];
                if (!prev) return;
                onStepChange(prev.id);
              }}
              type="button"
            >
              <span className="inline-flex items-center gap-2">
                <svg
                  viewBox="0 0 24 24"
                  className="h-4 w-4"
                  fill="none"
                  stroke="currentColor"
                  strokeWidth={2}
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  aria-hidden="true"
                >
                  <path d="M15 18l-6-6 6-6" />
                </svg>
                Back
              </span>
            </Button>

            <div className="flex items-center gap-3">
              <div className="hidden sm:block text-xs text-zinc-500">
                {currentStep?.footerTitle ?? currentStep?.navTitle ?? ""}
                <span className="mx-2">•</span>
                {Math.round(progress * 100)}%
              </div>

              {!isLast ? (
                <Button
                  variant="primary"
                  disabled={disabled}
                  onClick={() => {
                    const next = steps[stepIndex + 1];
                    if (!next) return;
                    onStepChange(next.id);
                  }}
                  type="button"
                >
                  <span className="inline-flex items-center gap-2">
                    Next
                    <svg
                      viewBox="0 0 24 24"
                      className="h-4 w-4"
                      fill="none"
                      stroke="currentColor"
                      strokeWidth={2}
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      aria-hidden="true"
                    >
                      <path d="M9 18l6-6-6-6" />
                    </svg>
                  </span>
                </Button>
              ) : (
                footerRight
              )}
            </div>
          </div>
        </div>
      </Card>
    </div>
  );
}
