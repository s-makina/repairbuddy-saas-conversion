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
                        <input type="time" id="business_hours_start" wire:model.defer="business_hours_start" />
                        @error('business_hours_start') <span class="st-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="st-fg">
                        <label for="business_hours_end">Business Hours End</label>
                        <input type="time" id="business_hours_end" wire:model.defer="business_hours_end" />
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

        {{-- ── Save ── --}}
        <div class="st-save-bar">
            <button type="submit" class="st-btn-save" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Save Appointment Settings</span>
                <span wire:loading wire:target="save" class="st-spinner"></span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </form>
</div>
