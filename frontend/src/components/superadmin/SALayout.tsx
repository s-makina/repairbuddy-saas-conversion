"use client";

import React from "react";
import { SASidebar } from "./SASidebar";
import "./superadmin.css";

type SALayoutProps = {
  children: React.ReactNode;
};

export function SALayout({ children }: SALayoutProps) {
  return (
    <div className="sa-root">
      <SASidebar />
      <div className="sa-main">
        {children}
      </div>
    </div>
  );
}
