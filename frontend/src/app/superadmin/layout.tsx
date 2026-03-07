"use client";

import React from "react";
import { usePathname } from "next/navigation";
import { SALayout } from "@/components/superadmin";
import "@/components/superadmin/superadmin.css";

const SUPERADMIN_FONTS = (
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
  </>
);

export default function AdminV2Layout({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();

  // Login page renders standalone (no sidebar shell)
  if (pathname === "/superadmin/login") {
    return (
      <>
        {SUPERADMIN_FONTS}
        {children}
      </>
    );
  }

  return (
    <>
      {SUPERADMIN_FONTS}
      <SALayout>{children}</SALayout>
    </>
  );
}
