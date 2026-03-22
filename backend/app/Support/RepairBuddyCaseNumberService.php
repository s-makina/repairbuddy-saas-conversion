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
        $prefix = is_string($generalSettings['caseNumberPrefix'] ?? null) ? trim((string) $generalSettings['caseNumberPrefix']) : '';
        if ($prefix === '') {
            $prefix = is_string($branch->rb_case_prefix ?? null) ? trim((string) $branch->rb_case_prefix) : '';
        }
        if ($prefix === '') {
            $prefix = 'WC';
        }

        // Ensure prefix is uppercase and remove any trailing underscore (we add it ourselves)
        $prefix = strtoupper($prefix);
        $prefix = rtrim($prefix, '_');

        $timestamp = now()->format('YmdHis');

        return $prefix . '_' . $timestamp;
    }
}
