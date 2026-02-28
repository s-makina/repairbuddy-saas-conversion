<?php

namespace App\Livewire\Tenant\Appointments;

use App\Models\Branch;
use App\Models\RepairBuddyAppointment;
use App\Models\RepairBuddyAppointmentSetting;
use App\Models\RepairBuddyEstimate;
use App\Models\RepairBuddyJob;
use App\Models\RepairBuddyEvent;
use App\Models\User;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AppointmentForm extends Component
{
    public $tenant;
    public $user;

    // Form properties
    public ?int $customer_id = null;
    public ?int $technician_id = null;
    public ?int $appointment_setting_id = null;
    public ?string $appointment_date = null;
    public ?string $time_slot = null;
    public ?int $job_id = null;
    public ?int $estimate_id = null;
    public ?string $notes = null;

    // Search properties
    public string $customer_search = '';
    public string $technician_search = '';
    public string $job_search = '';
    public string $estimate_search = '';

    // Link type selector
    public string $link_type = 'none'; // 'none', 'job', 'estimate'

    // Available options
    public $appointmentTypes = [];
    public $availableTimeSlots = [];

    // Selected entities for display
    public $selected_customer = null;
    public $selected_technician = null;
    public $selected_job = null;
    public $selected_estimate = null;

    protected $listeners = [
        'customerCreated' => 'handleCustomerCreated',
        'technicianCreated' => 'handleTechnicianCreated',
    ];

    public function mount($tenant, $user): void
    {
        $this->tenant = $tenant;
        $this->user = $user;

        // Set tenant context
        if ($tenant instanceof \App\Models\Tenant) {
            TenantContext::set($tenant);
            $branch = $tenant->defaultBranch ?? $tenant->branches->first();
            if ($branch) {
                BranchContext::set($branch);
            }
        }

        // Load enabled appointment types
        $this->appointmentTypes = RepairBuddyAppointmentSetting::query()
            ->where('tenant_id', (int) $tenant->id)
            ->where('is_enabled', true)
            ->orderBy('title')
            ->get();
    }

    public function hydrate(): void
    {
        if ($this->tenant instanceof \App\Models\Tenant) {
            TenantContext::set($this->tenant);
            $branch = $this->tenant->defaultBranch ?? $this->tenant->branches->first();
            if ($branch) {
                BranchContext::set($branch);
            }
        }
    }

    // Computed property for filtered customers
    public function getFilteredCustomersProperty()
    {
        $search = trim($this->customer_search);
        
        $query = User::query()
            ->where('tenant_id', (int) $this->tenant->id)
            ->where('role', 'customer');

        if (strlen($search) >= 2) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name')
            ->limit(strlen($search) >= 2 ? 10 : 5)
            ->get();
    }

    // Computed property for filtered technicians
    public function getFilteredTechniciansProperty()
    {
        $search = trim($this->technician_search);
        
        $query = User::query()
            ->where('tenant_id', (int) $this->tenant->id)
            ->where('is_admin', false)
            ->where('status', 'active')
            ->where(function ($q) {
                // The legacy `role` column is NULL for staff/technicians created via
                // UserController (role = null), but 'customer' for customer accounts.
                $q->whereNull('role')
                    ->orWhere('role', '!=', 'customer');
            });

        if ($this->technician_id) {
            $query->where('id', '!=', $this->technician_id);
        }

        if (strlen($search) >= 2) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name')
            ->limit(strlen($search) >= 2 ? 10 : 5)
            ->get();
    }

    // Computed property for filtered jobs
    public function getFilteredJobsProperty()
    {
        $search = trim($this->job_search);
        if (strlen($search) < 2) {
            return collect();
        }

        return RepairBuddyJob::query()
            ->where('tenant_id', (int) $this->tenant->id)
            ->where(function ($q) use ($search) {
                $q->where('case_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            })
            ->orderByDesc('id')
            ->limit(20)
            ->get();
    }

    // Computed property for filtered estimates
    public function getFilteredEstimatesProperty()
    {
        $search = trim($this->estimate_search);
        if (strlen($search) < 2) {
            return collect();
        }

        return RepairBuddyEstimate::query()
            ->where('tenant_id', (int) $this->tenant->id)
            ->where(function ($q) use ($search) {
                $q->where('case_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            })
            ->orderByDesc('id')
            ->limit(20)
            ->get();
    }

    public function selectCustomer(int $id): void
    {
        $this->customer_id = $id;
        $this->selected_customer = User::find($id);
        $this->customer_search = '';
    }

    public function removeCustomer(): void
    {
        $this->customer_id = null;
        $this->selected_customer = null;
        $this->customer_search = '';
    }

    public function selectTechnician(int $id): void
    {
        $this->technician_id = $id;
        $this->selected_technician = User::find($id);
        $this->technician_search = '';
    }

    public function removeTechnician(): void
    {
        $this->technician_id = null;
        $this->selected_technician = null;
        $this->technician_search = '';
    }

    public function selectJob(int $id): void
    {
        $this->job_id = $id;
        $this->estimate_id = null;
        $this->selected_job = RepairBuddyJob::find($id);
        $this->selected_estimate = null;
        $this->job_search = '';
    }

    public function removeJob(): void
    {
        $this->job_id = null;
        $this->selected_job = null;
        $this->job_search = '';
    }

    public function selectEstimate(int $id): void
    {
        $this->estimate_id = $id;
        $this->job_id = null;
        $this->selected_estimate = RepairBuddyEstimate::find($id);
        $this->selected_job = null;
        $this->estimate_search = '';
    }

    public function removeEstimate(): void
    {
        $this->estimate_id = null;
        $this->selected_estimate = null;
        $this->estimate_search = '';
    }

    public function updatedAppointmentSettingId(): void
    {
        $this->loadTimeSlots();
    }

    public function updatedAppointmentDate(): void
    {
        $this->loadTimeSlots();
    }

    public function loadTimeSlots(): void
    {
        $this->availableTimeSlots = [];

        if (!$this->appointment_setting_id || !$this->appointment_date) {
            \Log::debug('AppointmentForm: Missing setting_id or date', [
                'setting_id' => $this->appointment_setting_id,
                'date' => $this->appointment_date,
            ]);
            return;
        }

        $setting = RepairBuddyAppointmentSetting::find($this->appointment_setting_id);
        if (!$setting || !$setting->is_enabled) {
            \Log::debug('AppointmentForm: Setting not found or not enabled', [
                'setting_id' => $this->appointment_setting_id,
                'found' => $setting ? true : false,
                'is_enabled' => $setting?->is_enabled,
            ]);
            return;
        }

        $branch = BranchContext::branch();
        if (!$branch) {
            \Log::debug('AppointmentForm: No branch context');
            // Continue anyway - branch is optional for time slot generation
        }

        $date = Carbon::parse($this->appointment_date);
        $dayOfWeek = strtolower($date->englishDayOfWeek); // 'monday', 'tuesday', etc.

        // Get time slots from setting (stored as array)
        $timeSlots = is_array($setting->time_slots) ? $setting->time_slots : [];
        
        \Log::debug('AppointmentForm: Time slots data', [
            'dayOfWeek' => $dayOfWeek,
            'timeSlots' => $timeSlots,
            'count' => count($timeSlots),
        ]);

        // Filter by day of week and enabled status
        $todaySlot = collect($timeSlots)
            ->where('enabled', true)
            ->firstWhere('day', $dayOfWeek);

        if (!$todaySlot) {
            \Log::debug('AppointmentForm: No matching slot for day', [
                'dayOfWeek' => $dayOfWeek,
                'available_days' => collect($timeSlots)->pluck('day')->toArray(),
            ]);
            return;
        }

        \Log::debug('AppointmentForm: Found matching slot', ['todaySlot' => $todaySlot]);

        // Generate time slots from start to end
        $duration = is_numeric($setting->slot_duration_minutes) ? (int) $setting->slot_duration_minutes : 30;
        $buffer = is_numeric($setting->buffer_minutes) ? (int) $setting->buffer_minutes : 0;

        $startStr = $todaySlot['start'] ?? null;
        $endStr = $todaySlot['end'] ?? null;

        if (!$startStr || !$endStr) {
            \Log::debug('AppointmentForm: Missing start/end', [
                'start' => $startStr,
                'end' => $endStr,
            ]);
            return;
        }

        // Parse start and end times
        $startMinutes = intval(substr($startStr, 0, 2)) * 60 + intval(substr($startStr, 3, 2));
        $endMinutes = intval(substr($endStr, 0, 2)) * 60 + intval(substr($endStr, 3, 2));

        $formattedSlots = [];
        $current = $startMinutes;

        while ($current + $duration <= $endMinutes) {
            $h = str_pad((string) intdiv($current, 60), 2, '0', STR_PAD_LEFT);
            $m = str_pad((string) ($current % 60), 2, '0', STR_PAD_LEFT);
            $slotTime = "{$h}:{$m}";

            // Check if slot is in the past for today
            if ($date->isToday()) {
                $now = now();
                $slotDateTime = $date->copy()->setTime($h, $m);
                if ($slotDateTime->lt($now)) {
                    $current += $duration + $buffer;
                    continue;
                }
            }

            // Check capacity for this appointment type
            $existingCount = RepairBuddyAppointment::query()
                ->where('appointment_setting_id', $this->appointment_setting_id)
                ->where('appointment_date', $this->appointment_date)
                ->where('time_slot_start', $slotTime . ':00')
                ->whereNotIn('status', [RepairBuddyAppointment::STATUS_CANCELLED])
                ->count();

            if ($setting->max_appointments_per_day && $existingCount >= $setting->max_appointments_per_day) {
                $current += $duration + $buffer;
                continue;
            }

            $slotEnd = $current + $duration;
            $endH = str_pad((string) intdiv($slotEnd, 60), 2, '0', STR_PAD_LEFT);
            $endM = str_pad((string) ($slotEnd % 60), 2, '0', STR_PAD_LEFT);

            $formattedSlots[] = [
                'value' => $slotTime,
                'label' => "{$h}:{$m} - {$endH}:{$endM}",
            ];

            $current += $duration + $buffer;
        }

        \Log::debug('AppointmentForm: Generated slots', ['count' => count($formattedSlots), 'slots' => $formattedSlots]);
        
        $this->availableTimeSlots = $formattedSlots;
    }

    protected function generateDefaultSlots(RepairBuddyAppointmentSetting $setting): array
    {
        // Generate slots from 9 AM to 5 PM with configured duration
        $duration = is_numeric($setting->slot_duration_minutes) ? (int) $setting->slot_duration_minutes : 30;
        $slots = [];
        $start = Carbon::parse('09:00');
        $end = Carbon::parse('17:00');

        while ($start->lt($end)) {
            $slots[] = $start->format('H:i');
            $start->addMinutes($duration);
        }

        return $slots;
    }

    public function handleCustomerCreated(array $data): void
    {
        if (isset($data['id'])) {
            $this->selectCustomer($data['id']);
        }
    }

    public function handleTechnicianCreated(array $data): void
    {
        if (isset($data['id'])) {
            $this->selectTechnician($data['id']);
        }
    }

    protected function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:users,id'],
            'technician_id' => ['required', 'integer', 'exists:users,id'],
            'appointment_setting_id' => ['required', 'integer', 'exists:repairbuddy_appointment_settings,id'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'time_slot' => ['required', 'string'],
            'job_id' => ['nullable', 'integer', 'exists:repairbuddy_jobs,id'],
            'estimate_id' => ['nullable', 'integer', 'exists:repairbuddy_estimates,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function save()
    {
        $this->validate();

        $branch = BranchContext::branch();
        if (!$branch instanceof Branch) {
            abort(400, 'Tenant or branch context is missing.');
        }

        $setting = RepairBuddyAppointmentSetting::find($this->appointment_setting_id);
        if (!$setting || !$setting->is_enabled) {
            $this->addError('appointment_setting_id', __('Invalid appointment type.'));
            return;
        }

        $appointment = DB::transaction(function () use ($branch, $setting) {
            $duration = is_numeric($setting->slot_duration_minutes) ? (int) $setting->slot_duration_minutes : 30;
            $timeSlotStart = Carbon::parse($this->time_slot);
            $timeSlotEnd = $timeSlotStart->copy()->addMinutes($duration);

            $appointment = RepairBuddyAppointment::query()->create([
                'tenant_id' => (int) $this->tenant->id,
                'branch_id' => (int) $branch->id,
                'appointment_setting_id' => $this->appointment_setting_id,
                'customer_id' => $this->customer_id,
                'technician_id' => $this->technician_id,
                'job_id' => $this->job_id,
                'estimate_id' => $this->estimate_id,
                'title' => $setting->title,
                'appointment_date' => $this->appointment_date,
                'time_slot_start' => $timeSlotStart->format('H:i:s'),
                'time_slot_end' => $timeSlotEnd->format('H:i:s'),
                'status' => RepairBuddyAppointment::STATUS_CONFIRMED, // Auto-confirm staff-created
                'notes' => $this->notes,
                'created_by' => $this->user->id,
            ]);

            // Audit event
            RepairBuddyEvent::query()->create([
                'actor_user_id' => $this->user->id,
                'entity_type' => 'appointment',
                'entity_id' => $appointment->id,
                'visibility' => 'private',
                'event_type' => 'appointment.created',
                'payload_json' => [
                    'title' => 'Appointment created',
                    'date' => $this->appointment_date,
                    'time' => $this->time_slot,
                    'customer_id' => $this->customer_id,
                    'technician_id' => $this->technician_id,
                ],
            ]);

            return $appointment;
        });

        return redirect()->route('tenant.appointments.index', [
            'business' => $this->tenant->slug,
        ])->with('success', __('Appointment created successfully.'));
    }

    public function render()
    {
        return view('livewire.tenant.appointments.appointment-form');
    }
}
