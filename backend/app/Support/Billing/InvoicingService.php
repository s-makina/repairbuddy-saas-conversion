<?php

namespace App\Support\Billing;

use App\Models\Branch;
use App\Models\BranchInvoiceCounter;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\TaxProfile;
use App\Models\TaxRate;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class InvoicingService
{
    public function createDraftFromSubscription(Tenant $tenant, TenantSubscription $subscription): Invoice
    {
        return DB::transaction(function () use ($tenant, $subscription) {
            TenantContext::set($tenant);

            try {
                $branch = $this->resolveInvoiceBranch($tenant);
                $invoiceNumber = $this->nextInvoiceNumber($tenant, $branch);

                $sellerCountry = strtoupper((string) config('billing.seller_country'));
                $billingAddress = is_array($tenant->billing_address_json) ? $tenant->billing_address_json : [];
                $billingCountry = strtoupper((string) ($tenant->billing_country ?? ($billingAddress['country'] ?? '')));
                $billingVatNumber = is_string($tenant->billing_vat_number) && $tenant->billing_vat_number !== '' ? $tenant->billing_vat_number : null;

                $price = $subscription->price;
                $planName = $subscription->planVersion?->plan?->name;

                $description = trim('Subscription'.($planName ? ' - '.$planName : '').' ('.$subscription->currency.' '.($price?->interval ?? 'period').')');

                $unitAmountCents = (int) ($price?->amount_cents ?? 0);
                $quantity = 1;
                $subtotalCents = $unitAmountCents * $quantity;

                [$taxRatePercent, $taxScenario, $taxReason] = $this->resolveVat(
                    sellerCountry: $sellerCountry,
                    buyerCountry: $billingCountry,
                    buyerVatNumber: $billingVatNumber,
                );

                $taxCents = $this->computeTaxCents($subtotalCents, $taxRatePercent);
                $totalCents = $subtotalCents + $taxCents;

                $invoice = Invoice::query()->create([
                    'branch_id' => $branch?->id,
                    'tenant_subscription_id' => $subscription->id,
                    'invoice_number' => $invoiceNumber,
                    'status' => 'draft',
                    'currency' => (string) $subscription->currency,
                    'subtotal_cents' => $subtotalCents,
                    'tax_cents' => $taxCents,
                    'total_cents' => $totalCents,
                    'seller_country' => $sellerCountry,
                    'billing_country' => $billingCountry ?: null,
                    'billing_vat_number' => $billingVatNumber,
                    'billing_address_json' => $billingAddress ?: null,
                    'tax_details_json' => [
                        'scenario' => $taxScenario,
                        'reason' => $taxReason,
                        'seller_country' => $sellerCountry,
                        'buyer_country' => $billingCountry,
                        'buyer_vat_number' => $billingVatNumber,
                        'rate_percent' => $taxRatePercent,
                    ],
                    'issued_at' => null,
                    'paid_at' => null,
                ]);

                InvoiceLine::query()->create([
                    'invoice_id' => $invoice->id,
                    'description' => $description,
                    'quantity' => $quantity,
                    'unit_amount_cents' => $unitAmountCents,
                    'subtotal_cents' => $subtotalCents,
                    'tax_rate_percent' => $taxRatePercent,
                    'tax_cents' => $taxCents,
                    'total_cents' => $totalCents,
                    'tax_meta_json' => [
                        'scenario' => $taxScenario,
                        'reason' => $taxReason,
                    ],
                ]);

                return $invoice->load('lines');
            } finally {
                TenantContext::set(null);
            }
        });
    }

    protected function resolveInvoiceBranch(Tenant $tenant): ?Branch
    {
        $branch = BranchContext::branch();

        if ($branch instanceof Branch) {
            return $branch;
        }

        $branchId = is_numeric($tenant->default_branch_id ?? null) ? (int) $tenant->default_branch_id : null;

        if ($branchId && $branchId > 0) {
            return Branch::query()->whereKey($branchId)->first();
        }

        return Branch::query()->where('is_active', true)->orderBy('id')->first();
    }

    public function issue(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            if ($invoice->status !== 'draft') {
                throw new InvalidInvoiceStatusException('Only draft invoices can be issued.');
            }

            $invoice->forceFill([
                'status' => 'issued',
                'issued_at' => now(),
            ])->save();

            return $invoice;
        });
    }

    public function markPaid(Invoice $invoice, ?\DateTimeInterface $paidAt = null, ?string $paidMethod = null, ?string $paidNote = null): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            if ($invoice->status !== 'issued') {
                throw new InvalidInvoiceStatusException('Only issued invoices can be marked as paid.');
            }

            $invoice->forceFill([
                'status' => 'paid',
                'paid_at' => $paidAt ?: now(),
                'paid_method' => $paidMethod,
                'paid_note' => $paidNote,
            ])->save();

            return $invoice;
        });
    }

    public function buildPdf(Invoice $invoice)
    {
        $invoice->loadMissing('lines', 'tenant');

        $escape = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $html = '<!doctype html><html><head><meta charset="utf-8">'
            . '<style>'
            . 'body{font-family:DejaVu Sans, sans-serif; font-size:12px; color:#111;}'
            . 'h1{font-size:18px; margin:0 0 10px 0;}'
            . 'table{width:100%; border-collapse:collapse; margin-top:10px;}'
            . 'th,td{border:1px solid #ddd; padding:6px; vertical-align:top;}'
            . 'th{background:#f5f5f5; font-weight:700;}'
            . '.totals{margin-top:10px; width:40%; float:right;}'
            . '.totals td{border:none; padding:2px 6px;}'
            . '</style>'
            . '</head><body>'
            . '<h1>Invoice '.$escape($invoice->invoice_number).'</h1>'
            . '<div><strong>Status:</strong> '.$escape($invoice->status).'</div>'
            . '<div><strong>Tenant:</strong> '.$escape($invoice->tenant?->name).'</div>'
            . '<div><strong>Issued:</strong> '.$escape($invoice->issued_at ?? '').'</div>'
            . '<table><thead><tr>'
            . '<th>Description</th><th>Qty</th><th>Unit</th><th>Subtotal</th><th>VAT %</th><th>VAT</th><th>Total</th>'
            . '</tr></thead><tbody>';

        foreach ($invoice->lines as $line) {
            $html .= '<tr>'
                . '<td>'.$escape($line->description).'</td>'
                . '<td>'.$escape($line->quantity).'</td>'
                . '<td>'.$escape($line->unit_amount_cents).'</td>'
                . '<td>'.$escape($line->subtotal_cents).'</td>'
                . '<td>'.$escape($line->tax_rate_percent ?? '').'</td>'
                . '<td>'.$escape($line->tax_cents).'</td>'
                . '<td>'.$escape($line->total_cents).'</td>'
                . '</tr>';
        }

        $html .= '</tbody></table>'
            . '<table class="totals">'
            . '<tr><td><strong>Subtotal</strong></td><td style="text-align:right">'.$escape($invoice->subtotal_cents).'</td></tr>'
            . '<tr><td><strong>Tax</strong></td><td style="text-align:right">'.$escape($invoice->tax_cents).'</td></tr>'
            . '<tr><td><strong>Total</strong></td><td style="text-align:right">'.$escape($invoice->total_cents).'</td></tr>'
            . '</table>'
            . '</body></html>';

        return Pdf::loadHTML($html)->setPaper('a4', 'portrait');
    }

    protected function nextInvoiceNumber(Tenant $tenant, ?Branch $branch): string
    {
        $tenantSlug = strtoupper((string) $tenant->slug);
        $branchCode = strtoupper((string) ($branch?->code ?? 'MAIN'));
        $year = (int) now()->format('Y');

        $branchId = $branch?->id;

        if (! $branchId) {
            $padded = str_pad('1', 4, '0', STR_PAD_LEFT);
            return 'RB-'.$tenantSlug.'-'.$branchCode.'-'.$year.'-'.$padded;
        }

        $counter = BranchInvoiceCounter::query()
            ->where('branch_id', $branchId)
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        if (! $counter) {
            try {
                BranchInvoiceCounter::query()->create([
                    'branch_id' => $branchId,
                    'year' => $year,
                    'next_number' => 1,
                ]);
            } catch (QueryException $e) {
                $sqlState = is_array($e->errorInfo ?? null) ? (string) ($e->errorInfo[0] ?? '') : '';
                $errorCode = is_array($e->errorInfo ?? null) ? (string) ($e->errorInfo[1] ?? '') : '';

                $isUniqueViolation = $sqlState === '23000' || $errorCode === '1062';

                if (! $isUniqueViolation) {
                    throw $e;
                }
            }

            $counter = BranchInvoiceCounter::query()
                ->where('branch_id', $branchId)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();
        }

        if (! $counter) {
            throw new \RuntimeException('Failed to initialize invoice counter.');
        }

        $n = (int) ($counter->next_number ?? 1);

        $counter->forceFill([
            'next_number' => $n + 1,
        ])->save();

        $padded = str_pad((string) $n, 4, '0', STR_PAD_LEFT);

        return 'RB-'.$tenantSlug.'-'.$branchCode.'-'.$year.'-'.$padded;
    }

    protected function resolveVat(string $sellerCountry, string $buyerCountry, ?string $buyerVatNumber): array
    {
        $sellerCountry = strtoupper($sellerCountry);
        $buyerCountry = strtoupper($buyerCountry);

        if ($buyerVatNumber && $buyerCountry !== '' && $buyerCountry !== $sellerCountry) {
            return [0.00, 'reverse_charge', 'vat_number_present'];
        }

        if ($buyerCountry !== '' && $buyerCountry === $sellerCountry) {
            $rate = $this->activeVatRatePercentForCountry($buyerCountry);

            if ($rate !== null) {
                return [$rate, 'same_country_vat', 'rate_configured'];
            }

            return [0.00, 'non_vat', 'no_rate_configured'];
        }

        $rate = $buyerCountry !== '' ? $this->activeVatRatePercentForCountry($buyerCountry) : null;

        if ($rate === null) {
            return [0.00, 'non_vat', 'no_rate_configured'];
        }

        return [$rate, 'vat', 'rate_configured'];
    }

    protected function activeVatRatePercentForCountry(string $countryCode): ?float
    {
        $countryCode = strtoupper($countryCode);

        $profile = TaxProfile::query()
            ->where('country_code', $countryCode)
            ->where('is_vat', true)
            ->orderBy('id')
            ->first();

        if (! $profile) {
            return null;
        }

        $today = now()->toDateString();

        $rate = TaxRate::query()
            ->where('tax_profile_id', $profile->id)
            ->where('is_active', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('valid_to')->orWhere('valid_to', '>=', $today);
            })
            ->orderByDesc('id')
            ->first();

        if (! $rate) {
            return null;
        }

        return (float) $rate->rate_percent;
    }

    protected function computeTaxCents(int $subtotalCents, float $ratePercent): int
    {
        if ($subtotalCents <= 0 || $ratePercent <= 0) {
            return 0;
        }

        return (int) round($subtotalCents * ($ratePercent / 100), 0);
    }
}
