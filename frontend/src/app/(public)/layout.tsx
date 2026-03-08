import "./v2.css";
import React from "react";

export const metadata = {
  title: "99SmartX — Repair Shop Management Made Simple",
};

export default function PublicLayout({ children }: { children: React.ReactNode }) {
  return <div className="v2">{children}</div>;
}
