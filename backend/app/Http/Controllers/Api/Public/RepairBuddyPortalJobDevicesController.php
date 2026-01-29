<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyJob;
use App\Models\RepairBuddyJobDevice;
use Illuminate\Http\Request;

class RepairBuddyPortalJobDevicesController extends Controller
{
    public function index(Request $request, string $business)
    {
        $validated = $request->validate([
            'caseNumber' => ['required', 'string', 'max:64'],
        ]);

        $caseNumber = trim((string) $validated['caseNumber']);

        $job = RepairBuddyJob::query()->where('case_number', $caseNumber)->first();

        if (! $job) {
            return response()->json([
                'message' => 'Ticket not found.',
            ], 404);
        }

        $items = RepairBuddyJobDevice::query()
            ->where('job_id', $job->id)
            ->orderBy('id', 'desc')
            ->limit(200)
            ->get();

        return response()->json([
            'devices' => $items->map(function (RepairBuddyJobDevice $jd) {
                return [
                    'id' => $jd->id,
                    'customer_device_id' => $jd->customer_device_id,
                    'label' => $jd->label_snapshot,
                    'serial' => $jd->serial_snapshot,
                    'notes' => $jd->notes_snapshot,
                ];
            }),
        ]);
    }
}
