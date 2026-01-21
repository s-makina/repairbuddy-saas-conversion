"use client";

import React from "react";
import { RequireAuth } from "@/components/RequireAuth";
import { useParams, useRouter } from "next/navigation";
import { Button } from "@/components/ui/Button";

export default function PlansPage() {
  const params = useParams() as { business?: string };
  const business = params.business ?? "";
  const router = useRouter();

  return (
    <RequireAuth>
      <div className="min-h-screen flex items-center justify-center px-6">
        <div className="w-full max-w-md rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)] bg-white p-6 shadow-[var(--rb-shadow)]">
          <div className="text-base font-semibold text-[var(--rb-text)]">Choose a plan</div>
          <div className="mt-2 text-sm text-zinc-600">
            Plan selection is not implemented yet. This page is a placeholder for onboarding gating.
          </div>
          <div className="mt-5 flex flex-col gap-2">
            <Button variant="outline" onClick={() => router.replace(`/app/${business}`)}>
              Go to app
            </Button>
          </div>
        </div>
      </div>
    </RequireAuth>
  );
}
