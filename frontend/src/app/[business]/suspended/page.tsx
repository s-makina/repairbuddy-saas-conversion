"use client";

import React from "react";
import { RequireAuth } from "@/components/RequireAuth";
import { useAuth } from "@/lib/auth";
import { Button } from "@/components/ui/Button";
import { useRouter } from "next/navigation";

export default function SuspendedPage() {
  const auth = useAuth();
  const router = useRouter();

  return (
    <RequireAuth>
      <div className="min-h-screen flex items-center justify-center px-6">
        <div className="w-full max-w-md rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)] bg-white p-6 shadow-[var(--rb-shadow)]">
          <div className="text-base font-semibold text-[var(--rb-text)]">Account suspended</div>
          <div className="mt-2 text-sm text-zinc-600">
            {auth.tenant?.suspension_reason
              ? `Reason: ${auth.tenant.suspension_reason}`
              : "Your workspace is suspended. Please contact support."}
          </div>
          <div className="mt-5 flex flex-col gap-2">
            <Button
              variant="outline"
              onClick={() => {
                void auth.logout().then(() => router.replace("/login"));
              }}
            >
              Sign out
            </Button>
          </div>
        </div>
      </div>
    </RequireAuth>
  );
}
