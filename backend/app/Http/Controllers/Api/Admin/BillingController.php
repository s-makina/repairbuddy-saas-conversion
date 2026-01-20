<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillingPlanVersion;
use App\Models\BillingPrice;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Models\Scopes\TenantScope;
use App\Support\Billing\InvoicingService;
use App\Support\PlatformAudit;
use App\Support\SubscriptionService;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function subscriptionsIndex(Request $request, Tenant $tenant)
    {
        TenantContext::set($tenant);

        try {
            $subs = TenantSubscription::query()
                ->with(['planVersion.plan', 'price'])
                ->orderByDesc('id')
                ->get();

            return response()->json([
                'tenant' => $tenant,
                'subscriptions' => $subs,
            ]);
        } finally {
            TenantContext::set(null);
        }
    }

    public function subscriptionsAssign(Request $request, Tenant $tenant)
    {
        $actor = $request->user();

        if (! $actor instanceof User || ! $actor->is_admin) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'billing_plan_version_id' => ['required', 'integer', 'exists:billing_plan_versions,id'],
            'billing_price_id' => ['required', 'integer', 'exists:billing_prices,id'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $planVersion = BillingPlanVersion::query()->findOrFail($validated['billing_plan_version_id']);
        $price = BillingPrice::query()->findOrFail($validated['billing_price_id']);

        TenantContext::set($tenant);

        try {
            $subscription = (new SubscriptionService())->createOrChangeSubscription(
                tenant: $tenant,
                planVersion: $planVersion,
                price: $price,
                actorUserId: $actor->id,
            );
        } finally {
            TenantContext::set(null);
        }

        PlatformAudit::log($request, 'billing.subscription.assigned', $tenant, $validated['reason'] ?? null, [
            'tenant_subscription_id' => $subscription->id,
            'billing_plan_version_id' => $planVersion->id,
            'billing_price_id' => $price->id,
        ]);

        return response()->json([
            'subscription' => $subscription->load(['planVersion.plan', 'price']),
        ], 201);
    }

    public function subscriptionsCancel(Request $request, Tenant $tenant, int $subscription)
    {
        $actor = $request->user();

        if (! $actor instanceof User || ! $actor->is_admin) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'at_period_end' => ['nullable', 'boolean'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        TenantContext::set($tenant);

        try {
            $sub = TenantSubscription::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenant->id)
                ->where('id', $subscription)
                ->firstOrFail();

            $updated = (new SubscriptionService())->cancelSubscription(
                subscription: $sub,
                atPeriodEnd: (bool) ($validated['at_period_end'] ?? true),
                actorUserId: $actor->id,
            );
        } finally {
            TenantContext::set(null);
        }

        PlatformAudit::log($request, 'billing.subscription.canceled', $tenant, $validated['reason'] ?? null, [
            'tenant_subscription_id' => $subscription,
            'at_period_end' => (bool) ($validated['at_period_end'] ?? true),
        ]);

        return response()->json([
            'subscription' => $updated->fresh()->load(['planVersion.plan', 'price']),
        ]);
    }

    public function invoicesIndex(Request $request, Tenant $tenant)
    {
        TenantContext::set($tenant);

        try {
            $validated = $request->validate([
                'status' => ['nullable', 'string', 'max:32'],
                'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
            ]);

            $limit = (int) ($validated['limit'] ?? 50);
            $status = is_string($validated['status'] ?? null) ? trim((string) $validated['status']) : '';

            $q = Invoice::query()->with('lines')->orderByDesc('id');

            if ($status !== '') {
                $q->where('status', $status);
            }

            $invoices = $q->limit($limit)->get();

            return response()->json([
                'tenant' => $tenant,
                'invoices' => $invoices,
            ]);
        } finally {
            TenantContext::set(null);
        }
    }

    public function invoicesCreateFromSubscription(Request $request, Tenant $tenant)
    {
        $actor = $request->user();

        if (! $actor instanceof User || ! $actor->is_admin) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'tenant_subscription_id' => ['required', 'integer', 'exists:tenant_subscriptions,id'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        TenantContext::set($tenant);

        try {
            $subscription = TenantSubscription::query()->with(['planVersion.plan', 'price'])->findOrFail($validated['tenant_subscription_id']);

            if ((int) $subscription->tenant_id !== (int) $tenant->id) {
                return response()->json(['message' => 'Subscription does not belong to tenant.'], 404);
            }

            $invoice = (new InvoicingService())->createDraftFromSubscription($tenant, $subscription);
        } finally {
            TenantContext::set(null);
        }

        PlatformAudit::log($request, 'billing.invoice.draft_created', $tenant, $validated['reason'] ?? null, [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'tenant_subscription_id' => $subscription->id,
        ]);

        return response()->json([
            'invoice' => $invoice,
        ], 201);
    }

    public function invoicesIssue(Request $request, Tenant $tenant, int $invoice)
    {
        $actor = $request->user();

        if (! $actor instanceof User || ! $actor->is_admin) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        TenantContext::set($tenant);

        try {
            $inv = Invoice::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenant->id)
                ->where('id', $invoice)
                ->firstOrFail();

            $updated = (new InvoicingService())->issue($inv);
        } finally {
            TenantContext::set(null);
        }

        PlatformAudit::log($request, 'billing.invoice.issued', $tenant, $validated['reason'] ?? null, [
            'invoice_id' => $invoice,
            'invoice_number' => $updated->invoice_number,
        ]);

        return response()->json([
            'invoice' => $updated->fresh()->load('lines'),
        ]);
    }

    public function invoicesMarkPaid(Request $request, Tenant $tenant, int $invoice)
    {
        $actor = $request->user();

        if (! $actor instanceof User || ! $actor->is_admin) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        TenantContext::set($tenant);

        try {
            $inv = Invoice::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenant->id)
                ->where('id', $invoice)
                ->firstOrFail();

            $updated = (new InvoicingService())->markPaid($inv);
        } finally {
            TenantContext::set(null);
        }

        PlatformAudit::log($request, 'billing.invoice.paid', $tenant, $validated['reason'] ?? null, [
            'invoice_id' => $invoice,
            'invoice_number' => $updated->invoice_number,
        ]);

        return response()->json([
            'invoice' => $updated->fresh()->load('lines'),
        ]);
    }

    public function invoicesDownloadPdf(Request $request, Tenant $tenant, int $invoice)
    {
        $actor = $request->user();

        if (! $actor instanceof User || ! $actor->is_admin) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        TenantContext::set($tenant);

        try {
            $inv = Invoice::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenant->id)
                ->where('id', $invoice)
                ->firstOrFail();

            $pdf = (new InvoicingService())->buildPdf($inv);
        } finally {
            TenantContext::set(null);
        }

        $filename = 'invoice_'.$inv->invoice_number.'.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
