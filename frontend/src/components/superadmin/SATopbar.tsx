"use client";

import React from "react";

type SATopbarProps = {
  breadcrumb: React.ReactNode;
  title: string;
  actions?: React.ReactNode;
};

export function SATopbar({ breadcrumb, title, actions }: SATopbarProps) {
  return (
    <div className="sa-topbar">
      <div className="sa-tb-left">
        <div className="sa-tb-bc">{breadcrumb}</div>
        <div className="sa-tb-title">{title}</div>
      </div>
      {actions && <div className="sa-tb-right">{actions}</div>}
    </div>
  );
}

/* Re-usable topbar action buttons that match the design */
export function SAIconButton({
  children,
  hasNotification,
  onClick,
}: {
  children: React.ReactNode;
  hasNotification?: boolean;
  onClick?: () => void;
}) {
  return (
    <button className="sa-ib" type="button" onClick={onClick}>
      {children}
      {hasNotification && <span className="sa-ndot" />}
    </button>
  );
}

export function SAButton({
  variant = "primary",
  children,
  icon,
  onClick,
  style,
  disabled,
}: {
  variant?: "primary" | "ghost" | "outline";
  children: React.ReactNode;
  icon?: React.ReactNode;
  onClick?: () => void;
  style?: React.CSSProperties;
  disabled?: boolean;
}) {
  const cls =
    variant === "primary"
      ? "sa-btn sa-btn-primary"
      : variant === "outline"
        ? "sa-btn sa-btn-outline"
        : "sa-btn sa-btn-ghost";
  return (
    <button className={cls} type="button" onClick={onClick} style={style} disabled={disabled}>
      {icon}
      {children}
    </button>
  );
}
