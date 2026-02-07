"use client";

import React, { useCallback, useMemo, useState } from "react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import {
  Building2,
  Calculator,
  Calendar,
  CircleHelp,
  Clock,
  CreditCard,
  FileText,
  Laptop,
  Settings,
  Sparkles,
  Star,
  Tag,
  User,
  Wrench,
} from "lucide-react";
import { Alert } from "@/components/ui/Alert";
import { Button } from "@/components/ui/Button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/Card";
import { Skeleton } from "@/components/ui/Skeleton";
import { repairBuddyNav } from "@/app/app/[tenant]/settings/_components/repairbuddy/repairBuddyNav";
import { useRepairBuddyDraft } from "@/app/app/[tenant]/settings/_components/repairbuddy/useRepairBuddyDraft";
import { CompanyProfileSection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/CompanyProfileSection";
import { BookingSection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/BookingSection";
import { CurrencySection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/CurrencySection";
import { DevicesBrandsSection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/DevicesBrandsSection";
import { EstimatesSection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/EstimatesSection";
import { GeneralSection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/GeneralSection";
import { InvoicesReportsSection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/InvoicesReportsSection";
import { JobStatusesSection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/JobStatusesSection";
import { MaintenanceRemindersSection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/MaintenanceRemindersSection";
import { MyAccountSection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/MyAccountSection";
import { PaymentsSection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/PaymentsSection";
import { ReviewsSection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/ReviewsSection";
import { ServiceSettingsSection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/ServiceSettingsSection";
import { SignatureWorkflowSection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/SignatureWorkflowSection";
import { SmsSection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/SmsSection";
import { StylingLabelsSection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/StylingLabelsSection";
import { TaxesSection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/TaxesSection";
import { TimeLogsSection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/TimeLogsSection";

function SettingsSkeleton() {
  return (
    <div className="space-y-4">
      <div className="rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)] bg-white px-5 py-4">
        <div className="flex flex-wrap items-start justify-between gap-3">
          <div>
            <Skeleton className="h-4 w-40 rounded-[var(--rb-radius-sm)]" />
            <Skeleton className="mt-2 h-3 w-36 rounded-[var(--rb-radius-sm)]" />
            <Skeleton className="mt-3 h-9 w-[520px] max-w-full rounded-[var(--rb-radius-sm)]" />
          </div>
          <Skeleton className="h-9 w-20 rounded-[var(--rb-radius-sm)]" />
        </div>
      </div>

      <Card className="shadow-none">
        <CardContent className="pt-5">
          <Skeleton className="h-5 w-48 rounded-[var(--rb-radius-sm)]" />
          <Skeleton className="mt-3 h-4 w-full rounded-[var(--rb-radius-sm)]" />
          <Skeleton className="mt-2 h-4 w-4/5 rounded-[var(--rb-radius-sm)]" />
          <Skeleton className="mt-2 h-4 w-3/5 rounded-[var(--rb-radius-sm)]" />
          <div className="mt-5 grid gap-3 sm:grid-cols-2">
            {Array.from({ length: 6 }).map((_, idx) => (
              <div key={idx} className="rounded-[var(--rb-radius-sm)] border border-[var(--rb-border)] bg-white px-3 py-2">
                <Skeleton className="h-4 w-40 rounded-[var(--rb-radius-sm)]" />
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

const navIcons: Record<string, React.ComponentType<{ className?: string; "aria-hidden"?: boolean }>> = {
  "company-profile": Building2,
  general: Settings,
  currency: Calculator,
  "invoices-reports": FileText,
  "job-statuses": Tag,
  payments: CreditCard,
  reviews: Star,
  estimates: FileText,
  "my-account": User,
  "devices-brands": Laptop,
  sms: CircleHelp,
  taxes: Calculator,
  "service-settings": Wrench,
  "time-logs": Clock,
  "maintenance-reminders": Clock,
  "styling-labels": Sparkles,
  "signature-workflow": FileText,
  booking: Calendar,
};

export function RepairBuddySettingsTab({ tenantSlug }: { tenantSlug: string }) {
  const searchParams = useSearchParams();
  const { draft, updateSection, setDraft, isMock, savingDisabledReason, loading, saving, error, save } = useRepairBuddyDraft(tenantSlug);
  const [status, setStatus] = useState<string | null>(null);

  const updateCurrency = useCallback((patch: Partial<typeof draft.currency>) => updateSection("currency", patch), [updateSection]);

  const selectedKey = useMemo(() => {
    const sectionParam = searchParams.get("section");
    const keys = new Set(repairBuddyNav.map((item) => item.key));
    if (sectionParam && keys.has(sectionParam)) return sectionParam;
    return "company-profile";
  }, [searchParams]);

  const selectedItem = useMemo(() => repairBuddyNav.find((item) => item.key === selectedKey) ?? null, [selectedKey]);

  const sectionNode = useMemo(() => {
    switch (selectedKey) {
      case "company-profile":
        return <CompanyProfileSection tenantSlug={tenantSlug} />;
      case "general":
        return <GeneralSection draft={draft} updateGeneral={(patch) => updateSection("general", patch)} />;
      case "currency":
        return <CurrencySection draft={draft} updateCurrency={updateCurrency} />;
      case "invoices-reports":
        return <InvoicesReportsSection draft={draft} updateInvoicesReports={(patch) => updateSection("invoicesReports", patch)} />;
      case "job-statuses":
        return <JobStatusesSection tenantSlug={tenantSlug} />;
      case "payments":
        return <PaymentsSection draft={draft} updatePayments={(patch) => updateSection("payments", patch)} isMock={isMock} />;
      case "reviews":
        return <ReviewsSection draft={draft} updateReviews={(patch) => updateSection("reviews", patch)} />;
      case "estimates":
        return <EstimatesSection draft={draft} updateEstimates={(patch) => updateSection("estimates", patch)} />;
      case "my-account":
        return <MyAccountSection draft={draft} updateMyAccount={(patch) => updateSection("myAccount", patch)} />;
      case "devices-brands":
        return <DevicesBrandsSection draft={draft} updateDevicesBrands={(patch) => updateSection("devicesBrands", patch)} isMock={isMock} />;
      case "sms":
        return <SmsSection draft={draft} updateSms={(patch) => updateSection("sms", patch)} />;
      case "taxes":
        return <TaxesSection tenantSlug={tenantSlug} draft={draft} updateTaxes={(patch) => updateSection("taxes", patch)} isMock={isMock} />;
      case "service-settings":
        return <ServiceSettingsSection draft={draft} updateServiceSettings={(patch) => updateSection("serviceSettings", patch)} />;
      case "time-logs":
        return <TimeLogsSection tenantSlug={tenantSlug} draft={draft} updateTimeLogs={(patch) => updateSection("timeLogs", patch)} isMock={isMock} />;
      case "maintenance-reminders":
        return <MaintenanceRemindersSection tenantSlug={tenantSlug} draft={draft} isMock={isMock} />;
      case "styling-labels":
        return <StylingLabelsSection draft={draft} updateStylingLabels={(patch) => updateSection("stylingLabels", patch)} />;
      case "signature-workflow":
        return <SignatureWorkflowSection draft={draft} updateSignatureWorkflow={(patch) => updateSection("signatureWorkflow", patch)} />;
      case "booking":
        return <BookingSection draft={draft} updateBooking={(patch) => updateSection("booking", patch)} />;
      default:
        return null;
    }
  }, [draft, isMock, selectedKey, tenantSlug, updateCurrency, updateSection]);

  async function onSave() {
    setStatus(null);
    try {
      await save();
      setStatus("Saved.");
    } catch {
      return;
    }
  }

  return (
    <div className="space-y-6">
      {/* <Alert variant="warning" title="Mock screens">
        Saving is not available yet.
      </Alert> */}

      {error ? (
        <Alert variant="danger" title="Could not load or save business settings">
          {error}
        </Alert>
      ) : null}

      {status ? (
        <Alert variant="success" title="Success">
          {status}
        </Alert>
      ) : null}

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-[260px_1fr]">
        <Card>
          <CardHeader>
            <CardTitle>Business Settings</CardTitle>
            <CardDescription>Operational settings for this business.</CardDescription>
          </CardHeader>
          <CardContent className="space-y-1">
            {repairBuddyNav.map((item) => {
              const isActive = item.key === selectedKey;
              const Icon = navIcons[item.key] ?? CircleHelp;

              return (
                <Link
                  key={item.key}
                  href={`/app/${tenantSlug}/business-settings?section=${item.key}`}
                  scroll={false}
                  className={
                    "block rounded-[var(--rb-radius-sm)] px-3 py-2 text-sm transition-colors " +
                    (isActive
                      ? "bg-[var(--rb-surface-muted)] font-medium text-[var(--rb-text)]"
                      : "text-zinc-700 hover:bg-[var(--rb-surface-muted)]")
                  }
                >
                  <span className="flex items-center gap-2">
                    <Icon
                      className={
                        "h-4 w-4 shrink-0 " +
                        (isActive ? "text-[var(--rb-text)]" : "text-zinc-500")
                      }
                      aria-hidden
                    />
                    <span>{item.label}</span>
                  </span>
                </Link>
              );
            })}
          </CardContent>
        </Card>

        <div className="space-y-4">
          <div className="rounded-[var(--rb-radius-lg)] border border-[var(--rb-border)] bg-white px-5 py-4">
            <div className="flex flex-wrap items-start justify-between gap-3">
              <div>
                <div className="text-sm font-semibold text-[var(--rb-text)]">{selectedItem?.label ?? "Business Settings"}</div>
                <div className="mt-1 text-xs text-zinc-500">Business: {tenantSlug}</div>
                {savingDisabledReason ? (
                  <div className="mt-2 text-xs text-zinc-500">{savingDisabledReason}</div>
                ) : null}
                <div className="mt-3 flex flex-wrap items-center gap-2">
                  <Button asChild variant="outline" size="sm">
                    <Link href={`/t/${tenantSlug}/portal`}>Preview portal</Link>
                  </Button>
                  <Button asChild variant="outline" size="sm">
                    <Link href={`/t/${tenantSlug}/status`}>Preview status</Link>
                  </Button>
                  <Button asChild variant="outline" size="sm">
                    <Link href={`/t/${tenantSlug}/book`}>Preview booking</Link>
                  </Button>
                  <Button asChild variant="outline" size="sm">
                    <Link href={`/t/${tenantSlug}/services`}>Preview services</Link>
                  </Button>
                  <Button asChild variant="outline" size="sm">
                    <Link href={`/t/${tenantSlug}/parts`}>Preview parts</Link>
                  </Button>
                </div>
              </div>
              <Button variant="outline" onClick={() => void onSave()} disabled={Boolean(savingDisabledReason) || loading || saving}>
                {saving ? "Saving..." : "Save"}
              </Button>
            </div>
          </div>

          {loading ? <SettingsSkeleton /> : sectionNode}
        </div>
      </div>
    </div>
  );
}
