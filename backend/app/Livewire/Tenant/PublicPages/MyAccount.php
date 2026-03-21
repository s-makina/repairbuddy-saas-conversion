<?php

namespace App\Livewire\Tenant\PublicPages;

use App\Models\RepairBuddyJob;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class MyAccount extends Component
{
    /* ───────── Tenant context ───────── */
    public ?Tenant $tenant = null;
    public ?int $tenantId = null;
    public string $business = '';
    public string $tenantName = '';

    /* ───────── View state ───────── */
    public string $activeSection = 'dashboard';

    /* ───────── Section configuration ───────── */
    public array $sections = [
        'dashboard' => ['label' => 'Dashboard', 'icon' => 'grid'],
        'jobs' => ['label' => 'My Repairs', 'icon' => 'wrench-adjustable'],
        'profile' => ['label' => 'Account Settings', 'icon' => 'gear'],
    ];

    /* ───────── Login form ───────── */
    public string $loginEmail = '';
    public string $loginPassword = '';

    /* ───────── Registration form ───────── */
    public string $regFirstName = '';
    public string $regLastName = '';
    public string $regEmail = '';
    public string $regPhone = '';
    public string $regCompany = '';
    public string $regAddress = '';
    public string $regCity = '';
    public string $regPostalCode = '';
    public string $regState = '';

    /* ───────── Feedback ───────── */
    public string $successMessage = '';
    public string $errorMessage = '';

    /* ───────── Dashboard data ───────── */
    public array $jobs = [];
    public bool $statsLoaded = false;
    public array $jobStatusList = [];
    public array $estimateCountList = ['pending' => 0, 'approved' => 0, 'rejected' => 0];

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

        if (Auth::check()) {
            $this->loadDashboardData();
            $this->loadStats();
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

    /* ─────────── Login ─────────── */

    public function login(): void
    {
        $this->resetMessages();

        $this->validate([
            'loginEmail' => ['required', 'email'],
            'loginPassword' => ['required', 'string', 'min:1'],
        ]);

        if (! Auth::attempt(['email' => $this->loginEmail, 'password' => $this->loginPassword])) {
            $this->errorMessage = 'Invalid email or password.';
            return;
        }

        session()->regenerate();
        $this->loadDashboardData();
        $this->successMessage = 'Welcome back!';
    }

    /* ─────────── Registration ─────────── */

    public function register(): void
    {
        $this->resetMessages();

        $this->validate([
            'regFirstName' => ['required', 'string', 'max:100'],
            'regLastName' => ['required', 'string', 'max:100'],
            'regEmail' => ['required', 'email', 'unique:users,email'],
            'regPhone' => ['nullable', 'string', 'max:30'],
            'regCompany' => ['nullable', 'string', 'max:150'],
            'regAddress' => ['nullable', 'string', 'max:255'],
            'regCity' => ['nullable', 'string', 'max:100'],
            'regPostalCode' => ['nullable', 'string', 'max:20'],
            'regState' => ['nullable', 'string', 'max:100'],
        ]);

        $password = \Illuminate\Support\Str::random(10);

        $user = \App\Models\User::create([
            'tenant_id' => $this->tenantId,
            'name' => trim($this->regFirstName . ' ' . $this->regLastName),
            'email' => $this->regEmail,
            'password' => Hash::make($password),
            'role' => 'customer',
        ]);

        // Log in immediately
        Auth::login($user);
        session()->regenerate();

        $this->loadDashboardData();
        $this->successMessage = 'Account created! You are now logged in.';
    }

    /* ─────────── Logout ─────────── */

    public function logout(): void
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->jobs = [];
        $this->successMessage = '';
    }

    /* ─────────── Dashboard ─────────── */

    public function loadDashboardData(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $this->jobs = RepairBuddyJob::query()
            ->where('customer_id', $user->id)
            ->orderByDesc('opened_at')
            ->limit(50)
            ->get()
            ->map(fn ($j) => [
                'id' => $j->id,
                'case_number' => $j->case_number,
                'title' => $j->title,
                'status_slug' => $j->status_slug,
                'payment_status_slug' => $j->payment_status_slug,
                'priority' => $j->priority,
                'opened_at' => $j->opened_at?->format('M d, Y'),
                'closed_at' => $j->closed_at?->format('M d, Y'),
            ])
            ->toArray();
    }

    public function loadStats(): void
    {
        if ($this->statsLoaded) {
            return;
        }

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $this->jobStatusList = $this->buildJobStatuses($user->id);
        $this->estimateCountList = $this->buildEstimateCounts($user->id);
        $this->statsLoaded = true;
    }

    private function buildJobStatuses(int $customerId): array
    {
        $statuses = \App\Models\Status::query()
            ->where('status_type', 'Job')
            ->where('is_active', true)
            ->orderBy('id')
            ->limit(200)
            ->get();

        $counts = RepairBuddyJob::query()
            ->where('customer_id', $customerId)
            ->selectRaw('status_slug, COUNT(*) as aggregate')
            ->groupBy('status_slug')
            ->pluck('aggregate', 'status_slug')
            ->map(fn ($v) => (int) $v)
            ->all();

        return $statuses->map(function (\App\Models\Status $s) use ($counts) {
            $code = trim((string) $s->code);
            return [
                'label' => (string) $s->label,
                'slug'  => $code,
                'count' => (int) ($counts[$code] ?? 0),
                'color' => (string) ($s->color ?? '#063e70'),
            ];
        })->values()->all();
    }

    private function buildEstimateCounts(int $customerId): array
    {
        $defaults = ['pending' => 0, 'approved' => 0, 'rejected' => 0];

        $raw = \App\Models\RepairBuddyEstimate::query()
            ->where('customer_id', $customerId)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($v) => (int) $v)
            ->all();

        foreach ($defaults as $k => $_) {
            $defaults[$k] = (int) ($raw[$k] ?? 0);
        }
        return $defaults;
    }

    public function setSection(string $section): void
    {
        $this->activeSection = $section;
    }

    /* ─────────── Helpers ─────────── */

    private function resetMessages(): void
    {
        $this->successMessage = '';
        $this->errorMessage = '';
    }

    /* ─────────── Render ─────────── */

    public function render()
    {
        return view('livewire.tenant.public-pages.my-account', [
            'isLoggedIn' => Auth::check(),
            'currentUser' => Auth::user(),
        ]);
    }
}
