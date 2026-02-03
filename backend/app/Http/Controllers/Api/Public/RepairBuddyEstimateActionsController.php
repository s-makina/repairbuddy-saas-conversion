<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyEstimate;
use App\Models\RepairBuddyEstimateToken;
use App\Models\RepairBuddyEvent;
use App\Support\RepairBuddyEstimateConversionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RepairBuddyEstimateActionsController extends Controller
{
    public function approve(Request $request, string $business, string $caseNumber)
    {
        return $this->handleAction($request, $business, $caseNumber, 'approve');
    }

    public function reject(Request $request, string $business, string $caseNumber)
    {
        return $this->handleAction($request, $business, $caseNumber, 'reject');
    }

    private function handleAction(Request $request, string $business, string $caseNumber, string $purpose)
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'min:10', 'max:255'],
        ]);

        $token = (string) $validated['token'];
        $tokenHash = hash('sha256', $token);

        $estimate = RepairBuddyEstimate::query()
            ->where('case_number', $caseNumber)
            ->first();

        if (! $estimate) {
            return response()->json([
                'message' => 'Estimate not found.',
            ], 404);
        }

        $tokenRow = RepairBuddyEstimateToken::query()
            ->where('estimate_id', $estimate->id)
            ->where('purpose', $purpose)
            ->where('token_hash', $tokenHash)
            ->first();

        if (! $tokenRow) {
            return response()->json([
                'message' => 'Invalid token.',
            ], 403);
        }

        if ($tokenRow->expires_at && $tokenRow->expires_at->isPast()) {
            return response()->json([
                'message' => 'Token expired.',
            ], 403);
        }

        $result = DB::transaction(function () use ($estimate, $purpose, $tokenRow) {
            $estimate->refresh();
            $tokenRow->refresh();

            if ($tokenRow->used_at) {
                return 'token_used';
            }

            if ($purpose === 'approve') {
                if ($estimate->status !== 'approved') {
                    $estimate->forceFill([
                        'status' => 'approved',
                        'approved_at' => $estimate->approved_at ?: now(),
                        'rejected_at' => null,
                    ])->save();

                    RepairBuddyEvent::query()->create([
                        'actor_user_id' => null,
                        'entity_type' => 'estimate',
                        'entity_id' => $estimate->id,
                        'visibility' => 'public',
                        'event_type' => 'estimate.approved',
                        'payload_json' => [
                            'title' => 'Estimate approved',
                            'case_number' => $estimate->case_number,
                        ],
                    ]);
                }

                if (! is_numeric($estimate->converted_job_id) || (int) $estimate->converted_job_id <= 0) {
                    $converter = app(RepairBuddyEstimateConversionService::class);
                    $converter->convertToJob($estimate, null);
                }
            }

            if ($purpose === 'reject') {
                if ($estimate->status !== 'rejected') {
                    $estimate->forceFill([
                        'status' => 'rejected',
                        'rejected_at' => $estimate->rejected_at ?: now(),
                        'approved_at' => null,
                    ])->save();

                    RepairBuddyEvent::query()->create([
                        'actor_user_id' => null,
                        'entity_type' => 'estimate',
                        'entity_id' => $estimate->id,
                        'visibility' => 'public',
                        'event_type' => 'estimate.rejected',
                        'payload_json' => [
                            'title' => 'Estimate rejected',
                            'case_number' => $estimate->case_number,
                        ],
                    ]);
                }
            }

            $tokenRow->forceFill([
                'used_at' => now(),
            ])->save();

            return 'ok';
        });

        return response()->json([
            'message' => $result === 'token_used' ? 'Already processed.' : 'OK',
            'status' => $estimate->fresh()->status,
        ]);
    }
}
