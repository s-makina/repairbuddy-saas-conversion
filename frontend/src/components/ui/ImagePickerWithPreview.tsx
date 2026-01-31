import React from "react";

import { Button } from "@/components/ui/Button";

function XIcon({ className }: { className?: string }) {
  return (
    <svg
      viewBox="0 0 24 24"
      width="16"
      height="16"
      aria-hidden="true"
      className={className}
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    >
      <path d="M18 6 6 18" />
      <path d="M6 6l12 12" />
    </svg>
  );
}

type Props = {
  label?: string;
  file: File | null;
  existingUrl?: string | null;
  disabled?: boolean;
  accept?: string;
  maxBytes?: number;
  onFileChange: (file: File | null) => void;
  onRemoveExisting?: () => void;
  onError?: (message: string) => void;
};

export function ImagePickerWithPreview({
  label = "Image",
  file,
  existingUrl,
  disabled,
  accept = "image/png,image/jpeg,image/webp",
  maxBytes = 5 * 1024 * 1024,
  onFileChange,
  onRemoveExisting,
  onError,
}: Props) {
  const inputRef = React.useRef<HTMLInputElement | null>(null);
  const [previewUrl, setPreviewUrl] = React.useState<string | null>(null);

  React.useEffect(() => {
    if (!file) {
      setPreviewUrl(null);
      if (inputRef.current) inputRef.current.value = "";
      return;
    }

    const url = URL.createObjectURL(file);
    setPreviewUrl(url);

    return () => {
      URL.revokeObjectURL(url);
    };
  }, [file]);

  function validate(next: File): boolean {
    if (!next.type.startsWith("image/")) {
      onError?.("Please select an image file.");
      return false;
    }

    if (next.size > maxBytes) {
      const mb = Math.floor(maxBytes / (1024 * 1024));
      onError?.(`Image must be ${mb}MB or smaller.`);
      return false;
    }

    return true;
  }

  function onPick(e: React.ChangeEvent<HTMLInputElement>) {
    const next = e.target.files?.[0] ?? null;
    if (!next) return;

    if (!validate(next)) {
      if (inputRef.current) {
        inputRef.current.value = "";
      }
      return;
    }

    onFileChange(next);
  }

  const displayUrl = previewUrl ?? (existingUrl ?? null);
  const hasExisting = !previewUrl && !!existingUrl;
  const canRemove = !!file || (hasExisting && !!onRemoveExisting);

  return (
    <div className="space-y-2">
      <div className="text-sm font-medium">{label}</div>

      <input ref={inputRef} type="file" accept={accept} className="hidden" onChange={onPick} disabled={disabled} />

      <div className="flex flex-wrap items-center gap-2">
        <Button variant="outline" size="sm" type="button" onClick={() => inputRef.current?.click()} disabled={disabled}>
          {file ? "Change selected image" : "Choose image"}
        </Button>
      </div>

      {displayUrl ? (
        <div className="relative inline-block">
          <img src={displayUrl} alt="Selected image" className="max-h-40 rounded border border-[var(--rb-border)]" />

          {canRemove ? (
            <button
              type="button"
              onClick={() => {
                if (file) {
                  onFileChange(null);
                  return;
                }
                onRemoveExisting?.();
              }}
              disabled={disabled}
              aria-label={file ? "Remove selected image" : "Remove image"}
              className="absolute right-2 top-2 inline-flex h-7 w-7 items-center justify-center rounded-full border border-[var(--rb-border)] bg-white/90 text-zinc-700 shadow-sm transition hover:bg-white disabled:pointer-events-none disabled:opacity-50"
            >
              <XIcon />
            </button>
          ) : null}
        </div>
      ) : null}
    </div>
  );
}
