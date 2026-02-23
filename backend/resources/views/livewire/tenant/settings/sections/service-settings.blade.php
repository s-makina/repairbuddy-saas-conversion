{{-- Service Settings Section — Livewire Component --}}
<div>
    <form wire:submit.prevent="save">
        <div class="st-section" x-data="{ open: true }">
            <div class="st-section-header" @click="open = !open">
                <h3 class="st-section-title">
                    <svg class="st-sec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z"/></svg>
                    Service Page Settings
                </h3>
                <svg class="st-section-chevron" :class="{ 'open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </div>
            <div class="st-section-body" x-show="open" x-collapse>
                <div class="st-fg">
                    <label for="sidebar_description">Service Sidebar Description</label>
                    <textarea id="sidebar_description" wire:model.defer="sidebar_description" rows="4"
                              placeholder="Description shown in the service page sidebar..."></textarea>
                    <p class="st-help">Text shown in the single service price sidebar</p>
                </div>

                <div class="st-option-card">
                    <div class="st-option-control">
                        <label class="st-toggle">
                            <input type="checkbox" wire:model.defer="disable_booking_on_service_page" />
                            <span class="st-toggle-track"></span>
                        </label>
                    </div>
                    <div class="st-option-body">
                        <label class="st-option-title">Disable Booking on Service Page</label>
                        <p class="st-option-desc">Hide the booking form from individual service pages</p>
                    </div>
                </div>

                <div class="st-grid st-grid-2" style="margin-top: .75rem;">
                    <div class="st-fg">
                        <label for="booking_heading">Booking Heading</label>
                        <input type="text" id="booking_heading" wire:model.defer="booking_heading"
                               placeholder="Book a Repair" />
                        <p class="st-help">Heading text for the booking form on service pages</p>
                    </div>
                    <div class="st-fg">
                        <label for="booking_form_type">Booking Form Type</label>
                        <select id="booking_form_type" wire:model.defer="booking_form_type">
                            @foreach ($formTypeOptions as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="st-help">Choose the booking form variant for service pages</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Save ── --}}
        <div class="st-save-bar">
            <button type="submit" class="st-btn-save" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Save Service Settings</span>
                <span wire:loading wire:target="save" class="st-spinner"></span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </form>
</div>
