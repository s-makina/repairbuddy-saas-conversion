<?php

namespace Tests\Feature;

use App\Models\BillingPlan;
use App\Models\BillingPlanVersion;
use App\Models\BillingPrice;
use App\Models\EntitlementDefinition;
use App\Models\PlanEntitlement;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_assign_subscription_and_create_and_issue_invoice(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'status' => 'active',
            'currency' => 'USD',
            'billing_country' => 'EG',
        ]);

        $plan = BillingPlan::query()->create([
            'code' => 'starter',
            'name' => 'Starter',
            'is_active' => true,
        ]);

        $version = BillingPlanVersion::query()->create([
            'billing_plan_id' => $plan->id,
            'version' => 1,
            'status' => 'active',
            'locked_at' => now(),
        ]);

        $price = BillingPrice::query()->create([
            'billing_plan_version_id' => $version->id,
            'currency' => 'USD',
            'interval' => 'month',
            'amount_cents' => 4900,
            'trial_days' => null,
            'is_default' => true,
        ]);

        $defMaxUsers = EntitlementDefinition::query()->create([
            'code' => 'max_users',
            'name' => 'Maximum users',
            'value_type' => 'integer',
        ]);

        PlanEntitlement::query()->create([
            'billing_plan_version_id' => $version->id,
            'entitlement_definition_id' => $defMaxUsers->id,
            'value_json' => 5,
        ]);

        $admin = User::query()->forceCreate([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
            'admin_role' => 'platform_admin',
            'email_verified_at' => now(),
        ]);

        $token = $admin->createToken('api')->plainTextToken;

        $subRes = $this
            ->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/admin/tenants/'.$tenant->id.'/subscriptions', [
                'billing_plan_version_id' => $version->id,
                'billing_price_id' => $price->id,
                'reason' => 'initial',
            ]);

        $subRes->assertStatus(201);
        $subscriptionId = (int) ($subRes->json('subscription.id') ?? 0);
        $this->assertGreaterThan(0, $subscriptionId);

        $invRes = $this
            ->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/admin/tenants/'.$tenant->id.'/invoices', [
                'tenant_subscription_id' => $subscriptionId,
                'reason' => 'invoice',
            ]);

        $invRes->assertStatus(201);
        $invoiceId = (int) ($invRes->json('invoice.id') ?? 0);
        $this->assertGreaterThan(0, $invoiceId);

        $issueRes = $this
            ->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/admin/tenants/'.$tenant->id.'/invoices/'.$invoiceId.'/issue', [
                'reason' => 'issue',
            ]);

        $issueRes->assertStatus(200);
        $issueRes->assertJsonPath('invoice.status', 'issued');

        $paidRes = $this
            ->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/admin/tenants/'.$tenant->id.'/invoices/'.$invoiceId.'/paid', [
                'reason' => 'paid',
            ]);

        $paidRes->assertStatus(200);
        $paidRes->assertJsonPath('invoice.status', 'paid');

        $pdfRes = $this
            ->withHeaders(['Authorization' => 'Bearer '.$token])
            ->get('/api/admin/tenants/'.$tenant->id.'/invoices/'.$invoiceId.'/pdf');

        $pdfRes->assertStatus(200);
        $pdfRes->assertHeader('Content-Type', 'application/pdf');
    }
}
