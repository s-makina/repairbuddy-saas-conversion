<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyCustomerDevice;
use App\Models\RepairBuddyDevice;
use App\Models\RepairBuddyTimeLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class CustomerDevicesController extends Controller
{
    public function show(Request $request, string $business, int $id): JsonResponse
    {
        $cd = RepairBuddyCustomerDevice::query()
            ->with(['customer', 'device.brand', 'device.type', 'jobDevices.job.technicians'])
            ->whereKey($id)
            ->first();

        if (! $cd) {
            return response()->json([
                'message' => 'Customer device not found.',
            ], 404);
        }

        // Get latest job info
        $latestJobDevice = $cd->jobDevices->sortByDesc('id')->first();
        $latestJob = null;
        if ($latestJobDevice && $latestJobDevice->job) {
            $job = $latestJobDevice->job;
            $latestJob = [
                'id' => $job->id,
                'case_number' => $job->case_number,
                'title' => $job->title,
                'status_slug' => $job->status_slug,
                'technicians' => $job->technicians->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->values(),
            ];
        }

        // Get time logs from all jobs associated with this device
        $jobIds = $cd->jobDevices->pluck('job_id')->unique()->filter();
        $timeLogs = RepairBuddyTimeLog::query()
            ->with('technician')
            ->whereIn('job_id', $jobIds)
            ->orderBy('start_time', 'desc')
            ->limit(20)
            ->get();

        $timeLogsData = $timeLogs->map(function ($log) {
            return [
                'id' => $log->id,
                'job_id' => $log->job_id,
                'technician' => $log->technician ? [
                    'id' => $log->technician->id,
                    'name' => $log->technician->name,
                ] : null,
                'start_time' => $log->start_time?->toISOString(),
                'end_time' => $log->end_time?->toISOString(),
                'total_minutes' => $log->total_minutes,
                'activity' => $log->activity,
                'work_description' => $log->work_description,
                'is_billable' => $log->is_billable,
                'time_type' => $log->time_type,
            ];
        })->values();

        return response()->json([
            'customer_device' => array_merge($this->serialize($cd), [
                'customer' => $cd->customer ? [
                    'id' => $cd->customer->id,
                    'name' => $cd->customer->name,
                    'email' => $cd->customer->email,
                    'phone' => $cd->customer->phone,
                ] : null,
                'device' => $cd->device ? [
                    'id' => $cd->device->id,
                    'model' => $cd->device->model,
                    'brand' => $cd->device->brand ? [
                        'id' => $cd->device->brand->id,
                        'name' => $cd->device->brand->name,
                    ] : null,
                    'type' => $cd->device->type ? [
                        'id' => $cd->device->type->id,
                        'name' => $cd->device->type->name,
                    ] : null,
                ] : null,
                'latest_job' => $latestJob,
                'time_logs' => $timeLogsData,
            ]),
        ]);
    }

    public function update(Request $request, string $business, int $id): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'integer'],
            'device_id' => ['sometimes', 'nullable', 'integer'],
            'label' => ['required', 'string', 'max:255'],
            'serial' => ['sometimes', 'nullable', 'string', 'max:255'],
            'pin' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ]);

        $cd = RepairBuddyCustomerDevice::query()->whereKey($id)->first();

        if (! $cd) {
            return response()->json([
                'message' => 'Customer device not found.',
            ], 404);
        }

        $cd->customer_id = (int) $validated['customer_id'];
        $cd->device_id = is_numeric($validated['device_id'] ?? null) ? (int) $validated['device_id'] : null;
        $cd->label = trim((string) $validated['label']);
        $cd->serial = isset($validated['serial']) && is_string($validated['serial']) ? trim($validated['serial']) : null;
        $cd->pin = isset($validated['pin']) && is_string($validated['pin']) ? trim($validated['pin']) : null;
        $cd->notes = isset($validated['notes']) && is_string($validated['notes']) ? trim($validated['notes']) : null;
        $cd->save();

        return response()->json([
            'message' => 'Updated.',
            'customer_device' => $this->serialize($cd),
        ]);
    }

    public function destroy(Request $request, string $business, int $id): JsonResponse
    {
        $cd = RepairBuddyCustomerDevice::query()->whereKey($id)->first();

        if (! $cd) {
            return response()->json([
                'message' => 'Customer device not found.',
            ], 404);
        }

        // Check if device is in use
        $inUse = $cd->jobDevices()->exists();
        if ($inUse) {
            return response()->json([
                'message' => 'Cannot delete: this device is associated with one or more jobs.',
                'code' => 'in_use',
            ], 409);
        }

        $cd->delete();

        return response()->json([
            'message' => 'Deleted.',
        ]);
    }

    private function serialize(RepairBuddyCustomerDevice $d): array
    {
        return [
            'id' => $d->id,
            'customer_id' => $d->customer_id,
            'device_id' => $d->device_id,
            'label' => $d->label,
            'serial' => $d->serial,
            'pin' => $d->pin,
            'notes' => $d->notes,
            'created_at' => $d->created_at,
            'updated_at' => $d->updated_at,
        ];
    }
}
