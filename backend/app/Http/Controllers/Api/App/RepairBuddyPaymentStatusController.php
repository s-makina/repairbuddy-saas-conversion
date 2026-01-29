<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyPaymentStatus;
use Illuminate\Http\Request;

class RepairBuddyPaymentStatusController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'payment_statuses' => RepairBuddyPaymentStatus::query()
                ->orderBy('id')
                ->get()
                ->map(function (RepairBuddyPaymentStatus $s) {
                    return [
                        'id' => $s->id,
                        'slug' => $s->slug,
                        'label' => $s->label,
                        'is_active' => (bool) $s->is_active,
                    ];
                }),
        ]);
    }
}
