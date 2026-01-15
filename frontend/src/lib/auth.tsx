"use client";

import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from "react";
import { apiFetch } from "@/lib/api";
import { clearToken, getToken, setToken } from "@/lib/token";
import type { Tenant, User } from "@/lib/types";

type AuthPayload = {
  token: string;
  user: User;
  tenant: Tenant | null;
  permissions: string[];
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
  permissions: string[];
};

type AuthContextValue = {
  loading: boolean;
  token: string | null;
  user: User | null;
  tenant: Tenant | null;
  permissions: string[];
  isAuthenticated: boolean;
  isAdmin: boolean;
  can: (permission: string) => boolean;
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
  const [permissions, setPermissions] = useState<string[]>([]);

  const refresh = useCallback(async () => {
    const currentToken = getToken();
    if (!currentToken) {
      setTokenState(null);
      setUser(null);
      setTenant(null);
      setPermissions([]);
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
        setPermissions([]);
      } else {
        setTokenState(currentToken);
        setUser(me.user);
        setTenant(me.tenant);
        setPermissions(Array.isArray(me.permissions) ? me.permissions : []);
      }
    } catch {
      clearToken();
      setTokenState(null);
      setUser(null);
      setTenant(null);
      setPermissions([]);
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
      setPermissions(Array.isArray(payload.permissions) ? payload.permissions : []);
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
    setPermissions(Array.isArray(payload.permissions) ? payload.permissions : []);
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
      setPermissions([]);
    }
  }, []);

  const value = useMemo<AuthContextValue>(() => {
    const isAuthenticated = Boolean(token);
    const isAdmin = Boolean(user?.is_admin);
    const can = (permission: string) => {
      if (isAdmin) return true;
      return permissions.includes(permission);
    };

    return {
      loading,
      token,
      user,
      tenant,
      permissions,
      isAuthenticated,
      isAdmin,
      can,
      refresh,
      login,
      loginOtp,
      register,
      resendVerificationEmail,
      logout,
    };
  }, [loading, token, user, tenant, permissions, refresh, login, loginOtp, register, resendVerificationEmail, logout]);

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext);
  if (!ctx) {
    throw new Error("useAuth must be used within AuthProvider");
  }
  return ctx;
}
