<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyService;
use Illuminate\Http\Request;

class RepairBuddyServicesController extends Controller
{
    public function index(Request $request, string $business)
    {
        $services = RepairBuddyService::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(500)
            ->get();

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
        ];
    }
}
