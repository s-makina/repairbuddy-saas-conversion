<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyTax;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RepairBuddyTaxController extends Controller
{
    public function index(Request $request, string $business)
    {
        $this->importLegacyTaxesIfMissing();

        $taxes = RepairBuddyTax::query()
            ->orderByDesc('is_default')
            ->orderByDesc('is_active')
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
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $isDefault = (bool) ($validated['is_default'] ?? false);
        $isActive = (bool) ($validated['is_active'] ?? true);

        $tax = DB::transaction(function () use ($validated, $isDefault, $isActive) {
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
                'is_active' => $isActive,
            ]);
        });

        return response()->json([
            'tax' => $this->serialize($tax),
        ], 201);
    }

    public function update(Request $request, string $business, RepairBuddyTax $tax)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
        ]);

        $tax->forceFill([
            'name' => array_key_exists('name', $validated) ? $validated['name'] : $tax->name,
            'rate' => array_key_exists('rate', $validated) ? $validated['rate'] : $tax->rate,
        ])->save();

        return response()->json([
            'tax' => $this->serialize($tax->fresh()),
        ]);
    }

    public function destroy(Request $request, string $business, RepairBuddyTax $tax)
    {
        $tax->delete();

        return response()->json([
            'deleted' => true,
        ]);
    }

    public function setDefault(Request $request, string $business, RepairBuddyTax $tax)
    {
        DB::transaction(function () use ($tax) {
            RepairBuddyTax::query()
                ->where('is_default', true)
                ->update([
                    'is_default' => false,
                ]);

            $tax->forceFill([
                'is_default' => true,
            ])->save();
        });

        return response()->json([
            'tax' => $this->serialize($tax->fresh()),
        ]);
    }

    public function setActive(Request $request, string $business, RepairBuddyTax $tax)
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $tax->forceFill([
            'is_active' => (bool) $validated['is_active'],
        ])->save();

        return response()->json([
            'tax' => $this->serialize($tax->fresh()),
        ]);
    }

    private function importLegacyTaxesIfMissing(): void
    {
        if (RepairBuddyTax::query()->exists()) {
            return;
        }

        $tenant = TenantContext::tenant();
        if (! $tenant instanceof Tenant) {
            return;
        }

        $legacy = data_get($tenant->setup_state ?? [], 'repairbuddy_settings.taxes');
        if (! is_array($legacy)) {
            return;
        }

        $legacyTaxes = $legacy['taxes'] ?? null;
        if (! is_array($legacyTaxes) || count($legacyTaxes) === 0) {
            return;
        }

        $legacyDefault = is_string($legacy['defaultTaxId'] ?? null) ? (string) $legacy['defaultTaxId'] : null;

        DB::transaction(function () use ($legacyTaxes, $legacyDefault) {
            foreach ($legacyTaxes as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $name = is_string($row['name'] ?? null) ? trim((string) $row['name']) : '';
                if ($name === '') {
                    continue;
                }

                $ratePercent = $row['ratePercent'] ?? null;
                if (! is_numeric($ratePercent)) {
                    continue;
                }

                $status = is_string($row['status'] ?? null) ? strtolower(trim((string) $row['status'])) : 'active';
                $isActive = $status !== 'inactive';

                $legacyId = is_string($row['id'] ?? null) ? (string) $row['id'] : null;
                $isDefault = $legacyDefault !== null && $legacyId !== null && $legacyId === $legacyDefault;

                RepairBuddyTax::query()->create([
                    'name' => $name,
                    'rate' => (float) $ratePercent,
                    'is_active' => $isActive,
                    'is_default' => $isDefault,
                ]);
            }

            if (! RepairBuddyTax::query()->where('is_default', true)->exists()) {
                $firstActive = RepairBuddyTax::query()->where('is_active', true)->orderBy('id')->first();
                if ($firstActive) {
                    $firstActive->forceFill(['is_default' => true])->save();
                }
            }
        });
    }

    private function serialize(RepairBuddyTax $t): array
    {
        return [
            'id' => $t->id,
            'name' => $t->name,
            'rate' => $t->rate,
            'is_default' => (bool) $t->is_default,
            'is_active' => (bool) ($t->is_active ?? true),
        ];
    }
}
