<?php

namespace App\Support;

use App\Models\RepairBuddyEstimate;
use App\Models\RepairBuddyJob;

class StatusTransitionService
{
    /**
     * Allowed estimate status transitions.
     * Key = from status, Value = array of allowed to statuses.
     */
    protected const ESTIMATE_TRANSITIONS = [
        'draft' => ['pending', 'sent', 'approved', 'rejected', 'expired'],
        'pending' => ['sent', 'approved', 'rejected', 'expired'],
        'sent' => ['approved', 'rejected', 'expired'],
        'approved' => [], // Read-only after conversion
        'rejected' => ['pending', 'draft'],
        'expired' => ['pending', 'draft'],
    ];

    /**
     * Allowed job status transitions.
     * Key = from status, Value = array of allowed to statuses.
     */
    protected const JOB_TRANSITIONS = [
        'new' => ['quote', 'inprocess', 'cancelled'],
        'neworder' => ['quote', 'inprocess', 'cancelled'],
        'quote' => ['inprocess', 'cancelled', 'new', 'neworder'],
        'inprocess' => ['inservice', 'ready_complete', 'cancelled'],
        'inservice' => ['ready_complete', 'cancelled'],
        'ready_complete' => ['delivered', 'cancelled'],
        'delivered' => [], // Terminal state
        'cancelled' => [], // Terminal state
    ];

    /**
     * Terminal job statuses (no further transitions allowed).
     */
    protected const JOB_TERMINAL_STATUSES = ['delivered', 'cancelled'];

    /**
     * Check if an estimate can transition to a new status.
     */
    public function canTransitionEstimate(string $fromStatus, string $toStatus): bool
    {
        $from = strtolower(trim($fromStatus));
        $to = strtolower(trim($toStatus));

        if ($from === $to) {
            return true; // Same status is always allowed
        }

        $allowed = self::ESTIMATE_TRANSITIONS[$from] ?? null;

        if ($allowed === null) {
            return false; // Unknown from status
        }

        return in_array($to, $allowed, true);
    }

    /**
     * Check if a job can transition to a new status.
     */
    public function canTransitionJob(string $fromStatus, string $toStatus): bool
    {
        $from = strtolower(trim($fromStatus));
        $to = strtolower(trim($toStatus));

        if ($from === $to) {
            return true; // Same status is always allowed
        }

        $allowed = self::JOB_TRANSITIONS[$from] ?? null;

        if ($allowed === null) {
            return false; // Unknown from status
        }

        return in_array($to, $allowed, true);
    }

    /**
     * Check if an estimate is read-only (converted to job).
     */
    public function isEstimateReadOnly(RepairBuddyEstimate $estimate): bool
    {
        // If converted to a job, it's read-only
        if (is_numeric($estimate->converted_job_id) && (int) $estimate->converted_job_id > 0) {
            return true;
        }

        // If status is approved, it's read-only (should have converted_job_id, but check anyway)
        if (strtolower($estimate->status ?? '') === 'approved') {
            return true;
        }

        return false;
    }

    /**
     * Check if a job is in a terminal state (no further status changes).
     */
    public function isJobTerminal(RepairBuddyJob $job): bool
    {
        $status = strtolower($job->status_slug ?? '');

        return in_array($status, self::JOB_TERMINAL_STATUSES, true);
    }

    /**
     * Get allowed transitions for an estimate status.
     *
     * @return string[]
     */
    public function getAllowedEstimateTransitions(string $fromStatus): array
    {
        $from = strtolower(trim($fromStatus));

        return self::ESTIMATE_TRANSITIONS[$from] ?? [];
    }

    /**
     * Get allowed transitions for a job status.
     *
     * @return string[]
     */
    public function getAllowedJobTransitions(string $fromStatus): array
    {
        $from = strtolower(trim($fromStatus));

        return self::JOB_TRANSITIONS[$from] ?? [];
    }

    /**
     * Validate an estimate status transition and return an error message if invalid.
     */
    public function validateEstimateTransition(RepairBuddyEstimate $estimate, string $newStatus): ?string
    {
        // Check if estimate is read-only
        if ($this->isEstimateReadOnly($estimate)) {
            return 'Estimate is read-only after conversion to job.';
        }

        $currentStatus = strtolower($estimate->status ?? '');
        $targetStatus = strtolower(trim($newStatus));

        // Check if transition is allowed
        if (! $this->canTransitionEstimate($currentStatus, $targetStatus)) {
            $allowed = $this->getAllowedEstimateTransitions($currentStatus);

            if (empty($allowed)) {
                return "Cannot change status from '{$currentStatus}'.";
            }

            return "Cannot change estimate status from '{$currentStatus}' to '{$targetStatus}'. Allowed transitions: " . implode(', ', $allowed);
        }

        return null; // Valid transition
    }

    /**
     * Validate a job status transition and return an error message if invalid.
     */
    public function validateJobTransition(RepairBuddyJob $job, string $newStatus): ?string
    {
        // Check if job is in terminal state
        if ($this->isJobTerminal($job)) {
            $currentStatus = strtolower($job->status_slug ?? '');

            return "Cannot change status from '{$currentStatus}' - job is already completed.";
        }

        $currentStatus = strtolower($job->status_slug ?? '');
        $targetStatus = strtolower(trim($newStatus));

        // Check if transition is allowed
        if (! $this->canTransitionJob($currentStatus, $targetStatus)) {
            $allowed = $this->getAllowedJobTransitions($currentStatus);

            if (empty($allowed)) {
                return "Cannot change status from '{$currentStatus}'.";
            }

            return "Cannot change job status from '{$currentStatus}' to '{$targetStatus}'. Allowed transitions: " . implode(', ', $allowed);
        }

        return null; // Valid transition
    }

    /**
     * Check if a job status is terminal.
     */
    public function isTerminalJobStatus(string $status): bool
    {
        return in_array(strtolower(trim($status)), self::JOB_TERMINAL_STATUSES, true);
    }

    /**
     * Get all valid estimate statuses.
     *
     * @return string[]
     */
    public function getValidEstimateStatuses(): array
    {
        return array_keys(self::ESTIMATE_TRANSITIONS);
    }

    /**
     * Get all valid job statuses.
     *
     * @return string[]
     */
    public function getValidJobStatuses(): array
    {
        return array_keys(self::JOB_TRANSITIONS);
    }
}
