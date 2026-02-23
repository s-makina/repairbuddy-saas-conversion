{{-- General Settings Section — Livewire Component --}}
<div>
    <form wire:submit.prevent="save">
        {{-- ── Business Information ── --}}
        <div class="st-section" x-data="{ open: true }">
            <div class="st-section-header" @click="open = !open">
                <h3 class="st-section-title">
                    <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z"/></svg>
                    Business Information
                </h3>
                <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
            <div class="st-section-body" x-show="open" x-collapse>
                <div class="st-grid st-grid-2">
                    <div class="st-fg">
                        <label for="business_name">Business Name</label>
                        <input type="text" id="business_name" wire:model.defer="business_name"
                               placeholder="Your business name" />
                        <p class="st-help">Name will be used in reports/invoices</p>
                        @error('business_name') <p class="st-field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="st-fg">
                        <label for="business_phone">Business Phone</label>
                        <input type="tel" id="business_phone" wire:model.defer="business_phone"
                               placeholder="Phone number" />
                        <p class="st-help">Phone will be used in reports/invoices</p>
                        @error('business_phone') <p class="st-field-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="st-fg">
                    <label for="business_address">Business Address</label>
                    <input type="text" id="business_address" wire:model.defer="business_address"
                           placeholder="Full business address" />
                    <p class="st-help">Address will be used in reports/invoices</p>
                    @error('business_address') <p class="st-field-error">{{ $message }}</p> @enderror
                </div>

                <div class="st-grid st-grid-2">
                    <div class="st-fg">
                        <label for="logo_url">Logo URL</label>
                        <input type="url" id="logo_url" wire:model.defer="logo_url"
                               placeholder="https://example.com/logo.png" />
                        <p class="st-help">Logo used on invoices, estimates, and customer-facing pages</p>
                        @error('logo_url') <p class="st-field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="st-fg">
                        <label for="email">Contact Email</label>
                        <input type="email" id="email" wire:model.defer="email"
                               placeholder="admin@example.com" />
                        <p class="st-help">Where quote forms and other admin emails would be sent</p>
                        @error('email') <p class="st-field-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="st-grid st-grid-2">
                    <div class="st-fg">
                        <label for="case_number_prefix">Case Number Prefix</label>
                        <input type="text" id="case_number_prefix" wire:model.defer="case_number_prefix"
                               placeholder="e.g. WC_ or CHM_" />
                        <p class="st-help">Shown at the start of every case number</p>
                        @error('case_number_prefix') <p class="st-field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="st-fg">
                        <label for="case_number_length">Case Number Length</label>
                        <input type="number" id="case_number_length" wire:model.defer="case_number_length"
                               min="1" max="20" />
                        <p class="st-help">Number of characters before the timestamp is appended</p>
                        @error('case_number_length') <p class="st-field-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Notifications ── --}}
        <div class="st-section" x-data="{ open: true }">
            <div class="st-section-header" @click="open = !open">
                <h3 class="st-section-title">
                    <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                    Notifications
                </h3>
                <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
            <div class="st-section-body" x-show="open" x-collapse>
                <div class="st-option-card">
                    <div class="st-option-control">
                        <label class="st-toggle">
                            <input type="checkbox" wire:model.defer="email_customer_on_status_change" />
                            <span class="st-toggle-track"></span>
                        </label>
                    </div>
                    <div class="st-option-body">
                        <label for="" class="st-option-title" @click="$refs.emailToggle?.click()">Email Customer on Status Change</label>
                        <p class="st-option-desc">Email customer every time job status is changed</p>
                    </div>
                </div>

                <div class="st-option-card">
                    <div class="st-option-control">
                        <label class="st-toggle">
                            <input type="checkbox" wire:model.defer="attach_pdf" />
                            <span class="st-toggle-track"></span>
                        </label>
                    </div>
                    <div class="st-option-body">
                        <label class="st-option-title">Attach PDF</label>
                        <p class="st-option-desc">Attach PDF with emails to customer about jobs and estimates</p>
                    </div>
                </div>

                <div class="st-option-card">
                    <div class="st-option-control">
                        <label class="st-toggle">
                            <input type="checkbox" wire:model.defer="next_service_date" />
                            <span class="st-toggle-track"></span>
                        </label>
                    </div>
                    <div class="st-option-body">
                        <label class="st-option-title">Next Service Date</label>
                        <p class="st-option-desc">Turn on if you want to see jobs in calendar for next service date</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Compliance / GDPR ── --}}
        <div class="st-section" x-data="{ open: true }">
            <div class="st-section-header" @click="open = !open">
                <h3 class="st-section-title">
                    <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/></svg>
                    Compliance
                </h3>
                <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
            <div class="st-section-body" x-show="open" x-collapse>
                <div class="st-grid st-grid-3">
                    <div class="st-fg">
                        <label for="gdpr_acceptance">GDPR Acceptance Text</label>
                        <input type="text" id="gdpr_acceptance" wire:model.defer="gdpr_acceptance"
                               placeholder="GDPR acceptance text for booking & quote forms" />
                        <p class="st-help">Label shown next to the GDPR checkbox</p>
                        @error('gdpr_acceptance') <p class="st-field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="st-fg">
                        <label for="gdpr_link_label">Link Label</label>
                        <input type="text" id="gdpr_link_label" wire:model.defer="gdpr_link_label"
                               placeholder="Privacy policy" />
                        <p class="st-help">Clickable text for the privacy/terms link</p>
                        @error('gdpr_link_label') <p class="st-field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="st-fg">
                        <label for="gdpr_link_url">Link URL</label>
                        <input type="text" id="gdpr_link_url" wire:model.defer="gdpr_link_url"
                               placeholder="https://example.com/privacy-policy" />
                        <p class="st-help">Full URL to your privacy or terms page</p>
                        @error('gdpr_link_url') <p class="st-field-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Defaults ── --}}
        <div class="st-section" x-data="{ open: true }">
            <div class="st-section-header" @click="open = !open">
                <h3 class="st-section-title">
                    <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418"/></svg>
                    Defaults
                </h3>
                <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
            <div class="st-section-body" x-show="open" x-collapse>
                <div class="st-grid st-grid-2">
                    <div class="st-fg">
                        <label for="default_country">Default Country</label>
                        <select id="default_country" wire:model.defer="default_country">
                            @foreach ($countries as $code => $name)
                                <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        <p class="st-help">Default country used for new customers and documents</p>
                        @error('default_country') <p class="st-field-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Integrations ── --}}
        <div class="st-section" x-data="{ open: false }">
            <div class="st-section-header" @click="open = !open">
                <h3 class="st-section-title">
                    <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/></svg>
                    Integrations
                </h3>
                <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
            <div class="st-section-body" x-show="open" x-collapse>
                <div class="st-option-card">
                    <div class="st-option-control">
                        <label class="st-toggle">
                            <input type="checkbox" wire:model.defer="use_woo_products" />
                            <span class="st-toggle-track"></span>
                        </label>
                    </div>
                    <div class="st-option-body">
                        <label class="st-option-title">Use WooCommerce Products</label>
                        <p class="st-option-desc">Disable built-in parts and use WooCommerce products instead</p>
                    </div>
                </div>

                <div class="st-option-card">
                    <div class="st-option-control">
                        <label class="st-toggle">
                            <input type="checkbox" wire:model.defer="disable_status_check_serial" />
                            <span class="st-toggle-track"></span>
                        </label>
                    </div>
                    <div class="st-option-body">
                        <label class="st-option-title">Disable Status Check by Serial Number</label>
                        <p class="st-option-desc">Prevent customers from checking job status by device serial number</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Save ── --}}
        <div class="st-save-bar">
            <button type="submit" class="st-btn-save" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Save General Settings</span>
                <span wire:loading wire:target="save" class="st-spinner"></span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </form>
</div>
