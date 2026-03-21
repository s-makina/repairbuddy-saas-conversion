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

    /* ───────── Reviews data ───────── */
    public array $reviews = [];
    public array $reviewStats = ['total' => 0, 'avg' => 0, '5' => 0, '4' => 0, '3' => 0, '2' => 0, '1' => 0];
    public bool $reviewsLoaded = false;

    /* ───────── Jobs data ───────── */
    public array $jobsList = [];
    public array $jobsStatusCards = [];
    public string $jobsSearch = '';
    public string $jobsStatusFilter = '';
    public string $jobsPriorityFilter = '';
    public int $jobsPage = 1;
    public bool $jobsLoaded = false;
    public int $jobsPerPage = 20;

    /* ───────── Estimates data ───────── */
    public array $estimatesList = [];
    public array $estimatesStatusCards = [];
    public string $estimatesSearch = '';
    public string $estimatesStatusFilter = '';
    public int $estimatesPage = 1;
    public bool $estimatesLoaded = false;
    public int $estimatesPerPage = 20;

    /* ───────── Devices data ───────── */
    public array $devicesList = [];
    public int $devicesTotal = 0;
    public string $devicesSearch = '';
    public int $devicesPage = 1;
    public bool $devicesLoaded = false;
    public int $devicesPerPage = 20;
    public bool $showAddDeviceModal = false;
    public int $editingDeviceId = 0;
    public string $deviceName = '';
    public string $deviceTypeId = '';
    public string $deviceBrandId = '';
    public string $deviceIdentifier = '';
    public string $devicePinCode = '';
    public string $deviceNotes = '';

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

        $user = Auth::user();

        // If non-customer role, redirect to dashboard
        if ($user && $user->role !== 'customer') {
            $rp = $this->tenantId ? 'tenant.' : '';
            $this->redirect(route($rp . 'dashboard', ['business' => $this->business]));
            return;
        }

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

    /* ─────────── Reviews ─────────── */

    public function loadReviewsData(): void
    {
        if ($this->reviewsLoaded) {
            return;
        }

        $user = Auth::user();
        if (! $user) {
            return;
        }

        // Load reviews with job data
        $reviews = \App\Models\RepairBuddyReview::query()
            ->where('customer_id', $user->id)
            ->where('is_published', true)
            ->with(['job:id,case_number,title,opened_at'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $this->reviews = $reviews->map(function ($review) {
            return [
                'id' => $review->id,
                'rating' => $review->rating,
                'feedback' => $review->feedback,
                'created_at' => $review->created_at?->format('M d, Y'),
                'job_case_number' => $review->job?->case_number ?? '-',
                'job_title' => $review->job?->title ?? '-',
                'job_opened_at' => $review->job?->opened_at?->format('M d, Y') ?? '-',
            ];
        })->toArray();

        // Calculate stats
        $allReviews = \App\Models\RepairBuddyReview::where('customer_id', $user->id)
            ->where('is_published', true)
            ->get();

        $this->reviewStats['total'] = $allReviews->count();
        $this->reviewStats['avg'] = $allReviews->count() > 0
            ? round($allReviews->avg('rating'), 1)
            : 0;

        foreach ([5, 4, 3, 2, 1] as $rating) {
            $this->reviewStats[$rating] = $allReviews->where('rating', $rating)->count();
        }

        $this->reviewsLoaded = true;
    }

    /* ─────────── Jobs List ─────────── */

    public function loadJobsData(): void
    {
        if ($this->jobsLoaded) {
            return;
        }

        $user = Auth::user();
        if (! $user) {
            return;
        }

        // Build status cards
        $statusCounts = RepairBuddyJob::where('customer_id', $user->id)
            ->selectRaw('status_slug, COUNT(*) as count')
            ->groupBy('status_slug')
            ->pluck('count', 'status_slug')
            ->toArray();

        $this->jobsStatusCards = [];
        foreach ($statusCounts as $slug => $count) {
            $this->jobsStatusCards[] = [
                'slug' => $slug,
                'name' => ucfirst(str_replace(['-', '_'], ' ', $slug)),
                'count' => $count,
            ];
        }

        // Build query
        $query = RepairBuddyJob::where('customer_id', $user->id)
            ->with(['jobDevices.deviceType', 'jobDevices.deviceBrand']);

        if ($this->jobsSearch) {
            $search = $this->jobsSearch;
            $query->where(function ($q) use ($search) {
                $q->where('case_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('job_number', 'like', "%{$search}%");
            });
        }

        if ($this->jobsStatusFilter) {
            $query->where('status_slug', $this->jobsStatusFilter);
        }

        if ($this->jobsPriorityFilter) {
            $query->where('priority', $this->jobsPriorityFilter);
        }

        $total = $query->count();
        $jobs = $query->orderByDesc('opened_at')
            ->offset(($this->jobsPage - 1) * $this->jobsPerPage)
            ->limit($this->jobsPerPage)
            ->get();

        $this->jobsList = $jobs->map(function ($job) {
            $devices = $job->jobDevices->map(function ($jd) {
                return trim(($jd->deviceType?->name ?? '') . ' ' . ($jd->deviceBrand?->name ?? ''));
            })->filter()->implode(', ');

            return [
                'id' => $job->id,
                'job_number' => $job->job_number ?? $job->id,
                'case_number' => $job->case_number ?? '-',
                'title' => $job->title ?? '-',
                'devices' => $devices ?: '-',
                'opened_at' => $job->opened_at?->format('M d, Y') ?? '-',
                'closed_at' => $job->closed_at?->format('M d, Y'),
                'status_slug' => $job->status_slug ?? 'in-progress',
                'priority' => $job->priority ?? 'normal',
                'payment_status' => $job->payment_status_slug ?? '-',
            ];
        })->toArray();

        $this->jobsLoaded = true;
    }

    public function applyJobsFilters(): void
    {
        $this->jobsPage = 1;
        $this->jobsLoaded = false;
        $this->loadJobsData();
    }

    public function clearJobsFilters(): void
    {
        $this->jobsSearch = '';
        $this->jobsStatusFilter = '';
        $this->jobsPriorityFilter = '';
        $this->jobsPage = 1;
        $this->jobsLoaded = false;
        $this->loadJobsData();
    }

    /* ─────────── Estimates List ─────────── */

    public function loadEstimatesData(): void
    {
        if ($this->estimatesLoaded) {
            return;
        }

        $user = Auth::user();
        if (! $user) {
            return;
        }

        // Build status cards
        $statusCounts = \App\Models\RepairBuddyEstimate::where('customer_id', $user->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $this->estimatesStatusCards = [
            ['slug' => 'pending', 'name' => 'Pending', 'count' => $statusCounts['pending'] ?? 0, 'color' => 'info'],
            ['slug' => 'approved', 'name' => 'Approved', 'count' => $statusCounts['approved'] ?? 0, 'color' => 'success'],
            ['slug' => 'rejected', 'name' => 'Rejected', 'count' => $statusCounts['rejected'] ?? 0, 'color' => 'danger'],
        ];

        // Build query
        $query = \App\Models\RepairBuddyEstimate::where('customer_id', $user->id);

        if ($this->estimatesSearch) {
            $search = $this->estimatesSearch;
            $query->where(function ($q) use ($search) {
                $q->where('case_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        if ($this->estimatesStatusFilter) {
            $query->where('status', $this->estimatesStatusFilter);
        }

        $estimates = $query->orderByDesc('created_at')
            ->offset(($this->estimatesPage - 1) * $this->estimatesPerPage)
            ->limit($this->estimatesPerPage)
            ->get();

        $this->estimatesList = $estimates->map(function ($est) {
            return [
                'id' => $est->id,
                'case_number' => $est->case_number ?? '-',
                'title' => $est->title ?? '-',
                'created_at' => $est->created_at?->format('M d, Y') ?? '-',
                'status' => $est->status ?? 'pending',
                'total' => isset($est->total_cents) ? number_format($est->total_cents / 100, 2) : '-',
            ];
        })->toArray();

        $this->estimatesLoaded = true;
    }

    public function applyEstimatesFilters(): void
    {
        $this->estimatesPage = 1;
        $this->estimatesLoaded = false;
        $this->loadEstimatesData();
    }

    public function clearEstimatesFilters(): void
    {
        $this->estimatesSearch = '';
        $this->estimatesStatusFilter = '';
        $this->estimatesPage = 1;
        $this->estimatesLoaded = false;
        $this->loadEstimatesData();
    }

    /* ─────────── Devices List ─────────── */

    public function loadDevicesData(): void
    {
        if ($this->devicesLoaded) {
            return;
        }

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $query = \App\Models\RepairBuddyCustomerDevice::where('customer_id', $user->id)
            ->with(['deviceType', 'deviceBrand']);

        if ($this->devicesSearch) {
            $search = $this->devicesSearch;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('identifier', 'like', "%{$search}%");
            });
        }

        $this->devicesTotal = $query->count();

        $devices = $query->orderByDesc('created_at')
            ->offset(($this->devicesPage - 1) * $this->devicesPerPage)
            ->limit($this->devicesPerPage)
            ->get();

        $this->devicesList = $devices->map(function ($dev) {
            return [
                'id' => $dev->id,
                'name' => $dev->name ?? '-',
                'type' => $dev->deviceType?->name ?? '-',
                'brand' => $dev->deviceBrand?->name ?? '-',
                'identifier' => $dev->identifier ?? '-',
                'pin_code' => $dev->pin_code ?? '-',
                'notes' => $dev->notes ?? '-',
            ];
        })->toArray();

        $this->devicesLoaded = true;
    }

    public function openAddDeviceModal(): void
    {
        $this->resetDeviceForm();
        $this->showAddDeviceModal = true;
    }

    public function closeAddDeviceModal(): void
    {
        $this->showAddDeviceModal = false;
        $this->resetDeviceForm();
    }

    private function resetDeviceForm(): void
    {
        $this->editingDeviceId = 0;
        $this->deviceName = '';
        $this->deviceTypeId = '';
        $this->deviceBrandId = '';
        $this->deviceIdentifier = '';
        $this->devicePinCode = '';
        $this->deviceNotes = '';
    }

    public function saveDevice(): void
    {
        $this->validate([
            'deviceName' => 'required|string|max:255',
            'deviceTypeId' => 'nullable|exists:rb_device_types,id',
            'deviceBrandId' => 'nullable|exists:rb_device_brands,id',
            'deviceIdentifier' => 'nullable|string|max:100',
            'devicePinCode' => 'nullable|string|max:50',
            'deviceNotes' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $data = [
            'tenant_id' => $this->tenantId,
            'customer_id' => $user->id,
            'name' => $this->deviceName,
            'device_type_id' => $this->deviceTypeId ?: null,
            'device_brand_id' => $this->deviceBrandId ?: null,
            'identifier' => $this->deviceIdentifier,
            'pin_code' => $this->devicePinCode,
            'notes' => $this->deviceNotes,
        ];

        if ($this->editingDeviceId) {
            \App\Models\RepairBuddyCustomerDevice::where('id', $this->editingDeviceId)
                ->where('customer_id', $user->id)
                ->update($data);
            $this->successMessage = 'Device updated successfully.';
        } else {
            \App\Models\RepairBuddyCustomerDevice::create($data);
            $this->successMessage = 'Device added successfully.';
        }

        $this->closeAddDeviceModal();
        $this->devicesLoaded = false;
        $this->loadDevicesData();
    }

    public function editDevice(int $id): void
    {
        $device = \App\Models\RepairBuddyCustomerDevice::where('id', $id)
            ->where('customer_id', Auth::id())
            ->first();

        if (! $device) {
            $this->errorMessage = 'Device not found.';
            return;
        }

        $this->editingDeviceId = $device->id;
        $this->deviceName = $device->name ?? '';
        $this->deviceTypeId = $device->device_type_id ?? '';
        $this->deviceBrandId = $device->device_brand_id ?? '';
        $this->deviceIdentifier = $device->identifier ?? '';
        $this->devicePinCode = $device->pin_code ?? '';
        $this->deviceNotes = $device->notes ?? '';
        $this->showAddDeviceModal = true;
    }

    public function deleteDevice(int $id): void
    {
        $device = \App\Models\RepairBuddyCustomerDevice::where('id', $id)
            ->where('customer_id', Auth::id())
            ->first();

        if (! $device) {
            $this->errorMessage = 'Device not found.';
            return;
        }

        $device->delete();
        $this->successMessage = 'Device deleted successfully.';
        $this->devicesLoaded = false;
        $this->loadDevicesData();
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
