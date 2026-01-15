"use client";

import { useAuth } from "@/lib/auth";
import { useRouter } from "next/navigation";
import React, { useEffect } from "react";

export default function AppIndexPage() {
  const auth = useAuth();
  const router = useRouter();

  useEffect(() => {
    if (auth.loading) return;

    if (!auth.isAuthenticated) {
      router.replace("/login");
      return;
    }

    if (auth.isAdmin) {
      router.replace("/admin");
      return;
    }

    const slug = auth.tenant?.slug;

    if (!slug) {
      router.replace("/");
      return;
    }

    router.replace(`/app/${slug}`);
  }, [auth.isAdmin, auth.isAuthenticated, auth.loading, auth.tenant?.slug, router]);

  return (
    <div className="min-h-screen flex items-center justify-center text-sm text-zinc-500">
      Loading...
    </div>
  );
}
