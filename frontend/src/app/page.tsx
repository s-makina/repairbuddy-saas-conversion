"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import React, { useEffect } from "react";
import { useAuth } from "@/lib/auth";

export default function Home() {
  const auth = useAuth();
  const router = useRouter();

  useEffect(() => {
    if (auth.loading) return;
    if (!auth.isAuthenticated) return;

    if (auth.isAdmin) {
      router.replace("/admin");
      return;
    }

    router.replace("/app");
  }, [auth.isAdmin, auth.isAuthenticated, auth.loading, router]);

  return (
    <div className="min-h-screen bg-zinc-50 flex items-center justify-center px-4">
      <div className="w-full max-w-xl rounded-xl border bg-white p-8">
        <h1 className="text-2xl font-semibold">RepairBuddy</h1>
        <p className="mt-2 text-sm text-zinc-600">
          Milestone 1 frontend scaffold (auth + admin/tenant dashboards).
        </p>

        <div className="mt-6 flex flex-col gap-3 sm:flex-row">
          <Link
            className="inline-flex items-center justify-center rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800"
            href="/login"
          >
            Login
          </Link>
          <Link
            className="inline-flex items-center justify-center rounded-md border bg-white px-4 py-2 text-sm font-medium hover:bg-zinc-50"
            href="/register"
          >
            Register
          </Link>
        </div>

        <div className="mt-6 rounded-lg border bg-zinc-50 px-4 py-3 text-xs text-zinc-600">
          Backend endpoints:
          <div className="mt-2 font-mono">POST /api/auth/login</div>
          <div className="font-mono">POST /api/auth/register</div>
          <div className="font-mono">GET /api/admin/tenants</div>
          <div className="font-mono">GET /api/&lt;tenant&gt;/app/dashboard</div>
        </div>
      </div>
    </div>
  );
}
