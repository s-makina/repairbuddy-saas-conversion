"use client";

import { useAuth } from "@/lib/auth";
import { Button } from "@/components/ui/Button";
import { Preloader } from "@/components/Preloader";
import { computeGateRedirect, getGate } from "@/lib/gate";
import { useRouter } from "next/navigation";
import React, { useEffect } from "react";

export default function AppIndexPage() {
  const auth = useAuth();
  const router = useRouter();
  const missingTenant = !auth.loading && auth.isAuthenticated && !auth.isAdmin && !auth.tenant?.slug;

  useEffect(() => {
    if (auth.loading) return;

    if (!auth.isAuthenticated) {
      router.replace("/login");
      return;
    }

    if (auth.user?.must_change_password) {
      router.replace("/set-password");
      return;
    }

    if (auth.isAdmin) {
      router.replace("/admin");
      return;
    }

    const slug = auth.tenant?.slug;

    if (!slug) {
      return;
    }

    void getGate(slug)
      .then(({ gate }) => {
        router.replace(computeGateRedirect(slug, gate));
      })
      .catch(() => {
        if (!auth.tenant?.setup_completed_at) {
          router.replace(`/${slug}/setup`);
          return;
        }
        router.replace(`/app/${slug}`);
      });
  }, [auth.isAdmin, auth.isAuthenticated, auth.loading, auth.tenant?.setup_completed_at, auth.tenant?.slug, router]);

  if (!auth.loading && auth.isAuthenticated && !auth.isAdmin && missingTenant) {
    return (
      <div className="min-h-screen flex items-center justify-center px-6">
        <div className="w-full max-w-md rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)] bg-white p-6 shadow-[var(--rb-shadow)]">
          <div className="text-base font-semibold text-[var(--rb-text)]">Workspace not found</div>
          <div className="mt-2 text-sm text-zinc-600">
            Your account is signed in, but it isn&apos;t linked to a workspace (tenant) yet.
          </div>
          <div className="mt-5 flex flex-col gap-2">
            <Button
              variant="primary"
              onClick={() => {
                void auth.logout().then(() => router.replace("/login"));
              }}
            >
              Sign out
            </Button>
            <Button variant="outline" onClick={() => router.replace("/")}>Go to home</Button>
          </div>
        </div>
      </div>
    );
  }

  return <Preloader />;
}
