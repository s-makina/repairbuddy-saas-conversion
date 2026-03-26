<?php

namespace App\Services;

use App\Models\RepairBuddyEstimate;
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
    /* ────────────────────────────────────────────────────────────────
     *  JOB SIGNATURE METHODS
     * ──────────────────────────────────────────────────────────────── */

    /**
     * Generate a new signature request for a job.
     */
    public function generateRequestForJob(
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
     * Send signature request notification for a job.
     */
    public function sendSignatureNotificationForJob(
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

        $signatureUrl = $signatureRequest->getSignatureUrl($tenant->slug);

        // Replace keywords in templates
        $replacements = [
            '{{signature_url}}'           => $signatureUrl,
            '{{pickup_signature_url}}'    => $signatureUrl,
            '{{delivery_signature_url}}'  => $signatureUrl,
            '{{job_id}}'                  => $job->job_number ?? $job->id,
            '{{case_number}}'             => $job->case_number ?? '',
            '{{customer_full_name}}'      => $customer->name ?? '',
            '{{customer_device_label}}'   => $this->getDeviceLabelForJob($job),
            '{{order_invoice_details}}'   => "Job #{$job->case_number}",
        ];

        if (! empty($emailTemplate)) {
            $emailBody = str_replace(array_keys($replacements), array_values($replacements), $emailTemplate);
        } else {
            $emailBody = $this->getDefaultEmailBodyForJob($signatureRequest, $job, $customer, $signatureUrl, $tenant);
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
     * Check if a signature request should be automatically triggered when job enters a status.
     */
    public function checkAutoTriggerForJob(Tenant $tenant, RepairBuddyJob $job, string $newStatus): void
    {
        $store = new TenantSettingsStore($tenant);
        $settings = $store->get('signature', []);

        // Check pickup
        if (! empty($settings['pickup_enabled']) && ($settings['pickup_trigger_status'] ?? '') === $newStatus) {
            $request = $this->generateRequestForJob($tenant, $job, 'pickup', 'Pickup Signature');
            $this->sendSignatureNotificationForJob($tenant, $job, $request);
        }

        // Check delivery
        if (! empty($settings['delivery_enabled']) && ($settings['delivery_trigger_status'] ?? '') === $newStatus) {
            $request = $this->generateRequestForJob($tenant, $job, 'delivery', 'Delivery Signature');
            $this->sendSignatureNotificationForJob($tenant, $job, $request);
        }
    }

    /* ────────────────────────────────────────────────────────────────
     *  ESTIMATE SIGNATURE METHODS
     * ──────────────────────────────────────────────────────────────── */

    /**
     * Generate a new signature request for an estimate.
     * Supported types: approval, pickup, delivery, custom
     */
    public function generateRequestForEstimate(
        Tenant $tenant,
        RepairBuddyEstimate $estimate,
        string $signatureType,
        string $signatureLabel,
        ?User $generatedBy = null,
    ): RepairBuddySignatureRequest {
        // Check for existing pending request of same type
        $existing = RepairBuddySignatureRequest::query()
            ->where('estimate_id', $estimate->id)
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
            'branch_id'         => $estimate->branch_id,
            'estimate_id'       => $estimate->id,
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
            'entity_type'  => 'estimate',
            'entity_id'    => $estimate->id,
            'event_type'   => 'signature_request_generated',
            'actor_id'     => $generatedBy?->id,
            'payload_json' => [
                'title'   => ucfirst($signatureType) . ' signature request generated',
                'message' => "Signature request '{$signatureLabel}' generated for estimate #{$estimate->case_number}.",
            ],
        ]);

        return $request;
    }

    /**
     * Send signature request notification for an estimate.
     */
    public function sendSignatureNotificationForEstimate(
        Tenant $tenant,
        RepairBuddyEstimate $estimate,
        RepairBuddySignatureRequest $signatureRequest,
        ?User $triggeredBy = null,
    ): void {
        $customer = $estimate->customer;
        if (! $customer || ! $customer->email) {
            return;
        }

        $store = new TenantSettingsStore($tenant);
        $signatureSettings = $store->get('estimate_signature', []);
        $type = $signatureRequest->signature_type;

        // Get email subject and template based on type
        $emailSubject = $signatureSettings["{$type}_email_subject"] ?? "Signature Required: {$signatureRequest->signature_label}";
        $emailTemplate = $signatureSettings["{$type}_email_template"] ?? '';

        $signatureUrl = $signatureRequest->getSignatureUrl($tenant->slug);

        // Replace keywords in templates
        $replacements = [
            '{{signature_url}}'           => $signatureUrl,
            '{{approval_signature_url}}'  => $signatureUrl,
            '{{pickup_signature_url}}'    => $signatureUrl,
            '{{delivery_signature_url}}'  => $signatureUrl,
            '{{estimate_id}}'             => $estimate->id,
            '{{case_number}}'             => $estimate->case_number ?? '',
            '{{customer_full_name}}'      => $customer->name ?? '',
            '{{customer_device_label}}'   => $this->getDeviceLabelForEstimate($estimate),
            '{{order_invoice_details}}'   => "Estimate #{$estimate->case_number}",
        ];

        if (! empty($emailTemplate)) {
            $emailBody = str_replace(array_keys($replacements), array_values($replacements), $emailTemplate);
        } else {
            $emailBody = $this->getDefaultEmailBodyForEstimate($signatureRequest, $estimate, $customer, $signatureUrl, $tenant);
        }

        $emailSubject = str_replace(array_keys($replacements), array_values($replacements), $emailSubject);

        // Send notification
        $customer->notify(new SignatureRequestNotification(
            subject: $emailSubject,
            body: $emailBody,
            signatureUrl: $signatureUrl,
            job: null,
            estimate: $estimate,
            signatureRequest: $signatureRequest,
            tenant: $tenant,
        ));

        // Log event
        RepairBuddyEvent::create([
            'tenant_id'    => $tenant->id,
            'entity_type'  => 'estimate',
            'entity_id'    => $estimate->id,
            'event_type'   => 'signature_request_sent',
            'actor_id'     => $triggeredBy?->id,
            'payload_json' => [
                'title'   => ucfirst($type) . ' signature request sent',
                'message' => "Signature request email sent to {$customer->email}.",
            ],
        ]);
    }

    /**
     * Check if a signature request should be automatically triggered when estimate enters a status.
     */
    public function checkAutoTriggerForEstimate(Tenant $tenant, RepairBuddyEstimate $estimate, string $newStatus): void
    {
        $store = new TenantSettingsStore($tenant);
        $settings = $store->get('estimate_signature', []);

        // Check approval
        if (! empty($settings['approval_enabled']) && ($settings['approval_trigger_status'] ?? '') === $newStatus) {
            $request = $this->generateRequestForEstimate($tenant, $estimate, 'approval', 'Approval Signature');
            $this->sendSignatureNotificationForEstimate($tenant, $estimate, $request);
        }

        // Check pickup
        if (! empty($settings['pickup_enabled']) && ($settings['pickup_trigger_status'] ?? '') === $newStatus) {
            $request = $this->generateRequestForEstimate($tenant, $estimate, 'pickup', 'Pickup Signature');
            $this->sendSignatureNotificationForEstimate($tenant, $estimate, $request);
        }

        // Check delivery
        if (! empty($settings['delivery_enabled']) && ($settings['delivery_trigger_status'] ?? '') === $newStatus) {
            $request = $this->generateRequestForEstimate($tenant, $estimate, 'delivery', 'Delivery Signature');
            $this->sendSignatureNotificationForEstimate($tenant, $estimate, $request);
        }
    }

    /* ────────────────────────────────────────────────────────────────
     *  COMMON SIGNATURE COMPLETION
     * ──────────────────────────────────────────────────────────────── */

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

        $tenant = Tenant::find($signatureRequest->tenant_id);

        // Handle based on entity type
        if ($signatureRequest->isForJob()) {
            $this->completeJobSignature($signatureRequest, $filePath, $ip, $tenant);
        } elseif ($signatureRequest->isForEstimate()) {
            $this->completeEstimateSignature($signatureRequest, $filePath, $ip, $tenant);
        }

        return $signatureRequest->fresh();
    }

    /**
     * Complete signature for a job.
     */
    private function completeJobSignature(
        RepairBuddySignatureRequest $signatureRequest,
        string $filePath,
        string $ip,
        ?Tenant $tenant,
    ): void {
        $job = $signatureRequest->job;

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
    }

    /**
     * Complete signature for an estimate.
     */
    private function completeEstimateSignature(
        RepairBuddySignatureRequest $signatureRequest,
        string $filePath,
        string $ip,
        ?Tenant $tenant,
    ): void {
        $estimate = $signatureRequest->estimate;

        // Log event
        RepairBuddyEvent::create([
            'tenant_id'    => $signatureRequest->tenant_id,
            'entity_type'  => 'estimate',
            'entity_id'    => $signatureRequest->estimate_id,
            'event_type'   => 'signature_completed',
            'actor_id'     => null,
            'payload_json' => [
                'title'   => 'Signature received: ' . $signatureRequest->signature_label,
                'message' => "Verified signature submitted from IP {$ip}.",
                'file_path' => $filePath,
            ],
        ]);

        // Change estimate status if configured
        $this->updateEstimateStatusAfterSignature($signatureRequest, $estimate, $tenant);
    }

    /* ────────────────────────────────────────────────────────────────
     *  STATUS UPDATE METHODS
     * ──────────────────────────────────────────────────────────────── */

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

    /**
     * Update estimate status after signature is submitted (per settings).
     */
    private function updateEstimateStatusAfterSignature(
        RepairBuddySignatureRequest $signatureRequest,
        RepairBuddyEstimate $estimate,
        ?Tenant $tenant,
    ): void {
        if (! $tenant) {
            return;
        }

        $store = new TenantSettingsStore($tenant);
        $settings = $store->get('estimate_signature', []);

        $type = $signatureRequest->signature_type;
        $newStatus = $settings["{$type}_after_status"] ?? '';

        if (empty($newStatus) || $newStatus === $estimate->status) {
            return;
        }

        $oldStatus = $estimate->status;
        $estimate->update(['status' => $newStatus]);

        // Set approved_at if status is approved
        if ($newStatus === 'approved') {
            $estimate->update(['approved_at' => now()]);
        }

        RepairBuddyEvent::create([
            'tenant_id'    => $tenant->id,
            'entity_type'  => 'estimate',
            'entity_id'    => $estimate->id,
            'event_type'   => 'status_changed',
            'actor_id'     => null,
            'payload_json' => [
                'title'   => 'Status changed after signature',
                'message' => "Estimate status changed from '{$oldStatus}' to '{$newStatus}' after {$type} signature submission.",
            ],
        ]);
    }

    /* ────────────────────────────────────────────────────────────────
     *  HELPER METHODS
     * ──────────────────────────────────────────────────────────────── */

    private function getDeviceLabelForJob(RepairBuddyJob $job): string
    {
        $device = $job->jobDevices()->with('customerDevice.device')->first();
        if ($device && $device->customerDevice && $device->customerDevice->device) {
            return $device->customerDevice->device->name ?? '';
        }
        return '';
    }

    private function getDeviceLabelForEstimate(RepairBuddyEstimate $estimate): string
    {
        $device = $estimate->devices()->with('customerDevice.device')->first();
        if ($device && $device->customerDevice && $device->customerDevice->device) {
            return $device->customerDevice->device->name ?? '';
        }
        return '';
    }

    private function getDefaultEmailBodyForJob(
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

    private function getDefaultEmailBodyForEstimate(
        RepairBuddySignatureRequest $signatureRequest,
        RepairBuddyEstimate $estimate,
        User $customer,
        string $signatureUrl,
        Tenant $tenant,
    ): string {
        $type = ucfirst($signatureRequest->signature_type);
        $businessName = $tenant->name ?? 'RepairBuddy';

        $actionText = match ($signatureRequest->signature_type) {
            'approval'  => 'approve this estimate',
            'pickup'   => 'confirm pickup of your device',
            'delivery' => 'confirm delivery of your device',
            default    => 'complete the signature request',
        };

        return "Hello {$customer->name},\n\n"
            . "Please sign to {$actionText}.\n\n"
            . "Estimate ID: #{$estimate->id}\n"
            . "Case Number: {$estimate->case_number}\n\n"
            . "Please click the link below to sign:\n"
            . "{$signatureUrl}\n\n"
            . "Thank you,\n"
            . $businessName;
    }

    /* ────────────────────────────────────────────────────────────────
     *  LEGACY COMPATIBILITY METHODS
     * ──────────────────────────────────────────────────────────────── */

    /**
     * Generate a new signature request for a job (legacy method).
     * @deprecated Use generateRequestForJob instead
     */
    public function generateRequest(
        Tenant $tenant,
        RepairBuddyJob $job,
        string $signatureType,
        string $signatureLabel,
        ?User $generatedBy = null,
    ): RepairBuddySignatureRequest {
        return $this->generateRequestForJob($tenant, $job, $signatureType, $signatureLabel, $generatedBy);
    }

    /**
     * Send signature request notification (legacy method).
     * @deprecated Use sendSignatureNotificationForJob instead
     */
    public function sendSignatureNotification(
        Tenant $tenant,
        RepairBuddyJob $job,
        RepairBuddySignatureRequest $signatureRequest,
        ?User $triggeredBy = null,
    ): void {
        $this->sendSignatureNotificationForJob($tenant, $job, $signatureRequest, $triggeredBy);
    }

    /**
     * Check auto trigger (legacy method).
     * @deprecated Use checkAutoTriggerForJob instead
     */
    public function checkAutoTrigger(Tenant $tenant, RepairBuddyJob $job, string $newStatus): void
    {
        $this->checkAutoTriggerForJob($tenant, $job, $newStatus);
    }
}