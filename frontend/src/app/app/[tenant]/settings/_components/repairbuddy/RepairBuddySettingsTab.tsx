"use client";

import React, { useMemo } from "react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { Alert } from "@/components/ui/Alert";
import { Button } from "@/components/ui/Button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/Card";
import { repairBuddyNav } from "@/app/app/[tenant]/settings/_components/repairbuddy/repairBuddyNav";
import { useRepairBuddyDraft } from "@/app/app/[tenant]/settings/_components/repairbuddy/useRepairBuddyDraft";
import { BookingSection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/BookingSection";
import { CurrencySection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/CurrencySection";
import { DevicesBrandsSection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/DevicesBrandsSection";
import { EstimatesSection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/EstimatesSection";
import { GeneralSection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/GeneralSection";
import { InvoicesReportsSection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/InvoicesReportsSection";
import { JobStatusesSection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/JobStatusesSection";
import { MaintenanceRemindersSection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/MaintenanceRemindersSection";
import { MyAccountSection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/MyAccountSection";
import { PagesSetupSection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/PagesSetupSection";
import { PaymentsSection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/PaymentsSection";
import { ReviewsSection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/ReviewsSection";
import { ServiceSettingsSection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/ServiceSettingsSection";
import { SignatureWorkflowSection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/SignatureWorkflowSection";
import { SmsSection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/SmsSection";
import { StylingLabelsSection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/StylingLabelsSection";
import { TaxesSection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/TaxesSection";
import { TimeLogsSection } from "@/app/app/[tenant]/settings/_components/repairbuddy/sections/TimeLogsSection";

export function RepairBuddySettingsTab({ tenantSlug }: { tenantSlug: string }) {
  const searchParams = useSearchParams();
  const { draft, updateSection, setDraft, isMock, savingDisabledReason } = useRepairBuddyDraft();

  const selectedKey = useMemo(() => {
    const sectionParam = searchParams.get("section");
    const keys = new Set(repairBuddyNav.map((item) => item.key));
    if (sectionParam && keys.has(sectionParam)) return sectionParam;
    return "general";
  }, [searchParams]);

  const selectedItem = useMemo(() => repairBuddyNav.find((item) => item.key === selectedKey) ?? null, [selectedKey]);

  const sectionNode = useMemo(() => {
    switch (selectedKey) {
      case "general":
        return <GeneralSection draft={draft} updateGeneral={(patch) => updateSection("general", patch)} />;
      case "currency":
        return <CurrencySection draft={draft} updateCurrency={(patch) => updateSection("currency", patch)} />;
      case "invoices-reports":
        return <InvoicesReportsSection draft={draft} updateInvoicesReports={(patch) => updateSection("invoicesReports", patch)} />;
      case "job-statuses":
        return (
          <JobStatusesSection
            draft={draft}
            updateJobStatuses={(patch) => updateSection("jobStatuses", patch)}
            setDraft={setDraft}
            isMock={isMock}
          />
        );
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
      case "pages-setup":
        return <PagesSetupSection draft={draft} updatePagesSetup={(patch) => updateSection("pagesSetup", patch)} />;
      case "sms":
        return <SmsSection draft={draft} updateSms={(patch) => updateSection("sms", patch)} />;
      case "taxes":
        return <TaxesSection draft={draft} updateTaxes={(patch) => updateSection("taxes", patch)} isMock={isMock} />;
      case "service-settings":
        return <ServiceSettingsSection draft={draft} updateServiceSettings={(patch) => updateSection("serviceSettings", patch)} />;
      case "time-logs":
        return <TimeLogsSection draft={draft} updateTimeLogs={(patch) => updateSection("timeLogs", patch)} />;
      case "maintenance-reminders":
        return <MaintenanceRemindersSection draft={draft} isMock={isMock} />;
      case "styling-labels":
        return <StylingLabelsSection draft={draft} updateStylingLabels={(patch) => updateSection("stylingLabels", patch)} />;
      case "signature-workflow":
        return <SignatureWorkflowSection draft={draft} updateSignatureWorkflow={(patch) => updateSection("signatureWorkflow", patch)} />;
      case "booking":
        return <BookingSection draft={draft} updateBooking={(patch) => updateSection("booking", patch)} />;
      default:
        return null;
    }
  }, [draft, isMock, selectedKey, setDraft, updateSection]);

  return (
    <div className="space-y-6">
      <Alert variant="warning" title="Mock screens">
        Saving is not available yet.
      </Alert>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-[260px_1fr]">
        <Card>
          <CardHeader>
            <CardTitle>Business Settings</CardTitle>
            <CardDescription>Operational settings for this business.</CardDescription>
          </CardHeader>
          <CardContent className="space-y-1">
            {repairBuddyNav.map((item) => {
              const isActive = item.key === selectedKey;

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
                  {item.label}
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
                <div className="mt-2 text-xs text-zinc-500">
                  Editing is allowed for UX testing, but saving will be added later. ({savingDisabledReason})
                </div>
              </div>
              <Button disabled variant="outline">
                Save
              </Button>
            </div>
          </div>

          {sectionNode}
        </div>
      </div>
    </div>
  );
}
