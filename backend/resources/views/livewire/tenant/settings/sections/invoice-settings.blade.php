{{-- Invoice & Reports Settings Section — Livewire Component --}}
<div>
    <form wire:submit.prevent="save">
        {{-- ── Print Invoice Settings ── --}}
        <div class="st-section" x-data="{ open: true }">
            <div class="st-section-header" @click="open = !open">
                <h3 class="st-section-title">
                    <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"/></svg>
                    Print Invoice Settings
                </h3>
                <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
            <div class="st-section-body" x-show="open" x-collapse>
                <div class="st-option-card">
                    <div class="st-option-control">
                        <label class="st-toggle">
                            <input type="checkbox" wire:model.defer="add_qr_code" />
                            <span class="st-toggle-track"></span>
                        </label>
                    </div>
                    <div class="st-option-body">
                        <label class="st-option-title">Add QR Code to Invoice</label>
                        <p class="st-option-desc">Include a QR code on printed invoices for quick status check</p>
                    </div>
                </div>

                <div class="st-fg" style="margin-top: 1rem;">
                    <label for="invoice_footer_message">Invoice Footer Message</label>
                    <input type="text" id="invoice_footer_message" wire:model.defer="invoice_footer_message"
                           placeholder="Thank you for your business!" />
                    <p class="st-help">Message displayed at the bottom of printed invoices</p>
                </div>

                <div class="st-fg">
                    <label for="invoice_type">Invoice Print Type</label>
                    <select id="invoice_type" wire:model.defer="invoice_type">
                        @foreach ($invoiceTypeOptions as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="st-help">Choose how items are grouped on printed invoices</p>
                </div>

                {{-- Display dates on invoices --}}
                <div style="margin-top: 1rem; padding: .75rem 1rem; background: var(--st-bg); border: 1px solid var(--st-border); border-radius: 8px;">
                    <p style="font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--st-text-3); margin: 0 0 .65rem;">Display Dates on Invoice</p>

                    <div class="st-option-card">
                        <div class="st-option-control">
                            <label class="st-toggle">
                                <input type="checkbox" wire:model.defer="show_pickup_date" />
                                <span class="st-toggle-track"></span>
                            </label>
                        </div>
                        <div class="st-option-body">
                            <label class="st-option-title">Pickup Date</label>
                            <p class="st-option-desc">Show the pickup/created date on invoices</p>
                        </div>
                    </div>

                    <div class="st-option-card">
                        <div class="st-option-control">
                            <label class="st-toggle">
                                <input type="checkbox" wire:model.defer="show_delivery_date" />
                                <span class="st-toggle-track"></span>
                            </label>
                        </div>
                        <div class="st-option-body">
                            <label class="st-option-title">Delivery Date</label>
                            <p class="st-option-desc">Show the delivery date on invoices</p>
                        </div>
                    </div>

                    <div class="st-option-card">
                        <div class="st-option-control">
                            <label class="st-toggle">
                                <input type="checkbox" wire:model.defer="show_next_service_date" />
                                <span class="st-toggle-track"></span>
                            </label>
                        </div>
                        <div class="st-option-body">
                            <label class="st-option-title">Next Service Date</label>
                            <p class="st-option-desc">Show the next service date on invoices</p>
                        </div>
                    </div>
                </div>

                <div class="st-fg" style="margin-top: 1rem;">
                    <label for="invoice_disclaimer">Terms & Conditions / Disclaimer</label>
                    <textarea id="invoice_disclaimer" wire:model.defer="invoice_disclaimer" rows="4"
                              placeholder="Enter invoice terms and conditions..."></textarea>
                    <p class="st-help">Displayed on printed invoices below the line items</p>
                </div>
            </div>
        </div>

        {{-- ── Repair Order Settings ── --}}
        <div class="st-section" x-data="{ open: true }">
            <div class="st-section-header" @click="open = !open">
                <h3 class="st-section-title">
                    <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                    Repair Order Settings
                </h3>
                <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
            <div class="st-section-body" x-show="open" x-collapse>
                <div class="st-grid st-grid-2">
                    <div class="st-fg">
                        <label for="repair_order_type">Repair Order Type</label>
                        <select id="repair_order_type" wire:model.defer="repair_order_type">
                            @foreach ($repairOrderTypeOptions as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="st-fg">
                        <label for="repair_order_print_size">Print Size</label>
                        <select id="repair_order_print_size" wire:model.defer="repair_order_print_size">
                            @foreach ($printSizeOptions as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="st-fg">
                    <label for="repair_order_terms">Terms & Conditions URL</label>
                    <input type="url" id="repair_order_terms" wire:model.defer="repair_order_terms"
                           placeholder="https://example.com/terms" />
                    <p class="st-help">Link to your terms and conditions page</p>
                </div>

                <div class="st-fg">
                    <label for="repair_order_footer">Repair Order Footer Message</label>
                    <input type="text" id="repair_order_footer" wire:model.defer="repair_order_footer"
                           placeholder="Footer message for repair orders" />
                </div>

                <div class="st-option-card">
                    <div class="st-option-control">
                        <label class="st-toggle">
                            <input type="checkbox" wire:model.defer="display_business_address" />
                            <span class="st-toggle-track"></span>
                        </label>
                    </div>
                    <div class="st-option-body">
                        <label class="st-option-title">Display Business Address Details</label>
                        <p class="st-option-desc">Show full business address on repair orders</p>
                    </div>
                </div>

                <div class="st-option-card">
                    <div class="st-option-control">
                        <label class="st-toggle">
                            <input type="checkbox" wire:model.defer="display_customer_email" />
                            <span class="st-toggle-track"></span>
                        </label>
                    </div>
                    <div class="st-option-body">
                        <label class="st-option-title">Display Customer Email & Address Details</label>
                        <p class="st-option-desc">Show customer contact information on repair orders</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Save ── --}}
        <div class="st-save-bar">
            <button type="submit" class="st-btn-save" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Save Invoice Settings</span>
                <span wire:loading wire:target="save" class="st-spinner"></span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </form>
</div>
