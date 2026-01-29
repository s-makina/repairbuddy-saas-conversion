<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyJobStatus;
use Illuminate\Http\Request;

class RepairBuddyJobStatusController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'job_statuses' => RepairBuddyJobStatus::query()
                ->orderBy('id')
                ->get()
                ->map(function (RepairBuddyJobStatus $s) {
                    return [
                        'id' => $s->id,
                        'slug' => $s->slug,
                        'label' => $s->label,
                        'invoice_label' => $s->invoice_label,
                        'email_enabled' => (bool) $s->email_enabled,
                        'sms_enabled' => (bool) $s->sms_enabled,
                        'is_active' => (bool) $s->is_active,
                    ];
                }),
        ]);
    }
}
