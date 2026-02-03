<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyEstimate;
use App\Models\RepairBuddyEstimateItem;
use Illuminate\Http\Request;

class RepairBuddyPortalEstimatesController extends Controller
{
    public function index(Request $request, string $business)
    {
        $validated = $request->validate([
            'caseNumber' => ['required', 'string', 'max:64'],
        ]);

        $caseNumber = trim((string) $validated['caseNumber']);

        $estimate = RepairBuddyEstimate::query()
            ->where('case_number', $caseNumber)
            ->first();

        if (! $estimate) {
            return response()->json([
                'estimates' => [],
            ]);
        }

        return response()->json([
            'estimates' => [
                $this->serializeSummary($estimate),
            ],
        ]);
    }

    public function show(Request $request, string $business, string $estimateId)
    {
        $validated = $request->validate([
            'caseNumber' => ['required', 'string', 'max:64'],
        ]);

        $caseNumber = trim((string) $validated['caseNumber']);

        if (! is_numeric($estimateId)) {
            return response()->json([
                'message' => 'Estimate not found.',
            ], 404);
        }

        $estimate = RepairBuddyEstimate::query()
            ->whereKey((int) $estimateId)
            ->where('case_number', $caseNumber)
            ->with(['customer'])
            ->first();

        if (! $estimate) {
            return response()->json([
                'message' => 'Estimate not found.',
            ], 404);
        }

        $items = RepairBuddyEstimateItem::query()
            ->where('estimate_id', $estimate->id)
            ->with('tax')
            ->orderBy('id', 'asc')
            ->limit(5000)
            ->get();

        $subtotalCents = 0;
        $taxCents = 0;
        $currency = $items->first()?->unit_price_currency ?: ($this->tenant()->currency ?? 'USD');

        $serializedItems = $items->map(function (RepairBuddyEstimateItem $item) use (&$subtotalCents, &$taxCents, &$currency) {
            $qty = is_numeric($item->qty) ? (int) $item->qty : 0;
            $unit = is_numeric($item->unit_price_amount_cents) ? (int) $item->unit_price_amount_cents : 0;
            $lineSubtotal = $qty * $unit;

            $rate = $item->tax ? (float) $item->tax->rate : 0.0;
            $lineTax = (int) round($lineSubtotal * ($rate / 100.0));

            $subtotalCents += $lineSubtotal;
            $taxCents += $lineTax;

            if (is_string($item->unit_price_currency) && $item->unit_price_currency !== '') {
                $currency = (string) $item->unit_price_currency;
            }

            return [
                'id' => $item->id,
                'estimate_id' => $item->estimate_id,
                'item_type' => $item->item_type,
                'ref_id' => $item->ref_id,
                'name' => $item->name_snapshot,
                'qty' => $item->qty,
                'unit_price' => [
                    'currency' => $item->unit_price_currency,
                    'amount_cents' => (int) $item->unit_price_amount_cents,
                ],
                'tax' => $item->tax ? [
                    'id' => $item->tax->id,
                    'name' => $item->tax->name,
                    'rate' => $item->tax->rate,
                    'is_default' => (bool) $item->tax->is_default,
                ] : null,
                'meta' => is_array($item->meta_json) ? $item->meta_json : null,
                'created_at' => $item->created_at,
            ];
        })->values()->all();

        return response()->json([
            'estimate' => [
                'id' => $estimate->id,
                'case_number' => $estimate->case_number,
                'title' => $estimate->title,
                'status' => $estimate->status,
                'sent_at' => $estimate->sent_at,
                'approved_at' => $estimate->approved_at,
                'rejected_at' => $estimate->rejected_at,
                'case_detail' => $estimate->case_detail,
                'pickup_date' => $estimate->pickup_date,
                'delivery_date' => $estimate->delivery_date,
                'items' => $serializedItems,
                'totals' => [
                    'currency' => $currency,
                    'subtotal_cents' => $subtotalCents,
                    'tax_cents' => $taxCents,
                    'total_cents' => $subtotalCents + $taxCents,
                ],
                'updated_at' => $estimate->updated_at,
            ],
        ]);
    }

    private function serializeSummary(RepairBuddyEstimate $estimate): array
    {
        return [
            'id' => $estimate->id,
            'case_number' => $estimate->case_number,
            'title' => $estimate->title,
            'status' => $estimate->status,
            'updated_at' => $estimate->updated_at,
        ];
    }
}
