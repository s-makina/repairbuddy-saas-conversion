"use client";

import Link from "next/link";
import React from "react";
import { useAuth } from "@/lib/auth";

export function DashboardShell({
  title,
  children,
}: {
  title: string;
  children: React.ReactNode;
}) {
  const auth = useAuth();

  return (
    <div className="min-h-screen bg-zinc-50 text-zinc-900">
      <header className="border-b bg-white">
        <div className="mx-auto flex max-w-5xl items-center justify-between px-4 py-4">
          <div className="flex flex-col">
            <div className="text-sm font-semibold">RepairBuddy</div>
            <div className="text-xs text-zinc-500">{title}</div>
          </div>

          <nav className="flex items-center gap-3 text-sm">
            <Link className="text-zinc-600 hover:text-zinc-900" href="/">
              Home
            </Link>
            {auth.isAdmin ? (
              <Link className="text-zinc-600 hover:text-zinc-900" href="/admin">
                Admin
              </Link>
            ) : null}
            {auth.tenant?.slug ? (
              <Link
                className="text-zinc-600 hover:text-zinc-900"
                href={`/app/${auth.tenant.slug}`}
              >
                App
              </Link>
            ) : null}
            {!auth.isAdmin && auth.tenant?.slug ? (
              <Link
                className="text-zinc-600 hover:text-zinc-900"
                href={`/app/${auth.tenant.slug}/security`}
              >
                Security
              </Link>
            ) : null}
            <button
              className="rounded-md border bg-white px-3 py-1.5 hover:bg-zinc-50"
              onClick={() => void auth.logout()}
              type="button"
            >
              Logout
            </button>
          </nav>
        </div>
      </header>

      <main className="mx-auto max-w-5xl px-4 py-8">{children}</main>
    </div>
  );
}
