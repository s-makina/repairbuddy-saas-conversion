"use client";

import React from "react";
import { cn } from "@/lib/cn";

export type ButtonVariant = "primary" | "secondary" | "outline" | "ghost";
export type ButtonSize = "sm" | "md" | "lg";

export function Button({
  className,
  variant = "primary",
  size = "md",
  asChild = false,
  type = "button",
  disabled,
  onClick,
  children,
  ...props
}: Omit<React.ButtonHTMLAttributes<HTMLButtonElement>, "disabled" | "onClick" | "children"> & {
  variant?: ButtonVariant;
  size?: ButtonSize;
  asChild?: boolean;
  disabled?: boolean;
  onClick?: React.MouseEventHandler<HTMLElement>;
  children?: React.ReactNode;
}) {
  const base =
    "inline-flex items-center justify-center whitespace-nowrap rounded-[var(--rb-radius-sm)] text-sm font-medium transition-colors disabled:pointer-events-none disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--rb-orange)] focus-visible:ring-offset-2 focus-visible:ring-offset-white";

  const sizes: Record<ButtonSize, string> = {
    sm: "h-8 px-3",
    md: "h-10 px-4",
    lg: "h-11 px-5",
  };

  const variants: Record<ButtonVariant, string> = {
    primary: "bg-[var(--rb-blue)] text-white hover:opacity-90",
    secondary: "bg-[var(--rb-orange)] text-white hover:opacity-90",
    outline: "border border-[var(--rb-border)] bg-white text-[var(--rb-text)] hover:bg-[var(--rb-surface-muted)]",
    ghost: "bg-transparent text-[var(--rb-text)] hover:bg-[var(--rb-surface-muted)]",
  };

  const resolvedClassName = cn(
    base,
    sizes[size],
    variants[variant],
    disabled ? "pointer-events-none opacity-50" : "",
    className,
  );

  if (asChild) {
    const onlyChild = React.Children.only(children);

    if (!React.isValidElement(onlyChild)) {
      return null;
    }

    const child = onlyChild as React.ReactElement<any>;

    return React.cloneElement(child, {
      className: cn(resolvedClassName, child.props?.className),
      "aria-disabled": disabled ? true : undefined,
      tabIndex: disabled ? -1 : child.props?.tabIndex,
      onClick: (e: React.MouseEvent<HTMLElement>) => {
        if (disabled) {
          e.preventDefault();
          e.stopPropagation();
          return;
        }
        onClick?.(e);
        child.props?.onClick?.(e);
      },
    });
  }

  return (
    <button
      className={resolvedClassName}
      type={type}
      disabled={disabled}
      onClick={onClick as React.MouseEventHandler<HTMLButtonElement> | undefined}
      {...props}
    >
      {children}
    </button>
  );
}
