{{-- Signature Workflow Settings Section — Livewire Component --}}
<div>
    <form wire:submit.prevent="save">
        {{-- ── Pickup Signature ── --}}
        <div class="st-section" x-data="{ open: true }">
            <div class="st-section-header" @click="open = !open">
                <h3 class="st-section-title">
                    <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                    Pickup Signature
                </h3>
                <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
            <div class="st-section-body" x-show="open" x-collapse>
                <div class="st-option-card">
                    <div class="st-option-control">
                        <label class="st-toggle">
                            <input type="checkbox" wire:model.defer="pickup_enabled" />
                            <span class="st-toggle-track"></span>
                        </label>
                    </div>
                    <div class="st-option-body">
                        <label class="st-option-title">Enable Pickup Signature</label>
                        <p class="st-option-desc">Require customer signature when picking up their device</p>
                    </div>
                </div>

                <div class="st-grid st-grid-2" style="margin-top: .75rem;">
                    <div class="st-fg">
                        <label for="pickup_trigger_status">Trigger on Status</label>
                        <select id="pickup_trigger_status" wire:model.defer="pickup_trigger_status">
                            <option value="">— Select Status —</option>
                            @foreach ($statusOptions as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="st-help">Job status that triggers the pickup signature request</p>
                    </div>
                    <div class="st-fg">
                        <label for="pickup_after_status">Status After Submission</label>
                        <select id="pickup_after_status" wire:model.defer="pickup_after_status">
                            <option value="">— Select Status —</option>
                            @foreach ($statusOptions as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="st-help">Status to set after customer submits their signature</p>
                    </div>
                </div>

                <div class="st-fg">
                    <label for="pickup_email_subject">Email Subject</label>
                    <input type="text" id="pickup_email_subject" wire:model.defer="pickup_email_subject"
                           placeholder="Please sign for your pickup" />
                </div>

                <div class="st-fg">
                    <label for="pickup_email_template">Email Template</label>
                    <textarea id="pickup_email_template" wire:model.defer="pickup_email_template" rows="4"
                              placeholder="Email body sent when pickup signature is requested..."></textarea>
                    <p class="st-help">Keywords: @{{pickup_signature_url}}, @{{job_id}}, @{{customer_device_label}}, @{{case_number}}, @{{customer_full_name}}, @{{order_invoice_details}}</p>
                </div>

                <div class="st-fg">
                    <label for="pickup_sms_text">SMS Text</label>
                    <textarea id="pickup_sms_text" wire:model.defer="pickup_sms_text" rows="3"
                              placeholder="SMS message for pickup signature request..."></textarea>
                    <p class="st-help">Same keywords as email template above</p>
                </div>
            </div>
        </div>

        {{-- ── Delivery Signature ── --}}
        <div class="st-section" x-data="{ open: true }">
            <div class="st-section-header" @click="open = !open">
                <h3 class="st-section-title">
                    <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.029-.504 .899-1.107l-1.034-4.827A3.247 3.247 0 0 0 17.993 9H15.75m-7.5 9v-7.5m7.5 0v-1.5m0 1.5h-7.5"/></svg>
                    Delivery Signature
                </h3>
                <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
            <div class="st-section-body" x-show="open" x-collapse>
                <div class="st-option-card">
                    <div class="st-option-control">
                        <label class="st-toggle">
                            <input type="checkbox" wire:model.defer="delivery_enabled" />
                            <span class="st-toggle-track"></span>
                        </label>
                    </div>
                    <div class="st-option-body">
                        <label class="st-option-title">Enable Delivery Signature</label>
                        <p class="st-option-desc">Require customer signature when delivering their device</p>
                    </div>
                </div>

                <div class="st-grid st-grid-2" style="margin-top: .75rem;">
                    <div class="st-fg">
                        <label for="delivery_trigger_status">Trigger on Status</label>
                        <select id="delivery_trigger_status" wire:model.defer="delivery_trigger_status">
                            <option value="">— Select Status —</option>
                            @foreach ($statusOptions as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="st-help">Job status that triggers the delivery signature request</p>
                    </div>
                    <div class="st-fg">
                        <label for="delivery_after_status">Status After Submission</label>
                        <select id="delivery_after_status" wire:model.defer="delivery_after_status">
                            <option value="">— Select Status —</option>
                            @foreach ($statusOptions as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="st-help">Status to set after customer submits their signature</p>
                    </div>
                </div>

                <div class="st-fg">
                    <label for="delivery_email_subject">Email Subject</label>
                    <input type="text" id="delivery_email_subject" wire:model.defer="delivery_email_subject"
                           placeholder="Please sign for your delivery" />
                </div>

                <div class="st-fg">
                    <label for="delivery_email_template">Email Template</label>
                    <textarea id="delivery_email_template" wire:model.defer="delivery_email_template" rows="4"
                              placeholder="Email body sent when delivery signature is requested..."></textarea>
                    <p class="st-help">Keywords: @{{delivery_signature_url}}, @{{job_id}}, @{{customer_device_label}}, @{{case_number}}, @{{customer_full_name}}, @{{order_invoice_details}}</p>
                </div>

                <div class="st-fg">
                    <label for="delivery_sms_text">SMS Text</label>
                    <textarea id="delivery_sms_text" wire:model.defer="delivery_sms_text" rows="3"
                              placeholder="SMS message for delivery signature request..."></textarea>
                    <p class="st-help">Same keywords as email template above</p>
                </div>
            </div>
        </div>

        {{-- ── Save ── --}}
        <div class="st-save-bar">
            <button type="submit" class="st-btn-save" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Save Signature Settings</span>
                <span wire:loading wire:target="save" class="st-spinner"></span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </form>
</div>
