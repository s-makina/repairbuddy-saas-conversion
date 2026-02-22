<?php

namespace App\Livewire\Tenant\PublicPages;

use App\Models\RepairBuddyJob;
use App\Models\RepairBuddyJobCounter;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class ReviewJob extends Component
{
    /* ───────── Tenant context ───────── */
    public ?Tenant $tenant = null;
    public ?int $tenantId = null;
    public string $business = '';
    public string $tenantName = '';

    /* ───────── Form fields ───────── */
    public string $firstName = '';
    public string $lastName = '';
    public string $email = '';
    public string $phone = '';
    public string $city = '';
    public string $postalCode = '';
    public string $company = '';
    public string $address = '';
    public string $jobDetails = '';

    /* ───────── Feedback ───────── */
    public bool $submitted = false;
    public string $successMessage = '';
    public string $errorMessage = '';
    public string $createdCaseNumber = '';

    /* ─────────── mount ─────────── */

    public function mount(?Tenant $tenant = null, string $business = '')
    {
        $this->business = $business;

        if (! $tenant) {
            $tenant = TenantContext::tenant();
        }

        if ($tenant instanceof Tenant) {
            $this->tenant = $tenant;
            $this->tenantId = $tenant->id;
            $this->tenantName = (string) ($tenant->name ?? '');
        }

        // Pre-fill for logged-in users
        if (Auth::check()) {
            $user = Auth::user();
            $nameParts = explode(' ', $user->name ?? '', 2);
            $this->firstName = $nameParts[0] ?? '';
            $this->lastName = $nameParts[1] ?? '';
            $this->email = $user->email ?? '';
        }
    }

    public function hydrate(): void
    {
        if ($this->tenant instanceof Tenant) {
            TenantContext::set($this->tenant);

            $branchId = is_numeric($this->tenant->default_branch_id) ? (int) $this->tenant->default_branch_id : null;
            if ($branchId) {
                $branch = \App\Models\Branch::find($branchId);
                if ($branch) {
                    \App\Support\BranchContext::set($branch);
                }
            }
        }
    }

    /* ─────────── Submit ─────────── */

    public function submitQuote(): void
    {
        $this->errorMessage = '';
        $this->successMessage = '';

        $rules = [
            'jobDetails' => ['required', 'string', 'min:10'],
        ];

        // Require name + email for guests
        if (! Auth::check()) {
            $rules['firstName'] = ['required', 'string', 'max:100'];
            $rules['lastName'] = ['required', 'string', 'max:100'];
            $rules['email'] = ['required', 'email', 'max:255'];
        }

        $this->validate($rules);

        try {
            // Resolve or create customer
            $customerId = Auth::id();

            if (! $customerId) {
                $existingUser = User::where('email', $this->email)->first();

                if ($existingUser) {
                    $customerId = $existingUser->id;
                } else {
                    $newUser = User::create([
                        'tenant_id' => $this->tenantId,
                        'name' => trim($this->firstName . ' ' . $this->lastName),
                        'email' => $this->email,
                        'password' => Hash::make(\Illuminate\Support\Str::random(10)),
                        'role' => 'customer',
                    ]);
                    $customerId = $newUser->id;
                }
            }

            // Generate case number
            $caseNumber = $this->generateCaseNumber();

            // Create job as quote
            $branch = \App\Support\BranchContext::branch();
            $branchId = $branch ? $branch->id : ($this->tenant?->default_branch_id ?? null);

            $job = RepairBuddyJob::create([
                'tenant_id' => $this->tenantId,
                'branch_id' => $branchId,
                'case_number' => $caseNumber,
                'title' => 'Quote Request — ' . trim($this->firstName . ' ' . $this->lastName),
                'status_slug' => 'quote',
                'payment_status_slug' => 'pending',
                'customer_id' => $customerId,
                'created_by' => $customerId,
                'case_detail' => $this->jobDetails,
                'opened_at' => now(),
            ]);

            $this->submitted = true;
            $this->createdCaseNumber = $caseNumber;
            $this->successMessage = 'Your quote request has been submitted! Your case number is: ' . $caseNumber;

        } catch (\Throwable $e) {
            $this->errorMessage = 'Something went wrong. Please try again later.';
            report($e);
        }
    }

    /* ─────────── Reset form ─────────── */

    public function resetForm(): void
    {
        $this->submitted = false;
        $this->successMessage = '';
        $this->errorMessage = '';
        $this->createdCaseNumber = '';
        $this->jobDetails = '';

        if (! Auth::check()) {
            $this->firstName = '';
            $this->lastName = '';
            $this->email = '';
            $this->phone = '';
            $this->city = '';
            $this->postalCode = '';
            $this->company = '';
            $this->address = '';
        }
    }

    /* ─────────── Helpers ─────────── */

    private function generateCaseNumber(): string
    {
        // Try to use RepairBuddyJobCounter if it exists
        try {
            $counter = RepairBuddyJobCounter::query()
                ->where('tenant_id', $this->tenantId)
                ->lockForUpdate()
                ->first();

            if ($counter) {
                $counter->increment('next_number');
                return 'RB-' . str_pad((string) $counter->next_number, 6, '0', STR_PAD_LEFT);
            }
        } catch (\Throwable $e) {
            // Fall through to simpler method
        }

        // Fallback: timestamp-based
        return 'RB-' . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 8));
    }

    /* ─────────── Render ─────────── */

    public function render()
    {
        return view('livewire.tenant.public-pages.review-job', [
            'isLoggedIn' => Auth::check(),
        ]);
    }
}
