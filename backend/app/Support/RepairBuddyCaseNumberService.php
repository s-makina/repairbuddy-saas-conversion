<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\RepairBuddyCaseCounter;
use App\Models\Tenant;
use Illuminate\Database\QueryException;

class RepairBuddyCaseNumberService
{
    public function nextCaseNumber(Tenant $tenant, Branch $branch, array $generalSettings): string
    {
        $tenantSlug = is_string($tenant->slug) ? trim((string) $tenant->slug) : '';
        $branchCode = is_string($branch->code) ? trim((string) $branch->code) : '';

        $prefix = is_string($generalSettings['caseNumberPrefix'] ?? null) ? trim((string) $generalSettings['caseNumberPrefix']) : '';
        if ($prefix === '') {
            $prefix = is_string($branch->rb_case_prefix ?? null) ? trim((string) $branch->rb_case_prefix) : '';
        }
        if ($prefix === '') {
            $prefix = 'RB';
        }

        $digits = is_numeric($generalSettings['caseNumberLength'] ?? null) ? (int) $generalSettings['caseNumberLength'] : 0;
        if ($digits <= 0) {
            $digits = is_numeric($branch->rb_case_digits ?? null) ? (int) $branch->rb_case_digits : 6;
        }
        $digits = max(1, min(32, $digits));

        $counter = RepairBuddyCaseCounter::query()
            ->where('tenant_id', (int) $tenant->id)
            ->where('branch_id', (int) $branch->id)
            ->lockForUpdate()
            ->first();

        if (! $counter) {
            try {
                RepairBuddyCaseCounter::query()->create([
                    'tenant_id' => (int) $tenant->id,
                    'branch_id' => (int) $branch->id,
                    'next_number' => 1,
                ]);
            } catch (QueryException $e) {
                // ignore duplicate key and re-select
            }

            $counter = RepairBuddyCaseCounter::query()
                ->where('tenant_id', (int) $tenant->id)
                ->where('branch_id', (int) $branch->id)
                ->lockForUpdate()
                ->first();
        }

        if (! $counter) {
            throw new \RuntimeException('Case counter not found.');
        }

        $next = is_numeric($counter->next_number) ? (int) $counter->next_number : 1;
        $counter->forceFill([
            'next_number' => $next + 1,
        ])->save();

        $num = str_pad((string) $next, $digits, '0', STR_PAD_LEFT);

        $parts = array_values(array_filter([
            $prefix,
            $tenantSlug,
            $branchCode,
            $num,
        ], fn ($p) => is_string($p) && trim($p) !== ''));

        $case = implode('-', $parts);

        if (strlen($case) > 64) {
            $case = implode('-', array_values(array_filter([$prefix, $branchCode, $num])));
        }

        return $case;
    }
}
