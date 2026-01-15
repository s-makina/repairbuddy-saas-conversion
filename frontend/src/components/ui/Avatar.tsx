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
    "relative inline-flex items-center justify-center overflow-hidden rounded-full bg-white/15 text-sm font-semibold text-white",
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
          loader={({ src: loaderSrc }) => loaderSrc}
          unoptimized
        />
      ) : (
        <span className="select-none">{fallback}</span>
      )}
    </span>
  );
}
