<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyTax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RepairBuddyTaxController extends Controller
{
    public function index(Request $request, string $business)
    {
        $taxes = RepairBuddyTax::query()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->limit(200)
            ->get();

        return response()->json([
            'taxes' => $taxes->map(function (RepairBuddyTax $t) {
                return $this->serialize($t);
            }),
        ]);
    }

    public function store(Request $request, string $business)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        $isDefault = (bool) ($validated['is_default'] ?? false);

        $tax = DB::transaction(function () use ($validated, $isDefault) {
            if ($isDefault) {
                RepairBuddyTax::query()
                    ->where('is_default', true)
                    ->update([
                        'is_default' => false,
                    ]);
            }

            return RepairBuddyTax::query()->create([
                'name' => $validated['name'],
                'rate' => $validated['rate'],
                'is_default' => $isDefault,
            ]);
        });

        return response()->json([
            'tax' => $this->serialize($tax),
        ], 201);
    }

    private function serialize(RepairBuddyTax $t): array
    {
        return [
            'id' => $t->id,
            'name' => $t->name,
            'rate' => $t->rate,
            'is_default' => (bool) $t->is_default,
        ];
    }
}
