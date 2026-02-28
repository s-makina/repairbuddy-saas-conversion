{{-- Appointment Settings Section — Livewire Component --}}
<div>
    <form wire:submit.prevent="save">
        {{-- ── General ── --}}
        <div class="st-section" x-data="{ open: true }">
            <div class="st-section-header" @click="open = !open">
                <h3 class="st-section-title">
                    <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    Appointment Settings
                </h3>
                <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
            <div class="st-section-body" x-show="open" x-collapse>
                <div class="st-option-card">
                    <div class="st-option-control">
                        <label class="st-toggle">
                            <input type="checkbox" wire:model.defer="enable_appointments" />
                            <span class="st-toggle-track"></span>
                        </label>
                    </div>
                    <div class="st-option-body">
                        <label class="st-option-title">Enable Appointments</label>
                        <p class="st-option-desc">Allow scheduling appointments for device drop-off and pickup</p>
                    </div>
                </div>

                <div class="st-option-card">
                    <div class="st-option-control">
                        <label class="st-toggle">
                            <input type="checkbox" wire:model.defer="allow_online_booking" />
                            <span class="st-toggle-track"></span>
                        </label>
                    </div>
                    <div class="st-option-body">
                        <label class="st-option-title">Allow Online Booking</label>
                        <p class="st-option-desc">Let customers book appointments from your public booking page</p>
                    </div>
                </div>

                <div class="st-grid st-grid-3" style="margin-top: .75rem;">
                    <div class="st-fg">
                        <label for="default_duration">Default Duration</label>
                        <select id="default_duration" wire:model.defer="default_duration">
                            @foreach ($durationOptions as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="st-help">Default appointment slot duration</p>
                    </div>
                    <div class="st-fg">
                        <label for="business_hours_start">Business Hours Start</label>
                        <input type="time" id="business_hours_start" wire:model.defer="business_hours_start"
                            style="width: 100%; padding: .55rem .75rem; border: 1px solid var(--st-border); border-radius: var(--st-radius-sm); font-size: .85rem;" />
                        @error('business_hours_start') <span class="st-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="st-fg">
                        <label for="business_hours_end">Business Hours End</label>
                        <input type="time" id="business_hours_end" wire:model.defer="business_hours_end"
                            style="width: 100%; padding: .55rem .75rem; border: 1px solid var(--st-border); border-radius: var(--st-radius-sm); font-size: .85rem;" />
                        @error('business_hours_end') <span class="st-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="st-fg" style="max-width: 200px;">
                    <label for="buffer_time">Buffer Time (minutes)</label>
                    <input type="number" id="buffer_time" wire:model.defer="buffer_time"
                           min="0" max="120" step="5" />
                    @error('buffer_time') <span class="st-error">{{ $message }}</span> @enderror
                    <p class="st-help">Time between consecutive appointments</p>
                </div>
            </div>
        </div>

        {{-- ── Confirmation Email ── --}}
        <div class="st-section" x-data="{ open: false }">
            <div class="st-section-header" @click="open = !open">
                <h3 class="st-section-title">
                    <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                    Confirmation Email
                </h3>
                <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
            <div class="st-section-body" x-show="open" x-collapse>
                <div class="st-fg">
                    <label for="confirmation_email_subject">Email Subject</label>
                    <input type="text" id="confirmation_email_subject" wire:model.defer="confirmation_email_subject"
                           placeholder="Your appointment is confirmed" />
                </div>
                <div class="st-fg">
                    <label for="confirmation_email_body">Email Body</label>
                    <textarea id="confirmation_email_body" wire:model.defer="confirmation_email_body" rows="5"
                              placeholder="Appointment confirmation email body..."></textarea>
                    <p class="st-help">Keywords: @{{customer_full_name}}, @{{appointment_date}}, @{{appointment_time}}, @{{business_name}}, @{{business_address}}</p>
                </div>
            </div>
        </div>

        {{-- ── Reminder Email ── --}}
        <div class="st-section" x-data="{ open: false }">
            <div class="st-section-header" @click="open = !open">
                <h3 class="st-section-title">
                    <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                    Reminder Email
                </h3>
                <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
            <div class="st-section-body" x-show="open" x-collapse>
                <div class="st-fg" style="max-width: 200px;">
                    <label for="reminder_hours_before">Hours Before Appointment</label>
                    <input type="number" id="reminder_hours_before" wire:model.defer="reminder_hours_before"
                           min="1" max="168" />
                    @error('reminder_hours_before') <span class="st-error">{{ $message }}</span> @enderror
                    <p class="st-help">Send reminder this many hours before</p>
                </div>
                <div class="st-fg">
                    <label for="reminder_email_subject">Email Subject</label>
                    <input type="text" id="reminder_email_subject" wire:model.defer="reminder_email_subject"
                           placeholder="Appointment reminder" />
                </div>
                <div class="st-fg">
                    <label for="reminder_email_body">Email Body</label>
                    <textarea id="reminder_email_body" wire:model.defer="reminder_email_body" rows="5"
                              placeholder="Appointment reminder email body..."></textarea>
                    <p class="st-help">Keywords: @{{customer_full_name}}, @{{appointment_date}}, @{{appointment_time}}, @{{business_name}}, @{{business_address}}</p>
                </div>
            </div>
        </div>

        {{-- ── Appointment Types ── --}}
        <div class="st-section" x-data="{ open: true }">
            <div class="st-section-header" @click="open = !open">
                <h3 class="st-section-title">
                    <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                    Appointment Types
                </h3>
                <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
            <div class="st-section-body" x-show="open" x-collapse>
                <p class="st-help" style="margin-bottom: 1rem;">Create appointment types that customers can select when booking. Each type has its own duration, buffer time, and weekly schedule.</p>

                <div style="display: flex; justify-content: flex-end; margin-bottom: .75rem;">
                    <button type="button" class="st-btn-save" wire:click="openAddModal" style="font-size: .78rem; padding: .45rem 1rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Add Appointment Type
                    </button>
                </div>

                {{-- List existing types --}}
                @forelse($appointmentTypes as $type)
                    <div style="border: 1px solid var(--st-border); border-radius: var(--st-radius-sm); padding: .75rem 1rem; margin-bottom: .5rem; background: #fff;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong>{{ $type['title'] }}</strong>
                                @if($type['description'])
                                    <span style="color: var(--st-text-3); font-size: .8rem; margin-left: 0.5rem;">{{ Str::limit($type['description'], 60) }}</span>
                                @endif
                                <span style="display: inline-block; padding: .15rem .5rem; border-radius: 999px; font-size: .7rem; font-weight: 600; margin-left: 0.5rem;
                                    {{ $type['is_enabled'] ? 'background: var(--st-success-soft); color: #15803d;' : 'background: #f1f5f9; color: #64748b;' }}">
                                    {{ $type['is_enabled'] ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <div style="display: flex; gap: 0.35rem;">
                                <button type="button" wire:click="toggleType({{ $type['id'] }})" wire:loading.attr="disabled"
                                    style="background: none; border: 1px solid var(--st-border); border-radius: 6px; padding: .3rem .6rem; font-size: .75rem; cursor: pointer;">
                                    @if($type['is_enabled'])
                                        Disable
                                    @else
                                        Enable
                                    @endif
                                </button>
                                <button type="button" wire:click="openEditModal({{ $type['id'] }})"
                                    style="background: none; border: 1px solid var(--st-border); border-radius: 6px; padding: .3rem .6rem; font-size: .75rem; color: var(--st-brand); cursor: pointer;">
                                    Edit
                                </button>
                                <button type="button" wire:click="deleteType({{ $type['id'] }})" wire:confirm="Are you sure you want to delete this appointment type?"
                                    style="background: none; border: 1px solid var(--st-danger-soft, #fee2e2); border-radius: 6px; padding: .3rem .6rem; font-size: .75rem; color: #dc2626; cursor: pointer;">
                                    Delete
                                </button>
                            </div>
                        </div>
                        <div style="display: flex; gap: 1.5rem; margin-top: 0.5rem; font-size: 0.8rem; color: var(--st-text-3);">
                            <span>Duration: {{ $type['slot_duration_minutes'] }} min</span>
                            <span>Buffer: {{ $type['buffer_minutes'] }} min</span>
                            <span>Max: {{ $type['max_appointments_per_day'] }}/day</span>
                        </div>
                    </div>
                @empty
                    <div style="text-align: center; padding: 2rem; color: var(--st-text-3); font-size: .84rem;">
                        No appointment types created yet. Click "Add Appointment Type" to create one.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ── Save ── --}}
        <div class="st-save-bar">
            <button type="submit" class="st-btn-save" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Save Appointment Settings</span>
                <span wire:loading wire:target="save" class="st-spinner"></span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </form>

    {{-- ── Add/Edit Modal ── --}}
    @if ($showAddModal || $showEditModal)
        <div style="position: fixed; inset: 0; z-index: 999; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,.45); backdrop-filter: blur(4px);" wire:click.self="closeModal">
            <div style="background: #fff; border-radius: var(--st-radius); box-shadow: 0 20px 60px rgba(0,0,0,.2); width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; padding: 1.5rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
                    <h3 style="font-size: .95rem; font-weight: 700; color: var(--st-text); margin: 0;">
                        {{ $showAddModal ? 'Add Appointment Type' : 'Edit Appointment Type' }}
                    </h3>
                    <button type="button" wire:click="closeModal" style="background: none; border: none; font-size: 1.2rem; color: var(--st-text-3); cursor: pointer;">&times;</button>
                </div>

                <form wire:submit.prevent="saveType">
                    <div class="st-fg">
                        <label for="modal_title">Title</label>
                        <input type="text" id="modal_title" wire:model.defer="modal_title" placeholder="e.g., Standard Repair Appointment" />
                        @error('modal_title') <p class="st-field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="st-fg">
                        <label for="modal_description">Description</label>
                        <input type="text" id="modal_description" wire:model.defer="modal_description" placeholder="Optional description" />
                        @error('modal_description') <p class="st-field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="st-grid st-grid-3" style="margin-top: 1rem;">
                        <div class="st-fg">
                            <label for="modal_slot_duration_minutes">Duration (min)</label>
                            <input type="number" id="modal_slot_duration_minutes" wire:model.defer="modal_slot_duration_minutes" min="5" max="480" />
                            @error('modal_slot_duration_minutes') <p class="st-field-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="st-fg">
                            <label for="modal_buffer_minutes">Buffer (min)</label>
                            <input type="number" id="modal_buffer_minutes" wire:model.defer="modal_buffer_minutes" min="0" max="120" />
                            @error('modal_buffer_minutes') <p class="st-field-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="st-fg">
                            <label for="modal_max_appointments_per_day">Max/Day</label>
                            <input type="number" id="modal_max_appointments_per_day" wire:model.defer="modal_max_appointments_per_day" min="1" max="200" />
                            @error('modal_max_appointments_per_day') <p class="st-field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div style="margin-top: 1.5rem;">
                        <label style="font-size: .78rem; font-weight: 600; color: var(--st-text-2); display: block; margin-bottom: .5rem;">Weekly Schedule</label>
                        <p class="st-help">Set the days and times this appointment type is available.</p>
                    </div>

                    <div style="overflow-x: auto; margin-top: 0.5rem;">
                        <table style="width: 100%; border-collapse: collapse; font-size: .82rem;">
                            <thead>
                                <tr style="border-bottom: 2px solid var(--st-border); text-align: left;">
                                    <th style="padding: .5rem .75rem; font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--st-text-3); width: 60px;">Active</th>
                                    <th style="padding: .5rem .75rem; font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--st-text-3); width: 100px;">Day</th>
                                    <th style="padding: .5rem .75rem; font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--st-text-3);">Start</th>
                                    <th style="padding: .5rem .75rem; font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--st-text-3);">End</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($modal_time_slots as $idx => $slot)
                                    <tr style="border-bottom: 1px solid var(--st-border);">
                                        <td style="padding: .55rem .75rem; text-align: center;">
                                            <input type="hidden" wire:model.defer="modal_time_slots.{{ $idx }}.day" value="{{ $slot['day'] ?? '' }}" />
                                            <label class="st-toggle" style="margin: 0;">
                                                <input type="checkbox" wire:model.defer="modal_time_slots.{{ $idx }}.enabled" />
                                                <span class="st-toggle-track"></span>
                                            </label>
                                        </td>
                                        <td style="padding: .55rem .75rem; font-weight: 500;">{{ ucfirst($slot['day'] ?? '') }}</td>
                                        <td style="padding: .55rem .75rem;">
                                            <input type="time" wire:model.defer="modal_time_slots.{{ $idx }}.start" value="{{ $slot['start'] ?? '09:00' }}"
                                                style="width: 100%; padding: .4rem .5rem; border: 1px solid var(--st-border); border-radius: var(--st-radius-sm); font-size: .8rem;" />
                                        </td>
                                        <td style="padding: .55rem .75rem;">
                                            <input type="time" wire:model.defer="modal_time_slots.{{ $idx }}.end" value="{{ $slot['end'] ?? '17:00' }}"
                                                style="width: 100%; padding: .4rem .5rem; border: 1px solid var(--st-border); border-radius: var(--st-radius-sm); font-size: .8rem;" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="st-save-bar">
                        <button type="submit" class="st-btn-save" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="saveType">{{ $showAddModal ? 'Add Appointment Type' : 'Update Appointment Type' }}</span>
                            <span wire:loading wire:target="saveType" class="st-spinner"></span>
                            <span wire:loading wire:target="saveType">Saving…</span>
                        </button>
                        <button type="button" wire:click="closeModal"
                            style="padding: .55rem 1rem; font-size: .82rem; font-weight: 500; background: none; border: 1px solid var(--st-border); border-radius: var(--st-radius-sm); cursor: pointer; color: var(--st-text-2);">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
