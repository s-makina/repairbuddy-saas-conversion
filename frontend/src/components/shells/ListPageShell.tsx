"use client";

import React from "react";
import { PageHeader } from "@/components/ui/PageHeader";
import { Alert } from "@/components/ui/Alert";
import { Card, CardContent } from "@/components/ui/Card";

export function ListPageShell({
  title,
  description,
  actions,
  filters,
  loading,
  error,
  empty,
  emptyTitle = "Nothing here yet",
  emptyDescription = "No items to display.",
  children,
}: {
  title: string;
  description?: string;
  actions?: React.ReactNode;
  filters?: React.ReactNode;
  loading?: boolean;
  error?: string | React.ReactNode | null;
  empty?: boolean;
  emptyTitle?: string;
  emptyDescription?: string;
  children?: React.ReactNode;
}) {
  return (
    <div className="space-y-6">
      <PageHeader title={title} description={description} actions={actions} />

      {filters ? <Card className="shadow-none"><CardContent className="pt-5">{filters}</CardContent></Card> : null}

      {error ? (
        <Alert variant="danger" title="Something went wrong">
          {error}
        </Alert>
      ) : null}

      {loading ? <div className="text-sm text-zinc-500">Loading...</div> : null}

      {!loading && !error && empty ? (
        <Card className="shadow-none">
          <CardContent className="pt-5">
            <div className="text-sm font-semibold text-[var(--rb-text)]">{emptyTitle}</div>
            <div className="mt-1 text-sm text-zinc-600">{emptyDescription}</div>
          </CardContent>
        </Card>
      ) : null}

      {!loading && !error && !empty ? children : null}
    </div>
  );
}
