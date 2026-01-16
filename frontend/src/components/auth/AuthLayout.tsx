"use client";

import Image from "next/image";
import Link from "next/link";
import React from "react";
import { cn } from "@/lib/cn";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/Card";

function CheckIcon({ className }: { className?: string }) {
  return (
    <svg
      viewBox="0 0 24 24"
      className={cn("h-4 w-4 shrink-0", className)}
      fill="none"
      stroke="currentColor"
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
    >
      <path d="M20 6L9 17l-5-5" />
    </svg>
  );
}

export function AuthLayout({
  title,
  description,
  children,
  footer,
}: {
  title: string;
  description?: React.ReactNode;
  children: React.ReactNode;
  footer?: React.ReactNode;
}) {
  return (
    <div className="relative min-h-screen overflow-hidden bg-[color:color-mix(in_srgb,var(--rb-blue),white_96%)] text-[var(--rb-text)]">
      <div className="pointer-events-none absolute inset-0 z-0 bg-[linear-gradient(120deg,rgba(6,62,112,0.14),transparent_45%),linear-gradient(240deg,rgba(253,103,66,0.10),transparent_55%)]" />
      <div
        className={cn(
          "pointer-events-none absolute inset-0 z-0 opacity-70",
          "bg-[linear-gradient(to_right,rgba(6,62,112,0.12)_1px,transparent_1px),linear-gradient(to_bottom,rgba(6,62,112,0.12)_1px,transparent_1px)]",
          "bg-[length:52px_52px]",
        )}
      />
      <div
        className={cn(
          "pointer-events-none absolute inset-0 z-0",
          "bg-[radial-gradient(ellipse_at_top,rgba(6,62,112,0.18),transparent_55%),radial-gradient(ellipse_at_bottom,rgba(253,103,66,0.14),transparent_55%)]",
        )}
      />

      <div className="relative z-10 mx-auto flex min-h-screen w-full max-w-6xl items-stretch gap-10 px-4 py-10 lg:px-8">
        <aside className="hidden w-[360px] flex-col justify-between lg:flex">
          <div className="flex h-full flex-col rounded-[var(--rb-radius-xl)] bg-[var(--rb-blue)] p-8 shadow-[var(--rb-shadow)]">
            <Link href="/" className="inline-flex items-center gap-3">
              <Image alt="RepairBuddy" src="/brand/repair-buddy-logo.png" width={170} height={44} priority />
            </Link>

            <div className="mt-10 flex-1">
              <div className="text-sm font-semibold uppercase tracking-wider text-white/90">
                Welcome to RepairBuddy
              </div>
              <div className="mt-3 text-3xl font-semibold leading-tight text-white">
                Run your repair shop with clarity, speed, and confidence.
              </div>
              <div className="mt-4 text-sm leading-relaxed text-white/80">
                Secure access to your workspace, designed for busy teams.
              </div>

              <div className="mt-8 space-y-3">
                <div className="flex items-start gap-3 text-sm text-white/85">
                  <CheckIcon className="mt-0.5 text-white/90" />
                  <span className="min-w-0">Fast sign-in and verification flows.</span>
                </div>
                <div className="flex items-start gap-3 text-sm text-white/85">
                  <CheckIcon className="mt-0.5 text-white/90" />
                  <span className="min-w-0">Brand-consistent, accessible UI.</span>
                </div>
                <div className="flex items-start gap-3 text-sm text-white/85">
                  <CheckIcon className="mt-0.5 text-white/90" />
                  <span className="min-w-0">Designed for mobile and desktop.</span>
                </div>
              </div>

            </div>

            <div className="border-t border-white/15 pt-4 text-xs text-white/70">
              © {new Date().getFullYear()} RepairBuddy
            </div>
          </div>
        </aside>

        <div className="flex min-w-0 flex-1 items-center justify-center">
          <div className="w-full max-w-md">
            <div className="mb-6 flex items-center justify-center lg:hidden">
              <Link href="/" className="inline-flex items-center gap-3">
                <Image alt="RepairBuddy" src="/brand/repair-buddy-logo.png" width={160} height={42} priority />
              </Link>
            </div>

            <Card className="bg-[color:color-mix(in_srgb,white,var(--rb-blue)_3%)]">
              <CardHeader>
                <CardTitle className="text-base">{title}</CardTitle>
                {description ? <CardDescription>{description}</CardDescription> : null}
              </CardHeader>
              <CardContent>{children}</CardContent>
            </Card>

            {footer ? <div className="mt-5 text-center text-sm text-zinc-600">{footer}</div> : null}
          </div>
        </div>
      </div>

      <div className="pointer-events-none absolute inset-x-0 bottom-0 z-0 h-64 bg-[linear-gradient(to_top,rgba(6,62,112,0.18),transparent)]" />
      <div className="pointer-events-none absolute inset-y-0 left-0 z-0 w-[420px] bg-[linear-gradient(to_right,rgba(6,62,112,0.72),rgba(6,62,112,0.55),transparent)]" />
    </div>
  );
}
