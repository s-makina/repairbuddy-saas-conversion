"use client";

import React from "react";
import { Button } from "@/components/ui/Button";
import { Input } from "@/components/ui/Input";
import { Modal } from "@/components/ui/Modal";

export type CustomerCreatePayload = {
  name: string;
  email: string;
  phone: string;
  company: string;
  address_line1?: string;
  address_line2?: string;
  address_city?: string;
  address_state?: string;
  address_postal_code?: string;
  address_country?: string;
};

export function CustomerCreateModal({
  open,
  title,
  className,
  disabled,
  error,
  setError,
  name,
  setName,
  email,
  setEmail,
  emailInputType,
  phone,
  setPhone,
  company,
  setCompany,
  addressLine1,
  setAddressLine1,
  addressLine2,
  setAddressLine2,
  addressCity,
  setAddressCity,
  addressState,
  setAddressState,
  addressPostalCode,
  setAddressPostalCode,
  addressCountry,
  setAddressCountry,
  onClose,
  onSave,
}: {
  open: boolean;
  title: string;
  className?: string;
  disabled: boolean;
  error: string | null;
  setError: (next: string | null) => void;
  name: string;
  setName: (next: string) => void;
  email: string;
  setEmail: (next: string) => void;
  emailInputType?: React.HTMLInputTypeAttribute;
  phone: string;
  setPhone: (next: string) => void;
  company: string;
  setCompany: (next: string) => void;
  addressLine1?: string;
  setAddressLine1?: (next: string) => void;
  addressLine2?: string;
  setAddressLine2?: (next: string) => void;
  addressCity?: string;
  setAddressCity?: (next: string) => void;
  addressState?: string;
  setAddressState?: (next: string) => void;
  addressPostalCode?: string;
  setAddressPostalCode?: (next: string) => void;
  addressCountry?: string;
  setAddressCountry?: (next: string) => void;
  onClose: () => void;
  onSave: (payload: CustomerCreatePayload) => void;
}) {
  const hasAddressFields =
    typeof addressLine1 === "string" &&
    typeof setAddressLine1 === "function" &&
    typeof addressLine2 === "string" &&
    typeof setAddressLine2 === "function" &&
    typeof addressCity === "string" &&
    typeof setAddressCity === "function" &&
    typeof addressState === "string" &&
    typeof setAddressState === "function" &&
    typeof addressPostalCode === "string" &&
    typeof setAddressPostalCode === "function" &&
    typeof addressCountry === "string" &&
    typeof setAddressCountry === "function";

  return (
    <Modal
      open={open}
      title={title}
      onClose={() => {
        setError(null);
        onClose();
      }}
      className={className}
      footer={
        <div className="flex items-center justify-end gap-2">
          <Button
            type="button"
            variant="outline"
            onClick={() => {
              setError(null);
              onClose();
            }}
          >
            Cancel
          </Button>
          <Button
            type="button"
            disabled={disabled}
            onClick={() => {
              const trimmedName = name.trim();
              const trimmedEmail = email.trim();

              if (trimmedName === "") {
                setError("Customer name is required.");
                return;
              }
              if (trimmedEmail === "") {
                setError("Customer email is required.");
                return;
              }

              setError(null);

              onSave({
                name: trimmedName,
                email: trimmedEmail,
                phone,
                company,
                ...(hasAddressFields
                  ? {
                      address_line1: addressLine1,
                      address_line2: addressLine2,
                      address_city: addressCity,
                      address_state: addressState,
                      address_postal_code: addressPostalCode,
                      address_country: addressCountry,
                    }
                  : {}),
              });
            }}
          >
            Save
          </Button>
        </div>
      }
    >
      <div className="space-y-4">
        {error ? <div className="text-sm text-red-600">{error}</div> : null}

        <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
          <div>
            <div className="mb-1 text-xs text-zinc-600">Name</div>
            <Input value={name} onChange={(e) => setName(e.target.value)} disabled={disabled} />
          </div>
          <div>
            <div className="mb-1 text-xs text-zinc-600">Email</div>
            <Input
              type={emailInputType}
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              disabled={disabled}
            />
          </div>
          <div>
            <div className="mb-1 text-xs text-zinc-600">Phone</div>
            <Input value={phone} onChange={(e) => setPhone(e.target.value)} disabled={disabled} />
          </div>
          <div>
            <div className="mb-1 text-xs text-zinc-600">Company</div>
            <Input value={company} onChange={(e) => setCompany(e.target.value)} disabled={disabled} />
          </div>

          {hasAddressFields ? (
            <>
              <div className="md:col-span-2">
                <div className="mb-1 text-xs text-zinc-600">Address line 1</div>
                <Input value={addressLine1} onChange={(e) => setAddressLine1(e.target.value)} disabled={disabled} />
              </div>
              <div className="md:col-span-2">
                <div className="mb-1 text-xs text-zinc-600">Address line 2</div>
                <Input value={addressLine2} onChange={(e) => setAddressLine2(e.target.value)} disabled={disabled} />
              </div>
              <div>
                <div className="mb-1 text-xs text-zinc-600">City</div>
                <Input value={addressCity} onChange={(e) => setAddressCity(e.target.value)} disabled={disabled} />
              </div>
              <div>
                <div className="mb-1 text-xs text-zinc-600">State</div>
                <Input value={addressState} onChange={(e) => setAddressState(e.target.value)} disabled={disabled} />
              </div>
              <div>
                <div className="mb-1 text-xs text-zinc-600">Postal code</div>
                <Input value={addressPostalCode} onChange={(e) => setAddressPostalCode(e.target.value)} disabled={disabled} />
              </div>
              <div>
                <div className="mb-1 text-xs text-zinc-600">Country (2-letter)</div>
                <Input value={addressCountry} onChange={(e) => setAddressCountry(e.target.value)} disabled={disabled} placeholder="US" />
              </div>
            </>
          ) : null}
        </div>
      </div>
    </Modal>
  );
}
