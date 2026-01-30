import React from "react";
import { cn } from "@/lib/cn";

type RenderProps = {
  describedBy?: string;
  descriptionId?: string;
  errorId?: string;
  invalid: boolean;
};

export function FormRow({
  label,
  fieldId,
  description,
  error,
  required,
  className,
  labelClassName,
  fieldClassName,
  children,
}: {
  label: string;
  fieldId: string;
  description?: string;
  error?: string | null;
  required?: boolean;
  className?: string;
  labelClassName?: string;
  fieldClassName?: string;
  children: React.ReactNode | ((props: RenderProps) => React.ReactNode);
}) {
  const descriptionId = description ? `${fieldId}__description` : undefined;
  const errorId = error ? `${fieldId}__error` : undefined;

  const describedBy = [descriptionId, errorId].filter(Boolean).join(" ") || undefined;
  const invalid = Boolean(error);

  const content = typeof children === "function" ? children({ describedBy, descriptionId, errorId, invalid }) : children;

  return (
    <div className={cn("grid gap-1 sm:grid-cols-[220px_1fr] sm:gap-4", className)}>
      <div className="sm:pt-2">
        <label
          className={cn(
            "block text-sm font-medium text-[var(--rb-text)]",
            labelClassName,
          )}
          htmlFor={fieldId}
        >
          {label}
          {required ? <span className="text-red-600"> *</span> : null}
        </label>
      </div>

      <div className={cn("min-w-0", fieldClassName)}>
        {content}

        {description ? (
          <div id={descriptionId} className="mt-1 text-xs text-zinc-500">
            {description}
          </div>
        ) : null}

        {error ? (
          <div id={errorId} className="mt-1 text-xs text-red-600">
            {error}
          </div>
        ) : null}
      </div>
    </div>
  );
}
