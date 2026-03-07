"use client";

import React from "react";
import { SALayout } from "@/components/superadmin";

export default function AdminV2Layout({ children }: { children: React.ReactNode }) {
  return (
    <>
      {/* DM Sans font required by the design4 warm-light theme */}
      {/* eslint-disable-next-line @next/next/no-page-custom-font */}
      <link rel="preconnect" href="https://fonts.googleapis.com" />
      {/* eslint-disable-next-line @next/next/no-page-custom-font */}
      <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
      {/* eslint-disable-next-line @next/next/no-page-custom-font */}
      <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet"
      />
      <SALayout>{children}</SALayout>
    </>
  );
}
