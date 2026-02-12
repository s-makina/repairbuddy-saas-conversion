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
        $length = is_numeric($generalSettings['caseNumberLength'] ?? null) ? (int) $generalSettings['caseNumberLength'] : 0;
        if ($length <= 0) {
            $length = is_numeric($branch->rb_case_digits ?? null) ? (int) $branch->rb_case_digits : 6;
        }
        $length = max(1, min(32, $length));

        $prefix = is_string($generalSettings['caseNumberPrefix'] ?? null) ? trim((string) $generalSettings['caseNumberPrefix']) : '';
        if ($prefix === '') {
            $prefix = is_string($branch->rb_case_prefix ?? null) ? trim((string) $branch->rb_case_prefix) : '';
        }
        if ($prefix === '') {
            $prefix = 'WC_';
        }

        $alphabet = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $random = '';
        $max = strlen($alphabet) - 1;
        for ($i = 0; $i < $length; $i++) {
            $random .= $alphabet[random_int(0, $max)];
        }

        $case = $prefix.$random.time();

        if (strlen($case) > 64) {
            $case = substr($case, 0, 64);
        }

        return $case;
    }
}
