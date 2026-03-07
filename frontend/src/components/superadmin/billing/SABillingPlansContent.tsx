"use client";

import { SATopbar, SAButton } from "../SATopbar";
import {
  Search,
  Plus,
  CreditCard,
  Crown,
  Star,
  CheckCircle,
  Pencil,
  Trash2,
  Loader2,
  AlertCircle,
  ToggleLeft,
  ToggleRight,
} from "lucide-react";
import { useState, useEffect, useCallback } from "react";
import { useRouter } from "next/navigation";
import { getBillingCatalog, deleteBillingPlan, updateBillingPlan } from "@/lib/superadmin";
import type { BillingCatalogPayload } from "@/lib/billing";
import type { BillingPlan, BillingPlanVersion } from "@/lib/types";
import { notify } from "@/lib/notify";

export function SABillingPlansContent() {
  const router = useRouter();
  const [statusFilter, setStatusFilter] = useState("all");
  const [search, setSearch] = useState("");
  const [catalog, setCatalog] = useState<BillingCatalogPayload | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState<number | null>(null);

  const fetchCatalog = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await getBillingCatalog({ includeInactive: true });
      setCatalog(data);
    } catch (e) {
      setError(e instanceof Error ? e.message : "Failed to load billing plans");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { fetchCatalog(); }, [fetchCatalog]);

  const plans = catalog?.billing_plans ?? [];

  const filtered = plans.filter((p) => {
    if (statusFilter === "active" && !p.is_active) return false;
    if (statusFilter === "inactive" && p.is_active) return false;
    if (search.trim()) {
      const q = search.toLowerCase();
      return p.name.toLowerCase().includes(q) || p.code.toLowerCase().includes(q);
    }
    return true;
  });

  const totalPlans = plans.length;
  const activePlans = plans.filter((p) => p.is_active).length;
  const plansWithActiveVersion = plans.filter((p) =>
    p.versions?.some((v) => v.status === "active")
  ).length;

  function getActiveVersion(plan: BillingPlan): BillingPlanVersion | undefined {
    return plan.versions?.find((v) => v.status === "active");
  }

  function getDisplayPrice(plan: BillingPlan): { price: string; interval: string } {
    const ver = getActiveVersion(plan);
    if (!ver?.prices?.length) return { price: "No pricing", interval: "" };
    const defaultPrice = ver.prices.find((p) => p.is_default) ?? ver.prices[0];
    const amount = (defaultPrice.amount_cents / 100).toFixed(2);
    return {
      price: `${defaultPrice.currency} ${amount}`,
      interval: `/ ${defaultPrice.interval_model?.name ?? defaultPrice.interval ?? "period"}`,
    };
  }

  function getEntitlementTexts(plan: BillingPlan): string[] {
    const ver = getActiveVersion(plan) ?? plan.versions?.[0];
    if (!ver?.entitlements?.length) return [];
    return ver.entitlements.map((e) => {
      const name = e.definition?.name ?? `Entitlement #${e.entitlement_definition_id}`;
      const val = e.value_json;
      if (typeof val === "boolean") return val ? name : `No ${name}`;
      if (typeof val === "number") return `${name}: ${val}`;
      return name;
    });
  }

  async function handleToggleActive(plan: BillingPlan) {
    if (busy) return;
    setBusy(plan.id);
    try {
      await updateBillingPlan({
        planId: plan.id,
        name: plan.name,
        code: plan.code,
        description: plan.description ?? undefined,
        isActive: !plan.is_active,
        reason: plan.is_active ? "Deactivated from plans list" : "Activated from plans list",
      });
      notify.success(`Plan "${plan.name}" ${plan.is_active ? "deactivated" : "activated"}`);
      await fetchCatalog();
    } catch (e) {
      notify.error(e instanceof Error ? e.message : "Failed to update plan");
    } finally {
      setBusy(null);
    }
  }

  async function handleDelete(plan: BillingPlan) {
    if (busy) return;
    if (!confirm(`Delete plan "${plan.name}"? This cannot be undone.`)) return;
    setBusy(plan.id);
    try {
      await deleteBillingPlan({ planId: plan.id, reason: "Deleted from plans list" });
      notify.success(`Plan "${plan.name}" deleted`);
      await fetchCatalog();
    } catch (e) {
      notify.error(e instanceof Error ? e.message : "Failed to delete plan");
    } finally {
      setBusy(null);
    }
  }

  const summaryCards = [
    {
      label: "Total Plans",
      value: totalPlans,
      bg: "var(--sa-orange-bg)",
      color: "var(--sa-orange)",
      icon: <CreditCard />,
    },
    {
      label: "Active Plans",
      value: activePlans,
      bg: "var(--sa-green-bg)",
      color: "var(--sa-green)",
      icon: <Crown />,
    },
    {
      label: "With Active Version",
      value: plansWithActiveVersion,
      bg: "#fef3c7",
      color: "#d97706",
      icon: <Star />,
    },
  ];

  return (
    <>
      <SATopbar
        breadcrumb="Billing & Subscriptions"
        title="Billing Plans"
        actions={
          <SAButton variant="primary" icon={<Plus />} onClick={() => router.push("/superadmin/billing/builder")}>
            Create Plan
          </SAButton>
        }
      />

      <div className="sa-content">
        {/* Summary row */}
        <div className="sa-summary-row">
          {summaryCards.map((c) => (
            <div className="sa-scard" key={c.label}>
              <div className="sa-scard-icon" style={{ background: c.bg, color: c.color }}>
                {c.icon}
              </div>
              <div>
                <div className="sa-sc-val">{c.value}</div>
                <div className="sa-sc-lbl">{c.label}</div>
              </div>
            </div>
          ))}
        </div>

        {/* Filter bar */}
        <div className="sa-filter-bar" style={{ marginTop: 20 }}>
          <div className="sa-search-wrap">
            <Search />
            <input
              placeholder="Search plans..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
            />
          </div>
          <select value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)}>
            <option value="all">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>

        {/* Loading state */}
        {loading && (
          <div style={{ textAlign: "center", padding: 60 }}>
            <Loader2 className="sa-spin" style={{ width: 32, height: 32, color: "var(--sa-orange)" }} />
            <div style={{ marginTop: 12, color: "var(--sa-text-muted)", fontSize: 13 }}>Loading plans...</div>
          </div>
        )}

        {/* Error state */}
        {error && !loading && (
          <div className="sa-panel" style={{ padding: 32, textAlign: "center", color: "#dc2626" }}>
            <AlertCircle style={{ width: 28, height: 28, margin: "0 auto 8px" }} />
            <div>{error}</div>
            <SAButton variant="outline" onClick={fetchCatalog} style={{ marginTop: 12 }}>Retry</SAButton>
          </div>
        )}

        {/* Plan cards grid */}
        {!loading && !error && (
          <div className="sa-plan-grid" style={{ marginTop: 20 }}>
            {filtered.length === 0 && (
              <div className="sa-panel" style={{ padding: 32, textAlign: "center", color: "var(--sa-text-muted)", gridColumn: "1 / -1" }}>
                No plans found{search || statusFilter !== "all" ? " matching your filters" : ""}.
              </div>
            )}
            {filtered.map((p) => {
              const { price, interval } = getDisplayPrice(p);
              const entitlements = getEntitlementTexts(p);
              const activeVersion = getActiveVersion(p);
              const isBusy = busy === p.id;

              return (
                <div
                  className={`sa-plan-card${activeVersion ? " featured" : ""}`}
                  key={p.id}
                  style={isBusy ? { opacity: 0.6, pointerEvents: "none" } : undefined}
                >
                  <div className="sa-pc-header">
                    <div>
                      <div className="sa-pc-name">{p.name}</div>
                      <div className="sa-pc-code">{p.code}</div>
                    </div>
                    <span className={`sa-pc-status ${p.is_active ? "sa-pc-active" : "sa-pc-inactive"}`}>
                      {p.is_active ? "Active" : "Inactive"}
                    </span>
                  </div>

                  <div className="sa-pc-body">
                    <div className="sa-pc-price">
                      {price}
                      {interval && <span>{interval}</span>}
                    </div>
                    {p.description && <div className="sa-pc-desc">{p.description}</div>}
                    {activeVersion && (
                      <div style={{ fontSize: 11, color: "var(--sa-text-muted)", marginBottom: 8 }}>
                        Version {activeVersion.version} &middot; Active
                      </div>
                    )}
                    <div className="sa-pc-features">
                      {entitlements.slice(0, 5).map((text, i) => (
                        <div className="sa-pc-feat" key={i}>
                          <CheckCircle />
                          {text}
                        </div>
                      ))}
                      {entitlements.length > 5 && (
                        <div className="sa-pc-feat" style={{ color: "var(--sa-text-muted)" }}>
                          +{entitlements.length - 5} more
                        </div>
                      )}
                      {entitlements.length === 0 && (
                        <div style={{ fontSize: 12, color: "var(--sa-text-muted)", fontStyle: "italic" }}>
                          No entitlements configured
                        </div>
                      )}
                    </div>
                  </div>

                  <div className="sa-pc-footer">
                    <SAButton
                      variant="outline"
                      icon={<Pencil />}
                      onClick={() => router.push(`/superadmin/billing/builder?planId=${p.id}`)}
                    >
                      Edit
                    </SAButton>
                    <SAButton
                      variant="ghost"
                      icon={p.is_active ? <ToggleRight /> : <ToggleLeft />}
                      onClick={() => handleToggleActive(p)}
                    >
                      {p.is_active ? "Deactivate" : "Activate"}
                    </SAButton>
                    <SAButton
                      variant="ghost"
                      icon={<Trash2 />}
                      onClick={() => handleDelete(p)}
                      style={{ color: "#dc2626" }}
                    >
                      Delete
                    </SAButton>
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </div>
    </>
  );
}
