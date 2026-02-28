<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\RepairBuddyAppointmentSetting;
use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Livewire\Component;

class AppointmentSettings extends Component
{
    public $tenant;

    /* ─── Appointment Settings ───────────────────── */
    public bool $enable_appointments = false;
    public string $default_duration = '60';
    public string $business_hours_start = '09:00';
    public string $business_hours_end = '17:00';
    public bool $allow_online_booking = false;
    public int $buffer_time = 15;
    public string $confirmation_email_subject = '';
    public string $confirmation_email_body = '';
    public string $reminder_email_subject = '';
    public string $reminder_email_body = '';
    public int $reminder_hours_before = 24;

    /* ─── Duration options ───────────────────────── */
    public array $durationOptions = [];

    /* ─── Appointment Types ──────────────────────── */
    public array $appointmentTypes = [];
    public bool $showAddModal = false;
    public bool $showEditModal = false;
    public ?int $editingTypeId = null;

    /* ─── Appointment Type Form Fields ───────────── */
    public string $modal_title = '';
    public string $modal_description = '';
    public int $modal_slot_duration_minutes = 30;
    public int $modal_buffer_minutes = 10;
    public int $modal_max_appointments_per_day = 20;
    public array $modal_time_slots = [];

    protected function rules(): array
    {
        return [
            'enable_appointments'        => 'boolean',
            'default_duration'           => 'required|string|in:15,30,45,60,90,120',
            'business_hours_start'       => 'required|string|max:5',
            'business_hours_end'         => 'required|string|max:5',
            'allow_online_booking'       => 'boolean',
            'buffer_time'                => 'required|integer|min:0|max:120',
            'confirmation_email_subject' => 'nullable|string|max:255',
            'confirmation_email_body'    => 'nullable|string|max:5000',
            'reminder_email_subject'     => 'nullable|string|max:255',
            'reminder_email_body'        => 'nullable|string|max:5000',
            'reminder_hours_before'      => 'required|integer|min:1|max:168',
            // Appointment type modal rules
            'modal_title'                      => 'required|string|max:255',
            'modal_description'                => 'nullable|string|max:2000',
            'modal_slot_duration_minutes'      => 'required|integer|min:5|max:480',
            'modal_buffer_minutes'             => 'required|integer|min:0|max:120',
            'modal_max_appointments_per_day'   => 'required|integer|min:1|max:200',
            'modal_time_slots'                 => 'nullable|array',
            'modal_time_slots.*.day'           => 'required|string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'modal_time_slots.*.start'         => 'required|string|date_format:H:i',
            'modal_time_slots.*.end'           => 'required|string|date_format:H:i',
            'modal_time_slots.*.enabled'       => 'sometimes|boolean',
        ];
    }

    public function mount($tenant): void
    {
        $this->tenant = $tenant;
        $this->durationOptions = [
            '15'  => '15 minutes',
            '30'  => '30 minutes',
            '45'  => '45 minutes',
            '60'  => '1 hour',
            '90'  => '1.5 hours',
            '120' => '2 hours',
        ];
        $this->loadSettings();
        $this->loadAppointmentTypes();
        $this->initDefaultTimeSlots();
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

    private function loadSettings(): void
    {
        // Load from appointment settings model
        $apptSetting = RepairBuddyAppointmentSetting::first();

        if ($apptSetting) {
            $this->enable_appointments = $apptSetting->is_enabled;
            $this->default_duration    = (string) ($apptSetting->slot_duration_minutes ?? 60);
            $this->buffer_time         = $apptSetting->buffer_minutes ?? 15;

            $timeSlots = $apptSetting->time_slots ?? [];
            $this->business_hours_start = (string) ($timeSlots['start'] ?? '09:00');
            $this->business_hours_end   = (string) ($timeSlots['end'] ?? '17:00');
        }

        // Load email templates from TenantSettingsStore
        $store = new TenantSettingsStore($this->tenant);
        $settings = $store->get('appointments', []);
        if (! is_array($settings)) {
            $settings = [];
        }

        $this->allow_online_booking       = (bool) ($settings['allow_online_booking'] ?? false);
        $this->confirmation_email_subject = (string) ($settings['confirmation_email_subject'] ?? '');
        $this->confirmation_email_body    = (string) ($settings['confirmation_email_body'] ?? '');
        $this->reminder_email_subject     = (string) ($settings['reminder_email_subject'] ?? '');
        $this->reminder_email_body        = (string) ($settings['reminder_email_body'] ?? '');
        $this->reminder_hours_before      = (int) ($settings['reminder_hours_before'] ?? 24);
    }

    public function save(): void
    {
        $this->validate();

        // Save to appointment settings model (upsert)
        RepairBuddyAppointmentSetting::updateOrCreate(
            [
                'tenant_id'  => $this->tenant->id,
                'branch_id'  => BranchContext::branchId(),
            ],
            [
                'is_enabled'             => $this->enable_appointments,
                'slot_duration_minutes'  => (int) $this->default_duration,
                'buffer_minutes'         => $this->buffer_time,
                'time_slots'             => [
                    'start' => $this->business_hours_start,
                    'end'   => $this->business_hours_end,
                ],
            ]
        );

        // Save email templates to TenantSettingsStore
        $store = new TenantSettingsStore($this->tenant);

        $store->merge('appointments', [
            'allow_online_booking'       => $this->allow_online_booking,
            'confirmation_email_subject' => $this->confirmation_email_subject,
            'confirmation_email_body'    => $this->confirmation_email_body,
            'reminder_email_subject'     => $this->reminder_email_subject,
            'reminder_email_body'        => $this->reminder_email_body,
            'reminder_hours_before'      => $this->reminder_hours_before,
        ]);

        $store->save();

        $this->dispatch('settings-saved', message: 'Appointment settings saved successfully.');
    }

    /* ─── Appointment Types Methods ──────────────── */

    private function loadAppointmentTypes(): void
    {
        $this->appointmentTypes = RepairBuddyAppointmentSetting::query()
            ->orderBy('title')
            ->get()
            ->map(fn ($a) => [
                'id'                        => $a->id,
                'title'                     => $a->title,
                'description'               => $a->description,
                'is_enabled'                => $a->is_enabled,
                'slot_duration_minutes'     => $a->slot_duration_minutes,
                'buffer_minutes'            => $a->buffer_minutes,
                'max_appointments_per_day'  => $a->max_appointments_per_day,
                'time_slots'                => is_array($a->time_slots) ? $a->time_slots : [],
            ])
            ->toArray();
    }

    private function initDefaultTimeSlots(): void
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $this->modal_time_slots = [];
        foreach ($days as $day) {
            $this->modal_time_slots[] = [
                'day'     => $day,
                'start'   => $this->business_hours_start ?: '09:00',
                'end'     => $this->business_hours_end ?: '17:00',
                'enabled' => ! in_array($day, ['saturday', 'sunday']),
            ];
        }
    }

    public function openAddModal(): void
    {
        $this->resetModal();
        $this->initDefaultTimeSlots();
        $this->showAddModal = true;
    }

    public function openEditModal(int $id): void
    {
        $this->resetModal();

        $type = RepairBuddyAppointmentSetting::find($id);
        if (! $type) {
            return;
        }

        $this->editingTypeId                 = $type->id;
        $this->modal_title                   = $type->title;
        $this->modal_description             = $type->description ?? '';
        $this->modal_slot_duration_minutes   = $type->slot_duration_minutes;
        $this->modal_buffer_minutes          = $type->buffer_minutes;
        $this->modal_max_appointments_per_day = $type->max_appointments_per_day;

        $slots = is_array($type->time_slots) ? $type->time_slots : [];
        if (empty($slots)) {
            $this->initDefaultTimeSlots();
        } else {
            $this->modal_time_slots = $slots;
        }

        $this->showEditModal = true;
    }

    public function closeModal(): void
    {
        $this->showAddModal = false;
        $this->showEditModal = false;
        $this->resetModal();
    }

    private function resetModal(): void
    {
        $this->editingTypeId = null;
        $this->modal_title = '';
        $this->modal_description = '';
        $this->modal_slot_duration_minutes = 30;
        $this->modal_buffer_minutes = 10;
        $this->modal_max_appointments_per_day = 20;
        $this->modal_time_slots = [];
        $this->resetValidation();
    }

    public function saveType(): void
    {
        $this->validate([
            'modal_title'                    => 'required|string|max:255',
            'modal_description'              => 'nullable|string|max:2000',
            'modal_slot_duration_minutes'    => 'required|integer|min:5|max:480',
            'modal_buffer_minutes'           => 'required|integer|min:0|max:120',
            'modal_max_appointments_per_day' => 'required|integer|min:1|max:200',
            'modal_time_slots'               => 'nullable|array',
            'modal_time_slots.*.day'         => 'required|string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'modal_time_slots.*.start'       => 'required|string|date_format:H:i',
            'modal_time_slots.*.end'         => 'required|string|date_format:H:i',
            'modal_time_slots.*.enabled'     => 'sometimes|boolean',
        ]);

        if ($this->editingTypeId) {
            // Update existing
            $type = RepairBuddyAppointmentSetting::find($this->editingTypeId);
            if (! $type) {
                $this->dispatch('settings-saved', message: 'Appointment type not found.');
                return;
            }

            $type->update([
                'title'                     => $this->modal_title,
                'description'               => $this->modal_description ?: null,
                'slot_duration_minutes'     => $this->modal_slot_duration_minutes,
                'buffer_minutes'            => $this->modal_buffer_minutes,
                'max_appointments_per_day'  => $this->modal_max_appointments_per_day,
                'time_slots'                => $this->modal_time_slots,
            ]);
        } else {
            // Create new
            RepairBuddyAppointmentSetting::create([
                'tenant_id'                 => $this->tenant->id,
                'branch_id'                 => BranchContext::branchId(),
                'title'                     => $this->modal_title,
                'description'               => $this->modal_description ?: null,
                'is_enabled'                => true,
                'slot_duration_minutes'     => $this->modal_slot_duration_minutes,
                'buffer_minutes'            => $this->modal_buffer_minutes,
                'max_appointments_per_day'  => $this->modal_max_appointments_per_day,
                'time_slots'                => $this->modal_time_slots,
            ]);
        }

        $this->loadAppointmentTypes();
        $this->closeModal();
        $this->dispatch('settings-saved', message: 'Appointment type saved successfully.');
    }

    public function deleteType(int $id): void
    {
        $type = RepairBuddyAppointmentSetting::find($id);
        if ($type) {
            $type->delete();
            $this->loadAppointmentTypes();
            $this->dispatch('settings-saved', message: 'Appointment type deleted.');
        }
    }

    public function toggleType(int $id): void
    {
        $type = RepairBuddyAppointmentSetting::find($id);
        if ($type) {
            $type->update(['is_enabled' => ! $type->is_enabled]);
            $this->loadAppointmentTypes();
            $this->dispatch('settings-saved', message: $type->is_enabled ? 'Appointment type enabled.' : 'Appointment type disabled.');
        }
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.appointment-settings');
    }
}
