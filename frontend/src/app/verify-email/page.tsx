"use client";

import { ApiError } from "@/lib/api";
import { useAuth } from "@/lib/auth";
import { Preloader } from "@/components/Preloader";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import React, { Suspense, useEffect, useMemo, useRef, useState } from "react";

const WrenchIcon = () => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
    <path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z" />
  </svg>
);

function VerifyEmailPageInner() {
  const auth = useAuth();
  const searchParams = useSearchParams();

  const initialEmail = useMemo(() => searchParams.get("email") || "", [searchParams]);
  const verified = useMemo(() => searchParams.get("verified") === "1", [searchParams]);

  const [email] = useState(initialEmail);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(verified ? "Your email has been verified. You can now sign in." : null);
  const [submitting, setSubmitting] = useState(false);
  const [countdown, setCountdown] = useState(verified ? 0 : 59);
  const intervalRef = useRef<ReturnType<typeof setInterval> | null>(null);

  useEffect(() => {
    if (verified || countdown <= 0) return;
    intervalRef.current = setInterval(() => {
      setCountdown((c) => {
        if (c <= 1) {
          if (intervalRef.current) clearInterval(intervalRef.current);
          return 0;
        }
        return c - 1;
      });
    }, 1000);
    return () => { if (intervalRef.current) clearInterval(intervalRef.current); };
  }, [verified, countdown]);

  async function handleResend() {
    if (countdown > 0 || submitting) return;
    setError(null);
    setSuccess(null);
    setSubmitting(true);
    try {
      await auth.resendVerificationEmail(email);
      setSuccess("Verification email sent. Check your inbox.");
      setCountdown(59);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Failed to resend verification email.");
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="v2">
      <div className="auth-page">
        <Link href="/" className="brand-link">
          <div className="logo-mark-lg"><WrenchIcon /></div>
          <span className="brand-name-lg">RepairBuddy</span>
        </Link>

        <div className="auth-card auth-card-wide text-center">
          <div className={`auth-icon auth-icon-orange${verified ? "" : " floating"}`}>
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.8"
                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
          </div>

          <h1 style={{ fontSize: 24, fontWeight: 800, letterSpacing: "-.02em", marginBottom: 10 }}>
            {verified ? "Email verified!" : "Check your email"}
          </h1>

          {error && <div className="alert-error">{error}</div>}
          {success && <div className="alert-success">{success}</div>}

          {!verified && (
            <>
              <p className="sub">{"We've sent a verification link to"}</p>
              {email && <div className="email-highlight">{email}</div>}
              <p className="sub">Click the link in your email to verify your account and complete setup. The link will expire in 24 hours.</p>

              <a
                href="mailto:"
                className="btn-submit"
                style={{ textDecoration: "none", marginTop: 20 }}
              >
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                Open Email App
              </a>

              <div className="resend">
                {countdown > 0 ? (
                  <>Didn{"'"}t receive the email? Resend in <span className="timer">0:{String(countdown).padStart(2, "0")}</span></>
                ) : (
                  <>Didn{"'"}t receive the email?{" "}
                    <button onClick={handleResend} disabled={submitting}>
                      {submitting ? "Sending…" : "Resend verification email"}
                    </button>
                  </>
                )}
              </div>

              <div className="help-text">
                Check your spam or junk folder if you don{"'"}t see the email. If {"you're"} still having trouble, contact{" "}
                <a href="mailto:support@99smartx.com">support@99smartx.com</a>
              </div>
            </>
          )}

          {verified && (
            <p className="sub" style={{ marginTop: 8 }}>
              Your account is now active. Sign in to get started.
            </p>
          )}
        </div>

        <div className="auth-footer">
          Wrong email? <Link href="/register">Go back to signup</Link>
        </div>
        <Link href="/login" className="back-link">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 19l-7-7 7-7" />
          </svg>
          Back to sign in
        </Link>
      </div>
    </div>
  );
}

export default function VerifyEmailPage() {
  return (
    <Suspense fallback={<Preloader />}>
      <VerifyEmailPageInner />
    </Suspense>
  );
}
