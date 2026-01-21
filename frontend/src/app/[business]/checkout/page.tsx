"use client";

import React from "react";
import { RequireAuth } from "@/components/RequireAuth";
import { useParams, useRouter } from "next/navigation";
import { Button } from "@/components/ui/Button";

export default function CheckoutPage() {
  const params = useParams() as { business?: string };
  const business = params.business ?? "";
  const router = useRouter();

  return (
    <RequireAuth>
      <div className="min-h-screen flex items-center justify-center px-6">
        <div className="w-full max-w-md rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)] bg-white p-6 shadow-[var(--rb-shadow)]">
          <div className="text-base font-semibold text-[var(--rb-text)]">Checkout</div>
          <div className="mt-2 text-sm text-zinc-600">
            Checkout is not implemented yet. This page is a placeholder for onboarding gating.
          </div>
          <div className="mt-5 flex flex-col gap-2">
            <Button variant="outline" onClick={() => router.replace(`/${business}/plans`)}>
              Back to plans
            </Button>
          </div>
        </div>
      </div>
    </RequireAuth>
  );
}
