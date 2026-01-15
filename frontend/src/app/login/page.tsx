"use client";

import { useAuth } from "@/lib/auth";
import { ApiError } from "@/lib/api";
import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import React, { useMemo, useState } from "react";

export default function LoginPage() {
  const auth = useAuth();
  const router = useRouter();
  const searchParams = useSearchParams();

  const next = useMemo(() => searchParams.get("next"), [searchParams]);

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setSubmitting(true);

    try {
      await auth.login(email, password);

      router.replace(next || "/");
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError("Login failed.");
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="min-h-screen bg-zinc-50 flex items-center justify-center px-4">
      <div className="w-full max-w-md rounded-xl border bg-white p-6">
        <h1 className="text-lg font-semibold">Login</h1>
        <p className="mt-1 text-sm text-zinc-500">Sign in to access your dashboard.</p>

        {error ? <div className="mt-4 text-sm text-red-600">{error}</div> : null}

        <form className="mt-6 space-y-4" onSubmit={onSubmit}>
          <div className="space-y-1">
            <label className="text-sm font-medium" htmlFor="email">
              Email
            </label>
            <input
              className="w-full rounded-md border px-3 py-2 text-sm"
              id="email"
              autoComplete="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              type="email"
              required
            />
          </div>

          <div className="space-y-1">
            <label className="text-sm font-medium" htmlFor="password">
              Password
            </label>
            <input
              className="w-full rounded-md border px-3 py-2 text-sm"
              id="password"
              autoComplete="current-password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              type="password"
              required
            />
          </div>

          <button
            className="w-full rounded-md bg-zinc-900 px-3 py-2 text-sm font-medium text-white hover:bg-zinc-800 disabled:opacity-50"
            type="submit"
            disabled={submitting}
          >
            {submitting ? "Signing in..." : "Sign in"}
          </button>
        </form>

        <div className="mt-4 text-sm text-zinc-600">
          Don’t have an account?{" "}
          <Link className="text-zinc-900 underline" href="/register">
            Register
          </Link>
        </div>
      </div>
    </div>
  );
}
