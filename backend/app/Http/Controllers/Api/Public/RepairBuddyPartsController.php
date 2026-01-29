<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyPart;
use Illuminate\Http\Request;

class RepairBuddyPartsController extends Controller
{
    public function index(Request $request, string $business)
    {
        $parts = RepairBuddyPart::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(500)
            ->get();

        return response()->json([
            'parts' => $parts->map(fn (RepairBuddyPart $p) => $this->serialize($p)),
        ]);
    }

    private function serialize(RepairBuddyPart $p): array
    {
        $price = null;
        if (is_numeric($p->price_amount_cents) && is_string($p->price_currency) && $p->price_currency !== '') {
            $price = [
                'currency' => $p->price_currency,
                'amount_cents' => (int) $p->price_amount_cents,
            ];
        }

        return [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'price' => $price,
            'stock' => $p->stock,
        ];
    }
}
