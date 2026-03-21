<?php

namespace App\Livewire\Tenant\PublicPages;

use App\Models\RepairBuddyJob;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class MyAccount extends Component
{
    use WithFileUploads;
    /* ───────── Tenant context ───────── */
    public ?Tenant $tenant = null;
    public ?int $tenantId = null;
    public string $business = '';
    public string $tenantName = '';

    /* ───────── View state ───────── */
    public string $activeSection = 'dashboard';

    /* ───────── Section configuration ───────── */
    public array $sections = [
        'dashboard'   => ['label' => 'Dashboard',     'icon' => 'bi-speedometer2'],
        'jobs'        => ['label' => 'Jobs',          'icon' => 'bi-wrench'],
        'estimates'   => ['label' => 'Estimates',     'icon' => 'bi-file-earmark-text'],
        'my-devices'  => ['label' => 'My Devices',    'icon' => 'bi-phone'],
        'reviews'     => ['label' => 'Reviews',       'icon' => 'bi-star'],
        'book-device' => ['label' => 'Book My Device', 'icon' => 'bi-calendar-plus', 'external' => true],
        'profile'     => ['label' => 'Profile',       'icon' => 'bi-person-circle'],
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

    /* ───────── Profile form ───────── */
    public string $profileFirstName = '';
    public string $profileLastName = '';
    public string $profileEmail = '';
    public string $profilePhone = '';
    public string $profileCompany = '';
    public string $profileTaxId = '';
    public string $profileAddress = '';
    public string $profileCity = '';
    public string $profileState = '';
    public string $profilePostalCode = '';
    public string $profileCountry = '';

    /* ───────── Password form ───────── */
    public string $currentPassword = '';
    public string $newPassword = '';
    public string $confirmPassword = '';

    /* ───────── Profile photo ───────── */
    public $profilePhoto = null;
    public bool $profilePhotoUploading = false;

    /* ───────── Activity stats ───────── */
    public int $totalJobs = 0;
    public int $totalEstimates = 0;
    public string $lifetimeValue = '0.00';

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

    /* ─────────── Profile ─────────── */

    public function loadProfileData(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $this->profileFirstName = (string) ($user->first_name ?? '');
        $this->profileLastName = (string) ($user->last_name ?? '');
        $this->profileEmail = (string) ($user->email ?? '');
        $this->profilePhone = (string) ($user->phone ?? '');
        $this->profileCompany = (string) ($user->company ?? '');
        $this->profileTaxId = (string) ($user->tax_id ?? '');
        $this->profileAddress = (string) ($user->address_line1 ?? '');
        $this->profileCity = (string) ($user->address_city ?? '');
        $this->profileState = (string) ($user->address_state ?? '');
        $this->profilePostalCode = (string) ($user->address_postal_code ?? '');
        $this->profileCountry = (string) ($user->address_country_code ?? '');

        $this->loadActivityStats();
    }

    public function updateProfile(): void
    {
        $this->resetMessages();

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $this->validate([
            'profileFirstName' => ['required', 'string', 'max:100'],
            'profileLastName' => ['nullable', 'string', 'max:100'],
            'profileEmail' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'profilePhone' => ['nullable', 'string', 'max:30'],
            'profileCompany' => ['nullable', 'string', 'max:150'],
            'profileTaxId' => ['nullable', 'string', 'max:50'],
            'profileAddress' => ['nullable', 'string', 'max:255'],
            'profileCity' => ['nullable', 'string', 'max:100'],
            'profileState' => ['nullable', 'string', 'max:100'],
            'profilePostalCode' => ['nullable', 'string', 'max:20'],
            'profileCountry' => ['nullable', 'string', 'max:2'],
        ]);

        $user->update([
            'first_name' => $this->profileFirstName,
            'last_name' => $this->profileLastName,
            'name' => trim($this->profileFirstName . ' ' . $this->profileLastName),
            'email' => $this->profileEmail,
            'phone' => $this->profilePhone,
            'company' => $this->profileCompany,
            'tax_id' => $this->profileTaxId,
            'address_line1' => $this->profileAddress,
            'address_city' => $this->profileCity,
            'address_state' => $this->profileState,
            'address_postal_code' => $this->profilePostalCode,
            'address_country_code' => $this->profileCountry,
        ]);

        $this->successMessage = 'Profile updated successfully!';
    }

    public function updatePassword(): void
    {
        $this->resetMessages();

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $this->validate([
            'currentPassword' => ['required', 'string'],
            'newPassword' => ['required', 'string', 'min:8'],
            'confirmPassword' => ['required', 'string', 'same:newPassword'],
        ]);

        if (! Hash::check($this->currentPassword, $user->password)) {
            $this->addError('currentPassword', 'Current password is incorrect.');
            return;
        }

        $user->update([
            'password' => Hash::make($this->newPassword),
        ]);

        $this->currentPassword = '';
        $this->newPassword = '';
        $this->confirmPassword = '';

        $this->successMessage = 'Password updated successfully!';
    }

    public function updatedProfilePhoto(): void
    {
        $this->resetMessages();

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $this->validate([
            'profilePhoto' => ['nullable', 'image', 'max:2048', 'dimensions:max_width=2000,max_height=2000'],
        ]);

        if ($this->profilePhoto) {
            // Delete old avatar if exists
            if ($user->avatar_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar_path);
            }

            // Store new avatar
            $path = $this->profilePhoto->store('avatars', 'public');
            $user->update(['avatar_path' => $path]);

            $this->successMessage = 'Profile photo updated successfully!';
        }

        $this->profilePhoto = null;
    }

    public function loadActivityStats(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $this->totalJobs = RepairBuddyJob::where('customer_id', $user->id)->count();
        $this->totalEstimates = \App\Models\RepairBuddyEstimate::where('customer_id', $user->id)->count();

        // Calculate lifetime value from job items (unit_price_amount_cents * qty)
        $lifetimeCents = \DB::table('rb_job_items')
            ->join('rb_jobs', 'rb_job_items.job_id', '=', 'rb_jobs.id')
            ->where('rb_jobs.customer_id', $user->id)
            ->selectRaw('SUM(rb_job_items.unit_price_amount_cents * rb_job_items.qty) as total')
            ->value('total');

        $this->lifetimeValue = number_format(($lifetimeCents ?? 0) / 100, 2, '.', ',');
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
