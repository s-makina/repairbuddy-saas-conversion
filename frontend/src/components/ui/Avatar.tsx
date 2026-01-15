"use client";

import Image from "next/image";
import React from "react";
import { cn } from "@/lib/cn";

export function Avatar({
  src,
  alt,
  fallback,
  size = 36,
  className,
}: {
  src?: string | null;
  alt: string;
  fallback: string;
  size?: number;
  className?: string;
}) {
  const containerClass = cn(
      "relative inline-flex items-center justify-center overflow-hidden rounded-full",
      // Accessible background + text contrast
      "bg-slate-200 text-slate-800",
      "dark:bg-slate-700 dark:text-slate-100",
      // Visual separation
      "ring-1 ring-black/10 dark:ring-white/10",
      // Typography
      "text-sm font-semibold leading-none",
    className,
  );

  return (
    <span
      className={containerClass}
      style={{ width: size, height: size }}
      aria-label={alt}
      title={alt}
    >
      {src ? (
        <Image
          alt={alt}
          src={src}
          width={size}
          height={size}
          className="h-full w-full object-cover"
          loader={({ src }) => src}
          unoptimized
        />
      ) : (
        <span className="select-none uppercase">
          {fallback}
        </span>
      )}
    </span>
  );
}
