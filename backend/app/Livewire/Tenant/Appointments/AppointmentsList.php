<?php

namespace App\Livewire\Tenant\Appointments;

use App\Models\Branch;
use App\Models\RepairBuddyAppointment;
use App\Models\RepairBuddyAppointmentSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Livewire\Component;
use Livewire\WithPagination;

class AppointmentsList extends Component
{
    use WithPagination;

    public $tenant;

    public string $search = '';
    public string $statusFilter = 'all';
    public string $dateFilter = '';
    public string $typeFilter = 'all';
    public string $branchFilter = 'all';

    public bool $showCancelModal = false;
    public bool $showRescheduleModal = false;
    public ?int $selectedAppointmentId = null;
    public string $cancellationReason = '';

    public string $rescheduleDate = '';
    public string $rescheduleTime = '';
    public array $availableTimeSlots = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
        'dateFilter' => ['except' => ''],
        'typeFilter' => ['except' => 'all'],
    ];

    public function mount($tenant): void
    {
        $this->tenant = $tenant;
    }

    public function hydrate(): void
    {
        if ($this->tenant instanceof Tenant && is_int($this->tenant->id)) {
            TenantContext::set($this->tenant);
            $branch = $this->tenant->defaultBranch;
            if ($branch) {
                BranchContext::set($branch);
            }
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function openCancelModal(int $appointmentId): void
    {
        $this->selectedAppointmentId = $appointmentId;
        $this->cancellationReason = '';
        $this->showCancelModal = true;
    }

    public function closeCancelModal(): void
    {
        $this->showCancelModal = false;
        $this->selectedAppointmentId = null;
        $this->cancellationReason = '';
    }

    public function cancelAppointment(): void
    {
        if (! $this->selectedAppointmentId) {
            return;
        }

        $appointment = RepairBuddyAppointment::query()
            ->where('id', $this->selectedAppointmentId)
            ->where('tenant_id', TenantContext::tenantId())
            ->first();

        if (! $appointment) {
            $this->closeCancelModal();
            return;
        }

        $appointment->cancel($this->cancellationReason);

        $this->closeCancelModal();
        $this->resetPage();
    }

    public function confirmAppointment(int $appointmentId): void
    {
        $appointment = RepairBuddyAppointment::query()
            ->where('id', $appointmentId)
            ->where('tenant_id', TenantContext::tenantId())
            ->first();

        if ($appointment && $appointment->status === RepairBuddyAppointment::STATUS_SCHEDULED) {
            $appointment->confirm();
        }

        $this->resetPage();
    }

    public function markCompleted(int $appointmentId): void
    {
        $appointment = RepairBuddyAppointment::query()
            ->where('id', $appointmentId)
            ->where('tenant_id', TenantContext::tenantId())
            ->first();

        if ($appointment && in_array($appointment->status, [RepairBuddyAppointment::STATUS_SCHEDULED, RepairBuddyAppointment::STATUS_CONFIRMED])) {
            $appointment->complete();
        }

        $this->resetPage();
    }

    public function markNoShow(int $appointmentId): void
    {
        $appointment = RepairBuddyAppointment::query()
            ->where('id', $appointmentId)
            ->where('tenant_id', TenantContext::tenantId())
            ->first();

        if ($appointment && in_array($appointment->status, [RepairBuddyAppointment::STATUS_SCHEDULED, RepairBuddyAppointment::STATUS_CONFIRMED])) {
            $appointment->markNoShow();
        }

        $this->resetPage();
    }

    public function openRescheduleModal(int $appointmentId): void
    {
        $appointment = RepairBuddyAppointment::query()
            ->where('id', $appointmentId)
            ->where('tenant_id', TenantContext::tenantId())
            ->first();

        if (! $appointment) {
            return;
        }

        $this->selectedAppointmentId = $appointmentId;
        $this->rescheduleDate = $appointment->appointment_date->format('Y-m-d');
        $this->rescheduleTime = $appointment->time_slot_start->format('H:i');
        $this->loadAvailableTimeSlots();
        $this->showRescheduleModal = true;
    }

    public function closeRescheduleModal(): void
    {
        $this->showRescheduleModal = false;
        $this->selectedAppointmentId = null;
        $this->rescheduleDate = '';
        $this->rescheduleTime = '';
        $this->availableTimeSlots = [];
    }

    public function updatedRescheduleDate(): void
    {
        $this->loadAvailableTimeSlots();
    }

    private function loadAvailableTimeSlots(): void
    {
        $this->availableTimeSlots = [];

        if (! $this->rescheduleDate) {
            return;
        }

        $settings = RepairBuddyAppointmentSetting::query()
            ->where('tenant_id', TenantContext::tenantId())
            ->where('branch_id', BranchContext::branchId())
            ->where('is_enabled', true)
            ->get();

        foreach ($settings as $setting) {
            $duration = $setting->slot_duration_minutes ?? 30;
            $dayOfWeek = strtolower(\Carbon\Carbon::parse($this->rescheduleDate)->englishDayOfWeek);

            $slots = is_array($setting->time_slots) ? $setting->time_slots : [];
            foreach ($slots as $slot) {
                if (($slot['day'] ?? '') === $dayOfWeek && ($slot['enabled'] ?? false)) {
                    $start = \Carbon\Carbon::parse($slot['start']);
                    $end = \Carbon\Carbon::parse($slot['end']);

                    while ($start < $end) {
                        $this->availableTimeSlots[] = [
                            'value' => $start->format('H:i'),
                            'label' => $start->format('H:i') . ' - ' . $start->copy()->addMinutes($duration)->format('H:i'),
                            'type' => $setting->title,
                        ];
                        $start->addMinutes($duration);
                    }
                }
            }
        }
    }

    public function rescheduleAppointment(): void
    {
        if (! $this->selectedAppointmentId || ! $this->rescheduleDate || ! $this->rescheduleTime) {
            return;
        }

        $appointment = RepairBuddyAppointment::query()
            ->where('id', $this->selectedAppointmentId)
            ->where('tenant_id', TenantContext::tenantId())
            ->first();

        if (! $appointment) {
            $this->closeRescheduleModal();
            return;
        }

        $setting = $appointment->appointmentSetting;
        $duration = $setting?->slot_duration_minutes ?? 30;

        $timeSlotStart = \Carbon\Carbon::parse($this->rescheduleTime);
        $timeSlotEnd = $timeSlotStart->copy()->addMinutes($duration);

        $appointment->update([
            'appointment_date' => $this->rescheduleDate,
            'time_slot_start' => $timeSlotStart->format('H:i:s'),
            'time_slot_end' => $timeSlotEnd->format('H:i:s'),
            'status' => RepairBuddyAppointment::STATUS_SCHEDULED,
            'confirmed_at' => null,
        ]);

        $this->closeRescheduleModal();
        $this->resetPage();
    }

    public function render()
    {
        $tenantId = TenantContext::tenantId();
        $branchId = BranchContext::branchId();

        $query = RepairBuddyAppointment::query()
            ->with(['customer', 'appointmentSetting', 'job', 'estimate', 'branch'])
            ->where('tenant_id', $tenantId);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($this->search !== '') {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('job', fn ($jq) => $jq->where('case_number', 'like', "%{$search}%"))
                    ->orWhereHas('estimate', fn ($eq) => $eq->where('case_number', 'like', "%{$search}%"));
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->dateFilter !== '') {
            $query->where('appointment_date', $this->dateFilter);
        }

        if ($this->typeFilter !== 'all') {
            $query->where('appointment_setting_id', (int) $this->typeFilter);
        }

        $appointments = $query->orderBy('appointment_date')
            ->orderBy('time_slot_start')
            ->paginate(20);

        $appointmentTypes = RepairBuddyAppointmentSetting::query()
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->where('is_enabled', true)
            ->orderBy('title')
            ->get(['id', 'title']);

        $statusOptions = [
            'all' => __('All Statuses'),
            RepairBuddyAppointment::STATUS_SCHEDULED => __('Scheduled'),
            RepairBuddyAppointment::STATUS_CONFIRMED => __('Confirmed'),
            RepairBuddyAppointment::STATUS_COMPLETED => __('Completed'),
            RepairBuddyAppointment::STATUS_CANCELLED => __('Cancelled'),
            RepairBuddyAppointment::STATUS_NO_SHOW => __('No Show'),
        ];

        return view('livewire.tenant.appointments.appointments-list', [
            'appointments' => $appointments,
            'appointmentTypes' => $appointmentTypes,
            'statusOptions' => $statusOptions,
        ]);
    }
}
