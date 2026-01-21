"use client";

import React from "react";
import { AuthProvider } from "@/lib/auth";
import { Toaster } from "sonner";

export function Providers({ children }: { children: React.ReactNode }) {
  return (
    <AuthProvider>
      {children}
      <Toaster richColors closeButton position="top-right" />
    </AuthProvider>
  );
}
