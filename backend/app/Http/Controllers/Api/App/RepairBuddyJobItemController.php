<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyJob;
use App\Models\RepairBuddyJobItem;
use App\Models\RepairBuddyPart;
use App\Models\RepairBuddyService;
use App\Models\RepairBuddyTax;
use Illuminate\Http\Request;

class RepairBuddyJobItemController extends Controller
{
    public function store(Request $request, string $business, $jobId)
    {
        if (! is_numeric($jobId)) {
            return response()->json([
                'message' => 'Job not found.',
            ], 404);
        }

        $job = RepairBuddyJob::query()->whereKey((int) $jobId)->first();

        if (! $job) {
            return response()->json([
                'message' => 'Job not found.',
            ], 404);
        }

        $validated = $request->validate([
            'item_type' => ['required', 'string', 'max:32'],
            'ref_id' => ['sometimes', 'nullable', 'integer'],
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'qty' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:9999'],
            'unit_price_amount_cents' => ['sometimes', 'nullable', 'integer', 'min:-1000000000', 'max:1000000000'],
            'unit_price_currency' => ['sometimes', 'nullable', 'string', 'size:3'],
            'tax_id' => ['sometimes', 'nullable', 'integer'],
            'meta' => ['sometimes', 'nullable', 'array'],
        ]);

        $itemType = (string) $validated['item_type'];
        if (! in_array($itemType, ['service', 'part', 'fee', 'discount'], true)) {
            return response()->json([
                'message' => 'Item type is invalid.',
            ], 422);
        }

        $refId = array_key_exists('ref_id', $validated) && is_numeric($validated['ref_id']) ? (int) $validated['ref_id'] : null;
        $name = is_string($validated['name'] ?? null) ? trim((string) $validated['name']) : '';
        $qty = is_numeric($validated['qty'] ?? null) ? (int) $validated['qty'] : 1;

        $unitPriceCents = array_key_exists('unit_price_amount_cents', $validated) && is_numeric($validated['unit_price_amount_cents'])
            ? (int) $validated['unit_price_amount_cents']
            : null;

        $currency = is_string($validated['unit_price_currency'] ?? null) && $validated['unit_price_currency'] !== ''
            ? strtoupper((string) $validated['unit_price_currency'])
            : (string) ($this->tenant()->currency ?? 'USD');

        $taxId = array_key_exists('tax_id', $validated) && is_numeric($validated['tax_id']) ? (int) $validated['tax_id'] : null;
        if ($taxId) {
            $tax = RepairBuddyTax::query()->whereKey($taxId)->first();
            if (! $tax) {
                return response()->json([
                    'message' => 'Tax is invalid.',
                ], 422);
            }
        }

        $resolvedName = $name;
        $resolvedUnitPriceCents = $unitPriceCents;

        if ($itemType === 'service') {
            if (! $refId) {
                return response()->json([
                    'message' => 'Service ref_id is required.',
                ], 422);
            }

            $service = RepairBuddyService::query()->whereKey($refId)->first();
            if (! $service) {
                return response()->json([
                    'message' => 'Service is invalid.',
                ], 422);
            }

            $resolvedName = $resolvedName !== '' ? $resolvedName : (string) $service->name;
            if ($resolvedUnitPriceCents === null) {
                $resolvedUnitPriceCents = is_numeric($service->base_price_amount_cents) ? (int) $service->base_price_amount_cents : null;
            }
        }

        if ($itemType === 'part') {
            if (! $refId) {
                return response()->json([
                    'message' => 'Part ref_id is required.',
                ], 422);
            }

            $part = RepairBuddyPart::query()->whereKey($refId)->first();
            if (! $part) {
                return response()->json([
                    'message' => 'Part is invalid.',
                ], 422);
            }

            $resolvedName = $resolvedName !== '' ? $resolvedName : (string) $part->name;
            if ($resolvedUnitPriceCents === null) {
                $resolvedUnitPriceCents = is_numeric($part->price_amount_cents) ? (int) $part->price_amount_cents : null;
            }
        }

        if ($resolvedName === '') {
            return response()->json([
                'message' => 'Name is required.',
            ], 422);
        }

        if ($resolvedUnitPriceCents === null) {
            return response()->json([
                'message' => 'Unit price is required.',
            ], 422);
        }

        $item = RepairBuddyJobItem::query()->create([
            'job_id' => $job->id,
            'item_type' => $itemType,
            'ref_id' => $refId,
            'name_snapshot' => $resolvedName,
            'qty' => $qty,
            'unit_price_amount_cents' => $resolvedUnitPriceCents,
            'unit_price_currency' => $currency,
            'tax_id' => $taxId,
            'meta_json' => $validated['meta'] ?? null,
        ]);

        $totals = $this->computeTotals($job);

        return response()->json([
            'item' => $this->serializeItem($item->fresh(['tax'])),
            'totals' => $totals,
        ], 201);
    }

    public function destroy(Request $request, string $business, $jobId, $itemId)
    {
        if (! is_numeric($jobId) || ! is_numeric($itemId)) {
            return response()->json([
                'message' => 'Job item not found.',
            ], 404);
        }

        $job = RepairBuddyJob::query()->whereKey((int) $jobId)->first();

        if (! $job) {
            return response()->json([
                'message' => 'Job not found.',
            ], 404);
        }

        $item = RepairBuddyJobItem::query()
            ->whereKey((int) $itemId)
            ->where('job_id', $job->id)
            ->first();

        if (! $item) {
            return response()->json([
                'message' => 'Job item not found.',
            ], 404);
        }

        $item->delete();

        $totals = $this->computeTotals($job);

        return response()->json([
            'message' => 'Deleted.',
            'totals' => $totals,
        ]);
    }

    private function serializeItem(RepairBuddyJobItem $i): array
    {
        $tax = null;
        if ($i->tax) {
            $tax = [
                'id' => $i->tax->id,
                'name' => $i->tax->name,
                'rate' => $i->tax->rate,
                'is_default' => (bool) $i->tax->is_default,
            ];
        }

        return [
            'id' => $i->id,
            'job_id' => $i->job_id,
            'item_type' => $i->item_type,
            'ref_id' => $i->ref_id,
            'name' => $i->name_snapshot,
            'qty' => $i->qty,
            'unit_price' => [
                'currency' => $i->unit_price_currency,
                'amount_cents' => (int) $i->unit_price_amount_cents,
            ],
            'tax' => $tax,
            'meta' => is_array($i->meta_json) ? $i->meta_json : null,
            'created_at' => $i->created_at,
        ];
    }

    private function computeTotals(RepairBuddyJob $job): array
    {
        $items = RepairBuddyJobItem::query()
            ->where('job_id', $job->id)
            ->with('tax')
            ->limit(5000)
            ->get();

        $currency = (string) ($this->tenant()->currency ?? 'USD');

        $subtotalCents = 0;
        $taxCents = 0;

        foreach ($items as $item) {
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
        }

        return [
            'currency' => $currency,
            'subtotal_cents' => $subtotalCents,
            'tax_cents' => $taxCents,
            'total_cents' => $subtotalCents + $taxCents,
        ];
    }
}
