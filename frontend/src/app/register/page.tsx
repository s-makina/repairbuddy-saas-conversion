"use client";

import { ApiError } from "@/lib/api";
import { useAuth } from "@/lib/auth";
import Link from "next/link";
import { useRouter } from "next/navigation";
import React, { useState } from "react";

export default function RegisterPage() {
  const auth = useAuth();
  const router = useRouter();

  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [tenantName, setTenantName] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setSubmitting(true);

    try {
      await auth.register({
        name,
        email,
        password,
        tenant_name: tenantName || undefined,
      });

      router.replace(`/verify-email?email=${encodeURIComponent(email)}`);
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError("Registration failed.");
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="min-h-screen bg-zinc-50 flex items-center justify-center px-4">
      <div className="w-full max-w-md rounded-xl border bg-white p-6">
        <h1 className="text-lg font-semibold">Register</h1>
        <p className="mt-1 text-sm text-zinc-500">Create your tenant and owner account.</p>

        {error ? <div className="mt-4 text-sm text-red-600">{error}</div> : null}

        <form className="mt-6 space-y-4" onSubmit={onSubmit}>
          <div className="space-y-1">
            <label className="text-sm font-medium" htmlFor="name">
              Name
            </label>
            <input
              className="w-full rounded-md border px-3 py-2 text-sm"
              id="name"
              autoComplete="name"
              value={name}
              onChange={(e) => setName(e.target.value)}
              type="text"
              required
            />
          </div>

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
            <div className="text-xs text-zinc-500">Minimum 12 chars, mixed case, number, and symbol.</div>
            <input
              className="w-full rounded-md border px-3 py-2 text-sm"
              id="password"
              autoComplete="new-password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              type="password"
              required
              minLength={12}
            />
          </div>

          <div className="space-y-1">
            <label className="text-sm font-medium" htmlFor="tenant_name">
              Tenant name
            </label>
            <input
              className="w-full rounded-md border px-3 py-2 text-sm"
              id="tenant_name"
              value={tenantName}
              onChange={(e) => setTenantName(e.target.value)}
              type="text"
              placeholder="e.g. Acme Repair"
            />
          </div>

          <button
            className="w-full rounded-md bg-zinc-900 px-3 py-2 text-sm font-medium text-white hover:bg-zinc-800 disabled:opacity-50"
            type="submit"
            disabled={submitting}
          >
            {submitting ? "Creating account..." : "Create account"}
          </button>
        </form>

        <div className="mt-4 text-sm text-zinc-600">
          Already have an account?{" "}
          <Link className="text-zinc-900 underline" href="/login">
            Login
          </Link>
        </div>
      </div>
    </div>
  );
}
