{{-- Devices & Brands Settings Section — Livewire Component --}}
<div>
    <form wire:submit.prevent="save">
        {{-- ── Pin Code Settings ── --}}
        <div class="st-section" x-data="{ open: true }">
            <div class="st-section-header" @click="open = !open">
                <h3 class="st-section-title">
                    <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                    Pin Code Settings
                </h3>
                <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
            <div class="st-section-body" x-show="open" x-collapse>
                <div class="st-option-card">
                    <div class="st-option-control">
                        <label class="st-toggle">
                            <input type="checkbox" wire:model.defer="enable_pin_code" />
                            <span class="st-toggle-track"></span>
                        </label>
                    </div>
                    <div class="st-option-body">
                        <label class="st-option-title">Enable Pin Code Field</label>
                        <p class="st-option-desc">Show a pin code / password field in job forms for device unlock codes</p>
                    </div>
                </div>

                <div class="st-option-card">
                    <div class="st-option-control">
                        <label class="st-toggle">
                            <input type="checkbox" wire:model.defer="show_pin_in_documents" />
                            <span class="st-toggle-track"></span>
                        </label>
                    </div>
                    <div class="st-option-body">
                        <label class="st-option-title">Show Pin Code in Documents</label>
                        <p class="st-option-desc">Display pin code on invoices, emails, and status check pages</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Labels ── --}}
        <div class="st-section" x-data="{ open: true }">
            <div class="st-section-header" @click="open = !open">
                <h3 class="st-section-title">
                    <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"/></svg>
                    Device & Brand Labels
                </h3>
                <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
            <div class="st-section-body" x-show="open" x-collapse>
                <div class="st-grid st-grid-3">
                    <div class="st-fg">
                        <label for="label_note">Note Label</label>
                        <input type="text" id="label_note" wire:model.defer="label_note" placeholder="Note" />
                    </div>
                    <div class="st-fg">
                        <label for="label_pin">Pin Code Label</label>
                        <input type="text" id="label_pin" wire:model.defer="label_pin" placeholder="Pin Code / Password" />
                    </div>
                    <div class="st-fg">
                        <label for="label_device">Device Label</label>
                        <input type="text" id="label_device" wire:model.defer="label_device" placeholder="Device" />
                    </div>
                </div>
                <div class="st-grid st-grid-3">
                    <div class="st-fg">
                        <label for="label_brand">Brand Label</label>
                        <input type="text" id="label_brand" wire:model.defer="label_brand" placeholder="Brand" />
                    </div>
                    <div class="st-fg">
                        <label for="label_type">Type Label</label>
                        <input type="text" id="label_type" wire:model.defer="label_type" placeholder="Type" />
                    </div>
                    <div class="st-fg">
                        <label for="label_imei">ID / IMEI Label</label>
                        <input type="text" id="label_imei" wire:model.defer="label_imei" placeholder="ID / IMEI" />
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Pickup & Delivery ── --}}
        <div class="st-section" x-data="{ open: true }">
            <div class="st-section-header" @click="open = !open">
                <h3 class="st-section-title">
                    <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.029-.504 .899-1.107l-1.034-4.827A3.247 3.247 0 0 0 17.993 9H15.75m-7.5 9v-7.5m7.5 0v-1.5m0 1.5h-7.5"/></svg>
                    Pickup & Delivery
                </h3>
                <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
            <div class="st-section-body" x-show="open" x-collapse>
                <div class="st-option-card">
                    <div class="st-option-control">
                        <label class="st-toggle">
                            <input type="checkbox" wire:model.defer="pickup_delivery_enabled" />
                            <span class="st-toggle-track"></span>
                        </label>
                    </div>
                    <div class="st-option-body">
                        <label class="st-option-title">Offer Pickup & Delivery</label>
                        <p class="st-option-desc">Allow customers to request pickup and delivery for their devices</p>
                    </div>
                </div>

                <div class="st-grid st-grid-2" style="margin-top: .75rem;">
                    <div class="st-fg">
                        <label for="pickup_charge">Pickup Charge</label>
                        <input type="text" id="pickup_charge" wire:model.defer="pickup_charge" placeholder="0.00" />
                        <p class="st-help">Charge amount for device pickup</p>
                    </div>
                    <div class="st-fg">
                        <label for="delivery_charge">Delivery Charge</label>
                        <input type="text" id="delivery_charge" wire:model.defer="delivery_charge" placeholder="0.00" />
                        <p class="st-help">Charge amount for device delivery</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Rental ── --}}
        <div class="st-section" x-data="{ open: false }">
            <div class="st-section-header" @click="open = !open">
                <h3 class="st-section-title">
                    <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 0 0-3.7-3.7 48.678 48.678 0 0 0-7.324 0 4.006 4.006 0 0 0-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 0 0 3.7 3.7 48.656 48.656 0 0 0 7.324 0 4.006 4.006 0 0 0 3.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3-3 3"/></svg>
                    Device Rental
                </h3>
                <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
            <div class="st-section-body" x-show="open" x-collapse>
                <div class="st-option-card">
                    <div class="st-option-control">
                        <label class="st-toggle">
                            <input type="checkbox" wire:model.defer="rental_enabled" />
                            <span class="st-toggle-track"></span>
                        </label>
                    </div>
                    <div class="st-option-body">
                        <label class="st-option-title">Offer Device Rental</label>
                        <p class="st-option-desc">Offer rental devices to customers during repair</p>
                    </div>
                </div>

                <div class="st-grid st-grid-2" style="margin-top: .75rem;">
                    <div class="st-fg">
                        <label for="rental_per_day">Rental Per Day</label>
                        <input type="text" id="rental_per_day" wire:model.defer="rental_per_day" placeholder="0.00" />
                    </div>
                    <div class="st-fg">
                        <label for="rental_per_week">Rental Per Week</label>
                        <input type="text" id="rental_per_week" wire:model.defer="rental_per_week" placeholder="0.00" />
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Additional Device Fields ── --}}
        <div class="st-section" x-data="{ open: false }">
            <div class="st-section-header" @click="open = !open">
                <h3 class="st-section-title">
                    <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    Additional Device Fields
                </h3>
                <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
            <div class="st-section-body" x-show="open" x-collapse>
                <p style="font-size: .78rem; color: var(--st-text-3); margin-bottom: 1rem;">Add custom fields to device forms (up to 10 fields).</p>

                @foreach ($additional_fields as $index => $field)
                    <div style="display: flex; align-items: flex-end; gap: .75rem; padding: .75rem 0; border-bottom: 1px solid var(--st-border);">
                        <div class="st-fg" style="flex: 2; margin-bottom: 0;">
                            <label>Field Label</label>
                            <input type="text" wire:model.defer="additional_fields.{{ $index }}.label" placeholder="Field label" />
                            @error('additional_fields.'.$index.'.label') <span class="st-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="st-fg" style="flex: 1; margin-bottom: 0;">
                            <label>Field Type</label>
                            <select wire:model.defer="additional_fields.{{ $index }}.type">
                                <option value="text">Text</option>
                                <option value="number">Number</option>
                                <option value="date">Date</option>
                                <option value="textarea">Textarea</option>
                                <option value="select">Select</option>
                            </select>
                        </div>
                        <div class="st-fg" style="flex: 1; margin-bottom: 0;">
                            <label>Booking Form</label>
                            <select wire:model.defer="additional_fields.{{ $index }}.show_in_booking">
                                <option value="1">Display</option>
                                <option value="0">Hide</option>
                            </select>
                        </div>
                        <div class="st-fg" style="flex: 1; margin-bottom: 0;">
                            <label>Invoice</label>
                            <select wire:model.defer="additional_fields.{{ $index }}.show_in_invoice">
                                <option value="1">Display</option>
                                <option value="0">Hide</option>
                            </select>
                        </div>
                        <div class="st-fg" style="flex: 1; margin-bottom: 0;">
                            <label>Customer</label>
                            <select wire:model.defer="additional_fields.{{ $index }}.show_for_customer">
                                <option value="1">Display</option>
                                <option value="0">Hide</option>
                            </select>
                        </div>
                        <button type="button" wire:click="removeField({{ $index }})"
                            style="flex-shrink: 0; background: var(--st-danger-soft); color: var(--st-danger); border: none; border-radius: 6px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; cursor: pointer; margin-bottom: 0;"
                            title="Remove field">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                @endforeach

                @if (count($additional_fields) < 10)
                    <button type="button" wire:click="addField"
                        style="margin-top: .75rem; display: inline-flex; align-items: center; gap: .35rem; padding: .45rem .85rem; font-size: .78rem; font-weight: 600; color: var(--st-brand); background: var(--st-brand-soft); border: 1px solid rgba(14,165,233,.2); border-radius: var(--st-radius-sm); cursor: pointer;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Add Field
                    </button>
                @endif
            </div>
        </div>

        {{-- ── Save ── --}}
        <div class="st-save-bar">
            <button type="submit" class="st-btn-save" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Save Devices & Brands Settings</span>
                <span wire:loading wire:target="save" class="st-spinner"></span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </form>
</div>
