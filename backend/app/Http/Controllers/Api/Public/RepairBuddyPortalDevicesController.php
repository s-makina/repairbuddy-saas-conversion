<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyCustomerDevice;
use App\Models\RepairBuddyJob;
use Illuminate\Http\Request;

class RepairBuddyPortalDevicesController extends Controller
{
    public function index(Request $request, string $business)
    {
        $validated = $request->validate([
            'caseNumber' => ['required', 'string', 'max:64'],
        ]);

        $caseNumber = trim((string) $validated['caseNumber']);

        $job = RepairBuddyJob::query()
            ->where('case_number', $caseNumber)
            ->first();

        if (! $job) {
            return response()->json([
                'message' => 'Ticket not found.',
            ], 404);
        }

        $customerId = is_numeric($job->customer_id) ? (int) $job->customer_id : null;

        if (! $customerId) {
            return response()->json([
                'customer_devices' => [],
            ]);
        }

        $devices = RepairBuddyCustomerDevice::query()
            ->where('customer_id', $customerId)
            ->orderBy('id', 'desc')
            ->limit(200)
            ->get();

        return response()->json([
            'customer_devices' => $devices->map(function (RepairBuddyCustomerDevice $d) {
                return [
                    'id' => $d->id,
                    'customer_id' => $d->customer_id,
                    'device_id' => $d->device_id,
                    'label' => $d->label,
                    'serial' => $d->serial,
                    'notes' => $d->notes,
                ];
            }),
        ]);
    }
}
