<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyJob;
use Illuminate\Http\Request;

class RepairBuddyMaintenanceReminderUnsubscribeController extends Controller
{
    public function unsubscribe(Request $request, string $business, int $jobId)
    {
        $job = RepairBuddyJob::query()->whereKey($jobId)->first();

        if (! $job) {
            return response()->json([
                'message' => 'Job not found.',
            ], 404);
        }

        $job->forceFill([
            'maintenance_reminders_opted_out_at' => now(),
        ])->save();

        return response()->json([
            'ok' => true,
            'message' => 'You have been unsubscribed from maintenance reminders for this job.',
        ]);
    }
}
