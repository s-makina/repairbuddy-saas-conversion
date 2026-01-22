"use client";

import React, { useEffect, useMemo, useRef, useState } from "react";
import { apiFetch, ApiError } from "@/lib/api";
import { useAuth } from "@/lib/auth";
import { RequireAuth } from "@/components/RequireAuth";
import { Alert } from "@/components/ui/Alert";
import { Avatar } from "@/components/ui/Avatar";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/Card";
import { Input } from "@/components/ui/Input";
import { PageHeader } from "@/components/ui/PageHeader";

export default function TenantProfilePage() {
  const auth = useAuth();

  const user = auth.user;
  const isImpersonating = auth.isImpersonating;

  const initialName = user?.name ?? "";
  const initialEmail = user?.email ?? "";
  const initialPhone = user?.phone ?? "";
  const initialAddressLine1 = user?.address_line1 ?? "";
  const initialAddressLine2 = user?.address_line2 ?? "";
  const initialAddressCity = user?.address_city ?? "";
  const initialAddressState = user?.address_state ?? "";
  const initialAddressPostalCode = user?.address_postal_code ?? "";
  const initialAddressCountry = user?.address_country ?? "";

  const [name, setName] = useState(initialName);
  const [phone, setPhone] = useState(initialPhone);
  const [addressLine1, setAddressLine1] = useState(initialAddressLine1);
  const [addressLine2, setAddressLine2] = useState(initialAddressLine2);
  const [addressCity, setAddressCity] = useState(initialAddressCity);
  const [addressState, setAddressState] = useState(initialAddressState);
  const [addressPostalCode, setAddressPostalCode] = useState(initialAddressPostalCode);
  const [addressCountry, setAddressCountry] = useState(initialAddressCountry);

  const [saving, setSaving] = useState(false);
  const [sendingReset, setSendingReset] = useState(false);
  const [sendingVerify, setSendingVerify] = useState(false);

  const fileInputRef = useRef<HTMLInputElement | null>(null);
  const [avatarFile, setAvatarFile] = useState<File | null>(null);
  const [avatarPreviewUrl, setAvatarPreviewUrl] = useState<string | null>(null);
  const [uploadingAvatar, setUploadingAvatar] = useState(false);
  const [removingAvatar, setRemovingAvatar] = useState(false);

  const [error, setError] = useState<string | null>(null);
  const [status, setStatus] = useState<string | null>(null);

  useEffect(() => {
    setName(user?.name ?? "");
    setPhone(user?.phone ?? "");
    setAddressLine1(user?.address_line1 ?? "");
    setAddressLine2(user?.address_line2 ?? "");
    setAddressCity(user?.address_city ?? "");
    setAddressState(user?.address_state ?? "");
    setAddressPostalCode(user?.address_postal_code ?? "");
    setAddressCountry(user?.address_country ?? "");
  }, [
    user?.address_city,
    user?.address_country,
    user?.address_line1,
    user?.address_line2,
    user?.address_postal_code,
    user?.address_state,
    user?.name,
    user?.phone,
  ]);

  const emailVerified = Boolean(user?.email_verified_at);

  const roleLabel = useMemo(() => {
    if (!user) return "—";
    if (user.is_admin) return "Admin";
    return user.role ?? "User";
  }, [user]);

  const avatarFallback = useMemo(() => {
    const fromName = (user?.name ?? "").trim();
    if (fromName) {
      const parts = fromName.split(/\s+/).filter(Boolean);
      const first = parts[0]?.[0] ?? "";
      const last = parts.length > 1 ? parts[parts.length - 1]?.[0] ?? "" : "";
      return `${first}${last}`.toUpperCase().slice(0, 2) || "U";
    }

    const fromEmail = (user?.email ?? "").trim();
    if (fromEmail) {
      return fromEmail.slice(0, 2).toUpperCase();
    }

    return "U";
  }, [user?.email, user?.name]);

  const currentAvatarUrl = user?.avatar_url ?? null;
  const avatarSrc = avatarPreviewUrl ?? currentAvatarUrl;

  const canSave = Boolean(name.trim()) && !saving && !isImpersonating;
  const hasChanges =
    name.trim() !== (initialName ?? "").trim() ||
    (phone.trim() || "") !== (initialPhone ?? "").trim() ||
    (addressLine1.trim() || "") !== (initialAddressLine1 ?? "").trim() ||
    (addressLine2.trim() || "") !== (initialAddressLine2 ?? "").trim() ||
    (addressCity.trim() || "") !== (initialAddressCity ?? "").trim() ||
    (addressState.trim() || "") !== (initialAddressState ?? "").trim() ||
    (addressPostalCode.trim() || "") !== (initialAddressPostalCode ?? "").trim() ||
    addressCountry.trim().toUpperCase() !== (initialAddressCountry ?? "").trim().toUpperCase();

  async function onSave(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setStatus(null);

    if (!user) {
      setError("Not signed in.");
      return;
    }

    if (isImpersonating) {
      setError("Profile updates are disabled during impersonation.");
      return;
    }

    if (!name.trim()) {
      setError("Name is required.");
      return;
    }

    setSaving(true);

    try {
      await apiFetch<{ user: { id: number; name: string } }>("/api/auth/me", {
        method: "PATCH",
        body: {
          name: name.trim(),
          phone: phone.trim() || null,
          address_line1: addressLine1.trim() || null,
          address_line2: addressLine2.trim() || null,
          address_city: addressCity.trim() || null,
          address_state: addressState.trim() || null,
          address_postal_code: addressPostalCode.trim() || null,
          address_country: addressCountry.trim() ? addressCountry.trim().toUpperCase().slice(0, 2) : null,
        },
      });

      await auth.refresh();
      setStatus("Profile updated.");
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError("Failed to update profile.");
      }
    } finally {
      setSaving(false);
    }
  }

  function onPickAvatarFile(e: React.ChangeEvent<HTMLInputElement>) {
    const file = e.target.files?.[0] ?? null;
    if (!file) return;

    setError(null);
    setStatus(null);

    if (!file.type.startsWith("image/")) {
      setError("Please select an image file.");
      return;
    }

    if (file.size > 5 * 1024 * 1024) {
      setError("Image must be 5MB or smaller.");
      return;
    }

    setAvatarFile(file);
    const url = URL.createObjectURL(file);
    setAvatarPreviewUrl(url);
  }

  useEffect(() => {
    return () => {
      if (avatarPreviewUrl) {
        URL.revokeObjectURL(avatarPreviewUrl);
      }
    };
  }, [avatarPreviewUrl]);

  async function onUploadAvatar() {
    setError(null);
    setStatus(null);

    if (!user) {
      setError("Not signed in.");
      return;
    }

    if (isImpersonating) {
      setError("This action is disabled during impersonation.");
      return;
    }

    if (!avatarFile) {
      setError("Please choose a photo first.");
      return;
    }

    setUploadingAvatar(true);
    try {
      const formData = new FormData();
      formData.append("avatar", avatarFile);
      await apiFetch<{ user: unknown }>("/api/auth/me/avatar", {
        method: "POST",
        body: formData,
      });

      await auth.refresh();
      setStatus("Profile photo updated.");

      setAvatarFile(null);
      setAvatarPreviewUrl(null);
      if (fileInputRef.current) {
        fileInputRef.current.value = "";
      }
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError("Failed to update profile photo.");
      }
    } finally {
      setUploadingAvatar(false);
    }
  }

  async function onRemoveAvatar() {
    setError(null);
    setStatus(null);

    if (!user) {
      setError("Not signed in.");
      return;
    }

    if (isImpersonating) {
      setError("This action is disabled during impersonation.");
      return;
    }

    setRemovingAvatar(true);
    try {
      await apiFetch<{ user: unknown }>("/api/auth/me/avatar", {
        method: "DELETE",
      });

      await auth.refresh();
      setStatus("Profile photo removed.");

      setAvatarFile(null);
      setAvatarPreviewUrl(null);
      if (fileInputRef.current) {
        fileInputRef.current.value = "";
      }
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError("Failed to remove profile photo.");
      }
    } finally {
      setRemovingAvatar(false);
    }
  }

  async function onSendPasswordReset() {
    setError(null);
    setStatus(null);

    if (!initialEmail) {
      setError("Email is missing.");
      return;
    }

    if (isImpersonating) {
      setError("This action is disabled during impersonation.");
      return;
    }

    setSendingReset(true);

    try {
      await apiFetch<{ message: string }>("/api/auth/password/email", {
        method: "POST",
        body: { email: initialEmail },
      });

      setStatus("If that email exists, we sent a reset link.");
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError("Failed to send reset link.");
      }
    } finally {
      setSendingReset(false);
    }
  }

  async function onResendVerification() {
    setError(null);
    setStatus(null);

    if (!initialEmail) {
      setError("Email is missing.");
      return;
    }

    if (isImpersonating) {
      setError("This action is disabled during impersonation.");
      return;
    }

    setSendingVerify(true);

    try {
      await auth.resendVerificationEmail(initialEmail);
      setStatus("Verification email sent. Check your inbox.");
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError("Failed to resend verification email.");
      }
    } finally {
      setSendingVerify(false);
    }
  }

  return (
    <RequireAuth>
      <div className="space-y-6">
        <PageHeader title="Profile" description="Manage your account." />

        {isImpersonating ? (
          <Alert variant="warning" title="Impersonation active">
            Editing profile details is disabled while impersonating another user.
          </Alert>
        ) : null}

        {error ? (
          <Alert variant="danger" title="Could not update profile">
            {error}
          </Alert>
        ) : null}

        {status ? (
          <Alert variant="success" title="Success">
            {status}
          </Alert>
        ) : null}

        <Card className="overflow-hidden">
          <div className="h-20 w-full bg-[var(--rb-surface-muted)]" />
          <CardContent className="-mt-10">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
              <div className="flex items-end gap-4">
                <Avatar
                  alt={user?.name || initialEmail || "User"}
                  fallback={avatarFallback}
                  src={avatarSrc}
                  size={72}
                  className="ring-2 ring-white shadow-sm"
                />

                <div className="min-w-0">
                  <div className="truncate text-lg font-semibold text-[var(--rb-text)]">{user?.name || "—"}</div>
                  <div className="truncate text-sm text-zinc-600">{initialEmail || "—"}</div>
                  <div className="mt-2 flex flex-wrap items-center gap-2">
                    <Badge variant={emailVerified ? "success" : "warning"}>{emailVerified ? "Email verified" : "Email unverified"}</Badge>
                    <Badge>{roleLabel}</Badge>
                    {typeof user?.id === "number" ? <Badge>ID #{user.id}</Badge> : null}
                  </div>

                  <div className="mt-3 flex flex-wrap items-center gap-2">
                    <input
                      ref={fileInputRef}
                      type="file"
                      accept="image/png,image/jpeg,image/webp"
                      className="hidden"
                      onChange={onPickAvatarFile}
                      disabled={isImpersonating || uploadingAvatar || removingAvatar}
                    />

                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => fileInputRef.current?.click()}
                      disabled={isImpersonating || uploadingAvatar || removingAvatar}
                    >
                      {avatarFile ? "Change selected photo" : "Change photo"}
                    </Button>

                    {avatarFile ? (
                      <Button size="sm" onClick={() => void onUploadAvatar()} disabled={isImpersonating || uploadingAvatar || removingAvatar}>
                        {uploadingAvatar ? "Uploading..." : "Upload"}
                      </Button>
                    ) : null}

                    {currentAvatarUrl ? (
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => void onRemoveAvatar()}
                        disabled={isImpersonating || uploadingAvatar || removingAvatar}
                        className="text-zinc-600"
                      >
                        {removingAvatar ? "Removing..." : "Remove"}
                      </Button>
                    ) : null}
                  </div>
                </div>
              </div>

              <div className="flex flex-col items-start gap-1 text-xs text-zinc-600 sm:items-end">
                <div>Signed in as {initialEmail || "—"}</div>
                <div className="text-[var(--rb-text)]">Changes apply to your current session after saving.</div>
              </div>
            </div>
          </CardContent>
        </Card>

        <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
          <Card>
            <CardHeader>
              <div className="flex items-start justify-between gap-3">
                <div>
                  <CardTitle>Profile details</CardTitle>
                  <CardDescription>Update how your name appears across the app.</CardDescription>
                </div>
                {hasChanges ? <Badge variant="info">Unsaved changes</Badge> : null}
              </div>
            </CardHeader>
            <CardContent>
              <form className="space-y-4" onSubmit={onSave}>
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <div className="space-y-1">
                    <label className="text-sm font-medium" htmlFor="name">
                      Display name
                    </label>
                    <Input
                      id="name"
                      value={name}
                      onChange={(e) => setName(e.target.value)}
                      disabled={saving || isImpersonating}
                      autoComplete="name"
                      placeholder="Your name"
                      required
                    />
                  </div>

                  <div className="space-y-1">
                    <label className="text-sm font-medium" htmlFor="phone">
                      Phone
                    </label>
                    <Input
                      id="phone"
                      value={phone}
                      onChange={(e) => setPhone(e.target.value)}
                      disabled={saving || isImpersonating}
                      autoComplete="tel"
                      placeholder="Optional"
                    />
                  </div>

                  <div className="space-y-1 sm:col-span-2">
                    <label className="text-sm font-medium" htmlFor="address_line1">
                      Address line 1
                    </label>
                    <Input
                      id="address_line1"
                      value={addressLine1}
                      onChange={(e) => setAddressLine1(e.target.value)}
                      disabled={saving || isImpersonating}
                      autoComplete="address-line1"
                      placeholder="Optional"
                    />
                  </div>

                  <div className="space-y-1 sm:col-span-2">
                    <label className="text-sm font-medium" htmlFor="address_line2">
                      Address line 2
                    </label>
                    <Input
                      id="address_line2"
                      value={addressLine2}
                      onChange={(e) => setAddressLine2(e.target.value)}
                      disabled={saving || isImpersonating}
                      autoComplete="address-line2"
                      placeholder="Optional"
                    />
                  </div>

                  <div className="space-y-1">
                    <label className="text-sm font-medium" htmlFor="address_city">
                      City
                    </label>
                    <Input
                      id="address_city"
                      value={addressCity}
                      onChange={(e) => setAddressCity(e.target.value)}
                      disabled={saving || isImpersonating}
                      autoComplete="address-level2"
                      placeholder="Optional"
                    />
                  </div>

                  <div className="space-y-1">
                    <label className="text-sm font-medium" htmlFor="address_state">
                      State / Region
                    </label>
                    <Input
                      id="address_state"
                      value={addressState}
                      onChange={(e) => setAddressState(e.target.value)}
                      disabled={saving || isImpersonating}
                      autoComplete="address-level1"
                      placeholder="Optional"
                    />
                  </div>

                  <div className="space-y-1">
                    <label className="text-sm font-medium" htmlFor="address_postal_code">
                      Postal code
                    </label>
                    <Input
                      id="address_postal_code"
                      value={addressPostalCode}
                      onChange={(e) => setAddressPostalCode(e.target.value)}
                      disabled={saving || isImpersonating}
                      autoComplete="postal-code"
                      placeholder="Optional"
                    />
                  </div>

                  <div className="space-y-1">
                    <label className="text-sm font-medium" htmlFor="address_country">
                      Country
                    </label>
                    <Input
                      id="address_country"
                      value={addressCountry}
                      onChange={(e) => setAddressCountry(e.target.value.toUpperCase().slice(0, 2))}
                      disabled={saving || isImpersonating}
                      autoComplete="country"
                      placeholder="US"
                      maxLength={2}
                    />
                  </div>
                </div>

                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
                  <Button type="submit" disabled={!canSave || !hasChanges}>
                    {saving ? "Saving..." : "Save changes"}
                  </Button>
                </div>
              </form>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Account</CardTitle>
              <CardDescription>Security and login-related actions.</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-[var(--rb-surface-muted)] px-4 py-3">
                  <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Email</div>
                  <div className="mt-1 truncate text-sm font-semibold text-[var(--rb-text)]">{initialEmail || "—"}</div>
                  <div className="mt-2">
                    <Badge variant={emailVerified ? "success" : "warning"}>{emailVerified ? "Verified" : "Not verified"}</Badge>
                  </div>
                </div>

                <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-[var(--rb-surface-muted)] px-4 py-3">
                  <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">Role</div>
                  <div className="mt-1 text-sm font-semibold text-[var(--rb-text)]">{roleLabel}</div>
                  <div className="mt-1 text-xs text-zinc-600">User ID: {user?.id ?? "—"}</div>
                </div>
              </div>

              <div className="mt-4 border-t border-[var(--rb-border)] pt-4">
                <div className="flex flex-col gap-2 sm:flex-row">
                  {!emailVerified ? (
                    <Button
                      variant="outline"
                      onClick={() => void onResendVerification()}
                      disabled={sendingVerify || isImpersonating || !initialEmail}
                    >
                      {sendingVerify ? "Sending..." : "Resend verification email"}
                    </Button>
                  ) : null}

                  <Button
                    variant="outline"
                    onClick={() => void onSendPasswordReset()}
                    disabled={sendingReset || isImpersonating || !initialEmail}
                  >
                    {sendingReset ? "Sending..." : "Send password reset email"}
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </RequireAuth>
  );
}
