<?php

namespace App\Services;

use App\Models\RepairBuddyEvent;
use App\Models\RepairBuddyJob;
use App\Models\RepairBuddyJobExtraItem;
use App\Models\RepairBuddySignatureRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\SignatureRequestNotification;
use App\Services\TenantSettings\TenantSettingsStore;
use Illuminate\Support\Str;

class SignatureWorkflowService
{
    /**
     * Generate a new signature request for a job.
     */
    public function generateRequest(
        Tenant $tenant,
        RepairBuddyJob $job,
        string $signatureType,
        string $signatureLabel,
        ?User $generatedBy = null,
    ): RepairBuddySignatureRequest {
        // Check for existing pending request of same type
        $existing = RepairBuddySignatureRequest::query()
            ->where('job_id', $job->id)
            ->where('signature_type', $signatureType)
            ->where('status', 'pending')
            ->first();

        if ($existing && ! $existing->isExpired()) {
            return $existing;
        }

        // Mark the old one expired if it existed
        if ($existing) {
            $existing->update(['status' => 'expired']);
        }

        $verificationCode = Str::random(32);
        $expiresAt = now()->addDays(7);

        $request = RepairBuddySignatureRequest::create([
            'tenant_id'         => $tenant->id,
            'branch_id'         => $job->branch_id,
            'job_id'            => $job->id,
            'signature_type'    => $signatureType,
            'signature_label'   => $signatureLabel,
            'verification_code' => $verificationCode,
            'generated_at'      => now(),
            'expires_at'        => $expiresAt,
            'status'            => 'pending',
            'generated_by'      => $generatedBy?->id,
        ]);

        // Log event
        RepairBuddyEvent::create([
            'tenant_id'    => $tenant->id,
            'entity_type'  => 'job',
            'entity_id'    => $job->id,
            'event_type'   => 'signature_request_generated',
            'actor_id'     => $generatedBy?->id,
            'payload_json' => [
                'title'   => ucfirst($signatureType) . ' signature request generated',
                'message' => "Signature request '{$signatureLabel}' generated for job #{$job->case_number}.",
            ],
        ]);

        return $request;
    }

    /**
     * Send signature request notification to the customer via email/SMS.
     */
    public function sendSignatureNotification(
        Tenant $tenant,
        RepairBuddyJob $job,
        RepairBuddySignatureRequest $signatureRequest,
        ?User $triggeredBy = null,
    ): void {
        $customer = $job->customer;
        if (! $customer || ! $customer->email) {
            return;
        }

        $store = new TenantSettingsStore($tenant);
        $signatureSettings = $store->get('signature', []);
        $type = $signatureRequest->signature_type;

        // Get email subject and template based on type
        $emailSubject = $signatureSettings["{$type}_email_subject"] ?? "Signature Required: {$signatureRequest->signature_label}";
        $emailTemplate = $signatureSettings["{$type}_email_template"] ?? '';
        $smsText = $signatureSettings["{$type}_sms_text"] ?? '';

        $signatureUrl = $signatureRequest->getSignatureUrl($tenant->slug);

        // Replace keywords in templates
        $replacements = [
            '{{signature_url}}'           => $signatureUrl,
            '{{pickup_signature_url}}'    => $signatureUrl,
            '{{delivery_signature_url}}'  => $signatureUrl,
            '{{job_id}}'                  => $job->job_number ?? $job->id,
            '{{case_number}}'             => $job->case_number ?? '',
            '{{customer_full_name}}'      => $customer->name ?? '',
            '{{customer_device_label}}'   => $this->getDeviceLabel($job),
            '{{order_invoice_details}}'   => "Job #{$job->case_number}",
        ];

        if (! empty($emailTemplate)) {
            $emailBody = str_replace(array_keys($replacements), array_values($replacements), $emailTemplate);
        } else {
            $emailBody = $this->getDefaultEmailBody($signatureRequest, $job, $customer, $signatureUrl, $tenant);
        }

        $emailSubject = str_replace(array_keys($replacements), array_values($replacements), $emailSubject);

        // Send notification
        $customer->notify(new SignatureRequestNotification(
            subject: $emailSubject,
            body: $emailBody,
            signatureUrl: $signatureUrl,
            job: $job,
            signatureRequest: $signatureRequest,
            tenant: $tenant,
        ));

        // Log event
        RepairBuddyEvent::create([
            'tenant_id'    => $tenant->id,
            'entity_type'  => 'job',
            'entity_id'    => $job->id,
            'event_type'   => 'signature_request_sent',
            'actor_id'     => $triggeredBy?->id,
            'payload_json' => [
                'title'   => ucfirst($type) . ' signature request sent',
                'message' => "Signature request email sent to {$customer->email}.",
            ],
        ]);
    }

    /**
     * Complete a signature submission.
     */
    public function completeSignature(
        RepairBuddySignatureRequest $signatureRequest,
        string $filePath,
        string $ip,
        string $userAgent,
    ): RepairBuddySignatureRequest {
        $signatureRequest->update([
            'status'               => 'completed',
            'completed_at'         => now(),
            'completed_ip'         => $ip,
            'completed_user_agent' => Str::limit($userAgent, 255),
            'signature_file_path'  => $filePath,
        ]);

        $job = $signatureRequest->job;
        $tenant = Tenant::find($signatureRequest->tenant_id);

        // Save as extra item on the job (like the plugin does)
        RepairBuddyJobExtraItem::create([
            'tenant_id'   => $signatureRequest->tenant_id,
            'branch_id'   => $signatureRequest->branch_id,
            'job_id'      => $signatureRequest->job_id,
            'occurred_at' => now(),
            'label'       => $signatureRequest->signature_label,
            'data_text'   => $filePath,
            'description' => "Customer signature from IP: {$ip}",
            'item_type'   => 'signature',
            'visibility'  => 'public',
            'meta_json'   => [
                'signature_type'     => $signatureRequest->signature_type,
                'verification_code'  => $signatureRequest->verification_code,
                'completed_at'       => now()->toISOString(),
                'completed_ip'       => $ip,
            ],
        ]);

        // Log event
        RepairBuddyEvent::create([
            'tenant_id'    => $signatureRequest->tenant_id,
            'entity_type'  => 'job',
            'entity_id'    => $signatureRequest->job_id,
            'event_type'   => 'signature_completed',
            'actor_id'     => null,
            'payload_json' => [
                'title'   => 'Signature received: ' . $signatureRequest->signature_label,
                'message' => "Verified signature submitted from IP {$ip}.",
            ],
        ]);

        // Change job status if configured
        $this->updateJobStatusAfterSignature($signatureRequest, $job, $tenant);

        return $signatureRequest->fresh();
    }

    /**
     * Check if a signature request should be automatically triggered when job enters a status.
     */
    public function checkAutoTrigger(Tenant $tenant, RepairBuddyJob $job, string $newStatus): void
    {
        $store = new TenantSettingsStore($tenant);
        $settings = $store->get('signature', []);

        // Check pickup
        if (! empty($settings['pickup_enabled']) && ($settings['pickup_trigger_status'] ?? '') === $newStatus) {
            $request = $this->generateRequest($tenant, $job, 'pickup', 'Pickup Signature');
            $this->sendSignatureNotification($tenant, $job, $request);
        }

        // Check delivery
        if (! empty($settings['delivery_enabled']) && ($settings['delivery_trigger_status'] ?? '') === $newStatus) {
            $request = $this->generateRequest($tenant, $job, 'delivery', 'Delivery Signature');
            $this->sendSignatureNotification($tenant, $job, $request);
        }
    }

    /**
     * Update job status after signature is submitted (per settings).
     */
    private function updateJobStatusAfterSignature(
        RepairBuddySignatureRequest $signatureRequest,
        RepairBuddyJob $job,
        ?Tenant $tenant,
    ): void {
        if (! $tenant) {
            return;
        }

        $store = new TenantSettingsStore($tenant);
        $settings = $store->get('signature', []);

        $type = $signatureRequest->signature_type;
        $newStatus = $settings["{$type}_after_status"] ?? '';

        if (empty($newStatus) || $newStatus === $job->status_slug) {
            return;
        }

        $oldStatus = $job->status_slug;
        $job->update(['status_slug' => $newStatus]);

        RepairBuddyEvent::create([
            'tenant_id'    => $tenant->id,
            'entity_type'  => 'job',
            'entity_id'    => $job->id,
            'event_type'   => 'status_changed',
            'actor_id'     => null,
            'payload_json' => [
                'title'   => 'Status changed after signature',
                'message' => "Job status changed from '{$oldStatus}' to '{$newStatus}' after {$type} signature submission.",
            ],
        ]);
    }

    private function getDeviceLabel(RepairBuddyJob $job): string
    {
        $device = $job->jobDevices()->with('customerDevice.device')->first();
        if ($device && $device->customerDevice && $device->customerDevice->device) {
            return $device->customerDevice->device->name ?? '';
        }
        return $job->title ?? '';
    }

    private function getDefaultEmailBody(
        RepairBuddySignatureRequest $signatureRequest,
        RepairBuddyJob $job,
        User $customer,
        string $signatureUrl,
        Tenant $tenant,
    ): string {
        $type = ucfirst($signatureRequest->signature_type);
        $businessName = $tenant->name ?? 'RepairBuddy';

        return "Hello {$customer->name},\n\n"
            . "Please sign to authorize the {$type} of your device.\n\n"
            . "Job ID: " . ($job->job_number ?? $job->id) . "\n"
            . "Case Number: {$job->case_number}\n\n"
            . "Please click the link below to sign:\n"
            . "{$signatureUrl}\n\n"
            . "Thank you,\n"
            . $businessName;
    }
}
