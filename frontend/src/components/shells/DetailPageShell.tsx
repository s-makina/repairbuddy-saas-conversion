"use client";

import React from "react";
import Link from "next/link";
import { cn } from "@/lib/cn";
import { PageHeader } from "@/components/ui/PageHeader";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/Tabs";

export type DetailTabKey = "overview" | "devices" | "timeline" | "messages" | "financial" | "print";

export function DetailPageShell({
  breadcrumb,
  backHref,
  backLabel = "Back",
  title,
  description,
  actions,
  defaultTab = "overview",
  tabs,
  className,
}: {
  breadcrumb?: React.ReactNode;
  backHref?: string;
  backLabel?: string;
  title: string;
  description?: string;
  actions?: React.ReactNode;
  defaultTab?: DetailTabKey;
  tabs: Partial<Record<DetailTabKey, React.ReactNode>>;
  className?: string;
}) {
  return (
    <div className={cn("space-y-0 space-x-2", className)}>
      <div className="space-y-2">
        {/* {breadcrumb ? <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">{breadcrumb}</div> : null} */}
        {backHref ? (
          <div>
            <Link href={backHref} className="text-sm text-zinc-600 hover:text-[var(--rb-text)]">
              {backLabel}
            </Link>
          </div>
        ) : null}
      </div>

      <PageHeader title={title} description={description} actions={actions} />

      <Tabs defaultValue={defaultTab}>
        <TabsList>
          {tabs.overview !== undefined ? <TabsTrigger value="overview">Overview</TabsTrigger> : null}
          {tabs.devices !== undefined ? <TabsTrigger value="devices">Devices</TabsTrigger> : null}
          {tabs.timeline !== undefined ? <TabsTrigger value="timeline">Timeline</TabsTrigger> : null}
          {tabs.messages !== undefined ? <TabsTrigger value="messages">Messages</TabsTrigger> : null}
          {tabs.financial !== undefined ? <TabsTrigger value="financial">Financial</TabsTrigger> : null}
          {tabs.print !== undefined ? <TabsTrigger value="print">Print</TabsTrigger> : null}
        </TabsList>

        {tabs.overview !== undefined ? <TabsContent value="overview">{tabs.overview}</TabsContent> : null}
        {tabs.devices !== undefined ? <TabsContent value="devices">{tabs.devices}</TabsContent> : null}
        {tabs.timeline !== undefined ? <TabsContent value="timeline">{tabs.timeline}</TabsContent> : null}
        {tabs.messages !== undefined ? <TabsContent value="messages">{tabs.messages}</TabsContent> : null}
        {tabs.financial !== undefined ? <TabsContent value="financial">{tabs.financial}</TabsContent> : null}
        {tabs.print !== undefined ? <TabsContent value="print">{tabs.print}</TabsContent> : null}
      </Tabs>
    </div>
  );
}
