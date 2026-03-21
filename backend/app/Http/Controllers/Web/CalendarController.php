<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyAppointment;
use App\Models\RepairBuddyEstimate;
use App\Models\RepairBuddyJob;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    /**
     * Display the calendar page.
     */
    public function show(Request $request)
    {
        $tenant = TenantContext::tenant();
        $user = $request->user();

        if (! $tenant) {
            abort(400, 'Tenant context is missing.');
        }

        return view('tenant.calendar', [
            'tenant' => $tenant,
            'user' => $user,
            'activeNav' => 'calendar',
            'pageTitle' => 'Calendar',
            'pickup_date_label' => 'Pickup date',
            'delivery_date_label' => 'Delivery date',
            'nextservice_date_label' => 'Next service date',
            'enable_next_service' => true,
            'calendar_events_url' => $tenant->slug
                ? route('tenant.calendar.events', ['business' => $tenant->slug])
                : '#',
        ]);
    }

    /**
     * Fetch calendar events as JSON.
     */
    public function events(Request $request): JsonResponse
    {
        $tenant = TenantContext::tenant();
        $tenantId = TenantContext::tenantId();
        $branchId = BranchContext::branchId();
        $user = $request->user();

        if (! $tenant || ! $tenantId || ! $branchId) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant or branch context missing.',
            ], 400);
        }

        $start = $request->input('start');
        $end = $request->input('end');
        $filter = $request->input('filter', 'all');
        $dateField = $request->input('date_field', 'pickup_date');

        $startDate = is_string($start) ? \Carbon\Carbon::parse($start)->startOfDay() : now()->subMonth()->startOfDay();
        $endDate = is_string($end) ? \Carbon\Carbon::parse($end)->endOfDay() : now()->addMonth()->endOfDay();

        $events = [];
        $totalJobs = 0;
        $totalEstimates = 0;

        $statusColors = [
            'new' => 'success',
            'quote' => 'info',
            'in_process' => 'info',
            'inprocess' => 'info',
            'waiting_parts' => 'warning',
            'ready' => 'warning',
            'completed' => 'secondary',
            'delivered' => 'secondary',
            'cancelled' => 'danger',
        ];

        // Fetch Jobs
        if ($filter === 'all' || $filter === 'jobs' || $filter === 'my_assignments') {
            $jobQuery = RepairBuddyJob::query()
                ->with(['customer'])
                ->where('tenant_id', (int) $tenantId)
                ->where('branch_id', (int) $branchId);

            if ($filter === 'my_assignments' && $user) {
                $jobQuery->where(function ($q) use ($user) {
                    $q->where('assigned_technician_id', $user->id)
                        ->orWhereHas('technicians', fn ($tq) => $tq->where('users.id', $user->id));
                });
            }

            $dateColumn = match ($dateField) {
                'delivery_date' => 'delivery_date',
                'next_service_date' => 'next_service_date',
                'post_date' => 'created_at',
                default => 'pickup_date',
            };

            $jobQuery->whereNotNull($dateColumn)
                ->where($dateColumn, '>=', $startDate->toDateString())
                ->where($dateColumn, '<=', $endDate->toDateString());

            $jobs = $jobQuery->limit(500)->get();
            $totalJobs = $jobs->count();

            foreach ($jobs as $job) {
                $dateValue = $dateColumn === 'created_at' ? $job->created_at : $job->{$dateColumn};
                if (! $dateValue) {
                    continue;
                }

                $statusSlug = $job->status_slug ?? 'new';
                $color = $statusColors[$statusSlug] ?? 'primary';
                $customerName = $job->customer?->name ?? '—';

                $title = "Job #{$job->job_number} - {$customerName}";

                $tooltip = "Case: {$job->case_number} | Customer: {$customerName} | Status: " . ucfirst($statusSlug);
                if ($job->{$dateColumn}) {
                    $tooltip .= " | Date: " . $job->{$dateColumn}->format('M d, Y');
                }

                $events[] = [
                    'title' => $title,
                    'start' => $dateValue->toIso8601String(),
                    'allDay' => true,
                    'url' => route('tenant.jobs.show', ['business' => $tenant->slug, 'jobId' => $job->id]),
                    'classNames' => ["bg-{$color}", 'job-event'],
                    'extendedProps' => [
                        'tooltip' => $tooltip,
                        'status' => ucfirst($statusSlug),
                        'type' => 'job',
                        'id' => $job->id,
                    ],
                ];
            }
        }

        // Fetch Estimates
        if ($filter === 'all' || $filter === 'estimates') {
            $estimateQuery = RepairBuddyEstimate::query()
                ->with(['customer'])
                ->where('tenant_id', (int) $tenantId)
                ->where('branch_id', (int) $branchId);

            $dateColumn = match ($dateField) {
                'delivery_date' => 'delivery_date',
                'post_date' => 'created_at',
                default => 'pickup_date',
            };

            $estimateQuery->whereNotNull($dateColumn)
                ->where($dateColumn, '>=', $startDate->toDateString())
                ->where($dateColumn, '<=', $endDate->toDateString());

            $estimates = $estimateQuery->limit(500)->get();
            $totalEstimates = $estimates->count();

            foreach ($estimates as $estimate) {
                $dateValue = $dateColumn === 'created_at' ? $estimate->created_at : $estimate->{$dateColumn};
                if (! $dateValue) {
                    continue;
                }

                $statusSlug = $estimate->status ?? 'pending';
                $customerName = $estimate->customer?->name ?? '—';

                $title = "Estimate - {$customerName}";

                $tooltip = "Case: {$estimate->case_number} | Customer: {$customerName} | Status: " . ucfirst($statusSlug);

                $events[] = [
                    'title' => $title,
                    'start' => $dateValue->toIso8601String(),
                    'allDay' => true,
                    'url' => route('tenant.estimates.show', ['business' => $tenant->slug, 'estimateId' => $estimate->id]),
                    'classNames' => ['bg-warning', 'estimate-event'],
                    'extendedProps' => [
                        'tooltip' => $tooltip,
                        'status' => ucfirst($statusSlug),
                        'type' => 'estimate',
                        'id' => $estimate->id,
                    ],
                ];
            }
        }

        // Fetch Appointments
        if ($filter === 'all' || $filter === 'appointments') {
            $appointmentQuery = RepairBuddyAppointment::query()
                ->with(['customer', 'appointmentSetting'])
                ->where('tenant_id', (int) $tenantId)
                ->where('branch_id', (int) $branchId)
                ->where('appointment_date', '>=', $startDate->toDateString())
                ->where('appointment_date', '<=', $endDate->toDateString());

            $appointments = $appointmentQuery->limit(500)->get();

            foreach ($appointments as $appointment) {
                $customerName = $appointment->customer?->name ?? '—';
                $title = $appointment->title ?? ($appointment->appointmentSetting?->title ?? 'Appointment');
                $timeDisplay = $appointment->time_slot_start->format('H:i') . ' - ' . $appointment->time_slot_end->format('H:i');

                $statusColorsAppt = [
                    'scheduled' => 'info',
                    'confirmed' => 'success',
                    'completed' => 'secondary',
                    'cancelled' => 'danger',
                    'no_show' => 'danger',
                ];
                $color = $statusColorsAppt[$appointment->status] ?? 'info';

                $tooltip = "{$title} | Customer: {$customerName} | Time: {$timeDisplay} | Status: " . ucfirst($appointment->status);

                $url = '#';
                if ($appointment->job_id) {
                    $url = route('tenant.jobs.show', ['business' => $tenant->slug, 'jobId' => $appointment->job_id]);
                } elseif ($appointment->estimate_id) {
                    $url = route('tenant.estimates.show', ['business' => $tenant->slug, 'estimateId' => $appointment->estimate_id]);
                }

                $events[] = [
                    'title' => "{$title} - {$customerName}",
                    'start' => $appointment->appointment_date->format('Y-m-d') . 'T' . $appointment->time_slot_start->format('H:i:s'),
                    'end' => $appointment->appointment_date->format('Y-m-d') . 'T' . $appointment->time_slot_end->format('H:i:s'),
                    'url' => $url,
                    'classNames' => ["bg-{$color}", 'appointment-event'],
                    'extendedProps' => [
                        'tooltip' => $tooltip,
                        'status' => ucfirst($appointment->status),
                        'type' => 'appointment',
                        'id' => $appointment->id,
                    ],
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $events,
            'stats' => [
                'total_jobs' => $totalJobs,
                'total_estimates' => $totalEstimates,
            ],
        ]);
    }
}
