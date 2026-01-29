<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyService;
use Illuminate\Http\Request;

class RepairBuddyServiceController extends Controller
{
    public function index(Request $request, string $business)
    {
        $validated = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $q = is_string($validated['q'] ?? null) ? trim((string) $validated['q']) : '';
        $limit = is_int($validated['limit'] ?? null) ? (int) $validated['limit'] : 200;

        $query = RepairBuddyService::query()->orderBy('name');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('id', $q);
            });
        }

        $services = $query->limit($limit)->get();

        return response()->json([
            'services' => $services->map(fn (RepairBuddyService $s) => $this->serialize($s)),
        ]);
    }

    private function serialize(RepairBuddyService $s): array
    {
        $basePrice = null;
        if (is_numeric($s->base_price_amount_cents) && is_string($s->base_price_currency) && $s->base_price_currency !== '') {
            $basePrice = [
                'currency' => $s->base_price_currency,
                'amount_cents' => (int) $s->base_price_amount_cents,
            ];
        }

        return [
            'id' => $s->id,
            'name' => $s->name,
            'description' => $s->description,
            'base_price' => $basePrice,
            'is_active' => (bool) $s->is_active,
        ];
    }
}
