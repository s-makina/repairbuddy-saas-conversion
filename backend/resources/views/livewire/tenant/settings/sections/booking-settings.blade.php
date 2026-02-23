{{-- Booking Settings Section — Livewire Component --}}
<div>
    <form wire:submit.prevent="save">
        {{-- ── Booking Email to Customer ── --}}
        <div class="st-section" x-data="{ open: true }">
            <div class="st-section-header" @click="open = !open">
                <h3 class="st-section-title">
                    <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                    Booking Email to Customer
                </h3>
                <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
            <div class="st-section-body" x-show="open" x-collapse>
                <div class="st-fg">
                    <label for="email_subject_customer">Email Subject</label>
                    <input type="text" id="email_subject_customer" wire:model.defer="email_subject_customer"
                           placeholder="Booking confirmation" />
                </div>
                <div class="st-fg">
                    <label for="email_body_customer">Email Body</label>
                    <textarea id="email_body_customer" wire:model.defer="email_body_customer" rows="5"
                              placeholder="Booking confirmation email body for customer..."></textarea>
                    <p class="st-help">Keywords: @{{customer_full_name}}, @{{customer_device_label}}, @{{status_check_link}}, @{{start_anch_status_check_link}}, @{{end_anch_status_check_link}}, @{{order_invoice_details}}, @{{job_id}}, @{{case_number}}</p>
                </div>
            </div>
        </div>

        {{-- ── Booking Email to Admin ── --}}
        <div class="st-section" x-data="{ open: true }">
            <div class="st-section-header" @click="open = !open">
                <h3 class="st-section-title">
                    <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                    Booking Email to Administrator
                </h3>
                <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
            <div class="st-section-body" x-show="open" x-collapse>
                <div class="st-fg">
                    <label for="email_subject_admin">Email Subject</label>
                    <input type="text" id="email_subject_admin" wire:model.defer="email_subject_admin"
                           placeholder="New booking received" />
                </div>
                <div class="st-fg">
                    <label for="email_body_admin">Email Body</label>
                    <textarea id="email_body_admin" wire:model.defer="email_body_admin" rows="5"
                              placeholder="Booking notification email body for admin..."></textarea>
                    <p class="st-help">Keywords: @{{customer_full_name}}, @{{customer_device_label}}, @{{status_check_link}}, @{{start_anch_status_check_link}}, @{{end_anch_status_check_link}}, @{{order_invoice_details}}, @{{job_id}}, @{{case_number}}</p>
                </div>
            </div>
        </div>

        {{-- ── Booking & Quote Forms ── --}}
        <div class="st-section" x-data="{ open: true }">
            <div class="st-section-header" @click="open = !open">
                <h3 class="st-section-title">
                    <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75"/></svg>
                    Booking & Quote Forms
                </h3>
                <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
            <div class="st-section-body" x-show="open" x-collapse>
                <div class="st-option-card">
                    <div class="st-option-control">
                        <label class="st-toggle">
                            <input type="checkbox" wire:model.defer="send_to_jobs" />
                            <span class="st-toggle-track"></span>
                        </label>
                    </div>
                    <div class="st-option-body">
                        <label class="st-option-title">Send Booking Forms to Jobs</label>
                        <p class="st-option-desc">Send booking forms & quote forms to jobs instead of estimates</p>
                    </div>
                </div>

                <div class="st-option-card">
                    <div class="st-option-control">
                        <label class="st-toggle">
                            <input type="checkbox" wire:model.defer="turn_off_other_device_brands" />
                            <span class="st-toggle-track"></span>
                        </label>
                    </div>
                    <div class="st-option-body">
                        <label class="st-option-title">Turn Off "Other" for Devices & Brands</label>
                        <p class="st-option-desc">Hide the "Other" option in device and brand selection</p>
                    </div>
                </div>

                <div class="st-option-card">
                    <div class="st-option-control">
                        <label class="st-toggle">
                            <input type="checkbox" wire:model.defer="turn_off_other_service" />
                            <span class="st-toggle-track"></span>
                        </label>
                    </div>
                    <div class="st-option-body">
                        <label class="st-option-title">Turn Off "Other" Service Option</label>
                        <p class="st-option-desc">Hide the "Other" option in service selection</p>
                    </div>
                </div>

                <div class="st-option-card">
                    <div class="st-option-control">
                        <label class="st-toggle">
                            <input type="checkbox" wire:model.defer="turn_off_service_price" />
                            <span class="st-toggle-track"></span>
                        </label>
                    </div>
                    <div class="st-option-body">
                        <label class="st-option-title">Turn Off Service Prices</label>
                        <p class="st-option-desc">Hide prices from services in booking forms</p>
                    </div>
                </div>

                <div class="st-option-card">
                    <div class="st-option-control">
                        <label class="st-toggle">
                            <input type="checkbox" wire:model.defer="turn_off_id_imei_booking" />
                            <span class="st-toggle-track"></span>
                        </label>
                    </div>
                    <div class="st-option-body">
                        <label class="st-option-title">Turn Off ID/IMEI in Booking Form</label>
                        <p class="st-option-desc">Hide the ID/IMEI field from the booking form</p>
                    </div>
                </div>

                {{-- Default selections --}}
                <div class="st-grid st-grid-3" style="margin-top: 1rem;">
                    <div class="st-fg">
                        <label for="default_type">Default Device Type</label>
                        <select id="default_type" wire:model.defer="default_type">
                            <option value="">— Select —</option>
                            @foreach ($typeOptions as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="st-help">Pre-selected on booking page</p>
                    </div>
                    <div class="st-fg">
                        <label for="default_brand">Default Device Brand</label>
                        <select id="default_brand" wire:model.defer="default_brand">
                            <option value="">— Select —</option>
                            @foreach ($brandOptions as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="st-help">Pre-selected on booking page</p>
                    </div>
                    <div class="st-fg">
                        <label for="default_device">Default Device</label>
                        <select id="default_device" wire:model.defer="default_device">
                            <option value="">— Select —</option>
                            @foreach ($deviceOptions as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="st-help">Pre-selected on booking page</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Save ── --}}
        <div class="st-save-bar">
            <button type="submit" class="st-btn-save" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Save Booking Settings</span>
                <span wire:loading wire:target="save" class="st-spinner"></span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </form>
</div>
