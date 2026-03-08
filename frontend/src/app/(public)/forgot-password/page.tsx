"use client";

import { apiFetch, ApiError } from "@/lib/api";
import Link from "next/link";
import React, { useState } from "react";

const WrenchIcon = () => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
    <path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z" />
  </svg>
);

export default function ForgotPasswordPage() {
  const [email, setEmail] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setSuccess(null);
    setSubmitting(true);

    try {
      await apiFetch<{ message: string }>("/api/auth/password/email", {
        method: "POST",
        body: { email },
      });
      setSuccess("We've sent a password reset link to your email. Check your inbox.");
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Failed to send reset link. Please try again.");
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="auth-page">
      <Link href="/" className="brand-link">
        <div className="logo-mark-lg"><WrenchIcon /></div>
        <span className="brand-name-lg">RepairBuddy</span>
      </Link>

      <div className="auth-card">
        <div className="auth-icon auth-icon-orange" style={{ width: 64, height: 64, marginBottom: 20 }}>
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style={{ width: 28, height: 28 }}>
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.8"
              d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
          </svg>
        </div>
        <div className="auth-header">
          <h1>Forgot your password?</h1>
          <p>No worries! Enter the email address associated with your account and we{"'"}ll send you a reset link.</p>
        </div>

        {error && <div className="alert-error">{error}</div>}
        {success && <div className="alert-success">{success}</div>}

        {!success ? (
          <form onSubmit={onSubmit}>
            <div className="form-group">
              <label className="form-label">Email Address</label>
              <div className="input-wrap">
                <input
                  type="email"
                  className="form-input"
                  placeholder="john@repairshop.com"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  required
                  autoComplete="email"
                />
                <svg className="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
              </div>
            </div>
            <button type="submit" className="btn-submit" style={{ marginTop: 24 }} disabled={submitting}>
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
              {submitting ? "Sending…" : "Send Reset Link"}
            </button>
          </form>
        ) : (
          <Link href="/login" className="btn-submit" style={{ textDecoration: "none", marginTop: 8 }}>
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
            Back to Sign In
          </Link>
        )}
      </div>

      <div className="auth-footer">
        Remember your password? <Link href="/login">Sign in</Link>
      </div>
      <Link href="/login" className="back-link">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 19l-7-7 7-7" />
        </svg>
        Back to sign in
      </Link>
    </div>
  );
}
