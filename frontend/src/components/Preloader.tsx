"use client";

import Image from "next/image";
import React from "react";

export function Preloader({
  label = "Loading",
  fullscreen = true,
}: {
  label?: string;
  fullscreen?: boolean;
}) {
  return (
    <div
      className={
        fullscreen
          ? "min-h-screen flex items-center justify-center bg-[var(--rb-surface-muted)]"
          : "flex items-center justify-center"
      }
    >
      <div className="flex flex-col items-center gap-4">
        <Image
          alt="RepairBuddy"
          src="/brand/repair-buddy-logo.png"
          width={160}
          height={42}
          priority
        />
        <div className="flex items-center gap-3">
          <div
            className="h-6 w-6 rounded-full border-2 border-[var(--rb-border)] border-t-[var(--rb-orange)] animate-spin"
            aria-hidden="true"
          />
          <div className="text-sm font-medium text-[var(--rb-text)]">{label}...</div>
        </div>
      </div>
    </div>
  );
}
