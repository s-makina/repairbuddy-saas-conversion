"use client";

import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from "react";
import { apiFetch } from "@/lib/api";
import { clearToken, getToken, setToken } from "@/lib/token";
import type { Tenant, User } from "@/lib/types";

type AuthPayload = {
  token: string;
  user: User;
  tenant: Tenant | null;
};

type RegisterPayload = {
  verification_required: true;
  user: User;
  tenant: Tenant | null;
};

type LoginOtpRequiredPayload = {
  otp_required: true;
  otp_login_token: string;
};

type LoginVerificationRequiredPayload = {
  verification_required: true;
};

type LoginPayload = AuthPayload | LoginOtpRequiredPayload | LoginVerificationRequiredPayload;

export type LoginResult =
  | { status: "ok" }
  | { status: "otp_required"; otp_login_token: string }
  | { status: "verification_required" };

type MePayload = {
  user: User | null;
  tenant: Tenant | null;
};

type AuthContextValue = {
  loading: boolean;
  token: string | null;
  user: User | null;
  tenant: Tenant | null;
  isAuthenticated: boolean;
  isAdmin: boolean;
  refresh: () => Promise<void>;
  login: (email: string, password: string) => Promise<LoginResult>;
  loginOtp: (otpLoginToken: string, code: string) => Promise<void>;
  register: (input: {
    name: string;
    email: string;
    password: string;
    tenant_name?: string;
    tenant_slug?: string;
  }) => Promise<void>;
  resendVerificationEmail: (email: string) => Promise<void>;
  logout: () => Promise<void>;
};

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [loading, setLoading] = useState(true);
  const [token, setTokenState] = useState<string | null>(null);
  const [user, setUser] = useState<User | null>(null);
  const [tenant, setTenant] = useState<Tenant | null>(null);

  const refresh = useCallback(async () => {
    const currentToken = getToken();
    if (!currentToken) {
      setTokenState(null);
      setUser(null);
      setTenant(null);
      setLoading(false);
      return;
    }

    try {
      const me = await apiFetch<MePayload>("/api/auth/me", {
        token: currentToken,
      });

      if (!me.user) {
        clearToken();
        setTokenState(null);
        setUser(null);
        setTenant(null);
      } else {
        setTokenState(currentToken);
        setUser(me.user);
        setTenant(me.tenant);
      }
    } catch {
      clearToken();
      setTokenState(null);
      setUser(null);
      setTenant(null);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  const login = useCallback(async (email: string, password: string): Promise<LoginResult> => {
    const payload = await apiFetch<LoginPayload>("/api/auth/login", {
      method: "POST",
      body: { email, password },
    });

    if ("token" in payload && typeof payload.token === "string") {
      setToken(payload.token);
      setTokenState(payload.token);
      setUser(payload.user);
      setTenant(payload.tenant);
      return { status: "ok" };
    }

    if ("otp_required" in payload && payload.otp_required) {
      return { status: "otp_required", otp_login_token: payload.otp_login_token };
    }

    return { status: "verification_required" };
  }, []);

  const loginOtp = useCallback(async (otpLoginToken: string, code: string) => {
    const payload = await apiFetch<AuthPayload>("/api/auth/login/otp", {
      method: "POST",
      body: { otp_login_token: otpLoginToken, code },
    });

    setToken(payload.token);
    setTokenState(payload.token);
    setUser(payload.user);
    setTenant(payload.tenant);
  }, []);

  const register = useCallback(async (input: {
    name: string;
    email: string;
    password: string;
    tenant_name?: string;
    tenant_slug?: string;
  }) => {
    await apiFetch<RegisterPayload>("/api/auth/register", {
      method: "POST",
      body: input,
    });
  }, []);

  const resendVerificationEmail = useCallback(async (email: string) => {
    await apiFetch<{ status: "ok" }>("/api/auth/email/resend", {
      method: "POST",
      body: { email },
    });
  }, []);

  const logout = useCallback(async () => {
    const currentToken = getToken();

    try {
      if (currentToken) {
        await apiFetch<{ status: "ok" }>("/api/auth/logout", {
          method: "POST",
          token: currentToken,
        });
      }
    } finally {
      clearToken();
      setTokenState(null);
      setUser(null);
      setTenant(null);
    }
  }, []);

  const value = useMemo<AuthContextValue>(() => {
    const isAuthenticated = Boolean(token);
    const isAdmin = Boolean(user?.is_admin);

    return {
      loading,
      token,
      user,
      tenant,
      isAuthenticated,
      isAdmin,
      refresh,
      login,
      loginOtp,
      register,
      resendVerificationEmail,
      logout,
    };
  }, [loading, token, user, tenant, refresh, login, loginOtp, register, resendVerificationEmail, logout]);

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext);
  if (!ctx) {
    throw new Error("useAuth must be used within AuthProvider");
  }
  return ctx;
}
